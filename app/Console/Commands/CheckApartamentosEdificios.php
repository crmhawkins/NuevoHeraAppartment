<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Apartamento;
use App\Models\Edificio;

class CheckApartamentosEdificios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:apartamentos-edificios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar la integridad de los datos entre apartamentos y edificios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando integridad de datos entre apartamentos y edificios...');
        
        // Verificar apartamentos sin edificio
        $apartamentosSinEdificio = Apartamento::whereNull('edificio_id')->get();
        if ($apartamentosSinEdificio->count() > 0) {
            $this->warn("Se encontraron {$apartamentosSinEdificio->count()} apartamentos sin edificio_id:");
            foreach ($apartamentosSinEdificio as $apartamento) {
                $this->line("  - Apartamento ID: {$apartamento->id}, Nombre: {$apartamento->nombre}");
            }
        } else {
            $this->info('✓ Todos los apartamentos tienen edificio_id asignado');
        }
        
        // Verificar apartamentos con edificio_id inválido
        $apartamentosConEdificioInvalido = Apartamento::whereNotNull('edificio_id')
            ->whereNotIn('edificio_id', Edificio::pluck('id'))
            ->get();
            
        if ($apartamentosConEdificioInvalido->count() > 0) {
            $this->warn("Se encontraron {$apartamentosConEdificioInvalido->count()} apartamentos con edificio_id inválido:");
            foreach ($apartamentosConEdificioInvalido as $apartamento) {
                $this->line("  - Apartamento ID: {$apartamento->id}, Nombre: {$apartamento->nombre}, Edificio ID: {$apartamento->edificio_id}");
            }
        } else {
            $this->info('✓ Todos los edificio_id son válidos');
        }
        
        // Verificar relaciones cargadas correctamente
        $this->info('Verificando carga de relaciones...');
        $apartamentos = Apartamento::with('edificioName')->take(5)->get();
        
        foreach ($apartamentos as $apartamento) {
            if ($apartamento->edificioName) {
                $this->line("  ✓ Apartamento {$apartamento->id}: {$apartamento->edificioName->nombre}");
            } else {
                $this->error("  ✗ Apartamento {$apartamento->id}: No se pudo cargar el edificio");
            }
        }
        
        $this->info('Verificación completada.');
    }
}
