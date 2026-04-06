<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Apartamento;
use App\Models\NormaCasa;

class AsignarMascotasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $normaMascotas = NormaCasa::where('titulo', 'Mascotas')->first();
        
        if (!$normaMascotas) {
            $this->command->error('❌ Norma de Mascotas no encontrada');
            return;
        }
        
        $apartamentos = Apartamento::whereNotNull('id_channex')->get();
        $asignadas = 0;
        
        foreach ($apartamentos as $apartamento) {
            if (!$apartamento->normasCasa()->where('normas_casa.id', $normaMascotas->id)->exists()) {
                $apartamento->normasCasa()->attach($normaMascotas->id);
                $asignadas++;
            }
        }
        
        $this->command->info("✅ Norma de Mascotas asignada a {$asignadas} apartamentos");
    }
}




