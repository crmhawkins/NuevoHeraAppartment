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
        Schema::create('tareas_asignadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos_trabajo')->onDelete('cascade');
            $table->foreignId('tipo_tarea_id')->constrained('tipos_tareas')->onDelete('cascade');
            $table->foreignId('apartamento_id')->nullable()->constrained('apartamentos')->onDelete('cascade');
            $table->foreignId('zona_comun_id')->nullable()->constrained('zona_comuns')->onDelete('cascade');
            $table->integer('prioridad_calculada')->default(5); // Prioridad calculada dinámicamente
            $table->integer('orden_ejecucion')->default(1); // Orden en que debe ejecutarse
            $table->enum('estado', ['pendiente', 'en_progreso', 'completada', 'cancelada'])->default('pendiente');
            $table->timestamp('fecha_ultima_limpieza')->nullable(); // Última vez que se limpió
            $table->integer('dias_sin_limpiar')->default(0); // Días sin limpiar
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_inicio_real')->nullable();
            $table->timestamp('fecha_fin_real')->nullable();
            $table->integer('tiempo_real_minutos')->nullable(); // Tiempo real que tardó
            $table->timestamps();
            
            // Índices
            $table->index(['turno_id', 'orden_ejecucion']);
            $table->index(['turno_id', 'estado']);
            $table->index(['prioridad_calculada']);
            $table->index(['fecha_ultima_limpieza']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas_asignadas');
    }
};