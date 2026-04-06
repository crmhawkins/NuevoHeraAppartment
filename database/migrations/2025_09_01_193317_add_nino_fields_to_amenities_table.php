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
        Schema::table('amenities', function (Blueprint $table) {
            // Campos para amenities de niños
            $table->boolean('es_para_ninos')->default(false)->after('categoria');
            $table->integer('edad_minima')->nullable()->after('es_para_ninos');
            $table->integer('edad_maxima')->nullable()->after('edad_minima');
            $table->enum('tipo_nino', ['bebe', 'nino_pequeno', 'nino_grande', 'adolescente'])->nullable()->after('edad_maxima');
            $table->integer('cantidad_por_nino')->default(1)->after('tipo_nino');
            $table->text('notas_ninos')->nullable()->after('cantidad_por_nino');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->dropColumn([
                'es_para_ninos',
                'edad_minima', 
                'edad_maxima',
                'tipo_nino',
                'cantidad_por_nino',
                'notas_ninos'
            ]);
        });
    }
};
