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
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('prestamos', function (Blueprint $table) use ($driver) {
            $table->dropForeign(['equipo_id']);
            $table->dropForeign(['estudiante_id']);
            $table->foreign('equipo_id')->references('id')->on('equipos')->restrictOnDelete();
            $table->foreign('estudiante_id')->references('id')->on('users')->restrictOnDelete();
            $activo = $table->unsignedBigInteger('equipo_activo_id')->nullable();
            $expresion = 'CASE WHEN fecha_devolucion IS NULL THEN equipo_id ELSE NULL END';

            if ($driver === 'pgsql') {
                $activo->storedAs($expresion);
            } else {
                $activo->virtualAs($expresion);
            }
            $table->unique('equipo_activo_id');
            $table->index(['equipo_id', 'fecha_devolucion']);
            $table->index(['estudiante_id', 'fecha_prestamo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->dropForeign(['estudiante_id']);
            $table->dropIndex(['equipo_id', 'fecha_devolucion']);
            $table->dropIndex(['estudiante_id', 'fecha_prestamo']);
            $table->dropUnique(['equipo_activo_id']);
            $table->dropColumn('equipo_activo_id');
            $table->foreign('equipo_id')->references('id')->on('equipos')->cascadeOnDelete();
            $table->foreign('estudiante_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
