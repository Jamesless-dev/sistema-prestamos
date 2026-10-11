<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            
            $table->foreignId('estudiante_id')->constrained('users')->onDelete('cascade');
            
            $table->timestamp('fecha_prestamo')->useCurrent(); 
            $table->timestamp('fecha_devolucion')->nullable(); 
            
            $table->text('observaciones')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};