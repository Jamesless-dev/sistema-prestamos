<?php

namespace Tests\Feature;

use App\Livewire\GestionPrestamos;
use App\Livewire\GestionReservas;
use App\Livewire\ReservarEquipo;
use App\Models\Equipo;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReservasTest extends TestCase
{
    use RefreshDatabase;

    private function reserva(Equipo $equipo, User $student, string $estado = 'pendiente'): Reserva
    {
        return $equipo->reservas()->create([
            'estudiante_id' => $student->id, 'fecha_inicio' => now()->addDay()->startOfHour(),
            'fecha_fin' => now()->addDay()->startOfHour()->addHour(), 'estado' => $estado,
        ]);
    }

    public function test_both_reservation_pages_render_for_their_roles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->get(route('reservas.gestion'))->assertOk()->assertSee('Gestión de Reservas');
        $this->actingAs(User::factory()->create())
            ->get(route('reservas.estudiante'))->assertOk()->assertSee('Reservar un Equipo');
    }

    public function test_student_request_uses_the_authenticated_owner_and_rejects_duplicate_submission(): void
    {
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $this->actingAs($student);
        $fecha = now()->addDay()->format('Y-m-d');

        $panel = Livewire::test(ReservarEquipo::class)->set('equipo_id', $equipo->id)->set('fecha', $fecha)
            ->set('hora_inicio', '10:00')->set('hora_fin', '11:00')->call('reservar')->assertSet('mensajeError', '');
        $this->assertDatabaseHas('reservas', ['estudiante_id' => $student->id, 'equipo_id' => $equipo->id, 'estado' => 'pendiente']);
        $panel->set('fecha', $fecha)->set('hora_inicio', '10:00')->set('hora_fin', '11:00')->call('reservar')
            ->assertSet('mensajeError', 'Ya tienes una solicitud pendiente para ese equipo y horario.');

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_approval_delivery_and_return_form_a_complete_flow(): void
    {
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $reserva = $this->reserva($equipo, $student);
        $this->actingAs(User::factory()->create(['role' => 'encargado']));

        $panel = Livewire::test(GestionReservas::class)->call('aprobar', $reserva->id)->assertSet('mensajeError', '');
        $this->assertSame('reservado', $equipo->fresh()->estado);
        $panel->call('entregar', $reserva->id)->assertSet('mensajeError', 'La entrega debe realizarse dentro del horario reservado.');
        $this->assertDatabaseCount('prestamos', 0);
        $this->travelTo($reserva->fecha_inicio->copy()->addMinute());
        $panel->call('entregar', $reserva->id)->assertSet('mensajeError', '');

        $this->assertSame('finalizada', $reserva->fresh()->estado);
        $this->assertSame('prestado', $equipo->fresh()->estado);
        $this->assertDatabaseCount('prestamos', 1);
        $panel->call('entregar', $reserva->id)->assertSet('mensajeError', 'Solo se pueden entregar reservas aprobadas.');
        $this->assertDatabaseCount('prestamos', 1);

        Livewire::test(GestionPrestamos::class)
            ->call('registrarDevolucion', $equipo->prestamos()->firstOrFail()->id)->assertSet('mensajeError', '');
        $this->assertSame('disponible', $equipo->fresh()->estado);
        $this->assertSame('finalizada', $reserva->fresh()->estado);
    }

    public function test_approval_rejects_overlapping_reservations_but_accepts_adjacent_hours(): void
    {
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $first = $this->reserva($equipo, $student);
        $overlap = $this->reserva($equipo, $student);
        $adjacent = $this->reserva($equipo, $student);
        $adjacent->update(['fecha_inicio' => $first->fecha_fin, 'fecha_fin' => $first->fecha_fin->copy()->addHour()]);
        $this->actingAs(User::factory()->create(['role' => 'encargado']));

        $panel = Livewire::test(GestionReservas::class)->call('aprobar', $first->id)->assertSet('mensajeError', '');
        $panel->call('aprobar', $overlap->id)->assertSet('mensajeError', 'El equipo tiene otra reserva aprobada en ese horario.');
        $panel->call('aprobar', $adjacent->id)->assertSet('mensajeError', '');

        $this->assertSame('pendiente', $overlap->fresh()->estado);
        $this->assertSame('aprobada', $adjacent->fresh()->estado);
    }

    public function test_student_cannot_cancel_another_students_reservation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $reserva = $this->reserva($equipo, $owner);
        $this->actingAs($other);

        Livewire::test(ReservarEquipo::class)->call('cancelar', $reserva->id)->assertNotFound();

        $this->assertSame('pendiente', $reserva->fresh()->estado);
    }

    public function test_cancelling_a_reservation_does_not_release_a_borrowed_equipment(): void
    {
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => 'prestado']);
        $equipo->prestamos()->create(['estudiante_id' => $student->id]);
        $reserva = $this->reserva($equipo, $student, 'aprobada');
        $this->actingAs($student);

        Livewire::test(ReservarEquipo::class)->call('cancelar', $reserva->id)->assertSet('mensajeError', '');

        $this->assertSame('cancelada', $reserva->fresh()->estado);
        $this->assertSame('prestado', $equipo->fresh()->estado);
    }

    public function test_expired_reservations_are_closed_and_equipment_is_released(): void
    {
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => 'reservado']);
        $reserva = $this->reserva($equipo, $student, 'aprobada');
        $this->travelTo($reserva->fecha_fin->copy()->addMinute());

        $this->artisan('reservas:expirar')->assertSuccessful();

        $this->assertSame('cancelada', $reserva->fresh()->estado);
        $this->assertSame('disponible', $equipo->fresh()->estado);
    }

    public function test_rejected_reservations_cannot_be_approved(): void
    {
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $reserva = $this->reserva($equipo, $student);
        $this->actingAs(User::factory()->create(['role' => 'encargado']));

        $panel = Livewire::test(GestionReservas::class)->call('rechazar', $reserva->id)->assertSet('mensajeError', '');
        $panel->call('aprobar', $reserva->id)->assertSet('mensajeError', 'Esta reserva ya fue procesada.');

        $this->assertSame('rechazada', $reserva->fresh()->estado);
    }

    public static function invalidHours(): array
    {
        return ['equal' => ['10:00', '10:00'], 'inverted' => ['11:00', '10:00']];
    }

    #[DataProvider('invalidHours')]
    public function test_invalid_time_ranges_are_rejected(string $inicio, string $fin): void
    {
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $this->actingAs($student);

        Livewire::test(ReservarEquipo::class)->set('equipo_id', $equipo->id)->set('fecha', now()->addDay()->format('Y-m-d'))
            ->set('hora_inicio', $inicio)->set('hora_fin', $fin)->call('reservar')->assertHasErrors('hora_fin');

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_roles_cannot_mount_the_other_reservation_component(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(GestionReservas::class)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'encargado']));
        Livewire::test(ReservarEquipo::class)->assertForbidden();
    }
}
