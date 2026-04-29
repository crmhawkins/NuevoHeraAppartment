<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [2026-04-29] Recomendaciones de precio por apartamento/fecha.
 *
 * El motor de pricing (servidor IA) calcula cada noche un precio
 * recomendado por apartamento × fecha y lo guarda aquí. La pantalla
 * matriz lee de esta tabla.
 *
 * Cuando el admin aplica un precio, se rellena precio_aplicado +
 * aplicado_at + aplicado_por_user_id (auditoría).
 *
 * Una recomendación por apartamento+fecha (UNIQUE). Se reescribe en
 * cada cálculo nocturno.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_recomendaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')
                ->constrained('apartamentos')
                ->cascadeOnDelete();
            $table->date('fecha');

            // Precios
            $table->decimal('precio_actual', 10, 2)->nullable();      // lo que tenemos en Channex
            $table->decimal('precio_recomendado', 10, 2)->nullable(); // lo que sugiere la IA
            $table->decimal('precio_aplicado', 10, 2)->nullable();    // lo que finalmente puso el admin
            $table->timestamp('aplicado_at')->nullable();
            $table->unsignedBigInteger('aplicado_por_user_id')->nullable();

            // Variables que entraron en el cálculo (transparencia)
            $table->decimal('competencia_media', 10, 2)->nullable();
            $table->decimal('competencia_min', 10, 2)->nullable();
            $table->decimal('competencia_max', 10, 2)->nullable();
            $table->integer('competidores_count')->default(0);
            $table->decimal('ocupacion_nuestra_pct', 5, 2)->nullable(); // 0-100
            $table->boolean('es_finde')->default(false);
            $table->boolean('es_festivo')->default(false);
            $table->text('razonamiento')->nullable();

            $table->timestamp('calculado_at');

            $table->unique(['apartamento_id', 'fecha'], 'unq_apt_fecha');
            $table->index(['apartamento_id', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_recomendaciones');
    }
};
