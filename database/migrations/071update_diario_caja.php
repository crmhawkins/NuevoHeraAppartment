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
        Schema::table('diario_caja', function (Blueprint $table) {
            $table->unsignedBigInteger('estado_id')->nullable();

            $table->foreign('estado_id')->references('id')->on('estados_diario');

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
