<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reserva;
use App\Services\AccessCodeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Comando programado que envía códigos PIN a cerraduras (TTLock y Tuya) para
 * reservas futuras.
 *
 * Ventanas de programacion:
 *  - TTLock: 150 dias (limite cloud ~180d)
 *  - Tuya:   7 dias (cerradura portal compartida, slots limitados ~10)
 *
 * Para reservas fuera de la ventana, AccessCodeService genera el PIN y lo
 * guarda en BD con codigo_enviado_cerradura=false. Este comando se ejecuta
 * a diario y reintenta enviarlos cuando la reserva ya entra en la ventana.
 */
class ProgramarCerradurasProximas extends Command
{
    protected $signature = 'cerraduras:programar-proximas';

    protected $description = 'Programa en cerraduras (TTLock/Tuya) los códigos de reservas próximas';

    public function handle(): int
    {
        $hoy = Carbon::now()->toDateString();
        $limiteTTLock = Carbon::now()->addDays(150)->toDateString();
        $limiteTuya   = Carbon::now()->addDays(7)->toDateString();

        // Reservas candidatas: con PIN generado pero no enviado, no canceladas,
        // entrada dentro de la ventana de su proveedor.
        $reservas = Reserva::with(['apartamento'])
            ->whereNotNull('codigo_acceso')
            ->where('codigo_enviado_cerradura', false)
            ->where('estado_id', '!=', 4) // no canceladas
            ->where('fecha_entrada', '>=', $hoy)
            ->whereHas('apartamento', function ($q) use ($limiteTTLock, $limiteTuya) {
                $q->where(function ($inner) use ($limiteTTLock, $limiteTuya) {
                    // TTLock: ventana 150d
                    $inner->where(function ($sub) use ($limiteTTLock) {
                        $sub->whereNotNull('ttlock_lock_id')
                            ->where('ttlock_lock_id', '!=', '')
                            ->whereHas('reservas', fn($r) => $r->where('fecha_entrada', '<=', $limiteTTLock), '>=', 1);
                    })
                    // Tuya: ventana 7d
                    ->orWhere(function ($sub) use ($limiteTuya) {
                        $sub->whereNotNull('tuyalaravel_lock_id')
                            ->whereHas('reservas', fn($r) => $r->where('fecha_entrada', '<=', $limiteTuya), '>=', 1);
                    });
                });
            })
            ->get()
            // Filtrado final en PHP por ventana segun tipo (mas legible que el SQL)
            ->filter(function ($r) use ($limiteTTLock, $limiteTuya) {
                $apt = $r->apartamento;
                if (!$apt) return false;
                $tipo = strtolower($apt->tipo_cerradura ?? '');
                $fechaE = substr((string) $r->fecha_entrada, 0, 10);
                if ($tipo === 'tuya'   && !empty($apt->tuyalaravel_lock_id) && $fechaE <= $limiteTuya)   return true;
                if ($tipo === 'ttlock' && !empty($apt->ttlock_lock_id)      && $fechaE <= $limiteTTLock) return true;
                return false;
            })
            ->values();

        $this->info("Encontradas {$reservas->count()} reservas pendientes de programar cerradura.");

        if ($reservas->isEmpty()) {
            Log::info('cerraduras:programar-proximas - No hay reservas pendientes.');
            return 0;
        }

        $accessCodeService = app(AccessCodeService::class);
        $programadas = 0;
        $errores = 0;

        foreach ($reservas as $reserva) {
            try {
                // Reintenta enviar el PIN existente a la cerradura (TTLock o Tuya).
                // Si sigue fallando, el propio servicio maneja el fallback.
                $ok = $accessCodeService->reintentarOFallback($reserva);
                if ($ok) {
                    $programadas++;
                    $this->info("  Reserva #{$reserva->id} ({$reserva->apartamento->tipo_cerradura}): código programado OK");
                } else {
                    $this->warn("  Reserva #{$reserva->id}: pendiente (se reintentara manana)");
                }
            } catch (\Exception $e) {
                $errores++;
                Log::error('cerraduras:programar-proximas error', [
                    'reserva_id' => $reserva->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Reserva #{$reserva->id}: ERROR - {$e->getMessage()}");
            }
        }

        $this->info("Resultado: {$programadas} programadas, {$errores} errores.");
        Log::info("cerraduras:programar-proximas - Completado: {$programadas} programadas, {$errores} errores.");

        return $errores > 0 ? 1 : 0;
    }
}
