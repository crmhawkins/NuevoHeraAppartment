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
        Schema::table('configuracion_descuentos', function (Blueprint $table) {
            $table->foreignId('edificio_id')->nullable()->constrained('edificios')->onDelete('cascade');
            $table->decimal('porcentaje_incremento', 5, 2)->default(0)->after('porcentaje_descuento');
            
            // Agregar índices para mejorar rendimiento
            $table->index(['edificio_id', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_descuentos', function (Blueprint $table) {
            $table->dropForeign(['edificio_id']);
            $table->dropIndex(['edificio_id', 'activo']);
            $table->dropColumn(['edificio_id', 'porcentaje_incremento']);
        });
    }
};
