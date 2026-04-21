<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Huesped;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MirIaValidator (Nivel 3 - IA)
 *
 * Validador semantico por IA, complementario al MirDataValidator
 * determinista. Detecta incoherencias que un validador basado en reglas
 * no puede cazar (p.ej. CP dentro de un rango provincial valido pero que
 * no existe realmente en el callejero, o combinacion CP+direccion+provincia
 * imposibles fisicamente).
 *
 * FILOSOFIA: fail-safe. Si algo peta (API caida, timeout, JSON roto,
 * gateway sin tools), NUNCA tiramos excepcion: devolvemos [] para no
 * bloquear envios MIR por un fallo de IA. La ultima linea de defensa
 * sigue siendo el Nivel 1 (MirDataValidator determinista).
 *
 * USO:
 *   $issues = app(MirIaValidator::class)->validar($reserva);
 *
 * SELF-TEST:
 *   \App\Services\MirIaValidator::selfTest();  // via tinker
 */
class MirIaValidator
{
    /** Modelo IA por defecto. OpenAI gpt-4o soporta tools/function-calling. */
    private const MODEL = 'gpt-4o';

    /** TTL del cache en segundos (24h). */
    private const CACHE_TTL = 86400;

    /** Maximo de iteraciones del loop de tool_calls (evita bucles infinitos). */
    private const MAX_TOOL_ITERATIONS = 3;

    /** Timeout HTTP para web_search (segundos). */
    private const WEB_SEARCH_TIMEOUT = 6;

    /**
     * Valida los datos de la reserva y devuelve una lista de issues.
     *
     * Formato devuelto (igual que MirDataValidator::validar()):
     *   [
     *     [
     *       'severity' => 'error'|'warning',
     *       'entidad'  => 'cliente'|'huesped_{id}',
     *       'campo'    => 'codigo_postal'|'apellido1'|...,
     *       'mensaje'  => 'descripcion del problema',
     *       'sugerencia' => 'valor sugerido o null',
     *     ],
     *     ...
     *   ]
     *
     * @return array<int, array<string, mixed>>
     */
    public function validar(Reserva $reserva): array
    {
        try {
            // Respetar DEMO_MODE: no gastar tokens en demos
            if ((bool) config('demo.demo_mode', false)) {
                return [];
            }

            $datos = $this->recolectarDatos($reserva);
            if (empty($datos['bloques'])) {
                return [];
            }

            $hash = md5(json_encode($datos, JSON_UNESCAPED_UNICODE));
            $cacheKey = "mir_ia_val:{$hash}";

            $resultado = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($datos) {
                return $this->ejecutarValidacionIA($datos);
            });

            // [FIX 2026-04-19] Si la IA no pudo validar (timeout, API caida,
            // JSON malformado...), devolvemos un error BLOQUEANTE en vez de
            // dejar pasar. Mejor NO enviar a MIR que enviar sin validar.
            if ($resultado === null) {
                Cache::forget($cacheKey); // no persistir "null" en cache
                Log::warning('[MirIaValidator] IA no disponible -> bloqueando envio MIR', [
                    'reserva_id' => $reserva->id,
                ]);
                return [[
                    'severity'   => 'error',
                    'entidad'    => 'reserva',
                    'entidad_id' => $reserva->id,
                    'campo'      => '_ia_no_disponible',
                    'mensaje'    => 'Validacion IA no disponible (reintentar en breve). No se envia a MIR sin validar.',
                    'sugerencia' => null,
                ]];
            }
            return $resultado;
        } catch (\Throwable $e) {
            // [FIX 2026-04-19] Antes era fail-safe (dejaba pasar). Ahora
            // bloqueamos para no enviar sin validar.
            Log::warning('[MirIaValidator] Excepcion no capturada -> bloqueando envio MIR', [
                'error' => $e->getMessage(),
                'reserva_id' => $reserva->id ?? null,
            ]);
            return [[
                'severity'   => 'error',
                'entidad'    => 'reserva',
                'entidad_id' => $reserva->id ?? 0,
                'campo'      => '_ia_excepcion',
                'mensaje'    => 'Validacion IA lanzo excepcion: ' . mb_substr($e->getMessage(), 0, 200),
                'sugerencia' => null,
            ]];
        }
    }

    // =========================================================================
    // Recoleccion de datos
    // =========================================================================

    /**
     * Construye el bloque de datos a enviar al modelo.
     *
     * @return array{bloques: array<int, array<string, mixed>>}
     */
    private function recolectarDatos(Reserva $reserva): array
    {
        $bloques = [];

        // Titular (cliente)
        $cliente = $reserva->cliente;
        if ($cliente instanceof Cliente) {
            $bloques[] = [
                'entidad'        => 'cliente',
                'tipo'           => 'titular',
                'nombre'         => $cliente->nombre,
                'apellido1'      => $cliente->apellido1,
                'apellido2'      => $cliente->apellido2,
                // [FIX 2026-04-18] El modelo Cliente no tiene campo 'dni': el DNI
                // se guarda en num_identificacion. Misma historia con municipio:
                // no hay 'nombre_municipio', el campo real es 'municipio' (o 'localidad').
                'dni'            => $cliente->num_identificacion ?? null,
                'tipo_documento' => $cliente->tipo_documento_str ?? $cliente->tipo_documento ?? null,
                'nacionalidad'   => $cliente->nacionalidadStr ?? $cliente->nacionalidad ?? null,
                'direccion'      => $cliente->direccion ?? null,
                'municipio'      => $cliente->municipio ?? $cliente->localidad ?? null,
                'provincia'      => $cliente->provincia ?? null,
                'codigo_postal'  => $cliente->codigo_postal ?? null,
            ];
        }

        // Huespedes de la reserva
        $huespedes = Huesped::where('reserva_id', $reserva->id)->get();
        foreach ($huespedes as $h) {
            $bloques[] = [
                'entidad'        => 'huesped_' . $h->id,
                'tipo'           => 'huesped',
                'nombre'         => $h->nombre,
                // Huesped usa 'primer_apellido' O 'apellido1' (inconsistente en BD)
                'apellido1'      => $h->primer_apellido ?? $h->apellido1 ?? null,
                'apellido2'      => $h->segundo_apellido ?? $h->apellido2 ?? null,
                // [FIX 2026-04-18] Huesped usa 'numero_identificacion', no 'dni'
                'dni'            => $h->numero_identificacion ?? null,
                'tipo_documento' => $h->tipo_documento_str ?? $h->tipo_documento ?? null,
                'nacionalidad'   => $h->nacionalidadStr ?? $h->nacionalidad ?? null,
                'direccion'      => $h->direccion ?? null,
                'municipio'      => $h->municipio ?? $h->localidad ?? null,
                'provincia'      => $h->provincia ?? null,
                'codigo_postal'  => $h->codigo_postal ?? null,
            ];
        }

        return ['bloques' => $bloques];
    }

    // =========================================================================
    // Llamada IA + loop de tools
    // =========================================================================

    /**
     * Ejecuta la validacion a traves del AIGatewayService con loop de tool_calls.
     *
     * @param array{bloques: array<int, array<string, mixed>>} $datos
     * @return array<int, array<string, mixed>>
     */
    private function ejecutarValidacionIA(array $datos): array
    {
        $gateway = app(AIGatewayService::class);

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->userPrompt($datos['bloques'])],
        ];

        $tools = [[
            'type' => 'function',
            'function' => [
                'name' => 'web_search',
                'description' => 'Buscar informacion en internet para verificar datos de direcciones espanolas (codigos postales, calles, municipios).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Consulta a buscar en DuckDuckGo. Ejemplo: "codigo postal 18017 Granada callejero"',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
        ]];

        $params = [
            'model'           => self::MODEL,
            'temperature'     => 0.1,
            'max_tokens'      => 1000,
            'messages'        => $messages,
            'tools'           => $tools,
            'tool_choice'     => 'auto',
            'response_format' => ['type' => 'json_object'],
        ];

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            try {
                $response = $gateway->chatCompletion($params);
            } catch (\Throwable $e) {
                // [FIX 2026-04-19] Devolver null para señalizar "no pude validar"
                // — validar() lo convertira en error bloqueante.
                Log::warning('[MirIaValidator] chatCompletion fallo', [
                    'iter'  => $i,
                    'error' => mb_substr($e->getMessage(), 0, 250),
                ]);
                return null;
            }

            $choice = $response['choices'][0] ?? null;
            if (!is_array($choice)) {
                Log::warning('[MirIaValidator] Respuesta sin choices', [
                    'source' => $response['_gateway_source'] ?? 'unknown',
                ]);
                return null;
            }

            $message   = $choice['message'] ?? [];
            $toolCalls = $message['tool_calls'] ?? null;

            // Caso A: el modelo pide herramientas
            if (is_array($toolCalls) && !empty($toolCalls)) {
                // Guardamos el mensaje del asistente con las tool_calls (contrato OpenAI)
                $params['messages'][] = [
                    'role'       => 'assistant',
                    'content'    => $message['content'] ?? null,
                    'tool_calls' => $toolCalls,
                ];

                foreach ($toolCalls as $tc) {
                    $name = $tc['function']['name'] ?? '';
                    $args = $tc['function']['arguments'] ?? '{}';
                    $argsArr = is_array($args) ? $args : (json_decode($args, true) ?: []);
                    $toolResult = '';

                    if ($name === 'web_search') {
                        $toolResult = $this->webSearch((string) ($argsArr['query'] ?? ''));
                    } else {
                        $toolResult = json_encode(['error' => "tool '{$name}' no implementada"]);
                    }

                    $params['messages'][] = [
                        'role'         => 'tool',
                        'tool_call_id' => $tc['id'] ?? ($tc['tool_call_id'] ?? 'call_' . $i),
                        'name'         => $name,
                        'content'      => $toolResult,
                    ];
                }
                // Pedimos al modelo que continue con el resultado de la(s) herramienta(s)
                continue;
            }

            // Caso B: el modelo responde con contenido final
            $content = (string) ($message['content'] ?? '');
            return $this->parsearRespuesta($content);
        }

        Log::warning('[MirIaValidator] Excedido MAX_TOOL_ITERATIONS sin respuesta final');
        return null; // señal de "no pude validar" -> bloqueante
    }

    // =========================================================================
    // Prompts
    // =========================================================================

    private function systemPrompt(): string
    {
        return <<<SYS
Eres un validador de datos para el registro de hospedajes ante el Ministerio del
Interior de Espana (sistema SES.hospedajes) segun el Real Decreto 933/2021. Tu
tarea es detectar INCOHERENCIAS en los datos de una reserva que podrian hacer
que MIR rechace el envio.

IMPORTANTE — los viajeros pueden ser ESPANOLES o EXTRANJEROS:
- Si la nacionalidad NO es Espana (ES/ESP/ESPANA), los datos de residencia
  (direccion, ciudad, provincia, codigo postal) son los DEL PAIS DE ORIGEN
  del viajero, no de Espana. El RD 933/2021 Anexo I solo exige para el
  viajero: direccion completa, localidad y pais. El codigo postal NO es
  obligatorio, y si se rellena, debe evaluarse segun las reglas del pais
  del viajero, NO de Espana.
- Ejemplo: un viajero marroqui puede tener municipio=Tanger, CP=90000
  (formato marroqui). Esto es CORRECTO, no un error. NO marques como error
  "Tanger no existe en Espana", porque es evidente que no es un viajero
  espanol.
- Ejemplo: un viajero canadiense con CP L4n0r5 (formato alfanumerico
  canadiense). Esto es valido en Canada. No marques error.
- Ejemplo: un viajero con pasaporte (tipo_documento=Passport) es
  extranjero por definicion. NO esperes formato DNI espanol en ese caso.

Analiza si:
1. La direccion es coherente con el pais del viajero.
   - Si pais=Espana: CP debe ser espanol (5 digitos, prefijo provincial valido).
   - Si pais extranjero: no aplicar reglas espanolas.
2. Los apellidos estan bien escritos y partidos (no preposiciones sueltas
   tipo "DE", "DEL", "LA" como apellido completo).
3. El tipo de documento encaja con la nacionalidad:
   - Para espanoles: DNI (8 digitos + letra) o NIE si es residente extranjero.
   - Para extranjeros: Pasaporte o documento de identidad nacional de su
     pais. EL CAMPO "dni"/"num_identificacion" CONTIENE EL NUMERO DE SU
     DOCUMENTO EXTRANJERO, NO debe estar vacio.

REGLA TAJANTE sobre tipo_documento y viajeros extranjeros:
   - Si la nacionalidad NO es espanola (ES), NUNCA marques ERROR por:
     * El valor del campo tipo_documento (puede estar mal clasificado por
       el OCR que leyo el documento — p.ej. "DNI" para un checo es solo
       un error de clasificacion nuestro, no un problema de datos).
     * El formato del numero de documento (no tiene que cumplir el
       formato espanol).
     * Que el numero tenga mas o menos digitos que un DNI espanol.
   - TODO esto debe ser, como mucho, severity="warning". MIR acepta
     viajeros extranjeros aunque nuestro sistema haya clasificado mal
     internamente el tipo_documento: se envia como pasaporte extranjero
     y punto.
   - Tampoco marques error cuando un viajero extranjero tenga municipio
     o provincia de otro pais. Eso es lo normal.
4. Si el pais es Espana y el codigo postal es espanol, comprobar que
   existe realmente en el callejero para el municipio/provincia indicados.

Si no estas seguro sobre un codigo postal o direccion, USA la herramienta
web_search para verificarlo antes de marcar un issue.

Devuelve SIEMPRE un JSON valido con EXACTAMENTE este formato:
{
  "issues": [
    {
      "severity": "error" | "warning",
      "entidad":  "cliente" | "huesped_{id}",
      "campo":    "codigo_postal" | "apellido1" | "apellido2" | "direccion" | "municipio" | "provincia" | "nacionalidad" | "dni" | "tipo_documento" | "nombre",
      "mensaje":  "descripcion clara del problema",
      "sugerencia": "valor sugerido o null"
    }
  ]
}

Si no detectas ningun problema, devuelve: {"issues": []}

Reglas duras:
- Severity "error" solo cuando MIR va a rechazar con casi toda certeza.
- Severity "warning" para sospechas razonables.
- Si no estas seguro, prefiere "warning".
- NO inventes problemas para rellenar. Si todo cuadra, devuelve issues vacio.
- Un viajero extranjero con datos de su pais NO ES UN ERROR — es lo normal.
SYS;
    }

    /**
     * @param array<int, array<string, mixed>> $bloques
     */
    private function userPrompt(array $bloques): string
    {
        $texto = "Datos a validar:\n\n";
        foreach ($bloques as $b) {
            $texto .= "=== " . strtoupper((string) ($b['tipo'] ?? 'persona')) . " (entidad: {$b['entidad']}) ===\n";
            foreach ($b as $k => $v) {
                if (in_array($k, ['entidad', 'tipo'], true)) continue;
                $texto .= "- {$k}: " . ($v === null || $v === '' ? '(vacio)' : (string) $v) . "\n";
            }
            $texto .= "\n";
        }
        $texto .= "Analiza los datos anteriores y devuelve el JSON con los issues detectados.";
        return $texto;
    }

    // =========================================================================
    // Parseo
    // =========================================================================

    /**
     * Parsea la respuesta del modelo. Fail-safe: si algo falla devuelve [].
     *
     * @return array<int, array<string, mixed>>
     */
    private function parsearRespuesta(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            Log::warning('[MirIaValidator] content vacio de la IA -> bloquear');
            return null; // no pudo validar
        }

        // Intento 1: JSON directo
        $data = json_decode($content, true);

        // Intento 2: extraer primer objeto JSON con regex (por si el modelo
        // devuelve texto extra o lo envuelve en ```json ... ```)
        if (!is_array($data)) {
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $m)) {
                $data = json_decode($m[0], true);
            }
        }

        if (!is_array($data)) {
            Log::warning('[MirIaValidator] No se pudo parsear JSON de la IA -> bloquear', [
                'preview' => mb_substr($content, 0, 200),
            ]);
            return null;
        }

        // Si el JSON parsea pero no tiene "issues" o es invalido -> trataremos como "sin issues" (IA valido y dijo "todo OK"). Esto ES correcto.
        $issues = $data['issues'] ?? [];
        if (!is_array($issues)) {
            Log::warning('[MirIaValidator] campo issues invalido, asumir [] (IA dijo OK)');
            return [];
        }

        $out = [];
        foreach ($issues as $issue) {
            if (!is_array($issue)) continue;
            $severity = $issue['severity'] ?? 'warning';
            if (!in_array($severity, ['error', 'warning'], true)) {
                $severity = 'warning';
            }
            $out[] = [
                'severity'   => $severity,
                'entidad'    => (string) ($issue['entidad'] ?? ''),
                'campo'      => (string) ($issue['campo'] ?? ''),
                'mensaje'    => (string) ($issue['mensaje'] ?? ''),
                'sugerencia' => $issue['sugerencia'] ?? null,
            ];
        }
        return $out;
    }

    // =========================================================================
    // Tool: web_search (DuckDuckGo HTML endpoint)
    // =========================================================================

    /**
     * Busqueda web simple contra DuckDuckGo. Devuelve hasta 3 snippets.
     * Fail-safe: si no consigue resultados, devuelve JSON con aviso.
     */
    private function webSearch(string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return json_encode(['results' => [], 'note' => 'empty query']);
        }

        try {
            $url = 'https://html.duckduckgo.com/html/?q=' . urlencode($query);
            $resp = Http::timeout(self::WEB_SEARCH_TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; MirIaValidator/1.0)',
                    'Accept'     => 'text/html',
                ])
                ->get($url);

            if (!$resp->ok()) {
                return json_encode([
                    'results' => [],
                    'note'    => 'http status ' . $resp->status(),
                ]);
            }

            $html = $resp->body();
            $snippets = [];

            // Extraer snippets con regex: clase result__snippet es la convencion
            // actual del HTML de DDG (puede cambiar, por eso fail-safe).
            if (preg_match_all(
                '/<a[^>]*class="[^"]*result__snippet[^"]*"[^>]*>(.*?)<\/a>/is',
                $html,
                $matches
            )) {
                foreach ($matches[1] as $raw) {
                    $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $text = preg_replace('/\s+/', ' ', trim($text));
                    if ($text !== '') {
                        $snippets[] = $text;
                    }
                    if (count($snippets) >= 3) break;
                }
            }

            // Fallback: si el selector cambio, sacar titulos
            if (empty($snippets)
                && preg_match_all(
                    '/<a[^>]*class="[^"]*result__a[^"]*"[^>]*>(.*?)<\/a>/is',
                    $html,
                    $titles
                )
            ) {
                foreach ($titles[1] as $raw) {
                    $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $text = preg_replace('/\s+/', ' ', trim($text));
                    if ($text !== '') {
                        $snippets[] = $text;
                    }
                    if (count($snippets) >= 3) break;
                }
            }

            return json_encode([
                'query'   => $query,
                'results' => $snippets,
                'note'    => empty($snippets) ? 'no snippets extracted' : null,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Log::warning('[MirIaValidator] webSearch fallo', [
                'query' => $query,
                'error' => mb_substr($e->getMessage(), 0, 200),
            ]);
            return json_encode(['results' => [], 'error' => 'web_search unavailable']);
        }
    }

    // =========================================================================
    // Self-test
    // =========================================================================

    /**
     * Self-test integrado. Invocable desde tinker:
     *
     *   \App\Services\MirIaValidator::selfTest();
     *
     * Fabrica una Reserva + Cliente "in memory" (sin guardar en DB) con el
     * caso real de Pedro Jose: CP 18017 + direccion "BAR DE ABALO" + provincia
     * PONTEVEDRA (combinacion imposible en el callejero: el CP 18017 es de
     * Granada). Un validador determinista solo lo caza si valida el rango
     * provincial; el validador IA ademas deberia detectar que "BAR DE ABALO"
     * es un lugar de Pontevedra, no Granada.
     */
    public static function selfTest(): void
    {
        $cliente = new Cliente();
        $cliente->id = 999999;
        $cliente->nombre = 'PEDRO JOSE';
        $cliente->apellido1 = 'GARCIA';
        $cliente->apellido2 = 'LOPEZ';
        $cliente->dni = '12345678Z';
        $cliente->tipo_documento_str = 'DNI';
        $cliente->nacionalidadStr = 'ESPANA';
        $cliente->direccion = 'BAR DE ABALO';
        $cliente->nombre_municipio = 'ABALO';
        $cliente->provincia = 'PONTEVEDRA';
        $cliente->codigo_postal = '18017';

        $reserva = new Reserva();
        $reserva->id = 999999;
        $reserva->cliente_id = 999999;
        // Inyectamos la relacion cliente sin tocar DB
        $reserva->setRelation('cliente', $cliente);

        $validator = new self();
        // Saltamos el cache (el hash pega en Cache::remember; limpiamos por si acaso)
        $issues = $validator->ejecutarValidacionIA($validator->recolectarDatos($reserva));

        echo "=== MirIaValidator selfTest ===\n";
        echo "Caso: Pedro Jose, CP 18017 + BAR DE ABALO + PONTEVEDRA (incoherencia CP/provincia)\n\n";
        if (empty($issues)) {
            echo "La IA no devolvio issues (puede ser DEMO_MODE, API caida o falso negativo).\n";
            return;
        }
        foreach ($issues as $i => $issue) {
            echo "[" . ($i + 1) . "] {$issue['severity']} | {$issue['entidad']} | {$issue['campo']}\n";
            echo "    msg: {$issue['mensaje']}\n";
            if (!empty($issue['sugerencia'])) {
                echo "    sugerencia: {$issue['sugerencia']}\n";
            }
            echo "\n";
        }
    }
}
