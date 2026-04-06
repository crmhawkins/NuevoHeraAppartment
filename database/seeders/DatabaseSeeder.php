<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Database\Seeders\PlanContableSeeder as SeedersPlanContableSeeder;
use Illuminate\Database\Seeder;
use PlanContableSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $this->call([
            // Otros seeders
            SeedersPlanContableSeeder::class,
        ]);
    }
}
