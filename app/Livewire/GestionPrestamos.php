<?php

namespace App\Livewire;

use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\User;
use App\Services\OperacionEquipos;
use DomainException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionPrestamos extends Component
{
    use WithPagination;

    public $equipo_id = '';

    public $estudiante_id = '';

    public $observaciones = '';

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

    public function registrarPrestamo(OperacionEquipos $operaciones): void
    {
        $this->autorizar();
        $this->reset('mensajeExito', 'mensajeError');
        $this->validate([
            'equipo_id' => ['required', 'integer', 'exists:equipos,id'],
            'estudiante_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_ESTUDIANTE)],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ], [
            'required' => 'Selecciona un :attribute.',
            'equipo_id.exists' => 'El equipo seleccionado no existe.',
            'estudiante_id.exists' => 'Selecciona un estudiante válido.',
            'observaciones.max' => 'Las observaciones no pueden superar los 255 caracteres.',
        ], ['equipo_id' => 'equipo', 'estudiante_id' => 'estudiante']);

        try {
            $operaciones->prestar((int) $this->equipo_id, (int) $this->estudiante_id, $this->observaciones ?: null);
            $this->reset('equipo_id', 'estudiante_id', 'observaciones');
            $this->mensajeExito = 'Préstamo registrado correctamente.';
        } catch (DomainException $e) {
            $this->mensajeError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->mensajeError = 'No se pudo registrar el préstamo. Actualiza la página e inténtalo de nuevo.';
        }
    }

    public function registrarDevolucion(int $prestamoId, OperacionEquipos $operaciones): void
    {
        $this->autorizar();
        $this->reset('mensajeExito', 'mensajeError');

        try {
            $operaciones->devolver($prestamoId);
            $this->mensajeExito = 'Devolución registrada. El estado del equipo fue actualizado.';
            $this->resetPage('historialPage');
        } catch (DomainException $e) {
            $this->mensajeError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->mensajeError = 'No se pudo registrar la devolución. Actualiza la página e inténtalo de nuevo.';
        }
    }

    public function render()
    {
        $this->autorizar();

        return view('livewire.gestion-prestamos', [
            'equiposDisponibles' => Equipo::where('estado', Equipo::DISPONIBLE)->orderBy('codigo')->get(),
            'estudiantes' => User::where('role', User::ROLE_ESTUDIANTE)->orderBy('name')->get(),
            'prestamosActivos' => Prestamo::with(['equipo', 'estudiante'])->whereNull('fecha_devolucion')->latest('fecha_prestamo')->get(),
            'historial' => Prestamo::with(['equipo', 'estudiante'])->whereNotNull('fecha_devolucion')
                ->latest('fecha_devolucion')->paginate(10, pageName: 'historialPage'),
        ]);
    }
}
