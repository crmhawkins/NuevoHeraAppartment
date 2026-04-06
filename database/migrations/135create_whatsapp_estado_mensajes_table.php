<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('whatsapp_estado_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_mensaje_id')->constrained('whatsapp_mensajes')->onDelete('cascade');
            $table->string('estado'); // sent, delivered, read, failed
            $table->string('recipient_id')->nullable();
            $table->timestamp('fecha_estado')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_estado_mensajes');
    }
};
