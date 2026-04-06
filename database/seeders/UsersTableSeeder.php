<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'email_verified_at' => null,
            'password' => Hash::make('123456789'), // Reemplaza 'your_password_here' con la contraseña deseada
            'remember_token' => null,
            'created_at' => '2023-09-11 12:36:28',
            'updated_at' => '2023-09-11 12:36:28',
        ]);
    }
}
