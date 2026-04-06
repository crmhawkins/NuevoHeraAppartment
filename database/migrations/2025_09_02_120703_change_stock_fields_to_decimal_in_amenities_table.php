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
            // Cambiar campos de stock de integer a decimal para permitir cantidades como 0.20 litros
            $table->decimal('stock_actual', 10, 2)->default(0)->change();
            $table->decimal('stock_minimo', 10, 2)->default(0)->change();
            $table->decimal('stock_maximo', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            // Revertir campos de stock de decimal a integer
            $table->integer('stock_actual')->default(0)->change();
            $table->integer('stock_minimo')->default(0)->change();
            $table->integer('stock_maximo')->nullable()->change();
        });
    }
};
