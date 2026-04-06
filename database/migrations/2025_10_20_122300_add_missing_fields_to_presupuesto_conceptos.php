<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuesto_conceptos', function (Blueprint $table) {
            if (!Schema::hasColumn('presupuesto_conceptos', 'fecha_entrada')) {
                $table->date('fecha_entrada')->nullable()->after('subtotal');
            }
            if (!Schema::hasColumn('presupuesto_conceptos', 'fecha_salida')) {
                $table->date('fecha_salida')->nullable()->after('fecha_entrada');
            }
            if (!Schema::hasColumn('presupuesto_conceptos', 'precio_por_dia')) {
                $table->decimal('precio_por_dia', 10, 2)->nullable()->after('fecha_salida');
            }
            if (!Schema::hasColumn('presupuesto_conceptos', 'dias_totales')) {
                $table->integer('dias_totales')->nullable()->after('precio_por_dia');
            }
            if (!Schema::hasColumn('presupuesto_conceptos', 'precio_total')) {
                $table->decimal('precio_total', 10, 2)->nullable()->after('dias_totales');
            }
        });
    }

    public function down(): void
    {
        Schema::table('presupuesto_conceptos', function (Blueprint $table) {
            if (Schema::hasColumn('presupuesto_conceptos', 'precio_total')) {
                $table->dropColumn('precio_total');
            }
            if (Schema::hasColumn('presupuesto_conceptos', 'dias_totales')) {
                $table->dropColumn('dias_totales');
            }
            if (Schema::hasColumn('presupuesto_conceptos', 'precio_por_dia')) {
                $table->dropColumn('precio_por_dia');
            }
            if (Schema::hasColumn('presupuesto_conceptos', 'fecha_salida')) {
                $table->dropColumn('fecha_salida');
            }
            if (Schema::hasColumn('presupuesto_conceptos', 'fecha_entrada')) {
                $table->dropColumn('fecha_entrada');
            }
        });
    }
};






