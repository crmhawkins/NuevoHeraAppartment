<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [2026-04-29] Columnas de Revenue Management en apartamentos.
 *
 * - revenue_min_precio: suelo absoluto (nunca recomendamos por debajo).
 * - revenue_max_precio: techo absoluto (nunca por encima).
 * - revenue_factor_segmento: posicionamiento vs competencia.
 *     premium → +10% (queremos ir por encima)
 *     match   → mismo (default)
 *     budget  → -10% (ganar volumen)
 * - revenue_rate_plan_id: id del rate_plan en Channex para el push de
 *   precios (Channex requiere property_id + rate_plan_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->decimal('revenue_min_precio', 10, 2)->nullable()->after('claves');
            $table->decimal('revenue_max_precio', 10, 2)->nullable()->after('revenue_min_precio');
            $table->enum('revenue_factor_segmento', ['premium', 'match', 'budget'])
                ->default('match')
                ->after('revenue_max_precio');
            $table->string('revenue_rate_plan_id', 100)->nullable()
                ->after('revenue_factor_segmento')
                ->comment('UUID del rate_plan en Channex para push de precios');
        });
    }

    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn([
                'revenue_min_precio',
                'revenue_max_precio',
                'revenue_factor_segmento',
                'revenue_rate_plan_id',
            ]);
        });
    }
};
