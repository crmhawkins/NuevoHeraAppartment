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
        Schema::table('clientes', function (Blueprint $table) {

            $table->string('direccion')->nullable();
            $table->string('localidad')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('provincia')->nullable();
            $table->string('estado')->nullable();

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
