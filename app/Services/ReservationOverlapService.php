<?php

namespace App\Services;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReservationOverlapService
{
    /**
     * Detecta solapes por apartamento en el rango indicado.
     */
    public function detect(Carbon $from, Carbon $to): Collection
    {
        Log::info('ReservationOverlapService: iniciando detección de solapes', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);

        $reservas = Reserva::query()
            ->activas()
            ->whereNotNull('apartamento_id')
            // Cualquier reserva cuyo rango [entrada, salida) intersecte con [from, to]
            ->whereDate('fecha_salida', '>', $from->toDateString())
            ->whereDate('fecha_entrada', '<', $to->toDateString())
            ->with('apartamento')
            ->orderBy('apartamento_id')
            ->orderBy('fecha_entrada')
            ->get();

        Log::info('ReservationOverlapService: reservas candidatas encontradas', [
            'total' => $reservas->count(),
        ]);

        $porApartamento = $reservas->groupBy('apartamento_id');

        $conflictos = $porApartamento->flatMap(function (Collection $reservasApartamento, $apartamentoId) {
            Log::info('ReservationOverlapService: procesando apartamento', [
                'apartamento_id' => $apartamentoId,
                'reservas' => $reservasApartamento->pluck('id'),
            ]);

            return $this->detectOverlapsForApartment($reservasApartamento);
        });

        Log::info('ReservationOverlapService: detección terminada', [
            'conflictos' => $conflictos->count(),
        ]);

        return $conflictos;
    }

    /**
     * Detecta grupos solapados dentro de un apartamento.
     */
    private function detectOverlapsForApartment(Collection $reservasApartamento): Collection
    {
        $reservasOrdenadas = $reservasApartamento->sortBy('fecha_entrada')->values();
        $conflictos = collect();

        if ($reservasOrdenadas->isEmpty()) {
            return $conflictos;
        }

        $primera = $reservasOrdenadas->first();
        if (!$primera->fecha_entrada || !$primera->fecha_salida) {
            return $conflictos;
        }

        $grupoActual = [$primera];
        $finGrupo = Carbon::parse($primera->fecha_salida);

        foreach ($reservasOrdenadas->skip(1) as $reserva) {
            if (!$reserva->fecha_entrada || !$reserva->fecha_salida) {
                continue;
            }

            $inicio = Carbon::parse($reserva->fecha_entrada);
            $fin = Carbon::parse($reserva->fecha_salida);

            // Solape si el inicio es anterior al fin vigente del grupo
            if ($inicio->lt($finGrupo)) {
                $grupoActual[] = $reserva;
                if ($fin->gt($finGrupo)) {
                    $finGrupo = $fin;
                }
                continue;
            }

            if (count($grupoActual) >= 2) {
                $conflictos->push($this->buildConflict($grupoActual));
            }

            $grupoActual = [$reserva];
            $finGrupo = $fin;
        }

        if (count($grupoActual) >= 2) {
            $conflictos->push($this->buildConflict($grupoActual));
        }

        return $conflictos;
    }

    private function buildConflict(array $reservasGrupo): array
    {
        $ids = collect($reservasGrupo)->pluck('id')->sort()->values()->all();
        $inicio = collect($reservasGrupo)->pluck('fecha_entrada')->min();
        $fin = collect($reservasGrupo)->pluck('fecha_salida')->max();
        $apartamento = $reservasGrupo[0]->apartamento;

        return [
            'apartamento_id' => $reservasGrupo[0]->apartamento_id,
            'apartamento_nombre' => $apartamento->nombre ?? ('Apartamento #' . $reservasGrupo[0]->apartamento_id),
            'reserva_ids' => $ids,
            'rango_inicio' => $inicio,
            'rango_fin' => $fin,
        ];
    }
}

