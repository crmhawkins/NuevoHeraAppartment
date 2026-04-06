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
        Schema::table('empleada_horarios', function (Blueprint $table) {
            // Eliminar la columna dias_libres_mes
            $table->dropColumn('dias_libres_mes');
            
            // Agregar nueva columna para días libres por semana
            $table->integer('dias_libres_semana')->default(0)->after('horas_contratadas_dia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleada_horarios', function (Blueprint $table) {
            // Eliminar la columna dias_libres_semana
            $table->dropColumn('dias_libres_semana');
            
            // Restaurar la columna dias_libres_mes
            $table->integer('dias_libres_mes')->default(0)->after('horas_contratadas_dia');
        });
    }
};