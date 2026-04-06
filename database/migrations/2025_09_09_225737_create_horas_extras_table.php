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
        Schema::create('horas_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('turno_id')->constrained('turnos_trabajo')->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('horas_contratadas', 4, 2); // Horas que debería trabajar
            $table->decimal('horas_trabajadas', 4, 2); // Horas que realmente trabajó
            $table->decimal('horas_extras', 4, 2); // Diferencia (horas extras)
            $table->text('motivo')->nullable(); // Motivo de las horas extras
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('observaciones_admin')->nullable(); // Observaciones del admin
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'fecha']);
            $table->index('estado');
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horas_extras');
    }
};