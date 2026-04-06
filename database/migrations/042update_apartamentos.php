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
        Schema::table('apartamentos', function (Blueprint $table) {
            // $table->unsignedInteger('estado_id')->nullable();
            // $table->unsignedBigInteger('reserva_id')->nullable();
            $table->string('claves')->nullable();

            // $table->foreign('estado_id')->references('id')->on('estados_mensajes');
            // $table->foreign('reserva_id')->references('id')->on('reservas');
            // $table->foreign('cliente_id')->references('id')->on('clientes');

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
