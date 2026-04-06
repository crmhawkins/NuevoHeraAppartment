<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ApartamentosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('apartamentos')->insert([
            ['id' => 1, 'nombre' => 'Atico', 'created_at' => '2023-09-13 14:27:50', 'updated_at' => '2023-09-13 14:27:50', 'deleted_at' => NULL, 'id_booking' => '7115836', 'id_airbnb' => NULL],
            ['id' => 2, 'nombre' => '2A', 'created_at' => '2023-09-13 14:27:50', 'updated_at' => '2023-09-13 14:27:50', 'deleted_at' => NULL, 'id_booking' => '6926580', 'id_airbnb' => NULL],
            ['id' => 3, 'nombre' => '2B', 'created_at' => '2023-09-13 14:27:50', 'updated_at' => '2023-09-13 14:27:50', 'deleted_at' => NULL, 'id_booking' => '6910667', 'id_airbnb' => NULL],
            ['id' => 4, 'nombre' => '1A', 'created_at' => '2023-09-13 14:27:50', 'updated_at' => NULL, 'deleted_at' => NULL, 'id_booking' => '6926565', 'id_airbnb' => NULL],
            ['id' => 5, 'nombre' => '1B', 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL, 'id_booking' => '6759120', 'id_airbnb' => NULL],
            ['id' => 6, 'nombre' => 'BA', 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL, 'id_booking' => '6926514', 'id_airbnb' => NULL],
            ['id' => 7, 'nombre' => 'BB', 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL, 'id_booking' => '6874841', 'id_airbnb' => NULL],
        ]);
    }
}
