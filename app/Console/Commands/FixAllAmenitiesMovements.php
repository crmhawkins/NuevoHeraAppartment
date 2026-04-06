<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Amenity;
use App\Models\AmenityConsumo;
use App\Models\AmenityReposicion;

class FixAllAmenitiesMovements extends Command
{
    protected $signature = 'amenity:fix-all {--dry-run} {--amenity-id=}';
    protected $description = 'Repara movimientos AmenityConsumo de todos los amenities (o uno específico) y recalcula stock';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $amenityId = $this->option('amenity-id');

        if ($amenityId) {
            $amenities = Amenity::where('id', $amenityId)->get();
        } else {
            $amenities = Amenity::all();
        }

        if ($amenities->isEmpty()) {
            $this->error("No se encontraron amenities" . ($amenityId ? " con ID {$amenityId}" : ""));
            return self::FAILURE;
        }

        $this->info("Procesando {$amenities->count()} amenity(ies)...");
        if ($dryRun) {
            $this->warn("Modo DRY-RUN: no se realizarán cambios permanentes");
        }
        $this->newLine();

        $totalCorregidos = 0;
        $amenitiesConErrores = 0;
        $amenitiesProcesados = 0;

        foreach ($amenities as $amenity) {
            $this->line("Procesando: [{$amenity->id}] {$amenity->nombre} (Stock actual: {$amenity->stock_actual})");

            try {
                $corregidos = $this->repararAmenity($amenity, $dryRun);
                $totalCorregidos += $corregidos;

                if ($corregidos > 0) {
                    $amenitiesConErrores++;
                    $this->info("  ✓ Corregidos {$corregidos} movimientos");
                } else {
                    $this->line("  ✓ Sin correcciones necesarias");
                }
                $amenitiesProcesados++;
            } catch (\Exception $e) {
                $this->error("  ✗ Error procesando amenity {$amenity->id}: {$e->getMessage()}");
                $amenitiesConErrores++;
            }
            $this->newLine();
        }

        $this->newLine();
        $this->info("=== RESUMEN ===");
        $this->info("Amenities procesados: {$amenitiesProcesados}");
        $this->info("Amenities con errores corregidos: {$amenitiesConErrores}");
        $this->info("Total movimientos corregidos: {$totalCorregidos}");

        if ($dryRun) {
            $this->warn("Modo DRY-RUN: ningún cambio fue persistido");
        }

        return self::SUCCESS;
    }

    private function repararAmenity(Amenity $amenity, bool $dryRun): int
    {
        // Obtener todos los movimientos ordenados por fecha
        $repos = AmenityReposicion::where('amenity_id', $amenity->id)
            ->orderBy('created_at')
            ->get();
        $cons = AmenityConsumo::where('amenity_id', $amenity->id)
            ->orderBy('created_at')
            ->get();

        // Merge ordenado por fecha
        $movimientos = $repos->concat($cons)->sortBy('created_at')->values();

        if ($movimientos->isEmpty()) {
            return 0;
        }

        // Estimar stock inicial: usar el primer movimiento que tenga stock_anterior/nuevo fiable
        $stock = 0.0;
        foreach ($movimientos as $m) {
            if ($m instanceof AmenityReposicion && $m->stock_anterior !== null) {
                $stock = (float) $m->stock_anterior;
                break;
            }
            if ($m instanceof AmenityConsumo && $m->cantidad_anterior !== null && $m->cantidad_actual !== null) {
                // Buscar un consumo donde la diferencia coincida con cantidad_consumida
                if (abs((float)$m->cantidad_anterior - (float)$m->cantidad_actual - (float)$m->cantidad_consumida) < 0.01) {
                    $stock = (float) $m->cantidad_anterior;
                    break;
                }
            }
        }

        $actualizados = 0;

        DB::beginTransaction();
        try {
            foreach ($movimientos as $m) {
                if ($m instanceof AmenityReposicion) {
                    $esperadoAnterior = $stock;
                    $esperadoNuevo = $stock + (float) $m->cantidad_reponida;

                    if (abs((float)$m->stock_anterior - $esperadoAnterior) > 0.01 || 
                        abs((float)$m->stock_nuevo - $esperadoNuevo) > 0.01) {
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

                    // Verificar si necesita corrección (tolerancia de 0.01 para errores de redondeo)
                    if (abs((float)$m->cantidad_anterior - $esperadoAnterior) > 0.01 || 
                        abs((float)$m->cantidad_actual - $esperadoActual) > 0.01) {
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
                $amenity->update(['stock_actual' => max(0, $stock)]); // No permitir stocks negativos
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return $actualizados;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

