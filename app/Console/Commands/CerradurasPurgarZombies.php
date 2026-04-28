<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-28] Red de seguridad: purga PINs zombie en la cerradura.
 *
 * Recorre reservas con fecha_salida pasada (hace mas de 1 dia) que aun
 * tienen ttlock_pin_id. Para cada uno hace lookup en Tuyalaravel por
 * external_reference, y si existe lo borra (DELETE). Asi liberamos slots
 * que podrian seguir ocupados aunque el PIN haya expirado en Tuya pero
 * sin haber sido borrado del slot fisico.
 *
 * Este comando es complementario al Job `BorrarPinAlVencer`:
 *  - El Job es la primera linea (95%): borra al instante de vencer.
 *  - Este comando recoge el 5% que se perdio (queue caida, redis flush,
 *    excepcion no manejada, etc).
 *
 * SEGURIDAD:
 *  - Solo procesa reservas (external_reference que empieza por "reserva_").
 *    NO toca PINs permanentes de limpiadora/seguridad.
 *  - Solo si fecha_salida + 1 dia < hoy (margen de seguridad).
 *  - Por defecto --dry-run. Hay que pasar --apply explicito para actuar.
 *  - --limit por defecto 50 (no procesa miles de golpe).
 *
 * Uso:
 *   php artisan cerraduras:purgar-zombies                 # dry-run
 *   php artisan cerraduras:purgar-zombies --apply         # aplicar
 *   php artisan cerraduras:purgar-zombies --reserva=6418  # solo una
 *   php artisan cerraduras:purgar-zombies --limit=20
 */
class CerradurasPurgarZombies extends Command
{
    protected $signature = 'cerraduras:purgar-zombies
        {--apply : aplica el DELETE (sin esto solo dry-run)}
        {--reserva= : id de una reserva concreta}
        {--limit=50 : maximo de reservas a procesar}';

    protected $description = 'Borra PINs en Tuyalaravel de reservas ya finalizadas (red de seguridad para liberar slots)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $resId = $this->option('reserva');
        $limit = (int) $this->option('limit') ?: 50;

        $this->info($apply ? '== MODO APLICAR ==' : '== DRY-RUN ==');

        $url = config('services.tuya_app.url');
        $key = config('services.tuya_app.api_key');
        if (empty($url) || empty($key)) {
            $this->error('Falta TUYA_APP_URL/key');
            return self::FAILURE;
        }

        $query = Reserva::whereNotNull('ttlock_pin_id')
            ->whereDate('fecha_salida', '<', now()->subDay()->toDateString())
            ->whereNotIn('estado_id', [4, 9])
            ->orderBy('fecha_salida', 'desc');
        if ($resId) {
            $query = Reserva::where('id', $resId)->whereNotNull('ttlock_pin_id');
        }
        $reservas = $query->limit($limit)->get();

        if ($reservas->isEmpty()) {
            $this->info('No hay PINs zombie. Nada que purgar.');
            return self::SUCCESS;
        }

        $this->info("Encontradas {$reservas->count()} reserva(s) con PIN aun no purgado:");
        $purgados = $errores = $no_existian = 0;

        foreach ($reservas as $r) {
            $ref = "reserva_{$r->id}";
            $this->line("--- #{$r->id} salida={$r->fecha_salida} ttlock_pin_id={$r->ttlock_pin_id}");

            try {
                $look = Http::withHeaders(['X-API-Key' => $key])->timeout(15)
                    ->get(rtrim($url, '/') . "/api/pins/by-reference/" . rawurlencode($ref));

                if ($look->status() === 404) {
                    $this->comment('    no existe en Tuyalaravel (ya borrado), limpio BD');
                    if ($apply) {
                        $r->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                    }
                    $no_existian++;
                    continue;
                }

                if (!$look->successful()) {
                    $this->warn("    HTTP {$look->status()} consultando — salto, reintentaremos otro dia");
                    $errores++;
                    continue;
                }

                $internalId = $look->json('data.id');
                if (!$internalId) {
                    $this->warn('    sin id interno, salto');
                    $errores++;
                    continue;
                }

                if (!$apply) {
                    $this->comment("    [dry-run] borraria id={$internalId}");
                    $purgados++;
                    continue;
                }

                $del = Http::withHeaders(['X-API-Key' => $key])->timeout(20)
                    ->delete(rtrim($url, '/') . "/api/pins/{$internalId}");
                if ($del->successful()) {
                    $r->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                    $this->info("    ✓ borrado");
                    $purgados++;
                } else {
                    $this->error("    HTTP {$del->status()} al borrar: " . mb_substr($del->body(), 0, 200));
                    $errores++;
                }
            } catch (\Throwable $e) {
                $this->error('    excepcion: ' . $e->getMessage());
                $errores++;
            }
        }

        $this->line('');
        $this->info("Resumen: purgados={$purgados}, no existian={$no_existian}, errores={$errores}");
        if (!$apply) {
            $this->comment('Ningun DELETE ejecutado. Para aplicar usa --apply');
        }
        return self::SUCCESS;
    }
}
