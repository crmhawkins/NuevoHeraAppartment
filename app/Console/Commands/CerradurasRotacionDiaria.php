<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Services\AccessCodeService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-29] Rotacion diaria de PINs en cerraduras (Tuya / TTLock).
 *
 * Reglas inviolables del sistema (CLAUDE.md seccion 0):
 *  - Maximo 9 PINs por cerradura: 7 entrantes + 1 emergencia + 1 limpiadoras.
 *  - PROHIBIDO programar PINs futuros con dias o semanas de antelacion.
 *  - PINs se borran el dia de salida a las 11:00.
 *  - Cada dia: primero borrar salientes, despues programar entrantes.
 *
 * Este comando implementa esas reglas. Se programa en Kernel.php para
 * ejecutar diariamente a las 11:05 (despues de la hora de checkout).
 *
 * Flujo:
 *  1. BORRAR salientes — reservas con fecha_salida = hoy y ttlock_pin_id
 *     no nulo. Para cada una llama a Tuyalaravel DELETE /api/pins/{id}.
 *  2. PROGRAMAR entrantes — reservas con fecha_entrada = hoy y
 *     codigo_enviado_cerradura = 0. Llama a AccessCodeService::generarYProgramar.
 *  3. VERIFICAR slots — para cada lock_id usado, consulta pins-count y
 *     alerta si registered > 9 (deberia ser <= 9 tras limpieza).
 *
 * Idempotente: ejecutar dos veces el mismo dia no rompe nada.
 *
 * Opciones:
 *  --dry-run     no ejecuta DELETE/POST, solo informa
 *  --reserva=ID  procesa solo esa reserva (debug)
 *
 * Salida util para humanos + log estructurado para alerta.
 */
class CerradurasRotacionDiaria extends Command
{
    protected $signature = 'cerraduras:rotacion-diaria
        {--dry-run : no aplicar cambios, solo informar}
        {--reserva= : procesar solo una reserva especifica}';

    protected $description = 'Rotacion diaria: borra PINs de salientes y programa los de entrantes (regla 9 slots maximo)';

    private const MAX_SLOTS = 9;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $resId = $this->option('reserva');
        $hoy = Carbon::today();

        $this->info($dryRun ? '== DRY-RUN ==' : '== APLICANDO ==');
        $this->info("Fecha: {$hoy->toDateString()}");

        $tuyaUrl = config('services.tuya_app.url');
        $tuyaKey = config('services.tuya_app.api_key');
        if (empty($tuyaUrl) || empty($tuyaKey)) {
            $this->error('Falta TUYA_APP_URL/key en config');
            return self::FAILURE;
        }

        $stats = [
            'salientes_total'      => 0,
            'salientes_borrados'   => 0,
            'salientes_errores'    => 0,
            'entrantes_total'      => 0,
            'entrantes_programados'=> 0,
            'entrantes_errores'    => 0,
            'locks_revisados'      => 0,
            'locks_saturados'      => 0,
        ];

        // ============================================================
        // 1) BORRAR SALIENTES
        // ============================================================
        $this->newLine();
        $this->line('--- 1) Borrar PINs de salientes (fecha_salida = hoy) ---');

        $qSalientes = Reserva::with('apartamento')
            ->whereNotNull('ttlock_pin_id')
            ->where('codigo_enviado_cerradura', 1)
            ->whereDate('fecha_salida', '<=', $hoy)
            ->whereNotIn('estado_id', [4, 9])
            ->whereNull('deleted_at');
        if ($resId) {
            $qSalientes->where('id', $resId);
        }
        $salientes = $qSalientes->get();
        $stats['salientes_total'] = $salientes->count();
        $this->info("Encontradas {$salientes->count()} reservas salientes con PIN aun no borrado.");

        foreach ($salientes as $r) {
            $ref = "reserva_{$r->id}";
            $this->line("  #{$r->id} salida={$r->fecha_salida->toDateString()} pin_id={$r->ttlock_pin_id}");

            try {
                $look = Http::withHeaders(['X-API-Key' => $tuyaKey])->timeout(15)
                    ->get(rtrim($tuyaUrl, '/') . "/api/pins/by-reference/" . rawurlencode($ref));

                if ($look->status() === 404) {
                    $this->comment('    no existe en Tuyalaravel (ya borrado), limpio CRM');
                    if (!$dryRun) {
                        $r->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                    }
                    $stats['salientes_borrados']++;
                    continue;
                }
                if (!$look->successful()) {
                    $this->warn("    HTTP {$look->status()} consultando — salto");
                    $stats['salientes_errores']++;
                    continue;
                }
                $internalId = $look->json('data.id');
                if (!$internalId) {
                    $this->warn('    sin id interno, salto');
                    $stats['salientes_errores']++;
                    continue;
                }
                if ($dryRun) {
                    $this->comment("    [dry-run] borraria id={$internalId}");
                    $stats['salientes_borrados']++;
                    continue;
                }
                $del = Http::withHeaders(['X-API-Key' => $tuyaKey])->timeout(20)
                    ->delete(rtrim($tuyaUrl, '/') . "/api/pins/{$internalId}");
                if ($del->successful()) {
                    $r->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                    $this->info("    ✓ borrado");
                    $stats['salientes_borrados']++;
                } else {
                    $this->error("    ✗ HTTP {$del->status()} borrando: " . mb_substr($del->body(), 0, 200));
                    $stats['salientes_errores']++;
                }
            } catch (\Throwable $e) {
                $this->error('    excepcion: ' . $e->getMessage());
                $stats['salientes_errores']++;
            }
        }

        // ============================================================
        // 2) PROGRAMAR ENTRANTES
        // ============================================================
        $this->newLine();
        $this->line('--- 2) Programar PINs de entrantes (fecha_entrada = hoy) ---');

        $qEntrantes = Reserva::with('apartamento')
            ->where('codigo_enviado_cerradura', 0)
            ->whereDate('fecha_entrada', '<=', $hoy)
            ->whereDate('fecha_salida', '>=', $hoy)
            ->whereNotIn('estado_id', [4, 9])
            ->whereNull('deleted_at')
            ->whereHas('apartamento', function ($q) {
                $q->whereIn('tipo_cerradura', ['tuya', 'ttlock']);
            });
        if ($resId) {
            $qEntrantes->where('id', $resId);
        }
        $entrantes = $qEntrantes->get();
        $stats['entrantes_total'] = $entrantes->count();
        $this->info("Encontradas {$entrantes->count()} reservas entrantes con PIN no programado.");

        $accessCodeService = app(AccessCodeService::class);

        foreach ($entrantes as $r) {
            $apto = $r->apartamento?->nombre ?? '?';
            $this->line("  #{$r->id} entrada={$r->fecha_entrada->toDateString()} apto={$apto}");

            if ($dryRun) {
                $this->comment('    [dry-run] programaria PIN');
                $stats['entrantes_programados']++;
                continue;
            }

            try {
                $codigo = $accessCodeService->generarYProgramar($r);
                $r->refresh();
                if ($r->codigo_enviado_cerradura) {
                    $this->info("    ✓ programado pin={$codigo}");
                    $stats['entrantes_programados']++;
                } else {
                    $this->warn("    ✗ no programado (codigo generado pero envio fallo): {$codigo}");
                    $stats['entrantes_errores']++;
                }
            } catch (\Throwable $e) {
                $this->error('    excepcion: ' . $e->getMessage());
                $stats['entrantes_errores']++;
            }
        }

        // ============================================================
        // 3) VERIFICAR SLOTS
        // ============================================================
        $this->newLine();
        $this->line('--- 3) Verificar slots de cada cerradura ---');

        $lockIds = Reserva::with('apartamento')
            ->whereDate('fecha_salida', '>=', $hoy)
            ->whereNotIn('estado_id', [4, 9])
            ->whereNull('deleted_at')
            ->get()
            ->pluck('apartamento.tuyalaravel_lock_id')
            ->filter()
            ->unique()
            ->values();

        foreach ($lockIds as $lockId) {
            $stats['locks_revisados']++;
            try {
                $r = Http::withHeaders(['X-API-Key' => $tuyaKey])->timeout(10)
                    ->get(rtrim($tuyaUrl, '/') . "/api/locks/{$lockId}/pins-count");
                if (!$r->successful()) {
                    $this->warn("  lock {$lockId}: HTTP {$r->status()}");
                    continue;
                }
                $registered = (int) $r->json('data.registered');
                $activos = (int) $r->json('data.active_now');
                $simbolo = $registered > self::MAX_SLOTS ? '⚠️' : '✓';
                $this->line("  {$simbolo} lock {$lockId}: {$registered} registered, {$activos} activos ahora (limite " . self::MAX_SLOTS . ")");
                if ($registered > self::MAX_SLOTS) {
                    $stats['locks_saturados']++;
                    Log::warning('[rotacion-diaria] lock saturado', [
                        'lock_id' => $lockId,
                        'registered' => $registered,
                        'limite' => self::MAX_SLOTS,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->warn("  lock {$lockId}: excepcion " . $e->getMessage());
            }
        }

        // ============================================================
        // RESUMEN
        // ============================================================
        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->line("Salientes:  {$stats['salientes_borrados']}/{$stats['salientes_total']} borrados ({$stats['salientes_errores']} errores)");
        $this->line("Entrantes:  {$stats['entrantes_programados']}/{$stats['entrantes_total']} programados ({$stats['entrantes_errores']} errores)");
        $this->line("Locks:      {$stats['locks_revisados']} revisados ({$stats['locks_saturados']} saturados)");

        Log::info('[rotacion-diaria] resumen', $stats);

        return ($stats['salientes_errores'] + $stats['entrantes_errores'] + $stats['locks_saturados']) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
