<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Amenity;
use App\Models\AmenityConsumo;
use App\Models\AmenityReposicion;
use App\Models\ApartamentoLimpieza;

class CheckAmenityStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amenity:check-stock {--amenity-id= : ID específico del amenity a comprobar} {--all : Comprobar todos los amenities, incluso inactivos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprueba el stock de amenities sin hacer cambios. Muestra stock actual vs calculado y detecta inconsistencias.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $amenityId = $this->option('amenity-id');
        $all = $this->option('all');

        $this->info('🔍 COMPROBACIÓN DE STOCK DE AMENITIES');
        $this->line('Este comando solo comprueba el stock, NO realiza cambios.');
        $this->newLine();

        // Obtener amenities a comprobar
        $query = Amenity::query();
        
        if ($amenityId) {
            $query->where('id', $amenityId);
        } elseif (!$all) {
            $query->where('activo', true);
        }

        $amenities = $query->orderBy('categoria')->orderBy('nombre')->get();

        if ($amenities->isEmpty()) {
            $this->error('No se encontraron amenities para comprobar.');
            return self::FAILURE;
        }

        $this->info("📦 Amenities a comprobar: {$amenities->count()}");
        $this->newLine();

        $amenitiesConProblemas = [];
        $amenitiesConStockBajo = [];
        $amenitiesCorrectos = 0;
        $totalInconsistencias = 0;

        foreach ($amenities as $amenity) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("📦 [{$amenity->id}] {$amenity->nombre}");
            $this->line("   Categoría: {$amenity->categoria}");
            $this->line("   Tipo consumo: {$amenity->tipo_consumo}");
            $this->line("   Unidad: {$amenity->unidad_medida}");
            $this->line("   Activo: " . ($amenity->activo ? 'Sí' : 'No'));
            $this->newLine();

            // Stock actual en BD
            $stockActual = (float) $amenity->stock_actual;
            $this->line("   📊 Stock actual (BD): " . number_format($stockActual, 2) . " {$amenity->unidad_medida}");

            // Obtener primera reposición
            $primeraReposicion = AmenityReposicion::where('amenity_id', $amenity->id)
                ->orderBy('fecha_reposicion', 'asc')
                ->orderBy('created_at', 'asc')
                ->first();
            
            if ($primeraReposicion) {
                $fechaPrimeraRepo = $primeraReposicion->fecha_reposicion ?? $primeraReposicion->created_at;
                $this->line("   📅 Primera reposición: " . ($fechaPrimeraRepo instanceof \Carbon\Carbon ? $fechaPrimeraRepo->format('d/m/Y') : $fechaPrimeraRepo));
            } else {
                $this->warn("   ⚠️  No hay reposiciones registradas para este amenity");
            }

            // Calcular stock desde cero (suma simple)
            $totalReposiciones = (float) AmenityReposicion::where('amenity_id', $amenity->id)
                ->sum('cantidad_reponida');

            $totalConsumos = (float) AmenityConsumo::where('amenity_id', $amenity->id)
                ->sum('cantidad_consumida');

            $stockCalculado = $totalReposiciones - $totalConsumos;

            $this->line("   ➕ Total reposiciones: " . number_format($totalReposiciones, 2) . " {$amenity->unidad_medida}");
            $this->line("   ➖ Total consumos: " . number_format($totalConsumos, 2) . " {$amenity->unidad_medida}");
            $this->line("   🧮 Stock calculado (suma simple): " . number_format($stockCalculado, 2) . " {$amenity->unidad_medida}");

            // Calcular stock de forma cronológica (más preciso)
            $reposiciones = AmenityReposicion::where('amenity_id', $amenity->id)
                ->orderBy('fecha_reposicion', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();
            
            $consumos = AmenityConsumo::where('amenity_id', $amenity->id)
                ->orderBy('fecha_consumo', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            // Merge y ordenar por fecha
            $movimientos = collect();
            foreach ($reposiciones as $repo) {
                $movimientos->push([
                    'tipo' => 'reposicion',
                    'fecha' => $repo->fecha_reposicion ?? $repo->created_at,
                    'cantidad' => (float) $repo->cantidad_reponida,
                    'stock_anterior' => $repo->stock_anterior,
                    'stock_nuevo' => $repo->stock_nuevo,
                ]);
            }
            foreach ($consumos as $consumo) {
                $movimientos->push([
                    'tipo' => 'consumo',
                    'fecha' => $consumo->fecha_consumo ?? $consumo->created_at,
                    'cantidad' => (float) $consumo->cantidad_consumida,
                    'stock_anterior' => $consumo->cantidad_anterior,
                    'stock_actual' => $consumo->cantidad_actual,
                ]);
            }
            
            $movimientos = $movimientos->sortBy('fecha')->values();
            
            // Calcular stock cronológicamente
            $stockCronologico = 0.0;
            if ($movimientos->isNotEmpty()) {
                $primerMovimiento = $movimientos->first();
                // Intentar obtener el stock inicial del primer movimiento
                if (isset($primerMovimiento['stock_anterior']) && $primerMovimiento['stock_anterior'] !== null) {
                    $stockCronologico = (float) $primerMovimiento['stock_anterior'];
                    // Si es reposición, ya incluye el stock anterior, así que no sumamos la cantidad
                    if ($primerMovimiento['tipo'] === 'reposicion') {
                        // El stock nuevo ya incluye la reposición, así que usamos stock_nuevo
                        if (isset($primerMovimiento['stock_nuevo']) && $primerMovimiento['stock_nuevo'] !== null) {
                            $stockCronologico = (float) $primerMovimiento['stock_nuevo'];
                        } else {
                            $stockCronologico += (float) $primerMovimiento['cantidad'];
                        }
                    } else {
                        // Si es consumo, el stock actual ya refleja el descuento
                        if (isset($primerMovimiento['stock_actual']) && $primerMovimiento['stock_actual'] !== null) {
                            $stockCronologico = (float) $primerMovimiento['stock_actual'];
                        } else {
                            $stockCronologico -= (float) $primerMovimiento['cantidad'];
                        }
                    }
                    // Procesar el resto de movimientos
                    foreach ($movimientos->skip(1) as $mov) {
                        if ($mov['tipo'] === 'reposicion') {
                            if (isset($mov['stock_nuevo']) && $mov['stock_nuevo'] !== null) {
                                $stockCronologico = (float) $mov['stock_nuevo'];
                            } else {
                                $stockCronologico += $mov['cantidad'];
                            }
                        } else {
                            if (isset($mov['stock_actual']) && $mov['stock_actual'] !== null) {
                                $stockCronologico = (float) $mov['stock_actual'];
                            } else {
                                $stockCronologico -= $mov['cantidad'];
                            }
                        }
                    }
                } else {
                    // Si no hay stock anterior, calcular desde cero
                    foreach ($movimientos as $mov) {
                        if ($mov['tipo'] === 'reposicion') {
                            $stockCronologico += $mov['cantidad'];
                        } else {
                            $stockCronologico -= $mov['cantidad'];
                        }
                    }
                }
            }
            
            $this->line("   🧮 Stock calculado (cronológico): " . number_format($stockCronologico, 2) . " {$amenity->unidad_medida}");

            // Verificar limpiezas sin consumos registrados para este amenity
            $fechaInicio = null;
            if ($primeraReposicion) {
                $fechaInicio = $primeraReposicion->fecha_reposicion ?? $primeraReposicion->created_at;
            } elseif ($movimientos->isNotEmpty()) {
                $fechaInicio = $movimientos->first()['fecha'];
            }

            $limpiezasSinConsumos = ApartamentoLimpieza::whereNotNull('fecha_fin')
                ->whereDoesntHave('amenitiesConsumidos', function($query) use ($amenity) {
                    $query->where('amenity_id', $amenity->id);
                })
                ->when($fechaInicio, function($query) use ($fechaInicio) {
                    $fecha = $fechaInicio instanceof \Carbon\Carbon ? $fechaInicio : \Carbon\Carbon::parse($fechaInicio);
                    return $query->where('fecha_fin', '>=', $fecha->format('Y-m-d'));
                })
                ->count();

            if ($limpiezasSinConsumos > 0) {
                $fechaFrom = $fechaInicio instanceof \Carbon\Carbon 
                    ? $fechaInicio->format('Y-m-d') 
                    : ($fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->format('Y-m-d') : '2025-09-15');
                $this->warn("   ⚠️  {$limpiezasSinConsumos} limpieza(s) sin consumos registrados para este amenity");
                $this->comment("   💡 Ejecuta: php artisan amenity:backfill-consumos --from={$fechaFrom} --to=" . now()->format('Y-m-d'));
            }

            // Usar el stock cronológico para la comparación
            $stockCalculado = $stockCronologico;

            // Si el stock calculado es negativo, significa que hay más consumos que reposiciones
            // Esto puede indicar que falta registrar un stock inicial
            $stockInicialNecesario = 0;
            if ($stockCalculado < 0) {
                $stockInicialNecesario = abs($stockCalculado);
                $this->comment("   ℹ️  Stock calculado negativo: posible stock inicial no registrado de " . number_format($stockInicialNecesario, 2) . " {$amenity->unidad_medida}");
            }

            // Comparar stock actual vs calculado
            // Si el stock calculado es negativo y el actual es 0, es consistente (el sistema no permite stocks negativos)
            $diferencia = abs($stockActual - max(0, $stockCalculado));
            $tolerancia = 0.01; // Tolerancia para comparaciones de float

            if ($diferencia > $tolerancia) {
                // Si el stock calculado es negativo pero el actual es 0, no es una inconsistencia real
                if ($stockCalculado < 0 && $stockActual == 0) {
                    $this->info("   ✅ Stock consistente (stock negativo calculado ajustado a 0)");
                    $amenitiesCorrectos++;
                } else {
                    $this->warn("   ⚠️  DIFERENCIA DETECTADA: " . number_format($diferencia, 2) . " {$amenity->unidad_medida}");
                    $amenitiesConProblemas[] = [
                        'amenity' => $amenity,
                        'stock_actual' => $stockActual,
                        'stock_calculado' => $stockCalculado,
                        'diferencia' => $diferencia,
                        'stock_inicial_necesario' => $stockInicialNecesario
                    ];
                    $totalInconsistencias++;
                }
            } else {
                $this->info("   ✅ Stock consistente");
                $amenitiesCorrectos++;
            }

            // Verificar stock mínimo
            if ($amenity->stock_minimo !== null) {
                $stockMinimo = (float) $amenity->stock_minimo;
                if ($stockActual < $stockMinimo) {
                    $this->warn("   🔴 STOCK BAJO: Actual ({$stockActual}) < Mínimo ({$stockMinimo})");
                    $amenitiesConStockBajo[] = [
                        'amenity' => $amenity,
                        'stock_actual' => $stockActual,
                        'stock_minimo' => $stockMinimo
                    ];
                } elseif ($stockActual <= ($stockMinimo * 1.2)) {
                    $this->comment("   🟡 Stock cerca del mínimo: {$stockActual} (mínimo: {$stockMinimo})");
                }
            }

            // Para amenities tipo "por_reserva", verificar consumo_por_reserva
            if ($amenity->tipo_consumo === 'por_reserva' && $amenity->consumo_por_reserva) {
                $consumoEsperado = (float) $amenity->consumo_por_reserva;
                $consumos = AmenityConsumo::where('amenity_id', $amenity->id)->get();
                $consumosIncorrectos = 0;

                foreach ($consumos as $consumo) {
                    if (abs((float) $consumo->cantidad_consumida - $consumoEsperado) > 0.001) {
                        $consumosIncorrectos++;
                    }
                }

                if ($consumosIncorrectos > 0) {
                    $this->warn("   ⚠️  {$consumosIncorrectos} consumos no usan consumo_por_reserva ({$consumoEsperado})");
                } else {
                    $this->info("   ✅ Todos los consumos usan consumo_por_reserva correctamente");
                }
            }

            $this->newLine();
        }

        // Resumen final
        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 RESUMEN DE COMPROBACIÓN");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   ✅ Amenities con stock correcto: {$amenitiesCorrectos}");
        $this->line("   ⚠️  Amenities con inconsistencias: " . count($amenitiesConProblemas));
        $this->line("   🔴 Amenities con stock bajo: " . count($amenitiesConStockBajo));
        $this->newLine();

        if (count($amenitiesConProblemas) > 0) {
            $this->warn("⚠️  AMENITIES CON INCONSISTENCIAS:");
            foreach ($amenitiesConProblemas as $problema) {
                $a = $problema['amenity'];
                $this->line("   • [{$a->id}] {$a->nombre}");
                $this->line("     Stock actual: " . number_format($problema['stock_actual'], 2));
                $this->line("     Stock calculado: " . number_format($problema['stock_calculado'], 2));
                $this->line("     Diferencia: " . number_format($problema['diferencia'], 2) . " {$a->unidad_medida}");
                if (isset($problema['stock_inicial_necesario']) && $problema['stock_inicial_necesario'] > 0) {
                    $this->line("     💡 Stock inicial no registrado estimado: " . number_format($problema['stock_inicial_necesario'], 2) . " {$a->unidad_medida}");
                }
            }
            $this->newLine();
        }

        if (count($amenitiesConStockBajo) > 0) {
            $this->error("🔴 AMENITIES CON STOCK BAJO:");
            $mensajeWhatsApp = "";
            foreach ($amenitiesConStockBajo as $bajo) {
                $a = $bajo['amenity'];
                $this->line("   • [{$a->id}] {$a->nombre}");
                $this->line("     Stock actual: " . number_format($bajo['stock_actual'], 2) . " {$a->unidad_medida}");
                $this->line("     Stock mínimo: " . number_format($bajo['stock_minimo'], 2) . " {$a->unidad_medida}");
                $mensajeWhatsApp .= "- {$a->nombre}: " . number_format($bajo['stock_actual'], 2) . " / " . number_format($bajo['stock_minimo'], 2) . " {$a->unidad_medida}\n";
            }
            $this->newLine();

            // Enviar alerta WhatsApp con resumen de todos los amenities con stock bajo
            try {
                \App\Services\AlertaEquipoService::alertar(
                    'STOCK BAJO - ' . count($amenitiesConStockBajo) . ' AMENITIES',
                    "Amenities con stock por debajo del mínimo:\n" . $mensajeWhatsApp,
                    'stock_bajo'
                );
                $this->info("📲 Alerta WhatsApp enviada al equipo de gestión.");
            } catch (\Exception $e) {
                $this->warn("⚠️  Error al enviar alerta WhatsApp: " . $e->getMessage());
            }
        }

        if (count($amenitiesConProblemas) === 0 && count($amenitiesConStockBajo) === 0) {
            $this->info("✅ ¡Todos los amenities tienen stock correcto!");
        }

        return self::SUCCESS;
    }
}
