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
        Schema::create('lugar_cercanos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')->constrained('apartamentos')->onDelete('cascade');
            $table->string('nombre');
            $table->string('categoria'); // 'que_hay_cerca', 'restaurantes', 'transporte', 'playas', 'aeropuertos'
            $table->string('tipo')->nullable()->comment('Tipo específico: Restaurante, Tren, etc.');
            $table->decimal('distancia', 10, 2)->nullable()->comment('Distancia en km');
            $table->string('unidad_distancia', 10)->default('km')->comment('km o m');
            $table->integer('orden')->default(0)->comment('Orden de visualización');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lugar_cercanos');
    }
};
