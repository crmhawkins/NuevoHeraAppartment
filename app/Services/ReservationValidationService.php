<?php

namespace App\Services;

use App\Models\Reserva;
use Illuminate\Support\Facades\Log;

class ReservationValidationService
{
    /**
     * Check if there's an overlapping reservation for the same apartment.
     * Returns the conflicting reservation if found, null otherwise.
     */
    public static function findOverlap(int $apartamentoId, string $fechaEntrada, string $fechaSalida, ?int $excludeReservaId = null): ?Reserva
    {
        $query = Reserva::where('apartamento_id', $apartamentoId)
            ->where('estado_id', '!=', 4) // Exclude cancelled
            ->where(function ($q) use ($fechaEntrada, $fechaSalida) {
                $q->where('fecha_entrada', '<', $fechaSalida)
                  ->where('fecha_salida', '>', $fechaEntrada);
            });

        if ($excludeReservaId) {
            $query->where('id', '!=', $excludeReservaId);
        }

        return $query->first();
    }

    /**
     * Check overlap and log a warning if found. Returns true if there IS an overlap.
     */
    public static function hasOverlap(int $apartamentoId, string $fechaEntrada, string $fechaSalida, ?int $excludeReservaId = null, string $context = ''): bool
    {
        $conflict = self::findOverlap($apartamentoId, $fechaEntrada, $fechaSalida, $excludeReservaId);

        if ($conflict) {
            Log::warning("ReservationValidation: solapamiento detectado [{$context}]", [
                'apartamento_id' => $apartamentoId,
                'fecha_entrada' => $fechaEntrada,
                'fecha_salida' => $fechaSalida,
                'conflicto_reserva_id' => $conflict->id,
                'conflicto_codigo' => $conflict->codigo_reserva,
                'conflicto_entrada' => $conflict->fecha_entrada,
                'conflicto_salida' => $conflict->fecha_salida,
            ]);
            return true;
        }

        return false;
    }
}
