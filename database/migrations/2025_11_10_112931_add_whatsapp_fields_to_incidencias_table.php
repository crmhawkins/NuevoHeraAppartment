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
        Schema::table('incidencias', function (Blueprint $table) {
            // Campos para identificar incidencias desde WhatsApp
            $table->string('telefono_cliente', 20)->nullable()->after('empleada_id');
            $table->enum('origen', ['web', 'whatsapp', 'limpieza'])->default('web')->after('telefono_cliente');
            $table->string('hash_identificador', 64)->nullable()->unique()->after('origen');
            $table->string('apartamento_nombre', 255)->nullable()->after('hash_identificador');
            $table->unsignedBigInteger('reserva_id')->nullable()->after('apartamento_nombre');
            
            // Índices para optimizar consultas
            $table->index('telefono_cliente');
            $table->index('origen');
            $table->index('reserva_id');
            
            // Clave foránea para reserva_id
            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidencias', function (Blueprint $table) {
            // Eliminar clave foránea primero
            $table->dropForeign(['reserva_id']);
            
            // Eliminar índices
            $table->dropIndex(['telefono_cliente']);
            $table->dropIndex(['origen']);
            $table->dropIndex(['reserva_id']);
            
            // Eliminar columnas
            $table->dropColumn([
                'telefono_cliente',
                'origen',
                'hash_identificador',
                'apartamento_nombre',
                'reserva_id'
            ]);
        });
    }
};
