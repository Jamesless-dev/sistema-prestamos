<?php

namespace App\Livewire;

use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GestionReservas extends Component
{
    public $mensajeExito = '';
    public $mensajeError = '';

    public function mount()
    {
        $this->autorizar();
    }

    private function autorizar(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_ENCARGADO, 403);
    }

    private function avisar(string $exito = '', string $error = ''): void
    {
        $this->mensajeExito = $exito;
        $this->mensajeError = $error;
    }

    public function aprobar($reservaId)
    {
        $this->autorizar();

        try {
            DB::transaction(function () use ($reservaId) {
                $reserva = Reserva::with('equipo')->lockForUpdate()->findOrFail($reservaId);

                if ($reserva->estado !== Reserva::PENDIENTE) {
                    throw new \DomainException('Esta reserva ya fue procesada.');
                }

                if ($reserva->fecha_fin->lte(now())) {
                    throw new \DomainException('No se puede aprobar: el horario de la reserva ya pasó.');
                }

                if (Reserva::hayConflicto($reserva->equipo_id, $reserva->fecha_inicio, $reserva->fecha_fin, $reserva->id)) {
                    throw new \DomainException('No se puede aprobar: el equipo ya tiene otra reserva aprobada en ese horario.');
                }

                $reserva->update(['estado' => Reserva::APROBADA]);

                if ($reserva->equipo->estado === Equipo::DISPONIBLE) {
                    $reserva->equipo->update(['estado' => Equipo::RESERVADO]);
                }
            });

            $this->avisar('Reserva aprobada. El equipo quedó marcado como Reservado.');
        } catch (\DomainException $e) {
            $this->avisar('', $e->getMessage());
        } catch (\Throwable $e) {
            $this->avisar('', 'Ocurrió un error al aprobar la reserva.');
        }
    }

    public function rechazar($reservaId)
    {
        $this->autorizar();

        $reserva = Reserva::where('estado', Reserva::PENDIENTE)->findOrFail($reservaId);
        $reserva->update(['estado' => Reserva::RECHAZADA]);

        $this->avisar('Reserva rechazada.');
    }

    public function cancelar($reservaId)
    {
        $this->autorizar();

        $reserva = Reserva::with('equipo')->where('estado', Reserva::APROBADA)->findOrFail($reservaId);
        $reserva->update(['estado' => Reserva::CANCELADA]);
        $reserva->equipo->sincronizarEstadoReserva();

        $this->avisar('Reserva cancelada y equipo liberado.');
    }

    public function entregar($reservaId)
    {
        $this->autorizar();

        try {
            DB::transaction(function () use ($reservaId) {
                $reserva = Reserva::with('equipo')->lockForUpdate()->findOrFail($reservaId);

                if ($reserva->estado !== Reserva::APROBADA) {
                    throw new \DomainException('Solo se pueden entregar reservas aprobadas.');
                }

                if ($reserva->equipo->estado === Equipo::PRESTADO) {
                    throw new \DomainException('El equipo todavía está prestado a otra persona.');
                }

                Prestamo::create([
                    'equipo_id' => $reserva->equipo_id,
                    'estudiante_id' => $reserva->estudiante_id,
                    'observaciones' => 'Entrega de la reserva #' . $reserva->id,
                ]);

                $reserva->equipo->update(['estado' => Equipo::PRESTADO]);
                $reserva->update(['estado' => Reserva::FINALIZADA]);
            });

            $this->avisar('Equipo entregado: la reserva se registró como préstamo activo.');
        } catch (\DomainException $e) {
            $this->avisar('', $e->getMessage());
        } catch (\Throwable $e) {
            $this->avisar('', 'Ocurrió un error al entregar el equipo.');
        }
    }

    public function render()
    {
        return view('livewire.gestion-reservas', [
            'pendientes' => Reserva::with(['equipo', 'estudiante'])
                ->where('estado', Reserva::PENDIENTE)->orderBy('fecha_inicio')->get(),
            'aprobadas' => Reserva::with(['equipo', 'estudiante'])
                ->where('estado', Reserva::APROBADA)->orderBy('fecha_inicio')->get(),
            'historial' => Reserva::with(['equipo', 'estudiante'])
                ->whereIn('estado', [Reserva::RECHAZADA, Reserva::CANCELADA, Reserva::FINALIZADA])
                ->latest('updated_at')->limit(10)->get(),
        ]);
    }
}