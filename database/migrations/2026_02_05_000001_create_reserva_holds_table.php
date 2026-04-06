<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reserva_holds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apartamento_id');
            $table->unsignedBigInteger('room_type_id')->nullable();
            $table->unsignedBigInteger('reserva_id')->nullable();
            $table->date('fecha_entrada');
            $table->date('fecha_salida');
            $table->string('hold_token')->unique();
            $table->string('estado')->default('activo'); // activo, confirmado, expirado, cancelado
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->foreign('apartamento_id')->references('id')->on('apartamentos')->onDelete('cascade');
            $table->foreign('room_type_id')->references('id')->on('room_types')->onDelete('set null');
            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_holds');
    }
};

