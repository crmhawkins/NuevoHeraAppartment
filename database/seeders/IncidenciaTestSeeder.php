<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Incidencia;
use App\Models\User;
use App\Models\Apartamento;

class IncidenciaTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar cualquier usuario
        $empleada = User::first();

        // Buscar un apartamento
        $apartamento = Apartamento::first();

        if ($empleada && $apartamento) {
            Incidencia::create([
                'titulo' => 'Problema de prueba - Grifo goteando',
                'descripcion' => 'El grifo del baño principal está goteando constantemente. Necesita reparación urgente.',
                'tipo' => 'apartamento',
                'apartamento_id' => $apartamento->id,
                'zona_comun_id' => null,
                'empleada_id' => $empleada->id,
                'apartamento_limpieza_id' => null,
                'prioridad' => 'media',
                'estado' => 'pendiente',
                'fotos' => null
            ]);

            $this->command->info('Incidencia de prueba creada exitosamente');
        } else {
            $this->command->error('No se pudo crear la incidencia de prueba. Faltan empleada o apartamento.');
        }
    }
}
