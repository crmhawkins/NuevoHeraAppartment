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
            $table->string('codigo_postal', 10)->nullable()->after('localidad')->comment('Código postal del huésped');
            $table->string('relacion_parentesco')->nullable()->after('telefono_movil')->comment('Relación de parentesco con el cliente principal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
            $table->dropColumn(['codigo_postal', 'relacion_parentesco']);
        });
    }
};
