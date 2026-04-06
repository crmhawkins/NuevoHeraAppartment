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
        Schema::create('categoria_lugares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique()->comment('Nombre de la categoría (ej: restaurantes, playas)');
            $table->string('slug')->unique()->comment('Slug único para la categoría');
            $table->string('tipo_categoria')->comment('Tipo interno: restaurantes, transporte, playas, aeropuertos, etc.');
            $table->text('terminos_busqueda')->nullable()->comment('Términos de búsqueda separados por comas');
            $table->string('amenity_osm')->nullable()->comment('Tipo de amenidad OSM (restaurant, cafe, etc.)');
            $table->string('shop_osm')->nullable()->comment('Tipo de shop OSM (supermarket, etc.)');
            $table->string('tourism_osm')->nullable()->comment('Tipo de turismo OSM (museum, etc.)');
            $table->string('leisure_osm')->nullable()->comment('Tipo de ocio OSM (park, beach, etc.)');
            $table->integer('radio_metros')->default(2000)->comment('Radio de búsqueda en metros');
            $table->integer('limite_resultados')->default(10)->comment('Límite de resultados a buscar');
            $table->integer('orden')->default(0)->comment('Orden de visualización');
            $table->boolean('activo')->default(true)->comment('Categoría activa para búsqueda automática');
            $table->boolean('busqueda_automatica')->default(true)->comment('Incluir en búsqueda automática');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('tipo_categoria');
            $table->index('activo');
            $table->index('busqueda_automatica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_lugares');
    }
};
