<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade los campos faltantes requeridos por la legislación española (Real Decreto 933/2021 y Ley 13/2011)
     */
    public function up(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
            // Fecha de caducidad del documento (obligatorio para verificar validez)
            $table->date('fecha_caducidad')->nullable()->after('fecha_expedicion')->comment('Fecha de caducidad del documento de identidad');
            
            // Provincia (obligatorio según Real Decreto 933/2021)
            $table->string('provincia')->nullable()->after('codigo_postal')->comment('Provincia de residencia');
            
            // Lugar de nacimiento (opcional pero recomendado, aparece en el reverso del DNI)
            $table->string('lugar_nacimiento')->nullable()->after('fecha_nacimiento')->comment('Lugar de nacimiento (ciudad, provincia)');
            
            // Fecha y hora de entrada (obligatorio para registro turístico)
            $table->dateTime('fecha_hora_entrada')->nullable()->after('relacion_parentesco')->comment('Fecha y hora de entrada del huésped');
            
            // Fecha y hora de salida (obligatorio para registro turístico)
            $table->dateTime('fecha_hora_salida')->nullable()->after('fecha_hora_entrada')->comment('Fecha y hora de salida del huésped');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_caducidad',
                'provincia',
                'lugar_nacimiento',
                'fecha_hora_entrada',
                'fecha_hora_salida'
            ]);
        });
    }
};
