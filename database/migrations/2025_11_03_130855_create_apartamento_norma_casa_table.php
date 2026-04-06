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
        Schema::create('apartamento_norma_casa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')->constrained('apartamentos')->onDelete('cascade');
            $table->foreignId('norma_casa_id')->constrained('normas_casa')->onDelete('cascade');
            $table->timestamps();
            
            // Evitar duplicados
            $table->unique(['apartamento_id', 'norma_casa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartamento_norma_casa');
    }
};
