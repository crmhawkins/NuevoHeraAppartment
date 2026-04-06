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
        Schema::table('reparacion', function (Blueprint $table) {
            $table->tinyInteger('lunes')->nullable();
            $table->tinyInteger('martes')->nullable();
            $table->tinyInteger('miercoles')->nullable();
            $table->tinyInteger('jueves')->nullable();
            $table->tinyInteger('viernes')->nullable();
            $table->tinyInteger('sabado')->nullable();
            $table->tinyInteger('domingo')->nullable();
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
