<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tarifa;
use App\Models\Apartamento;

class TestTarifaCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tarifa-creation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar la creación de tarifas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Probando creación de tarifa...');
        
        try {
            // Simular los datos que vendrían del formulario
            $tarifaData = [
                'nombre' => 'Tarifa de Prueba',
                'descripcion' => 'Esta es una tarifa de prueba',
                'precio' => 100.00,
                'fecha_inicio' => '2025-09-01',
                'fecha_fin' => '2025-12-31',
                'temporada_alta' => false,
                'temporada_baja' => true,
                'activo' => true
            ];
            
            // Crear la tarifa
            $tarifa = Tarifa::create($tarifaData);
            $this->info("✅ Tarifa creada exitosamente con ID: {$tarifa->id}");
            
            // Obtener algunos apartamentos para asignar
            $apartamentos = Apartamento::take(3)->get();
            
            if ($apartamentos->count() > 0) {
                // Asignar apartamentos a la tarifa
                $apartamentosData = $apartamentos->mapWithKeys(function ($apartamento) {
                    return [$apartamento->id => ['activo' => true]];
                });
                
                $tarifa->apartamentos()->attach($apartamentosData);
                $this->info("✅ {$apartamentos->count()} apartamentos asignados a la tarifa");
                
                // Verificar la relación
                $tarifa->load('apartamentos');
                $this->info("✅ Tarifa tiene {$tarifa->apartamentos->count()} apartamentos asignados");
            }
            
            // Limpiar - eliminar la tarifa de prueba
            $tarifa->apartamentos()->detach();
            $tarifa->delete();
            $this->info("✅ Tarifa de prueba eliminada");
            
            $this->info('🎉 ¡Prueba completada exitosamente!');
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
        }
    }
}

