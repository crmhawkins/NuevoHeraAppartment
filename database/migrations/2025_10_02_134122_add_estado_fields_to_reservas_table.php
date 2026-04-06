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
        Schema::table('reservas', function (Blueprint $table) {
            // Campos para plataforma del estado
            $table->string('referencia_contrato')->nullable()->comment('Referencia del contrato para plataforma del estado');
            $table->date('fecha_contrato')->nullable()->comment('Fecha del contrato');
            $table->datetime('fecha_hora_entrada')->nullable()->comment('Fecha y hora de entrada (datetime)');
            $table->datetime('fecha_hora_salida')->nullable()->comment('Fecha y hora de salida (datetime)');
            $table->integer('numero_habitaciones')->nullable()->comment('Número de habitaciones de la reserva');
            $table->boolean('conexion_internet')->default(true)->comment('Disponibilidad de conexión a internet');
            
            // Índices para búsquedas
            $table->index('referencia_contrato');
            $table->index('fecha_contrato');
            $table->index('fecha_hora_entrada');
            $table->index('fecha_hora_salida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex(['referencia_contrato']);
            $table->dropIndex(['fecha_contrato']);
            $table->dropIndex(['fecha_hora_entrada']);
            $table->dropIndex(['fecha_hora_salida']);
            
            $table->dropColumn([
                'referencia_contrato',
                'fecha_contrato',
                'fecha_hora_entrada',
                'fecha_hora_salida',
                'numero_habitaciones',
                'conexion_internet'
            ]);
        });
    }
};
