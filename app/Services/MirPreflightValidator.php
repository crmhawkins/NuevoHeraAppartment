<?php

namespace App\Services;

use App\Models\Reserva;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-18] Orquestador de validacion pre-envio MIR.
 *
 * Combina:
 *   - Nivel 1: MirDataValidator (deterministico, rapido, sin coste)
 *   - Nivel 3: MirIaValidator (semantico por IA, con fallback web search)
 *
 * Logica:
 *   1) Siempre corre Nivel 1. Si hay issues 'error', cortocircuita y devuelve.
 *   2) Si Nivel 1 pasa, corre Nivel 3. Sus issues se anaden a los warnings.
 *   3) La IA es fail-safe: si falla, devuelve []. El Nivel 1 es la linea dura.
 *
 * Decision de bloqueo ('hasBlockingErrors'):
 *   - Si hay AL MENOS 1 issue con severity='error', bloqueamos el envio MIR.
 *   - Los warnings NO bloquean (solo se loggean y notifican).
 */
class MirPreflightValidator
{
    /**
     * Valida una reserva a fondo (N1 + N3). Devuelve la lista combinada de
     * issues con el mismo formato que los sub-validadores.
     */
    public function validar(Reserva $reserva): array
    {
        $issues = [];

        // Nivel 1: deterministico
        try {
            $n1 = app(MirDataValidator::class)->validar($reserva);
            $issues = array_merge($issues, $n1);
        } catch (\Throwable $e) {
            Log::error('[MirPreflight] Nivel 1 fallo inesperado', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
            ]);
            // Nivel 1 no debe tirar: si lo hace, lo marcamos como error
            // para ser conservadores (mejor no enviar que enviar basura).
            $issues[] = [
                'severity'   => 'error',
                'entidad'    => 'reserva',
                'entidad_id' => $reserva->id,
                'campo'      => '_interno',
                'mensaje'    => 'Fallo interno en validacion deterministica',
                'sugerencia' => null,
            ];
        }

        // Si Nivel 1 ya bloquea, no desperdiciamos tokens en Nivel 3
        if ($this->hasBlockingErrors($issues)) {
            Log::info('[MirPreflight] Nivel 1 bloqueo el envio — Nivel 3 omitido', [
                'reserva_id' => $reserva->id,
                'errores' => count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'error')),
            ]);
            return $issues;
        }

        // Nivel 3: IA semantica (fail-safe, nunca propaga excepciones)
        try {
            $n3 = app(MirIaValidator::class)->validar($reserva);
            $issues = array_merge($issues, $n3);
        } catch (\Throwable $e) {
            // Nunca deberia llegar aqui (MirIaValidator::validar() ya envuelve
            // todo en try/catch), pero por si acaso.
            Log::warning('[MirPreflight] Nivel 3 IA fallo inesperado', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $issues;
    }

    /**
     * Indica si la lista de issues contiene al menos un error bloqueante.
     */
    public function hasBlockingErrors(array $issues): bool
    {
        foreach ($issues as $i) {
            if (($i['severity'] ?? '') === 'error') {
                return true;
            }
        }
        return false;
    }

    /**
     * Formatea los issues para un mensaje legible (WhatsApp, email, log).
     * Maximo 300 caracteres por defecto.
     */
    public function formatearParaAlerta(array $issues, int $maxChars = 300): string
    {
        if (empty($issues)) {
            return '';
        }

        $lineas = [];
        foreach ($issues as $i) {
            $sev = strtoupper($i['severity'] ?? '?');
            $ent = $i['entidad'] ?? '?';
            $eid = $i['entidad_id'] ?? '';
            $campo = $i['campo'] ?? '';
            $msg = $i['mensaje'] ?? '';
            $sug = $i['sugerencia'] ?? null;
            $linea = "[{$sev}] {$ent}" . ($eid ? " {$eid}" : '') . " {$campo}: {$msg}";
            if ($sug) {
                $linea .= " -> sugerencia: {$sug}";
            }
            $lineas[] = $linea;
        }

        $texto = implode('. ', $lineas);
        if (mb_strlen($texto) > $maxChars) {
            $texto = mb_substr($texto, 0, $maxChars - 3) . '...';
        }
        return $texto;
    }
}
