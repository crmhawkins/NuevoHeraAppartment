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
        Schema::table('invoices', function (Blueprint $table) {
            // Campos para facturas rectificativas
            $table->boolean('es_rectificativa')->default(false)->comment('Indica si es una factura rectificativa');
            $table->unsignedBigInteger('factura_original_id')->nullable()->comment('ID de la factura original que se rectifica');
            $table->string('motivo_rectificacion')->nullable()->comment('Motivo de la rectificación');
            $table->text('observaciones_rectificacion')->nullable()->comment('Observaciones adicionales sobre la rectificación');
            
            // Índices para mejorar el rendimiento
            $table->index('es_rectificativa');
            $table->index('factura_original_id');
            
            // Clave foránea para la factura original
            $table->foreign('factura_original_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Eliminar clave foránea primero
            $table->dropForeign(['factura_original_id']);
            
            // Eliminar índices
            $table->dropIndex(['es_rectificativa']);
            $table->dropIndex(['factura_original_id']);
            
            // Eliminar columnas
            $table->dropColumn([
                'es_rectificativa',
                'factura_original_id',
                'motivo_rectificacion',
                'observaciones_rectificacion'
            ]);
        });
    }
};