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
            // Campos para plataforma del estado
            $table->string('pais_iso3', 3)->nullable()->comment('Código ISO3 del país de origen');
            $table->string('codigo_municipio_ine')->nullable()->comment('Código INE del municipio de origen');
            $table->string('nombre_municipio')->nullable()->comment('Nombre del municipio de origen');
            $table->string('telefono2')->nullable()->comment('Teléfono secundario (opcional)');
            
            // Índices para búsquedas
            $table->index('pais_iso3');
            $table->index('codigo_municipio_ine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('huespedes', function (Blueprint $table) {
            $table->dropIndex(['pais_iso3']);
            $table->dropIndex(['codigo_municipio_ine']);
            
            $table->dropColumn([
                'pais_iso3',
                'codigo_municipio_ine',
                'nombre_municipio',
                'telefono2'
            ]);
        });
    }
};
