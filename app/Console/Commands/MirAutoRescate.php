<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Huesped;
use App\Models\Photo;
use App\Models\Reserva;
use App\Services\CodigoPostalLookupService;
use App\Services\OpenAIVisionFallbackService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-26] Auto-rescate de reservas bloqueadas para MIR.
 *
 * Lee los issues registrados por el validador (mir_respuesta JSON), y para
 * cada uno aplica el fallback adecuado:
 *
 *  - codigo_postal vacio o invalido       -> CodigoPostalLookupService
 *  - numero_soporte_documento vacio/invalido  -> Ollama Cloud Vision (NUM SOPORTE)
 *  - num_identificacion / numero_identificacion mal formato -> Ollama Cloud Vision (numero documento)
 *  - nacionalidad vacia + tipo DNI / nacionalidadStr -> auto-rellenar
 *
 * Despues de aplicar las correcciones, deja el flag mir_estado=null para que
 * el cron normal de MIR lo procese y reenvie. No envia directamente para no
 * duplicar logica.
 *
 * Uso:
 *   php artisan mir:auto-rescate                  -> recorre reservas bloqueadas
 *   php artisan mir:auto-rescate --reserva=6373
 *   php artisan mir:auto-rescate --dry-run        -> no guarda, solo lista lo que haria
 *   php artisan mir:auto-rescate --limit=20
 *
 * Programado como cron cada 30 min, asi cualquier reserva bloqueada se intenta
 * rescatar automaticamente sin intervencion humana.
 */
class MirAutoRescate extends Command
{
    protected $signature = 'mir:auto-rescate
        {--reserva= : id de una reserva concreta}
        {--limit=20 : numero maximo de reservas a procesar}
        {--dry-run : no guarda, solo lista}';

    protected $description = 'Aplica fallbacks (CP lookup, OCR cloud) a reservas bloqueadas para MIR. Tras corregir, deja que el cron normal las reenvie.';

    public function handle(
        OpenAIVisionFallbackService $vision,
        CodigoPostalLookupService $cpLookup
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit') ?: 20;
        $resId = $this->option('reserva');

        $q = Reserva::with(['cliente'])
            ->whereIn('mir_estado', ['error_validacion', 'error', 'rechazado']);
        if ($resId) {
            $q = Reserva::where('id', $resId);
        }
        $reservas = $q->orderBy('fecha_entrada')->limit($limit)->get();

        if ($reservas->isEmpty()) {
            $this->info('No hay reservas bloqueadas. Nada que hacer.');
            return self::SUCCESS;
        }

        $this->info('Procesando ' . $reservas->count() . ' reserva(s)...');
        $this->info($dryRun ? '== DRY-RUN ==' : '== APLICANDO ==');

        $resumen = ['rescatadas' => 0, 'sin_cambios' => 0, 'errores' => 0];

        foreach ($reservas as $r) {
            $this->line('');
            $this->line("--- Reserva #{$r->id} (estado={$r->mir_estado}) ---");

            $issues = $this->extraerIssues($r);
            if (empty($issues)) {
                $this->comment('  sin issues registrados');
                $resumen['sin_cambios']++;
                continue;
            }

            $cambios = 0;
            foreach ($issues as $iss) {
                [$accion, $cambio] = $this->aplicarFallback($iss, $r, $vision, $cpLookup, $dryRun);
                if (!$accion) continue;
                $simbolo = $cambio ? '✓' : '·';
                $this->line("  {$simbolo} {$accion}");
                if ($cambio) $cambios++;
            }

            if ($cambios > 0) {
                $resumen['rescatadas']++;
                if (!$dryRun) {
                    // Reset mir_estado para que el cron normal vuelva a intentar.
                    $r->mir_estado = null;
                    $r->mir_respuesta = null;
                    $r->save();
                }
            } else {
                $resumen['sin_cambios']++;
                $this->comment('  no se pudo rescatar ningun campo');
            }
        }

        $this->info('');
        $this->info("Resultado: {$resumen['rescatadas']} rescatadas, {$resumen['sin_cambios']} sin cambios, {$resumen['errores']} errores.");
        if (!$dryRun && $resumen['rescatadas'] > 0) {
            $this->comment('Las rescatadas estan ahora con mir_estado=null, el cron normal las enviara en el proximo ciclo.');
        }
        return self::SUCCESS;
    }

    /**
     * Extrae los issues a resolver. Pueden venir de dos sitios:
     *  1. mir_respuesta.issues  (validador interno, mir_estado=error_validacion)
     *  2. mir_respuesta.mensaje (rechazo XSD del MIR, mir_estado=error)
     *     -> traducimos el mensaje a un issue equivalente para usar el mismo
     *        flujo de fallback.
     */
    private function extraerIssues(Reserva $r): array
    {
        if (!$r->mir_respuesta) return [];
        $data = json_decode($r->mir_respuesta, true);
        if (!is_array($data)) return [];

        // Caso 1: issues del validador interno
        if (!empty($data['issues']) && is_array($data['issues'])) {
            return $data['issues'];
        }

        // Caso 2: rechazo XSD del MIR
        $mensaje = (string) ($data['mensaje'] ?? '');
        if ($mensaje === '') return [];

        $issues = [];
        // Detectar campo del XSD que falla y mapear a campo de BD.
        // XSD types vistos: documentoIdentidadType, soporteDocumentoType,
        //                   codigoPostalType, paisType, fechaType, etc.
        if (str_contains($mensaje, 'documentoIdentidadType')) {
            $issues[] = $this->fakeIssue('cliente', $r->cliente_id, 'num_identificacion', $mensaje);
            foreach (Huesped::where('reserva_id', $r->id)->pluck('id') as $hid) {
                $issues[] = $this->fakeIssue('huesped', $hid, 'numero_identificacion', $mensaje);
            }
        }
        if (str_contains($mensaje, 'soporteDocumentoType')) {
            $issues[] = $this->fakeIssue('cliente', $r->cliente_id, 'numero_soporte_documento', $mensaje);
            foreach (Huesped::where('reserva_id', $r->id)->pluck('id') as $hid) {
                $issues[] = $this->fakeIssue('huesped', $hid, 'numero_soporte_documento', $mensaje);
            }
        }
        if (str_contains($mensaje, 'codigoPostalType')) {
            $issues[] = $this->fakeIssue('cliente', $r->cliente_id, 'codigo_postal', $mensaje);
        }

        return $issues;
    }

    private function fakeIssue(string $entidad, $id, string $campo, string $mensaje): array
    {
        return [
            'severity' => 'error',
            'entidad' => $entidad,
            'entidad_id' => $id,
            'campo' => $campo,
            'mensaje' => mb_substr($mensaje, 0, 200),
        ];
    }

    /**
     * Decide y aplica el fallback adecuado para un issue concreto.
     * Devuelve un par [mensaje, hubo_cambio]:
     *  - mensaje string: descripcion (tanto exito como fracaso, para log)
     *  - hubo_cambio bool: true si cambio algo en BD (false si fallback no
     *    encontro nada, no aplica, etc).
     * Si no supo que hacer, devuelve [null, false].
     */
    private function aplicarFallback(
        array $iss,
        Reserva $r,
        OpenAIVisionFallbackService $vision,
        CodigoPostalLookupService $cpLookup,
        bool $dryRun
    ): array {
        $campo = (string) ($iss['campo'] ?? '');
        $entidad = (string) ($iss['entidad'] ?? '');
        $entidadId = $iss['entidad_id'] ?? null;

        $persona = $this->resolverPersona($entidad, $entidadId, $r);
        if (!$persona) return [null, false];

        // ---------------- numero_soporte_documento ----------------
        if ($campo === 'numero_soporte_documento') {
            $foto = $this->localizarFotoFrontal($persona, $entidad, $r);
            if (!$foto) return ["soporte: persona #{$persona->id} sin foto del anverso", false];

            $codigo = $vision->extractNumeroSoporte($foto);
            if (!$codigo) return ["soporte: persona #{$persona->id} no rescatado (foto difícil)", false];

            if (!$dryRun) {
                $persona->numero_soporte_documento = $codigo;
                $persona->save();
            }
            return ["soporte rescatado en {$entidad} #{$persona->id} -> {$codigo}", true];
        }

        // ---------------- numero_identificacion / num_identificacion ----------------
        if (in_array($campo, ['num_identificacion', 'numero_identificacion'], true)) {
            $foto = $this->localizarFotoFrontal($persona, $entidad, $r);
            if (!$foto) return [null, false];

            $tipoDoc = strtolower((string) ($persona->tipo_documento_str ?? ''));
            $tipoLlamada = (str_contains($tipoDoc, 'pas') || str_contains($tipoDoc, 'passport'))
                ? 'pasaporte' : 'auto';

            $dni = $vision->extractNumeroDocumento($foto, 3, $tipoLlamada);
            if (!$dni) return ["doc: persona #{$persona->id} no rescatado", false];

            if (!$dryRun) {
                if ($persona instanceof Cliente) $persona->num_identificacion = $dni;
                else $persona->numero_identificacion = $dni;
                $persona->save();
            }
            return ["documento ({$tipoLlamada}) rescatado en {$entidad} #{$persona->id} -> {$dni}", true];
        }

        // ---------------- codigo_postal ----------------
        if ($campo === 'codigo_postal') {
            $aliasEs = ['ES', 'ESP', 'SPAIN', 'ESPANA', 'ESPAÑA', 'ESPAÑOLA', 'ESPANOLA'];
            $nac = strtoupper(trim((string) ($persona->nacionalidad ?? '')));
            if ($nac !== '' && !in_array($nac, $aliasEs, true)) {
                return [null, false];
            }

            $res = $cpLookup->buscar(
                $persona->direccion,
                $persona->localidad,
                $persona->provincia
            );
            if (!$res || empty($res['codigo_postal'])) {
                return ["cp: persona #{$persona->id} no rescatado", false];
            }

            if (!$dryRun) {
                $persona->codigo_postal = $res['codigo_postal'];
                if (!empty($res['provincia'])
                    && mb_strtolower((string) $persona->provincia) === mb_strtolower((string) $persona->localidad)) {
                    $persona->provincia = $res['provincia'];
                }
                $persona->save();
            }
            return ["cp rescatado en {$entidad} #{$persona->id} -> {$res['codigo_postal']}", true];
        }

        return [null, false];
    }

    /**
     * El issue trae 'entidad'='cliente'|'huesped' y 'entidad_id'=int. Devuelve
     * el modelo correspondiente. Si entidad_id falta (issues antiguos), cae al
     * cliente principal de la reserva.
     */
    private function resolverPersona(string $entidad, $entidadId, Reserva $r)
    {
        if ($entidad === 'cliente') {
            return $entidadId
                ? Cliente::find((int) $entidadId)
                : $r->cliente;
        }
        if ($entidad === 'huesped' || str_starts_with($entidad, 'huesped')) {
            if ($entidadId) return Huesped::find((int) $entidadId);
            // Fallback: primer huesped de la reserva
            return Huesped::where('reserva_id', $r->id)->first();
        }
        return null;
    }

    /**
     * Localiza la foto frontal (categoria 13 o 15 = Pasaporte) asociada a
     * esta persona, primero por id directo y si no por reserva.
     */
    private function localizarFotoFrontal($persona, string $entidad, Reserva $r): ?string
    {
        $categorias = [13, 15]; // 13=Frontal-DNI, 15=Pasaporte
        $q = Photo::whereIn('photo_categoria_id', $categorias);

        if ($entidad === 'cliente') {
            $q->where('cliente_id', $persona->id);
        } else {
            $q->where(function ($qq) use ($persona, $r) {
                $qq->where('huespedes_id', $persona->id)
                    ->orWhere('reserva_id', $r->id);
            });
        }

        $photo = $q->orderBy('id', 'desc')->first();
        return $photo ? $photo->url : null;
    }
}
