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
        Schema::create('faq_apartamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')->constrained('apartamentos')->onDelete('cascade');
            $table->string('pregunta');
            $table->text('respuesta');
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
        Schema::dropIfExists('faq_apartamentos');
    }
};
