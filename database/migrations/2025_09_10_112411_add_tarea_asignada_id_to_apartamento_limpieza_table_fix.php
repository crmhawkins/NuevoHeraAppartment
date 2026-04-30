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
        if (!Schema::hasTable('apartamento_limpieza')) return; // [2026-04-30] idempotente
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            // [2026-04-30] hasColumn check para evitar duplicate al re-ejecutar
            if (!Schema::hasColumn('apartamento_limpieza', 'tarea_asignada_id')) {
                $table->unsignedBigInteger('tarea_asignada_id')->nullable()->after('empleada_id');
                $table->index('tarea_asignada_id');
            }
            if (!Schema::hasColumn('apartamento_limpieza', 'origen')) {
                $table->string('origen')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            $table->dropIndex(['tarea_asignada_id']);
            $table->dropColumn(['tarea_asignada_id', 'origen']);
        });
    }
};
