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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('numero_soporte_documento')->nullable();
            $table->string('telefono_movil')->nullable();
            $table->string('relacion_parentesco')->nullable();
            $table->string('numero_referencia_contrato')->nullable();
            $table->date('fecha_firma_contrato')->nullable();
            $table->dateTime('fecha_hora_entrada')->nullable();
            $table->dateTime('fecha_hora_salida')->nullable();
            $table->integer('numero_habitaciones')->nullable();
            $table->boolean('conexion_internet')->default(false);
            $table->string('tipo_pago')->nullable();
            $table->string('identificacion_medio_pago')->nullable();
            $table->string('titular_medio_pago')->nullable();
            $table->date('fecha_caducidad_tarjeta')->nullable();
            $table->date('fecha_pago')->nullable();
        });

        Schema::table('huespedes', function (Blueprint $table) {
            $table->string('numero_soporte_documento')->nullable();
            $table->string('telefono_movil')->nullable();
            $table->string('direccion')->nullable();
            $table->string('localidad')->nullable(); // Aquí se cierra correctamente con el método "nullable()"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes_and_huespedes', function (Blueprint $table) {
            //
        });
    }
};
