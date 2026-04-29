<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [2026-04-29] Histórico de precios scrapeados de competidores.
 *
 * Cada vez que el scraper visita un competidor, deja un snapshot
 * por noche futura. Mantenemos histórico para detectar tendencias
 * (¿la competencia subió un 20% este finde? ¿se quedó sin disponibilidad?).
 *
 * Limpieza: borrar registros > 180 días con un comando mensual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_precios_competencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competidor_id')
                ->constrained('revenue_competidores')
                ->cascadeOnDelete();
            $table->date('fecha');                      // noche de estancia
            $table->decimal('precio', 10, 2)->nullable();
            $table->string('moneda', 3)->default('EUR');
            $table->boolean('disponible')->default(false);
            $table->integer('min_noches')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->timestamp('scrapeado_at');
            $table->json('raw_data')->nullable();        // todo lo que devolvió el scraper

            $table->unique(['competidor_id', 'fecha', 'scrapeado_at'], 'unq_comp_fecha_scrape');
            $table->index(['competidor_id', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_precios_competencia');
    }
};
