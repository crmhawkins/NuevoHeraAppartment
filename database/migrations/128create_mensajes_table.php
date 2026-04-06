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
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->uuid('channex_message_id')->unique(); // ota_message_id
            $table->uuid('booking_id')->nullable();
            $table->uuid('thread_id')->nullable(); // message_thread_id
            $table->uuid('property_id');
            $table->string('sender'); // "guest", "hotel", "system", etc.
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('have_attachment')->default(false);
            $table->timestamp('received_at')->nullable(); // Desde timestamp original
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
