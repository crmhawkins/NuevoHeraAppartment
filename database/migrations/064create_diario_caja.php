<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('diario_caja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gasto_id')->nullable();
            $table->unsignedBigInteger('ingreso_id')->nullable();
            $table->unsignedBigInteger('cuenta_id')->nullable();
            $table->unsignedBigInteger('formas_pago_id')->nullable();
            $table->string('asiento_contable')->nullable();
            $table->string('tipo', 254);
            $table->date('date')->nullable();
            $table->string('concepto', 254)->nullable();
            $table->double('debe')->nullable();
            $table->double('haber')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('gasto_id')->references('id')->on('gastos');
            $table->foreign('ingreso_id')->references('id')->on('ingresos');
            $table->foreign('formas_pago_id')->references('id')->on('formas_pago');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diario_caja');
    }
};
