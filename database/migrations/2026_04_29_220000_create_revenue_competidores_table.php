<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [2026-04-29] Tabla de competidores para Revenue Management.
 *
 * Cada apartamento nuestro tiene N URLs de listings competencia
 * (Booking + Airbnb) que se scrapean cada noche para comparar precios.
 *
 * El admin define los competidores manualmente en la pantalla de
 * configuración del apartamento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_competidores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')
                ->constrained('apartamentos')
                ->cascadeOnDelete();
            $table->enum('plataforma', ['booking', 'airbnb']);
            $table->string('url', 500);
            $table->string('titulo')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamp('ultimo_scrape_at')->nullable();
            $table->timestamp('ultimo_error_at')->nullable();
            $table->text('ultimo_error_msg')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['apartamento_id', 'activo']);
            $table->index('plataforma');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_competidores');
    }
};
