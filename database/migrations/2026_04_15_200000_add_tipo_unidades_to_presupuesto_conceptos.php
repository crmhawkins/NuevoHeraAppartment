<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade soporte para dos tipos de conceptos en los presupuestos:
 * - alojamiento: con fechas, precio por noche, noches totales (flujo actual)
 * - servicio: con concepto, unidades, precio por unidad (nuevo)
 *
 * Los campos existentes de alojamiento (fecha_entrada, fecha_salida, etc.)
 * ya son nullable, asi que ambos tipos conviven en la misma tabla.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('presupuesto_conceptos', function (Blueprint $t) {
            $t->string('tipo', 20)->default('alojamiento')->after('concepto');
            $t->unsignedInteger('unidades')->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('presupuesto_conceptos', function (Blueprint $t) {
            $t->dropColumn(['tipo', 'unidades']);
        });
    }
};
