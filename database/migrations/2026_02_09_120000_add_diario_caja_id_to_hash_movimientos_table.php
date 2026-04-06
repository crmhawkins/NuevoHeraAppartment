<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade diario_caja_id a hash_movimientos para poder borrar el hash
     * cuando se elimina la línea del diario de caja.
     */
    public function up(): void
    {
        Schema::table('hash_movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('diario_caja_id')->nullable()->after('hash');
            $table->foreign('diario_caja_id')->references('id')->on('diario_caja')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hash_movimientos', function (Blueprint $table) {
            $table->dropForeign(['diario_caja_id']);
            $table->dropColumn('diario_caja_id');
        });
    }
};
