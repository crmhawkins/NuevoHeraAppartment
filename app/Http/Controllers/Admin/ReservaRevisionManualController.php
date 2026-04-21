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
                // Sigue bloqueada por validacion o datos no listos
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
            if ($request->boolean('autorevalidar')) {
                $reserva = Reserva::find($data['reserva_id']);
                if ($reserva) {
                    try {
                        $mir = new MIRService();
                        $mir->enviarSiLista($reserva);
                    } catch (\Throwable $e) {
                        Log::error('[RevisionManual] Error revalidando tras fix', [
                            'reserva_id' => $reserva->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
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
