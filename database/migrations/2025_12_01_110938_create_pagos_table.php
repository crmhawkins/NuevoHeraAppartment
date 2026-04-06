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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->string('stripe_payment_intent_id')->unique()->nullable();
            $table->string('stripe_checkout_session_id')->unique()->nullable();
            $table->enum('metodo_pago', ['stripe', 'transferencia', 'efectivo', 'otro'])->default('stripe');
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido', 'cancelado', 'reembolsado'])->default('pendiente');
            $table->decimal('monto', 12, 2);
            $table->string('moneda', 3)->default('EUR');
            $table->text('descripcion')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->timestamp('fecha_vencimiento')->nullable();
            $table->text('notas')->nullable();
            $table->string('referencia_externa')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['reserva_id', 'estado']);
            $table->index('stripe_payment_intent_id');
            $table->index('stripe_checkout_session_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
