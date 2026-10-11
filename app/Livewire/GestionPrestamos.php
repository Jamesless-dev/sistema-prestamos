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

    public function mount()
    {
        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        $this->equiposDisponibles = Equipo::where('estado', 'disponible')->get();
        
        $this->estudiantes = User::where('role', 'estudiante')->get();
    }

    public function registrarPrestamo()
    {
        $this->validate([
            'equipo_id' => 'required|exists:equipos,id',
            'estudiante_id' => 'required|exists:users,id',
            'observaciones' => 'nullable|string|max:255',
        ], [
            'equipo_id.required' => 'Debes seleccionar un equipo.',
            'estudiante_id.required' => 'Debes seleccionar un estudiante.',
        ]);

        try {
            DB::beginTransaction();

            Prestamo::create([
                'equipo_id' => $this->equipo_id,
                'estudiante_id' => $this->estudiante_id,
                'observaciones' => $this->observaciones,

            ]);

            $equipo = Equipo::find($this->equipo_id);
            $equipo->update(['estado' => 'prestado']);

            DB::commit();

            $this->reset(['equipo_id', 'estudiante_id', 'observaciones', 'mensajeError']);
            $this->mensajeExito = '¡Préstamo registrado exitosamente!';
            
            $this->cargarDatos();

        } catch (\Exception $e) {
      
            DB::rollBack();
            $this->mensajeError = 'Ocurrió un error al registrar el préstamo. Inténtalo de nuevo.';

        }
    }

    public function render()
    {
        return view('livewire.gestion-prestamos');
    }
}