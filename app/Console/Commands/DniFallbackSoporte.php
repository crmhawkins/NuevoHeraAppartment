<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Huesped;
use App\Models\Photo;
use App\Services\OpenAIVisionFallbackService;
use Illuminate\Console\Command;

/**
 * [2026-04-26] Reprocesa DNIs ya almacenados llamando al fallback de
 * OpenAI Vision para extraer el numero_soporte_documento que el modelo
 * local qwen3-vl:8b dejo vacio o mal.
 *
 * Uso:
 *   php artisan dni:fallback-soporte                     -> recorre todos los pendientes
 *   php artisan dni:fallback-soporte --huesped=2621      -> solo ese huesped
 *   php artisan dni:fallback-soporte --cliente=5938      -> solo ese cliente
 *   php artisan dni:fallback-soporte --dry-run           -> no guarda, solo loguea
 *   php artisan dni:fallback-soporte --limit=5
 */
class DniFallbackSoporte extends Command
{
    protected $signature = 'dni:fallback-soporte
        {--huesped= : id de un huesped concreto a reprocesar}
        {--cliente= : id de un cliente concreto a reprocesar}
        {--limit=10 : limite de personas a procesar}
        {--dry-run : no guarda el resultado en BD}';

    protected $description = 'Reprocesa el numero_soporte_documento de DNIs ya escaneados, usando OpenAI Vision como fallback al modelo local';

    private const REGEX_DNI = '/^[A-Z]{3}\d{6}$/';
    private const REGEX_PERMISIVO = '/^[A-Z]{1,3}\d{6,8}$/';

    public function handle(OpenAIVisionFallbackService $fallback): int
    {
        $huespedId = $this->option('huesped');
        $clienteId = $this->option('cliente');
        $limit = (int) $this->option('limit') ?: 10;
        $dryRun = (bool) $this->option('dry-run');

        $personas = $this->resolverPersonas($huespedId, $clienteId, $limit);
        if ($personas->isEmpty()) {
            $this->info('No hay personas con numero_soporte_documento vacio o invalido.');
            return self::SUCCESS;
        }

        $this->info("Procesando " . $personas->count() . " persona(s)...");

        $okCount = 0;
        $failCount = 0;

        foreach ($personas as $p) {
            $tipo = $p instanceof Huesped ? 'huesped' : 'cliente';
            $nombre = $tipo === 'huesped'
                ? trim(($p->nombre ?? '') . ' ' . ($p->primer_apellido ?? ''))
                : trim(($p->nombre ?? '') . ' ' . ($p->apellido1 ?? ''));

            $this->line("--- {$tipo} #{$p->id} ({$nombre}) — actual: '" . ($p->numero_soporte_documento ?? '-') . "'");

            $foto = $this->localizarFotoAnverso($p, $tipo);
            if (!$foto) {
                $this->warn("    sin foto del anverso, salto.");
                $failCount++;
                continue;
            }

            $this->line("    foto: {$foto}");

            $codigo = $fallback->extractNumeroSoporte($foto);

            if (!$codigo) {
                $this->warn("    OpenAI no devolvio codigo valido.");
                $failCount++;
                continue;
            }

            $this->info("    OpenAI -> {$codigo}");

            if ($dryRun) {
                $this->comment("    [dry-run] no se guarda.");
                $okCount++;
                continue;
            }

            $p->numero_soporte_documento = $codigo;
            $p->save();
            $okCount++;
        }

        $this->info("");
        $this->info("Resultado: {$okCount} ok, {$failCount} fallidos.");
        return self::SUCCESS;
    }

    /**
     * Construye la coleccion de personas a procesar.
     */
    private function resolverPersonas($huespedId, $clienteId, int $limit)
    {
        if ($huespedId) {
            return Huesped::where('id', $huespedId)->get();
        }
        if ($clienteId) {
            return Cliente::where('id', $clienteId)->get();
        }

        // Por defecto: huespedes con numero_soporte invalido o vacio.
        $hs = Huesped::where(function ($q) {
            $q->whereNull('numero_soporte_documento')
                ->orWhere('numero_soporte_documento', '')
                ->orWhereRaw("numero_soporte_documento NOT REGEXP '^[A-Z]{1,3}[0-9]{6,8}$'");
        })
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        return $hs;
    }

    /**
     * Devuelve la ruta (relativa de Storage o absoluta) de la foto del anverso
     * del DNI para esta persona. Categoria 13 = Frontal-DNI.
     */
    private function localizarFotoAnverso($persona, string $tipo): ?string
    {
        $reservaId = $tipo === 'huesped' ? $persona->reserva_id : null;

        $q = Photo::where('photo_categoria_id', 13);

        if ($tipo === 'huesped') {
            $q->where(function ($qq) use ($persona, $reservaId) {
                $qq->where('huespedes_id', $persona->id);
                if ($reservaId) {
                    $qq->orWhere('reserva_id', $reservaId);
                }
            });
        } else {
            $q->where('cliente_id', $persona->id);
        }

        $photo = $q->orderBy('id', 'desc')->first();
        if (!$photo) return null;

        return $photo->url;
    }
}
