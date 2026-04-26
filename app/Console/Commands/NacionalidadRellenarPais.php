<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Huesped;
use Illuminate\Console\Command;

/**
 * [2026-04-26] Rellena el campo `nacionalidad` (que es el que usa el validador
 * MIR para decidir si exigir CP espanol o no) cuando lo dejamos vacio.
 *
 * Origen del problema:
 *  - El OCR de DNI extrae varios campos de nacionalidad: `nacionalidad` y
 *    `nacionalidadStr`. La logica antigua solo rellenaba `nacionalidadStr`
 *    con texto libre tipo "MAROCAINE" / "FRANCAISE", dejando `nacionalidad`
 *    vacia.
 *  - El MirDataValidator hace:
 *      $pais = $persona->pais ?? ($persona->nacionalidad ?? ($persona->pais_iso3 ?? ''));
 *    Como `pais` no existe en huesped y `nacionalidad` esta vacia, asume Espana.
 *  - Resultado: huespedes claramente extranjeros (pasaporte marroqui, frances, etc)
 *    salen rechazados por "falta CP espanol" cuando MIR no se lo pide.
 *
 * Reglas aplicadas por este comando:
 *  - Si `nacionalidad` ya esta rellena -> nada.
 *  - Si `tipo_documento` es DNI (codigo D / 1) -> nacionalidad = ESP.
 *  - Si hay `nacionalidadStr` (texto libre) -> copiar a `nacionalidad`.
 *  - El validador MIR solo necesita saber si es Espana o no, asi que cualquier
 *    otro string ("MAROCAINE", "FR", etc) cuenta como extranjero.
 *
 * Uso:
 *   php artisan nacionalidad:rellenar-pais                    -> dry-run por defecto
 *   php artisan nacionalidad:rellenar-pais --apply            -> guarda cambios
 *   php artisan nacionalidad:rellenar-pais --cliente=5931
 *   php artisan nacionalidad:rellenar-pais --huesped=2622
 */
class NacionalidadRellenarPais extends Command
{
    protected $signature = 'nacionalidad:rellenar-pais
        {--apply : aplica los cambios a BD (sin esto solo lista)}
        {--cliente= : id de un cliente concreto}
        {--huesped= : id de un huesped concreto}
        {--limit=200 : limite por tabla}';

    protected $description = 'Rellena nacionalidad vacia a partir de tipo_documento (DNI=ESP) o nacionalidadStr (pasaporte). Necesario para que el validador MIR no exija CP espanol a extranjeros.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = (int) $this->option('limit') ?: 200;

        $cliId = $this->option('cliente');
        $huesId = $this->option('huesped');

        if ($cliId) {
            $clientes = Cliente::where('id', $cliId)->get();
            $huespedes = collect();
        } elseif ($huesId) {
            $clientes = collect();
            $huespedes = Huesped::where('id', $huesId)->get();
        } else {
            $clientes = Cliente::where(function ($q) {
                $q->whereNull('nacionalidad')->orWhere('nacionalidad', '');
            })->orderBy('id', 'desc')->limit($limit)->get();

            $huespedes = Huesped::where(function ($q) {
                $q->whereNull('nacionalidad')->orWhere('nacionalidad', '');
            })->orderBy('id', 'desc')->limit($limit)->get();
        }

        $this->info("Clientes: " . $clientes->count() . " | Huespedes: " . $huespedes->count());
        $this->info($apply ? '== APLICANDO ==' : '== DRY-RUN ==');

        $ok = 0;
        $skipped = 0;

        foreach ([['cliente', $clientes], ['huesped', $huespedes]] as [$tipo, $coll]) {
            foreach ($coll as $p) {
                $tipoDoc = (string) ($p->tipo_documento_str ?? '');
                $nacStr = trim((string) ($p->nacionalidadStr ?? ''));

                $nuevoValor = null;
                $razon = '';

                if (stripos($tipoDoc, 'dni') !== false) {
                    $nuevoValor = 'ESP';
                    $razon = 'tipo_documento=DNI';
                } elseif ($nacStr !== '') {
                    $nuevoValor = $nacStr;
                    $razon = 'nacionalidadStr="' . $nacStr . '"';
                }

                if ($nuevoValor === null) {
                    $skipped++;
                    continue;
                }

                $nombre = $tipo === 'cliente'
                    ? trim(($p->nombre ?? '') . ' ' . ($p->apellido1 ?? ''))
                    : trim(($p->nombre ?? '') . ' ' . ($p->primer_apellido ?? ''));

                $this->line("  {$tipo} #{$p->id} ({$nombre}) -> nacionalidad='{$nuevoValor}' [{$razon}]");

                if ($apply) {
                    $p->nacionalidad = $nuevoValor;
                    $p->save();
                }
                $ok++;
            }
        }

        $this->info('');
        $this->info("Resultado: {$ok} actualizados, {$skipped} sin datos para deducir.");
        if (!$apply) $this->comment('Vuelve a ejecutar con --apply para guardar.');
        return self::SUCCESS;
    }
}
