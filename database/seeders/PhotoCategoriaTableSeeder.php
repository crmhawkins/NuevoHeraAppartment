<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhotoCategoriaTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('photo_categoria')->insert([
            ['id' => 1, 'nombre' => 'Dormitorio General'],
            ['id' => 2, 'nombre' => 'Dormitorio - Almohada'],
            ['id' => 3, 'nombre' => 'Dormitorio - Canapé'],
            ['id' => 4, 'nombre' => 'Salón General'],
            ['id' => 5, 'nombre' => 'Salón - Sofa y Bajos'],
            ['id' => 6, 'nombre' => 'Cocina General'],
            ['id' => 7, 'nombre' => 'Cocina - Nevera'],
            ['id' => 8, 'nombre' => 'Cocina - Microondas'],
            ['id' => 9, 'nombre' => 'Cocina - Bajos'],
            ['id' => 10, 'nombre' => 'Baño General'],
            ['id' => 11, 'nombre' => 'Baño - Inodoro'],
            ['id' => 12, 'nombre' => 'Baño - Desagüe Ducha'],
        ]);
    }
}
