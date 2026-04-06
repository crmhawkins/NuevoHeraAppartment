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
        Schema::table('amenities', function (Blueprint $table) {
            // Cambiar campos de consumo de integer a decimal
            $table->decimal('consumo_por_reserva', 8, 2)->nullable()->change();
            $table->decimal('consumo_minimo_reserva', 8, 2)->nullable()->change();
            $table->decimal('consumo_maximo_reserva', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            // Revertir campos de consumo de decimal a integer
            $table->integer('consumo_por_reserva')->nullable()->change();
            $table->integer('consumo_minimo_reserva')->nullable()->change();
            $table->integer('consumo_maximo_reserva')->nullable()->change();
        });
    }
};
