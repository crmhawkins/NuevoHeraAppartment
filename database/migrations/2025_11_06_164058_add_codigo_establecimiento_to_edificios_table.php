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
        Schema::table('edificios', function (Blueprint $table) {
            $table->string('codigo_establecimiento', 50)->nullable()->after('clave')->comment('Código de establecimiento MIR asignado por el Sistema de Hospedajes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('edificios', function (Blueprint $table) {
            $table->dropColumn('codigo_establecimiento');
        });
    }
};
