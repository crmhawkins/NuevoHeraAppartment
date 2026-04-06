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
        Schema::create('tipos_tareas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // "Limpieza de Apartamento", "Limpieza de Oficina", etc.
            $table->text('descripcion')->nullable();
            $table->enum('categoria', [
                'limpieza_apartamento',
                'limpieza_zona_comun', 
                'limpieza_oficina',
                'preparacion_amenities',
                'planchado',
                'mantenimiento',
                'otro'
            ])->default('otro');
            $table->integer('prioridad_base')->default(5); // 1-10, donde 10 es máxima prioridad
            $table->integer('tiempo_estimado_minutos')->default(30); // Tiempo estimado en minutos
            $table->integer('dias_max_sin_limpiar')->nullable(); // Días máximos sin limpiar antes de aumentar prioridad
            $table->integer('incremento_prioridad_por_dia')->default(1); // Incremento de prioridad por día sin limpiar
            $table->integer('prioridad_maxima')->default(10); // Prioridad máxima alcanzable
            $table->boolean('requiere_apartamento')->default(false); // Si requiere apartamento específico
            $table->boolean('requiere_zona_comun')->default(false); // Si requiere zona común específica
            $table->boolean('activo')->default(true);
            $table->text('instrucciones')->nullable(); // Instrucciones específicas para la tarea
            $table->timestamps();
            
            // Índices
            $table->index(['categoria', 'activo']);
            $table->index(['prioridad_base']);
            $table->index(['activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_tareas');
    }
};