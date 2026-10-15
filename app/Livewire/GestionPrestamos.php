<?php

namespace App\Livewire;

use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class GestionPrestamos extends Component
{
    public $equipo_id = '';

    public $estudiante_id = '';

    public $observaciones = '';

    public $mensajeExito = '';

    public $mensajeError = '';

    public $equiposDisponibles;

    public $estudiantes;

    public $prestamosActivos;

    public function boot(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_ENCARGADO, 403);
    }

    public function mount(): void
    {
        $this->cargarDatos();
        $this->cargarPrestamosActivos();
    }

    public function cargarDatos(): void
    {
        $this->equiposDisponibles = Equipo::where('estado', Equipo::DISPONIBLE)
            ->whereDoesntHave('prestamos', fn ($query) => $query->whereNull('fecha_devolucion'))
            ->whereDoesntHave('reservas', fn ($query) => $query->where('estado', Reserva::APROBADA)->where('fecha_fin', '>', now()))
            ->get();
        $this->estudiantes = User::where('role', User::ROLE_ESTUDIANTE)->get();
    }

    public function cargarPrestamosActivos(): void
    {
        $this->prestamosActivos = Prestamo::with(['equipo', 'estudiante'])
            ->whereNull('fecha_devolucion')->orderByDesc('fecha_prestamo')->get();
    }

    public function registrarPrestamo(): void
    {
        $this->reset(['mensajeError', 'mensajeExito']);
        $this->validate([
            'equipo_id' => ['required', 'integer', 'exists:equipos,id'],
            'estudiante_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_ESTUDIANTE)],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::transaction(function (): void {
                $equipo = Equipo::lockForUpdate()->findOrFail($this->equipo_id);

                if ($equipo->estado !== Equipo::DISPONIBLE
                    || $equipo->prestamos()->whereNull('fecha_devolucion')->exists()
                    || $equipo->reservas()->where('estado', Reserva::APROBADA)->where('fecha_fin', '>', now())->exists()) {
                    throw new \DomainException('El equipo ya no está disponible. Selecciona otro equipo.');
                }

                Prestamo::create([
                    'equipo_id' => $equipo->id,
                    'estudiante_id' => $this->estudiante_id,
                    'observaciones' => $this->observaciones,
                ]);
                $equipo->update(['estado' => Equipo::PRESTADO]);
            });

            $this->reset(['equipo_id', 'estudiante_id', 'observaciones']);
            $this->mensajeExito = 'Préstamo registrado exitosamente.';
        } catch (\DomainException $exception) {
            $this->mensajeError = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->mensajeError = 'Ocurrió un error al registrar el préstamo. Inténtalo de nuevo.';
        }

        $this->cargarDatos();
        $this->cargarPrestamosActivos();
    }

    public function registrarDevolucion(int $prestamoId): void
    {
        $this->reset(['mensajeError', 'mensajeExito']);

        try {
            DB::transaction(function () use ($prestamoId): void {
                $equipoId = Prestamo::findOrFail($prestamoId)->equipo_id;
                $equipo = Equipo::lockForUpdate()->findOrFail($equipoId);
                $prestamo = Prestamo::lockForUpdate()->findOrFail($prestamoId);

                if ($prestamo->fecha_devolucion !== null) {
                    throw new \DomainException('Este préstamo ya fue devuelto.');
                }

                $prestamo->update(['fecha_devolucion' => now()]);
                $equipo->sincronizarEstadoReserva();
            });

            $this->mensajeExito = 'Devolución registrada y estado del equipo actualizado.';
        } catch (\DomainException $exception) {
            $this->mensajeError = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->mensajeError = 'Ocurrió un error al registrar la devolución.';
        }

        $this->cargarDatos();
        $this->cargarPrestamosActivos();
    }

    public function render(): View
    {
        return view('livewire.gestion-prestamos');
    }
}
