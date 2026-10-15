<?php

namespace Tests\Feature;

use App\Livewire\GestionPrestamos;
use App\Livewire\GestionReservas;
use App\Livewire\ReservarEquipo;
use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\HandleRequests;
use Livewire\Volt\Volt;
use Tests\TestCase;

/** Independent audit: assertions describe the expected business safeguards. */
class AuditBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-20 09:00:00'));
    }

    private function manager(): User
    {
        return User::factory()->create(['role' => 'encargado']);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'estudiante']);
    }

    private function reservation(array $data = []): Reserva
    {
        return Reserva::create($data + [
            'equipo_id' => Equipo::factory()->create()->id,
            'estudiante_id' => $this->student()->id,
            'fecha_inicio' => now()->addDay()->setTime(10, 0),
            'fecha_fin' => now()->addDay()->setTime(11, 0),
            'estado' => Reserva::PENDIENTE,
        ]);
    }

    private function loan(Equipo $equipment, User $student): Prestamo
    {
        $equipment->update(['estado' => Equipo::PRESTADO]);

        return Prestamo::create(['equipo_id' => $equipment->id, 'estudiante_id' => $student->id]);
    }

    private function requestReservation(Equipo $equipment, array $data = [])
    {
        $component = Livewire::test(ReservarEquipo::class);
        foreach ($data + ['equipo_id' => $equipment->id, 'fecha' => '2026-10-21', 'hora_inicio' => '10:00', 'hora_fin' => '11:00'] as $key => $value) {
            $component->set($key, $value);
        }

        return $component->call('reservar');
    }

    public function test_home_contains_login_link(): void
    {
        $this->get('/')->assertOk()->assertSee('Iniciar Sesión')->assertSee('href="'.route('login').'"', false);
    }

    public function test_seeding_creates_documented_accounts_and_equipment(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('equipos', 8);
        $this->assertTrue(Hash::check('Admin123', User::where('email', 'encargado@tecsup.edu.pe')->firstOrFail()->password));
    }

    public function test_registration_produces_usable_student_account(): void
    {
        Volt::test('pages.auth.register')->set('name', 'Audit Student')->set('email', 'audit@example.test')
            ->set('password', 'password123')->set('password_confirmation', 'password123')->call('register')->assertHasNoErrors();
        $user = User::where('email', 'audit@example.test')->firstOrFail();
        $this->assertSame('estudiante', $user->role);
        $this->actingAs($user)->get('/dashboard/estudiante')->assertOk();
    }

    public function test_manager_reservations_page_renders(): void
    {
        $this->actingAs($this->manager())->get('/reservas/gestion')->assertOk();
    }

    public function test_student_reservations_page_renders(): void
    {
        $this->actingAs($this->student())->get('/reservas')->assertOk();
    }

    public function test_manager_can_create_valid_loan(): void
    {
        $equipment = Equipo::factory()->create();
        $student = $this->student();
        $this->actingAs($this->manager());
        Livewire::test(GestionPrestamos::class)->set('equipo_id', $equipment->id)->set('estudiante_id', $student->id)->call('registrarPrestamo')->assertHasNoErrors()->assertSet('mensajeError', '');
        $this->assertDatabaseCount('prestamos', 1);
        $this->assertSame('prestado', $equipment->fresh()->estado);
    }

    public function test_loan_requires_fields(): void
    {
        $this->actingAs($this->manager());
        Livewire::test(GestionPrestamos::class)->call('registrarPrestamo')->assertHasErrors(['equipo_id', 'estudiante_id']);
        $this->assertDatabaseCount('prestamos', 0);
    }

    public function test_loan_rejects_nonexistent_equipment(): void
    {
        $student = $this->student();
        $this->actingAs($this->manager());
        Livewire::test(GestionPrestamos::class)->set('equipo_id', 99999)->set('estudiante_id', $student->id)->call('registrarPrestamo')->assertHasErrors('equipo_id');
    }

    public function test_loan_cannot_be_assigned_to_manager(): void
    {
        $manager = $this->manager();
        $equipment = Equipo::factory()->create();
        $this->actingAs($manager);
        Livewire::test(GestionPrestamos::class)->set('equipo_id', $equipment->id)->set('estudiante_id', $manager->id)->call('registrarPrestamo');
        $this->assertDatabaseCount('prestamos', 0);
    }

    public function test_equipment_cannot_have_two_active_loans(): void
    {
        $equipment = Equipo::factory()->create();
        $student = $this->student();
        $this->loan($equipment, $student);
        $this->actingAs($this->manager());
        Livewire::test(GestionPrestamos::class)->set('equipo_id', $equipment->id)->set('estudiante_id', $student->id)->call('registrarPrestamo');
        $this->assertSame(1, Prestamo::whereNull('fecha_devolucion')->count());
    }

    public function test_direct_loan_cannot_bypass_approved_reservation(): void
    {
        $equipment = Equipo::factory()->create(['estado' => Equipo::RESERVADO]);
        $this->reservation(['equipo_id' => $equipment->id, 'estado' => Reserva::APROBADA]);
        $this->actingAs($this->manager());
        Livewire::test(GestionPrestamos::class)->set('equipo_id', $equipment->id)->set('estudiante_id', $this->student()->id)->call('registrarPrestamo');
        $this->assertDatabaseCount('prestamos', 0);
    }

    public function test_manager_can_return_active_loan(): void
    {
        $equipment = Equipo::factory()->create();
        $loan = $this->loan($equipment, $this->student());
        $this->actingAs($this->manager());
        Livewire::test(GestionPrestamos::class)->call('registrarDevolucion', $loan->id)->assertSet('mensajeError', '');
        $this->assertNotNull($loan->fresh()->fecha_devolucion);
        $this->assertSame('disponible', $equipment->fresh()->estado);
    }

    public function test_repeated_return_cannot_release_another_active_loan(): void
    {
        $equipment = Equipo::factory()->create();
        $student = $this->student();
        $oldLoan = $this->loan($equipment, $student);
        $oldLoan->update(['fecha_devolucion' => now()->subHour()]);
        $this->loan($equipment, $student);
        $this->actingAs($this->manager());
        Livewire::test(GestionPrestamos::class)->call('registrarDevolucion', $oldLoan->id);
        $this->assertSame('prestado', $equipment->fresh()->estado);
    }

    public function test_return_preserves_existing_approved_reservations(): void
    {
        $equipment = Equipo::factory()->create();
        $loan = $this->loan($equipment, $this->student());
        $this->reservation(['equipo_id' => $equipment->id, 'estado' => Reserva::APROBADA]);
        $this->actingAs($this->manager());
        Livewire::test(GestionPrestamos::class)->call('registrarDevolucion', $loan->id);
        $this->assertSame('reservado', $equipment->fresh()->estado);
    }

    public function test_student_cannot_replay_old_manager_component_snapshot(): void
    {
        $equipment = Equipo::factory()->create();
        $student = $this->student();
        $this->actingAs($this->manager());
        $html = $this->get('/dashboard/encargado')->assertOk()->getContent();
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);
        $snapshot = null;
        foreach ($matches[1] as $value) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
            if (json_decode($decoded, true)['memo']['name'] === 'gestion-prestamos') {
                $snapshot = $decoded;
            }
        }
        $this->assertNotNull($snapshot);
        $this->actingAs($student);
        $uri = app(HandleRequests::class)->getUpdateUri();
        $this->postJson($uri, ['components' => [[
            'snapshot' => $snapshot,
            'updates' => ['equipo_id' => $equipment->id, 'estudiante_id' => $student->id],
            'calls' => [['path' => '', 'method' => 'registrarPrestamo', 'params' => []]],
        ]]], ['X-Livewire' => 'true']);
        $this->assertDatabaseCount('prestamos', 0);
    }

    public function test_student_can_request_future_reservation(): void
    {
        $student = $this->student();
        $this->actingAs($student);
        $this->requestReservation(Equipo::factory()->create())->assertHasNoErrors()->assertSet('mensajeError', '');
        $this->assertDatabaseHas('reservas', ['estado' => 'pendiente', 'estudiante_id' => $student->id]);
    }

    public function test_reservation_requires_fields(): void
    {
        $this->actingAs($this->student());
        Livewire::test(ReservarEquipo::class)->call('reservar')->assertHasErrors(['equipo_id', 'fecha', 'hora_inicio', 'hora_fin']);
    }

    public function test_reservation_rejects_past_date(): void
    {
        $this->actingAs($this->student());
        $this->requestReservation(Equipo::factory()->create(), ['fecha' => '2026-10-19'])->assertHasErrors('fecha');
    }

    public function test_reservation_rejects_past_time_today(): void
    {
        $this->actingAs($this->student());
        $this->requestReservation(Equipo::factory()->create(), ['fecha' => '2026-10-20', 'hora_inicio' => '08:00', 'hora_fin' => '08:30'])->assertHasErrors('hora_inicio');
    }

    public function test_reservation_rejects_reversed_time_range(): void
    {
        $this->actingAs($this->student());
        $this->requestReservation(Equipo::factory()->create(), ['hora_fin' => '09:00'])->assertHasErrors('hora_fin');
    }

    public function test_reservation_rejects_approved_overlap(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA]);
        $this->actingAs($this->student());
        $this->requestReservation($reservation->equipo)->assertSet('mensajeError', 'Ese equipo ya está reservado en ese horario. Elige otro horario u otro día.');
        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_reservation_allows_adjacent_time_ranges(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA]);
        $this->actingAs($this->student());
        $this->requestReservation($reservation->equipo, ['hora_inicio' => '11:00', 'hora_fin' => '12:00'])->assertHasNoErrors();
        $this->assertDatabaseCount('reservas', 2);
    }

    public function test_student_cannot_cancel_another_students_reservation(): void
    {
        $reservation = $this->reservation();
        $this->actingAs($this->student());
        Livewire::test(ReservarEquipo::class)->call('cancelar', $reservation->id)->assertStatus(404);
        $this->assertSame('pendiente', $reservation->fresh()->estado);
    }

    public function test_student_can_cancel_own_reservation(): void
    {
        $reservation = $this->reservation();
        $this->actingAs($reservation->estudiante);
        Livewire::test(ReservarEquipo::class)->call('cancelar', $reservation->id)->assertHasNoErrors();
        $this->assertSame('cancelada', $reservation->fresh()->estado);
    }

    public function test_student_cannot_mount_reservation_management(): void
    {
        $this->actingAs($this->student());
        Livewire::test(GestionReservas::class)->assertForbidden();
    }

    public function test_manager_cannot_mount_student_reservation_form(): void
    {
        $this->actingAs($this->manager());
        Livewire::test(ReservarEquipo::class)->assertForbidden();
    }

    public function test_manager_can_approve_pending_reservation(): void
    {
        $reservation = $this->reservation();
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('aprobar', $reservation->id)->assertSet('mensajeError', '');
        $this->assertSame('aprobada', $reservation->fresh()->estado);
        $this->assertSame('reservado', $reservation->equipo->fresh()->estado);
    }

    public function test_manager_can_reject_pending_reservation(): void
    {
        $reservation = $this->reservation();
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('rechazar', $reservation->id);
        $this->assertSame('rechazada', $reservation->fresh()->estado);
    }

    public function test_canceling_only_approved_reservation_releases_equipment(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA]);
        $reservation->equipo->update(['estado' => Equipo::RESERVADO]);
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('cancelar', $reservation->id);
        $this->assertSame('disponible', $reservation->equipo->fresh()->estado);
    }

    public function test_canceling_one_reservation_preserves_other_approved_reservation(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA]);
        $reservation->equipo->update(['estado' => Equipo::RESERVADO]);
        $this->reservation(['equipo_id' => $reservation->equipo_id, 'estado' => Reserva::APROBADA, 'fecha_inicio' => now()->addDays(2), 'fecha_fin' => now()->addDays(2)->addHour()]);
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('cancelar', $reservation->id);
        $this->assertSame('reservado', $reservation->equipo->fresh()->estado);
    }

    public function test_manager_cannot_approve_twice(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA]);
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('aprobar', $reservation->id)->assertSet('mensajeError', 'Esta reserva ya fue procesada.');
    }

    public function test_manager_cannot_approve_expired_reservation(): void
    {
        $reservation = $this->reservation(['fecha_inicio' => now()->subHours(2), 'fecha_fin' => now()->subHour()]);
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('aprobar', $reservation->id)->assertSet('mensajeError', 'No se puede aprobar: el horario de la reserva ya pasó.');
        $this->assertSame('pendiente', $reservation->fresh()->estado);
    }

    public function test_manager_cannot_approve_overlapping_reservations(): void
    {
        $first = $this->reservation(['estado' => Reserva::APROBADA]);
        $second = $this->reservation(['equipo_id' => $first->equipo_id]);
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('aprobar', $second->id);
        $this->assertSame('pendiente', $second->fresh()->estado);
    }

    public function test_manager_can_deliver_current_reservation(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA, 'fecha_inicio' => now()->subMinutes(10), 'fecha_fin' => now()->addHour()]);
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('entregar', $reservation->id)->assertSet('mensajeError', '');
        $this->assertSame('finalizada', $reservation->fresh()->estado);
        $this->assertDatabaseHas('prestamos', ['equipo_id' => $reservation->equipo_id, 'estudiante_id' => $reservation->estudiante_id]);
    }

    public function test_manager_cannot_deliver_expired_reservation(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA, 'fecha_inicio' => now()->subHours(2), 'fecha_fin' => now()->subHour()]);
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('entregar', $reservation->id);
        $this->assertDatabaseCount('prestamos', 0);
    }

    public function test_delivery_cannot_duplicate_active_loan(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA]);
        $this->loan($reservation->equipo, $this->student());
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('entregar', $reservation->id)->assertSet('mensajeError', 'El equipo todavía está prestado a otra persona.');
        $this->assertDatabaseCount('prestamos', 1);
    }

    public function test_equipment_with_approved_reservation_cannot_be_deleted(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA]);
        $this->actingAs($this->manager())->delete(route('equipos.destroy', $reservation->equipo));
        $this->assertDatabaseHas('equipos', ['id' => $reservation->equipo_id]);
        $this->assertDatabaseHas('reservas', ['id' => $reservation->id]);
    }

    public function test_crud_cannot_mark_active_loan_as_available(): void
    {
        $equipment = Equipo::factory()->create();
        $this->loan($equipment, $this->student());
        $this->actingAs($this->manager())->put(route('equipos.update', $equipment), $equipment->only(['codigo', 'nombre', 'categoria']) + ['estado' => Equipo::DISPONIBLE]);
        $this->assertSame('prestado', $equipment->fresh()->estado);
    }

    public function test_verified_middleware_blocks_unverified_account(): void
    {
        config(['auth.verify_email' => true]);
        $this->actingAs(User::factory()->unverified()->create(['role' => 'estudiante']))
            ->get('/dashboard/estudiante')->assertRedirect(route('verification.notice'));
    }

    public function test_local_registration_does_not_require_email_delivery(): void
    {
        config(['auth.verify_email' => false]);
        $this->actingAs(User::factory()->unverified()->create(['role' => 'estudiante']))
            ->get('/dashboard/estudiante')->assertOk();
    }

    public function test_manager_cannot_deliver_before_reservation_starts(): void
    {
        $reservation = $this->reservation(['estado' => Reserva::APROBADA]);
        $this->actingAs($this->manager());
        Livewire::test(GestionReservas::class)->call('entregar', $reservation->id);
        $this->assertDatabaseCount('prestamos', 0);
    }

    public function test_profile_http_update_keeps_role_and_validates_email(): void
    {
        $student = $this->student();
        $this->actingAs($student)->patch('/profile', [
            'name' => 'Updated Student', 'email' => 'updated@example.test', 'role' => 'encargado',
        ])->assertRedirect(route('profile.edit'));
        $this->assertSame('estudiante', $student->fresh()->role);
        $this->assertSame('updated@example.test', $student->fresh()->email);
        $this->patch('/profile', ['name' => '', 'email' => 'not-an-email'])->assertSessionHasErrors(['name', 'email']);
    }

    public function test_profile_http_delete_preserves_loan_history_and_session(): void
    {
        $student = $this->student();
        $loan = $this->loan(Equipo::factory()->create(), $student);
        $this->actingAs($student)->delete('/profile', ['password' => 'password'])->assertSessionHasErrors('password');
        $this->assertAuthenticatedAs($student);
        $this->assertDatabaseHas('prestamos', ['id' => $loan->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_profile_http_delete_requires_password_and_logs_out_on_success(): void
    {
        $student = $this->student();
        $this->actingAs($student)->delete('/profile', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $student->id]);
        $this->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_account_deletion_cannot_erase_active_loan_history(): void
    {
        $student = $this->student();
        $equipment = Equipo::factory()->create();
        $loan = $this->loan($equipment, $student);
        $this->actingAs($student);
        Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser');
        $this->assertDatabaseHas('prestamos', ['id' => $loan->id]);
    }
}
