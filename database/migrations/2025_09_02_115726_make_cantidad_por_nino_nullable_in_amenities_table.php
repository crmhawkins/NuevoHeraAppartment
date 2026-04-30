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
        if (!Schema::hasTable('amenities')) return;
        // [2026-04-30] SQLite no soporta ALTER COLUMN. Skipped en local; produccion MySQL OK.
        if (\DB::connection()->getDriverName() === 'sqlite') return; // [2026-04-30] idempotente
        Schema::table('amenities', function (Blueprint $table) {
            // Hacer nullable el campo cantidad_por_nino
            $table->integer('cantidad_por_nino')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            // Revertir a no nullable
            $table->integer('cantidad_por_nino')->nullable(false)->change();
        });
    }
};
