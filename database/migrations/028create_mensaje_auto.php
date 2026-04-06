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
        Schema::create('mensajes_auto', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reserva_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->date('fecha_envio')->nullable();
            //$table->string('categoria_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('reserva_id')->references('id')->on('reservas');
            $table->foreign('cliente_id')->references('id')->on('clientes');
            $table->foreign('categoria_id')->references('id')->on('mensajes_auto_categorias');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
