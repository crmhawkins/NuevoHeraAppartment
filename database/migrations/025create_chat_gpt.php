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
        Schema::create('whatsapp_mensaje_chatgpt', function (Blueprint $table) {
            $table->id();
            $table->string('id_mensaje')->nullable();
            $table->string('remitente')->nullable();
            $table->text('mensaje')->nullable();
            $table->text('respuesta')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
