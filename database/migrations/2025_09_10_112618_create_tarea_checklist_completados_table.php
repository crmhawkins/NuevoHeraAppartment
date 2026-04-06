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
            $table->unsignedBigInteger('tarea_asignada_id');
            $table->unsignedBigInteger('item_checklist_id');
            $table->unsignedBigInteger('checklist_id')->nullable();
            $table->unsignedBigInteger('completado_por');
            $table->tinyInteger('estado')->default(1); // 1 = completado, 0 = no completado
            $table->timestamp('fecha_completado')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['tarea_asignada_id', 'item_checklist_id'], 'tarea_item_idx');
            $table->index('tarea_asignada_id', 'tarea_idx');
            $table->index('item_checklist_id', 'item_idx');
            $table->index('completado_por', 'completado_por_idx');
            
            // Claves foráneas
            $table->foreign('tarea_asignada_id')->references('id')->on('tareas_asignadas')->onDelete('cascade');
            $table->foreign('item_checklist_id')->references('id')->on('items_checklists')->onDelete('cascade');
            $table->foreign('checklist_id')->references('id')->on('checklists')->onDelete('cascade');
            $table->foreign('completado_por')->references('id')->on('users')->onDelete('cascade');
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
