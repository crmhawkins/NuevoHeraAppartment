<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckTarifasTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:tarifas-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar la estructura de la tabla tarifas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando estructura de la tabla tarifas...');
        
        // Verificar si existe la tabla tarifas
        if (Schema::hasTable('tarifas')) {
            $this->info('✓ Tabla tarifas existe');
            
            // Obtener la estructura de la tabla
            $columns = Schema::getColumnListing('tarifas');
            $this->info('Columnas en la tabla tarifas:');
            foreach ($columns as $column) {
                $this->line("  - {$column}");
            }
            
            // Verificar columnas específicas que necesita el modelo
            $requiredColumns = [
                'nombre',
                'descripcion', 
                'precio',
                'fecha_inicio',
                'fecha_fin',
                'temporada_alta',
                'temporada_baja',
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
            $this->error('✗ Tabla tarifas NO existe');
        }
        
        $this->info('Verificación completada.');
    }
}

