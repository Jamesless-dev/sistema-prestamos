<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('reservas')) {
            $original = require __DIR__.'/2026_10_13_000000_create_reservas_table.php';
            $original->up();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** La migración original es dueña de la tabla; revertir esta reparación conserva sus datos. */
    }
};
