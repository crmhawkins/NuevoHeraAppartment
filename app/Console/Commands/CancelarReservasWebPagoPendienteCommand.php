<?php

namespace App\Console\Commands;

use App\Models\Pago;
use App\Models\Reserva;
use App\Models\RoomType;
use App\Services\AlertaEquipoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CancelarReservasWebPagoPendienteCommand extends Command
{
    protected $signature = 'ari:cancelar-reservas-web-pago-pendiente';

    protected $description = 'Cancela reservas web cuyo pago sigue pendiente tras el tiempo configurado (por defecto 10 minutos)';

    public function handle(): int
    {
        $minutos = (int) config('app.web_reservas_hold_minutes', 10);
        $limite = now()->subMinutes($minutos);

        $pagos = Pago::where('estado', 'pendiente')
            ->where('metodo_pago', 'stripe')
            ->where('created_at', '<', $limite)
            ->whereHas('reserva', function ($q) {
                $q->where('origen', 'Web')->where('estado_id', 2); // Pendiente
            })
            ->with(['reserva.apartamento.roomTypes'])
            ->get();

        if ($pagos->isEmpty()) {
            Log::info('[ReservaWeb] CancelarReservasWebPagoPendiente: no hay pagos pendientes a cancelar', [
                'limite' => $limite->toIso8601String(),
                'minutos' => $minutos,
            ]);
            $this->info("No hay reservas web con pago pendiente desde hace más de {$minutos} minutos.");
            return self::SUCCESS;
        }

        Log::info('[ReservaWeb] CancelarReservasWebPagoPendiente: reservas a cancelar', [
            'count' => $pagos->count(),
            'pago_ids' => $pagos->pluck('id')->toArray(),
            'reserva_ids' => $pagos->pluck('reserva_id')->toArray(),
        ]);
        $this->info('Encontradas ' . $pagos->count() . ' reserva(s) web con pago pendiente. Cancelando...');
        $this->newLine();

        $apiUrl = env('CHANNEX_URL');
        $apiToken = env('CHANNEX_TOKEN');

        foreach ($pagos as $pago) {
            $reserva = $pago->reserva;
            if (!$reserva) {
                continue;
            }

            $this->line("  Pago #{$pago->id} / Reserva #{$reserva->id} ({$reserva->codigo_reserva})");

            // Cancelar reserva (estado_id 4 = Cancelado)
            $reserva->update(['estado_id' => 4]);
            $pago->update(['estado' => 'cancelado']);

            Log::info('[ReservaWeb] CancelarReservasWebPagoPendiente: reserva y pago cancelados', [
                'pago_id' => $pago->id,
                'reserva_id' => $reserva->id,
                'codigo_reserva' => $reserva->codigo_reserva,
            ]);

            // Alerta al equipo: pago abandonado
            try {
                $reserva->load(['cliente', 'apartamento']);
                AlertaEquipoService::pagoAbandonado($reserva);
            } catch (\Exception $e) {
                Log::warning('[ReservaWeb] Error enviando alerta pago abandonado', ['error' => $e->getMessage()]);
            }

            // Enviar WhatsApp recordando que dejó el pago sin completar
            $cliente = $reserva->cliente;
            if ($cliente) {
                $telefono = $cliente->telefono ?? $cliente->telefono_movil ?? null;
                if (!empty($telefono)) {
                    try {
                        $token = env('TOKEN_WHATSAPP');
                        $phoneId = env('WHATSAPP_PHONE_ID');
                        if ($token && $phoneId) {
                            $nombre = $cliente->nombre ?? 'Huésped';
                            $mensaje = "Hola {$nombre}, hemos visto que iniciaste una reserva en Apartamentos Hawkins pero no completaste el pago. "
                                     . "Si tuviste algún problema, puedes volver a intentarlo en https://apartamentosalgeciras.com/web "
                                     . "o contactarnos si necesitas ayuda. ¡Te esperamos!";

                            Http::withToken($token)->post(
                                "https://graph.facebook.com/v20.0/{$phoneId}/messages",
                                [
                                    'messaging_product' => 'whatsapp',
                                    'to' => preg_replace('/[^0-9]/', '', $telefono),
                                    'type' => 'text',
                                    'text' => ['body' => $mensaje],
                                ]
                            );

                            Log::info('[ReservaWeb] WhatsApp de pago abandonado enviado', [
                                'reserva_id' => $reserva->id,
                                'telefono' => substr($telefono, 0, 6) . '***',
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('[ReservaWeb] Error enviando WhatsApp de pago abandonado', [
                            'reserva_id' => $reserva->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Liberar disponibilidad en Channex (solo reservas web sin id_channex)
            if (empty($reserva->id_channex)) {
                $apartamento = $reserva->apartamento;
                $roomType = null;
                if ($reserva->room_type_id) {
                    $roomType = RoomType::where('id', $reserva->room_type_id)
                        ->where('property_id', $reserva->apartamento_id)
                        ->first();
                }
                if (!$roomType && $apartamento) {
                    $roomType = $apartamento->roomTypes()->whereNotNull('id_channex')->first();
                }

                if ($apartamento && $apartamento->id_channex && $roomType && $roomType->id_channex) {
                    $startDate = Carbon::parse($reserva->fecha_entrada);
                    $endDate = Carbon::parse($reserva->fecha_salida)->subDay();
                    $values = [];
                    for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                        $values[] = [
                            'property_id' => $apartamento->id_channex,
                            'room_type_id' => $roomType->id_channex,
                            'date' => $date->toDateString(),
                            'availability' => 1,
                        ];
                    }

                    if ($apiUrl && $apiToken) {
                        $url = rtrim($apiUrl, '/') . '/availability';
                        $response = Http::withHeaders([
                            'user-api-key' => $apiToken,
                        ])->post($url, ['values' => $values]);

                        if ($response->successful()) {
                            Log::info('[ReservaWeb] CancelarReservasWebPagoPendiente: Channex liberado', [
                                'reserva_id' => $reserva->id,
                                'apartamento_id' => $apartamento->id,
                            ]);
                            $this->info("    Channex liberado para reserva #{$reserva->id}");
                        } else {
                            Log::warning('[ReservaWeb] CancelarReservasWebPagoPendiente: Channex no liberado', [
                                'reserva_id' => $reserva->id,
                                'status' => $response->status(),
                                'body' => $response->body(),
                            ]);
                            $this->warn("    Channex no pudo liberar (HTTP {$response->status()})");
                        }
                    }
                }
            }
        }

        $this->info('Proceso finalizado.');
        return self::SUCCESS;
    }
}
