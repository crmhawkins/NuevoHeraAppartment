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
        Schema::create('tarea_checklist_completados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_asignada_id')->constrained('tareas_asignadas')->onDelete('cascade');
            $table->foreignId('item_checklist_id')->constrained('item_checklist_tarea_generals')->onDelete('cascade');
            $table->foreignId('completado_por')->constrained('users')->onDelete('cascade');
            $table->timestamp('fecha_completado');
            $table->timestamps();
            
            // Índice único para evitar duplicados
            $table->unique(['tarea_asignada_id', 'item_checklist_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarea_checklist_completados');
    }
};