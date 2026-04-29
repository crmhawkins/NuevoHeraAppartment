<?php

namespace App\Services;

use App\Models\Apartamento;
use App\Models\RevenueRecomendacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-29] Push de precios desde el módulo de Revenue Management
 * a Channex en bulk.
 *
 * Channex acepta hasta 1000 cambios por POST, rate limit 10 ARI/min/property.
 * Endpoint: POST /api/v1/restrictions
 *
 * Body:
 * {
 *   "values": [
 *     {"property_id": "<uuid>", "rate_plan_id": "<uuid>",
 *      "date_from": "YYYY-MM-DD", "date_to": "YYYY-MM-DD",
 *      "rate": "72.00"}, ...
 *   ]
 * }
 *
 * IMPORTANTE: este service NO se ejecuta automáticamente. Solo cuando
 * el admin hace click en "Aplicar precios" desde la UI. Cero auto-apply
 * sin supervisión (regla CLAUDE.md sección 0).
 */
class ChannexRevenueService
{
    /**
     * Aplica una lista de cambios de precio. Cada cambio es:
     *   ['apartamento_id' => int, 'fecha' => 'YYYY-MM-DD', 'precio' => float]
     *
     * @return array ['ok' => int, 'errores' => array]
     */
    public function aplicarCambios(array $cambios, ?int $userId = null): array
    {
        $stats = ['ok' => 0, 'errores' => [], 'total' => count($cambios)];

        // Validar que cada apartamento tiene id_channex + revenue_rate_plan_id
        $apartamentoIds = collect($cambios)->pluck('apartamento_id')->unique()->values();
        $apartamentos = Apartamento::whereIn('id', $apartamentoIds)->get()->keyBy('id');

        $values = [];
        foreach ($cambios as $cambio) {
            $apt = $apartamentos[$cambio['apartamento_id']] ?? null;
            if (!$apt) {
                $stats['errores'][] = "Apartamento {$cambio['apartamento_id']} no existe";
                continue;
            }
            if (!$apt->id_channex) {
                $stats['errores'][] = "Apartamento {$apt->nombre} sin id_channex";
                continue;
            }
            if (!$apt->revenue_rate_plan_id) {
                $stats['errores'][] = "Apartamento {$apt->nombre} sin revenue_rate_plan_id (configurar en /admin/apartamentos/{$apt->id}/revenue)";
                continue;
            }
            // Aplicar guardrails de min/max si están configurados
            $precio = (float) $cambio['precio'];
            if ($apt->revenue_min_precio && $precio < $apt->revenue_min_precio) {
                $stats['errores'][] = "Apartamento {$apt->nombre} fecha {$cambio['fecha']}: precio {$precio}€ < mínimo {$apt->revenue_min_precio}€. Saltado.";
                continue;
            }
            if ($apt->revenue_max_precio && $precio > $apt->revenue_max_precio) {
                $stats['errores'][] = "Apartamento {$apt->nombre} fecha {$cambio['fecha']}: precio {$precio}€ > máximo {$apt->revenue_max_precio}€. Saltado.";
                continue;
            }
            $values[] = [
                '_meta' => $cambio,  // se quita antes de mandar a Channex
                'property_id' => $apt->id_channex,
                'rate_plan_id' => $apt->revenue_rate_plan_id,
                'date' => $cambio['fecha'],
                'rate' => number_format($precio, 2, '.', ''),
            ];
        }

        if (empty($values)) {
            return $stats;
        }

        // Enviamos en batches respetando rate limit. Por seguridad, max 100/req.
        foreach (array_chunk($values, 100) as $batch) {
            $bodyValues = array_map(function ($v) {
                unset($v['_meta']);
                return $v;
            }, $batch);

            try {
                $response = Http::withHeaders([
                    'user-api-key' => config('services.channex.api_token', env('CHANNEX_TOKEN')),
                    'Content-Type' => 'application/json',
                ])->timeout(60)
                  ->post(rtrim(config('services.channex.api_url'), '/') . '/restrictions', [
                      'values' => $bodyValues,
                  ]);

                if ($response->successful()) {
                    // Marcar como aplicados en BD
                    foreach ($batch as $v) {
                        $meta = $v['_meta'];
                        RevenueRecomendacion::where('apartamento_id', $meta['apartamento_id'])
                            ->where('fecha', $meta['fecha'])
                            ->update([
                                'precio_aplicado' => $meta['precio'],
                                'aplicado_at' => now(),
                                'aplicado_por_user_id' => $userId,
                            ]);
                        $stats['ok']++;
                    }
                    Log::info('[Revenue] batch Channex OK', [
                        'count' => count($batch),
                        'response_preview' => mb_substr($response->body(), 0, 200),
                    ]);
                } else {
                    $stats['errores'][] = "Channex HTTP {$response->status()}: " . mb_substr($response->body(), 0, 200);
                    Log::error('[Revenue] batch Channex FAIL', [
                        'status' => $response->status(),
                        'body' => mb_substr($response->body(), 0, 500),
                    ]);
                }

                // Respetar rate limit: 10 req/min/property → pausa entre batches
                usleep(500_000); // 0.5s
            } catch (\Throwable $e) {
                $stats['errores'][] = "Excepción Channex: " . $e->getMessage();
                Log::error('[Revenue] excepción aplicando batch', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Modo simulación (dry-run): valida pero no envía a Channex.
     */
    public function simularCambios(array $cambios): array
    {
        $stats = ['ok' => 0, 'errores' => [], 'total' => count($cambios), 'dry_run' => true];
        $apartamentos = Apartamento::whereIn('id', collect($cambios)->pluck('apartamento_id'))->get()->keyBy('id');

        foreach ($cambios as $c) {
            $apt = $apartamentos[$c['apartamento_id']] ?? null;
            if (!$apt) { $stats['errores'][] = "Apto {$c['apartamento_id']} no existe"; continue; }
            if (!$apt->id_channex) { $stats['errores'][] = "Apto {$apt->nombre} sin id_channex"; continue; }
            if (!$apt->revenue_rate_plan_id) { $stats['errores'][] = "Apto {$apt->nombre} sin rate_plan"; continue; }
            $precio = (float) $c['precio'];
            if ($apt->revenue_min_precio && $precio < $apt->revenue_min_precio) {
                $stats['errores'][] = "Apto {$apt->nombre} {$c['fecha']}: {$precio}€ < min"; continue;
            }
            if ($apt->revenue_max_precio && $precio > $apt->revenue_max_precio) {
                $stats['errores'][] = "Apto {$apt->nombre} {$c['fecha']}: {$precio}€ > max"; continue;
            }
            $stats['ok']++;
        }
        return $stats;
    }
}
