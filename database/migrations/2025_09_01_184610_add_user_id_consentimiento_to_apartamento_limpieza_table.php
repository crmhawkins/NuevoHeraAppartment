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
            $table->unsignedBigInteger('user_id_consentimiento')->nullable();
            $table->foreign('user_id_consentimiento')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            $table->dropForeign(['user_id_consentimiento']);
            $table->dropColumn('user_id_consentimiento');
        });
    }
};
