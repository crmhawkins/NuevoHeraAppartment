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
                $r->_issues_parsed = $this->parseIssues($r->mir_respuesta, $r);
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
     * [2026-04-22] Rota una foto de DNI ±90 / 180 grados in-place. Se usa
     * desde los botones de la miniatura en el panel de revision manual para
     * arreglar fotos que quedaron mal orientadas tras el auto-orient.
     */
    public function rotarFoto(Request $request, int $photoId)
    {
        $grados = (int) $request->input('grados', 90);
        if (!in_array($grados, [90, -90, 180], true)) {
            return back()->with('error', "Grados invalidos: {$grados}");
        }

        $photo = Photo::find($photoId);
        if (!$photo || !in_array($photo->photo_categoria_id, [13, 14, 15, 16], true)) {
            return back()->with('error', 'Foto no encontrada o categoria no DNI.');
        }

        $rel = preg_replace('~^private/~', '', (string) $photo->url);
        $abs = storage_path('app/' . $rel);
        if (!is_file($abs)) {
            return back()->with('error', "El archivo de la foto ya no existe en disco.");
        }

        try {
            $bytes = @file_get_contents($abs);
            $img = @imagecreatefromstring($bytes);
            if (!$img) {
                return back()->with('error', 'La imagen no se pudo decodificar (formato no soportado).');
            }
            $rotado = imagerotate($img, -$grados, 0);
            imagedestroy($img);
            if (!$rotado) {
                return back()->with('error', 'GD no pudo rotar la imagen.');
            }
            if (!imagejpeg($rotado, $abs, 90)) {
                imagedestroy($rotado);
                return back()->with('error', 'No se pudo guardar la imagen rotada.');
            }
            imagedestroy($rotado);

            Log::info('[RevisionManual] Foto DNI rotada manualmente', [
                'photo_id' => $photoId, 'grados' => $grados, 'user' => optional(auth()->user())->id,
            ]);

            return back()->with('success', "Foto #{$photoId} rotada {$grados}°.");
        } catch (\Throwable $e) {
            Log::error('[RevisionManual] Error rotando foto', ['photo_id' => $photoId, 'error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
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

        // [2026-04-22] Construir prompt ENFOCADO segun los campos que faltan
        // (parseados del mir_respuesta). Asi la IA se centra en lo que
        // realmente necesitamos en vez de volver a leer todo el DNI entero.
        $camposFaltantes = $this->detectarCamposFaltantes($reserva);

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

            $side = $foto->photo_categoria_id === 15 ? 'passport' : 'front';
            // Primera pasada con auto-orientacion. Si no encuentra ninguno
            // de los campos objetivo, reintenta con rotaciones adicionales
            // (la foto puede ser ambigua: misma proporcion vertical y el
            // EXIF puede mentir). Asi nos aseguramos de pillar el dato.
            $data = $this->invocarIaOcr($absolutePath, $side, $camposFaltantes);
            if ($data === null || !$this->tieneCamposUtiles($data, $camposFaltantes)) {
                foreach ([180, 90, 270] as $rot) {
                    $tmpRotado = $this->rotarImagenA($absolutePath, $rot);
                    if ($tmpRotado === null) continue;
                    $dataAlt = $this->invocarIaOcr($tmpRotado, $side, $camposFaltantes);
                    @unlink($tmpRotado);
                    if ($dataAlt !== null && $this->tieneCamposUtiles($dataAlt, $camposFaltantes)) {
                        Log::info('[RevisionManual] OCR encontro datos tras rotar ' . $rot . 'deg', [
                            'reserva_id' => $reserva->id, 'photo_id' => $foto->id,
                        ]);
                        $data = $dataAlt;
                        break;
                    }
                }
            }
            if ($data === null) {
                $errores[] = "photo#{$foto->id}: IA no respondio";
                continue;
            }

            // Actualizar solo los campos VACIOS del cliente/huesped
            $updates = $this->aplicarCamposVacios($persona, $data, $esCliente);

            // [2026-04-22] Propagacion: si cliente y huesped son la misma
            // persona (mismo DNI), lo que leyo el OCR del cliente vale
            // tambien para el huesped. Asi rellenamos numero_soporte del
            // huesped 2597 aunque la foto sea del cliente 5903.
            $propagados = $this->propagarAGemelos($persona, $data, $esCliente, $reserva->id);

            if (!empty($updates)) {
                if ($esCliente) {
                    $actualizadosCli[$persona->id] = array_merge($actualizadosCli[$persona->id] ?? [], $updates);
                } else {
                    $actualizadosHue[$persona->id] = array_merge($actualizadosHue[$persona->id] ?? [], $updates);
                }
            }
            foreach ($propagados as $tipo => $porPersona) {
                foreach ($porPersona as $pid => $campos) {
                    if ($tipo === 'cliente') {
                        $actualizadosCli[$pid] = array_merge($actualizadosCli[$pid] ?? [], $campos);
                    } else {
                        $actualizadosHue[$pid] = array_merge($actualizadosHue[$pid] ?? [], $campos);
                    }
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

    private function rotarImagenA(string $path, int $grados): ?string
    {
        return \App\Support\DniImageOrienter::rotarA($path, $grados);
    }

    /**
     * ¿La respuesta de la IA contiene al menos UNO de los campos que
     * estabamos buscando, con valor no vacio? Usado para decidir si
     * hacen falta mas rotaciones.
     *
     * @param array<int, string> $camposObjetivo
     */
    private function tieneCamposUtiles(array $data, array $camposObjetivo): bool
    {
        // Mapa nombre_campo_modelo -> posibles keys en la respuesta IA
        $keys = [
            'numero_soporte_documento'  => ['numero_soporte_documento', 'numero_soporte', 'num_soporte'],
            'num_identificacion'        => ['num_identificacion', 'dni', 'numero_dni_o_pasaporte'],
            'numero_identificacion'     => ['num_identificacion', 'dni', 'numero_dni_o_pasaporte'],
            'primer_apellido'           => ['apellido1', 'apellidos', 'primer_apellido'],
            'segundo_apellido'          => ['apellido2', 'segundo_apellido'],
            'apellido1'                 => ['apellido1', 'apellidos'],
            'apellido2'                 => ['apellido2'],
            'nombre'                    => ['nombre'],
            'fecha_nacimiento'          => ['fecha_nacimiento'],
            'fecha_expedicion'          => ['fecha_expedicion'],
            'fecha_caducidad'           => ['fecha_caducidad'],
            'fecha_expedicion_doc'      => ['fecha_expedicion'],
            'codigo_postal'             => ['codigo_postal'],
            'direccion'                 => ['direccion'],
            'provincia'                 => ['provincia'],
            'nombre_municipio'          => ['nombre_municipio', 'municipio', 'localidad'],
        ];
        // Si no habia campos objetivo especificos, aceptamos cualquier campo no vacio
        if (empty($camposObjetivo)) {
            foreach ($data as $v) {
                if (is_string($v) && trim($v) !== '') return true;
            }
            return false;
        }
        foreach ($camposObjetivo as $c) {
            foreach ($keys[$c] ?? [$c] as $k) {
                if (!empty(trim((string) ($data[$k] ?? '')))) return true;
            }
        }
        return false;
    }

    private function corregirOrientacionImagen(string $path): ?string
    {
        return \App\Support\DniImageOrienter::autoOrient($path);
    }

    /**
     * Parsea el mir_respuesta actual de la reserva para saber que campos
     * faltan y poder pedirselos explicitamente a la IA.
     *
     * @return array<int, string> Lista de nombres de campo (sin duplicados).
     */
    private function detectarCamposFaltantes(Reserva $reserva): array
    {
        $respuesta = json_decode((string) $reserva->mir_respuesta, true);
        $issues = is_array($respuesta) ? ($respuesta['issues'] ?? []) : [];
        $campos = [];
        foreach ((array) $issues as $i) {
            if (($i['severity'] ?? '') !== 'error') continue;
            $campo = (string) ($i['campo'] ?? '');
            if ($campo === '' || str_starts_with($campo, '_')) continue;
            // Normalizar alias genericos de la IA al nombre real
            $alias = [
                'dni'                   => 'numero_identificacion',
                'codigo_soporte'        => 'numero_soporte_documento',
                'num_soporte'           => 'numero_soporte_documento',
                'idesp'                 => 'numero_soporte_documento',
                'localidad'             => 'nombre_municipio',
            ];
            $campo = $alias[strtolower($campo)] ?? $campo;
            $campos[$campo] = true;
        }
        return array_keys($campos);
    }

    /**
     * Llama directamente a /api/chat de Ollama con la foto en base64. Si se
     * pasa $camposObjetivo, el prompt es ENFOCADO en esos campos (la IA
     * mira puntos concretos del DNI en vez de extraer todo de nuevo).
     */
    private function invocarIaOcr(string $imagePath, string $side, array $camposObjetivo = []): ?array
    {
        $baseUrl = config('services.hawkins_ai.url', env('HAWKINS_AI_URL'));
        $apiKey  = config('services.hawkins_ai.api_key', env('HAWKINS_AI_API_KEY'));
        $model   = config('services.hawkins_ai.model', env('HAWKINS_AI_MODEL', 'qwen3-vl:8b'));
        if (empty($baseUrl)) return null;

        $directOllamaUrl = preg_replace('/:11435(\/)?$/', ':11434$1', rtrim($baseUrl, '/'));
        $fullUrl = rtrim($directOllamaUrl, '/') . '/api/chat';

        // Construir prompt enfocado segun campos objetivo (si hay). Si no,
        // caer al prompt general.
        if (!empty($camposObjetivo)) {
            $prompt = $this->construirPromptEnfocado($side, $camposObjetivo);
        } else {
            $prompt = $side === 'passport'
                ? 'Extrae del pasaporte usando la MRZ como fuente principal. Responde JSON con keys: nombre, apellidos, fecha_nacimiento (YYYY-MM-DD), nacionalidad (ISO2), numero_dni_o_pasaporte, fecha_caducidad, fecha_expedicion, sexo (M/F), tipo_documento="Passport", mrz_linea1, mrz_linea2. Solo JSON.'
                : 'Extrae del DNI/NIE espanol: nombre, apellido1, apellido2, dni (num_identificacion), fecha_nacimiento (YYYY-MM-DD), fecha_expedicion, fecha_caducidad, sexo, nacionalidad, tipo_documento (DNI/NIE), numero_soporte_documento (campo NUM SOPORTE o IDESP del anverso, formato 3 letras + 6 digitos). Responde SOLO JSON valido.';
        }

        // [2026-04-22] Corregir orientacion antes de mandar a la IA. Fotos
        // de moviles suelen venir con metadato EXIF de rotacion que PHP no
        // aplica automaticamente. Ademas el DNI espanol es SIEMPRE apaisado
        // (mas ancho que alto), asi que si la foto viene en vertical la
        // rotamos 90 grados. Qwen3-VL puede leer texto rotado pero pierde
        // mucha fiabilidad con texto pequeno (NUM SOPORTE, fechas).
        $bytes = $this->corregirOrientacionImagen($imagePath);
        if ($bytes === null) $bytes = @file_get_contents($imagePath);
        if ($bytes === false || $bytes === null) return null;

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
     * Construye un prompt enfocado pidiendo a la IA solo los campos que
     * faltan. Incluye pistas visuales concretas para que se centre en la
     * zona correcta del documento (el NUM SOPORTE esta en un sitio fijo
     * del DNI, por ejemplo).
     *
     * @param array<int, string> $campos Nombres reales de columna que faltan.
     */
    private function construirPromptEnfocado(string $side, array $campos): string
    {
        // Pistas visuales por campo — le decimos a la IA donde mirar.
        $pistas = [
            'numero_soporte_documento' =>
                'NUM SOPORTE / IDESP: busca la etiqueta "NUM SOPORTE" (DNI) o "IDESP" en el anverso del documento, DEBAJO de los datos personales (justo encima de la firma). Es un codigo de 3 LETRAS + 6 DIGITOS para DNI (ej: CCA145235, BAB123456). Para NIE es LETRA + 8 digitos (ej: E01234567). Es un texto PEQUENO pero perfectamente legible.',
            'num_identificacion' =>
                'NUMERO DE DNI/NIE: aparece en la parte superior con la etiqueta "DNI" o "NIE". Formato: 8 digitos + 1 letra para DNI (ej: 77018157N); 1 letra + 7 digitos + 1 letra para NIE (ej: X1234567L).',
            'numero_identificacion' =>
                'NUMERO DE DNI/NIE: aparece en la parte superior con la etiqueta "DNI" o "NIE". Formato: 8 digitos + 1 letra para DNI (ej: 77018157N); 1 letra + 7 digitos + 1 letra para NIE (ej: X1234567L).',
            'fecha_nacimiento' =>
                'FECHA NACIMIENTO: busca la etiqueta "NACIMIENTO" en el anverso. Formato DD MM YYYY en el DNI. Convertir a YYYY-MM-DD.',
            'fecha_expedicion' =>
                'FECHA EXPEDICION / EMISION: busca la etiqueta "EMISION" en el anverso. Formato DD MM YYYY. Convertir a YYYY-MM-DD.',
            'fecha_caducidad' =>
                'FECHA CADUCIDAD / VALIDEZ: busca la etiqueta "VALIDEZ" en el anverso. Formato DD MM YYYY. Convertir a YYYY-MM-DD. Siempre POSTERIOR a la emision.',
            'apellido1' => 'PRIMER APELLIDO: primera palabra o primera linea bajo la etiqueta "APELLIDOS".',
            'apellido2' => 'SEGUNDO APELLIDO: segunda palabra o segunda linea bajo la etiqueta "APELLIDOS".',
            'primer_apellido' => 'PRIMER APELLIDO: primera palabra o primera linea bajo la etiqueta "APELLIDOS".',
            'segundo_apellido' => 'SEGUNDO APELLIDO: segunda palabra o segunda linea bajo la etiqueta "APELLIDOS".',
            'nombre' => 'NOMBRE: bajo la etiqueta "NOMBRE".',
            'sexo' => 'SEXO: bajo la etiqueta "SEXO". Valor M o F.',
            'nacionalidad' => 'NACIONALIDAD: bajo la etiqueta "NACIONALIDAD". Codigo 3 letras (ESP, FRA, GBR, etc.).',
            'codigo_postal' => 'CODIGO POSTAL: 5 digitos en la linea del DOMICILIO (reverso del DNI).',
            'direccion' => 'DOMICILIO: direccion completa en el reverso del DNI.',
            'nombre_municipio' => 'LOCALIDAD / MUNICIPIO: bajo el DOMICILIO en el reverso.',
            'provincia' => 'PROVINCIA: bajo la LOCALIDAD en el reverso.',
            'tipo_documento' => 'TIPO DE DOCUMENTO: DNI, NIE o Pasaporte segun la cabecera.',
        ];

        // Mapeo nombres de BD a nombres de clave en el JSON de salida.
        // El cliente y el huesped usan nombres distintos; usamos los del
        // cliente en el JSON y la propagacion se encargara del mapeo.
        $keyMap = [
            'num_identificacion'       => 'num_identificacion',
            'numero_identificacion'    => 'num_identificacion',
            'primer_apellido'          => 'apellido1',
            'segundo_apellido'         => 'apellido2',
        ];

        $listado = [];
        foreach ($campos as $c) {
            $key = $keyMap[$c] ?? $c;
            $pista = $pistas[$c] ?? $pistas[$key] ?? 'Extrae este campo si esta visible en el documento.';
            $listado[] = "- \"{$key}\": {$pista}";
        }

        $sideDesc = $side === 'passport' ? 'PASAPORTE' : 'DNI/NIE espanol';

        return
            "Analiza esta foto de {$sideDesc} y extrae UNICAMENTE los siguientes campos. "
            . "Mira cuidadosamente las zonas indicadas — los campos son perfectamente legibles aunque sean pequenos.\n\n"
            . "CAMPOS A EXTRAER:\n"
            . implode("\n", $listado)
            . "\n\nFORMATO DE RESPUESTA: JSON valido con las keys listadas arriba. Si un campo no se puede leer con certeza, devuelve cadena vacia. Ninguna explicacion, solo el JSON.";
    }

    /**
     * Si el cliente y algun huesped son la misma persona (mismo DNI), los
     * datos leidos del OCR del cliente valen tambien para el huesped, y
     * viceversa. Propaga solo los campos que tenga el destino VACIOS.
     *
     * @return array{cliente: array<int, array<int, string>>, huesped: array<int, array<int, string>>}
     *         Campos efectivamente actualizados por cada entidad propagada.
     */
    private function propagarAGemelos($persona, array $data, bool $esCliente, int $reservaId): array
    {
        $out = ['cliente' => [], 'huesped' => []];

        $dni = $esCliente
            ? (string) ($persona->num_identificacion ?? '')
            : (string) ($persona->numero_identificacion ?? '');
        if ($dni === '') {
            return $out;
        }

        if ($esCliente) {
            // Cliente -> huespedes con mismo DNI en esta reserva
            $huespedes = \App\Models\Huesped::where('reserva_id', $reservaId)
                ->where('numero_identificacion', $dni)
                ->get();
            foreach ($huespedes as $h) {
                $updates = $this->aplicarCamposVacios($h, $data, false);
                if (!empty($updates)) $out['huesped'][$h->id] = $updates;
            }
        } else {
            // Huesped -> cliente de la reserva si mismo DNI
            $reserva = Reserva::find($reservaId);
            $cli = $reserva?->cliente;
            if ($cli && (string) $cli->num_identificacion === $dni) {
                $updates = $this->aplicarCamposVacios($cli, $data, true);
                if (!empty($updates)) $out['cliente'][$cli->id] = $updates;
            }
        }
        return $out;
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
     * Parsea el JSON de mir_respuesta para extraer los issues detectados.
     *
     * [2026-04-22] Ademas de dedup por entidad+campo+mensaje, fusiona issues
     * de cliente y huesped cuando ambos son la misma persona fisica (mismo
     * DNI). Asi en vez de mostrar "cliente: falta X" + "huesped: falta X"
     * (confuso) mostramos un unico issue con badge "cliente + huesped" y un
     * solo boton Arreglar — el metodo fix() ya propaga al gemelo del mismo
     * DNI automaticamente.
     */
    private function parseIssues(?string $raw, $reserva = null): array
    {
        if (empty($raw)) return [];
        try {
            $data = json_decode($raw, true);
            if (!is_array($data)) return [];
            $issues = $data['issues'] ?? [];
            if (!is_array($issues)) return [];

            // [2026-04-22] Normalizar formato: MirIaValidator usa
            // entidad='huesped_2595' (con ID pegado) mientras MirDataValidator
            // usa entidad='huesped' + entidad_id=2595. Unificamos a lo segundo.
            foreach ($issues as &$i) {
                $e = (string) ($i['entidad'] ?? '');
                if (preg_match('/^huesped_(\d+)$/', $e, $m)) {
                    $i['entidad'] = 'huesped';
                    $i['entidad_id'] = (int) $m[1];
                }
            }
            unset($i);

            // Deduplicar por entidad+entidad_id+campo+mensaje (issues identicos)
            $seen = [];
            $uniq = [];
            foreach ($issues as $i) {
                $key = ($i['entidad'] ?? '') . '|' . ($i['entidad_id'] ?? '') . '|' . ($i['campo'] ?? '') . '|' . ($i['mensaje'] ?? '');
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $uniq[] = $i;
            }

            // Fusionar issues de cliente vs huesped que son la misma persona
            // fisica (mismo DNI). La propagacion del fix() se encarga del
            // resto automaticamente.
            if ($reserva) {
                $uniq = $this->fusionarIssuesGemelos($uniq, $reserva);
            }

            return $uniq;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Agrupa issues de cliente + huesped con mismo DNI bajo una unica
     * entrada con las dos entidades anotadas (para que la vista muestre
     * "cliente + huesped #N" en el badge).
     *
     * @param array<int, array<string, mixed>> $issues
     * @return array<int, array<string, mixed>>
     */
    private function fusionarIssuesGemelos(array $issues, $reserva): array
    {
        // Mapa: dni -> lista de entidades que comparten ese DNI
        $cliente = $reserva->cliente;
        if (!$cliente) return $issues;

        $dniCliente = (string) ($cliente->num_identificacion ?? '');
        if ($dniCliente === '') return $issues;

        $idsGemelos = \App\Models\Huesped::where('reserva_id', $reserva->id)
            ->where('numero_identificacion', $dniCliente)
            ->pluck('id')
            ->map(fn($i) => (int) $i)
            ->toArray();

        // Si no hay huespedes con mismo DNI, nada que fusionar
        if (empty($idsGemelos)) return $issues;

        // Agrupar por (campo + mensaje normalizado). Si el grupo tiene un
        // issue de 'cliente' y otro de 'huesped_X' donde X es gemelo,
        // fusionamos.
        $grupos = [];
        foreach ($issues as $idx => $i) {
            $campo = (string) ($i['campo'] ?? '');
            $mensajeNorm = preg_replace('/\s+/', ' ', trim((string) ($i['mensaje'] ?? '')));
            $k = $campo . '||' . $mensajeNorm;
            $grupos[$k][] = $idx;
        }

        $fusionados = [];
        $yaUsados = [];
        foreach ($grupos as $indices) {
            if (count($indices) < 2) continue;

            // Buscar dentro del grupo uno "cliente" y uno "huesped" gemelo
            $idxCli = null; $idxHue = null;
            foreach ($indices as $idx) {
                $e = (string) ($issues[$idx]['entidad'] ?? '');
                $eid = (int) ($issues[$idx]['entidad_id'] ?? 0);
                if ($e === 'cliente') $idxCli = $idx;
                if ($e === 'huesped' && in_array($eid, $idsGemelos, true)) $idxHue = $idx;
            }

            if ($idxCli !== null && $idxHue !== null) {
                // Fusionar: nos quedamos con el issue del cliente pero
                // anotamos que incluye tambien al huesped.
                $issues[$idxCli]['_incluye_huesped'] = true;
                $yaUsados[$idxHue] = true;
            }
        }

        // Reconstruir la lista sin los huespedes gemelos fusionados
        foreach ($issues as $idx => $i) {
            if (isset($yaUsados[$idx])) continue;
            $fusionados[] = $i;
        }
        return $fusionados;
    }
}
