<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Apartamento;

class TestTarifasPage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tarifas-page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar que la página de tarifas funciona correctamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Probando página de tarifas...');
        
        // Simular lo que hace el controlador de tarifas
        $apartamentos = Apartamento::with('edificioName')->get();
        
        $this->info("Total de apartamentos cargados: {$apartamentos->count()}");
        
        // Verificar que cada apartamento tenga su edificio cargado correctamente
        $errors = 0;
        foreach ($apartamentos as $apartamento) {
            if (!$apartamento->edificioName) {
                $this->error("✗ Apartamento {$apartamento->id} ({$apartamento->nombre}): No tiene edificio");
                $errors++;
            } else {
                $this->line("✓ Apartamento {$apartamento->id} ({$apartamento->nombre}): {$apartamento->edificioName->nombre}");
            }
        }
        
        if ($errors === 0) {
            $this->info('✅ Todos los apartamentos tienen edificio cargado correctamente');
            $this->info('✅ La página de tarifas debería funcionar sin errores');
        } else {
            $this->error("❌ Se encontraron {$errors} errores");
        }
        
        $this->info('Prueba completada.');
    }
}

