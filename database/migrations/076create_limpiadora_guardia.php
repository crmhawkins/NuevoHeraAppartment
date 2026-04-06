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
        Schema::create('limpiadora_guardia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('hora_inicio')->nullable();
            $table->string('hora_fin')->nullable();
            $table->tinyInteger('lunes')->nullable();
            $table->tinyInteger('martes')->nullable();
            $table->tinyInteger('miercoles')->nullable();
            $table->tinyInteger('jueves')->nullable();
            $table->tinyInteger('viernes')->nullable();
            $table->tinyInteger('sabado')->nullable();
            $table->tinyInteger('domingo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
