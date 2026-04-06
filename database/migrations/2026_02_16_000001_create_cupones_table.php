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
        Schema::create('cupones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique()->comment('Código del cupón (ej: VERANO2024)');
            $table->string('nombre')->comment('Nombre descriptivo del cupón');
            $table->text('descripcion')->nullable()->comment('Descripción del cupón');
            
            // Tipo de descuento
            $table->enum('tipo_descuento', ['porcentaje', 'fijo'])->default('porcentaje')
                ->comment('porcentaje: % de descuento, fijo: cantidad fija en euros');
            $table->decimal('valor_descuento', 10, 2)->comment('Valor del descuento (% o €)');
            
            // Restricciones de uso
            $table->integer('usos_maximos')->nullable()->comment('Número máximo de usos totales (null = ilimitado)');
            $table->integer('usos_por_cliente')->default(1)->comment('Usos máximos por cliente');
            $table->integer('usos_actuales')->default(0)->comment('Contador de usos actuales');
            
            // Restricciones de importe
            $table->decimal('importe_minimo', 10, 2)->nullable()->comment('Importe mínimo de reserva para aplicar');
            $table->decimal('descuento_maximo', 10, 2)->nullable()->comment('Descuento máximo en euros (para % descuento)');
            
            // Restricciones temporales
            $table->date('fecha_inicio')->nullable()->comment('Fecha desde la que es válido');
            $table->date('fecha_fin')->nullable()->comment('Fecha hasta la que es válido');
            
            // Restricciones de reserva
            $table->date('reserva_desde')->nullable()->comment('Válido para reservas desde esta fecha');
            $table->date('reserva_hasta')->nullable()->comment('Válido para reservas hasta esta fecha');
            $table->integer('noches_minimas')->nullable()->comment('Número mínimo de noches para aplicar');
            
            // Restricciones de apartamentos/edificios
            $table->json('apartamentos_ids')->nullable()->comment('IDs de apartamentos permitidos (null = todos)');
            $table->json('edificios_ids')->nullable()->comment('IDs de edificios permitidos (null = todos)');
            
            // Estado
            $table->boolean('activo')->default(true)->comment('Si el cupón está activo');
            
            // Auditoría
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('codigo');
            $table->index('activo');
            $table->index(['fecha_inicio', 'fecha_fin']);
            
            // Foreign keys
            $table->foreign('creado_por')->references('id')->on('users')->onDelete('set null');
        });
        
        // Tabla para registrar uso de cupones
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupon_usos');
        Schema::dropIfExists('cupones');
    }
};
