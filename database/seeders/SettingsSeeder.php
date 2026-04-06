<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Settings para la plataforma del estado - subida de viajeros
        $settings = [
            [
                'key' => 'codigo_arrendador',
                'value' => null,
                'description' => 'Código del arrendador para la plataforma del estado'
            ],
            [
                'key' => 'aplicacion',
                'value' => 'HAWKINS_SUITES',
                'description' => 'Nombre de la aplicación para identificación en plataforma del estado'
            ],
            [
                'key' => 'credenciales',
                'value' => null,
                'description' => 'Credenciales de acceso a la plataforma del estado (JSON)'
            ],
            [
                'key' => 'ca_path',
                'value' => null,
                'description' => 'Ruta del certificado CA para conexión segura con plataforma del estado'
            ]
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description']
                ]
            );
        }
    }
}
