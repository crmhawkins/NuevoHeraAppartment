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
        Schema::table('reparacion', function (Blueprint $table) {
            $table->string('email')->nullable()->after('telefono');
            $table->text('direccion')->nullable()->after('email');
            $table->string('localidad')->nullable()->after('direccion');
            $table->string('codigo_postal')->nullable()->after('localidad');
            $table->string('provincia')->nullable()->after('codigo_postal');
            $table->string('nif_cif')->nullable()->after('provincia');
            $table->text('observaciones')->nullable()->after('nif_cif');
            $table->boolean('activo')->default(true)->after('observaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reparacion', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'direccion',
                'localidad',
                'codigo_postal',
                'provincia',
                'nif_cif',
                'observaciones',
                'activo'
            ]);
        });
    }
};
