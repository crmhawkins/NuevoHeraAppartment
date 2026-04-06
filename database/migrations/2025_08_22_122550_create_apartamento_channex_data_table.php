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
        Schema::create('apartamento_channex_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apartamento_id');
            $table->string('channex_property_id');
            $table->string('channex_room_type_id');
            $table->string('channex_rate_plan_id');
            $table->date('fecha');
            $table->decimal('precio', 10, 2)->nullable();
            $table->boolean('disponible')->default(true);
            $table->integer('cantidad_disponible')->default(1);
            $table->json('restricciones')->nullable(); // Para almacenar todas las restricciones
            $table->json('datos_completos')->nullable(); // Para almacenar la respuesta completa de Channex
            $table->timestamp('ultima_actualizacion')->nullable();
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['apartamento_id', 'fecha']);
            $table->index(['channex_property_id', 'fecha'], 'idx_channex_prop_fecha');
            $table->index('ultima_actualizacion');
            
            // Clave foránea
            $table->foreign('apartamento_id')->references('id')->on('apartamentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartamento_channex_data');
    }
};
