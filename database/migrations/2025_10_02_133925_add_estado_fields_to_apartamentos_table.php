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
        Schema::table('apartamentos', function (Blueprint $table) {
            // Campos para plataforma del estado
            $table->string('codigo_establecimiento')->nullable()->comment('Código del establecimiento para plataforma del estado');
            $table->string('pais_iso3', 3)->nullable()->comment('Código ISO3 del país (ESP)');
            $table->string('codigo_municipio_ine')->nullable()->comment('Código INE del municipio');
            $table->string('nombre_municipio')->nullable()->comment('Nombre del municipio');
            $table->string('tipo_establecimiento')->nullable()->comment('Tipo de establecimiento (opcional)');
            
            // Índices para búsquedas
            $table->index('codigo_establecimiento');
            $table->index('pais_iso3');
            $table->index('codigo_municipio_ine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropIndex(['codigo_establecimiento']);
            $table->dropIndex(['pais_iso3']);
            $table->dropIndex(['codigo_municipio_ine']);
            
            $table->dropColumn([
                'codigo_establecimiento',
                'pais_iso3',
                'codigo_municipio_ine',
                'nombre_municipio',
                'tipo_establecimiento'
            ]);
        });
    }
};
