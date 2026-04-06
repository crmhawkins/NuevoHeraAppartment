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
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('limpieza_id')->nullable();
            $table->unsignedBigInteger('photo_categoria_id')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('limpieza_id')->references('id')->on('apartamento_limpieza');
            $table->foreign('photo_categoria_id')->references('id')->on('photo_categoria');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
