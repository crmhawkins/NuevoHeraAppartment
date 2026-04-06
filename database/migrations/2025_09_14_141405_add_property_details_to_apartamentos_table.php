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
            $table->integer('bedrooms')->nullable()->comment('Número de habitaciones');
            $table->decimal('bathrooms', 3, 1)->nullable()->comment('Número de baños (permite decimales como 1.5)');
            $table->integer('max_guests')->nullable()->comment('Número máximo de huéspedes');
            $table->decimal('size', 8, 2)->nullable()->comment('Tamaño en metros cuadrados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn(['bedrooms', 'bathrooms', 'max_guests', 'size']);
        });
    }
};
