<?php

namespace Tests\Feature;

use App\Livewire\GestionPrestamos;
use App\Models\Equipo;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OperacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_loan_and_return_preserve_history_and_show_it_in_the_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']));
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $this->travelTo(now()->startOfMinute());

        $panel = Livewire::test(GestionPrestamos::class)
            ->set('equipo_id', $equipo->id)->set('estudiante_id', $student->id)
            ->set('observaciones', '<script>alert(1)</script>')->call('registrarPrestamo')
            ->assertSet('mensajeError', '')->assertSee('Préstamo registrado correctamente.')
            ->assertDontSee('<script>alert(1)</script>', false);
        $prestamo = $equipo->prestamos()->firstOrFail();
        $this->assertSame('prestado', $equipo->fresh()->estado);
        $this->assertNull($prestamo->fecha_devolucion);

        $this->travel(1)->hour();
        $panel->call('registrarDevolucion', $prestamo->id)->assertSet('mensajeError', '')
            ->assertSee('Historial de devoluciones')->assertSee($equipo->codigo);

        $this->assertSame('disponible', $equipo->fresh()->estado);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $prestamo->fresh()->fecha_devolucion->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('prestamos', 1);
    }

    public function test_stale_form_cannot_register_a_second_active_loan(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']));
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $panel = Livewire::test(GestionPrestamos::class)->set('equipo_id', $equipo->id)->set('estudiante_id', $student->id);
        $equipo->prestamos()->create(['estudiante_id' => $student->id]);
        $equipo->update(['estado' => 'prestado']);

        $panel->call('registrarPrestamo')->assertSet('mensajeError', 'El equipo ya no está disponible. Actualiza la selección.');

        $this->assertDatabaseCount('prestamos', 1);
        $this->assertSame('prestado', $equipo->fresh()->estado);
    }

    public function test_recipient_must_be_a_student(): void
    {
        $encargado = User::factory()->create(['role' => 'encargado']);
        $this->actingAs($encargado);
        $equipo = Equipo::factory()->create();

        Livewire::test(GestionPrestamos::class)->set('equipo_id', $equipo->id)
            ->set('estudiante_id', $encargado->id)->call('registrarPrestamo')->assertHasErrors('estudiante_id');

        $this->assertDatabaseCount('prestamos', 0);
        $this->assertSame('disponible', $equipo->fresh()->estado);
    }

    public function test_returning_an_old_loan_cannot_release_a_new_loan(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']));
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => 'prestado']);
        $old = $equipo->prestamos()->create(['estudiante_id' => $student->id, 'fecha_devolucion' => now()->subDay()]);
        $active = $equipo->prestamos()->create(['estudiante_id' => $student->id]);

        Livewire::test(GestionPrestamos::class)->call('registrarDevolucion', $old->id)
            ->assertSet('mensajeError', 'Este préstamo ya tiene una devolución registrada.');

        $this->assertNull($active->fresh()->fecha_devolucion);
        $this->assertSame($old->fecha_devolucion->format('Y-m-d H:i:s'), $old->fresh()->fecha_devolucion->format('Y-m-d H:i:s'));
        $this->assertSame('prestado', $equipo->fresh()->estado);
    }

    public function test_return_keeps_equipment_reserved_when_an_approved_reservation_exists(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']));
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => 'prestado']);
        $loan = $equipo->prestamos()->create(['estudiante_id' => $student->id]);
        $equipo->reservas()->create([
            'estudiante_id' => $student->id, 'fecha_inicio' => now()->addDay(),
            'fecha_fin' => now()->addDay()->addHour(), 'estado' => Reserva::APROBADA,
        ]);

        Livewire::test(GestionPrestamos::class)->call('registrarDevolucion', $loan->id)->assertSet('mensajeError', '');

        $this->assertSame('reservado', $equipo->fresh()->estado);
    }

    public function test_account_with_history_cannot_be_deleted(): void
    {
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $equipo->prestamos()->create(['estudiante_id' => $student->id]);
        $this->actingAs($student);

        Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')->assertHasErrors('password');

        $this->assertModelExists($student);
        $this->assertDatabaseCount('prestamos', 1);
        $this->assertAuthenticatedAs($student);
    }

    public function test_permissions_are_rechecked_when_role_changes_after_mount(): void
    {
        $encargado = User::factory()->create(['role' => 'encargado']);
        $this->actingAs($encargado);
        $panel = Livewire::test(GestionPrestamos::class);
        $encargado->update(['role' => 'estudiante']);

        $panel->call('registrarPrestamo')->assertForbidden();

        $this->assertDatabaseCount('prestamos', 0);
    }

    public static function forbiddenRoles(): array
    {
        return ['student' => ['estudiante'], 'unknown' => ['otro']];
    }

    #[DataProvider('forbiddenRoles')]
    public function test_non_managers_cannot_mount_loan_management(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]));

        Livewire::test(GestionPrestamos::class)->assertForbidden();
    }

    public function test_unverified_users_cannot_open_internal_panels(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'encargado']);
        $this->actingAs($user);

        $this->get(route('dashboard.encargado'))->assertRedirect(route('verification.notice'));
        Livewire::test(GestionPrestamos::class)->assertForbidden();
    }
}
