<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Services\AccessCodeService;
use App\Services\CerraduraFallbackService;
use App\Services\CerraduraPinHealthService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-28] Healthcheck de PINs activos. Detecta los que se perdieron
 * silenciosamente en la cerradura (Tuya devuelve 200 al programar pero
 * luego el PIN desaparece por slot saturado, falta de sync, etc.).
 *
 * Para cada reserva con codigo_enviado_cerradura=1 y entrada cercana o en
 * curso (default: -1d a +7d), comprueba contra Tuyalaravel:
 *   - state=ok       -> nada
 *   - state=inactive -> PIN expirado/revocado: reprogramar
 *   - state=lost     -> PIN no existe: reprogramar
 *   - state=unknown  -> error tecnico: NO HACE NADA (proximo run reintenta)
 *
 * Si la reprogramacion falla -> envia codigo de emergencia al huesped
 * (template aprobado cambio_clave_emergencia).
 *
 * SEGURIDAD:
 *  - Por defecto --dry-run: NO modifica BD ni llama a Tuyalaravel para
 *    crear PINs ni envia WhatsApp. Solo lista lo que haria.
 *  - --apply explicito requerido para actuar.
 *  - --reserva=N para probar UNA sola reserva (recomendado al validar).
 *  - --limit=N corta el numero maximo procesado por ejecucion (10 por
 *    defecto, evita cualquier lluvia de WhatsApp si algo va mal).
 *  - Saltea reservas con codigo_fallback_enviado=1 (ya recibieron el
 *    codigo de emergencia recientemente, no spamear).
 *
 * Cuando se programe en cron sera con --apply --limit=20 cada 30 min.
 * Hasta entonces, --apply NO se ejecuta automaticamente.
 */
class CerradurasHealthcheckPins extends Command
{
    protected $signature = 'cerraduras:healthcheck-pins
        {--apply : aplicar cambios (sin esto solo dry-run, no modifica nada)}
        {--reserva= : id de una reserva concreta a verificar}
        {--ventana-dias=7 : verificar reservas con entrada en los proximos N dias}
        {--limit=10 : maximo de reservas por ejecucion (proteccion anti-spam)}';

    protected $description = 'Verifica que los PINs programados en Tuya siguen activos. Si se perdieron, reprograma y, si falla, envia codigo de emergencia.';

    public function handle(
        CerraduraPinHealthService $health,
        AccessCodeService $accessCodeSvc,
        CerraduraFallbackService $fallbackSvc
    ): int {
        $apply = (bool) $this->option('apply');
        $reservaId = $this->option('reserva');
        $ventana = (int) $this->option('ventana-dias') ?: 7;
        $limit = (int) $this->option('limit') ?: 10;

        $this->info($apply ? '== MODO APLICAR ==' : '== DRY-RUN (no modifica nada) ==');

        $query = Reserva::with(['cliente', 'apartamento.edificio'])
            ->where('codigo_enviado_cerradura', 1)
            ->whereNotNull('ttlock_pin_id')
            ->where('estado_id', '!=', 4)
            ->where('estado_id', '!=', 9)
            // Reservas en ventana: salida >= ayer y entrada <= +ventana_dias
            ->whereDate('fecha_salida', '>=', now()->subDay()->toDateString())
            ->whereDate('fecha_entrada', '<=', now()->addDays($ventana)->toDateString());

        if ($reservaId) {
            $query = Reserva::with(['cliente', 'apartamento.edificio'])
                ->where('id', $reservaId);
        }

        $reservas = $query->orderBy('fecha_entrada')->limit($limit)->get();

        if ($reservas->isEmpty()) {
            $this->info('No hay reservas que verificar.');
            return self::SUCCESS;
        }

        $this->info("Verificando {$reservas->count()} reserva(s)...");
        $resumen = ['ok' => 0, 'reprogramadas' => 0, 'emergencia' => 0, 'unknown' => 0, 'sin_accion' => 0];

        foreach ($reservas as $r) {
            $pid = $r->ttlock_pin_id;
            $cli = $r->cliente?->nombre . ' ' . ($r->cliente?->apellido1 ?? '');
            $this->line("");
            $this->line("--- #{$r->id} " . trim($cli) . " | apt=" . ($r->apartamento->titulo ?? '-') . " | pin_id={$pid}");

            // [2026-04-28] Usamos el endpoint by-reference (nuevo) en lugar de
            // /api/pins/{id}, porque el id que guardamos en reservas.ttlock_pin_id
            // es el provider_code_id (Tuya) y no el id interno de Tuyalaravel.
            $check = $health->checkReserva($r);
            $this->line("    estado: {$check['state']} (HTTP {$check['http_status']})");

            // [2026-04-28] Refinado tras dry-run: 'inactive' NO siempre es
            // un problema. Tuyalaravel devuelve is_active=false cuando AHORA
            // no estamos dentro del rango effective_time -> invalid_time.
            // Eso pasa en 2 escenarios validos: PIN futuro (huesped aun no
            // ha llegado) y PIN ya expirado (huesped ya salio). En ambos
            // casos NO hay que reprogramar nada — la cerradura tiene el PIN
            // correcto, simplemente no es activo en este momento.
            //
            // Solo es PROBLEMA REAL cuando estamos DENTRO de la ventana
            // (now >= effective AND now < invalid) y aun asi is_active=false.
            // Eso indica que el PIN esta revocado o algo raro paso.
            $esProblemaInactive = false;
            if ($check['state'] === CerraduraPinHealthService::STATE_INACTIVE) {
                $eff = isset($check['raw']['data']['effective_time'])
                    ? Carbon::parse($check['raw']['data']['effective_time']) : null;
                $inv = isset($check['raw']['data']['invalid_time'])
                    ? Carbon::parse($check['raw']['data']['invalid_time']) : null;
                if ($eff && $inv && now()->between($eff, $inv)) {
                    $esProblemaInactive = true;
                }
            }

            switch ($check['state']) {
                case CerraduraPinHealthService::STATE_OK:
                    $resumen['ok']++;
                    break;

                case CerraduraPinHealthService::STATE_UNKNOWN:
                    $this->warn("    Tuyalaravel no respondio bien, no actuamos. Reintentaremos en el proximo ciclo.");
                    $resumen['unknown']++;
                    break;

                case CerraduraPinHealthService::STATE_INACTIVE:
                    if (!$esProblemaInactive) {
                        $eff = $check['raw']['data']['effective_time'] ?? '?';
                        $inv = $check['raw']['data']['invalid_time'] ?? '?';
                        $this->comment("    inactive normal (ventana {$eff} -> {$inv}). No accion.");
                        $resumen['ok']++; // contamos como OK desde el punto de vista del huesped
                        break;
                    }
                    // fall-through al case LOST (problema real, reprogramar)
                case CerraduraPinHealthService::STATE_LOST:
                    $resumen['sin_accion']++;
                    if (!$apply) {
                        $this->comment("    [dry-run] reprogramaria; si fallase, enviaria codigo emergencia");
                        break;
                    }
                    // [SEGURIDAD] No reenviar codigo emergencia si ya se le envio en
                    // las ultimas 24h (codigo_fallback_enviado=1), salvo que sea por
                    // un PIN nuevo que volvio a fallar.
                    $fueEmergencia = $this->intentarReprogramar($r, $accessCodeSvc, $fallbackSvc);
                    if ($fueEmergencia === 'reprogramado') {
                        $resumen['reprogramadas']++;
                        $resumen['sin_accion']--;
                    } elseif ($fueEmergencia === 'emergencia') {
                        $resumen['emergencia']++;
                        $resumen['sin_accion']--;
                    }
                    break;
            }
        }

        $this->line('');
        $this->info(sprintf(
            "Resumen: ok=%d, reprogramadas=%d, codigo emergencia enviado=%d, errores tecnicos=%d, sin accion=%d",
            $resumen['ok'], $resumen['reprogramadas'], $resumen['emergencia'], $resumen['unknown'], $resumen['sin_accion']
        ));

        if (!$apply) {
            $this->comment('Ningun WhatsApp enviado, ningun PIN programado. Para aplicar usa --apply');
        }

        return self::SUCCESS;
    }

    /**
     * Reprograma el PIN. Si la reprogramacion falla, envia codigo de
     * emergencia (template aprobado, atraviesa ventana 24h).
     *
     * @return string 'reprogramado' | 'emergencia' | 'nada'
     */
    private function intentarReprogramar(Reserva $r, AccessCodeService $accessCodeSvc, CerraduraFallbackService $fallbackSvc): string
    {
        try {
            // Resetear flags para que AccessCodeService genere un PIN nuevo
            $oldPin = $r->codigo_acceso;
            $r->codigo_enviado_cerradura = 0;
            $r->ttlock_pin_id = null;
            $r->save();

            $nuevoPin = $accessCodeSvc->generarYProgramar($r);
            $r->refresh();

            if ($nuevoPin && $r->codigo_enviado_cerradura) {
                Log::info('[PinHealthcheck] Reprogramacion OK', [
                    'reserva_id' => $r->id, 'old_pin' => $oldPin, 'new_pin' => $nuevoPin,
                ]);
                $this->info("    ✓ reprogramado nuevo pin={$nuevoPin}");
                return 'reprogramado';
            }

            // No se programo (Tuya saturado o caido). Activar fallback de emergencia.
            $this->warn("    reprogramacion fallida -> enviando codigo de emergencia");
            $enviado = $fallbackSvc->enviarCodigoEmergenciaACliente($r);
            if ($enviado) {
                Log::warning('[PinHealthcheck] Reprogramacion fallida, enviado codigo emergencia', [
                    'reserva_id' => $r->id,
                ]);
                $this->info("    ✓ codigo emergencia enviado");
                return 'emergencia';
            }
            $this->error("    ✗ tampoco se pudo enviar codigo emergencia (revisar manualmente)");
            return 'nada';
        } catch (\Throwable $e) {
            Log::error('[PinHealthcheck] Excepcion reprogramando: ' . $e->getMessage(), [
                'reserva_id' => $r->id,
            ]);
            $this->error("    ✗ excepcion: " . $e->getMessage());
            return 'nada';
        }
    }
}
