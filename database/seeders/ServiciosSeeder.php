<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Servicio;
use Illuminate\Support\Str;

class ServiciosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servicios = [
            [
                'nombre' => 'Check-in temprano',
                'slug' => 'check-in-temprano',
                'descripcion' => 'Solicita un check-in temprano y comienza tu estancia relajado.',
                'precio' => 25.00,
                'icono' => 'fas fa-clock',
                'orden' => 1,
                'activo' => true,
                'es_popular' => true,
            ],
            [
                'nombre' => 'Check-out tardío',
                'slug' => 'check-out-tardio',
                'descripcion' => 'Aprovecha cada minuto con un check-out tardío.',
                'precio' => 25.00,
                'icono' => 'fas fa-clock',
                'orden' => 2,
                'activo' => true,
                'es_popular' => true,
            ],
            [
                'nombre' => 'Limpieza extra',
                'slug' => 'limpieza-extra',
                'descripcion' => 'Asegura un confort total con servicios de limpieza adicional.',
                'precio' => 30.00,
                'icono' => 'fas fa-broom',
                'orden' => 3,
                'activo' => true,
                'es_popular' => true,
            ],
            [
                'nombre' => 'Mascotas',
                'slug' => 'mascotas',
                'descripcion' => 'Tus amigos peludos son bienvenidos, con un pequeño suplemento.',
                'precio' => 15.00,
                'icono' => 'fas fa-paw',
                'orden' => 4,
                'activo' => true,
                'es_popular' => false,
            ],
            [
                'nombre' => 'Ropa de cama & toallas',
                'slug' => 'ropa-de-cama-toallas',
                'descripcion' => 'Si requiere un cambio de ropa y toallas tendrá un cargo adicional.',
                'precio' => 20.00,
                'icono' => 'fas fa-bed',
                'orden' => 5,
                'activo' => true,
                'es_popular' => false,
            ],
        ];

        foreach ($servicios as $servicio) {
            // Asegurar que precio e imagen tengan valores por defecto si no están definidos
            $servicioData = array_merge([
                'precio' => null,
                'imagen' => null,
            ], $servicio);
            
            Servicio::updateOrCreate(
                ['slug' => $servicio['slug']],
                $servicioData
            );
        }
    }
}
