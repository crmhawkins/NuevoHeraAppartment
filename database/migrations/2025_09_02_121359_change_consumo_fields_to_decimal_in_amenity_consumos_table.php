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
        Schema::table('amenity_consumos', function (Blueprint $table) {
            // Cambiar campos de consumo de integer a decimal para permitir cantidades como 0.20 litros
            $table->decimal('cantidad_consumida', 10, 2)->change();
            $table->decimal('cantidad_anterior', 10, 2)->change();
            $table->decimal('cantidad_actual', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amenity_consumos', function (Blueprint $table) {
            // Revertir campos de consumo de decimal a integer
            $table->integer('cantidad_consumida')->change();
            $table->integer('cantidad_anterior')->change();
            $table->integer('cantidad_actual')->change();
        });
    }
};
