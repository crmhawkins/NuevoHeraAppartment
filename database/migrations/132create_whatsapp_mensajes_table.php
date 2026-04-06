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
        Schema::create('whatsapp_mensajes', function (Blueprint $table) {
            $table->id();
            $table->string('mensaje_id')->unique(); // ID del mensaje de WhatsApp
            $table->string('tipo'); // text, image, audio, document, reaction
            $table->text('contenido')->nullable(); // Cuerpo del mensaje
            $table->string('remitente'); // wa_id del cliente
            $table->string('estado')->nullable(); // sent, delivered, read, failed
            $table->string('recipient_id')->nullable(); // ID del destinatario
            $table->timestamp('fecha_mensaje')->nullable(); // Timestamp del mensaje
            $table->json('metadata')->nullable(); // JSON completo del mensaje
            $table->string('conversacion_id')->nullable();
            $table->string('origen_conversacion')->nullable(); // user_initiated, etc.
            $table->timestamp('expiracion_conversacion')->nullable();
            $table->boolean('billable')->nullable();
            $table->string('categoria_precio')->nullable(); // marketing, utility, etc.
            $table->string('modelo_precio')->nullable(); // CBP
            $table->json('errores')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_mensajes');
    }
};
