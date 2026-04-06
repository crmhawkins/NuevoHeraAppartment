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
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->string('route_name')->unique()->comment('Nombre de la ruta (ej: web.index, web.apartamentos)');
            $table->string('page_title')->nullable()->comment('Título de la página (title tag)');
            $table->text('meta_description')->nullable()->comment('Meta descripción');
            $table->text('meta_keywords')->nullable()->comment('Palabras clave (keywords)');
            $table->string('og_title')->nullable()->comment('Open Graph title');
            $table->text('og_description')->nullable()->comment('Open Graph description');
            $table->string('og_image')->nullable()->comment('Open Graph image URL');
            $table->string('og_type')->default('website')->comment('Open Graph type');
            $table->string('twitter_card')->default('summary_large_image')->comment('Twitter card type');
            $table->string('twitter_title')->nullable()->comment('Twitter title');
            $table->text('twitter_description')->nullable()->comment('Twitter description');
            $table->string('twitter_image')->nullable()->comment('Twitter image URL');
            $table->text('canonical_url')->nullable()->comment('URL canónica');
            $table->text('robots')->nullable()->comment('Meta robots (index, noindex, follow, nofollow)');
            $table->string('hreflang_es')->nullable()->comment('Hreflang para español');
            $table->string('hreflang_en')->nullable()->comment('Hreflang para inglés');
            $table->string('hreflang_fr')->nullable()->comment('Hreflang para francés');
            $table->string('hreflang_de')->nullable()->comment('Hreflang para alemán');
            $table->string('hreflang_it')->nullable()->comment('Hreflang para italiano');
            $table->string('hreflang_pt')->nullable()->comment('Hreflang para portugués');
            $table->text('structured_data')->nullable()->comment('JSON-LD structured data');
            $table->boolean('active')->default(true)->comment('Activo/Inactivo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
