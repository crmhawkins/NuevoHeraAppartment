<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ARIController;

class FullSyncCommand extends Command
{
    /**
     * El nombre y la firma del comando de la consola.
     *
     * @var string
     */
    protected $signature = 'ari:fullsync';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Sincronización completa de disponibilidad para Channex';

    /**
     * Ejecutar el comando de consola.
     *
     * @return void
     */
    public function handle()
    {
        // Crear instancia del controlador
        $controller = new ARIController();

        // Llamar a la función fullSync
        $controller->fullSync();

        $this->info('Sincronización completa realizada correctamente.');
    }
}
