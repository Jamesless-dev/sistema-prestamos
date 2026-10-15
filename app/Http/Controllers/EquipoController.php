<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipoRequest;
use App\Models\Equipo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EquipoController extends Controller
{
    public function index(): View
    {
        return view('equipos.index', [
            'equipos' => Equipo::orderBy('codigo')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('equipos.create', ['equipo' => new Equipo]);
    }

    public function store(EquipoRequest $request): RedirectResponse
    {
        Equipo::create($request->validated());

        return to_route('equipos.index')->with('success', 'Equipo registrado correctamente.');
    }

    public function edit(Equipo $equipo): View
    {
        return view('equipos.edit', compact('equipo'));
    }

    public function update(EquipoRequest $request, Equipo $equipo): RedirectResponse
    {
        $equipo->update($request->validated());

        return to_route('equipos.index')->with('success', 'Equipo actualizado correctamente.');
    }

    public function destroy(Equipo $equipo): RedirectResponse
    {
        if ($equipo->prestamos()->exists() || $equipo->reservas()->exists()) {
            return to_route('equipos.index')->with('error', 'No se puede eliminar un equipo con préstamos o reservas registrados.');
        }

        $equipo->delete();

        return to_route('equipos.index')->with('success', 'Equipo eliminado correctamente.');
    }
}
