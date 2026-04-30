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
        if (!Schema::hasTable('diario_caja')) return; // [2026-04-30] idempotente
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
