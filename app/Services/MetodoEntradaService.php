<?php

namespace App\Services;

use App\Models\Edificio;
use App\Models\Reserva;

class MetodoEntradaService
{
    public const METODO_FISICA = 'fisica';
    public const METODO_DIGITAL = 'digital';

    /**
     * Resolver método de entrada final para una reserva.
     * Solo usa el valor guardado en edificio.metodo_entrada.
     * Por defecto siempre física (comportamiento actual); solo cambia si en configuración se elige "digital".
     */
    public function resolverParaReserva(?Reserva $reserva): string
    {
        $apartamento = $reserva?->apartamento;
        if (!$apartamento) {
            return self::METODO_FISICA;
        }

        // Preferir relación Edificio (objeto), no el campo legacy legacy "edificio" (posiblemente int).
        $edificio = null;
        if (($apartamento->edificioName ?? null) instanceof Edificio) {
            $edificio = $apartamento->edificioName;
        } elseif (($apartamento->edificioRel ?? null) instanceof Edificio) {
            $edificio = $apartamento->edificioRel;
        } elseif (isset($apartamento->edificio_id) && !empty($apartamento->edificio_id)) {
            $edificio = Edificio::find($apartamento->edificio_id);
        } elseif (is_numeric($apartamento->edificio ?? null)) {
            // Compatibilidad con columna legacy "edificio" si todavía existe en la BD.
            $edificio = Edificio::find($apartamento->edificio);
        }

        return $this->resolverParaEdificio($edificio);
    }

    public function resolverParaEdificio(?Edificio $edificio): string
    {
        $metodo = strtolower(trim((string) ($edificio?->metodo_entrada ?? '')));
        if ($metodo === self::METODO_DIGITAL) {
            return self::METODO_DIGITAL;
        }

        return self::METODO_FISICA;
    }
}

