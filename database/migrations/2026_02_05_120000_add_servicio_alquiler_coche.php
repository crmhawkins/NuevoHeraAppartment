<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Servicio "Alquiler de coche": reserva con rango de fechas, siempre no disponible.
     */
    public function up(): void
    {
        if (DB::table('servicios')->where('slug', 'alquiler-de-coche')->exists()) {
            return;
        }

        DB::table('servicios')->insert([
            'nombre' => 'Alquiler de coche',
            'slug' => 'alquiler-de-coche',
            'descripcion' => 'Reserva un vehículo para tu estancia. Indica las fechas de recogida y devolución para comprobar disponibilidad. Servicio sujeto a disponibilidad.',
            'precio' => 115.00,
            'icono' => 'fas fa-car',
            'imagen' => 'alquiler-coche.jpg',
            'orden' => 10,
            'categoria' => null,
            'es_popular' => false,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('servicios')->where('slug', 'alquiler-de-coche')->delete();
    }
};
