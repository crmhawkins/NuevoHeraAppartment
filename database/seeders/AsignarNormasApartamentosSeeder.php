<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Apartamento;
use App\Models\NormaCasa;

class AsignarNormasApartamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los apartamentos que tienen id_channex (visibles en portal público)
        $apartamentos = Apartamento::whereNotNull('id_channex')->get();
        
        // Obtener todas las normas activas
        $normas = NormaCasa::where('activo', true)->orderBy('orden')->get();
        
        $totalAsignaciones = 0;
        
        foreach ($apartamentos as $apartamento) {
            // Asignar todas las normas activas a cada apartamento
            foreach ($normas as $norma) {
                // Verificar si ya está asignada
                if (!$apartamento->normasCasa()->where('normas_casa.id', $norma->id)->exists()) {
                    $apartamento->normasCasa()->attach($norma->id);
                    $totalAsignaciones++;
                }
            }
        }
        
        $this->command->info("✅ Asignadas {$totalAsignaciones} normas a " . $apartamentos->count() . " apartamentos");
        $this->command->info("   Cada apartamento tiene ahora " . $normas->count() . " normas asignadas");
    }
}




