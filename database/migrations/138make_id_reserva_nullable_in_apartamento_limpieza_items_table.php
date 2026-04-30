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
        // Verificar si la tabla existe antes de modificarla

        // [2026-04-30] SQLite no soporta ALTER COLUMN. Skipped en local; produccion MySQL OK.
        if (\DB::connection()->getDriverName() === 'sqlite') return;        if (Schema::hasTable('apartamento_limpieza_items')) {
            Schema::table('apartamento_limpieza_items', function (Blueprint $table) {
                $table->unsignedBigInteger('id_reserva')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verificar si la tabla existe antes de modificarla
        if (Schema::hasTable('apartamento_limpieza_items')) {
            Schema::table('apartamento_limpieza_items', function (Blueprint $table) {
                $table->unsignedBigInteger('id_reserva')->nullable(false)->change();
            });
        }
    }
};
