<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActualizacionReservasTest extends TestCase
{
    use DatabaseMigrations;

    private const REPARACION = '2026_10_14_104349_ensure_reservas_table_exists';

    private function marcarReparacionPendiente(): void
    {
        DB::table('migrations')->where('migration', self::REPARACION)->delete();
    }

    public function test_upgrade_creates_missing_reservations_without_deleting_existing_records(): void
    {
        $estudiante = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => 'prestado']);
        $prestamo = $equipo->prestamos()->create(['estudiante_id' => $estudiante->id]);
        Schema::drop('reservas');
        $this->marcarReparacionPendiente();
        $this->assertDatabaseHas('migrations', ['migration' => '2026_10_13_000000_create_reservas_table']);

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('reservas'));
        $this->assertModelExists($estudiante);
        $this->assertModelExists($equipo);
        $this->assertModelExists($prestamo);
        $this->assertSame('prestado', $equipo->fresh()->estado);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('equipos', 1);
        $this->assertDatabaseCount('prestamos', 1);
    }

    public function test_upgrade_preserves_reservations_when_the_table_already_exists(): void
    {
        $estudiante = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $reserva = $equipo->reservas()->create([
            'estudiante_id' => $estudiante->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay()->addHour(),
            'observaciones' => 'Solicitud existente',
        ]);
        $this->marcarReparacionPendiente();

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $this->assertModelExists($reserva);
        $this->assertDatabaseHas('reservas', ['id' => $reserva->id, 'observaciones' => 'Solicitud existente']);
        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_rolling_back_only_the_repair_preserves_reservation_history(): void
    {
        $estudiante = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $reserva = $equipo->reservas()->create([
            'estudiante_id' => $estudiante->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay()->addHour(),
        ]);

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/'.self::REPARACION.'.php',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertModelExists($reserva);
    }

    public function test_diagnostic_detects_a_missing_table_even_when_connection_works(): void
    {
        Schema::drop('reservas');

        $this->artisan('entorno:verificar', ['--conexion' => true, '--esquema' => true])
            ->expectsOutputToContain('Conexión a la base de datos correcta.')
            ->expectsOutputToContain('Faltan tablas del proyecto: reservas.')
            ->expectsOutputToContain('composer run actualizar')
            ->assertFailed();
    }

    public function test_diagnostic_accepts_a_complete_database(): void
    {
        $this->artisan('entorno:verificar', ['--esquema' => true])
            ->expectsOutputToContain('Las tablas del proyecto están disponibles.')
            ->assertSuccessful();
    }

    public function test_reservations_button_works_after_repairing_the_missing_table(): void
    {
        $this->app['env'] = 'local';
        $encargado = User::factory()->create(['role' => 'encargado']);
        $this->actingAs($encargado);
        Schema::drop('reservas');
        $this->marcarReparacionPendiente();

        $this->get(route('reservas.gestion'))->assertStatus(503)
            ->assertSee('falta la tabla')->assertSee('composer run actualizar');
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $this->get(route('reservas.gestion'))->assertOk()->assertSee('No hay solicitudes pendientes.');
        $this->assertModelExists($encargado);
    }
}
