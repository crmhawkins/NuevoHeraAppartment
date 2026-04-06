<?php

namespace App\Console\Commands;

use App\Models\Apartamento;
use App\Models\Reserva;
use App\Models\Tarifa;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AplicarDescuentoApartamentosLibres extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aplicar:descuento-apartamentos-libres 
                            {--fecha= : Fecha de análisis (YYYY-MM-DD). Por defecto: hoy}
                            {--dry-run : Solo simular sin aplicar cambios}
                            {--force : Forzar ejecución aunque no sea lunes-jueves}
                            {--confirmar : Confirmar automáticamente sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aplica descuento del 20% a apartamentos libres si hay más de 3 disponibles (excepto 3, priorizando áticos)';

    /**
     * Descuento a aplicar (20%)
     */
    private const DESCUENTO_PORCENTAJE = 20;
    
    /**
     * Número mínimo de apartamentos libres para aplicar descuento
     */
    private const MIN_APARTAMENTOS_LIBRES = 3;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fechaAnalisis = $this->option('fecha') 
            ? Carbon::parse($this->option('fecha')) 
            : Carbon::today();
        
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🏠 APLICACIÓN DE DESCUENTO A APARTAMENTOS LIBRES');
        $this->line("Fecha de análisis: {$fechaAnalisis->format('d/m/Y')} (" . $this->getNombreDia($fechaAnalisis->dayOfWeek) . ")");
        $this->line('');

        // Log de inicio de ejecución
        Log::info("Inicio de ejecución: Aplicar descuento a apartamentos libres", [
            'fecha_analisis' => $fechaAnalisis->format('Y-m-d'),
            'dia_semana' => $this->getNombreDia($fechaAnalisis->dayOfWeek),
            'dry_run' => $dryRun,
            'force' => $force
        ]);

        // Verificar que sea lunes-jueves
        // En Carbon: 0=Domingo, 1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado
        if (!$force && !in_array($fechaAnalisis->dayOfWeek, [1, 2, 3, 4])) {
            $this->warn('⚠️  Este comando solo se ejecuta de lunes a jueves');
            $this->warn('   Usa --force para forzar la ejecución');
            
            Log::warning("Comando cancelado: no es día válido (lunes-jueves)", [
                'fecha_analisis' => $fechaAnalisis->format('Y-m-d'),
                'dia_semana' => $this->getNombreDia($fechaAnalisis->dayOfWeek)
            ]);
            
            return 0;
        }

        // Obtener apartamentos libres para hoy
        $apartamentosLibres = $this->obtenerApartamentosLibres($fechaAnalisis);

        $this->line("📊 Apartamentos libres encontrados: " . $apartamentosLibres->count());
        $this->line('');

        // Verificar si hay más de 3 apartamentos libres
        if ($apartamentosLibres->count() <= self::MIN_APARTAMENTOS_LIBRES) {
            $this->info("✅ No se aplica descuento: hay {$apartamentosLibres->count()} apartamentos libres (máximo {$this->MIN_APARTAMENTOS_LIBRES})");
            
            Log::info("No se aplica descuento: insuficientes apartamentos libres", [
                'fecha_analisis' => $fechaAnalisis->format('Y-m-d'),
                'apartamentos_libres' => $apartamentosLibres->count(),
                'minimo_requerido' => self::MIN_APARTAMENTOS_LIBRES
            ]);
            
            return 0;
        }

        // Separar áticos del resto
        $aticos = $apartamentosLibres->filter(function($apt) {
            return $this->esAtico($apt);
        });

        $noAticos = $apartamentosLibres->reject(function($apt) {
            return $this->esAtico($apt);
        });

        $this->line("🏢 Áticos libres: " . $aticos->count());
        $this->line("🏠 Otros apartamentos libres: " . $noAticos->count());
        $this->line('');

        // Seleccionar los 3 apartamentos que NO tendrán descuento
        // Prioridad: todos los áticos libres (hasta 3), luego otros apartamentos
        $apartamentosSinDescuento = collect();
        
        // Primero agregar áticos (hasta 3)
        $apartamentosSinDescuento = $apartamentosSinDescuento->merge($aticos->take(3));
        
        // Si aún no tenemos 3, agregar otros apartamentos
        if ($apartamentosSinDescuento->count() < 3) {
            $restantes = 3 - $apartamentosSinDescuento->count();
            $apartamentosSinDescuento = $apartamentosSinDescuento->merge($noAticos->take($restantes));
        }

        // Los apartamentos que SÍ tendrán descuento son el resto
        $apartamentosConDescuento = $apartamentosLibres->diff($apartamentosSinDescuento);

        $this->info("🎯 RESUMEN:");
        $this->line("   Apartamentos libres totales: " . $apartamentosLibres->count());
        $this->line("   Apartamentos SIN descuento (3): " . $apartamentosSinDescuento->count());
        $this->line("   Apartamentos CON descuento (20%): " . $apartamentosConDescuento->count());
        $this->line('');

        // Mostrar apartamentos sin descuento
        $this->line("🏠 Apartamentos SIN descuento:");
        foreach ($apartamentosSinDescuento as $apt) {
            $tipo = $this->esAtico($apt) ? ' (ÁTICO)' : '';
            $this->line("   • {$apt->nombre}{$tipo}");
        }
        $this->line('');

        // Mostrar apartamentos con descuento
        $this->line("💰 Apartamentos CON descuento (20%):");
        foreach ($apartamentosConDescuento as $apt) {
            $this->line("   • {$apt->nombre}");
        }
        $this->line('');

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se aplicarán cambios');
            return 0;
        }

        // Log antes de aplicar descuentos
        Log::info("Aplicando descuentos a apartamentos libres", [
            'fecha_analisis' => $fechaAnalisis->format('Y-m-d'),
            'total_apartamentos_libres' => $apartamentosLibres->count(),
            'apartamentos_sin_descuento' => $apartamentosSinDescuento->count(),
            'apartamentos_con_descuento' => $apartamentosConDescuento->count(),
            'descuento_porcentaje' => self::DESCUENTO_PORCENTAJE,
            'apartamentos_sin_descuento_lista' => $apartamentosSinDescuento->pluck('nombre')->toArray(),
            'apartamentos_con_descuento_lista' => $apartamentosConDescuento->pluck('nombre')->toArray()
        ]);

        // Confirmar antes de aplicar
        // Si está en modo no-interactivo (cron) o se usa --confirmar, confirmar automáticamente
        $confirmar = $this->option('confirmar');
        $esNoInteractivo = !$this->input->isInteractive(); // Detecta si está en modo cron/no-interactivo
        
        if (!$confirmar && !$esNoInteractivo) {
            // Solo preguntar si NO está en modo no-interactivo y NO se usó --confirmar
            if (!$this->confirm('¿Deseas aplicar el descuento del 20% a los apartamentos seleccionados?')) {
                $this->info('❌ Operación cancelada');
                
                Log::info("Operación cancelada por el usuario", [
                    'fecha_analisis' => $fechaAnalisis->format('Y-m-d')
                ]);
                
                return 0;
            }
        } else {
            // Modo automático (cron o --confirmar)
            if ($esNoInteractivo) {
                $this->line('🤖 Modo no-interactivo detectado: confirmación automática');
            }
        }

        // Aplicar descuentos
        $this->aplicarDescuentos($apartamentosConDescuento, $fechaAnalisis);

        return 0;
    }

    /**
     * Obtener apartamentos libres para una fecha específica
     */
    private function obtenerApartamentosLibres(Carbon $fecha)
    {
        // Obtener apartamentos ocupados en esa fecha
        $apartamentosOcupados = Reserva::where('estado_id', '!=', 4) // No canceladas
            ->where('fecha_entrada', '<=', $fecha)
            ->where('fecha_salida', '>', $fecha)
            ->pluck('apartamento_id')
            ->unique();

        // Obtener apartamentos libres que tengan id_channex configurado
        $apartamentosLibres = Apartamento::whereNotIn('id', $apartamentosOcupados)
            ->whereNotNull('id_channex')
            ->whereNotNull('edificio_id')
            ->with(['roomTypes', 'ratePlans', 'tarifas'])
            ->get();

        return $apartamentosLibres;
    }

    /**
     * Verificar si un apartamento es un ático
     */
    private function esAtico(Apartamento $apartamento)
    {
        $nombre = strtolower($apartamento->nombre ?? '');
        $titulo = strtolower($apartamento->titulo ?? '');
        
        return str_contains($nombre, 'atico') || 
               str_contains($nombre, 'ático') ||
               str_contains($titulo, 'atico') || 
               str_contains($titulo, 'ático');
    }

    /**
     * Aplicar descuentos a los apartamentos seleccionados
     */
    private function aplicarDescuentos($apartamentos, Carbon $fecha)
    {
        $this->info('🔄 APLICANDO DESCUENTOS...');
        $this->line('');

        $exitosos = 0;
        $errores = 0;

        foreach ($apartamentos as $apartamento) {
            $this->line("🏠 Procesando: {$apartamento->nombre}");

            try {
                // Obtener tarifa del apartamento
                $tarifa = $apartamento->tarifas->first();
                if (!$tarifa) {
                    $this->warn("   ⚠️  No se encontró tarifa para el apartamento");
                    $errores++;
                    continue;
                }

                $precioOriginal = $tarifa->precio;
                $precioConDescuento = $precioOriginal * (1 - (self::DESCUENTO_PORCENTAJE / 100));

                $this->line("   💰 Precio original: " . number_format($precioOriginal, 2) . " €");
                $this->line("   💰 Precio con descuento (" . self::DESCUENTO_PORCENTAJE . "%): " . number_format($precioConDescuento, 2) . " €");
                $this->line("   📤 Precio a enviar a Channex (centavos): " . round($precioConDescuento * 100));

                // Obtener room types y rate plans
                $roomTypes = $apartamento->roomTypes;
                $ratePlans = $apartamento->ratePlans;

                if ($roomTypes->isEmpty() || $ratePlans->isEmpty()) {
                    $this->warn("   ⚠️  No se encontraron room types o rate plans para el apartamento");
                    $errores++;
                    continue;
                }

                // Preparar actualizaciones para Channex
                // IMPORTANTE: Channex espera el precio en centavos (multiplicar por 100)
                // Si el precio es 56.00 €, debemos enviar 5600
                $precioEnCentavos = round($precioConDescuento * 100);
                
                $updates = [];
                foreach ($roomTypes as $roomType) {
                    foreach ($ratePlans as $ratePlan) {
                        if ($ratePlan->room_type_id == $roomType->id) {
                            $updates[] = [
                                'property_id' => $apartamento->id_channex,
                                'room_type_id' => $roomType->id_channex,
                                'rate_plan_id' => $ratePlan->id_channex,
                                'date' => $fecha->format('Y-m-d'),
                                'rate' => $precioEnCentavos
                            ];
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
                    $exitosos++;
                    $this->info("   ✅ Descuento aplicado exitosamente");
                    
                    Log::info("Descuento aplicado a apartamento", [
                        'apartamento_id' => $apartamento->id,
                        'apartamento_nombre' => $apartamento->nombre,
                        'fecha' => $fecha->format('Y-m-d'),
                        'precio_original' => $precioOriginal,
                        'precio_con_descuento' => $precioConDescuento,
                        'descuento_porcentaje' => self::DESCUENTO_PORCENTAJE,
                        'updates' => $updates
                    ]);
                } else {
                    $errores++;
                    $this->error("   ❌ Error en Channex: " . $response->body());
                    
                    Log::error("Error aplicando descuento a apartamento", [
                        'apartamento_id' => $apartamento->id,
                        'apartamento_nombre' => $apartamento->nombre,
                        'fecha' => $fecha->format('Y-m-d'),
                        'error' => $response->body(),
                        'updates' => $updates
                    ]);
                }

            } catch (\Exception $e) {
                $errores++;
                $this->error("   ❌ Error: " . $e->getMessage());
                
                Log::error("Excepción aplicando descuento a apartamento", [
                    'apartamento_id' => $apartamento->id,
                    'apartamento_nombre' => $apartamento->nombre,
                    'fecha' => $fecha->format('Y-m-d'),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->line('');
        $this->info("📊 RESUMEN FINAL:");
        $this->line("   ✅ Exitosos: {$exitosos}");
        $this->line("   ❌ Errores: {$errores}");
        
        // Log del resumen final
        Log::info("Resumen final: Aplicar descuento a apartamentos libres", [
            'fecha_analisis' => $fecha->format('Y-m-d'),
            'exitosos' => $exitosos,
            'errores' => $errores
        ]);
    }

    /**
     * Obtener nombre del día de la semana
     */
    private function getNombreDia($dayOfWeek)
    {
        // Mapeo correcto de Carbon dayOfWeek: 0=Domingo, 1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado
        $dias = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado'
        ];

        return $dias[$dayOfWeek] ?? 'Desconocido';
    }
}
