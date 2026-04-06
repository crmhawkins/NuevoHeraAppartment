<?php

namespace App\Console\Commands;

use App\Models\ReservationConflictAlert;
use App\Services\ReservationOverlapService;
use App\Services\WhatsappNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckOverlappingReservations extends Command
{
    protected $signature = 'reservas:check-overlaps';

    protected $description = 'Detecta reservas solapadas por apartamento y notifica por WhatsApp.';

    public function __construct(
        private readonly ReservationOverlapService $overlapService,
        private readonly WhatsappNotificationService $whatsappNotificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ahora = now();
        $fin = $ahora->copy()->addMonth();

        Log::info('CheckOverlappingReservations: inicio de comando', [
            'from' => $ahora->toDateTimeString(),
            'to' => $fin->toDateTimeString(),
        ]);

        $conflictos = $this->overlapService->detect($ahora, $fin);

        $this->info('Rango analizado: ' . $ahora->toDateString() . ' -> ' . $fin->toDateString());
        $this->info('Conflictos detectados: ' . $conflictos->count());

        Log::info('CheckOverlappingReservations: conflictos detectados', [
            'total_conflictos' => $conflictos->count(),
            'detalles' => $conflictos->all(),
        ]);

        $keysDetectados = [];

        foreach ($conflictos as $conflicto) {
            $key = $this->buildConflictKey($conflicto);
            $keysDetectados[] = $key;

            $alerta = ReservationConflictAlert::firstWhere('conflict_key', $key);
            $alerta ??= new ReservationConflictAlert(['conflict_key' => $key]);

            $alerta->apartamento_id = $conflicto['apartamento_id'];
            $alerta->reserva_ids = $conflicto['reserva_ids'];

            $debeEnviar = $alerta->isDueForSend($ahora);
            $alerta->resolved_at = null;

            $alerta->save();

            if ($debeEnviar) {
                Log::info('CheckOverlappingReservations: envío pendiente para conflicto', [
                    'conflict_key' => $key,
                    'apartamento_id' => $alerta->apartamento_id,
                    'reserva_ids' => $alerta->reserva_ids,
                ]);

                $this->enviarNotificacion($conflicto, $ahora);
                $alerta->last_sent_at = $ahora;
                $alerta->save();
            } else {
                Log::info('CheckOverlappingReservations: no se envía por ventana de 6h', [
                    'conflict_key' => $key,
                    'last_sent_at' => optional($alerta->last_sent_at)->toDateTimeString(),
                ]);
            }
        }

        // Marcar resueltos los que ya no aparecieron
        $query = ReservationConflictAlert::whereNull('resolved_at');
        if (!empty($keysDetectados)) {
            $query->whereNotIn('conflict_key', $keysDetectados);
        }
        $query->update(['resolved_at' => $ahora]);

        $this->info('Comprobación de solapes finalizada');

        Log::info('CheckOverlappingReservations: fin de comando');

        return Command::SUCCESS;
    }

    private function buildConflictKey(array $conflicto): string
    {
        $idsOrdenados = Arr::sort($conflicto['reserva_ids']);
        return Str::of('apt:')
            ->append($conflicto['apartamento_id'])
            ->append(':ids:')
            ->append(implode('-', $idsOrdenados));
    }

    private function enviarNotificacion(array $conflicto, Carbon $ahora): void
    {
        $texto = $this->construirMensaje($conflicto, $ahora);
        $variables = $this->construirVariablesTemplate($conflicto);

        try {
            $this->whatsappNotificationService->sendToConfiguredRecipients($texto, $variables);
            Log::info("CheckOverlappingReservations: notificación enviada para {$conflicto['apartamento_id']}");
        } catch (\Throwable $e) {
            Log::error('CheckOverlappingReservations: error enviando notificación', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function construirMensaje(array $conflicto, Carbon $ahora): string
    {
        $rangos = collect($conflicto['reserva_ids'])
            ->map(fn ($id) => "#{$id}")
            ->implode(', ');

        return "⚠️ Doble reserva detectada\n"
            . "🏠 Apartamento: {$conflicto['apartamento_nombre']}\n"
            . "🗓️ Rango: {$conflicto['rango_inicio']} - {$conflicto['rango_fin']}\n"
            . "🔗 Reservas: {$rangos}\n"
            . "⏱️ Detectado: {$ahora->format('d/m/Y H:i')}\n"
            . "Por favor, revisa y resuelve en el ERP.";
    }

    /**
     * Variables para el template alerta_doble_reserva:
     * 1) Apartamento
     * 2) Fecha inicio
     * 3) Fecha fin
     * 4) IDs de reservas
     */
    private function construirVariablesTemplate(array $conflicto): array
    {
        $ids = collect($conflicto['reserva_ids'])
            ->map(fn ($id) => "#{$id}")
            ->implode(', ');

        return [
            $conflicto['apartamento_nombre'],
            Carbon::parse($conflicto['rango_inicio'])->format('d/m/Y'),
            Carbon::parse($conflicto['rango_fin'])->format('d/m/Y'),
            $ids,
        ];
    }
}

