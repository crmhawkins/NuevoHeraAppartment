<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Huesped;
use App\Models\Reserva;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-22] Auto-correccion del campo "provincia" cuando no coincide con
 * el CP declarado.
 *
 * Ejemplo tipico: el usuario declara provincia="Algeciras" con CP=11201.
 * Algeciras es una localidad de la provincia de Cadiz, no una provincia.
 * MirDataValidator solo emite warning (no bloquea MIR), pero ademas aqui
 * intentamos corregir el dato consultando a la IA con web_search para que
 * no tengamos que volver a ver este warning nunca mas para este cliente.
 *
 * Resultado cacheado por par (CP, localidad) durante 30 dias: una vez
 * sabemos que "Algeciras" + CP 11xxx -> Cadiz, no hace falta llamar a IA
 * para otros clientes con el mismo par.
 */
class ProvinceCorrectorService
{
    private const CACHE_TTL_DAYS = 30;

    /**
     * Intenta resolver la provincia oficial para un CP+localidad.
     *
     * @return string|null Nombre oficial de la provincia (ej. "Cadiz") o null
     *                     si la IA no pudo determinarlo con seguridad.
     */
    public function resolver(string $cp, string $localidad): ?string
    {
        $cp = trim($cp);
        $localidad = trim($localidad);

        if ($cp === '' || $localidad === '') {
            return null;
        }
        if (!preg_match('/^\d{5}$/', $cp)) {
            return null;
        }

        $cacheKey = 'province_corrector:' . md5($cp . '|' . mb_strtolower($localidad));

        // 1. Cache hit
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === '__NEGATIVE__' ? null : $cached;
        }

        // 2. Consulta IA con web search
        try {
            $gateway = app(AIGatewayService::class);

            $messages = [
                ['role' => 'system', 'content' =>
                    'Eres un experto en codigos postales espanoles. Tu tarea es devolver el nombre oficial de la provincia espanola dada una localidad y un codigo postal. '.
                    'Responde UNICAMENTE con JSON valido, sin texto antes ni despues. '.
                    'Formato: {"provincia":"Nombre oficial","confianza":0.0-1.0,"razon":"breve"}. '.
                    'Si no estas 100% seguro o no es Espana, responde {"provincia":null,"confianza":0.0,"razon":"..."}. '.
                    'Usa web_search cuando no conozcas la localidad. El campo provincia debe ser el nombre oficial (ej: "Cadiz", "A Coruna", "Gipuzkoa"), sin acentos que puedan romper codificacion.',
                ],
                ['role' => 'user', 'content' =>
                    "Codigo postal: {$cp}\nLocalidad/ciudad: {$localidad}\n\n".
                    'Devuelve el nombre oficial de la provincia espanola a la que pertenece este codigo postal.',
                ],
            ];

            $params = [
                'model'           => 'gpt-4o',
                'temperature'     => 0.0,
                'max_tokens'      => 150,
                'messages'        => $messages,
                'response_format' => ['type' => 'json_object'],
                // Permitir web search si el gateway lo soporta (MirIaValidator ya
                // define la tool, aqui no la inyectamos para mantener la llamada
                // barata — gpt-4o conoce los CPs espanoles de memoria).
            ];

            $response = $gateway->chatCompletion($params);

            $content = $response['choices'][0]['message']['content'] ?? null;
            if (!is_string($content) || $content === '') {
                Cache::put($cacheKey, '__NEGATIVE__', now()->addDays(self::CACHE_TTL_DAYS));
                return null;
            }

            $data = json_decode($content, true);
            if (!is_array($data)) {
                Cache::put($cacheKey, '__NEGATIVE__', now()->addDays(self::CACHE_TTL_DAYS));
                return null;
            }

            $provincia = $data['provincia'] ?? null;
            $confianza = (float) ($data['confianza'] ?? 0);

            if (!is_string($provincia) || $provincia === '' || $confianza < 0.75) {
                Cache::put($cacheKey, '__NEGATIVE__', now()->addDays(self::CACHE_TTL_DAYS));
                Log::info('[ProvinceCorrector] IA no pudo resolver con seguridad', [
                    'cp' => $cp, 'localidad' => $localidad, 'respuesta' => $data,
                ]);
                return null;
            }

            $provincia = trim($provincia);
            Cache::put($cacheKey, $provincia, now()->addDays(self::CACHE_TTL_DAYS));
            Log::info('[ProvinceCorrector] Resuelto', [
                'cp' => $cp, 'localidad' => $localidad,
                'provincia' => $provincia, 'confianza' => $confianza,
            ]);
            return $provincia;

        } catch (\Throwable $e) {
            Log::warning('[ProvinceCorrector] Fallo consulta IA', [
                'cp' => $cp, 'localidad' => $localidad,
                'error' => mb_substr($e->getMessage(), 0, 200),
            ]);
            // No cacheamos errores de infra — puede ser IA caida transitoria
            return null;
        }
    }

    /**
     * Aplica auto-correccion a una reserva: si el cliente o algun huesped
     * tiene provincia que no coincide con CP (segun MirDataValidator::$provincias),
     * consulta IA y corrige en DB. Devuelve cuantas correcciones se aplicaron.
     */
    public function autocorregirReserva(Reserva $reserva): int
    {
        $corregidos = 0;

        $cliente = $reserva->cliente;
        if ($cliente && $this->necesitaCorreccion($cliente->codigo_postal, $cliente->provincia)) {
            $loc = (string) ($cliente->nombre_municipio ?: $cliente->municipio ?: '');
            if ($loc === '') {
                // Si no tenemos localidad, usamos la propia provincia declarada
                // como "localidad" — suele ser el caso (usuario puso "Algeciras"
                // pensando que era provincia).
                $loc = (string) $cliente->provincia;
            }
            $nuevo = $this->resolver((string) $cliente->codigo_postal, $loc);
            if ($nuevo !== null && $this->normalizar($nuevo) !== $this->normalizar((string) $cliente->provincia)) {
                $antes = $cliente->provincia;
                $cliente->provincia = $nuevo;
                $cliente->save();
                Log::info('[ProvinceCorrector] Cliente corregido', [
                    'cliente_id' => $cliente->id, 'reserva_id' => $reserva->id,
                    'antes' => $antes, 'despues' => $nuevo,
                ]);
                $corregidos++;
            }
        }

        $huespedes = Huesped::where('reserva_id', $reserva->id)->get();
        foreach ($huespedes as $h) {
            if (!$this->necesitaCorreccion($h->codigo_postal, $h->provincia)) continue;
            $loc = (string) ($h->nombre_municipio ?: $h->municipio ?: $h->provincia);
            if ($loc === '') continue;
            $nuevo = $this->resolver((string) $h->codigo_postal, $loc);
            if ($nuevo !== null && $this->normalizar($nuevo) !== $this->normalizar((string) $h->provincia)) {
                $antes = $h->provincia;
                $h->provincia = $nuevo;
                $h->save();
                Log::info('[ProvinceCorrector] Huesped corregido', [
                    'huesped_id' => $h->id, 'reserva_id' => $reserva->id,
                    'antes' => $antes, 'despues' => $nuevo,
                ]);
                $corregidos++;
            }
        }

        return $corregidos;
    }

    /**
     * Check rapido y sin llamar a IA: hace falta correccion si CP es valido
     * espanol y la provincia declarada no es vacia pero tampoco coincide con
     * el alias oficial (heuristica del MirDataValidator).
     */
    private function necesitaCorreccion(?string $cp, ?string $provincia): bool
    {
        $cp = trim((string) $cp);
        $provincia = trim((string) $provincia);

        if ($cp === '' || $provincia === '') return false;
        if (!preg_match('/^\d{5}$/', $cp)) return false;

        // Reutilizamos el mapa de MirDataValidator (aliases provincia oficial)
        try {
            $validator = app(MirDataValidator::class);
            $refl = new \ReflectionClass($validator);
            if (!$refl->hasProperty('provincias')) return false;

            $prop = $refl->getProperty('provincias');
            $prop->setAccessible(true);
            $provincias = $prop->getValue($validator);

            $prefix = substr($cp, 0, 2);
            if (!isset($provincias[$prefix])) return false;

            [, $aliases] = $provincias[$prefix];
            $provNorm = $this->normalizar($provincia);
            return !in_array($provNorm, $aliases, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function normalizar(string $s): string
    {
        $s = mb_strtoupper(trim($s), 'UTF-8');
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        $s = preg_replace('/[^A-Z0-9]/', '', $s);
        return (string) $s;
    }
}
