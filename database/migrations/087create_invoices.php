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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('budget_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('reserva_id')->nullable();
            $table->unsignedBigInteger('invoice_status_id')->nullable();
            $table->string('concepto')->nullable();
            $table->string('description')->nullable();
            $table->date('fecha')->nullable();
            $table->date('fecha_cobro')->nullable();
            $table->double('base', 10,2)->nullable();
            $table->double('iva', 10, 2)->nullable();
            $table->double('descuento', 10, 2)->nullable();
            $table->string('total', 10, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
