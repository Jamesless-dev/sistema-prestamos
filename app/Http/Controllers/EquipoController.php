<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipoRequest;
use App\Models\Equipo;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        if ($request->validated('estado') !== Equipo::DISPONIBLE) {
            throw ValidationException::withMessages(['estado' => 'Un equipo nuevo debe estar Disponible. Registra su préstamo o reserva desde el panel.']);
        }
        try {
            Equipo::create($request->validated());
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with('error', 'No se pudo guardar el equipo. Comprueba que su código no esté registrado e inténtalo de nuevo.');
        }

        return to_route('equipos.index')->with('success', 'Equipo registrado correctamente.');
    }

    public function edit(Equipo $equipo): View
    {
        return view('equipos.edit', compact('equipo'));
    }

    public function update(EquipoRequest $request, Equipo $equipo): RedirectResponse
    {
        try {
            DB::transaction(function () use ($equipo, $request) {
                $actual = Equipo::lockForUpdate()->findOrFail($equipo->id);
                if ($request->validated('estado') !== $actual->estadoSegunMovimientos()) {
                    throw ValidationException::withMessages(['estado' => 'El estado debe coincidir con los préstamos y reservas. Utiliza las acciones del panel para cambiarlo.']);
                }
                $actual->update($request->validated());
            }, 3);
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with('error', 'No se pudo actualizar el equipo. Comprueba el código e inténtalo de nuevo.');
        }

        return to_route('equipos.index')->with('success', 'Equipo actualizado correctamente.');
    }

    public function destroy(Equipo $equipo): RedirectResponse
    {
        try {
            $eliminado = DB::transaction(function () use ($equipo) {
                $actual = Equipo::lockForUpdate()->findOrFail($equipo->id);
                if ($actual->prestamos()->exists() || $actual->reservas()->exists()) {
                    return false;
                }
                $actual->delete();

                return true;
            }, 3);
        } catch (QueryException $e) {
            report($e);

            return to_route('equipos.index')->with('error', 'No se pudo eliminar el equipo porque tiene movimientos asociados o la base de datos no está disponible.');
        }

        if (! $eliminado) {
            return to_route('equipos.index')->with('error', 'No se puede eliminar un equipo con historial de préstamos o reservas.');
        }

        return to_route('equipos.index')->with('success', 'Equipo eliminado correctamente.');
    }
}
