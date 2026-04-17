<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tokens seguros de un solo uso para que el cliente pueda descargarse su
 * factura sin estar autenticado. Se genera al pulsar "Enviar factura al
 * cliente" y se envia por WhatsApp/email. Caduca a los 30 dias por
 * defecto. downloaded_at registra cuando (y si) se uso.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_download_tokens', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id');
            $t->string('token', 64)->unique();
            $t->timestamp('expires_at');
            $t->timestamp('downloaded_at')->nullable();
            $t->string('sent_via', 20)->nullable(); // whatsapp, email, both
            $t->timestamps();

            $t->index('invoice_id');
            $t->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_download_tokens');
    }
};
