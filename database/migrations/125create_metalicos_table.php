<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('metalicos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->decimal('importe', 10, 2);
            $table->unsignedBigInteger('reserva_id');
            $table->date('fecha_ingreso');
            $table->timestamps();

            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metalicos');
    }
};
