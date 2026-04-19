<?php

namespace App\Console\Commands;

use App\Models\Edificio;
use App\Services\CerraduraFallbackService;
use Illuminate\Console\Command;

/**
 * [2026-04-19] Desactiva el modo fallback de un edificio+proveedor tras
 * resolver manualmente la incidencia con la cerradura. Uso:
 *
 *   php artisan cerraduras:desactivar-fallback 1 tuya
 *   php artisan cerraduras:desactivar-fallback 1 ttlock
 *
 * Envia notificacion WhatsApp al equipo confirmando la vuelta a la
 * normalidad.
 */
class DesactivarFallbackCerradura extends Command
{
    protected $signature = 'cerraduras:desactivar-fallback {edificio_id} {proveedor : tuya|ttlock}';

    protected $description = 'Desactiva el modo fallback de un edificio+proveedor tras restaurar la cerradura';

    public function handle(CerraduraFallbackService $fallback): int
    {
        $edificioId = (int) $this->argument('edificio_id');
        $proveedor = strtolower((string) $this->argument('proveedor'));

        $edificio = Edificio::find($edificioId);
        if (!$edificio) {
            $this->error("Edificio {$edificioId} no encontrado.");
            return self::FAILURE;
        }

        if (!in_array($proveedor, ['tuya', 'ttlock'], true)) {
            $this->error("Proveedor debe ser 'tuya' o 'ttlock'. Recibido: {$proveedor}");
            return self::FAILURE;
        }

        $campoActivo = "fallback_{$proveedor}_activo";
        if (!$edificio->{$campoActivo}) {
            $this->warn("El edificio {$edificioId} NO esta en modo fallback para {$proveedor}. Nada que hacer.");
            return self::SUCCESS;
        }

        $fallback->desactivarFallback($edificio, $proveedor);
        $this->info("Modo fallback desactivado para edificio {$edificio->nombre} proveedor {$proveedor}.");
        $this->info("Las proximas reservas recibiran PIN unico normal.");

        return self::SUCCESS;
    }
}
