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
        Schema::create('historial_descuentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apartamento_id');
            $table->unsignedBigInteger('tarifa_id');
            $table->unsignedBigInteger('configuracion_descuento_id');
            $table->date('fecha_aplicacion');
            $table->date('fecha_inicio_descuento');
            $table->date('fecha_fin_descuento');
            $table->decimal('precio_original', 10, 2);
            $table->decimal('precio_con_descuento', 10, 2);
            $table->decimal('porcentaje_descuento', 5, 2);
            $table->integer('dias_aplicados');
            $table->decimal('ahorro_total', 10, 2);
            $table->enum('estado', ['pendiente', 'aplicado', 'revertido', 'error'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->json('datos_channex')->nullable(); // Respuesta de Channex
            $table->timestamps();

            $table->foreign('apartamento_id')->references('id')->on('apartamentos')->onDelete('cascade');
            $table->foreign('tarifa_id')->references('id')->on('tarifas')->onDelete('cascade');
            $table->foreign('configuracion_descuento_id')->references('id')->on('configuracion_descuentos')->onDelete('cascade');
            
            $table->index(['apartamento_id', 'fecha_aplicacion']);
            $table->index(['estado', 'fecha_aplicacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_descuentos');
    }
};
