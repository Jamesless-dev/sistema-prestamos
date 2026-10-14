<?php

namespace App\Livewire;

use App\Models\Reserva;
use App\Models\User;
use App\Services\OperacionEquipos;
use DomainException;
use Livewire\Component;

class GestionReservas extends Component
{
    public string $mensajeExito = '';

    public string $mensajeError = '';

    public function mount(): void
    {
        $this->autorizar();
    }

    private function autorizar(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_ENCARGADO && auth()->user()->hasVerifiedEmail(), 403);
    }

    private function ejecutar(\Closure $accion, string $mensaje): void
    {
        $this->autorizar();
        $this->reset('mensajeExito', 'mensajeError');

        try {
            $accion();
            $this->mensajeExito = $mensaje;
        } catch (DomainException $e) {
            $this->mensajeError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->mensajeError = 'No se pudo procesar la reserva. Actualiza la página e inténtalo de nuevo.';
        }
    }

    public function aprobar(int $reservaId, OperacionEquipos $operaciones): void
    {
        $this->ejecutar(fn () => $operaciones->aprobarReserva($reservaId), 'Reserva aprobada. El estado del equipo fue actualizado.');
    }

    public function rechazar(int $reservaId, OperacionEquipos $operaciones): void
    {
        $this->ejecutar(fn () => $operaciones->rechazarReserva($reservaId), 'Reserva rechazada.');
    }

    public function cancelar(int $reservaId, OperacionEquipos $operaciones): void
    {
        $this->ejecutar(fn () => $operaciones->cancelarReserva($reservaId), 'Reserva cancelada. El estado del equipo fue actualizado.');
    }

    public function entregar(int $reservaId, OperacionEquipos $operaciones): void
    {
        $this->ejecutar(fn () => $operaciones->entregarReserva($reservaId), 'Equipo entregado y registrado como préstamo activo.');
    }

    public function render()
    {
        $this->autorizar();

        return view('livewire.gestion-reservas', [
            'pendientes' => Reserva::with(['equipo', 'estudiante'])->where('estado', Reserva::PENDIENTE)->orderBy('fecha_inicio')->get(),
            'aprobadas' => Reserva::with(['equipo', 'estudiante'])->where('estado', Reserva::APROBADA)->orderBy('fecha_inicio')->get(),
            'historial' => Reserva::with(['equipo', 'estudiante'])->whereIn('estado', [Reserva::RECHAZADA, Reserva::CANCELADA, Reserva::FINALIZADA])
                ->latest('updated_at')->limit(10)->get(),
        ]);
    }
}
