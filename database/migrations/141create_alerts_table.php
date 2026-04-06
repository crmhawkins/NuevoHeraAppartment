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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'info', 'warning', 'error', 'success'
            $table->string('scenario'); // 'reservation_pending', 'payment_due', 'maintenance_required', etc.
            $table->string('title');
            $table->text('content');
            $table->string('action_url')->nullable(); // URL a la que dirigir si se hace clic
            $table->string('action_text')->nullable(); // Texto del botón de acción
            $table->boolean('is_dismissible')->default(true); // Si se puede cerrar
            $table->boolean('is_read')->default(false); // Si ya fue leída
            $table->timestamp('expires_at')->nullable(); // Fecha de expiración
            $table->json('metadata')->nullable(); // Datos adicionales en formato JSON
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['scenario', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
