<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Amenity;
use App\Models\AmenityConsumo;
use App\Models\AmenityReposicion;

class FixAmenityConsumosPorReserva extends Command
{
    protected $signature = 'amenity:fix-consumos-por-reserva {--dry-run}';
    protected $description = 'Corrige consumos de amenities tipo "por_reserva" usando consumo_por_reserva en lugar de cantidad_dejada y recalcula stocks';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('🔧 CORRECCIÓN DE CONSUMOS POR RESERVA');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  Modo DRY-RUN: no se realizarán cambios permanentes');
            $this->newLine();
        }

        // Obtener todos los amenities activos tipo "por_reserva"
        $amenities = Amenity::where('activo', true)
            ->where('tipo_consumo', 'por_reserva')
            ->whereNotNull('consumo_por_reserva')
            ->get();

        if ($amenities->isEmpty()) {
            $this->error('No se encontraron amenities tipo "por_reserva" con consumo_por_reserva configurado');
            return self::FAILURE;
        }

        $this->info("Procesando {$amenities->count()} amenity(ies) tipo 'por_reserva'...");
        $this->newLine();

        $totalConsumosCorregidos = 0;
        $totalStockCorregido = 0;
        $amenitiesProcesados = 0;

        foreach ($amenities as $amenity) {
            $this->line("Procesando: {$amenity->nombre} (ID: {$amenity->id})");
            $this->line("  Consumo por reserva configurado: {$amenity->consumo_por_reserva} {$amenity->unidad_medida}");

            $resultado = $this->corregirConsumosAmenity($amenity, $dryRun);
            
            $totalConsumosCorregidos += $resultado['consumos_corregidos'];
            $totalStockCorregido += $resultado['stock_corregido'];
            $amenitiesProcesados++;

            if ($resultado['consumos_corregidos'] > 0) {
                $this->info("  ✅ Corregidos {$resultado['consumos_corregidos']} consumos");
                $this->line("  📊 Stock corregido: {$resultado['stock_anterior']} -> {$resultado['stock_actual']} {$amenity->unidad_medida}");
            } else {
                $this->line("  ✓ Sin correcciones necesarias");
            }
            $this->newLine();
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info("✅ RESUMEN FINAL");
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("Amenities procesados: {$amenitiesProcesados}");
        $this->info("Consumos corregidos: {$totalConsumosCorregidos}");
        $this->info("Stocks corregidos: {$totalStockCorregido}");

        if ($dryRun) {
            $this->warn("\n⚠️  Este fue un DRY-RUN. Ejecuta sin --dry-run para aplicar los cambios.");
        } else {
            $this->info("\n✅ Correcciones aplicadas exitosamente.");
        }

        return self::SUCCESS;
    }

    private function corregirConsumosAmenity(Amenity $amenity, bool $dryRun): array
    {
        $consumoPorReserva = (float) $amenity->consumo_por_reserva;
        $consumosCorregidos = 0;
        $stockAnterior = $amenity->stock_actual;
        $stockActual = $stockAnterior;

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
            return [
                'consumos_corregidos' => 0,
                'stock_corregido' => 0,
                'stock_anterior' => $stockAnterior,
                'stock_actual' => $stockActual
            ];
        }

        // Estimar stock inicial desde la primera reposición
        $stock = 0.0;
        foreach ($movimientos as $m) {
            if ($m instanceof AmenityReposicion && $m->stock_anterior !== null) {
                $stock = (float) $m->stock_anterior;
                break;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($movimientos as $m) {
                if ($m instanceof AmenityReposicion) {
                    // Reposición: sumar al stock
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
                    }
                    $stock = $esperadoNuevo;
                } else { // AmenityConsumo
                    // CORRECCIÓN PRINCIPAL: Usar consumo_por_reserva en lugar de cantidad_consumida
                    $cantidadCorrecta = $consumoPorReserva;
                    $cantidadIncorrecta = (float) $m->cantidad_consumida;

                    // Solo corregir si la cantidad es diferente (con tolerancia de 0.001)
                    if (abs($cantidadIncorrecta - $cantidadCorrecta) > 0.001) {
                        $esperadoAnterior = $stock;
                        $esperadoActual = $stock - $cantidadCorrecta;

                        // Calcular diferencia en stock
                        $diferenciaStock = $cantidadIncorrecta - $cantidadCorrecta;
                        // Si se descontó de más, hay que reponer la diferencia
                        // Si se descontó de menos, hay que descontar la diferencia
                        // Pero como estamos recalculando desde cero, simplemente usamos la cantidad correcta

                        if (!$dryRun) {
                            $m->update([
                                'cantidad_consumida' => $cantidadCorrecta,
                                'cantidad_anterior' => $esperadoAnterior,
                                'cantidad_actual' => $esperadoActual,
                            ]);
                        }

                        $consumosCorregidos++;
                        $this->line("    🔧 Consumo ID {$m->id}: {$cantidadIncorrecta} -> {$cantidadCorrecta} {$amenity->unidad_medida}");
                    } else {
                        // Aunque la cantidad sea correcta, verificar que los stocks anteriores/actuales sean correctos
                        $esperadoAnterior = $stock;
                        $esperadoActual = $stock - $cantidadCorrecta;

                        if (abs((float)$m->cantidad_anterior - $esperadoAnterior) > 0.01 || 
                            abs((float)$m->cantidad_actual - $esperadoActual) > 0.01) {
                            if (!$dryRun) {
                                $m->update([
                                    'cantidad_anterior' => $esperadoAnterior,
                                    'cantidad_actual' => $esperadoActual,
                                ]);
                            }
                            $consumosCorregidos++;
                        }
                    }
                    $stock = $stock - $cantidadCorrecta;
                }
            }

            // Actualizar stock_actual del amenity
            $stockFinal = max(0, $stock); // No permitir stocks negativos
            if (!$dryRun) {
                $amenity->update(['stock_actual' => $stockFinal]);
            }
            $stockActual = $stockFinal;

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  ❌ Error procesando amenity {$amenity->id}: " . $e->getMessage());
            throw $e;
        }

        return [
            'consumos_corregidos' => $consumosCorregidos,
            'stock_corregido' => abs($stockActual - $stockAnterior),
            'stock_anterior' => $stockAnterior,
            'stock_actual' => $stockActual
        ];
    }
}

