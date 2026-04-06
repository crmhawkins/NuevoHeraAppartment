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
        Schema::table('huespedes', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_comprador_id')->nullable()->after('reserva_id')->comment('ID del cliente que realizó la compra cuando la reserva no es para él');
            $table->foreign('cliente_comprador_id')->references('id')->on('clientes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
            $table->dropForeign(['cliente_comprador_id']);
            $table->dropColumn('cliente_comprador_id');
        });
    }
};
