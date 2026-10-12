<?php

namespace App\Livewire;

use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

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

    public function mount()
    {
        $this->cargarDatos();
        $this->cargarPrestamosActivos();
    }

    public function cargarDatos()
    {
        $this->equiposDisponibles = Equipo::where('estado', 'disponible')->get();
        $this->estudiantes = User::where('role', 'estudiante')->get();
    }

    public function cargarPrestamosActivos()
    {
 
        $this->prestamosActivos = Prestamo::with(['equipo', 'estudiante'])
            ->whereNull('fecha_devolucion')
            ->orderBy('fecha_prestamo', 'desc')
            ->get();
    }

    public function registrarPrestamo()
    {
        $this->validate([
            'equipo_id' => 'required|exists:equipos,id',
            'estudiante_id' => 'required|exists:users,id',
            'observaciones' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            Prestamo::create([
                'equipo_id' => $this->equipo_id,
                'estudiante_id' => $this->estudiante_id,
                'observaciones' => $this->observaciones,
            ]);

            Equipo::find($this->equipo_id)->update(['estado' => 'prestado']);

            DB::commit();
            $this->reset(['equipo_id', 'estudiante_id', 'observaciones', 'mensajeError']);
            $this->mensajeExito = '¡Préstamo registrado exitosamente!';
            
            $this->cargarDatos();
            $this->cargarPrestamosActivos();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = 'Ocurrió un error al registrar el préstamo. Inténtalo de nuevo.';
        }
    }


    public function registrarDevolucion($prestamoId)
    {
        try {
            DB::beginTransaction();

            $prestamo = Prestamo::findOrFail($prestamoId);
            $prestamo->fecha_devolucion = now();
            $prestamo->save();

            $prestamo->equipo->update(['estado' => 'disponible']);

            DB::commit();
            $this->mensajeExito = '¡Equipo devuelto y marcado como disponible!';
            $this->mensajeError = '';
            
            $this->cargarDatos();
            $this->cargarPrestamosActivos();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = 'Ocurrió un error al registrar la devolución.';
        }
    }

    public function render()
    {
        return view('livewire.gestion-prestamos');
    }
}