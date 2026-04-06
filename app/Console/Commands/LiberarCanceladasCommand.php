<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reserva;
use App\Models\Apartamento;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class LiberarCanceladasCommand extends Command
{
    protected $signature = 'ari:liberar-canceladas';
    protected $description = 'Libera disponibilidad en Channex para reservas canceladas';

    public function handle()
    {
        $this->info("Buscando reservas canceladas...");

        $canceladas = Reserva::where('estado_id', 4)
            ->whereDate('fecha_entrada', '>=', now()->toDateString())
            ->whereNotNull('apartamento_id')
            ->whereNotNull('room_type_id')
            ->get();

        foreach ($canceladas as $reserva) {
            $apartamento = $reserva->apartamento;
            $roomType = $reserva->roomType;

            if (!$apartamento || !$roomType || !$apartamento->id_channex || !$roomType->id_channex) {
                $this->warn("Reserva {$reserva->id} sin datos completos. Saltando...");
                continue;
            }

            $start = Carbon::parse($reserva->fecha_entrada);
            $end = Carbon::parse($reserva->fecha_salida)->subDay();
            $valores = [];

            for ($date = $start; $date->lte($end); $date->addDay()) {
                $valores[] = [
                    'property_id' => $apartamento->id_channex,
                    'room_type_id' => $roomType->id_channex,
                    'date' => $date->toDateString(),
                    'availability' => 1,
                ];
            }

            $respuesta = Http::withHeaders([
                'user-api-key' => env('CHANNEX_TOKEN'),
            ])->post(env('CHANNEX_URL') . '/availability', [
                'values' => $valores
            ]);

            if ($respuesta->successful()) {
                $this->info("Reserva {$reserva->id} liberada correctamente.");
            } else {
                $this->error("Error liberando reserva {$reserva->id}: " . $respuesta->body());
            }
        }

        $this->info('Proceso de liberación de reservas canceladas finalizado.');
    }
}

