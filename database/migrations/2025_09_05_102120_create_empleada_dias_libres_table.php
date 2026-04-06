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
        Schema::create('empleada_dias_libres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleada_horario_id')->constrained('empleada_horarios')->onDelete('cascade');
            $table->date('semana_inicio'); // Lunes de la semana
            $table->json('dias_libres'); // Array de días libres [0,1,2] donde 0=domingo, 1=lunes, etc.
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['empleada_horario_id', 'semana_inicio']);
            $table->unique(['empleada_horario_id', 'semana_inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleada_dias_libres');
    }
};