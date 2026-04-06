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
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('categoria')->default('general'); // general, higiene, alimentacion, limpieza, etc.
            $table->decimal('precio_compra', 10, 2)->default(0);
            $table->string('unidad_medida')->default('unidad'); // unidad, ml, l, kg, g, etc.
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->integer('stock_maximo')->nullable();
            
            // Tipo de consumo
            $table->enum('tipo_consumo', ['por_reserva', 'por_tiempo', 'por_persona'])->default('por_reserva');
            
            // Consumo por reserva
            $table->integer('consumo_por_reserva')->nullable(); // cantidad por reserva
            $table->integer('consumo_minimo_reserva')->nullable(); // cantidad mínima por reserva
            $table->integer('consumo_maximo_reserva')->nullable(); // cantidad máxima por reserva
            
            // Consumo por tiempo
            $table->integer('duracion_dias')->nullable(); // días que dura (ej: ambientador 20 días)
            
            // Consumo por persona
            $table->decimal('consumo_por_persona', 8, 2)->nullable(); // cantidad por persona por día
            $table->string('unidad_consumo')->nullable(); // ml, g, etc. por persona
            
            $table->boolean('activo')->default(true);
            $table->string('proveedor')->nullable();
            $table->string('codigo_producto')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
