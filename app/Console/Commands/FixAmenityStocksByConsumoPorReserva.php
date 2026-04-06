<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Amenity;
use App\Models\AmenityConsumo;
use App\Models\AmenityReposicion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixAmenityStocksByConsumoPorReserva extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amenity:fix-stocks-by-consumo-por-reserva {--dry-run : Solo mostrar lo que se haría sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige los stocks de amenities usando consumo_por_reserva y recalcula el stock desde cero';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 MODO DRY-RUN: Solo se mostrarán los cambios, no se aplicarán');
        } else {
            $this->info('🔧 MODO EJECUCIÓN: Se aplicarán los cambios');
        }
        
        $this->newLine();
        
        // Obtener todos los amenities con tipo_consumo = 'por_reserva'
        $amenities = Amenity::where('tipo_consumo', 'por_reserva')
            ->where('activo', true)
            ->get();
        
        $this->info("📦 Amenities tipo 'por_reserva' encontrados: " . $amenities->count());
        $this->newLine();
        
        $totalCorregidos = 0;
        $totalAjustesStock = 0;
        $amenitiesConProblemas = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($amenities as $amenity) {
                $consumoEsperado = $amenity->consumo_por_reserva ?? 0;
                
                if ($consumoEsperado <= 0) {
                    continue; // Saltar si no tiene consumo_por_reserva configurado
                }
                
                // 1. Obtener todos los consumos de este amenity
                $consumos = AmenityConsumo::where('amenity_id', $amenity->id)
                    ->orderBy('fecha_consumo', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                $consumosIncorrectos = [];
                $ajusteTotalStock = 0;
                
                foreach ($consumos as $consumo) {
                    $cantidadConsumida = $consumo->cantidad_consumida;
                    
                    // Si la cantidad consumida es diferente a la esperada, hay que corregir
                    if (abs($cantidadConsumida - $consumoEsperado) > 0.001) {
                        $diferencia = $cantidadConsumida - $consumoEsperado;
                        $consumosIncorrectos[] = [
                            'consumo' => $consumo,
                            'cantidad_actual' => $cantidadConsumida,
                            'cantidad_esperada' => $consumoEsperado,
                            'diferencia' => $diferencia
                        ];
                        
                        // Acumular el ajuste de stock (positivo = se descontó más, negativo = se descontó menos)
                        $ajusteTotalStock += $diferencia;
                    }
                }
                
                // 2. Recalcular stock desde cero basándose en reposiciones - consumos corregidos
                $totalReposiciones = AmenityReposicion::where('amenity_id', $amenity->id)
                    ->sum('cantidad_reponida');
                
                $totalConsumosCorregidos = $consumos->sum(function($c) use ($consumoEsperado) {
                    // Usar consumo_por_reserva si el consumo está incorrecto
                    if (abs($c->cantidad_consumida - $consumoEsperado) > 0.001) {
                        return $consumoEsperado;
                    }
                    return $c->cantidad_consumida;
                });
                
                $stockCalculado = $totalReposiciones - $totalConsumosCorregidos;
                $diferenciaStock = $amenity->stock_actual - $stockCalculado;
                
                if (count($consumosIncorrectos) > 0 || abs($diferenciaStock) > 0.001) {
                    $amenitiesConProblemas[] = [
                        'amenity' => $amenity,
                        'consumos_incorrectos' => $consumosIncorrectos,
                        'ajuste_total' => $ajusteTotalStock,
                        'stock_actual' => $amenity->stock_actual,
                        'stock_calculado' => $stockCalculado,
                        'diferencia_stock' => $diferenciaStock
                    ];
                    
                    $this->warn("⚠️  {$amenity->nombre} (ID: {$amenity->id})");
                    $this->line("   Consumo por reserva configurado: {$consumoEsperado} {$amenity->unidad_medida}");
                    
                    if (count($consumosIncorrectos) > 0) {
                        $this->line("   Consumos incorrectos: " . count($consumosIncorrectos));
                        $this->line("   Ajuste total de stock necesario: " . number_format($ajusteTotalStock, 2) . " {$amenity->unidad_medida}");
                    }
                    
                    $this->line("   Stock actual: " . number_format($amenity->stock_actual, 2) . " {$amenity->unidad_medida}");
                    $this->line("   Stock calculado (reposiciones - consumos corregidos): " . number_format($stockCalculado, 2) . " {$amenity->unidad_medida}");
                    $this->line("   Diferencia: " . number_format($diferenciaStock, 2) . " {$amenity->unidad_medida}");
                    
                    if (!$dryRun) {
                        // 1. Corregir cada consumo incorrecto
                        foreach ($consumosIncorrectos as $item) {
                            $consumo = $item['consumo'];
                            $cantidadAnterior = $consumo->cantidad_consumida;
                            $cantidadCorrecta = $item['cantidad_esperada'];
                            
                            // Actualizar el consumo con la cantidad correcta
                            $consumo->update([
                                'cantidad_consumida' => $cantidadCorrecta,
                                'cantidad_anterior' => $consumo->cantidad_anterior, // Mantener el histórico
                                // Recalcular cantidad_actual basándose en el stock anterior y la cantidad correcta
                                'cantidad_actual' => $consumo->cantidad_anterior - $cantidadCorrecta
                            ]);
                            
                            $totalCorregidos++;
                            
                            Log::info("Consumo corregido", [
                                'amenity_id' => $amenity->id,
                                'consumo_id' => $consumo->id,
                                'cantidad_anterior' => $cantidadAnterior,
                                'cantidad_correcta' => $cantidadCorrecta,
                                'diferencia' => $item['diferencia']
                            ]);
                        }
                        
                        // 2. Actualizar el stock al valor calculado
                        $stockAnterior = $amenity->stock_actual;
                        $amenity->stock_actual = max(0, $stockCalculado); // No permitir stock negativo
                        $amenity->save();
                        
                        $this->line("   ✅ Stock actualizado: {$stockAnterior} -> {$amenity->stock_actual}");
                        $totalAjustesStock += abs($diferenciaStock);
                        
                        Log::info("Stock recalculado", [
                            'amenity_id' => $amenity->id,
                            'stock_anterior' => $stockAnterior,
                            'stock_actual' => $amenity->stock_actual,
                            'stock_calculado' => $stockCalculado,
                            'total_reposiciones' => $totalReposiciones,
                            'total_consumos_corregidos' => $totalConsumosCorregidos
                        ]);
                    }
                    
                    $this->newLine();
                }
            }
            
            if (!$dryRun) {
                DB::commit();
                $this->info("✅ Transacción completada exitosamente");
            } else {
                DB::rollBack();
                $this->info("🔍 Modo dry-run: no se aplicaron cambios");
            }
            
            $this->newLine();
            $this->info("📊 RESUMEN:");
            $this->line("   Amenities con problemas: " . count($amenitiesConProblemas));
            $this->line("   Consumos corregidos: " . $totalCorregidos);
            $this->line("   Ajustes de stock realizados: " . number_format($totalAjustesStock, 2));
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            Log::error("Error en fix-stocks-by-consumo-por-reserva: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
