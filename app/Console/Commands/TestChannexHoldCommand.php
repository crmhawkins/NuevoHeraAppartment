<?php

namespace App\Console\Commands;

use App\Models\Apartamento;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestChannexHoldCommand extends Command
{
    protected $signature = 'ari:test-channex-hold
                            {--apartamento= : ID o título parcial del apartamento (por defecto: Ático Hawkins Suite)}
                            {--entrada=2026-05-21 : Fecha entrada Y-m-d}
                            {--salida=2026-05-23 : Fecha salida Y-m-d}';

    protected $description = 'Prueba la petición de disponibilidad a Channex (misma que el hold de reserva web) y muestra la respuesta.';

    public function handle(): int
    {
        $entrada = $this->option('entrada');
        $salida = $this->option('salida');
        $apartamentoBuscar = $this->option('apartamento') ?? 'Suite Atico';

        $this->info("Buscando apartamento: {$apartamentoBuscar}");
        $this->info("Rango: {$entrada} → {$salida}");
        $this->newLine();

        $apartamento = $this->findApartamento($apartamentoBuscar);
        if (!$apartamento) {
            $this->error('No se encontró ningún apartamento con ese criterio.');
            return self::FAILURE;
        }

        $this->info("Apartamento: [{$apartamento->id}] {$apartamento->titulo}");
        $this->info("id_channex: " . ($apartamento->id_channex ?? 'NULL'));
        $this->newLine();

        $roomType = $apartamento->roomTypes()->whereNotNull('id_channex')->first();
        if (!$roomType) {
            $this->error('El apartamento no tiene ningún RoomType con id_channex.');
            return self::FAILURE;
        }

        $this->info("RoomType: [{$roomType->id}] id_channex: {$roomType->id_channex}");
        $this->newLine();

        $fechaEntrada = Carbon::parse($entrada);
        $fechaSalida = Carbon::parse($salida);
        $startDate = $fechaEntrada->copy();
        $endDate = $fechaSalida->copy()->subDay();

        $values = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $values[] = [
                'property_id' => $apartamento->id_channex,
                'room_type_id' => $roomType->id_channex,
                'date' => $date->toDateString(),
                'availability' => 0,
            ];
        }

        $payload = ['values' => $values];
        $this->info('--- Payload enviado a Channex (igual que en reserva) ---');
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->newLine();

        $apiUrl = rtrim(env('CHANNEX_URL'), '/');
        $apiToken = env('CHANNEX_TOKEN');

        if (!$apiUrl || !$apiToken) {
            $this->error('Faltan CHANNEX_URL o CHANNEX_TOKEN en .env');
            return self::FAILURE;
        }

        $url = $apiUrl . '/availability';
        $this->info("POST {$url}");
        $this->newLine();

        $response = Http::withHeaders([
            'user-api-key' => $apiToken,
        ])->post($url, $payload);

        $status = $response->status();
        $body = $response->body();

        $this->info('--- Respuesta de Channex ---');
        $this->info("HTTP Status: {$status}");
        $this->line('Body (raw):');
        $this->line($body);

        if ($response->successful()) {
            $this->newLine();
            $this->info('OK: La petición es la misma que la reserva; Channex respondió correctamente.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('La petición fue rechazada por Channex. Revisa el body arriba.');
        return self::FAILURE;
    }

    private function findApartamento(string $criterio): ?Apartamento
    {
        if (is_numeric($criterio)) {
            return Apartamento::whereNotNull('id_channex')->find($criterio);
        }

        return Apartamento::whereNotNull('id_channex')
            ->where(function ($q) use ($criterio) {
                $q->where('titulo', 'LIKE', '%' . $criterio . '%');
            })
            ->first();
    }
}
