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
        Schema::table('historial_descuentos', function (Blueprint $table) {
            $table->json('datos_momento')->nullable()->after('datos_channex')->comment('Datos completos del momento de aplicación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_descuentos', function (Blueprint $table) {
            $table->dropColumn('datos_momento');
        });
    }
};
