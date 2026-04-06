<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Amenity;
use App\Models\AmenityConsumo;
use App\Models\AmenityReposicion;

class FixAmenityMovements extends Command
{
    protected $signature = 'amenity:fix-movements {amenity_id} {--dry-run}';
    protected $description = 'Repara movimientos AmenityConsumo con cantidades mal grabadas y recalcula stock';

    public function handle(): int
    {
        $amenityId = (int) $this->argument('amenity_id');
        $dryRun = (bool) $this->option('dry-run');

        $amenity = Amenity::find($amenityId);
        if (!$amenity) {
            $this->error("Amenity {$amenityId} no encontrado");
            return self::FAILURE;
        }

        $this->info("Amenity: {$amenity->id} - {$amenity->nombre}");

        // Tomar todos los movimientos ordenados por fecha
        $repos = AmenityReposicion::where('amenity_id', $amenityId)
            ->orderBy('created_at')
            ->get();
        $cons = AmenityConsumo::where('amenity_id', $amenityId)
            ->orderBy('created_at')
            ->get();

        // Merge ordenado por fecha
        $movimientos = $repos->concat($cons)->sortBy('created_at')->values();

        // Estimar stock inicial: usar el primer movimiento que tenga stock_anterior/nuevo fiable
        $stock = 0.0;
        foreach ($movimientos as $m) {
            if ($m instanceof AmenityReposicion && $m->stock_anterior !== null) {
                $stock = (float) $m->stock_anterior;
                break;
            }
            if ($m instanceof AmenityConsumo && $m->cantidad_anterior !== null && $m->cantidad_actual !== null) {
                $stock = (float) $m->cantidad_anterior; // mejor que cero si existe
                break;
            }
        }

        $this->info("Stock inicial estimado: {$stock}");

        $actualizados = 0;

        DB::beginTransaction();
        try {
            foreach ($movimientos as $m) {
                if ($m instanceof AmenityReposicion) {
                    $esperadoAnterior = $stock;
                    $esperadoNuevo = $stock + (float) $m->cantidad_reponida;

                    if ((float)$m->stock_anterior !== (float)$esperadoAnterior || (float)$m->stock_nuevo !== (float)$esperadoNuevo) {
                        $this->line("Repo {$m->id} corrigiendo {$m->stock_anterior}->{$m->stock_nuevo} a {$esperadoAnterior}->{$esperadoNuevo}");
                        if (!$dryRun) {
                            $m->update([
                                'stock_anterior' => $esperadoAnterior,
                                'stock_nuevo' => $esperadoNuevo,
                            ]);
                        }
                        $actualizados++;
                    }
                    $stock = $esperadoNuevo;
                } else { // AmenityConsumo
                    $cantidad = (float) $m->cantidad_consumida;
                    $esperadoAnterior = $stock;
                    $esperadoActual = $stock - $cantidad;

                    if ((float)$m->cantidad_anterior !== (float)$esperadoAnterior || (float)$m->cantidad_actual !== (float)$esperadoActual) {
                        $this->line("Cons {$m->id} corrigiendo {$m->cantidad_anterior}->{$m->cantidad_actual} a {$esperadoAnterior}->{$esperadoActual}");
                        if (!$dryRun) {
                            $m->update([
                                'cantidad_anterior' => $esperadoAnterior,
                                'cantidad_actual' => $esperadoActual,
                            ]);
                        }
                        $actualizados++;
                    }
                    $stock = $esperadoActual;
                }
            }

            // Actualizar stock_actual del amenity al último stock calculado
            if (!$dryRun) {
                $this->line("Actualizando stock_actual de {$amenity->stock_actual} a {$stock}");
                $amenity->update(['stock_actual' => $stock]);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn("Dry-run: sin cambios persistidos.");
            } else {
                DB::commit();
            }

            $this->info("Movimientos revisados: {$movimientos->count()} | Registros corregidos: {$actualizados}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error corrigiendo movimientos: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}




