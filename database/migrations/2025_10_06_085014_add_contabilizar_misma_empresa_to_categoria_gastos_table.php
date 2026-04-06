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
        Schema::table('categoria_gastos', function (Blueprint $table) {
            $table->boolean('contabilizar_misma_empresa')->default(false)->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categoria_gastos', function (Blueprint $table) {
            $table->dropColumn('contabilizar_misma_empresa');
        });
    }
};
