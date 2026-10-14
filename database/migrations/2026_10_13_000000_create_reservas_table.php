<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('estado')->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices para mejorar rendimiento
            $table->index(['equipo_id', 'fecha_inicio', 'fecha_fin']);
            $table->index(['estudiante_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};