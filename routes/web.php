<?php

use App\Http\Controllers\ProfileController;
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';