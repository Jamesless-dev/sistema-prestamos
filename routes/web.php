<?php

use App\Http\Controllers\EquipoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    if (auth()->check()) {
        if (auth()->user()->role === 'encargado') {
            return redirect()->route('dashboard.encargado');
        }

        return redirect()->route('dashboard.estudiante');
    }

    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified', 'role:encargado'])->group(function () {
    Route::resource('equipos', EquipoController::class)->except('show');

    Route::get('/dashboard/encargado', function () {
        return view('dashboard.encargado');
    })->name('dashboard.encargado');
});

Route::middleware(['auth', 'verified', 'role:estudiante'])->group(function () {
    Route::get('/dashboard/estudiante', function () {
        return view('dashboard.estudiante');
    })->name('dashboard.estudiante');
});

Route::middleware('auth')->group(function () {
    Route::view('/profile', 'profile')->name('profile.edit');
});

require __DIR__.'/auth.php';
