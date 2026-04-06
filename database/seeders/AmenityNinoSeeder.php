<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Amenity;

class AmenityNinoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenitiesNinos = [
            // Amenities para bebés (0-2 años)
            [
                'nombre' => 'Pañales Talla 1-2',
                'categoria' => 'higiene',
                'es_para_ninos' => true,
                'edad_minima' => 0,
                'edad_maxima' => 2,
                'tipo_nino' => 'bebe',
                'cantidad_por_nino' => 5,
                'descripcion' => 'Pañales desechables para bebés de 0-2 años',
                'precio_compra' => 0.50,
                'unidad_medida' => 'unidad',
                'stock_actual' => 100,
                'stock_minimo' => 20,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 5,
                'notas_ninos' => '5 pañales por día de estancia para bebés'
            ],
            [
                'nombre' => 'Toallitas Húmedas Bebé',
                'categoria' => 'higiene',
                'es_para_ninos' => true,
                'edad_minima' => 0,
                'edad_maxima' => 2,
                'tipo_nino' => 'bebe',
                'cantidad_por_nino' => 1,
                'descripcion' => 'Paquete de toallitas húmedas para bebés',
                'precio_compra' => 2.50,
                'unidad_medida' => 'paquete',
                'stock_actual' => 50,
                'stock_minimo' => 10,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 1,
                'notas_ninos' => '1 paquete por estancia para bebés'
            ],
            [
                'nombre' => 'Crema para Pañal',
                'categoria' => 'higiene',
                'es_para_ninos' => true,
                'edad_minima' => 0,
                'edad_maxima' => 2,
                'tipo_nino' => 'bebe',
                'cantidad_por_nino' => 1,
                'descripcion' => 'Crema protectora para la piel del bebé',
                'precio_compra' => 3.00,
                'unidad_medida' => 'tubo',
                'stock_actual' => 30,
                'stock_minimo' => 5,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 1,
                'notas_ninos' => '1 tubo por estancia para bebés'
            ],

            // Amenities para niños pequeños (3-6 años)
            [
                'nombre' => 'Pasta de Dientes Infantil',
                'categoria' => 'higiene',
                'es_para_ninos' => true,
                'edad_minima' => 3,
                'edad_maxima' => 6,
                'tipo_nino' => 'nino_pequeno',
                'cantidad_por_nino' => 1,
                'descripcion' => 'Pasta de dientes con sabor suave para niños',
                'precio_compra' => 1.80,
                'unidad_medida' => 'tubo',
                'stock_actual' => 40,
                'stock_minimo' => 8,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 1,
                'notas_ninos' => '1 tubo por estancia para niños pequeños'
            ],
            [
                'nombre' => 'Cepillo de Dientes Infantil',
                'categoria' => 'higiene',
                'es_para_ninos' => true,
                'edad_minima' => 3,
                'edad_maxima' => 6,
                'tipo_nino' => 'nino_pequeno',
                'cantidad_por_nino' => 1,
                'descripcion' => 'Cepillo de dientes de tamaño infantil',
                'precio_compra' => 1.20,
                'unidad_medida' => 'unidad',
                'stock_actual' => 60,
                'stock_minimo' => 12,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 1,
                'notas_ninos' => '1 cepillo por estancia para niños pequeños'
            ],
            [
                'nombre' => 'Golosinas Variadas',
                'categoria' => 'alimentacion',
                'es_para_ninos' => true,
                'edad_minima' => 3,
                'edad_maxima' => 6,
                'tipo_nino' => 'nino_pequeno',
                'cantidad_por_nino' => 1,
                'descripcion' => 'Bolsa de golosinas variadas para niños',
                'precio_compra' => 2.00,
                'unidad_medida' => 'bolsa',
                'stock_actual' => 80,
                'stock_minimo' => 15,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 1,
                'notas_ninos' => '1 bolsa por estancia para niños pequeños'
            ],

            // Amenities para niños grandes (7-12 años)
            [
                'nombre' => 'Chocolate con Leche',
                'categoria' => 'alimentacion',
                'es_para_ninos' => true,
                'edad_minima' => 7,
                'edad_maxima' => 12,
                'tipo_nino' => 'nino_grande',
                'cantidad_por_nino' => 1,
                'descripcion' => 'Tableta de chocolate con leche para niños',
                'precio_compra' => 1.50,
                'unidad_medida' => 'tableta',
                'stock_actual' => 70,
                'stock_minimo' => 14,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 1,
                'notas_ninos' => '1 tableta por estancia para niños grandes'
            ],
            [
                'nombre' => 'Zumo de Frutas',
                'categoria' => 'alimentacion',
                'es_para_ninos' => true,
                'edad_minima' => 7,
                'edad_maxima' => 12,
                'tipo_nino' => 'nino_grande',
                'cantidad_por_nino' => 2,
                'descripcion' => 'Botellas de zumo de frutas para niños',
                'precio_compra' => 1.20,
                'unidad_medida' => 'botella',
                'stock_actual' => 100,
                'stock_minimo' => 20,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 2,
                'notas_ninos' => '2 botellas por estancia para niños grandes'
            ],
            [
                'nombre' => 'Snacks Salados',
                'categoria' => 'alimentacion',
                'es_para_ninos' => true,
                'edad_minima' => 7,
                'edad_maxima' => 12,
                'tipo_nino' => 'nino_grande',
                'cantidad_por_nino' => 1,
                'descripcion' => 'Bolsa de snacks salados para niños',
                'precio_compra' => 1.80,
                'unidad_medida' => 'bolsa',
                'stock_actual' => 60,
                'stock_minimo' => 12,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 1,
                'notas_ninos' => '1 bolsa por estancia para niños grandes'
            ],

            // Amenities para adolescentes (13+ años)
            [
                'nombre' => 'Bebida Energética',
                'categoria' => 'alimentacion',
                'es_para_ninos' => true,
                'edad_minima' => 13,
                'edad_maxima' => 17,
                'tipo_nino' => 'adolescente',
                'cantidad_por_nino' => 1,
                'descripcion' => 'Bebida energética para adolescentes',
                'precio_compra' => 2.50,
                'unidad_medida' => 'lata',
                'stock_actual' => 40,
                'stock_minimo' => 8,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 1,
                'notas_ninos' => '1 lata por estancia para adolescentes'
            ],
            [
                'nombre' => 'Barra de Cereales',
                'categoria' => 'alimentacion',
                'es_para_ninos' => true,
                'edad_minima' => 13,
                'edad_maxima' => 17,
                'tipo_nino' => 'adolescente',
                'cantidad_por_nino' => 2,
                'descripcion' => 'Barras de cereales para adolescentes',
                'precio_compra' => 1.00,
                'unidad_medida' => 'barra',
                'stock_actual' => 80,
                'stock_minimo' => 16,
                'tipo_consumo' => 'por_reserva',
                'consumo_por_reserva' => 2,
                'notas_ninos' => '2 barras por estancia para adolescentes'
            ]
        ];

        foreach ($amenitiesNinos as $amenityData) {
            Amenity::updateOrCreate(
                ['nombre' => $amenityData['nombre']],
                $amenityData
            );
        }

        $this->command->info('Amenities para niños creados exitosamente!');
    }
}
