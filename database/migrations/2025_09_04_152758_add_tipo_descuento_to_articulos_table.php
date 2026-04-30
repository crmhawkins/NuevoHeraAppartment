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
        if (!Schema::hasTable('articulos')) return; // [2026-04-30] idempotente
        Schema::table('articulos', function (Blueprint $table) {
            $table->enum('tipo_descuento', ['reposicion', 'consumo'])
                  ->default('reposicion')
                  ->after('categoria')
                  ->comment('reposicion: solo se repone físicamente, consumo: se descuenta del stock general');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropColumn('tipo_descuento');
        });
    }
};