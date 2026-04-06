<?php

namespace App\Console\Commands;

use App\Models\ConfiguracionDescuento;
use App\Models\Apartamento;
use App\Models\Reserva;
use App\Models\HistorialDescuento;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AplicarDescuentosChannex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aplicar:descuentos-channex 
                            {--fecha= : Fecha de análisis (YYYY-MM-DD)}
                            {--configuracion= : ID de configuración específica}
                            {--dry-run : Solo simular sin aplicar cambios}
                            {--confirmar : Confirmar automáticamente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aplica descuentos o incrementos basados en ocupación por edificio';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fechaAnalisis = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : Carbon::now();
        $configuracionId = $this->option('configuracion');
        $dryRun = $this->option('dry-run');
        $confirmar = $this->option('confirmar');

        $this->info('🔄 APLICANDO AJUSTES DE PRECIOS POR OCUPACIÓN DE EDIFICIO');
        $this->line("Fecha de análisis: {$fechaAnalisis->format('d/m/Y')} (" . $fechaAnalisis->format('l') . ")");
        $this->line('');

        // Obtener configuraciones activas
        $configuraciones = $this->obtenerConfiguraciones($configuracionId);
        if ($configuraciones->isEmpty()) {
            $this->warn('⚠️  No hay configuraciones de descuento activas');
            return;
        }

        $edificiosConAccion = [];

        foreach ($configuraciones as $configuracion) {
            $this->line("🏢 Analizando edificio: {$configuracion->edificio->nombre}");
            
            // Verificar si es el día configurado
            $diaConfigurado = $configuracion->condiciones['dia_semana'] ?? 'friday';
            if (!$this->esDiaConfigurado($fechaAnalisis, $diaConfigurado)) {
                $this->line("   ℹ️  No es el día configurado ({$this->getNombreDia($diaConfigurado)})");
                continue;
            }

            // Calcular semana siguiente
            $lunesSiguiente = $fechaAnalisis->copy()->addDays(3);
            $juevesSiguiente = $lunesSiguiente->copy()->addDays(3);

            // Determinar acción basada en ocupación
            $accion = $configuracion->determinarAccionOcupacion($lunesSiguiente, $juevesSiguiente);
            
            if ($accion['accion'] === 'ninguna') {
                $this->line("   ✅ Ocupación normal ({$accion['ocupacion_actual']}%), no se requiere acción");
                continue;
            }

            $accionTexto = $accion['accion'] === 'descuento' ? 'DESCUENTO' : 'INCREMENTO';
            $this->line("   🎯 ¡{$accionTexto} APLICABLE!");
            $this->line("   📊 Ocupación: {$accion['ocupacion_actual']}% (límite: {$accion['ocupacion_limite']}%)");

            // Analizar apartamentos del edificio
            $apartamentosConAccion = $this->analizarApartamentosEdificio($configuracion, $lunesSiguiente, $juevesSiguiente, $accion);
            
            if (!empty($apartamentosConAccion)) {
                $edificiosConAccion[] = [
                    'configuracion' => $configuracion,
                    'accion' => $accion,
                    'apartamentos' => $apartamentosConAccion
                ];
            }
        }

        if (empty($edificiosConAccion)) {
            $this->info('✅ No hay edificios que requieran ajuste de precios');
            return;
        }

        // Mostrar resumen
        $this->mostrarResumen($edificiosConAccion);

        // Confirmar si no es dry-run
        if (!$dryRun && !$confirmar) {
            if (!$this->confirm('¿Deseas aplicar los ajustes de precios?')) {
                $this->info('❌ Operación cancelada');
                return;
            }
        }

        // Aplicar ajustes
        $this->aplicarAjustes($edificiosConAccion, $dryRun);
    }

    /**
     * Obtener configuraciones activas
     */
    private function obtenerConfiguraciones($configuracionId = null)
    {
        if ($configuracionId) {
            return ConfiguracionDescuento::with('edificio.apartamentos')
                ->where('id', $configuracionId)
                ->activas()
                ->get();
        } else {
            return ConfiguracionDescuento::with('edificio.apartamentos')
                ->activas()
                ->get();
        }
    }

    /**
     * Analizar apartamentos de un edificio
     */
    private function analizarApartamentosEdificio($configuracion, $lunesSiguiente, $juevesSiguiente, $accion)
    {
        $apartamentosConAccion = [];
        $apartamentos = $configuracion->edificio->apartamentos;

        foreach ($apartamentos as $apartamento) {
            // Verificar que tenga id_channex
            if (!$apartamento->id_channex) {
                continue;
            }

            $disponibilidad = $this->verificarDisponibilidad($apartamento, $lunesSiguiente, $juevesSiguiente);
            $diasLibres = $disponibilidad['dias_libres'];
            
            if (!empty($diasLibres)) {
                $apartamentosConAccion[] = [
                    'apartamento' => $apartamento,
                    'dias_libres' => $diasLibres,
                    'fecha_inicio' => $lunesSiguiente,
                    'fecha_fin' => $juevesSiguiente,
                    'accion' => $accion
                ];
            }
        }

        return $apartamentosConAccion;
    }

    /**
     * Verificar disponibilidad de un apartamento
     */
    private function verificarDisponibilidad($apartamento, $fechaInicio, $fechaFin)
    {
        $diasLibres = [];
        $diasOcupados = [];
        $fechaActual = $fechaInicio->copy();

        while ($fechaActual <= $fechaFin) {
            $reservas = Reserva::where('apartamento_id', $apartamento->id)
                ->where('fecha_entrada', '<=', $fechaActual)
                ->where('fecha_salida', '>', $fechaActual)
                ->whereNull('deleted_at')
                ->exists();

            if (!$reservas) {
                $diasLibres[] = $fechaActual->copy();
            } else {
                $diasOcupados[] = $fechaActual->copy();
            }

            $fechaActual->addDay();
        }

        return [
            'dias_libres' => $diasLibres,
            'dias_ocupados' => $diasOcupados,
            'total_dias_libres' => count($diasLibres),
            'total_dias_ocupados' => count($diasOcupados)
        ];
    }

    /**
     * Mostrar resumen de ajustes
     */
    private function mostrarResumen($edificiosConAccion)
    {
        $this->info('📊 RESUMEN DE AJUSTES A APLICAR:');
        $this->line('');

        $totalDias = 0;
        $ahorroTotal = 0;

        foreach ($edificiosConAccion as $edificio) {
            $configuracion = $edificio['configuracion'];
            $accion = $edificio['accion'];
            $apartamentos = $edificio['apartamentos'];

            $this->line("🏢 {$configuracion->edificio->nombre}");
            $this->line("   Acción: " . ($accion['accion'] === 'descuento' ? 'DESCUENTO' : 'INCREMENTO'));
            $this->line("   Porcentaje: {$accion['porcentaje']}%");
            $this->line("   Ocupación: {$accion['ocupacion_actual']}%");
            $this->line("   Apartamentos afectados: " . count($apartamentos));
            $this->line("");

            foreach ($apartamentos as $apartamento) {
                $diasLibres = $apartamento['dias_libres'];
                $this->line("   🏠 {$apartamento['apartamento']->nombre}: " . count($diasLibres) . " días");
                
                foreach ($diasLibres as $fecha) {
                    $this->line("      • {$fecha->format('d/m/Y (l)')}");
                }
                
                $totalDias += count($diasLibres);
            }
            $this->line("");
        }

        $this->info("📈 TOTAL:");
        $this->line("   Edificios: " . count($edificiosConAccion));
        $this->line("   Días totales: {$totalDias}");
        $this->line("");
    }

    /**
     * Aplicar ajustes de precios
     */
    private function aplicarAjustes($edificiosConAccion, $dryRun)
    {
        $this->info('🔄 APLICANDO AJUSTES...');
        $this->line('');

        $exitosos = 0;
        $errores = 0;

        foreach ($edificiosConAccion as $edificio) {
            $configuracion = $edificio['configuracion'];
            $accion = $edificio['accion'];
            $apartamentos = $edificio['apartamentos'];

            $this->line("🏢 Procesando: {$configuracion->edificio->nombre}");

            foreach ($apartamentos as $apartamento) {
                $apartamentoObj = $apartamento['apartamento'];
                $diasLibres = $apartamento['dias_libres'];

                $this->line("   🏠 {$apartamentoObj->nombre}");

                try {
                    // Crear registro en historial
                    $historial = $this->crearHistorial($apartamento, $configuracion, $accion);

                    if (!$dryRun) {
                        // Aplicar ajuste a Channex
                        $resultado = $this->aplicarAjusteChannex($apartamentoObj, $diasLibres, $configuracion, $accion);
                        
                        // Actualizar estado del historial
                        $historial->estado = $resultado['success'] ? 'aplicado' : 'error';
                        $historial->datos_channex = $resultado['response'] ?? null;
                        $historial->observaciones = $resultado['message'] ?? null;
                        $historial->save();

                        if ($resultado['success']) {
                            $exitosos++;
                            $this->info("      ✅ Ajuste aplicado exitosamente");
                        } else {
                            $errores++;
                            $this->error("      ❌ Error: " . ($resultado['message'] ?? 'Error desconocido'));
                        }
                    } else {
                        $exitosos++;
                        $this->info("      ✅ Simulación exitosa");
                    }

                } catch (\Exception $e) {
                    $errores++;
                    $this->error("      ❌ Error: " . $e->getMessage());
                    
                    if (!$dryRun) {
                        $historial->estado = 'error';
                        $historial->observaciones = $e->getMessage();
                        $historial->save();
                    }
                }
            }
            $this->line('');
        }

        $this->info('📊 RESULTADO FINAL:');
        $this->line("   ✅ Exitosos: {$exitosos}");
        $this->line("   ❌ Errores: {$errores}");
    }

    /**
     * Crear registro en historial
     */
    private function crearHistorial($apartamento, $configuracion, $accion)
    {
        $apartamentoObj = $apartamento['apartamento'];
        $diasLibres = $apartamento['dias_libres'];
        
        // Obtener tarifa del apartamento
        $tarifa = $apartamentoObj->tarifas->first();
        
        $precioOriginal = $tarifa ? $tarifa->precio : 0;
        $precioConAjuste = $accion['accion'] === 'descuento' 
            ? $configuracion->calcularPrecioConDescuento($precioOriginal)
            : $configuracion->calcularPrecioConIncremento($precioOriginal);
        
        $ahorroPorDia = $accion['accion'] === 'descuento'
            ? $configuracion->calcularAhorroPorDia($precioOriginal)
            : $configuracion->calcularGananciaPorDia($precioOriginal);

        // Recopilar todos los datos del momento
        $datosMomento = [
            'fecha_analisis' => now()->format('Y-m-d H:i:s'),
            'edificio' => [
                'id' => $configuracion->edificio->id,
                'nombre' => $configuracion->edificio->nombre,
                'total_apartamentos' => $configuracion->edificio->apartamentos->count()
            ],
            'configuracion' => [
                'id' => $configuracion->id,
                'nombre' => $configuracion->nombre,
                'porcentaje_descuento' => $configuracion->porcentaje_descuento,
                'porcentaje_incremento' => $configuracion->porcentaje_incremento,
                'condiciones' => $configuracion->condiciones
            ],
            'apartamento' => [
                'id' => $apartamentoObj->id,
                'nombre' => $apartamentoObj->nombre,
                'id_channex' => $apartamentoObj->id_channex
            ],
            'tarifa' => $tarifa ? [
                'id' => $tarifa->id,
                'nombre' => $tarifa->nombre,
                'precio' => $tarifa->precio
            ] : null,
            'accion' => $accion['accion'],
            'porcentaje' => $accion['porcentaje'],
            'ocupacion_actual' => $accion['ocupacion_actual'],
            'ocupacion_limite' => $accion['ocupacion_limite'],
            'dias_libres' => collect($diasLibres)->map(function($fecha) {
                return $fecha->format('Y-m-d');
            })->toArray(),
            'fecha_inicio' => $apartamento['fecha_inicio']->format('Y-m-d'),
            'fecha_fin' => $apartamento['fecha_fin']->format('Y-m-d'),
            'precio_original' => $precioOriginal,
            'precio_con_ajuste' => $precioConAjuste,
            'ahorro_por_dia' => $ahorroPorDia,
            'total_dias' => count($diasLibres)
        ];

        return HistorialDescuento::create([
            'apartamento_id' => $apartamentoObj->id,
            'tarifa_id' => $tarifa ? $tarifa->id : null,
            'configuracion_descuento_id' => $configuracion->id,
            'fecha_aplicacion' => now(),
            'fecha_inicio_descuento' => $apartamento['fecha_inicio'],
            'fecha_fin_descuento' => $apartamento['fecha_fin'],
            'precio_original' => $precioOriginal,
            'precio_con_descuento' => $precioConAjuste,
            'porcentaje_descuento' => $accion['porcentaje'],
            'dias_aplicados' => count($diasLibres),
            'ahorro_total' => $ahorroPorDia * count($diasLibres),
            'estado' => 'pendiente',
            'observaciones' => "Ajuste por ocupación: {$accion['ocupacion_actual']}% ({$accion['accion']})",
            'datos_momento' => $datosMomento
        ]);
    }

    /**
     * Aplicar ajuste a Channex
     */
    private function aplicarAjusteChannex($apartamento, $diasLibres, $configuracion, $accion)
    {
        try {
            // Obtener tarifa del apartamento
            $tarifa = $apartamento->tarifas->first();
            if (!$tarifa) {
                return [
                    'success' => false,
                    'message' => 'No se encontró tarifa para el apartamento'
                ];
            }

            $precioOriginal = $tarifa->precio;
            $precioConAjuste = $accion['accion'] === 'descuento' 
                ? $configuracion->calcularPrecioConDescuento($precioOriginal)
                : $configuracion->calcularPrecioConIncremento($precioOriginal);

            // Obtener room types y rate plans del apartamento
            $roomTypes = $apartamento->roomTypes;
            $ratePlans = $apartamento->ratePlans;
            
            if ($roomTypes->isEmpty() || $ratePlans->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron room types o rate plans para el apartamento'
                ];
            }

            $updates = [];
            foreach ($diasLibres as $fecha) {
                foreach ($roomTypes as $roomType) {
                    foreach ($ratePlans as $ratePlan) {
                        if ($ratePlan->room_type_id == $roomType->id) {
                            $updates[] = [
                                'property_id' => $apartamento->id_channex,
                                'room_type_id' => $roomType->id_channex,
                                'rate_plan_id' => $ratePlan->id_channex,
                                'date' => $fecha->format('Y-m-d'),
                                'rate' => $precioConAjuste
                            ];
                        }
                    }
                }
            }

            // Enviar actualización a Channex
            $response = Http::withHeaders([
                'user-api-key' => env('CHANNEX_TOKEN'),
            ])->post(env('CHANNEX_URL') . "/restrictions", [
                'values' => $updates
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => ucfirst($accion['accion']) . ' aplicado correctamente',
                    'response' => [
                        'sent_data' => $updates,
                        'channex_response' => $response->json()
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error en la respuesta de Channex: ' . $response->body(),
                    'response' => [
                        'sent_data' => $updates,
                        'channex_response' => $response->json()
                    ]
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error aplicando ajuste: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si la fecha es el día configurado
     */
    private function esDiaConfigurado($fecha, $diaConfigurado)
    {
        $dias = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0
        ];
        
        return $fecha->dayOfWeek === $dias[$diaConfigurado];
    }

    /**
     * Obtiene el nombre del día en español
     */
    private function getNombreDia($diaConfigurado)
    {
        $dias = [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo'
        ];
        
        return $dias[$diaConfigurado] ?? $diaConfigurado;
    }
}
