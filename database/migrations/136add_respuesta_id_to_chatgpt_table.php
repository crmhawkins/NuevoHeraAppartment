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
        Schema::table('whatsapp_mensaje_chatgpt', function (Blueprint $table) {
            $table->string('respuesta_id')->nullable()->after('respuesta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_mensaje_chatgpt', function (Blueprint $table) {
            $table->dropColumn('respuesta_id');
        });
    }
};
