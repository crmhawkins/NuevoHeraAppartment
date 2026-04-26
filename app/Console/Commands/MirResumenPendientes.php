<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Services\WhatsappNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-26] Resumen diario de reservas bloqueadas para MIR.
 *
 * Antes el sistema enviaba un mensaje WhatsApp por CADA reserva que fallaba la
 * validacion pre-envio MIR. Con varias reservas al dia, el admin recibia 5-10
 * mensajes practicamente identicos cada vez que el cron pasaba.
 *
 * Ahora el envio individual esta desactivado (ver MIRService::1178). Este
 * comando se ejecuta una vez al dia y manda UN solo mensaje WhatsApp con la
 * lista de TODAS las reservas en estado de error de validacion, agrupadas y
 * con un resumen claro de los campos que hay que corregir.
 *
 * Uso: php artisan mir:resumen-pendientes
 *      --dry-run    no envia, solo muestra lo que mandaria
 *      --max=20     numero maximo de reservas a listar en el mensaje
 */
class MirResumenPendientes extends Command
{
    protected $signature = 'mir:resumen-pendientes
        {--dry-run : solo muestra el mensaje sin enviarlo}
        {--max=20 : numero maximo de reservas a listar}';

    protected $description = 'Envia un unico mensaje WhatsApp resumen con todas las reservas bloqueadas para MIR (sustituye al spam de un mensaje por reserva)';

    public function handle(WhatsappNotificationService $whatsapp): int
    {
        $max = (int) $this->option('max') ?: 20;
        $dryRun = (bool) $this->option('dry-run');

        // Reservas bloqueadas: mir_estado en error_validacion o error_envio,
        // y con fecha de entrada >= hoy (las pasadas no merecen el aviso ya).
        $reservas = Reserva::with(['cliente', 'apartamento'])
            ->whereIn('mir_estado', ['error_validacion', 'error_envio', 'rechazado'])
            ->whereNotIn('estado_id', [4, 9]) // ni canceladas
            ->where('fecha_entrada', '>=', Carbon::today()->subDays(2)) // tolerancia
            ->orderBy('fecha_entrada', 'asc')
            ->get();

        if ($reservas->isEmpty()) {
            $this->info('No hay reservas pendientes de MIR. No se envia nada.');
            return self::SUCCESS;
        }

        $total = $reservas->count();
        $mostradas = $reservas->take($max);
        $restantes = $total - $mostradas->count();

        // Construir el bloque de cada reserva
        $lineas = [];
        foreach ($mostradas as $r) {
            $cli = $r->cliente;
            $nombre = $cli ? trim(($cli->nombre ?? '') . ' ' . ($cli->apellido1 ?? '')) : 'sin cliente';
            if ($nombre === '') $nombre = 'sin nombre';

            $entrada = $r->fecha_entrada
                ? Carbon::parse($r->fecha_entrada)->format('d/m')
                : '?';
            $apto = $r->apartamento ? $r->apartamento->titulo : '?';

            $motivo = $this->extraerMotivoCorto($r);

            $lineas[] = "• #{$r->id} {$entrada} {$apto} — {$nombre}: {$motivo}";
        }

        $url = rtrim(config('app.url'), '/') . '/admin/reservas-revision-manual';

        $texto = "📋 *Reservas bloqueadas para MIR* ({$total})\n\n"
            . implode("\n", $lineas);

        if ($restantes > 0) {
            $texto .= "\n\n…y {$restantes} mas.";
        }

        $texto .= "\n\nRevisalas en:\n{$url}\n\n"
            . "Tras corregir, marca 'Revalidar' y se envia a MIR automaticamente.";

        if ($dryRun) {
            $this->line($texto);
            $this->info("\n[dry-run] No se ha enviado.");
            return self::SUCCESS;
        }

        try {
            $whatsapp->sendToConfiguredRecipients($texto);
            Log::info('[MirResumenPendientes] Resumen diario enviado', [
                'total_reservas' => $total,
                'mostradas' => $mostradas->count(),
            ]);
            $this->info("Resumen enviado ({$total} reservas).");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('[MirResumenPendientes] Error enviando resumen: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Extrae un motivo corto y legible del mir_respuesta JSON, para que el
     * admin sepa de un vistazo que falla en cada reserva.
     */
    private function extraerMotivoCorto(Reserva $r): string
    {
        $resp = $r->mir_respuesta ?? '';
        if (!$resp) return 'sin detalle';

        $data = json_decode($resp, true);
        if (!is_array($data)) return 'sin detalle';

        // Caso validacion fallida: deduplicar por campo (un mismo error puede
        // venir varias veces, una por cliente y otra por huesped).
        if (!empty($data['issues']) && is_array($data['issues'])) {
            $camposUnicos = [];
            foreach ($data['issues'] as $i) {
                $c = $i['campo'] ?? null;
                if ($c && !in_array($c, $camposUnicos, true)) {
                    $camposUnicos[] = $c;
                }
            }
            if (!empty($camposUnicos)) {
                $primeros = array_slice($camposUnicos, 0, 2);
                $extra = count($camposUnicos) > count($primeros)
                    ? ' (+ ' . (count($camposUnicos) - count($primeros)) . ')'
                    : '';
                return 'falta/incorrecto ' . implode(', ', $primeros) . $extra;
            }
        }

        // Caso rechazo del MIR
        if (!empty($data['mensaje'])) {
            return mb_substr((string) $data['mensaje'], 0, 80);
        }

        if (!empty($data['error'])) {
            return (string) $data['error'];
        }

        return 'sin detalle';
    }
}
