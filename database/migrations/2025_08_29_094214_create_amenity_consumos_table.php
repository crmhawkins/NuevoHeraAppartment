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
        Schema::create('amenity_consumos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('amenity_id');
            $table->unsignedBigInteger('reserva_id')->nullable();
            $table->unsignedBigInteger('apartamento_id')->nullable();
            $table->unsignedBigInteger('limpieza_id')->nullable();
            $table->unsignedBigInteger('user_id'); // Usuario que registra el consumo
            
            $table->enum('tipo_consumo', ['reserva', 'limpieza', 'reposicion', 'ajuste']);
            $table->integer('cantidad_consumida');
            $table->integer('cantidad_anterior');
            $table->integer('cantidad_actual');
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->decimal('costo_total', 10, 2)->nullable();
            
            $table->text('observaciones')->nullable();
            $table->date('fecha_consumo');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('amenity_id')->references('id')->on('amenities')->onDelete('cascade');
            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('set null');
            $table->foreign('apartamento_id')->references('id')->on('apartamentos')->onDelete('set null');
            $table->foreign('limpieza_id')->references('id')->on('apartamento_limpieza')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenity_consumos');
    }
};
