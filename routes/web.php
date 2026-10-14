<?php

use App\Http\Controllers\EquipoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    if (auth()->check()) {
        return redirect()->route(auth()->user()->dashboardRoute());
    }

    return view('welcome');
})->name('home');

// Entrada común para el registro y los enlaces del sistema.
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->dashboardRoute());
})->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'verified', 'role:encargado'])->group(function () {
    // Todas las operaciones del CRUD pasan por el control de rol.
    Route::resource('equipos', EquipoController::class)->except('show');

    Route::get('/dashboard/encargado', function () {
        return view('dashboard.encargado');
    })->name('dashboard.encargado');

    Route::get('/reservas/gestion', function () {
        return view('reservas.gestion');
    })->name('reservas.gestion');
});

Route::middleware(['auth', 'verified', 'role:estudiante'])->group(function () {
    Route::get('/dashboard/estudiante', function () {
        return view('dashboard.estudiante');
    })->name('dashboard.estudiante');

    Route::get('/reservas', function () {
        return view('reservas.estudiante');
    })->name('reservas.estudiante');
});

Route::middleware('auth')->group(function () {
    Route::view('/profile', 'profile')->name('profile.edit');
});

require __DIR__.'/auth.php';
