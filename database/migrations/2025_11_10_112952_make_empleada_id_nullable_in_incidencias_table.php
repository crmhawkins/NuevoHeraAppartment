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
        if (!Schema::hasTable('incidencias')) return;
        // [2026-04-30] SQLite no soporta ALTER COLUMN. Skipped en local; produccion MySQL OK.
        if (\DB::connection()->getDriverName() === 'sqlite') return; // [2026-04-30] idempotente
        Schema::table('incidencias', function (Blueprint $table) {
            // Hacer empleada_id nullable para permitir incidencias desde WhatsApp
            $table->unsignedBigInteger('empleada_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidencias', function (Blueprint $table) {
            // Revertir a NOT NULL (solo si no hay registros null)
            // Nota: Esto puede fallar si hay incidencias con empleada_id null
            $table->unsignedBigInteger('empleada_id')->nullable(false)->change();
        });
    }
};
