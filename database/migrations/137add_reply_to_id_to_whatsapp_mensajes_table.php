<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('whatsapp_mensajes', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_to_id')->nullable()->after('id');
            $table->foreign('reply_to_id')->references('id')->on('whatsapp_mensajes')->onDelete('set null');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_mensajes', function (Blueprint $table) {
            //
        });
    }
};
