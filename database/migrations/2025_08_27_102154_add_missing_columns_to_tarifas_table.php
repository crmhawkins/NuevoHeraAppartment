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
        Schema::table('tarifas', function (Blueprint $table) {
            // Agregar columnas faltantes para el modelo Tarifa
            $table->string('nombre')->nullable()->after('id');
            $table->text('descripcion')->nullable()->after('nombre');
            $table->decimal('precio', 10, 2)->nullable()->after('descripcion');
            $table->date('fecha_inicio')->nullable()->after('precio');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->boolean('temporada_alta')->default(false)->after('fecha_fin');
            $table->boolean('temporada_baja')->default(false)->after('temporada_alta');
            $table->boolean('activo')->default(true)->after('temporada_baja');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            // Revertir los cambios
            $table->dropColumn([
                'nombre',
                'descripcion',
                'precio',
                'fecha_inicio',
                'fecha_fin',
                'temporada_alta',
                'temporada_baja',
                'activo'
            ]);
        });
    }
};
