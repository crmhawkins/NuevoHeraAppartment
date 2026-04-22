<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Reserva;
use App\Services\MIRService;
use App\Services\MirPreflightValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * [2026-04-20] Panel de reservas que requieren revision manual por fallo
 * del preflight de MIR.
 *
 * Una reserva llega aqui cuando:
 *  - El cliente ya subio el DNI (dni_entregado=1).
 *  - MIRService::enviarSiLista() intento enviar pero el preflight detecto
 *    errores bloqueantes (CP incorrecto, DNI sin letra, etc.).
 *  - mir_estado quedo en 'error_validacion' y mir_respuesta contiene el
 *    JSON de issues detectados.
 *
 * Desde aqui el admin ve cada issue y puede:
 *  - Ir directamente a editar el cliente o el huesped afectado.
 *  - Re-validar la reserva una vez corregida (boton que llama a
 *    enviarSiLista de nuevo).
 */
class ReservaRevisionManualController extends Controller
{
    public function index(Request $request)
    {
        $reservas = Reserva::with(['cliente', 'apartamento.edificio'])
            ->where('mir_estado', 'error_validacion')
            ->where('estado_id', '!=', 4) // no canceladas
            ->where('dni_entregado', true)
            ->orderBy('fecha_entrada')
            ->get()
            ->map(function ($r) {
                $r->_issues_parsed = $this->parseIssues($r->mir_respuesta);
                $r->_fotos_dni = $this->getFotosDni($r->id);
                return $r;
            });

        return view('admin.reservas-revision-manual.index', compact('reservas'));
    }

    /**
     * Sirve una foto privada del DNI subida por el cliente. Solo accesible
     * desde el panel admin (protegido por el middleware role:ADMIN de la ruta).
     *
     * Las fotos viven en storage/app/photos/dni/ (fuera de public/) y la
     * columna Photo.url guarda 'private/photos/dni/xxxxx.jpg'. Aqui hacemos
     * el strip de 'private/' y servimos desde storage_path.
     */
    public function verFoto(int $photoId): BinaryFileResponse|\Illuminate\Http\Response
    {
        $photo = Photo::find($photoId);
        if (!$photo) {
            abort(404);
        }

        $relUrl = (string) $photo->url;
        // Solo permitimos servir fotos de identificacion (DNI/pasaporte)
        // 13=DNI Frontal, 14=DNI Trasera, 15=Pasaporte Frontal, 16=Pasaporte Trasera
        if (!in_array($photo->photo_categoria_id, [13, 14, 15, 16], true)) {
            abort(403, 'Solo fotos de DNI / pasaporte');
        }

        // Mapeo de prefijos posibles -> storage_path:
        //  'private/photos/dni/...'        -> storage/app/photos/dni/...
        //  'private/photos/dni/legacy/...' -> storage/app/photos/dni/legacy/...
        //  'photos/dni/...' (disco local)  -> storage/app/photos/dni/...
        $rel = preg_replace('~^private/~', '', $relUrl);
        $full = storage_path('app/' . $rel);

        if (!is_file($full)) {
            abort(404, 'Fichero no encontrado');
        }

        return response()->file($full);
    }

    /**
     * Devuelve fotos de DNI (frontal=13, trasera=14) asociadas a una reserva,
     * separadas por persona (cliente / huesped).
     *
     * @return array<string, array<int, \App\Models\Photo>> con claves 'cliente' y 'huespedes'
     */
    private function getFotosDni(int $reservaId): array
    {
        $fotos = Photo::where('reserva_id', $reservaId)
            ->whereIn('photo_categoria_id', [13, 14, 15, 16]) // DNI + Pasaporte
            ->orderBy('photo_categoria_id')
            ->get();

        // [2026-04-21] Marcar cada foto con si el archivo existe fisicamente
        // en disco. Asi la vista sabe si mostrar thumbnail o un placeholder
        // "archivo no disponible" (suele pasar con reservas de hace meses
        // cuyas fotos se perdieron en un deploy antiguo).
        $out = ['cliente' => [], 'huespedes' => []];
        foreach ($fotos as $f) {
            $rel = preg_replace('~^private/~', '', (string) $f->url);
            $f->_archivo_existe = is_file(storage_path('app/' . $rel));

            if ($f->cliente_id) {
                $out['cliente'][] = $f;
            } elseif ($f->huespedes_id) {
                $out['huespedes'][$f->huespedes_id][] = $f;
            }
        }
        return $out;
    }

    public function revalidar(Request $request, int $id)
    {
        $reserva = Reserva::findOrFail($id);

        try {
            $mirService = new MIRService();
            $resultado = $mirService->enviarSiLista($reserva);
            $reserva->refresh();

            if ($resultado === null) {
                // Sigue bloqueada por validacion o datos no listos. Sacamos
                // los errores actuales del mir_respuesta para que el admin
                // vea exactamente que le falta en vez de un mensaje vago.
                $errores = [];
                $resp = json_decode((string) $reserva->mir_respuesta, true);
                if (is_array($resp) && !empty($resp['issues'])) {
                    foreach ($resp['issues'] as $i) {
                        if (($i['severity'] ?? '') !== 'error') continue;
                        $campo = str_starts_with((string) ($i['campo'] ?? ''), '_') ? '(IA)' : ($i['campo'] ?? '?');
                        $errores[] = "{$i['entidad']} · {$campo}: " . mb_substr((string) ($i['mensaje'] ?? ''), 0, 100);
                    }
                }
                if (!empty($errores)) {
                    $faltan = count($errores);
                    $txt = "Reserva #{$reserva->id}: sigue bloqueada por {$faltan} error(es):";
                    $txt .= "\n• " . implode("\n• ", array_slice($errores, 0, 5));
                    if ($faltan > 5) $txt .= "\n… y " . ($faltan - 5) . ' mas';
                    return redirect()
                        ->route('admin.reservas-revision-manual.index')
                        ->with('warning', $txt);
                }
                return redirect()
                    ->route('admin.reservas-revision-manual.index')
                    ->with('warning', "Reserva #{$reserva->id}: sigue bloqueada. Estado: " . ($reserva->mir_estado ?: 'desconocido'));
            }

            if (!empty($resultado['success'])) {
                return redirect()
                    ->route('admin.reservas-revision-manual.index')
                    ->with('success', "Reserva #{$reserva->id}: enviada a MIR correctamente. Lote: " . ($resultado['codigo_referencia'] ?? '-'));
            }

            return redirect()
                ->route('admin.reservas-revision-manual.index')
                ->with('error', "Reserva #{$reserva->id}: " . ($resultado['mensaje'] ?? 'MIR rechazo el envio'));
        } catch (\Throwable $e) {
            Log::error('[RevisionManual] Error revalidando', ['reserva_id' => $id, 'error' => $e->getMessage()]);
            return redirect()
                ->route('admin.reservas-revision-manual.index')
                ->with('error', "Excepcion al revalidar #{$id}: " . $e->getMessage());
        }
    }

    /**
     * Ignora una reserva: la saca del listado cambiando mir_estado a
     * 'ignorado_manual'. Util cuando el admin decide que no se va a
     * enviar a MIR (p.ej. reserva cancelada de facto, huesped no va a
     * venir, etc.).
     */
    public function ignorar(Request $request, int $id)
    {
        $reserva = Reserva::findOrFail($id);
        $reserva->mir_estado = 'ignorado_manual';
        $reserva->save();

        return redirect()
            ->route('admin.reservas-revision-manual.index')
            ->with('success', "Reserva #{$id} marcada como ignorada (no se enviara a MIR).");
    }

    /**
     * [2026-04-21] Corrige un campo concreto de un cliente o huesped desde
     * el panel, sin salir de la pagina. Se usa con los botones "Arreglar"
     * que aparecen al lado de cada issue detectado.
     *
     * Solo permite modificar un whitelist de campos: los que suelen fallar
     * en el preflight MIR (codigo_postal, num_identificacion, provincia,
     * direccion, municipio, nacionalidad, apellido1, apellido2, nombre,
     * tipo_documento).
     */
    public function fix(Request $request)
    {
        $data = $request->validate([
            'reserva_id' => 'required|integer|exists:reservas,id',
            'entidad'    => 'required|in:cliente,huesped',
            'entidad_id' => 'required|integer',
            'campo'      => 'required|string',
            'valor'      => 'nullable|string|max:300',
            'autorevalidar' => 'sometimes|boolean',
        ]);

        // [2026-04-22] Traduccion de nombres de campo genericos (del validador IA
        // o del usuario) a los nombres reales de las columnas en cliente/huesped.
        // Antes: si la IA devolvia campo='dni' (generico) el fix fallaba silencio-
        // samente porque cliente->dni no es una columna real (es num_identificacion).
        $camposAlias = [
            'cliente' => [
                'dni' => 'num_identificacion',
                'numero_identificacion' => 'num_identificacion', // usuario escribe plural
                'codigo_soporte' => 'numero_soporte_documento',
                'num_soporte' => 'numero_soporte_documento',
                'idesp' => 'numero_soporte_documento',
                'localidad' => 'nombre_municipio',
            ],
            'huesped' => [
                'dni' => 'numero_identificacion',
                'num_identificacion' => 'numero_identificacion',
                'codigo_soporte' => 'numero_soporte_documento',
                'num_soporte' => 'numero_soporte_documento',
                'idesp' => 'numero_soporte_documento',
                'apellido1' => 'primer_apellido',
                'apellido2' => 'segundo_apellido',
                'localidad' => 'nombre_municipio',
            ],
        ];
        $campoNorm = strtolower(trim($data['campo']));
        if (isset($camposAlias[$data['entidad']][$campoNorm])) {
            $data['campo'] = $camposAlias[$data['entidad']][$campoNorm];
        }

        // Whitelist de campos editables desde aqui (mapa por entidad porque
        // el nombre del campo difiere entre Cliente y Huesped)
        $camposClienteOk = [
            'codigo_postal', 'num_identificacion', 'provincia', 'direccion',
            'nombre_municipio', 'municipio', 'nacionalidad', 'apellido1',
            'apellido2', 'nombre', 'tipo_documento', 'numero_soporte_documento',
        ];
        $camposHuespedOk = [
            'codigo_postal', 'numero_identificacion', 'provincia', 'direccion',
            'nombre_municipio', 'municipio', 'nacionalidad', 'primer_apellido',
            'segundo_apellido', 'nombre', 'tipo_documento', 'numero_soporte_documento',
            'pais',
        ];

        try {
            if ($data['entidad'] === 'cliente') {
                if (!in_array($data['campo'], $camposClienteOk, true)) {
                    return back()->with('error', "Campo '{$data['campo']}' no editable desde este panel.");
                }
                $persona = \App\Models\Cliente::findOrFail($data['entidad_id']);
            } else {
                if (!in_array($data['campo'], $camposHuespedOk, true)) {
                    return back()->with('error', "Campo '{$data['campo']}' no editable desde este panel.");
                }
                $persona = \App\Models\Huesped::findOrFail($data['entidad_id']);
            }

            $valorAntes = $persona->{$data['campo']} ?? null;
            $persona->{$data['campo']} = $data['valor'] === '' ? null : $data['valor'];
            $persona->save();

            // [2026-04-21] Auto-propagacion: el cliente principal suele
            // aparecer tambien como huesped (es la misma persona fisica).
            // Si se arregla un campo del cliente y hay huespedes con el
            // mismo DNI en esta reserva, actualizamos tambien el campo
            // equivalente del huesped. Y viceversa.
            $propagados = $this->propagarCampo(
                $data['entidad'],
                $persona,
                $data['campo'],
                $data['valor'] === '' ? null : $data['valor'],
                (int) $data['reserva_id']
            );

            Log::info('[RevisionManual] Campo corregido', [
                'reserva_id' => $data['reserva_id'],
                'entidad'    => $data['entidad'],
                'entidad_id' => $data['entidad_id'],
                'campo'      => $data['campo'],
                'antes'      => $valorAntes,
                'despues'    => $persona->{$data['campo']},
                'propagados' => $propagados,
                'user'       => optional(auth()->user())->id,
            ]);

            // Si el admin pidio revalidar automaticamente, intentamos reenviar
            // a MIR. Ojo: esto solo lanza el envio una vez; si sigue bloqueada
            // la veras con los issues actualizados.
            $statusMir = null;
            $erroresRestantes = [];
            if ($request->boolean('autorevalidar')) {
                $reserva = Reserva::find($data['reserva_id']);
                if ($reserva) {
                    try {
                        $mir = new MIRService();
                        $resultado = $mir->enviarSiLista($reserva);
                        $reserva->refresh();
                        if ($resultado !== null && !empty($resultado['success'])) {
                            $statusMir = 'enviado';
                        } else {
                            $statusMir = $reserva->mir_estado;
                            // Extraer los errores restantes de mir_respuesta
                            $resp = json_decode((string) $reserva->mir_respuesta, true);
                            if (is_array($resp) && !empty($resp['issues'])) {
                                foreach ($resp['issues'] as $i) {
                                    if (($i['severity'] ?? '') !== 'error') continue;
                                    $campo = str_starts_with((string) ($i['campo'] ?? ''), '_') ? '(IA)' : ($i['campo'] ?? '?');
                                    $erroresRestantes[] = "{$i['entidad']} · {$campo}: " . mb_substr((string) ($i['mensaje'] ?? ''), 0, 100);
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::error('[RevisionManual] Error revalidando tras fix', [
                            'reserva_id' => $reserva->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // [2026-04-22] Mensaje de vuelta mas util: antes decia solo "campo
            // actualizado" aunque la reserva siguiera bloqueada por otros
            // errores. Ahora decimos exactamente que falta.
            if ($statusMir === 'enviado') {
                return redirect()
                    ->route('admin.reservas-revision-manual.index')
                    ->with('success', "Campo '{$data['campo']}' actualizado. Reserva #{$data['reserva_id']} ENVIADA A MIR correctamente.");
            }
            if (!empty($erroresRestantes)) {
                $faltan = count($erroresRestantes);
                $txt = "Campo '{$data['campo']}' guardado, pero la reserva sigue bloqueada por {$faltan} error(es):";
                $txt .= "\n• " . implode("\n• ", array_slice($erroresRestantes, 0, 5));
                if ($faltan > 5) $txt .= "\n… y " . ($faltan - 5) . ' mas';
                return redirect()
                    ->route('admin.reservas-revision-manual.index')
                    ->with('warning', $txt);
            }
            return redirect()
                ->route('admin.reservas-revision-manual.index')
                ->with('success', "Campo '{$data['campo']}' del {$data['entidad']} #{$data['entidad_id']} actualizado.");
        } catch (\Throwable $e) {
            Log::error('[RevisionManual] Error en fix', ['error' => $e->getMessage(), 'data' => $data]);
            return back()->with('error', 'No se pudo guardar: ' . $e->getMessage());
        }
    }

    /**
     * [2026-04-21] Sincroniza el cambio entre Cliente y Huesped que son la
     * misma persona fisica (comparten num_identificacion). El nombre de
     * algunos campos difiere entre modelos:
     *   cliente.num_identificacion    <-> huesped.numero_identificacion
     *   cliente.apellido1             <-> huesped.primer_apellido
     *   cliente.apellido2             <-> huesped.segundo_apellido
     * El resto de campos (codigo_postal, provincia, direccion, municipio,
     * nombre, nacionalidad, tipo_documento, numero_soporte_documento) se
     * llaman igual en ambos modelos.
     *
     * @return array<int, string> IDs actualizados (para log)
     */
    private function propagarCampo(string $entidad, $persona, string $campo, $nuevoValor, int $reservaId): array
    {
        $propagados = [];

        // Mapeo de nombres entre cliente y huesped
        $mapCliente2Huesped = [
            'num_identificacion' => 'numero_identificacion',
            'apellido1'          => 'primer_apellido',
            'apellido2'          => 'segundo_apellido',
        ];
        $mapHuesped2Cliente = array_flip($mapCliente2Huesped);

        try {
            if ($entidad === 'cliente') {
                $dniCli = (string) ($persona->num_identificacion ?? '');
                if ($dniCli === '') return $propagados;

                $campoH = $mapCliente2Huesped[$campo] ?? $campo;

                $huespedes = \App\Models\Huesped::where('reserva_id', $reservaId)
                    ->where('numero_identificacion', $dniCli)
                    ->get();

                foreach ($huespedes as $h) {
                    if (!array_key_exists($campoH, $h->getAttributes()) &&
                        !property_exists($h, $campoH) &&
                        !in_array($campoH, $h->getFillable(), true)) {
                        continue; // el huesped no tiene ese campo
                    }
                    $h->{$campoH} = $nuevoValor;
                    $h->save();
                    $propagados[] = "huesped#{$h->id}.{$campoH}";
                }
            } elseif ($entidad === 'huesped') {
                $dniHue = (string) ($persona->numero_identificacion ?? '');
                if ($dniHue === '') return $propagados;

                $campoC = $mapHuesped2Cliente[$campo] ?? $campo;

                $reserva = Reserva::find($reservaId);
                $cli = $reserva?->cliente;
                if ($cli && (string) $cli->num_identificacion === $dniHue) {
                    if (array_key_exists($campoC, $cli->getAttributes()) ||
                        in_array($campoC, $cli->getFillable(), true)) {
                        $cli->{$campoC} = $nuevoValor;
                        $cli->save();
                        $propagados[] = "cliente#{$cli->id}.{$campoC}";
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[RevisionManual] Error propagando campo', [
                'entidad' => $entidad, 'campo' => $campo, 'error' => $e->getMessage(),
            ]);
        }

        return $propagados;
    }

    /**
     * [2026-04-22] Re-analiza las fotos del DNI/pasaporte de la reserva con
     * la IA visual (qwen3-vl:8b) para extraer los campos que quedaron vacios
     * la primera vez. Util cuando el primer pass de OCR no vio el numero de
     * soporte o algun otro campo pequeno del documento.
     *
     * Solo sobreescribe campos vacios del cliente/huesped — no pisa datos
     * ya rellenados (por el admin a mano, por un OCR anterior, etc.).
     */
    public function reanalizarDni(Request $request, int $id)
    {
        $reserva = Reserva::with(['cliente'])->findOrFail($id);

        // Localizar fotos DNI (frontales) asociadas a la reserva
        $fotos = Photo::where('reserva_id', $reserva->id)
            ->whereIn('photo_categoria_id', [13, 15]) // 13=DNI Frontal, 15=Pasaporte Frontal
            ->get();

        if ($fotos->isEmpty()) {
            return redirect()
                ->route('admin.reservas-revision-manual.index')
                ->with('error', "Reserva #{$id}: no hay foto del DNI/pasaporte para re-analizar.");
        }

        $actualizadosCli = [];
        $actualizadosHue = [];
        $errores = [];

        foreach ($fotos as $foto) {
            // Resolver ruta fisica
            $rel = preg_replace('~^private/~', '', (string) $foto->url);
            $absolutePath = storage_path('app/' . $rel);
            if (!is_file($absolutePath)) {
                $errores[] = "photo#{$foto->id}: archivo no encontrado en disco";
                continue;
            }

            // Identificar titular: cliente o huesped
            $persona = null;
            $esCliente = false;
            if ($foto->cliente_id && $reserva->cliente && $reserva->cliente->id === (int) $foto->cliente_id) {
                $persona = $reserva->cliente;
                $esCliente = true;
            } elseif ($foto->huespedes_id) {
                $persona = \App\Models\Huesped::find($foto->huespedes_id);
            }
            if (!$persona) {
                $errores[] = "photo#{$foto->id}: sin cliente/huesped asociado";
                continue;
            }

            $data = $this->invocarIaOcr($absolutePath, $foto->photo_categoria_id === 15 ? 'passport' : 'front');
            if ($data === null) {
                $errores[] = "photo#{$foto->id}: IA no respondio";
                continue;
            }

            // Actualizar solo los campos VACIOS del cliente/huesped
            $updates = $this->aplicarCamposVacios($persona, $data, $esCliente);
            if (!empty($updates)) {
                if ($esCliente) {
                    $actualizadosCli[$persona->id] = array_merge($actualizadosCli[$persona->id] ?? [], $updates);
                } else {
                    $actualizadosHue[$persona->id] = array_merge($actualizadosHue[$persona->id] ?? [], $updates);
                }
            }
        }

        // Tras re-analizar, lanzar revalidacion MIR automaticamente
        $mensaje = "Reserva #{$id}: re-analisis completado.";
        $detalles = [];
        foreach ($actualizadosCli as $pid => $fields) $detalles[] = "cliente#{$pid}: " . implode(', ', $fields);
        foreach ($actualizadosHue as $pid => $fields) $detalles[] = "huesped#{$pid}: " . implode(', ', $fields);
        if (!empty($detalles)) {
            $mensaje .= ' Actualizados: ' . implode(' · ', $detalles);
        } else {
            $mensaje .= ' La IA no encontro campos nuevos que rellenar.';
        }
        if (!empty($errores)) {
            $mensaje .= ' (Avisos: ' . implode(', ', $errores) . ')';
        }

        try {
            $mir = new MIRService();
            $mir->enviarSiLista($reserva);
        } catch (\Throwable $e) {
            Log::warning('[RevisionManual] Revalidacion tras reanalisis fallo', [
                'reserva_id' => $id, 'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.reservas-revision-manual.index')
            ->with(empty($detalles) ? 'warning' : 'success', $mensaje);
    }

    /**
     * Llama directamente a /api/chat de Ollama con la foto en base64 y el
     * mismo prompt que usa CheckInPublicController. Devuelve el array de
     * datos extraidos o null si algo fallo.
     */
    private function invocarIaOcr(string $imagePath, string $side): ?array
    {
        $baseUrl = config('services.hawkins_ai.url', env('HAWKINS_AI_URL'));
        $apiKey  = config('services.hawkins_ai.api_key', env('HAWKINS_AI_API_KEY'));
        $model   = config('services.hawkins_ai.model', env('HAWKINS_AI_MODEL', 'qwen3-vl:8b'));
        if (empty($baseUrl)) return null;

        $directOllamaUrl = preg_replace('/:11435(\/)?$/', ':11434$1', rtrim($baseUrl, '/'));
        $fullUrl = rtrim($directOllamaUrl, '/') . '/api/chat';

        $prompt = $side === 'passport'
            ? 'Extrae del pasaporte usando la MRZ como fuente principal. Responde JSON con keys: nombre, apellidos, fecha_nacimiento (YYYY-MM-DD), nacionalidad (ISO2), numero_dni_o_pasaporte, fecha_caducidad, fecha_expedicion, sexo (M/F), tipo_documento="Passport", mrz_linea1, mrz_linea2. Solo JSON.'
            : 'Extrae del DNI/NIE espanol: nombre, apellido1, apellido2, dni (num_identificacion), fecha_nacimiento (YYYY-MM-DD), fecha_expedicion, fecha_caducidad, sexo, nacionalidad, tipo_documento (DNI/NIE), numero_soporte_documento (campo NUM SOPORTE o IDESP del anverso, formato 3 letras + 6 digitos). Responde SOLO JSON valido.';

        $bytes = @file_get_contents($imagePath);
        if ($bytes === false) return null;

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $fullUrl,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 180,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'    => $model,
                    'messages' => [[
                        'role'    => 'user',
                        'content' => $prompt,
                        'images'  => [base64_encode($bytes)],
                    ]],
                    'stream'  => false,
                    'options' => ['temperature' => 0.1],
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-API-Key: ' . $apiKey,
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $resp = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($code !== 200 || !$resp) return null;

            $data = json_decode((string) $resp, true);
            $content = $data['message']['content'] ?? '';
            if (!is_string($content) || $content === '') return null;

            $json = json_decode($content, true);
            if (is_array($json)) return $json;

            // Fallback: extraer JSON de texto
            if (preg_match('/\{.*\}/s', $content, $m)) {
                $json = json_decode($m[0], true);
                if (is_array($json)) return $json;
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('[RevisionManual] invocarIaOcr excepcion', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Aplica al cliente/huesped solo los campos que vengan rellenos del OCR
     * y que el modelo destino tenga actualmente vacios. Devuelve la lista
     * de campos actualizados (para mostrar al admin).
     *
     * @return array<int,string>
     */
    private function aplicarCamposVacios($persona, array $data, bool $esCliente): array
    {
        // Mapa campo_ai -> campo_modelo (distinto cliente/huesped)
        $mapCliente = [
            'nombre'                    => 'nombre',
            'apellido1'                 => 'apellido1',
            'apellido2'                 => 'apellido2',
            'dni'                       => 'num_identificacion',
            'numero_dni_o_pasaporte'    => 'num_identificacion',
            'fecha_nacimiento'          => 'fecha_nacimiento',
            'fecha_expedicion'          => 'fecha_expedicion_doc',
            'sexo'                      => 'sexo',
            'nacionalidad'              => 'nacionalidadStr',
            'numero_soporte_documento'  => 'numero_soporte_documento',
            'numero_soporte'            => 'numero_soporte_documento',
        ];
        $mapHuesped = [
            'nombre'                    => 'nombre',
            'apellido1'                 => 'primer_apellido',
            'apellido2'                 => 'segundo_apellido',
            'dni'                       => 'numero_identificacion',
            'numero_dni_o_pasaporte'    => 'numero_identificacion',
            'fecha_nacimiento'          => 'fecha_nacimiento',
            'fecha_expedicion'          => 'fecha_expedicion',
            'fecha_caducidad'           => 'fecha_caducidad',
            'sexo'                      => 'sexo',
            'nacionalidad'              => 'nacionalidadStr',
            'numero_soporte_documento'  => 'numero_soporte_documento',
            'numero_soporte'            => 'numero_soporte_documento',
        ];
        $map = $esCliente ? $mapCliente : $mapHuesped;

        $actualizados = [];
        foreach ($map as $campoAi => $campoModelo) {
            if (empty($data[$campoAi])) continue;
            // Solo rellenamos campos que estuvieran vacios en el modelo
            $valorActual = $persona->{$campoModelo} ?? null;
            if (!empty($valorActual)) continue;

            $persona->{$campoModelo} = trim((string) $data[$campoAi]);
            $actualizados[] = $campoModelo;
        }

        if (!empty($actualizados)) {
            $persona->save();
            Log::info('[RevisionManual] Campos rellenados via re-analisis IA', [
                'entidad'   => $esCliente ? 'cliente' : 'huesped',
                'id'        => $persona->id,
                'campos'    => $actualizados,
            ]);
        }
        return $actualizados;
    }

    /**
     * Parsea el JSON de mir_respuesta para extraer los issues detectados
     * con el formato [['severity','campo','mensaje','entidad','entidad_id']].
     */
    private function parseIssues(?string $raw): array
    {
        if (empty($raw)) return [];
        try {
            $data = json_decode($raw, true);
            if (!is_array($data)) return [];
            $issues = $data['issues'] ?? [];
            if (!is_array($issues)) return [];
            // Deduplicar por campo+mensaje
            $seen = [];
            $out = [];
            foreach ($issues as $i) {
                $key = ($i['entidad'] ?? '') . '|' . ($i['campo'] ?? '') . '|' . ($i['mensaje'] ?? '');
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $out[] = $i;
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
