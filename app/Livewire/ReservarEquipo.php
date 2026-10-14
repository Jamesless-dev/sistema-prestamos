<?php

namespace App\Livewire;

use App\Models\Equipo;
use App\Models\Reserva;
use App\Models\User;
use App\Services\OperacionEquipos;
use Carbon\Carbon;
use Livewire\Component;

class ReservarEquipo extends Component
{
    public $equipo_id = '';

    public $fecha = '';

    public $hora_inicio = '';

    public $hora_fin = '';

    public $observaciones = '';

    public $mes = '';

    public $mensajeExito = '';

    public $mensajeError = '';

    public function mount()
    {
        $this->autorizar();
        $this->mes = now()->format('Y-m');
    }

    private function autorizar(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_ESTUDIANTE && auth()->user()->hasVerifiedEmail(), 403);
    }

    private function inicioMes(): Carbon
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $this->mes)) {
            $this->mes = now()->format('Y-m');
        }

        return Carbon::parse($this->mes.'-01')->startOfDay();
    }

    public function updatedEquipoId()
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
    }

    public function mesAnterior()
    {
        $this->autorizar();
        $anterior = $this->inicioMes()->subMonthNoOverflow();

        if ($anterior->format('Y-m') >= now()->format('Y-m')) {
            $this->mes = $anterior->format('Y-m');
        }
    }

    public function mesSiguiente()
    {
        $this->autorizar();
        $this->mes = $this->inicioMes()->addMonthNoOverflow()->format('Y-m');
    }

    public function seleccionarDia(string $fecha)
    {
        $this->autorizar();

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha < today()->toDateString()) {
            return;
        }

        $this->fecha = $fecha;
    }

    public function reservar(OperacionEquipos $operaciones)
    {
        $this->autorizar();
        $this->mensajeExito = '';
        $this->mensajeError = '';

        $this->validate([
            'equipo_id' => 'required|integer|exists:equipos,id',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'observaciones' => 'nullable|string|max:255',
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El equipo seleccionado no existe.',
            'date_format' => 'El campo :attribute no tiene un formato válido.',
            'after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'max' => 'El campo :attribute no puede superar los :max caracteres.',
        ], [
            'equipo_id' => 'equipo',
            'hora_inicio' => 'hora de inicio',
            'hora_fin' => 'hora de fin',
        ]);

        $inicio = Carbon::createFromFormat('Y-m-d H:i', $this->fecha.' '.$this->hora_inicio);
        $fin = Carbon::createFromFormat('Y-m-d H:i', $this->fecha.' '.$this->hora_fin);

        if ($inicio->lte(now())) {
            $this->addError('hora_inicio', 'La reserva debe ser para una fecha y hora futuras.');

            return;
        }

        try {
            $operaciones->solicitarReserva((int) $this->equipo_id, auth()->id(), $inicio, $fin, $this->observaciones ?: null);
            $this->reset(['fecha', 'hora_inicio', 'hora_fin', 'observaciones']);
            $this->mensajeExito = '¡Reserva enviada! El encargado la revisará y la aprobará o rechazará.';
        } catch (\DomainException $e) {
            $this->mensajeError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->mensajeError = 'No se pudo guardar la reserva. Actualiza la página e inténtalo de nuevo.';
        }
    }

    public function cancelar(int $reservaId, OperacionEquipos $operaciones)
    {
        $this->autorizar();

        Reserva::query()
            ->where('estudiante_id', auth()->id())
            ->whereIn('estado', [Reserva::PENDIENTE, Reserva::APROBADA])
            ->findOrFail($reservaId);

        $this->mensajeError = '';
        $this->mensajeExito = '';
        try {
            $operaciones->cancelarReserva($reservaId, auth()->id());
            $this->mensajeExito = 'Reserva cancelada.';
        } catch (\DomainException $e) {
            $this->mensajeError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->mensajeError = 'No se pudo cancelar la reserva. Inténtalo de nuevo.';
        }
    }

    private function construirCalendario(Carbon $inicioMes): array
    {
        $dia = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $ultimo = $inicioMes->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $semanas = [];

        while ($dia->lte($ultimo)) {
            $semana = [];
            for ($i = 0; $i < 7; $i++) {
                $semana[] = $dia->copy();
                $dia->addDay();
            }
            $semanas[] = $semana;
        }

        return $semanas;
    }

    public function render()
    {
        $this->autorizar();
        $inicioMes = $this->inicioMes();
        $equipoSeleccionado = $this->equipo_id ? Equipo::find($this->equipo_id) : null;

        $ocupados = collect();
        if ($equipoSeleccionado) {
            $ocupados = Reserva::where('equipo_id', $equipoSeleccionado->id)
                ->whereIn('estado', [Reserva::PENDIENTE, Reserva::APROBADA])
                ->whereBetween('fecha_inicio', [$inicioMes, $inicioMes->copy()->endOfMonth()])
                ->orderBy('fecha_inicio')
                ->get();
        }

        return view('livewire.reservar-equipo', [
            'equipos' => Equipo::orderBy('categoria')->orderBy('nombre')->get(),
            'equipoSeleccionado' => $equipoSeleccionado,
            'inicioMes' => $inicioMes,
            'semanas' => $this->construirCalendario($inicioMes),
            'ocupadosPorDia' => $ocupados->groupBy(fn ($r) => $r->fecha_inicio->format('Y-m-d')),
            'misReservas' => Reserva::with('equipo')
                ->where('estudiante_id', auth()->id())
                ->orderByDesc('fecha_inicio')
                ->get(),
        ]);
    }
}
