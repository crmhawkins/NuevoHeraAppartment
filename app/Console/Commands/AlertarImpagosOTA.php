<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-21] Comando semanal que detecta reservas de OTAs (Booking,
 * Airbnb, Agoda, ...) con check-out hace mas de 10 dias sin ingreso
 * bancario vinculado. Las considera posibles impagos y crea una
 * notificacion interna en el CRM para el admin.
 *
 * Solo aplica a reservas con fecha_salida >= 2026-04-01 (empezamos
 * el seguimiento desde este mes; lo anterior se considera historico
 * ya cobrado).
 *
 * Se ejecuta desde el scheduler (Console\Kernel) una vez por semana.
 * No manda WhatsApp ni Telegram, solo notificacion intranet.
 */
class AlertarImpagosOTA extends Command
{
    protected $signature = 'facturacion:alertar-impagos-ota';
    protected $description = 'Detecta reservas OTA con check-out >10 dias sin cobro y crea notificacion en el CRM';

    /** Umbral de dias tras check-out para considerar impago */
    private const UMBRAL_DIAS = 10;

    /** Fecha a partir de la cual empezamos a monitorizar impagos */
    private const FECHA_INICIO = '2026-04-01';

    public function handle(): int
    {
        $this->info('[Impagos OTA] Buscando reservas OTA sin cobrar...');

        $hoy = Carbon::today();
        $fechaLimite = $hoy->copy()->subDays(self::UMBRAL_DIAS);

        $impagas = Reserva::where('estado_id', '!=', 4) // no canceladas
            ->whereRaw("LOWER(origen) NOT IN ('web','directo','')")
            ->whereNotNull('origen')
            ->whereDate('fecha_salida', '<', $fechaLimite)
            ->whereDate('fecha_salida', '>=', self::FECHA_INICIO)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('pagos')
                  ->whereColumn('pagos.reserva_id', 'reservas.id')
                  ->where('pagos.estado', 'completado');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('ingresos')
                  ->whereColumn('ingresos.reserva_id', 'reservas.id');
            })
            ->with(['apartamento', 'cliente'])
            ->orderBy('fecha_salida')
            ->get();

        $total = $impagas->count();

        if ($total === 0) {
            $this->info('[Impagos OTA] Sin impagos detectados. Todo cobrado.');
            Log::info('[Impagos OTA] Sin impagos en esta pasada.');
            return 0;
        }

        $this->warn("[Impagos OTA] Detectadas {$total} reservas con posible impago:");

        // Calcular total adeudado + agrupar por canal
        $totalAdeudado = 0.0;
        $porCanal = [];
        $lineas = [];
        foreach ($impagas as $r) {
            $precio = (float) ($r->precio ?? 0);
            $totalAdeudado += $precio;
            $canal = $r->origen ?: 'sin_canal';
            $porCanal[$canal] = ($porCanal[$canal] ?? 0) + 1;

            $dias = Carbon::parse($r->fecha_salida)->diffInDays($hoy);
            $lineas[] = "#{$r->id} | {$canal} | " .
                Carbon::parse($r->fecha_salida)->format('d/m/Y') .
                " (hace {$dias}d) | " . number_format($precio, 2) . "€";

            $this->line('  ' . end($lineas));
        }

        // Construir mensaje + data
        $resumenCanales = collect($porCanal)
            ->map(fn($n, $c) => "{$c}: {$n}")
            ->implode(', ');

        $title = "Impagos OTA detectados ({$total})";
        $message = "{$total} reservas con check-out hace mas de " . self::UMBRAL_DIAS
            . " dias sin ingreso bancario. Total adeudado aproximado: "
            . number_format($totalAdeudado, 2) . "€. Por canal: {$resumenCanales}.";

        $data = [
            'total_impagas' => $total,
            'total_adeudado' => round($totalAdeudado, 2),
            'por_canal' => $porCanal,
            'detalle' => $lineas,
            'umbral_dias' => self::UMBRAL_DIAS,
            'fecha_inicio' => self::FECHA_INICIO,
            'generado_en' => $hoy->toDateString(),
        ];

        $actionUrl = route('reservas.index');

        try {
            Notification::createForAdmins(
                Notification::TYPE_FACTURACION,
                $title,
                $message,
                $data,
                Notification::PRIORITY_HIGH,
                Notification::CATEGORY_WARNING,
                $actionUrl
            );
            Log::info('[Impagos OTA] Notificacion intranet creada', [
                'total' => $total,
                'adeudado' => $totalAdeudado,
            ]);
            $this->info("[Impagos OTA] Notificacion creada en el CRM (centro de notificaciones). Total adeudado: " . number_format($totalAdeudado, 2) . "€");
        } catch (\Throwable $e) {
            Log::error('[Impagos OTA] Error creando notificacion', ['error' => $e->getMessage()]);
            $this->error('[Impagos OTA] Error creando notificacion: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
