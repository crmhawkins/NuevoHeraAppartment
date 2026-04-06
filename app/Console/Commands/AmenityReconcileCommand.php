<?php

namespace App\Console\Commands;

use App\Models\Amenity;
use App\Models\AmenityConsumo;
use App\Models\AmenityReposicion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recalcula el stock de todos los amenities desde cero (reposiciones - consumos)
 * y actualiza stock_actual. Opcionalmente corrige los registros de movimientos
 * (stock_anterior/cantidad_anterior, stock_nuevo/cantidad_actual) para que sean coherentes.
 *
 * Ejecutar después de importaciones o si el stock se desincroniza.
 * Uso: php artisan amenity:reconcile [--fix-movements] [--dry-run]
 */
class AmenityReconcileCommand extends Command
{
    protected $signature = 'amenity:reconcile
                            {--fix-movements : Corregir también stock_anterior/cantidad_anterior en cada movimiento}
                            {--dry-run : Solo mostrar cambios sin aplicarlos}
                            {--amenity-id= : Solo un amenity por ID}';

    protected $description = 'Recalcula stock de amenities desde movimientos (repos - consumos) y opcionalmente corrige registros de movimientos.';

    public function handle(): int
    {
        $fixMovements = (bool) $this->option('fix-movements');
        $dryRun = (bool) $this->option('dry-run');
        $amenityId = $this->option('amenity-id');

        $this->info('Reconciliación de stock de amenities');
        if ($dryRun) {
            $this->warn('Modo DRY-RUN: no se aplicarán cambios.');
        }
        if ($fixMovements) {
            $this->line('Se corregirán también los campos stock_anterior/cantidad_anterior en movimientos.');
        }
        $this->newLine();

        $query = Amenity::query();
        if ($amenityId) {
            $query->where('id', $amenityId);
        }
        $amenities = $query->orderBy('categoria')->orderBy('nombre')->get();

        if ($amenities->isEmpty()) {
            $this->error('No se encontraron amenities.');
            return self::FAILURE;
        }

        $totalAjustes = 0;
        $amenitiesConAjuste = 0;

        foreach ($amenities as $amenity) {
            $result = $this->reconcileAmenity($amenity, $fixMovements, $dryRun);
            if ($result['ajustado']) {
                $amenitiesConAjuste++;
                $totalAjustes++;
                $this->line("[{$amenity->id}] {$amenity->nombre}: stock {$result['stock_anterior']} → {$result['stock_nuevo']} {$amenity->unidad_medida}");
            }
        }

        $this->newLine();
        $this->info("Amenities revisados: {$amenities->count()}");
        $this->info("Amenities con stock ajustado: {$amenitiesConAjuste}");
        if ($dryRun) {
            $this->warn('DRY-RUN: no se guardaron cambios. Ejecuta sin --dry-run para aplicar.');
        } else {
            Log::info('AmenityReconcileCommand: reconciliación completada', [
                'amenities_revisados' => $amenities->count(),
                'amenities_ajustados' => $amenitiesConAjuste,
            ]);
        }

        return self::SUCCESS;
    }

    private function reconcileAmenity(Amenity $amenity, bool $fixMovements, bool $dryRun): array
    {
        $repos = AmenityReposicion::where('amenity_id', $amenity->id)
            ->orderBy('fecha_reposicion', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $consumos = AmenityConsumo::where('amenity_id', $amenity->id)
            ->orderBy('fecha_consumo', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $movimientos = $repos->concat($consumos)->sortBy(function ($m) {
            $fecha = $m instanceof AmenityReposicion
                ? ($m->fecha_reposicion ?? $m->created_at)
                : ($m->fecha_consumo ?? $m->created_at);
            return $fecha ? (is_string($fecha) ? strtotime($fecha) : $fecha->timestamp) : 0;
        })->values();

        if ($movimientos->isEmpty()) {
            return ['ajustado' => false, 'stock_anterior' => $amenity->stock_actual, 'stock_nuevo' => $amenity->stock_actual];
        }

        // Stock inicial: primer movimiento que sea reposición con stock_anterior, o 0
        $stock = 0.0;
        foreach ($movimientos as $m) {
            if ($m instanceof AmenityReposicion && $m->stock_anterior !== null) {
                $stock = (float) $m->stock_anterior;
                break;
            }
            if ($m instanceof AmenityConsumo && $m->cantidad_anterior !== null && $m->cantidad_actual !== null) {
                $diff = (float) $m->cantidad_anterior - (float) $m->cantidad_actual;
                if (abs($diff - (float) $m->cantidad_consumida) < 0.02) {
                    $stock = (float) $m->cantidad_anterior;
                    break;
                }
            }
        }

        $stockAnterior = (float) $amenity->stock_actual;

        DB::beginTransaction();
        try {
            foreach ($movimientos as $m) {
                if ($m instanceof AmenityReposicion) {
                    $esperadoAnterior = $stock;
                    $esperadoNuevo = $stock + (float) $m->cantidad_reponida;
                    if ($fixMovements && (abs((float) $m->stock_anterior - $esperadoAnterior) > 0.01 || abs((float) $m->stock_nuevo - $esperadoNuevo) > 0.01)) {
                        if (!$dryRun) {
                            $m->update(['stock_anterior' => $esperadoAnterior, 'stock_nuevo' => $esperadoNuevo]);
                        }
                    }
                    $stock = $esperadoNuevo;
                } else {
                    $cantidad = (float) $m->cantidad_consumida;
                    $esperadoAnterior = $stock;
                    $esperadoActual = $stock - $cantidad;
                    if ($fixMovements && (abs((float) $m->cantidad_anterior - $esperadoAnterior) > 0.01 || abs((float) $m->cantidad_actual - $esperadoActual) > 0.01)) {
                        if (!$dryRun) {
                            $m->update(['cantidad_anterior' => $esperadoAnterior, 'cantidad_actual' => $esperadoActual]);
                        }
                    }
                    $stock = $esperadoActual;
                }
            }

            $stockNuevo = max(0, round($stock, 2));
            $ajustado = abs($stockAnterior - $stockNuevo) > 0.01;

            if ($ajustado && !$dryRun) {
                $amenity->update(['stock_actual' => $stockNuevo]);
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return [
                'ajustado' => $ajustado,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AmenityReconcileCommand: error en amenity ' . $amenity->id, ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
