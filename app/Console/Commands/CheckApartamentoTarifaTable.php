<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckApartamentoTarifaTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:apartamento-tarifa-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar la tabla pivot apartamento_tarifa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando tabla pivot apartamento_tarifa...');
        
        // Verificar si existe la tabla apartamento_tarifa
        if (Schema::hasTable('apartamento_tarifa')) {
            $this->info('✓ Tabla apartamento_tarifa existe');
            
            // Obtener la estructura de la tabla
            $columns = Schema::getColumnListing('apartamento_tarifa');
            $this->info('Columnas en la tabla apartamento_tarifa:');
            foreach ($columns as $column) {
                $this->line("  - {$column}");
            }
            
            // Verificar columnas específicas que necesita la relación
            $requiredColumns = [
                'apartamento_id',
                'tarifa_id',
                'activo'
            ];
            
            $this->info('Verificando columnas requeridas:');
            foreach ($requiredColumns as $column) {
                if (in_array($column, $columns)) {
                    $this->info("  ✓ {$column}");
                } else {
                    $this->error("  ✗ {$column} - FALTANTE");
                }
            }
            
        } else {
            $this->error('✗ Tabla apartamento_tarifa NO existe');
        }
        
        $this->info('Verificación completada.');
    }
}

