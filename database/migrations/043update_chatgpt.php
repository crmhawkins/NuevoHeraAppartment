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
        // Verificar si la tabla existe antes de modificarla
        if (Schema::hasTable('whatsapp_mensaje_chatgpt')) {
            Schema::table('whatsapp_mensaje_chatgpt', function (Blueprint $table) {
                // Verificar si las columnas ya existen antes de agregarlas
                if (!Schema::hasColumn('whatsapp_mensaje_chatgpt', 'estado_id')) {
                    $table->unsignedBigInteger('estado_id')->nullable();
                }
                if (!Schema::hasColumn('whatsapp_mensaje_chatgpt', 'reserva_id')) {
                    $table->unsignedBigInteger('reserva_id')->nullable();
                }
                if (!Schema::hasColumn('whatsapp_mensaje_chatgpt', 'cliente_id')) {
                    $table->unsignedBigInteger('cliente_id')->nullable();
                }
            });

            // Agregar foreign keys solo si las columnas existen
            Schema::table('whatsapp_mensaje_chatgpt', function (Blueprint $table) {
                if (Schema::hasColumn('whatsapp_mensaje_chatgpt', 'estado_id')) {
                    $table->foreign('estado_id')->references('id')->on('estado_mensajes');
                }
                if (Schema::hasColumn('whatsapp_mensaje_chatgpt', 'reserva_id')) {
                    $table->foreign('reserva_id')->references('id')->on('reservas');
                }
                if (Schema::hasColumn('whatsapp_mensaje_chatgpt', 'cliente_id')) {
                    $table->foreign('cliente_id')->references('id')->on('clientes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('whatsapp_mensaje_chatgpt')) {
            Schema::table('whatsapp_mensaje_chatgpt', function (Blueprint $table) {
                $table->dropForeign(['estado_id', 'reserva_id', 'cliente_id']);
                $table->dropColumn(['estado_id', 'reserva_id', 'cliente_id']);
            });
        }
    }
};
