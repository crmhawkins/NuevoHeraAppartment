<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Huesped;
use App\Services\CodigoPostalLookupService;
use Illuminate\Console\Command;

/**
 * [2026-04-26] Rescata el codigo postal de clientes/huespedes que tienen el
 * campo vacio o invalido (no son 5 digitos), usando OpenStreetMap Nominatim
 * sobre la direccion + localidad.
 *
 * Uso:
 *   php artisan cp:rescatar                       -> recorre clientes/huespedes con CP malo
 *   php artisan cp:rescatar --cliente=571
 *   php artisan cp:rescatar --huesped=2622
 *   php artisan cp:rescatar --dry-run --limit=20
 *   php artisan cp:rescatar --solo-clientes
 *   php artisan cp:rescatar --solo-huespedes
 */
class CpRescatar extends Command
{
    protected $signature = 'cp:rescatar
        {--cliente= : id de un cliente concreto}
        {--huesped= : id de un huesped concreto}
        {--solo-clientes : solo procesa clientes}
        {--solo-huespedes : solo procesa huespedes}
        {--limit=20 : numero maximo a procesar}
        {--dry-run : no guarda, solo muestra}
        {--corregir-provincia : si Nominatim devuelve una provincia distinta, sobrescribirla tambien}';

    protected $description = 'Rescata codigos postales invalidos buscando direccion+localidad en Nominatim (OpenStreetMap). Gratis, sin API key.';

    public function handle(CodigoPostalLookupService $svc): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit') ?: 20;
        $corregirProv = (bool) $this->option('corregir-provincia');

        $personas = $this->resolverPersonas($limit);
        if ($personas->isEmpty()) {
            $this->info('No hay personas con CP invalido.');
            return self::SUCCESS;
        }

        $this->info("Procesando " . $personas->count() . " persona(s)...");

        $ok = 0;
        $fail = 0;

        foreach ($personas as $p) {
            $tipo = $p instanceof Cliente ? 'cliente' : 'huesped';
            $nombre = $tipo === 'cliente'
                ? trim(($p->nombre ?? '') . ' ' . ($p->apellido1 ?? ''))
                : trim(($p->nombre ?? '') . ' ' . ($p->primer_apellido ?? ''));

            $cpActual = trim((string) ($p->codigo_postal ?? ''));
            $this->line("--- {$tipo} #{$p->id} ({$nombre}) — cp actual: '{$cpActual}'");
            $this->line("    direccion: " . ($p->direccion ?: '-') . " | localidad: " . ($p->localidad ?: '-') . " | provincia: " . ($p->provincia ?: '-'));

            if (empty($p->direccion) && empty($p->localidad)) {
                $this->warn('    sin direccion ni localidad, salto.');
                $fail++;
                continue;
            }

            $resultado = $svc->buscar($p->direccion, $p->localidad, $p->provincia);

            if (!$resultado) {
                $this->warn('    Nominatim no encontro nada.');
                $fail++;
                continue;
            }

            $this->info("    Nominatim -> cp={$resultado['codigo_postal']} prov={$resultado['provincia']} loc={$resultado['localidad']}");

            if ($dryRun) {
                $this->comment('    [dry-run] no se guarda.');
                $ok++;
                continue;
            }

            $update = ['codigo_postal' => $resultado['codigo_postal']];
            if ($corregirProv && !empty($resultado['provincia'])) {
                $update['provincia'] = $resultado['provincia'];
            }
            $p->update($update);
            $ok++;
        }

        $this->info('');
        $this->info("Resultado: {$ok} ok, {$fail} fallidos.");
        return self::SUCCESS;
    }

    private function resolverPersonas(int $limit)
    {
        $cliId = $this->option('cliente');
        $huesId = $this->option('huesped');
        if ($cliId) return Cliente::where('id', $cliId)->get();
        if ($huesId) return Huesped::where('id', $huesId)->get();

        $soloCli = (bool) $this->option('solo-clientes');
        $soloHue = (bool) $this->option('solo-huespedes');

        // CP malo o vacio
        $where = function ($q) {
            $q->whereNull('codigo_postal')
                ->orWhere('codigo_postal', '')
                ->orWhereRaw("codigo_postal NOT REGEXP '^[0-9]{5}$'");
        };

        // [2026-04-26] Filtro CRITICO: solo procesamos clientes/huespedes con
        // nacionalidad espanola o sin nacionalidad informada. Para extranjeros
        // el MIR (RD 933/2021) NO exige codigo postal, asi que no tiene
        // sentido buscarles uno espanol — ademas riesgo de inventar datos.
        $aliasEs = ['ES', 'ESP', 'SPAIN', 'ESPANA', 'ESPAÑA', 'ESPAÑOLA', 'ESPANOLA'];
        $whereEspanol = function ($q) use ($aliasEs) {
            $q->whereNull('nacionalidad')
                ->orWhere('nacionalidad', '')
                ->orWhereIn('nacionalidad', $aliasEs);
        };

        $clientes = collect();
        $huespedes = collect();

        if (!$soloHue) {
            $clientes = Cliente::where($where)
                ->where($whereEspanol)
                ->whereNotNull('direccion')
                ->where('direccion', '!=', '')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();
        }
        if (!$soloCli) {
            $huespedes = Huesped::where($where)
                ->where($whereEspanol)
                ->whereNotNull('direccion')
                ->where('direccion', '!=', '')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();
        }

        return $clientes->concat($huespedes)->take($limit);
    }
}
