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
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->enum('tipo', ['apartamento', 'zona_comun']);
            $table->unsignedBigInteger('apartamento_id')->nullable();
            $table->unsignedBigInteger('zona_comun_id')->nullable();
            $table->unsignedBigInteger('empleada_id'); // Quien reporta la incidencia
            $table->unsignedBigInteger('apartamento_limpieza_id')->nullable(); // Relación con la limpieza si existe
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->enum('estado', ['pendiente', 'en_proceso', 'resuelta', 'cerrada'])->default('pendiente');
            $table->json('fotos')->nullable(); // Array de URLs de fotos
            $table->text('solucion')->nullable(); // Solución aplicada por admin
            $table->unsignedBigInteger('admin_resuelve_id')->nullable(); // Admin que resuelve
            $table->timestamp('fecha_resolucion')->nullable();
            $table->text('observaciones_admin')->nullable();
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['tipo', 'estado']);
            $table->index(['empleada_id']);
            $table->index(['apartamento_id']);
            $table->index(['zona_comun_id']);
            $table->index(['prioridad']);
            $table->index(['created_at']);
            
            // Claves foráneas
            $table->foreign('apartamento_id')->references('id')->on('apartamentos')->onDelete('cascade');
            $table->foreign('zona_comun_id')->references('id')->on('zona_comuns')->onDelete('cascade');
            $table->foreign('empleada_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('apartamento_limpieza_id')->references('id')->on('apartamento_limpieza')->onDelete('cascade');
            $table->foreign('admin_resuelve_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
