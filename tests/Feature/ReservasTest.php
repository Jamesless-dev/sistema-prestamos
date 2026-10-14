<?php

namespace Tests\Feature;

use App\Livewire\GestionPrestamos;
use App\Livewire\GestionReservas;
use App\Livewire\ReservarEquipo;
use App\Models\Equipo;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
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
        $this->travelTo(now()->setDate(2026, 10, 14)->setTime(8, 0, 17));
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create();
        $this->actingAs($student);
        $fecha = now()->addDay()->format('Y-m-d');

        $panel = Livewire::test(ReservarEquipo::class)->set('equipo_id', $equipo->id)->set('fecha', $fecha)
            ->set('hora_inicio', '10:00')->set('hora_fin', '11:00')->call('reservar')->assertSet('mensajeError', '');
        $this->assertDatabaseHas('reservas', ['estudiante_id' => $student->id, 'equipo_id' => $equipo->id, 'estado' => 'pendiente']);
        $reserva = Reserva::firstOrFail();
        $this->assertSame('2026-10-15 10:00:00', $reserva->fecha_inicio->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-15 11:00:00', $reserva->fecha_fin->format('Y-m-d H:i:s'));
        $this->travel(30)->seconds();
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

    public static function invalidReservationFields(): array
    {
        return [
            'fecha pasada' => ['fecha', '2026-10-13', 'after_or_equal'],
            'fecha imposible' => ['fecha', '2026-02-30', 'date_format'],
            'inicio mal formado' => ['hora_inicio', '25:00', 'date_format'],
            'fin mal formado' => ['hora_fin', '30:00', 'date_format'],
            'equipo inexistente' => ['equipo_id', 999999, 'exists'],
            'motivo largo' => ['observaciones', str_repeat('x', 256), 'max'],
        ];
    }

    #[DataProvider('invalidReservationFields')]
    public function test_invalid_reservations_are_not_saved(string $field, mixed $value, string $rule): void
    {
        $this->travelTo(now()->setDate(2026, 10, 14)->setTime(8, 0));
        $equipo = Equipo::factory()->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(ReservarEquipo::class)->set('equipo_id', $equipo->id)->set('fecha', '2026-10-15')
            ->set('hora_inicio', '10:00')->set('hora_fin', '11:00')->set($field, $value)
            ->call('reservar')->assertHasErrors([$field => $rule]);

        $this->assertDatabaseCount('reservas', 0);
        $this->assertSame('disponible', $equipo->fresh()->estado);
    }

    public function test_reservation_cannot_start_at_the_current_time(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 14)->setTime(10, 0));
        $equipo = Equipo::factory()->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(ReservarEquipo::class)->set('equipo_id', $equipo->id)->set('fecha', '2026-10-14')
            ->set('hora_inicio', '10:00')->set('hora_fin', '11:00')->call('reservar')
            ->assertHasErrors('hora_inicio')->assertSee('La reserva debe ser para una fecha y hora futuras.');

        $this->assertDatabaseCount('reservas', 0);
    }

    public static function deliveryTimes(): array
    {
        return ['inicio inclusive' => ['10:00:00', true], 'fin exclusive' => ['11:00:00', false]];
    }

    #[DataProvider('deliveryTimes')]
    public function test_delivery_respects_the_exact_reservation_boundaries(string $time, bool $allowed): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15)->setTimeFromTimeString($time));
        $equipo = Equipo::factory()->create(['estado' => 'reservado']);
        $reserva = $equipo->reservas()->create([
            'estudiante_id' => User::factory()->create()->id,
            'fecha_inicio' => '2026-10-15 10:00:00', 'fecha_fin' => '2026-10-15 11:00:00', 'estado' => 'aprobada',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'encargado']));

        Livewire::test(GestionReservas::class)->call('entregar', $reserva->id)
            ->assertSet('mensajeError', $allowed ? '' : 'La entrega debe realizarse dentro del horario reservado.');

        $this->assertDatabaseCount('prestamos', $allowed ? 1 : 0);
        $this->assertSame($allowed ? 'finalizada' : 'aprobada', $reserva->fresh()->estado);
        $this->assertSame($allowed ? 'prestado' : 'reservado', $equipo->fresh()->estado);
    }

    public function test_cancelling_one_approved_reservation_preserves_other_approved_slots(): void
    {
        $this->freezeTime();
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => 'reservado']);
        $first = $this->reserva($equipo, $student, 'aprobada');
        $second = $this->reserva($equipo, $student, 'aprobada');
        $second->update(['fecha_inicio' => $first->fecha_fin, 'fecha_fin' => $first->fecha_fin->copy()->addHour()]);
        $this->actingAs($student);

        Livewire::test(ReservarEquipo::class)->call('cancelar', $first->id)->assertSet('mensajeError', '');

        $this->assertSame('cancelada', $first->fresh()->estado);
        $this->assertSame('aprobada', $second->fresh()->estado);
        $this->assertSame('reservado', $equipo->fresh()->estado);
    }

    public function test_borrowed_equipment_cannot_be_delivered_until_the_active_loan_is_returned(): void
    {
        $this->freezeTime();
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => 'prestado']);
        $loan = $equipo->prestamos()->create(['estudiante_id' => $student->id]);
        $reserva = $this->reserva($equipo, User::factory()->create(), 'aprobada');
        $this->travelTo($reserva->fecha_inicio);
        $this->actingAs(User::factory()->create(['role' => 'encargado']));

        $panel = Livewire::test(GestionReservas::class)->call('entregar', $reserva->id)
            ->assertSet('mensajeError', 'El equipo todavía está prestado a otra persona.');
        $this->assertDatabaseCount('prestamos', 1);
        $this->assertSame('aprobada', $reserva->fresh()->estado);
        Livewire::test(GestionPrestamos::class)->call('registrarDevolucion', $loan->id)->assertSet('mensajeError', '');
        $panel->call('entregar', $reserva->id)->assertSet('mensajeError', '');

        $this->assertDatabaseCount('prestamos', 2);
        $this->assertSame('prestado', $equipo->fresh()->estado);
        $this->assertDatabaseHas('prestamos', ['equipo_id' => $equipo->id, 'estudiante_id' => $reserva->estudiante_id, 'fecha_devolucion' => null]);
    }

    public function test_expiration_preserves_active_loans_and_future_reservations_and_is_repeatable(): void
    {
        $this->freezeTime();
        $student = User::factory()->create();
        $equipo = Equipo::factory()->create(['estado' => 'prestado']);
        $loan = $equipo->prestamos()->create(['estudiante_id' => $student->id]);
        $expired = $this->reserva($equipo, $student);
        $expired->update(['fecha_inicio' => now()->subHours(2), 'fecha_fin' => now()]);
        $future = $this->reserva($equipo, $student, 'aprobada');

        $this->artisan('reservas:expirar')->assertSuccessful();
        $this->artisan('reservas:expirar')->assertSuccessful();

        $this->assertSame('cancelada', $expired->fresh()->estado);
        $this->assertSame('aprobada', $future->fresh()->estado);
        $this->assertSame('prestado', $equipo->fresh()->estado);
        $this->assertNull($loan->fresh()->fecha_devolucion);
        $this->assertDatabaseCount('reservas', 2);
    }

    public function test_reservation_delivery_is_rolled_back_if_equipment_cannot_be_saved(): void
    {
        $this->freezeTime();
        $equipo = Equipo::factory()->create(['estado' => 'reservado']);
        $reserva = $this->reserva($equipo, User::factory()->create(), 'aprobada');
        $this->travelTo($reserva->fecha_inicio);
        $this->actingAs(User::factory()->create(['role' => 'encargado']));
        Exceptions::fake();
        Event::listen('eloquent.updating: '.Equipo::class, function (): void {
            throw new \RuntimeException('Fallo simulado en entrega.');
        });

        Livewire::test(GestionReservas::class)->call('entregar', $reserva->id)
            ->assertSet('mensajeError', 'No se pudo procesar la reserva. Actualiza la página e inténtalo de nuevo.');

        Exceptions::assertReported(\RuntimeException::class);
        $this->assertDatabaseCount('prestamos', 0);
        $this->assertSame('aprobada', $reserva->fresh()->estado);
        $this->assertSame('reservado', $equipo->fresh()->estado);
    }

    public function test_reservation_observations_are_escaped_in_the_management_panel(): void
    {
        $student = User::factory()->create();
        $reserva = $this->reserva(Equipo::factory()->create(), $student);
        $reserva->update(['observaciones' => '<script>alert(1)</script>']);

        $this->actingAs(User::factory()->create(['role' => 'encargado']))->get(route('reservas.gestion'))->assertOk()
            ->assertSee($reserva->observaciones)->assertDontSee($reserva->observaciones, false);
    }

    public function test_manager_cannot_approve_after_losing_the_role(): void
    {
        $reserva = $this->reserva(Equipo::factory()->create(), User::factory()->create());
        $manager = User::factory()->create(['role' => 'encargado']);
        $this->actingAs($manager);
        $panel = Livewire::test(GestionReservas::class);
        $manager->update(['role' => 'estudiante']);

        $panel->call('aprobar', $reserva->id)->assertForbidden();

        $this->assertSame('pendiente', $reserva->fresh()->estado);
    }

    public function test_student_cannot_cancel_after_email_verification_is_revoked(): void
    {
        $student = User::factory()->create();
        $reserva = $this->reserva(Equipo::factory()->create(), $student);
        $this->actingAs($student);
        $panel = Livewire::test(ReservarEquipo::class);
        $student->forceFill(['email_verified_at' => null])->save();

        $panel->call('cancelar', $reserva->id)->assertForbidden();

        $this->assertSame('pendiente', $reserva->fresh()->estado);
    }

    public function test_past_pending_reservation_cannot_be_approved(): void
    {
        $this->freezeTime();
        $equipo = Equipo::factory()->create();
        $reserva = $this->reserva($equipo, User::factory()->create());
        $this->travelTo($reserva->fecha_inicio);
        $this->actingAs(User::factory()->create(['role' => 'encargado']));

        Livewire::test(GestionReservas::class)->call('aprobar', $reserva->id)
            ->assertSet('mensajeError', 'No se puede aprobar: el horario de inicio ya pasó.');

        $this->assertSame('pendiente', $reserva->fresh()->estado);
        $this->assertSame('disponible', $equipo->fresh()->estado);
    }
}
