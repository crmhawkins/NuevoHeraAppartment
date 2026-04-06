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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('icono')->nullable()->comment('Icono Font Awesome');
            $table->string('nombre');
            $table->string('slug')->unique()->nullable()->comment('Slug para URLs');
            $table->text('descripcion')->nullable()->comment('Descripción opcional del servicio');
            $table->integer('orden')->default(0)->comment('Orden de visualización');
            $table->boolean('es_popular')->default(false)->comment('Marcar como servicio popular');
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
        Schema::dropIfExists('servicios');
    }
};
