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
        Schema::create('huespedes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserva_id')->nullable();
            $table->string('nombre')->nullable();
            $table->string('primer_apellido')->nullable();
            $table->string('segundo_apellido')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('pais')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('numero_identificacion')->nullable();
            $table->date('fecha_expedicion')->nullable();
            $table->string('sexo')->nullable();
            $table->string('email')->nullable();
            $table->string('contador')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('reserva_id')->references('id')->on('reservas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
        });
    }
};
