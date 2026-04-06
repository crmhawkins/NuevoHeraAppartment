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
        Schema::table('cupones', function (Blueprint $table) {
            // Añadir columna tipo_descuento si no existe
            if (!Schema::hasColumn('cupones', 'tipo_descuento')) {
                $table->enum('tipo_descuento', ['porcentaje', 'fijo'])->default('porcentaje')
                    ->after('descripcion')
                    ->comment('porcentaje: % de descuento, fijo: cantidad fija en euros');
            }
            
            // Renombrar 'valor' a 'valor_descuento' si existe
            if (Schema::hasColumn('cupones', 'valor') && !Schema::hasColumn('cupones', 'valor_descuento')) {
                $table->renameColumn('valor', 'valor_descuento');
            }
            
            // Añadir columnas de restricciones de uso
            if (!Schema::hasColumn('cupones', 'usos_maximos')) {
                $table->integer('usos_maximos')->nullable()->after('valor_descuento')
                    ->comment('Número máximo de usos totales (null = ilimitado)');
            }
            
            if (!Schema::hasColumn('cupones', 'usos_por_cliente')) {
                $table->integer('usos_por_cliente')->default(1)->after('usos_maximos')
                    ->comment('Usos máximos por cliente');
            }
            
            if (!Schema::hasColumn('cupones', 'usos_actuales')) {
                $table->integer('usos_actuales')->default(0)->after('usos_por_cliente')
                    ->comment('Contador de usos actuales');
            }
            
            // Añadir restricciones de importe
            if (!Schema::hasColumn('cupones', 'importe_minimo')) {
                $table->decimal('importe_minimo', 10, 2)->nullable()->after('usos_actuales')
                    ->comment('Importe mínimo de reserva para aplicar');
            }
            
            if (!Schema::hasColumn('cupones', 'descuento_maximo')) {
                $table->decimal('descuento_maximo', 10, 2)->nullable()->after('importe_minimo')
                    ->comment('Descuento máximo en euros (para % descuento)');
            }
            
            // Renombrar columnas de fechas si existen con nombres diferentes
            if (Schema::hasColumn('cupones', 'fecha_inicio') && !Schema::hasColumn('cupones', 'fecha_fin')) {
                // Ya existe fecha_inicio, solo añadir fecha_fin
            }
            
            if (!Schema::hasColumn('cupones', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable()->after('fecha_inicio')
                    ->comment('Fecha hasta la que es válido');
            }
            
            // Añadir restricciones de reserva
            if (!Schema::hasColumn('cupones', 'reserva_desde')) {
                $table->date('reserva_desde')->nullable()->after('fecha_fin')
                    ->comment('Válido para reservas desde esta fecha');
            }
            
            if (!Schema::hasColumn('cupones', 'reserva_hasta')) {
                $table->date('reserva_hasta')->nullable()->after('reserva_desde')
                    ->comment('Válido para reservas hasta esta fecha');
            }
            
            if (!Schema::hasColumn('cupones', 'noches_minimas')) {
                $table->integer('noches_minimas')->nullable()->after('reserva_hasta')
                    ->comment('Número mínimo de noches para aplicar');
            }
            
            // Añadir restricciones de apartamentos/edificios
            if (!Schema::hasColumn('cupones', 'apartamentos_ids')) {
                $table->json('apartamentos_ids')->nullable()->after('noches_minimas')
                    ->comment('IDs de apartamentos permitidos (null = todos)');
            }
            
            if (!Schema::hasColumn('cupones', 'edificios_ids')) {
                $table->json('edificios_ids')->nullable()->after('apartamentos_ids')
                    ->comment('IDs de edificios permitidos (null = todos)');
            }
            
            // Añadir creado_por si no existe
            if (!Schema::hasColumn('cupones', 'creado_por')) {
                $table->unsignedBigInteger('creado_por')->nullable()->after('activo');
                $table->foreign('creado_por')->references('id')->on('users')->onDelete('set null');
            }
            
            // Añadir softDeletes si no existe
            if (!Schema::hasColumn('cupones', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cupones', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_descuento',
                'usos_maximos',
                'usos_por_cliente',
                'usos_actuales',
                'importe_minimo',
                'descuento_maximo',
                'fecha_fin',
                'reserva_desde',
                'reserva_hasta',
                'noches_minimas',
                'apartamentos_ids',
                'edificios_ids',
                'deleted_at',
            ]);
            
            if (Schema::hasColumn('cupones', 'creado_por')) {
                $table->dropForeign(['creado_por']);
                $table->dropColumn('creado_por');
            }
        });
    }
};
