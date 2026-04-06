<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('presupuesto_conceptos', function (Blueprint $table) {
            $table->date('fecha_entrada')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->decimal('precio_por_dia', 10, 2)->nullable();
            $table->integer('dias_totales')->nullable();
            $table->decimal('precio_total', 10, 2)->nullable();
        });
    }

    public function down()
    {
        Schema::table('presupuesto_conceptos', function (Blueprint $table) {
            $table->dropColumn(['fecha_entrada', 'fecha_salida', 'precio_por_dia', 'dias_totales', 'precio_total']);
        });
    }
};
