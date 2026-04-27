<?php

namespace App\Console\Commands;

use App\Models\Edificio;
use App\Services\CerraduraFallbackService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-27] Auto-desactivacion segura del modo fallback.
 *
 * Cuando un edificio entra en fallback (Tuya/TTLock cae), todas las
 * nuevas reservas reciben el codigo de emergencia. Hasta hoy la
 * desactivacion era 100% manual (admin ejecutaba comando) — riesgo:
 * si admin no se entera, los huespedes siguen recibiendo el codigo de
 * emergencia indefinidamente cuando la cerradura ya recupero.
 *
 * Como funciona este cron (conservador):
 *  1. Cada hora, para cada edificio en fallback, intenta programar un PIN
 *     de prueba (`healthcheck` con fecha pasada inmediata, asi se borra
 *     solo). external_reference: "healthcheck_fallback_{edificio_id}".
 *  2. Si la API responde 200 -> incrementa contador de exitos consecutivos.
 *  3. Tras EXITOS_NECESARIOS exitos consecutivos -> auto-desactiva.
 *  4. Si falla -> reset del contador.
 *
 * Por que conservador (3 exitos): un solo exito puede ser ruido (un retry
 * que casualmente paso). 3 a lo largo de 3 horas dan confianza real de
 * que la API esta de vuelta. La activacion fue al reves: 3 fallos
 * consecutivos. Simetria.
 *
 * Si Tuya/TTLock no responde, el cron simplemente no hace nada (no
 * desactiva, no rompe). Idempotente y seguro.
 */
class CerradurasProbarRecuperacion extends Command
{
    protected $signature = 'cerraduras:probar-recuperacion
        {--edificio= : id de un edificio concreto}
        {--dry-run : muestra que haria sin desactivar}';

    protected $description = 'Comprueba si los edificios en modo fallback ya tienen la cerradura recuperada y desactiva el fallback tras 3 exitos consecutivos';

    public const EXITOS_NECESARIOS = 3;
    private const CACHE_TTL_HORAS = 24;

    public function handle(CerraduraFallbackService $fallbackSvc): int
    {
        $edificioId = $this->option('edificio');
        $dryRun = (bool) $this->option('dry-run');

        $query = Edificio::where(function ($q) {
            $q->where('fallback_tuya_activo', true)
              ->orWhere('fallback_ttlock_activo', true);
        });
        if ($edificioId) $query->where('id', $edificioId);

        $edificios = $query->get();
        if ($edificios->isEmpty()) {
            $this->info('No hay edificios en modo fallback. Nada que probar.');
            return self::SUCCESS;
        }

        $this->info("Probando recuperacion en {$edificios->count()} edificio(s)...");

        foreach ($edificios as $edif) {
            foreach (['tuya', 'ttlock'] as $proveedor) {
                $campoActivo = "fallback_{$proveedor}_activo";
                if (!$edif->{$campoActivo}) continue;

                $this->procesarEdificioProveedor($edif, $proveedor, $fallbackSvc, $dryRun);
            }
        }

        return self::SUCCESS;
    }

    private function procesarEdificioProveedor(
        Edificio $edif,
        string $proveedor,
        CerraduraFallbackService $fallbackSvc,
        bool $dryRun
    ): void {
        $this->line(" · Edificio {$edif->id} ({$edif->nombre}) / proveedor {$proveedor}");

        // Necesitamos un lock_id de algun apartamento de este edificio para probar.
        $lockId = \App\Models\Apartamento::where('edificio_id', $edif->id)
            ->where(function ($q) use ($proveedor) {
                if ($proveedor === 'tuya') $q->whereNotNull('tuyalaravel_lock_id');
                else $q->whereNotNull('ttlock_lock_id');
            })
            ->value($proveedor === 'tuya' ? 'tuyalaravel_lock_id' : 'ttlock_lock_id');

        if (!$lockId) {
            $this->warn("    sin lock_id en este edificio para {$proveedor}, salto");
            return;
        }

        $tuyaAppUrl = config('services.tuya_app.url');
        $apiKey = config('services.tuya_app.api_key');
        if (empty($tuyaAppUrl) || empty($apiKey)) {
            $this->warn('    config tuya_app incompleta');
            return;
        }

        // PIN de prueba con ventana muy estrecha para que se borre solo
        // (ej: 1 minuto en el pasado). Asi no contamina la cerradura.
        $efectivo = Carbon::now()->subMinutes(2)->toDateTimeString();
        $invalido = Carbon::now()->subMinute()->toDateTimeString();
        $pin = '0' . random_int(100000, 999999); // 7 digitos
        $extRef = "healthcheck_fallback_{$edif->id}_{$proveedor}";

        $exitoso = false;
        try {
            $resp = Http::withHeaders(['X-API-Key' => $apiKey])
                ->timeout(20)
                ->post(rtrim($tuyaAppUrl, '/') . '/api/pins', [
                    'lock_id' => $lockId,
                    'name' => 'Healthcheck fallback',
                    'pin' => $pin,
                    'effective_time' => $efectivo,
                    'invalid_time' => $invalido,
                    'external_reference' => $extRef,
                ]);
            $exitoso = $resp->successful();
            if (!$exitoso) {
                $this->line("    intento fallido: HTTP {$resp->status()} " . mb_substr($resp->body(), 0, 100));
            }
        } catch (\Throwable $e) {
            $this->line('    intento fallido: ' . mb_substr($e->getMessage(), 0, 120));
        }

        $cacheKey = "fallback_recovery:{$edif->id}:{$proveedor}";
        $exitosConsecutivos = (int) Cache::get($cacheKey, 0);

        if (!$exitoso) {
            // Reset del contador si fallo. Mantiene fallback activo.
            if ($exitosConsecutivos > 0) {
                Cache::forget($cacheKey);
                $this->line("    reset contador (era {$exitosConsecutivos})");
            }
            return;
        }

        $exitosConsecutivos++;
        $this->info("    ✓ exito {$exitosConsecutivos}/" . self::EXITOS_NECESARIOS);

        if ($exitosConsecutivos >= self::EXITOS_NECESARIOS) {
            if ($dryRun) {
                $this->comment('    [dry-run] alcanzaria umbral, desactivaria fallback');
                return;
            }
            $this->info("    >>> umbral alcanzado, DESACTIVANDO fallback automaticamente");
            $fallbackSvc->desactivarFallback($edif, $proveedor);
            Cache::forget($cacheKey);
            Log::alert("[Fallback] Auto-desactivado tras {$exitosConsecutivos} exitos consecutivos", [
                'edificio_id' => $edif->id, 'proveedor' => $proveedor,
            ]);
        } else {
            Cache::put($cacheKey, $exitosConsecutivos, now()->addHours(self::CACHE_TTL_HORAS));
        }
    }
}
