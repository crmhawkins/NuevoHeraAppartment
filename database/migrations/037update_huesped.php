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
            $table->string('nacionalidadStr')->nullable();
            $table->string('nacionalidadCode')->nullable();
            $table->string('sexo_str')->nullable();
            $table->string('tipo_documento_str')->nullable();
            $table->string('nacionalidad')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
            $table->dropColumn(['nacionalidadStr', 'nacionalidadCode', 'sexo_str', 'tipo_documento_str', 'nacionalidad']);
        });
    }
};
