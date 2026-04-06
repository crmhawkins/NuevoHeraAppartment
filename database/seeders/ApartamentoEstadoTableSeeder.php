<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApartamentoEstadoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('apartamento_estado')->insert([
            ['id' => 1, 'nombre' => 'Sucio', 'created_at' => '2023-10-27 12:03:34', 'updated_at' => '2023-10-27 12:03:36', 'deleted_at' => NULL],
            ['id' => 2, 'nombre' => 'En Limpieza', 'created_at' => '2023-10-27 12:03:53', 'updated_at' => '2023-10-27 12:03:54', 'deleted_at' => NULL],
            ['id' => 3, 'nombre' => 'Limpio', 'created_at' => '2023-10-27 12:04:02', 'updated_at' => '2023-10-27 12:04:03', 'deleted_at' => NULL],
        ]);
    }
}
