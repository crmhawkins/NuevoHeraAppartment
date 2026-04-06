<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('whatsapp_mensaje_chatgpt', function (Blueprint $table) {
            $table->foreignId('whatsapp_mensaje_id')->nullable()->constrained('whatsapp_mensajes')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('whatsapp_mensaje_chatgpt', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_mensaje_id']);
            $table->dropColumn('whatsapp_mensaje_id');
        });
    }
};
