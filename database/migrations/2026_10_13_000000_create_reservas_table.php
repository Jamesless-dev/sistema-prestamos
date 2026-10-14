<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->foreignId('estudiante_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'cancelada', 'finalizada'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['equipo_id', 'estado', 'fecha_inicio', 'fecha_fin'], 'reservas_disponibilidad_index');
            $table->index(['estudiante_id', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
