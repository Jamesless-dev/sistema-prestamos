<?php

namespace App\Livewire;

use App\Models\Equipo;
use Livewire\Attributes\Url;
use Livewire\Component;

class ConsultaEquipos extends Component
{
    public $equiposDisponibles;

    #[Url(except: '')]
    public string $busqueda = '';

    public function mount()
    {
        $this->cargarEquipos();
    }

    public function cargarEquipos()
    {

        $this->equiposDisponibles = Equipo::where('estado', 'disponible')
            ->where(function ($query) {
                $query->whereLike('nombre', '%'.$this->busqueda.'%')
                    ->orWhereLike('codigo', '%'.$this->busqueda.'%')
                    ->orWhereLike('categoria', '%'.$this->busqueda.'%');
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
