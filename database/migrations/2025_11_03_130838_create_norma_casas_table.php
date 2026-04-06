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
        Schema::create('normas_casa', function (Blueprint $table) {
            $table->id();
            $table->string('icono')->nullable()->comment('Icono Font Awesome o emoji');
            $table->string('titulo');
            $table->text('descripcion')->comment('HTML permitido para enlaces y encabezados');
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
        Schema::dropIfExists('normas_casa');
    }
};
