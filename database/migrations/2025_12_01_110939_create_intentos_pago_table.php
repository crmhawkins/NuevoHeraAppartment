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
        Schema::create('intentos_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->nullable()->constrained('pagos')->onDelete('cascade');
            $table->foreignId('reserva_id')->nullable()->constrained('reservas')->onDelete('cascade');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_checkout_session_id')->nullable();
            $table->enum('estado', ['iniciado', 'procesando', 'exitoso', 'fallido', 'cancelado'])->default('iniciado');
            $table->decimal('monto', 12, 2);
            $table->string('moneda', 3)->default('EUR');
            $table->text('mensaje_error')->nullable();
            $table->json('respuesta_stripe')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('fecha_intento');
            $table->timestamps();
            
            $table->index(['pago_id', 'estado']);
            $table->index(['reserva_id', 'estado']);
            $table->index('stripe_payment_intent_id');
            $table->index('fecha_intento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intentos_pago');
    }
};
