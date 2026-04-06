<?php

namespace Database\Seeders;

use App\Models\CategoriaLugar;
use Illuminate\Database\Seeder;

class CategoriaLugaresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Restaurantes',
                'tipo_categoria' => 'restaurantes',
                'amenity_osm' => 'restaurant',
                'radio_metros' => 2000,
                'limite_resultados' => 15,
                'orden' => 10,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Cafeterías',
                'tipo_categoria' => 'restaurantes',
                'amenity_osm' => 'cafe',
                'radio_metros' => 1500,
                'limite_resultados' => 10,
                'orden' => 11,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Bares',
                'tipo_categoria' => 'restaurantes',
                'amenity_osm' => 'bar',
                'radio_metros' => 2000,
                'limite_resultados' => 10,
                'orden' => 12,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Supermercados',
                'tipo_categoria' => 'que_hay_cerca',
                'shop_osm' => 'supermarket',
                'radio_metros' => 2000,
                'limite_resultados' => 10,
                'orden' => 20,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Farmacias',
                'tipo_categoria' => 'que_hay_cerca',
                'amenity_osm' => 'pharmacy',
                'radio_metros' => 2000,
                'limite_resultados' => 10,
                'orden' => 21,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Hospitales',
                'tipo_categoria' => 'que_hay_cerca',
                'amenity_osm' => 'hospital',
                'radio_metros' => 5000,
                'limite_resultados' => 5,
                'orden' => 22,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Estaciones de Autobús',
                'tipo_categoria' => 'transporte',
                'amenity_osm' => 'bus_station',
                'radio_metros' => 3000,
                'limite_resultados' => 10,
                'orden' => 30,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Paradas de Autobús',
                'tipo_categoria' => 'transporte',
                'amenity_osm' => 'bus_stop',
                'radio_metros' => 1000,
                'limite_resultados' => 15,
                'orden' => 31,
                'activo' => true,
                'busqueda_automatica' => false, // Muchas paradas, no en automático
            ],
            [
                'nombre' => 'Estaciones de Tren',
                'tipo_categoria' => 'transporte',
                'amenity_osm' => 'train_station',
                'radio_metros' => 10000,
                'limite_resultados' => 5,
                'orden' => 32,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Playas',
                'tipo_categoria' => 'playas',
                'terminos_busqueda' => 'beach,playa',
                'leisure_osm' => 'beach',
                'radio_metros' => 10000,
                'limite_resultados' => 5,
                'orden' => 40,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Aeropuertos',
                'tipo_categoria' => 'aeropuertos',
                'amenity_osm' => 'aerodrome',
                'radio_metros' => 50000,
                'limite_resultados' => 5,
                'orden' => 50,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Museos',
                'tipo_categoria' => 'que_hay_cerca',
                'tourism_osm' => 'museum',
                'radio_metros' => 5000,
                'limite_resultados' => 10,
                'orden' => 23,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Iglesias',
                'tipo_categoria' => 'que_hay_cerca',
                'amenity_osm' => 'place_of_worship',
                'radio_metros' => 3000,
                'limite_resultados' => 10,
                'orden' => 24,
                'activo' => true,
                'busqueda_automatica' => false,
            ],
            [
                'nombre' => 'Parques',
                'tipo_categoria' => 'que_hay_cerca',
                'leisure_osm' => 'park',
                'radio_metros' => 3000,
                'limite_resultados' => 10,
                'orden' => 25,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
            [
                'nombre' => 'Gimnasios',
                'tipo_categoria' => 'que_hay_cerca',
                'amenity_osm' => 'gym',
                'radio_metros' => 3000,
                'limite_resultados' => 10,
                'orden' => 26,
                'activo' => true,
                'busqueda_automatica' => true,
            ],
        ];

        foreach ($categorias as $categoria) {
            CategoriaLugar::updateOrCreate(
                ['tipo_categoria' => $categoria['tipo_categoria'], 'nombre' => $categoria['nombre']],
                $categoria
            );
        }
    }
}
