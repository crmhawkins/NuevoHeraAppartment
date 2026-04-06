<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabaseStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:database-structure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar la estructura de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando estructura de la base de datos...');
        
        // Verificar si existe la tabla apartamentos
        if (Schema::hasTable('apartamentos')) {
            $this->info('✓ Tabla apartamentos existe');
            
            // Obtener la estructura de la tabla
            $columns = Schema::getColumnListing('apartamentos');
            $this->info('Columnas en la tabla apartamentos:');
            foreach ($columns as $column) {
                $this->line("  - {$column}");
            }
            
            // Verificar campos específicos
            if (in_array('edificio', $columns)) {
                $this->warn('⚠ Campo "edificio" existe (este es el problema)');
            }
            
            if (in_array('edificio_id', $columns)) {
                $this->info('✓ Campo "edificio_id" existe');
            } else {
                $this->error('✗ Campo "edificio_id" NO existe');
            }
            
            // Verificar datos en la tabla
            $this->info('Verificando datos...');
            $apartamentos = DB::table('apartamentos')->select('id', 'nombre', 'edificio', 'edificio_id')->take(5)->get();
            
            foreach ($apartamentos as $apartamento) {
                $this->line("  Apartamento ID: {$apartamento->id}, Nombre: {$apartamento->nombre}");
                $this->line("    - Campo 'edificio': " . ($apartamento->edificio ?? 'NULL'));
                $this->line("    - Campo 'edificio_id': " . ($apartamento->edificio_id ?? 'NULL'));
            }
            
        } else {
            $this->error('✗ Tabla apartamentos NO existe');
        }
        
        // Verificar si existe la tabla edificios
        if (Schema::hasTable('edificios')) {
            $this->info('✓ Tabla edificios existe');
            $count = DB::table('edificios')->count();
            $this->info("  - Total de edificios: {$count}");
        } else {
            $this->error('✗ Tabla edificios NO existe');
        }
        
        $this->info('Verificación completada.');
    }
}
