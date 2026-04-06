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
        Schema::create('movimiento_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos')->onDelete('cascade');
            $table->enum('tipo', ['entrada', 'salida', 'ajuste']); // entrada, salida, ajuste
            $table->decimal('cantidad', 10, 2);
            $table->decimal('stock_anterior', 10, 2);
            $table->decimal('stock_nuevo', 10, 2);
            $table->decimal('precio_unitario', 10, 2)->nullable();
            $table->decimal('precio_total', 10, 2)->nullable();
            $table->string('motivo')->nullable(); // compra, reposicion, consumo, ajuste, etc.
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedors')->onDelete('set null');
            $table->foreignId('apartamento_limpieza_id')->nullable()->constrained('apartamento_limpieza')->onDelete('set null');
            $table->string('numero_factura')->nullable();
            $table->date('fecha_movimiento')->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_stocks');
    }
};
