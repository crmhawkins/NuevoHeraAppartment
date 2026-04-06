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
        Schema::table('clientes', function (Blueprint $table) {
            // Campos específicos para facturación (datos del receptor)
            $table->string('facturacion_nombre_razon_social')->nullable()->comment('Nombre o razón social para facturación');
            $table->string('facturacion_nif_cif')->nullable()->comment('NIF/CIF para facturación');
            $table->string('facturacion_direccion')->nullable()->comment('Dirección fiscal para facturación');
            $table->string('facturacion_localidad')->nullable()->comment('Localidad fiscal para facturación');
            $table->string('facturacion_codigo_postal')->nullable()->comment('Código postal fiscal para facturación');
            $table->string('facturacion_provincia')->nullable()->comment('Provincia fiscal para facturación');
            $table->string('facturacion_pais')->nullable()->comment('País fiscal para facturación');
            $table->string('facturacion_email')->nullable()->comment('Email para envío de facturas');
            $table->string('facturacion_telefono')->nullable()->comment('Teléfono de contacto para facturación');
            
            // Campos adicionales para empresas
            $table->boolean('es_empresa')->default(false)->comment('Indica si el cliente es una empresa');
            $table->string('tipo_cliente')->default('particular')->comment('Tipo de cliente: particular, empresa, autónomo');
            
            // Campos para configuración de facturación
            $table->boolean('requiere_factura')->default(false)->comment('Indica si el cliente requiere factura');
            $table->string('condiciones_pago')->nullable()->comment('Condiciones de pago preferidas');
            $table->text('observaciones_facturacion')->nullable()->comment('Observaciones específicas para facturación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'facturacion_nombre_razon_social',
                'facturacion_nif_cif',
                'facturacion_direccion',
                'facturacion_localidad',
                'facturacion_codigo_postal',
                'facturacion_provincia',
                'facturacion_pais',
                'facturacion_email',
                'facturacion_telefono',
                'es_empresa',
                'tipo_cliente',
                'requiere_factura',
                'condiciones_pago',
                'observaciones_facturacion'
            ]);
        });
    }
};