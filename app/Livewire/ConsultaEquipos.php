<?php

namespace App\Livewire;

use App\Models\Equipo;
use Livewire\Component;

class ConsultaEquipos extends Component
{
    public $equiposDisponibles;
    public $busqueda = '';

    public function mount()
    {
        $this->cargarEquipos();
    }

    public function cargarEquipos()
    {

        $this->equiposDisponibles = Equipo::where('estado', 'disponible')
            ->where(function($query) {
                $query->where('nombre', 'like', '%' . $this->busqueda . '%')
                      ->orWhere('codigo', 'like', '%' . $this->busqueda . '%')
                      ->orWhere('categoria', 'like', '%' . $this->busqueda . '%');
            })
            ->orderBy('categoria')
            ->get();
    }

    public function updatedBusqueda()
    {
        $this->cargarEquipos();
    }

    public function render()
    {
        return view('livewire.consulta-equipos');
    }
}