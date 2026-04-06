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
        Schema::create('apartamento_tarifa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')->constrained('apartamentos')->onDelete('cascade');
            $table->foreignId('tarifa_id')->constrained('tarifas')->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Índice único para evitar duplicados
            $table->unique(['apartamento_id', 'tarifa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartamento_tarifa');
    }
};
