<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BaseDatosTest extends TestCase
{
    use DatabaseMigrations;

    public function test_seeders_create_required_demo_data_without_duplicates_or_resetting_loans(): void
    {
        $this->seed();
        $encargado = User::where('email', 'encargado@tecsup.edu.pe')->firstOrFail();
        $estudiante = User::where('email', 'estudiante1@tecsup.edu.pe')->firstOrFail();
        $equipo = Equipo::where('codigo', 'EQ-001')->firstOrFail();
        $equipo->prestamos()->create(['estudiante_id' => $estudiante->id]);
        $equipo->update(['estado' => 'prestado']);

        $this->seed();

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('equipos', 8);
        $this->assertSame(1, User::where('role', 'encargado')->count());
        $this->assertSame(2, User::where('role', 'estudiante')->count());
        $this->assertTrue(Hash::check('Admin123', $encargado->password));
        $this->assertTrue(Hash::check('Estudiante123', $estudiante->password));
        $this->assertTrue($encargado->hasVerifiedEmail());
        $this->assertTrue($estudiante->hasVerifiedEmail());
        $this->assertSame('prestado', $equipo->fresh()->estado);
    }

    public function test_database_prevents_deleting_a_user_with_loan_history(): void
    {
        $user = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $equipo->prestamos()->create(['estudiante_id' => $user->id, 'fecha_devolucion' => now()]);

        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $user->id)->delete();
    }

    public function test_database_rejects_orphan_reservations(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('reservas')->insert([
            'equipo_id' => 999999,
            'estudiante_id' => $user->id,
            'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay()->addHour(),
            'estado' => 'pendiente',
        ]);
    }

    public function test_database_rejects_two_active_loans_for_the_same_equipment(): void
    {
        $user = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $equipo->prestamos()->create(['estudiante_id' => $user->id]);

        $this->expectException(QueryException::class);
        $equipo->prestamos()->create(['estudiante_id' => $user->id]);
    }

    public function test_history_migration_can_be_rolled_back_and_reapplied_without_losing_records(): void
    {
        $user = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $prestamo = $equipo->prestamos()->create(['estudiante_id' => $user->id]);

        $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true])->assertSuccessful();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('prestamos', ['id' => $prestamo->id, 'equipo_id' => $equipo->id]);
    }
}
