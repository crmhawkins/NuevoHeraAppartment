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
        if (!Schema::hasTable('reservas')) return; // [2026-04-30] idempotente
        Schema::table('reservas', function (Blueprint $table) {
            $table->boolean('no_facturar')->default(false)->comment('Indica si la reserva no debe facturarse automáticamente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('no_facturar');
        });
    }
};
