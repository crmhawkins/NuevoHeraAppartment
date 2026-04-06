<?php

namespace App\Console\Commands;

use App\Models\ReservaHold;
use App\Models\Apartamento;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiberarReservaHoldsCommand extends Command
{
    protected $signature = 'ari:liberar-holds-expirados';

    protected $description = 'Libera en Channex los bloqueos temporales de reservas web que hayan expirado';

    public function handle(): int
    {
        $now = Carbon::now();

        Log::info('[ReservaWeb] LiberarReservaHoldsCommand: inicio', [
            'now' => $now->toIso8601String(),
        ]);
        $this->info('Buscando holds de reserva expirados...');

        $holds = ReservaHold::where('estado', 'activo')
            ->where('expires_at', '<=', $now)
            ->get();

        if ($holds->isEmpty()) {
            Log::info('[ReservaWeb] LiberarReservaHoldsCommand: no hay holds expirados');
            $this->info('No hay holds expirados que liberar.');
            return self::SUCCESS;
        }

        Log::info('[ReservaWeb] LiberarReservaHoldsCommand: holds a procesar', [
            'count' => $holds->count(),
            'hold_ids' => $holds->pluck('id')->toArray(),
            'apartamento_ids' => $holds->pluck('apartamento_id')->unique()->toArray(),
        ]);
        $this->info('Encontrados ' . $holds->count() . ' hold(s) expirado(s).');
        $this->newLine();

        $apiUrl = env('CHANNEX_URL');
        $apiToken = env('CHANNEX_TOKEN');

        foreach ($holds as $hold) {
            Log::info('[ReservaWeb] LiberarReservaHoldsCommand: procesando hold', [
                'hold_id' => $hold->id,
                'apartamento_id' => $hold->apartamento_id,
                'fecha_entrada' => $hold->fecha_entrada,
                'fecha_salida' => $hold->fecha_salida,
                'expires_at' => $hold->expires_at?->toIso8601String(),
            ]);
            $this->line("Procesando hold #{$hold->id} (apartamento {$hold->apartamento_id}, {$hold->fecha_entrada} → {$hold->fecha_salida})...");
            $apartamento = Apartamento::find($hold->apartamento_id);
            $roomType = $hold->room_type_id ? RoomType::find($hold->room_type_id) : null;

            if (!$apartamento || !$apartamento->id_channex) {
                Log::warning('[ReservaWeb] LiberarReservaHoldsCommand: apartamento sin id_channex', [
                    'hold_id' => $hold->id,
                    'apartamento_id' => $hold->apartamento_id,
                ]);

                $hold->estado = 'expirado';
                $hold->save();
                continue;
            }

            if (!$roomType || !$roomType->id_channex) {
                $roomType = $apartamento->roomTypes()
                    ->whereNotNull('id_channex')
                    ->first();
            }

            if (!$roomType || !$roomType->id_channex) {
                Log::warning('[ReservaWeb] LiberarReservaHoldsCommand: room_type sin id_channex', [
                    'hold_id' => $hold->id,
                    'apartamento_id' => $hold->apartamento_id,
                    'room_type_id' => $hold->room_type_id,
                ]);

                $hold->estado = 'expirado';
                $hold->save();
                continue;
            }

            $startDate = Carbon::parse($hold->fecha_entrada);
            $endDate = Carbon::parse($hold->fecha_salida)->subDay();

            $values = [];
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $values[] = [
                    'property_id' => $apartamento->id_channex,
                    'room_type_id' => $roomType->id_channex,
                    'date' => $date->toDateString(),
                    'availability' => 1,
                ];
            }

            if (!$apiUrl || !$apiToken) {
                Log::error('[ReservaWeb] LiberarReservaHoldsCommand: falta CHANNEX_URL o CHANNEX_TOKEN');
                $hold->estado = 'expirado';
                $hold->save();
                continue;
            }

            $url = rtrim($apiUrl, '/') . '/availability';
            Log::info('[ReservaWeb] LiberarReservaHoldsCommand: enviando liberación a Channex', [
                'hold_id' => $hold->id,
                'url' => $url,
                'dias' => count($values),
            ]);
            $response = Http::withHeaders([
                'user-api-key' => $apiToken,
            ])->post($url, ['values' => $values]);

            $status = $response->status();
            $body = $response->body();

            $this->line('  Payload (availability=1): ' . json_encode(['values' => $values]));
            $this->line('  Channex response: HTTP ' . $status . ' | ' . $body);
            $this->newLine();

            if ($response->successful()) {
                Log::info('[ReservaWeb] LiberarReservaHoldsCommand: hold liberado en Channex', [
                    'hold_id' => $hold->id,
                    'apartamento_id' => $apartamento->id,
                    'apartamento_titulo' => $apartamento->titulo ?? null,
                    'room_type_id' => $roomType->id,
                    'fecha_entrada' => $hold->fecha_entrada,
                    'fecha_salida' => $hold->fecha_salida,
                    'channex_response' => $body,
                ]);

                $hold->estado = 'expirado';
                $hold->save();
                $this->info("  Hold #{$hold->id} marcado como expirado.");
            } else {
                Log::error('[ReservaWeb] LiberarReservaHoldsCommand: Channex rechazó liberación', [
                    'hold_id' => $hold->id,
                    'http_status' => $status,
                    'body' => $body,
                    'values' => $values,
                ]);
                $this->error("  Channex rechazó la liberación para hold #{$hold->id}.");
            }
        }

        Log::info('[ReservaWeb] LiberarReservaHoldsCommand: finalizado', [
            'holds_procesados' => $holds->count(),
        ]);
        $this->info('Proceso de liberación de holds expirados finalizado.');

        return self::SUCCESS;
    }
}

