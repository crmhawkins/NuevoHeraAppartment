<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Apartamento;
use App\Models\Tarifa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EstablecerPreciosBaseTemporada extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'establecer:precios-base-temporada 
                            {--fecha= : Fecha específica para analizar (formato: Y-m-d)}
                            {--dry-run : Solo mostrar qué se haría sin aplicar cambios}
                            {--confirmar : Confirmar automáticamente sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Establece precios base para la temporada sin aplicar descuentos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fechaAnalisis = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : Carbon::now();
        $dryRun = $this->option('dry-run');
        $confirmar = $this->option('confirmar');
        
        $this->info('💰 ESTABLECIENDO PRECIOS BASE PARA TEMPORADA');
        $this->info('Fecha de análisis: ' . $fechaAnalisis->format('d/m/Y (l)'));
        $this->info('Modo: ' . ($dryRun ? 'SIMULACIÓN' : 'APLICACIÓN REAL'));
        $this->line('');

        // COMENTADO PARA PRUEBAS - Verificación de viernes
        // if (!$fechaAnalisis->isFriday()) {
        //     $this->warn('❌ No es viernes. Este comando solo funciona los viernes.');
        //     return;
        // }

        // Calcular semana siguiente
        $lunesSiguiente = $fechaAnalisis->copy()->addDays(3);
        $juevesSiguiente = $lunesSiguiente->copy()->addDays(3);

        $this->info("📅 Semana siguiente: {$lunesSiguiente->format('d/m/Y (l)')} - {$juevesSiguiente->format('d/m/Y (l)')}");
        $this->line('');

        // Obtener apartamentos con tarifas de temporada baja
        $apartamentos = Apartamento::whereNotNull('id_channex')
            ->with(['edificioName', 'roomTypes', 'ratePlans', 'tarifas' => function($query) {
                $query->where('tarifas.temporada_baja', true)
                      ->where('tarifas.activo', true);
            }])
            ->get();

        $apartamentosConPrecios = [];

        foreach ($apartamentos as $apartamento) {
            $precios = $this->analizarApartamento($apartamento, $lunesSiguiente, $juevesSiguiente);
            if ($precios) {
                $apartamentosConPrecios[] = $precios;
            }
        }

        if (empty($apartamentosConPrecios)) {
            $this->info('✅ No hay apartamentos que requieran establecer precios base');
            return;
        }

        // Mostrar resumen
        $this->mostrarResumen($apartamentosConPrecios);

        // Confirmar aplicación
        if (!$confirmar && !$dryRun) {
            if (!$this->confirm('¿Deseas establecer estos precios base en Channex?')) {
                $this->info('❌ Operación cancelada');
                return;
            }
        }

        // Establecer precios
        $this->establecerPrecios($apartamentosConPrecios, $dryRun);
    }

    /**
     * Analizar un apartamento para establecer precios base
     */
    private function analizarApartamento($apartamento, $lunesSiguiente, $juevesSiguiente)
    {
        $tarifasTemporadaBaja = $apartamento->tarifas;
        if ($tarifasTemporadaBaja->isEmpty()) {
            return null;
        }

        foreach ($tarifasTemporadaBaja as $tarifa) {
            // Verificar si la tarifa está vigente
            if ($tarifa->fecha_inicio <= $juevesSiguiente && $tarifa->fecha_fin >= $lunesSiguiente) {
                // Verificar disponibilidad
                $diasLibres = $this->verificarDisponibilidad($apartamento, $lunesSiguiente, $juevesSiguiente);
                
                if (!empty($diasLibres)) {
                    return [
                        'apartamento' => $apartamento,
                        'tarifa' => $tarifa,
                        'dias_libres' => $diasLibres,
                        'fecha_inicio' => $lunesSiguiente,
                        'fecha_fin' => $juevesSiguiente
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Verificar disponibilidad
     */
    private function verificarDisponibilidad($apartamento, $fechaInicio, $fechaFin)
    {
        $diasLibres = [];
        $fechaActual = $fechaInicio->copy();

        while ($fechaActual <= $fechaFin) {
            $reservas = \App\Models\Reserva::where('apartamento_id', $apartamento->id)
                ->where('fecha_entrada', '<=', $fechaActual)
                ->where('fecha_salida', '>', $fechaActual)
                ->whereNull('deleted_at')
                ->exists();

            if (!$reservas) {
                $diasLibres[] = $fechaActual->copy();
            }

            $fechaActual->addDay();
        }

        return $diasLibres;
    }

    /**
     * Mostrar resumen de precios a establecer
     */
    private function mostrarResumen($apartamentosConPrecios)
    {
        $this->info('📊 RESUMEN DE PRECIOS BASE A ESTABLECER:');
        $this->line('');

        $totalDias = 0;

        foreach ($apartamentosConPrecios as $precios) {
            $apartamento = $precios['apartamento'];
            $tarifa = $precios['tarifa'];
            $diasLibres = $precios['dias_libres'];

            $this->line("🏠 {$apartamento->nombre}");
            $this->line("   Tarifa: {$tarifa->nombre} ({$tarifa->precio}€)");
            $this->line("   Días libres: " . count($diasLibres) . " días");
            $this->line("   Room Types: " . $apartamento->roomTypes->count());
            $this->line("   Rate Plans: " . $apartamento->ratePlans->count());
            $this->line("");

            $totalDias += count($diasLibres);
        }

        $this->info("📈 TOTAL:");
        $this->line("   Apartamentos: " . count($apartamentosConPrecios));
        $this->line("   Días totales: {$totalDias}");
        $this->line("");
    }

    /**
     * Establecer precios base
     */
    private function establecerPrecios($apartamentosConPrecios, $dryRun)
    {
        $this->info('🔄 ESTABLECIENDO PRECIOS BASE...');
        $this->line('');

        $exitosos = 0;
        $errores = 0;

        foreach ($apartamentosConPrecios as $precios) {
            $apartamento = $precios['apartamento'];
            $tarifa = $precios['tarifa'];
            $diasLibres = $precios['dias_libres'];

            $this->line("🏠 Procesando: {$apartamento->nombre}");

            try {
                if (!$dryRun) {
                    $resultado = $this->establecerPreciosBase($apartamento, $diasLibres, $tarifa);
                    
                    if ($resultado['success']) {
                        $exitosos++;
                        $this->info("   ✅ Precios base establecidos exitosamente");
                    } else {
                        $errores++;
                        $this->error("   ❌ Error: " . ($resultado['message'] ?? 'Error desconocido'));
                    }
                } else {
                    $exitosos++;
                    $this->info("   ✅ Simulación exitosa");
                }

            } catch (\Exception $e) {
                $errores++;
                $this->error("   ❌ Error: " . $e->getMessage());
            }

            $this->line('');
        }

        $this->info('📊 RESULTADO FINAL:');
        $this->line("   ✅ Exitosos: {$exitosos}");
        $this->line("   ❌ Errores: {$errores}");
    }

    /**
     * Establecer precios base para un apartamento
     */
    private function establecerPreciosBase($apartamento, $diasLibres, $tarifa)
    {
        try {
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
                                'rate' => $tarifa->precio
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
                    'message' => 'Precios base establecidos correctamente',
                    'response' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error estableciendo precios base: ' . $response->body(),
                    'response' => $response->json()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error estableciendo precios base: ' . $e->getMessage()
            ];
        }
    }
}
