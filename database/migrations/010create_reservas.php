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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('apartamento_id')->nullable();
            $table->unsignedBigInteger('estado_id')->nullable();
            $table->string('origen')->nullable();
            $table->date('fecha_entrada')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->string('precio')->nullable();
            $table->tinyInteger('verificado')->nullable();
            $table->tinyInteger('dni_entregado')->nullable();
            $table->tinyInteger('enviado_webpol')->nullable();
            $table->string('codigo_reserva')->nullable();
            $table->string('fecha_limpieza')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('apartamento_id')->references('id')->on('apartamentos');
            $table->foreign('estado_id')->references('id')->on('estados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');

    }
};
