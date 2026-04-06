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
        Schema::create('turnos_trabajo', function (Blueprint $table) {
            $table->id();
            $table->date('fecha'); // Fecha del turno
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Empleada
            $table->time('hora_inicio')->nullable(); // Hora de inicio del turno
            $table->time('hora_fin')->nullable(); // Hora de fin del turno
            $table->integer('horas_trabajadas')->default(0); // Horas trabajadas reales
            $table->enum('estado', ['programado', 'en_progreso', 'completado', 'ausente'])->default('programado');
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent(); // Cuándo se generó el turno
            $table->timestamp('fecha_inicio_real')->nullable(); // Cuándo empezó realmente
            $table->timestamp('fecha_fin_real')->nullable(); // Cuándo terminó realmente
            $table->timestamps();
            
            // Índices
            $table->index(['fecha', 'user_id']);
            $table->index(['fecha', 'estado']);
            $table->index(['user_id', 'fecha']);
            $table->unique(['fecha', 'user_id']); // Una empleada solo puede tener un turno por día
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnos_trabajo');
    }
};