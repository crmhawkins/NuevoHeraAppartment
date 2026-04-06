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
        // Crear tabla cupon_usos solo si no existe
        if (!Schema::hasTable('cupon_usos')) {
            Schema::create('cupon_usos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cupon_id')->constrained('cupones')->onDelete('cascade');
                $table->foreignId('reserva_id')->nullable()->constrained('reservas')->onDelete('set null');
                $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
                $table->decimal('importe_original', 10, 2)->comment('Importe antes del descuento');
                $table->decimal('descuento_aplicado', 10, 2)->comment('Descuento aplicado en euros');
                $table->decimal('importe_final', 10, 2)->comment('Importe después del descuento');
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                
                // Índices
                $table->index('cupon_id');
                $table->index('reserva_id');
                $table->index('cliente_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupon_usos');
    }
};
