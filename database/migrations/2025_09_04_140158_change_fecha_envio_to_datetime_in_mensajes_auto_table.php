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
        if (!Schema::hasTable('mensajes_auto')) return;
        // [2026-04-30] SQLite no soporta ALTER COLUMN. Skipped en local; produccion MySQL OK.
        if (\DB::connection()->getDriverName() === 'sqlite') return; // [2026-04-30] idempotente
        Schema::table('mensajes_auto', function (Blueprint $table) {
            $table->datetime('fecha_envio')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensajes_auto', function (Blueprint $table) {
            $table->date('fecha_envio')->nullable()->change();
        });
    }
};
