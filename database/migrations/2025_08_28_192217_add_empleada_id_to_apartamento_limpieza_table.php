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
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            $table->unsignedBigInteger('empleada_id')->nullable()->after('reserva_id');
            $table->foreign('empleada_id')->references('id')->on('users')->onDelete('set null');
            $table->index('empleada_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            $table->dropForeign(['empleada_id']);
            $table->dropIndex(['empleada_id']);
            $table->dropColumn('empleada_id');
        });
    }
};
