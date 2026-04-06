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
        Schema::create('photo_analyses', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->unsignedBigInteger('limpieza_id');
            $table->unsignedBigInteger('categoria_id');
            $table->unsignedBigInteger('empleada_id')->nullable();
            
            // Datos de la imagen
            $table->string('image_url');
            $table->string('categoria_nombre');
            
            // Análisis de OpenAI
            $table->enum('calidad_general', ['excelente', 'buena', 'regular', 'mala']);
            $table->integer('puntuacion');
            $table->boolean('cumple_estandares');
            $table->json('deficiencias');
            $table->text('observaciones');
            $table->json('recomendaciones');
            
            // Metadatos del análisis
            $table->boolean('continuo_bajo_responsabilidad')->default(false);
            $table->timestamp('fecha_analisis');
            $table->json('raw_openai_response')->nullable();
            
            // Relaciones
            $table->foreign('limpieza_id')->references('id')->on('apartamento_limpieza')->onDelete('cascade');
            $table->foreign('categoria_id')->references('id')->on('photo_categoria')->onDelete('cascade');
            $table->foreign('empleada_id')->references('id')->on('users')->onDelete('set null');
            
            // Índices para consultas rápidas
            $table->index(['limpieza_id', 'categoria_id']);
            $table->index('fecha_analisis');
            $table->index('empleada_id');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_analyses');
    }
};
