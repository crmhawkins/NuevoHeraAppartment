<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('estados')->insert([
            ['id' => 1, 'nombre' => 'Pendiente Cliente', 'created_at' => '2023-10-18 12:19:19', 'updated_at' => '2023-10-18 12:19:19', 'deleted_at' => NULL],
            ['id' => 2, 'nombre' => 'Dni Recibido', 'created_at' => '2023-10-18 12:19:19', 'updated_at' => '2023-10-18 12:19:19', 'deleted_at' => NULL],
            ['id' => 3, 'nombre' => 'Finalizado', 'created_at' => '2023-10-18 12:19:19', 'updated_at' => '2023-10-18 12:19:19', 'deleted_at' => NULL],
            ['id' => 4, 'nombre' => 'Cancelada', 'created_at' => '2023-10-18 12:19:19', 'updated_at' => '2023-10-18 12:19:19', 'deleted_at' => NULL],
        ]);
    }
}
