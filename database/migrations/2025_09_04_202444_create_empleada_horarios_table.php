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
        Schema::create('empleada_horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('horas_contratadas_dia')->default(8); // 4, 6, 8 horas por día
            $table->integer('dias_libres_mes')->default(2); // Días libres por mes
            $table->time('hora_inicio_atencion')->default('08:00:00'); // Horario de atención inicio
            $table->time('hora_fin_atencion')->default('17:00:00'); // Horario de atención fin
            $table->boolean('lunes')->default(true);
            $table->boolean('martes')->default(true);
            $table->boolean('miercoles')->default(true);
            $table->boolean('jueves')->default(true);
            $table->boolean('viernes')->default(true);
            $table->boolean('sabado')->default(false);
            $table->boolean('domingo')->default(false);
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'activo']);
            $table->index(['activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleada_horarios');
    }
};