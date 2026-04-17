<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmpleadaHorario;
use App\Models\TipoTarea;
use App\Models\TurnoTrabajo;
use App\Models\TareaAsignada;
use App\Models\Apartamento;
use App\Models\ZonaComun;
use App\Models\ApartamentoLimpieza;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerarTurnosTrabajo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'turnos:generar {fecha?} {--force : Forzar regeneración de turnos existentes} {--ia : Usar OpenAI para optimizar asignación} {--test-ia : Probar sistema IA con respuesta simulada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera turnos de trabajo para las empleadas de limpieza basado en horarios y tareas pendientes';
    
    private function esMustDoTipo(\App\Models\TipoTarea $tipo): bool
    {
        return (int)($tipo->prioridad_base ?? 0) === 10;
    }
    
    /** Clave única para deduplicar tareas en memoria */
    private function hashTarea(array $t): string
    {
        return implode('|', [
            $t['tipo_tarea']->id ?? '0',
            $t['apartamento_id'] ?? '0',
            $t['zona_comun_id'] ?? '0',
            $t['edificio_id'] ?? '0',
        ]);
    }
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fecha = $this->argument('fecha') ? Carbon::parse($this->argument('fecha')) : today();
        $forzar = $this->option('force');
        $usarIA = $this->option('ia');
        $testIA = $this->option('test-ia');
        
        $this->info("🔄 Generando turnos para la fecha: {$fecha->format('Y-m-d')}");
        
        if ($usarIA) {
            $this->info("🤖 Modo IA activado - Usando OpenAI para optimización");
        } elseif ($testIA) {
            $this->info("🧪 Modo Test IA activado - Usando respuesta simulada");
        }
        
        try {
            DB::beginTransaction();
            
            // 1. Obtener empleadas disponibles para la fecha
            $empleadasDisponibles = $this->obtenerEmpleadasDisponibles($fecha);
            
            if ($empleadasDisponibles->isEmpty()) {
                $this->warn('⚠️  No hay empleadas disponibles para esta fecha');
                return;
            }
            
            $this->info("👥 Empleadas disponibles: {$empleadasDisponibles->count()}");
            
            // 2. Verificar si ya existen turnos para esta fecha
            if (!$forzar && TurnoTrabajo::porFecha($fecha)->exists()) {
                $this->warn('⚠️  Ya existen turnos para esta fecha. Usa --force para regenerar.');
                return;
            }
            
            // 3. Eliminar turnos existentes si se fuerza
            if ($forzar) {
                TurnoTrabajo::porFecha($fecha)->delete();
                $this->info('🗑️  Turnos existentes eliminados');
            }
            
            // 4. Generar tareas pendientes
            $tareasPendientes = $this->generarTareasPendientes($fecha);
            
            if ($tareasPendientes->isEmpty()) {
                $this->warn('⚠️  No hay tareas pendientes para generar');
                return;
            }
            
            $this->info("📋 Tareas pendientes: {$tareasPendientes->count()}");
            
            // 5. Distribuir tareas entre empleadas
            if ($usarIA) {
                // Usar IA para optimizar asignación
                $asignacionIA = $this->generarAsignacionConIA($empleadasDisponibles, $tareasPendientes, $fecha);
                
                if ($asignacionIA) {
                    $this->info("✅ Asignación optimizada por IA recibida");
                    $turnosGenerados = $this->crearTurnosDesdeIA($asignacionIA, $empleadasDisponibles, $fecha);
                } else {
                    $this->warn("⚠️  Fallback al sistema tradicional");
                    $turnosGenerados = $this->distribuirTareasEntreEmpleadas($empleadasDisponibles, $tareasPendientes, $fecha);
                }
            } elseif ($testIA) {
                // Usar respuesta simulada de IA para probar
                $asignacionIA = $this->generarRespuestaSimuladaIA($empleadasDisponibles, $tareasPendientes);
                $this->info("✅ Respuesta simulada de IA generada");
                $turnosGenerados = $this->crearTurnosDesdeIA($asignacionIA, $empleadasDisponibles, $fecha);
            } else {
                // Usar sistema tradicional
            $turnosGenerados = $this->distribuirTareasEntreEmpleadas($empleadasDisponibles, $tareasPendientes, $fecha);
            }
            
            $this->info("✅ Turnos generados exitosamente: {$turnosGenerados}");
            
            DB::commit();
            
            // 6. Mostrar resumen
            $this->mostrarResumen($fecha);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error al generar turnos: " . $e->getMessage());
            Log::error('Error generando turnos: ' . $e->getMessage(), [
                'fecha' => $fecha->format('Y-m-d'),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Obtener empleadas disponibles para una fecha específica
     */
    private function obtenerEmpleadasDisponibles($fecha)
    {
        $diaSemana = $fecha->dayOfWeek;
        $diasColumnas = [
            0 => 'domingo',
            1 => 'lunes',
            2 => 'martes', 
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado'
        ];
        
        $columnaDia = $diasColumnas[$diaSemana] ?? 'lunes';
        
        // Obtener empleadas que trabajan ese día de la semana
        $empleadasQueTrabajan = EmpleadaHorario::activos()
            ->where($columnaDia, true)
            ->with('user')
            ->get();
        
        // Filtrar por días libres por semana
        $empleadasDisponibles = $empleadasQueTrabajan->filter(function($empleada) use ($fecha) {
            return $this->estaDisponibleEnFecha($empleada, $fecha);
        });
        
        return $empleadasDisponibles;
    }
    
    /**
     * Verificar si una empleada está disponible en una fecha específica
     * considerando sus días libres por semana
     */
    private function estaDisponibleEnFecha($empleada, $fecha)
    {
        // Usar el nuevo modelo EmpleadaDiasLibres para verificar disponibilidad
        return \App\Models\EmpleadaDiasLibres::estaDisponibleEnFecha($empleada->id, $fecha);
    }
    
    // generarTareasPendientes2 eliminado - sustituido por el nuevo sistema de prioridades
    
    private function obtenerApartamentosPendientesHoy(Carbon $fecha)
    {
        $hoy = $fecha->toDateString();
    
        // 1) Reservas activas que SALEN hoy y aún no tienen limpieza registrada
        $reservas = \App\Models\Reserva::query()
            ->where('estado_id', '!=', 4)       // activas
            ->whereDate('fecha_salida', $hoy)   // salida hoy
            ->whereNull('fecha_limpieza')       // aún no limpiado
            ->with('apartamento')
            ->get();
    
        // 2) Apartamentos que YA tienen tarea de limpieza creada hoy
        $apartamentosConTareaHoy = \App\Models\TareaAsignada::query()
            ->whereDate('created_at', $hoy)
            ->whereHas('tipoTarea', fn($q) => $q->where('categoria','limpieza_apartamento'))
            ->pluck('apartamento_id')
            ->filter()
            ->unique()
            ->all();
    
        // 3) Filtrar reservas para no repetir esas tareas
        $reservas = $reservas->reject(fn($r) => in_array($r->apartamento_id, $apartamentosConTareaHoy));
    
        // Log de control
        Log::info('[TURNOS] Reservas filtradas', [
            'fecha'  => $hoy,
            'ids'    => $reservas->pluck('id')->all(),
            'apts'   => $reservas->pluck('apartamento_id')->all(),
            'total'  => $reservas->count(),
        ]);
    
        // 4) Devolver apartamentos únicos de las reservas que quedan
        return $reservas->pluck('apartamento')
            ->filter()
            ->unique('id')
            ->values();
    }
    
    /**
     * Generar tareas pendientes organizadas por prioridad (P1, P2, P3).
     *
     * Devuelve una colección con la clave 'nivel_prioridad':
     *   P1 = obligatorias, P2 = zonas comunes, P3 = secundarias
     */
    private function generarTareasPendientes($fecha)
    {
        $tareas = collect();

        // ── P1-A: LAVANDERIA (se genera una por cada limpiadora, se asigna en distribución) ──
        $tipoLavanderia = TipoTarea::where('id', 6)->first()
            ?? TipoTarea::where('nombre', 'LIKE', '%lavanderia%')->orWhere('nombre', 'LIKE', '%Lavandería%')->first();

        if ($tipoLavanderia) {
            $tareas->push([
                'tipo_tarea'       => $tipoLavanderia,
                'apartamento_id'   => null,
                'zona_comun_id'    => null,
                'edificio_id'      => null,
                'nivel_prioridad'  => 'P1',
                'subtipo'          => 'lavanderia',
                'prioridad'        => 100,
                'tiempo_estimado'  => 60, // 60 min por limpiadora (se clona en distribución)
            ]);
            Log::info('[TURNOS] P1 Lavandería encontrada', ['tipo_tarea_id' => $tipoLavanderia->id]);
        } else {
            Log::warning('[TURNOS] No se encontró tipo de tarea de Lavandería (id=6)');
        }

        // ── P1-B: APARTAMENTOS CON CHECKOUT HOY ──
        $apartamentosPendientesHoy = $this->obtenerApartamentosPendientesHoy($fecha);
        $tipoLimpApto = TipoTarea::activos()->limpiezaApartamentos()->first();

        foreach ($apartamentosPendientesHoy as $apartamento) {
            if (!$tipoLimpApto) continue;
            $tareas->push([
                'tipo_tarea'       => $tipoLimpApto,
                'apartamento_id'   => $apartamento->id,
                'zona_comun_id'    => null,
                'edificio_id'      => $apartamento->edificio_id ?? null,
                'nivel_prioridad'  => 'P1',
                'subtipo'          => 'checkout',
                'prioridad'        => 90,
                'tiempo_estimado'  => 60, // 1 hora por apartamento
            ]);
        }
        Log::info('[TURNOS] P1 Checkouts hoy: ' . $apartamentosPendientesHoy->count());

        // ── P1-C: OFICINA URGENTE (+7 días sin limpiar) ──
        $oficinaUrgente = $this->verificarOficinaUrgente();
        if ($oficinaUrgente) {
            // Buscar tipo tarea de oficina (id 2 o 11)
            $tipoOficina = TipoTarea::whereIn('id', [2, 11])->first()
                ?? TipoTarea::where('nombre', 'LIKE', '%Oficina%')->first();
            if ($tipoOficina) {
                $tareas->push([
                    'tipo_tarea'       => $tipoOficina,
                    'apartamento_id'   => 18, // OFICINA
                    'zona_comun_id'    => null,
                    'edificio_id'      => null,
                    'nivel_prioridad'  => 'P1',
                    'subtipo'          => 'oficina_urgente',
                    'prioridad'        => 80,
                    'tiempo_estimado'  => 60,
                ]);
                Log::info('[TURNOS] P1 Oficina URGENTE - más de 7 días sin limpiar');
            }
        }

        // ── P2: ZONAS COMUNES ──
        $zonasComunes = ZonaComun::activas()->ordenadas()->get();
        // tipo_tarea_id 3 = Costa, 7 = Suite
        $tipoZonaCosta = TipoTarea::find(3);
        $tipoZonaSuite = TipoTarea::find(7);
        $tipoZonaDefault = $tipoZonaCosta ?? $tipoZonaSuite ?? TipoTarea::activos()->limpiezaZonasComunes()->first();

        foreach ($zonasComunes as $zona) {
            $tipoZona = $tipoZonaDefault;
            // Si la zona tiene tipo, intentar asociar Costa/Suite
            if (stripos($zona->nombre, 'costa') !== false && $tipoZonaCosta) {
                $tipoZona = $tipoZonaCosta;
            } elseif (stripos($zona->nombre, 'suite') !== false && $tipoZonaSuite) {
                $tipoZona = $tipoZonaSuite;
            }

            if ($tipoZona) {
                $tareas->push([
                    'tipo_tarea'       => $tipoZona,
                    'apartamento_id'   => null,
                    'zona_comun_id'    => $zona->id,
                    'edificio_id'      => null,
                    'nivel_prioridad'  => 'P2',
                    'subtipo'          => 'zona_comun',
                    'prioridad'        => 50,
                    'tiempo_estimado'  => 60, // 1 hora por zona (ajustado de los 30min de DB)
                ]);
            }
        }
        Log::info('[TURNOS] P2 Zonas comunes: ' . $zonasComunes->count());

        // ── P3-A: LIMPIEZA A FONDO ──
        $limpiezasFondoEsteMes = $this->verificarLimpiezaFondoMensual();
        if ($limpiezasFondoEsteMes < 2) {
            $tipoLimpiezaFondo = TipoTarea::find(14)
                ?? TipoTarea::where('nombre', 'LIKE', '%fondo%')->first();
            if ($tipoLimpiezaFondo) {
                // Buscar apartamento que no haya tenido limpieza a fondo recientemente
                $aptoParaFondo = $this->obtenerApartamentoParaLimpiezaFondo();
                $tareas->push([
                    'tipo_tarea'       => $tipoLimpiezaFondo,
                    'apartamento_id'   => $aptoParaFondo ? $aptoParaFondo->id : null,
                    'zona_comun_id'    => null,
                    'edificio_id'      => $aptoParaFondo ? ($aptoParaFondo->edificio_id ?? null) : null,
                    'nivel_prioridad'  => 'P3',
                    'subtipo'          => 'limpieza_fondo',
                    'prioridad'        => 40,
                    'tiempo_estimado'  => 120, // 2 horas
                ]);
                Log::info('[TURNOS] P3 Limpieza a fondo necesaria (este mes: ' . $limpiezasFondoEsteMes . '/2)');
            }
        }

        // ── P3-B: OFICINA (si no fue P1) ──
        if (!$oficinaUrgente) {
            $tipoOficina = TipoTarea::whereIn('id', [2, 11])->first()
                ?? TipoTarea::where('nombre', 'LIKE', '%Oficina%')->first();
            if ($tipoOficina) {
                $tareas->push([
                    'tipo_tarea'       => $tipoOficina,
                    'apartamento_id'   => 18,
                    'zona_comun_id'    => null,
                    'edificio_id'      => null,
                    'nivel_prioridad'  => 'P3',
                    'subtipo'          => 'oficina_normal',
                    'prioridad'        => 30,
                    'tiempo_estimado'  => 60,
                ]);
            }
        }

        // ── P3-C: INVENTARIO/STOCK ──
        $tipoInventario = TipoTarea::find(9)
            ?? TipoTarea::where('nombre', 'LIKE', '%inventario%')->orWhere('nombre', 'LIKE', '%stock%')->first();
        if ($tipoInventario) {
            $tareas->push([
                'tipo_tarea'       => $tipoInventario,
                'apartamento_id'   => null,
                'zona_comun_id'    => null,
                'edificio_id'      => null,
                'nivel_prioridad'  => 'P3',
                'subtipo'          => 'inventario',
                'prioridad'        => 20,
                'tiempo_estimado'  => 30,
            ]);
        }

        // ── P3-D: OTRAS TAREAS (planchado, amenities, etc.) ──
        $tareasOtras = TipoTarea::activos()
            ->whereNotIn('id', $tareas->pluck('tipo_tarea')->pluck('id')->filter()->unique()->toArray())
            ->whereNotIn('categoria', ['limpieza_apartamento'])
            ->get();

        foreach ($tareasOtras as $tipoTarea) {
            $tareas->push([
                'tipo_tarea'       => $tipoTarea,
                'apartamento_id'   => null,
                'zona_comun_id'    => null,
                'edificio_id'      => null,
                'nivel_prioridad'  => 'P3',
                'subtipo'          => 'otra',
                'prioridad'        => 10,
                'tiempo_estimado'  => $tipoTarea->tiempo_estimado_minutos ?? 30,
            ]);
        }

        // Ordenar: P1 primero (prioridad desc), luego P2, luego P3
        return $tareas->sortByDesc('prioridad')->values();
    }

    
    /**
     * Calcular prioridad de una limpieza
     */
    private function calcularPrioridadLimpieza($limpieza, $tipoTarea)
    {
        $prioridad = $tipoTarea->prioridad_base;
        
        // Si la limpieza tiene días sin limpiar, aumentar prioridad
        if ($limpieza->fecha_comienzo && $tipoTarea->necesitaActualizacionPrioridad()) {
            $diasSinLimpiar = Carbon::parse($limpieza->fecha_comienzo)->diffInDays(now());
            $prioridad = $tipoTarea->calcularPrioridad($diasSinLimpiar);
        }
        
        return $prioridad;
    }
    
    /**
     * Calcular prioridad para un apartamento
     */
    private function calcularPrioridadApartamento($apartamento, $tipoTarea)
    {
        $prioridad = $tipoTarea->prioridad_base;
        
        // Prioridad alta para apartamentos que salen hoy
        $reservaSalidaHoy = \App\Models\Reserva::where('apartamento_id', $apartamento->id)
            ->whereNull('fecha_limpieza')
            ->where('estado_id', '!=', 4)
            ->whereDate('fecha_salida', now()->toDateString())
            ->first();
            
        if ($reservaSalidaHoy) {
            $prioridad = 10; // Máxima prioridad para salidas de hoy
        }
        
        return $prioridad;
    }
    
    /**
     * Distribuir tareas entre empleadas disponibles usando el sistema de prioridades P1/P2/P3.
     *
     * Algoritmo:
     *   Para cada limpiadora:
     *     1. Lavandería 60min (P1 obligatoria)
     *     2. Apartamentos checkout (P1)
     *     3. Oficina urgente si +7 días (P1)
     *     4. Zonas comunes (P2)
     *     5. Limpieza a fondo, oficina normal, inventario, otras (P3)
     */
    private function distribuirTareasEntreEmpleadas($empleadas, $tareas, $fecha)
    {
        $turnosGenerados = 0;
        $turnos = [];

        // ── Crear turnos para cada empleada ──
        foreach ($empleadas as $empleada) {
            $turno = TurnoTrabajo::create([
                'fecha'          => $fecha,
                'user_id'        => $empleada->user_id,
                'hora_inicio'    => $empleada->hora_inicio_atencion,
                'hora_fin'       => $empleada->hora_fin_atencion,
                'estado'         => 'programado',
                'fecha_creacion' => now(),
            ]);

            // [FIX 2026-04-17] Cap duro de 8 horas (480 min) por jornada,
            // independientemente de lo que indique horas_contratadas_dia en
            // la ficha de la empleada. El usuario reporta que hoy se asignan
            // mas tareas de las 8h reglamentarias.
            $maxMinutosJornada = 480; // 8 horas
            $contratadasMin = (int) ($empleada->horas_contratadas_dia * 60);
            $tiempoDisponible = min(max($contratadasMin, 0), $maxMinutosJornada);

            if ($contratadasMin > $maxMinutosJornada) {
                Log::warning("[TURNOS] {$empleada->user->name} tiene {$contratadasMin}min contratados, se capa a {$maxMinutosJornada}min (8h)");
            }

            $turnos[] = [
                'turno'             => $turno,
                'empleada'          => $empleada,
                'tiempo_disponible' => $tiempoDisponible,
                'tiempo_asignado'   => 0,
                'tareas_asignadas'  => 0,
                'orden_ejecucion'   => 1,
            ];

            $turnosGenerados++;
            Log::info("[TURNOS] Turno creado para {$empleada->user->name} ({$tiempoDisponible}min disponibles, max 8h/dia)");
        }

        // ── Separar tareas por nivel de prioridad ──
        $tareasP1Lavanderia = $tareas->where('subtipo', 'lavanderia');
        $tareasP1Checkout   = $tareas->where('subtipo', 'checkout');
        $tareasP1Oficina    = $tareas->where('subtipo', 'oficina_urgente');
        $tareasP2           = $tareas->where('nivel_prioridad', 'P2');
        $tareasP3           = $tareas->where('nivel_prioridad', 'P3')->sortByDesc('prioridad')->values();

        // ══════════════════════════════════════════════════
        // PASO 1: P1-A  LAVANDERIA (1h por cada limpiadora)
        // ══════════════════════════════════════════════════
        $tipoLavanderia = $tareasP1Lavanderia->first();
        if ($tipoLavanderia) {
            foreach ($turnos as $i => &$t) {
                if ($t['tiempo_asignado'] + 60 <= $t['tiempo_disponible']) {
                    TareaAsignada::create([
                        'turno_id'           => $t['turno']->id,
                        'tipo_tarea_id'      => $tipoLavanderia['tipo_tarea']->id,
                        'apartamento_id'     => null,
                        'zona_comun_id'      => null,
                        'prioridad_calculada' => 100,
                        'orden_ejecucion'    => $t['orden_ejecucion'],
                        'estado'             => 'pendiente',
                        'dias_sin_limpiar'   => 0,
                    ]);
                    $t['tiempo_asignado'] += 60;
                    $t['tareas_asignadas']++;
                    $t['orden_ejecucion']++;
                    Log::info("[TURNOS] P1 Lavandería asignada a {$t['empleada']->user->name}");
                }
            }
            unset($t);
        }

        // ══════════════════════════════════════════════════
        // PASO 2: P1-B  APARTAMENTOS CHECKOUT
        // ══════════════════════════════════════════════════
        // Agrupar por edificio para intentar mantener mismo edificio = misma limpiadora
        $checkoutPorEdificio = $this->agruparTareasPorEdificio($tareasP1Checkout);
        $apartamentosNoAsignados = [];

        foreach ($checkoutPorEdificio as $edificioId => $tareasEdificio) {
            $this->info("P1 Edificio {$edificioId}: " . count($tareasEdificio) . " checkouts");

            // Intentar asignar todo el edificio a una sola limpiadora
            $tiempoNecesario = count($tareasEdificio) * 60;
            $asignadoCompleto = false;

            // Buscar la limpiadora con menos carga que pueda asumir el edificio entero
            $mejorIdx = -1;
            $mejorCarga = PHP_INT_MAX;
            foreach ($turnos as $idx => $turno) {
                $restante = $turno['tiempo_disponible'] - $turno['tiempo_asignado'];
                if ($restante >= $tiempoNecesario && $turno['tiempo_asignado'] < $mejorCarga) {
                    $mejorIdx = $idx;
                    $mejorCarga = $turno['tiempo_asignado'];
                }
            }

            if ($mejorIdx >= 0) {
                foreach ($tareasEdificio as $tarea) {
                    TareaAsignada::create([
                        'turno_id'           => $turnos[$mejorIdx]['turno']->id,
                        'tipo_tarea_id'      => $tarea['tipo_tarea']->id,
                        'apartamento_id'     => $tarea['apartamento_id'],
                        'zona_comun_id'      => null,
                        'prioridad_calculada' => $tarea['prioridad'],
                        'orden_ejecucion'    => $turnos[$mejorIdx]['orden_ejecucion'],
                        'estado'             => 'pendiente',
                        'dias_sin_limpiar'   => 0,
                    ]);
                    $turnos[$mejorIdx]['tiempo_asignado'] += 60;
                    $turnos[$mejorIdx]['tareas_asignadas']++;
                    $turnos[$mejorIdx]['orden_ejecucion']++;
                }
                $asignadoCompleto = true;
                $this->info("  -> Edificio completo asignado a {$turnos[$mejorIdx]['empleada']->user->name}");
            }

            // Si no cabe completo, asignar apartamentos individualmente
            if (!$asignadoCompleto) {
                foreach ($tareasEdificio as $tarea) {
                    $asignado = false;
                    // Buscar limpiadora con menos carga que tenga al menos 60min
                    $mejorIdx2 = -1;
                    $mejorCarga2 = PHP_INT_MAX;
                    foreach ($turnos as $idx => $turno) {
                        $restante = $turno['tiempo_disponible'] - $turno['tiempo_asignado'];
                        if ($restante >= 60 && $turno['tiempo_asignado'] < $mejorCarga2) {
                            $mejorIdx2 = $idx;
                            $mejorCarga2 = $turno['tiempo_asignado'];
                        }
                    }

                    if ($mejorIdx2 >= 0) {
                        TareaAsignada::create([
                            'turno_id'           => $turnos[$mejorIdx2]['turno']->id,
                            'tipo_tarea_id'      => $tarea['tipo_tarea']->id,
                            'apartamento_id'     => $tarea['apartamento_id'],
                            'zona_comun_id'      => null,
                            'prioridad_calculada' => $tarea['prioridad'],
                            'orden_ejecucion'    => $turnos[$mejorIdx2]['orden_ejecucion'],
                            'estado'             => 'pendiente',
                            'dias_sin_limpiar'   => 0,
                        ]);
                        $turnos[$mejorIdx2]['tiempo_asignado'] += 60;
                        $turnos[$mejorIdx2]['tareas_asignadas']++;
                        $turnos[$mejorIdx2]['orden_ejecucion']++;
                        $asignado = true;
                    }

                    if (!$asignado) {
                        $apartamentosNoAsignados[] = $tarea['apartamento_id'];
                        $this->error("  ALERTA: Apartamento {$tarea['apartamento_id']} NO CABE en ninguna jornada");
                    }
                }
            }
        }

        // Alerta si hay apartamentos sin asignar
        if (!empty($apartamentosNoAsignados)) {
            $msg = "SOBRECARGA: " . count($apartamentosNoAsignados) . " apartamentos checkout no pudieron asignarse (IDs: " . implode(', ', $apartamentosNoAsignados) . ")";
            Log::warning("[TURNOS] {$msg}");
            $this->error($msg);
            try {
                \App\Services\AlertaEquipoService::alertar(
                    'Turnos Sobrecargados',
                    $msg . "\nFecha: {$fecha->format('Y-m-d')}\nSe necesita personal adicional.",
                    'urgente'
                );
            } catch (\Exception $e) {
                Log::error("[TURNOS] Error enviando alerta: " . $e->getMessage());
            }
        }

        // ══════════════════════════════════════════════════
        // PASO 3: P1-C  OFICINA URGENTE (+7 días)
        // ══════════════════════════════════════════════════
        foreach ($tareasP1Oficina as $tarea) {
            $this->asignarTareaAlMenosCargado($turnos, $tarea, 'P1 Oficina urgente');
        }

        // ══════════════════════════════════════════════════
        // PASO 4: P2  ZONAS COMUNES (60min cada una)
        // ══════════════════════════════════════════════════
        foreach ($tareasP2 as $tarea) {
            $this->asignarTareaAlMenosCargado($turnos, $tarea, 'P2 Zona Comun');
        }

        // ══════════════════════════════════════════════════
        // PASO 5: P3  TAREAS SECUNDARIAS (rellenar tiempo)
        // ══════════════════════════════════════════════════
        foreach ($tareasP3 as $tarea) {
            $this->asignarTareaAlMenosCargado($turnos, $tarea, 'P3 ' . ($tarea['subtipo'] ?? 'otra'));
        }

        // VALIDACION FINAL: verificar que ningun turno excede la jornada contratada
        foreach ($turnos as $t) {
            if ($t['tiempo_asignado'] > $t['tiempo_disponible']) {
                $exceso = $t['tiempo_asignado'] - $t['tiempo_disponible'];
                $msg = "EXCESO DETECTADO: {$t['empleada']->user->name} tiene {$t['tiempo_asignado']}min asignados pero solo {$t['tiempo_disponible']}min contratados (exceso: {$exceso}min)";
                Log::error("[TURNOS] {$msg}");
                $this->error($msg);
                try {
                    \App\Services\AlertaEquipoService::alertar(
                        'Turno Excede Jornada',
                        $msg . "\nFecha: {$fecha->format('Y-m-d')}",
                        'urgente'
                    );
                } catch (\Exception $e) {
                    Log::error("[TURNOS] Error enviando alerta de exceso: " . $e->getMessage());
                }
            }
        }

        // Log resumen por limpiadora
        foreach ($turnos as $t) {
            $pct = $t['tiempo_disponible'] > 0
                ? round(($t['tiempo_asignado'] / $t['tiempo_disponible']) * 100)
                : 0;
            Log::info("[TURNOS] {$t['empleada']->user->name}: {$t['tareas_asignadas']} tareas, {$t['tiempo_asignado']}/{$t['tiempo_disponible']}min ({$pct}%)");
        }

        return $turnosGenerados;
    }

    /**
     * Asignar una tarea a la limpiadora con menos carga que tenga tiempo suficiente.
     * No excede nunca la jornada contratada.
     */
    private function asignarTareaAlMenosCargado(array &$turnos, array $tarea, string $label = ''): bool
    {
        $tiempoNecesario = $tarea['tiempo_estimado'];
        $mejorIdx = -1;
        $mejorCarga = PHP_INT_MAX;

        foreach ($turnos as $idx => $turno) {
            $restante = $turno['tiempo_disponible'] - $turno['tiempo_asignado'];
            if ($restante >= $tiempoNecesario && $turno['tiempo_asignado'] < $mejorCarga) {
                $mejorIdx = $idx;
                $mejorCarga = $turno['tiempo_asignado'];
            }
        }

        if ($mejorIdx < 0) {
            Log::info("[TURNOS] {$label} '{$tarea['tipo_tarea']->nombre}' no asignada - sin capacidad");
            return false;
        }

        TareaAsignada::create([
            'turno_id'           => $turnos[$mejorIdx]['turno']->id,
            'tipo_tarea_id'      => $tarea['tipo_tarea']->id,
            'apartamento_id'     => $tarea['apartamento_id'] ?? null,
            'zona_comun_id'      => $tarea['zona_comun_id'] ?? null,
            'prioridad_calculada' => $tarea['prioridad'],
            'orden_ejecucion'    => $turnos[$mejorIdx]['orden_ejecucion'],
            'estado'             => 'pendiente',
            'dias_sin_limpiar'   => 0,
        ]);

        $turnos[$mejorIdx]['tiempo_asignado'] += $tiempoNecesario;
        $turnos[$mejorIdx]['tareas_asignadas']++;
        $turnos[$mejorIdx]['orden_ejecucion']++;

        Log::info("[TURNOS] {$label} '{$tarea['tipo_tarea']->nombre}' -> {$turnos[$mejorIdx]['empleada']->user->name}");
        return true;
    }
    
    /**
     * Verificar si la oficina (apartamento_id=18) lleva +7 días sin limpiar.
     * Devuelve true si es urgente (necesita limpieza P1).
     */
    private function verificarOficinaUrgente(): bool
    {
        $ultimaLimpieza = ApartamentoLimpieza::where('apartamento_id', 18)
            ->whereIn('status_id', [3, 4]) // completada / finalizada
            ->orderByDesc('fecha_comienzo')
            ->first();

        if (!$ultimaLimpieza) {
            Log::info('[TURNOS] Oficina: nunca limpiada -> URGENTE');
            return true;
        }

        $dias = Carbon::parse($ultimaLimpieza->fecha_comienzo)->diffInDays(now());
        Log::info("[TURNOS] Oficina: ultima limpieza hace {$dias} dias");

        return $dias > 7;
    }

    /**
     * Contar cuantas limpiezas a fondo se han realizado este mes.
     * El objetivo es un minimo de 2 al mes.
     */
    private function verificarLimpiezaFondoMensual(): int
    {
        $count = \App\Models\LimpiezaFondo::whereYear('fecha', now()->year)
            ->whereMonth('fecha', now()->month)
            ->count();

        Log::info("[TURNOS] Limpiezas a fondo este mes: {$count}/2");
        return $count;
    }

    /**
     * Obtener un apartamento que no haya tenido limpieza a fondo recientemente.
     * Devuelve el apartamento con mas tiempo sin limpieza a fondo.
     */
    private function obtenerApartamentoParaLimpiezaFondo()
    {
        // Todos los apartamentos activos, excluyendo oficina (18)
        $apartamentos = Apartamento::where('id', '!=', 18)
            ->whereNotIn('id', [16, 17, 19, 20, 22, 23]) // Excluir escaleras, oficina, lavanderia, test
            ->get();

        $mejorApto = null;
        $maxDias = -1;

        foreach ($apartamentos as $apto) {
            $ultimaFondo = \App\Models\LimpiezaFondo::where('apartamento_id', $apto->id)
                ->orderByDesc('fecha')
                ->first();

            $dias = $ultimaFondo
                ? Carbon::parse($ultimaFondo->fecha)->diffInDays(now())
                : 999;

            if ($dias > $maxDias) {
                $maxDias = $dias;
                $mejorApto = $apto;
            }
        }

        if ($mejorApto) {
            Log::info("[TURNOS] Apartamento para limpieza a fondo: {$mejorApto->id} ({$maxDias} dias sin fondo)");
        }

        return $mejorApto;
    }

    /**
     * Mostrar resumen de turnos generados
     */
    private function mostrarResumen($fecha)
    {
        $turnos = TurnoTrabajo::porFecha($fecha)
            ->with(['user', 'tareasAsignadas.tipoTarea'])
            ->get();
            
        $this->info("\n📊 RESUMEN DE TURNOS GENERADOS:");
        $this->info("Fecha: {$fecha->format('Y-m-d')}");
        $this->info("Total turnos: {$turnos->count()}");
        
        $totalTareas = $turnos->sum(function($turno) {
            return $turno->tareasAsignadas->count();
        });
        
        $this->info("Total tareas: {$totalTareas}");
        
        $this->info("\n👥 DETALLE POR EMPLEADA:");
        foreach ($turnos as $turno) {
            $tareasCount = $turno->tareasAsignadas->count();
            $tiempoEstimado = $turno->tareasAsignadas->sum(function($tarea) {
                return $tarea->tipoTarea->tiempo_estimado_minutos;
            });
            $tiempoFormateado = $this->formatearTiempo($tiempoEstimado);
            
            // Obtener jornada contratada
            $empleadaHorario = EmpleadaHorario::where('user_id', $turno->user_id)->first();
            $jornadaContratada = $empleadaHorario ? $empleadaHorario->horas_contratadas_dia : 0;
            
            // Si solo hay una empleada, usar 8 horas como jornada efectiva
            $jornadaEfectiva = $turnos->count() === 1 ? 8 : $jornadaContratada;
            $jornadaEfectivaMinutos = $jornadaEfectiva * 60;
            
            // Verificar si se sobrepasa la jornada efectiva
            $sobrepasaJornada = $tiempoEstimado > $jornadaEfectivaMinutos;
            $estadoJornada = $sobrepasaJornada ? "⚠️  SOBREPASA JORNADA" : "✅ DENTRO DE JORNADA";
            
            $this->info("- {$turno->user->name}: {$tareasCount} tareas ({$tiempoFormateado}) - Jornada: {$jornadaEfectiva}h - {$estadoJornada}");
            
            // Mostrar detalle de tareas
            $tareasApartamentos = $turno->tareasAsignadas->filter(function($tarea) {
                return $tarea->apartamento_id !== null;
            });
            $tareasGenerales = $turno->tareasAsignadas->filter(function($tarea) {
                return $tarea->apartamento_id === null;
            });
            
            $this->info("  📍 Apartamentos: {$tareasApartamentos->count()} tareas (prioridad alta)");
            $this->info("  🏢 Generales: {$tareasGenerales->count()} tareas (respetando jornada)");
            
            if ($sobrepasaJornada) {
                $this->warn("  ⚠️  ADVERTENCIA: Se sobrepasa la jornada contratada");
            }
        }
    }
    
    /**
     * Agrupar tareas de apartamentos por edificio
     */
    private function agruparTareasPorEdificio($tareasApartamentos)
    {
        $tareasPorEdificio = [];
        
        foreach ($tareasApartamentos as $tarea) {
            $apartamento = \App\Models\Apartamento::find($tarea['apartamento_id']);
            $edificioId = $apartamento ? $apartamento->edificio_id : 'sin_edificio';
            
            if (!isset($tareasPorEdificio[$edificioId])) {
                $tareasPorEdificio[$edificioId] = [];
            }
            
            $tareasPorEdificio[$edificioId][] = $tarea;
        }
        
        // Ordenar por número de apartamentos (edificios con más apartamentos primero)
        uasort($tareasPorEdificio, function($a, $b) {
            return count($b) - count($a);
        });
        
        return $tareasPorEdificio;
    }
    
    /**
     * Intentar asignar un edificio completo a una empleada
     */
    private function asignarEdificioCompleto(&$turnos, $tareasEdificio, $ordenEjecucion)
    {
        $tiempoTotalEdificio = collect($tareasEdificio)->sum('tiempo_estimado');
        
        // Buscar empleada que pueda asumir todo el edificio, priorizando balance de carga
        $mejorOpcion = null;
        $mejorIndex = -1;
        $mejorBalance = PHP_INT_MAX;
        
        foreach ($turnos as $index => $turno) {
            if ($turno['tiempo_asignado'] + $tiempoTotalEdificio <= $turno['tiempo_disponible']) {
                // Calcular balance de carga (tiempo asignado actual)
                $balanceActual = $turno['tiempo_asignado'];
                
                // Preferir empleada con menos carga actual
                if ($balanceActual < $mejorBalance) {
                    $mejorOpcion = $turno;
                    $mejorIndex = $index;
                    $mejorBalance = $balanceActual;
                }
            }
        }
        
        // Si encontramos una empleada que pueda asumir el edificio completo
        if ($mejorOpcion) {
            // Asignar todas las tareas del edificio a esta empleada
            foreach ($tareasEdificio as $tarea) {
                TareaAsignada::create([
                    'turno_id' => $mejorOpcion['turno']->id,
                    'tipo_tarea_id' => $tarea['tipo_tarea']->id,
                    'apartamento_id' => $tarea['apartamento_id'],
                    'zona_comun_id' => $tarea['zona_comun_id'] ?? null,
                    'prioridad_calculada' => $tarea['prioridad'],
                    'orden_ejecucion' => $ordenEjecucion,
                    'estado' => 'pendiente',
                    'dias_sin_limpiar' => 0
                ]);
                
                $turnos[$mejorIndex]['tiempo_asignado'] += $tarea['tiempo_estimado'];
                $turnos[$mejorIndex]['tareas_asignadas']++;
                $ordenEjecucion++;
            }
            
            return [
                'asignado' => true,
                'empleada' => $mejorOpcion['empleada']->user->name,
                'ordenEjecucion' => $ordenEjecucion
            ];
        }
        
        return ['asignado' => false];
    }
    
    /**
     * Asignar una tarea individual a la mejor empleada disponible
     */
    private function asignarTareaIndividual(&$turnos, $tarea, $ordenEjecucion)
    {
        $turnoSeleccionado = null;
        $turnoIndex = -1;
        
        foreach ($turnos as $index => $turno) {
            if ($turno['tiempo_asignado'] + $tarea['tiempo_estimado'] <= $turno['tiempo_disponible']) {
                if (!$turnoSeleccionado || $turno['tiempo_asignado'] < $turnoSeleccionado['tiempo_asignado']) {
                    $turnoSeleccionado = $turno;
                    $turnoIndex = $index;
                }
            }
        }
        
        if (!$turnoSeleccionado) {
            $this->warn("⚠️  Apartamento '{$tarea['apartamento_id']}' no asignado - jornada completa");
            return false;
        }
        
        // Crear la tarea asignada
        TareaAsignada::create([
            'turno_id' => $turnoSeleccionado['turno']->id,
            'tipo_tarea_id' => $tarea['tipo_tarea']->id,
            'apartamento_id' => $tarea['apartamento_id'],
            'zona_comun_id' => $tarea['zona_comun_id'] ?? null,
            'prioridad_calculada' => $tarea['prioridad'],
            'orden_ejecucion' => $ordenEjecucion,
            'estado' => 'pendiente',
            'dias_sin_limpiar' => 0
        ]);
        
        $turnos[$turnoIndex]['tiempo_asignado'] += $tarea['tiempo_estimado'];
        $turnos[$turnoIndex]['tareas_asignadas']++;
        
        return true;
    }
    
    /**
     * Generar asignación optimizada usando OpenAI
     */
    private function generarAsignacionConIA($empleadas, $tareas, $fecha)
    {
        try {
            // Preparar datos para OpenAI
            $datosIA = $this->prepararDatosParaIA($empleadas, $tareas, $fecha);
            
            // Crear prompt para OpenAI
            $prompt = $this->crearPromptParaIA($datosIA);
            
            $this->info("🤖 Enviando datos a OpenAI para optimización...");
            $this->info("📊 Datos enviados:");
            $this->info(json_encode($datosIA, JSON_PRETTY_PRINT));
            
            // Llamar a OpenAI
            $respuestaIA = $this->llamarOpenAI($prompt);
            
            $this->info("🤖 Respuesta de OpenAI:");
            $this->info($respuestaIA);
            
            // Parsear respuesta JSON
            $asignacionIA = json_decode($respuestaIA, true);
            
            if (!$asignacionIA) {
                throw new \Exception("Error al parsear respuesta de OpenAI");
            }
            
            return $asignacionIA;
            
        } catch (\Exception $e) {
            $this->error("❌ Error en generación con IA: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Preparar datos estructurados para OpenAI
     */
    private function prepararDatosParaIA($empleadas, $tareas, $fecha = null)
    {
        // Obtener información del día de la semana
        $fechaCarbon = $fecha ? Carbon::parse($fecha) : today();
        $diaSemana = $fechaCarbon->dayOfWeek; // 0 = domingo, 1 = lunes, etc.
        $esFinDeSemana = $diaSemana == 0 || $diaSemana == 6; // Domingo o sábado
        $nombreDia = $fechaCarbon->locale('es')->dayName;
        
        // Separar tareas por categorías
        $tareasApartamentos = $tareas->filter(function($tarea) {
            return $tarea['apartamento_id'] !== null;
        });
        
        $tareasZonasComunes = $tareas->filter(function($tarea) {
            return isset($tarea['zona_comun_id']) && $tarea['zona_comun_id'] !== null;
        });
        
        // Las tareas de mantenimiento ahora están integradas en limpieza de zonas comunes por edificio
        $tareasMantenimiento = $tareas->filter(function($tarea) {
            return $tarea['apartamento_id'] === null && (!isset($tarea['zona_comun_id']) || $tarea['zona_comun_id'] === null) && isset($tarea['edificio_id']);
        });
        
        $tareasGenerales = $tareas->filter(function($tarea) {
            return $tarea['apartamento_id'] === null && (!isset($tarea['zona_comun_id']) || $tarea['zona_comun_id'] === null) && !isset($tarea['edificio_id']);
        });
        
        // Agrupar apartamentos por edificio
        $apartamentosPorEdificio = [];
        foreach ($tareasApartamentos as $tarea) {
            $apartamento = \App\Models\Apartamento::find($tarea['apartamento_id']);
            $edificioId = $apartamento ? $apartamento->edificio_id : 'sin_edificio';
            
            if (!isset($apartamentosPorEdificio[$edificioId])) {
                $apartamentosPorEdificio[$edificioId] = [
                    'edificio_id' => $edificioId,
                    'nombre' => $apartamento ? $apartamento->edificio->nombre ?? "Edificio {$edificioId}" : 'Sin edificio',
                    'apartamentos' => [],
                    'tiempo_total' => 0,
                    'prioridad_maxima' => 0
                ];
            }
            
            $apartamentosPorEdificio[$edificioId]['apartamentos'][] = [
                'apartamento_id' => $tarea['apartamento_id'],
                'apartamento_titulo' => $apartamento ? $apartamento->titulo : 'Sin título',
                'tiempo_estimado' => $tarea['tiempo_estimado'],
                'prioridad' => $tarea['prioridad'],
                'motivo_prioridad' => $this->obtenerMotivoPrioridadApartamento($apartamento)
            ];
            
            $apartamentosPorEdificio[$edificioId]['tiempo_total'] += $tarea['tiempo_estimado'];
            $apartamentosPorEdificio[$edificioId]['prioridad_maxima'] = max(
                $apartamentosPorEdificio[$edificioId]['prioridad_maxima'], 
                $tarea['prioridad']
            );
        }
        
        // Procesar tareas generales con priorización dinámica
        $tareasGeneralesProcesadas = [];
        foreach ($tareasGenerales as $tarea) {
            $tareaInfo = [
                'id' => $tarea['tipo_tarea']->id,
                'tipo' => $tarea['tipo_tarea']->nombre,
                'categoria' => $tarea['tipo_tarea']->categoria,
                'tiempo_estimado' => $tarea['tiempo_estimado'],
                'prioridad_base' => $tarea['tipo_tarea']->prioridad_base,
                'prioridad_calculada' => $tarea['prioridad'],
                'dias_max_sin_limpiar' => $tarea['tipo_tarea']->dias_max_sin_limpiar,
                'incremento_prioridad_por_dia' => $tarea['tipo_tarea']->incremento_prioridad_por_dia,
                'prioridad_maxima' => $tarea['tipo_tarea']->prioridad_maxima,
                'ultima_ejecucion' => $this->obtenerUltimaEjecucionTarea($tarea['tipo_tarea']->id),
                'dias_desde_ultima_ejecucion' => $this->calcularDiasDesdeUltimaEjecucion($tarea['tipo_tarea']->id),
                'necesita_urgente' => $this->necesitaUrgente($tarea['tipo_tarea']),
                'motivo_prioridad' => $this->obtenerMotivoPrioridadTarea($tarea['tipo_tarea'])
            ];
            
            $tareasGeneralesProcesadas[] = $tareaInfo;
        }
        
        // Procesar zonas comunes
        $zonasComunesProcesadas = [];
        foreach ($tareasZonasComunes as $tarea) {
            $zonaComun = \App\Models\ZonaComun::find($tarea['zona_comun_id']);
            $zonasComunesProcesadas[] = [
                'id' => $tarea['tipo_tarea']->id,
                'tipo' => $tarea['tipo_tarea']->nombre,
                'zona_comun_id' => $tarea['zona_comun_id'] ?? null,
                'zona_nombre' => $zonaComun ? $zonaComun->nombre : 'Sin nombre',
                'tiempo_estimado' => $tarea['tiempo_estimado'],
                'prioridad' => $tarea['prioridad'],
                'motivo_prioridad' => 'Limpieza de zona común programada'
            ];
        }
        
        // Procesar tareas de limpieza común por edificio (SIEMPRE OBLIGATORIAS)
        $limpiezaComunProcesadas = [];
        foreach ($tareasMantenimiento as $tarea) {
            $limpiezaComunProcesadas[] = [
                'id' => $tarea['tipo_tarea']->id,
                'tipo' => $tarea['tipo_tarea']->nombre,
                'edificio_id' => $tarea['edificio_id'],
                'tiempo_estimado' => $tarea['tiempo_estimado'],
                'prioridad' => $tarea['prioridad'],
                'motivo_prioridad' => $tarea['motivo_prioridad'] ?? 'Limpieza común edificio - SIEMPRE OBLIGATORIA'
            ];
        }
        
        return [
            'fecha' => $fechaCarbon->format('Y-m-d'),
            'dia_semana' => $nombreDia,
            'es_fin_de_semana' => $esFinDeSemana,
            'reglas_especiales' => [
                'fines_semana' => [
                    'empleada_4_horas' => 'DEBE trabajar 8 horas (o más si hay muchos apartamentos)',
                    'apartamentos_prioridad' => 'Los apartamentos SIEMPRE se limpian aunque se sobrepasen las horas contratadas',
                    'prioridad_absoluta' => 'TODOS los apartamentos de salida'
                ],
                'entre_semana' => [
                    'empleada_4_horas' => 'MÍNIMO 6 horas (no 4 ni 8)',
                    'balance_carga' => 'Balancear carga entre ambas empleadas',
                    'solo_una_empleada' => 'Trabaja su jornada normal'
                ]
            ],
            'empleadas' => $empleadas->map(function($empleada) use ($empleadas, $esFinDeSemana) {
                // Lógica especial para fines de semana
                if ($esFinDeSemana && $empleada->horas_contratadas_dia == 4) {
                    $tiempoDisponible = 8 * 60; // 8 horas en fines de semana
                } elseif ($empleadas->count() === 1) {
                    $tiempoDisponible = 8 * 60; // 8 horas si es la única
                } else {
                    $tiempoDisponible = $empleada->horas_contratadas_dia * 60;
                }
                
                return [
                    'id' => $empleada->id,
                    'nombre' => $empleada->user->name,
                    'jornada_horas' => $empleada->horas_contratadas_dia,
                    'jornada_minutos' => $empleada->horas_contratadas_dia * 60,
                    'tiempo_disponible' => $tiempoDisponible,
                    'dias_trabajo_semana' => $empleada->numero_dias_trabajo,
                    'regla_especial' => $esFinDeSemana && $empleada->horas_contratadas_dia == 4 ? 'FIN DE SEMANA: 8 horas obligatorias' : null
                ];
            })->toArray(),
            'edificios' => array_values($apartamentosPorEdificio),
            'zonas_comunes' => $zonasComunesProcesadas,
            'limpieza_comun_edificios' => $limpiezaComunProcesadas,
            'tareas_generales' => $tareasGeneralesProcesadas,
            'reglas_priorizacion' => [
                'prioridad_maxima' => 'Apartamentos y zonas comunes (prioridad 10)',
                'prioridad_alta' => 'Limpieza común de edificios (SIEMPRE OBLIGATORIA) y tareas urgentes',
                'prioridad_media' => 'Tareas con prioridad base normal',
                'prioridad_baja' => 'Tareas recientemente ejecutadas (< 7 días)',
                'relleno_jornada' => 'Usar tareas de menor prioridad para completar jornadas'
            ],
            'constraints' => [
                'respetar_jornada_estricto' => true,
                'priorizar_edificios_completos' => true,
                'balancear_carga_trabajo' => true,
                'max_tiempo_por_empleada' => 'No sobrepasar jornada contratada',
                'regla_especial_una_empleada' => 'Si solo hay una empleada disponible, asignar 8 horas completas'
            ]
        ];
    }
    
    /**
     * Obtener motivo de prioridad para apartamento
     */
    private function obtenerMotivoPrioridadApartamento($apartamento)
    {
        if (!$apartamento) return 'Apartamento no encontrado';
        
        $reservaSalidaHoy = \App\Models\Reserva::where('apartamento_id', $apartamento->id)
            ->whereNull('fecha_limpieza')
            ->where('estado_id', '!=', 4)
            ->whereDate('fecha_salida', now()->toDateString())
            ->first();
            
        if ($reservaSalidaHoy) {
            return 'Reserva sale hoy - MÁXIMA PRIORIDAD';
        }
        
        $limpiezaFondo = \App\Models\LimpiezaFondo::where('apartamento_id', $apartamento->id)
            ->whereDate('fecha', now()->toDateString())
            ->first();
            
        if ($limpiezaFondo) {
            return 'Limpieza de fondo programada';
        }
        
        return 'Limpieza pendiente';
    }
    
    /**
     * Obtener última ejecución de una tarea
     */
    private function obtenerUltimaEjecucionTarea($tipoTareaId)
    {
        $ultimaTarea = \App\Models\TareaAsignada::where('tipo_tarea_id', $tipoTareaId)
            ->where('estado', 'completada')
            ->whereNotNull('fecha_ultima_limpieza')
            ->orderBy('fecha_ultima_limpieza', 'desc')
            ->first();
            
        return $ultimaTarea ? $ultimaTarea->fecha_ultima_limpieza->format('Y-m-d H:i') : 'Nunca ejecutada';
    }
    
    /**
     * Calcular días desde última ejecución
     */
    private function calcularDiasDesdeUltimaEjecucion($tipoTareaId)
    {
        $ultimaTarea = \App\Models\TareaAsignada::where('tipo_tarea_id', $tipoTareaId)
            ->where('estado', 'completada')
            ->whereNotNull('fecha_ultima_limpieza')
            ->orderBy('fecha_ultima_limpieza', 'desc')
            ->first();
            
        if (!$ultimaTarea) {
            return 999; // Nunca ejecutada = muy urgente
        }
        
        return Carbon::parse($ultimaTarea->fecha_ultima_limpieza)->diffInDays(now());
    }
    
    /**
     * Verificar si una tarea necesita ser urgente
     */
    private function necesitaUrgente($tipoTarea)
    {
        $diasSinLimpiar = $this->calcularDiasDesdeUltimaEjecucion($tipoTarea->id);
        
        // Si es limpieza de oficina y se hizo hace menos de 7 días, bajar prioridad
        if ($tipoTarea->categoria === 'limpieza_oficina' && $diasSinLimpiar < 7) {
            return false; // No urgente
        }
        
        // Si supera los días máximos sin limpiar, es urgente
        if ($diasSinLimpiar >= $tipoTarea->dias_max_sin_limpiar) {
            return true; // Urgente
        }
        
        return false;
    }
    
    /**
     * Obtener motivo de prioridad para tarea general
     */
    private function obtenerMotivoPrioridadTarea($tipoTarea)
    {
        $diasSinLimpiar = $this->calcularDiasDesdeUltimaEjecucion($tipoTarea->id);
        
        if ($diasSinLimpiar === 999) {
            return 'Nunca ejecutada - URGENTE';
        }
        
        if ($tipoTarea->categoria === 'limpieza_oficina' && $diasSinLimpiar < 7) {
            return "Ejecutada hace {$diasSinLimpiar} días - Prioridad baja";
        }
        
        if ($diasSinLimpiar >= $tipoTarea->dias_max_sin_limpiar) {
            return "Sin limpiar {$diasSinLimpiar} días - URGENTE";
        }
        
        if ($diasSinLimpiar > 0) {
            return "Ejecutada hace {$diasSinLimpiar} días";
        }
        
        return 'Prioridad base';
    }
    
    /**
     * Crear prompt optimizado para OpenAI
     */
    private function crearPromptParaIA($datos)
    {
        return "Eres un experto en optimización de recursos humanos para empresas de limpieza.

OBJETIVO: Asignar tareas de limpieza de apartamentos, zonas comunes y tareas generales a empleadas de forma óptima.

REGLAS DE PRIORIZACIÓN (EN ORDEN DE IMPORTANCIA):
1. MÁXIMA PRIORIDAD: Apartamentos y zonas comunes (prioridad 10) - SIEMPRE se asignan primero
2. ALTA PRIORIDAD: Mantenimiento de edificios (una tarea por cada edificio con apartamentos)
3. MEDIA PRIORIDAD: Tareas urgentes por tiempo sin ejecutar (superan días máximos sin limpiar)
4. BAJA PRIORIDAD: Tareas recientemente ejecutadas (< 7 días, especialmente limpieza de oficina)
5. RELLENO: Usar tareas de menor prioridad para completar jornadas

REGLAS DE ASIGNACIÓN:
1. PRIORIZAR: Asignar edificios completos a la misma empleada
2. RESPETAR: Jornada laboral estricta (NO sobrepasar horas contratadas)
3. ESPECIAL: Si solo hay UNA empleada disponible, asignar 8 horas completas (no su jornada normal)
4. BALANCEAR: Distribuir carga de trabajo equitativamente entre múltiples empleadas
5. EFICIENCIA: Agrupar tareas por ubicación cuando sea posible

REGLAS ESPECÍFICAS POR DÍA DE LA SEMANA:
- FINES DE SEMANA (SÁBADO Y DOMINGO):
  * Empleada de 4 horas: DEBE trabajar 8 horas (o más si hay muchos apartamentos)
  * Los apartamentos SIEMPRE se limpian aunque se sobrepasen las horas contratadas
  * Prioridad absoluta: TODOS los apartamentos de salida
- ENTRE SEMANA (LUNES A VIERNES):
  * Si hay 2 empleadas disponibles: empleada de 4 horas trabaja MÍNIMO 6 horas (no 4 ni 8)
  * Si solo hay 1 empleada: trabaja su jornada normal
  * Balancear carga entre ambas empleadas

REGLAS ESPECÍFICAS CRÍTICAS:
1. LAVANDERÍA Y COCINA COMUNITARIA: SIEMPRE asignar a la empleada con MÁS HORAS contratadas
2. EDIFICIO COSTA: La empleada con más horas debe trabajar preferentemente en Edificio Costa
3. TAREAS PRIORIDAD 10: Solo la empleada con más horas puede recibir tareas de prioridad 10
4. ORDEN EMPLEADAS: Procesar empleadas ordenadas por horas contratadas (más horas primero)

LÓGICA ESPECIAL PARA LIMPIEZA DE OFICINA:
- Si se ejecutó hace menos de 7 días: BAJAR prioridad
- Si no se ha ejecutado o supera días máximos: SUBIR a urgente

DATOS DE ENTRADA:
" . json_encode($datos, JSON_PRETTY_PRINT) . "

INSTRUCCIONES DETALLADAS:
1. PRIMERO: Asignar TODOS los apartamentos (prioridad máxima) - OBLIGATORIO
2. SEGUNDO: Asignar limpieza de zonas comunes por edificio (incluye mantenimiento)
3. TERCERO: Asignar tareas urgentes por tiempo sin ejecutar
4. CUARTO: Asignar tareas de prioridad media
5. QUINTO: Usar tareas de baja prioridad para rellenar jornadas
6. RESPETAR: Jornada estricta - nunca sobrepasar tiempo disponible
7. OPTIMIZAR: Agrupar por edificio cuando sea posible

CRÍTICO: TODOS los apartamentos de salida DEBEN ser asignados. No dejar ningún apartamento sin asignar.

LÓGICA ESPECIAL PARA APARTAMENTOS:
- FINES DE SEMANA: Los apartamentos tienen prioridad ABSOLUTA sobre las horas contratadas
- ENTRE SEMANA: Respetar horas contratadas pero asignar MÍNIMO 6 horas a empleada de 4 horas
- SIEMPRE: Todos los apartamentos de salida se limpian, sin excepción

INSTRUCCIONES CRÍTICAS OBLIGATORIAS:
- APARTAMENTOS: TODOS los apartamentos de salida DEBEN ser asignados (no opcional)
- LAVANDERÍA Y COCINA COMUNITARIA: SOLO a la empleada con MÁS HORAS
- EDIFICIO COSTA: Asignar a la empleada con más horas cuando sea posible
- TAREAS PRIORIDAD 10: Exclusivas de la empleada con más horas
- ORDEN: Procesar empleadas por horas contratadas (descendente)

VALIDACIÓN OBLIGATORIA: Al final, verificar que TODOS los apartamentos de salida estén asignados.

FORMATO DE RESPUESTA (JSON):
{
  \"asignaciones\": [
    {
      \"empleada_id\": 1,
      \"empleada_nombre\": \"Test Limpieadoras\",
      \"edificios_asignados\": [1],
      \"tareas_apartamentos\": [
        {
          \"apartamento_id\": 101,
          \"edificio_id\": 1,
          \"tiempo_estimado\": 60,
          \"prioridad\": 10,
          \"motivo_prioridad\": \"Reserva sale hoy - MÁXIMA PRIORIDAD\",
          \"orden_ejecucion\": 1
        }
      ],
      \"tareas_zonas_comunes\": [
        {
          \"zona_comun_id\": 1,
          \"tiempo_estimado\": 45,
          \"prioridad\": 10,
          \"orden_ejecucion\": 2
        }
      ],
      \"tareas_zonas_comunes\": [
        {
          \"tarea_id\": 3,
          \"tipo\": \"Limpieza Zona Común\",
          \"edificio_id\": 1,
          \"tiempo_estimado\": 45,
          \"prioridad\": 10,
          \"orden_ejecucion\": 2
        }
      ],
      \"tareas_generales\": [
        {
          \"tarea_id\": 1,
          \"tipo\": \"Limpieza de Oficina\",
          \"tiempo_estimado\": 60,
          \"prioridad_calculada\": 3,
          \"motivo_prioridad\": \"Ejecutada hace 3 días - Prioridad baja\",
          \"orden_ejecucion\": 5
        }
      ],
      \"tiempo_total_asignado\": 420,
      \"tiempo_disponible\": 480,
      \"eficiencia\": \"Edificio completo asignado\"
    }
  ],
  \"resumen\": {
    \"total_empleadas_utilizadas\": 2,
    \"edificios_completos_asignados\": 2,
    \"tareas_prioridad_maxima\": 7,
    \"tareas_urgentes\": 1,
    \"tareas_relleno\": 2,
    \"tiempo_total_optimizado\": 660,
    \"eficiencia_global\": \"Alta - Edificios agrupados\"
  }
}

Responde SOLO con el JSON, sin texto adicional.";
    }
    
    /**
     * Generar respuesta simulada de IA para pruebas
     */
    private function generarRespuestaSimuladaIA($empleadas, $tareas)
    {
        $this->info("🧪 Generando respuesta simulada de IA...");
        
        // Separar tareas
        $tareasApartamentos = $tareas->filter(function($tarea) {
            return $tarea['apartamento_id'] !== null;
        });
        
        $tareasMantenimiento = $tareas->filter(function($tarea) {
            return $tarea['apartamento_id'] === null && isset($tarea['edificio_id']);
        });
        
        $tareasGenerales = $tareas->filter(function($tarea) {
            return $tarea['apartamento_id'] === null && !isset($tarea['edificio_id']);
        });
        
        // Ordenar tareas generales por prioridad (mayor primero) para respetar el orden correcto
        $tareasGenerales = $tareasGenerales->sortByDesc('prioridad')->values();
        
        // Agrupar apartamentos por edificio
        $apartamentosPorEdificio = [];
        foreach ($tareasApartamentos as $tarea) {
            $apartamento = \App\Models\Apartamento::find($tarea['apartamento_id']);
            $edificioId = $apartamento ? $apartamento->edificio_id : 'sin_edificio';
            
            if (!isset($apartamentosPorEdificio[$edificioId])) {
                $apartamentosPorEdificio[$edificioId] = [];
            }
            
            $apartamentosPorEdificio[$edificioId][] = $tarea;
        }
        
        $asignaciones = [];
        // Ordenar empleadas por horas contratadas (más horas primero) para asignar tareas importantes primero
        $empleadasArray = $empleadas->sortByDesc('horas_contratadas_dia')->values()->toArray();
        
        // Simular asignación optimizada
        $edificioIndex = 0;
        foreach ($empleadasArray as $index => $empleada) {
            $asignacion = [
                'empleada_id' => $empleada['id'],
                'empleada_nombre' => $empleada['user']['name'],
                'edificios_asignados' => [],
                'tareas_apartamentos' => [],
                'tareas_zonas_comunes' => [],
                'tareas_generales' => [],
                'tiempo_total_asignado' => 0,
                'tiempo_disponible' => $empleadas->count() === 1 ? 8 * 60 : $empleada['horas_contratadas_dia'] * 60, // 8 horas si es la única
                'eficiencia' => 'Asignación optimizada por IA'
            ];
            
            // Asignar edificio si hay disponibles
            if (!empty($apartamentosPorEdificio)) {
                $edificios = array_keys($apartamentosPorEdificio);
                
                // Si es la primera empleada (más horas), priorizar Edificio Costa
                if ($index === 0) {
                    $edificioCosta = collect($apartamentosPorEdificio)->keys()->first(function($key) {
                        return strpos($key, 'Costa') !== false || strpos($key, '2') !== false;
                    });
                    
                    if ($edificioCosta) {
                        $edificioId = $edificioCosta;
                    } else {
                        $edificioId = $edificios[0];
                    }
                } else {
                    $edificioId = $edificios[0];
                }
                
                $tareasEdificio = $apartamentosPorEdificio[$edificioId];
                
                $tiempoEdificio = collect($tareasEdificio)->sum('tiempo_estimado');
                
                // Solo asignar si cabe en la jornada
                if ($tiempoEdificio <= $asignacion['tiempo_disponible']) {
                    $asignacion['edificios_asignados'][] = $edificioId;
                    
                    $ordenEjecucion = 1;
                    foreach ($tareasEdificio as $tarea) {
                        $asignacion['tareas_apartamentos'][] = [
                            'apartamento_id' => $tarea['apartamento_id'],
                            'edificio_id' => $edificioId,
                            'tiempo_estimado' => $tarea['tiempo_estimado'],
                            'prioridad' => $tarea['prioridad'],
                            'orden_ejecucion' => $ordenEjecucion
                        ];
                        
                        $asignacion['tiempo_total_asignado'] += $tarea['tiempo_estimado'];
                        $ordenEjecucion++;
                    }
                    
                    // Remover el edificio asignado del pool para que no se asigne a otra empleada
                    unset($apartamentosPorEdificio[$edificioId]);
                }
            }
            
            // Asignar tarea de limpieza común del edificio asignado (SIEMPRE OBLIGATORIA)
            if ($edificioIndex > 0 && $edificioIndex <= count($apartamentosPorEdificio)) {
                $edificios = array_keys($apartamentosPorEdificio);
                $edificioId = $edificios[$edificioIndex - 1];
                
                // Buscar tarea de limpieza común para este edificio
                $tareaLimpiezaComun = $tareasMantenimiento->first(function($tarea) use ($edificioId) {
                    return $tarea['edificio_id'] == $edificioId;
                });
                
                if ($tareaLimpiezaComun && $tareaLimpiezaComun['tiempo_estimado'] <= $asignacion['tiempo_disponible'] - $asignacion['tiempo_total_asignado']) {
                    $asignacion['tareas_zonas_comunes'][] = [
                        'tarea_id' => $tareaLimpiezaComun['tipo_tarea']->id,
                        'tipo' => $tareaLimpiezaComun['tipo_tarea']->nombre,
                        'edificio_id' => $edificioId,
                        'tiempo_estimado' => $tareaLimpiezaComun['tiempo_estimado'],
                        'prioridad' => $tareaLimpiezaComun['prioridad'],
                        'orden_ejecucion' => count($asignacion['tareas_apartamentos']) + 1
                    ];
                    
                    $asignacion['tiempo_total_asignado'] += $tareaLimpiezaComun['tiempo_estimado'];
                }
            }
            
        // Asignar tareas generales para completar jornada
        $tiempoRestante = $asignacion['tiempo_disponible'] - $asignacion['tiempo_total_asignado'];
        $ordenEjecucion = count($asignacion['tareas_apartamentos']) + 1;
        
        // Lógica inteligente: priorizar tareas de alta prioridad (10) a la empleada con más horas
        // Iterar sobre una copia para poder remover tareas ya asignadas
        $tareasGeneralesDisponibles = collect($tareasGenerales);
        
        foreach ($tareasGeneralesDisponibles as $key => $tarea) {
            if ($tarea['tiempo_estimado'] <= $tiempoRestante) {
                // Solo la primera empleada (más horas) puede recibir tareas de prioridad 10
                $puedeAsignar = ($index === 0) || ($tarea['prioridad'] < 10);
                
                if ($puedeAsignar) {
                    $asignacion['tareas_generales'][] = [
                        'tarea_id' => $tarea['tipo_tarea']->id,
                        'tipo' => $tarea['tipo_tarea']->nombre,
                        'tiempo_estimado' => $tarea['tiempo_estimado'],
                        'prioridad_calculada' => $tarea['prioridad'],
                        'orden_ejecucion' => $ordenEjecucion
                    ];
                    
                    $asignacion['tiempo_total_asignado'] += $tarea['tiempo_estimado'];
                    $tiempoRestante -= $tarea['tiempo_estimado'];
                    $ordenEjecucion++;
                    
                    // Remover la tarea del pool para que no se asigne a otra empleada
                    unset($tareasGenerales[$key]);
                }
            }
        }
            
            $asignaciones[] = $asignacion;
        }
        
        return [
            'asignaciones' => $asignaciones,
            'resumen' => [
                'total_empleadas_utilizadas' => count($asignaciones),
                'edificios_completos_asignados' => $edificioIndex,
                'tareas_prioridad_maxima' => $tareasApartamentos->count(),
                'tareas_urgentes' => $tareasGenerales->where('prioridad', '>', 5)->count(),
                'tareas_relleno' => $tareasGenerales->where('prioridad', '<=', 5)->count(),
                'tiempo_total_optimizado' => collect($asignaciones)->sum('tiempo_total_asignado'),
                'eficiencia_global' => 'Alta - Distribución optimizada por IA'
            ]
        ];
    }
    
    /**
     * Crear turnos basándose en la respuesta de IA
     */
    private function crearTurnosDesdeIA($asignacionIA, $empleadas, $fecha)
    {
        $turnosGenerados = 0;
        $tareasNoAsignadas = [];

        try {
            $this->info("🤖 Procesando asignación de IA...");

            // Crear mapa de empleadas por ID
            $empleadasMap = $empleadas->keyBy('id');

            foreach ($asignacionIA['asignaciones'] as $asignacion) {
                $empleadaId = $asignacion['empleada_id'];
                $empleada = $empleadasMap->get($empleadaId);

                if (!$empleada) {
                    $this->warn("⚠️  Empleada ID {$empleadaId} no encontrada");
                    continue;
                }

                $this->info("👩‍💼 Creando turno para {$asignacion['empleada_nombre']}");

                // Crear turno de trabajo
                $turno = TurnoTrabajo::create([
                    'fecha' => $fecha,
                    'user_id' => $empleada->user_id,
                    'hora_inicio' => $empleada->hora_inicio_atencion,
                    'hora_fin' => $empleada->hora_fin_atencion,
                    'estado' => 'programado',
                    'fecha_creacion' => now()
                ]);

                $ordenEjecucion = 1;
                $tiempoTotalAsignado = 0;
                $tiempoDisponible = $empleada->horas_contratadas_dia * 60; // LIMITE ESTRICTO

                // Asignar tareas de apartamentos
                if (isset($asignacion['tareas_apartamentos'])) {
                    foreach ($asignacion['tareas_apartamentos'] as $tareaApto) {
                        $tiempoTarea = $tareaApto['tiempo_estimado'] ?? 60;

                        // LIMITE ESTRICTO: no exceder horas_contratadas_dia
                        if ($tiempoTotalAsignado + $tiempoTarea > $tiempoDisponible) {
                            $tareasNoAsignadas[] = "Apartamento ID {$tareaApto['apartamento_id']}";
                            Log::warning("[TURNOS-IA] Apartamento {$tareaApto['apartamento_id']} NO asignado a {$asignacion['empleada_nombre']} - excederia jornada ({$tiempoTotalAsignado}+{$tiempoTarea} > {$tiempoDisponible}min)");
                            continue;
                        }

                        $tipoTarea = TipoTarea::limpiezaApartamentos()->first();

                        if ($tipoTarea) {
                            TareaAsignada::create([
                                'turno_id' => $turno->id,
                                'tipo_tarea_id' => $tipoTarea->id,
                                'apartamento_id' => $tareaApto['apartamento_id'],
                                'zona_comun_id' => null,
                                'prioridad_calculada' => $tareaApto['prioridad'] ?? 10,
                                'orden_ejecucion' => $tareaApto['orden_ejecucion'] ?? $ordenEjecucion,
                                'estado' => 'pendiente',
                                'dias_sin_limpiar' => 0
                            ]);

                            $tiempoTotalAsignado += $tiempoTarea;
                            $ordenEjecucion++;
                        }
                    }
                }

                // Asignar tareas de zonas comunes
                if (isset($asignacion['tareas_zonas_comunes'])) {
                    foreach ($asignacion['tareas_zonas_comunes'] as $tareaZona) {
                        $tiempoTarea = $tareaZona['tiempo_estimado'] ?? 60;

                        // LIMITE ESTRICTO: no exceder horas_contratadas_dia
                        if ($tiempoTotalAsignado + $tiempoTarea > $tiempoDisponible) {
                            $tareasNoAsignadas[] = "Zona comun ID " . ($tareaZona['zona_comun_id'] ?? '?');
                            Log::warning("[TURNOS-IA] Zona comun NO asignada a {$asignacion['empleada_nombre']} - excederia jornada ({$tiempoTotalAsignado}+{$tiempoTarea} > {$tiempoDisponible}min)");
                            continue;
                        }

                        $tipoTarea = TipoTarea::limpiezaZonasComunes()->first();

                        if ($tipoTarea) {
                            TareaAsignada::create([
                                'turno_id' => $turno->id,
                                'tipo_tarea_id' => $tipoTarea->id,
                                'apartamento_id' => null,
                                'zona_comun_id' => $tareaZona['zona_comun_id'] ?? null,
                                'prioridad_calculada' => $tareaZona['prioridad'] ?? 10,
                                'orden_ejecucion' => $tareaZona['orden_ejecucion'] ?? $ordenEjecucion,
                                'estado' => 'pendiente',
                                'dias_sin_limpiar' => 0
                            ]);

                            $tiempoTotalAsignado += $tiempoTarea;
                            $ordenEjecucion++;
                        }
                    }
                }

                // Asignar tareas de limpieza comun de edificios
                if (isset($asignacion['tareas_limpieza_comun'])) {
                    foreach ($asignacion['tareas_limpieza_comun'] as $tareaLimpiezaComun) {
                        $tiempoTarea = $tareaLimpiezaComun['tiempo_estimado'] ?? 60;

                        // LIMITE ESTRICTO: no exceder horas_contratadas_dia
                        if ($tiempoTotalAsignado + $tiempoTarea > $tiempoDisponible) {
                            $tareasNoAsignadas[] = "Limpieza comun tarea ID " . ($tareaLimpiezaComun['tarea_id'] ?? '?');
                            Log::warning("[TURNOS-IA] Limpieza comun NO asignada a {$asignacion['empleada_nombre']} - excederia jornada ({$tiempoTotalAsignado}+{$tiempoTarea} > {$tiempoDisponible}min)");
                            continue;
                        }

                        $tipoTarea = TipoTarea::find($tareaLimpiezaComun['tarea_id']);

                        if ($tipoTarea) {
                            TareaAsignada::create([
                                'turno_id' => $turno->id,
                                'tipo_tarea_id' => $tipoTarea->id,
                                'apartamento_id' => null,
                                'zona_comun_id' => null,
                                'prioridad_calculada' => $tareaLimpiezaComun['prioridad'] ?? $tipoTarea->prioridad_base,
                                'orden_ejecucion' => $tareaLimpiezaComun['orden_ejecucion'] ?? $ordenEjecucion,
                                'estado' => 'pendiente',
                                'dias_sin_limpiar' => 0
                            ]);

                            $tiempoTotalAsignado += $tiempoTarea;
                            $ordenEjecucion++;
                        }
                    }
                }

                // Asignar tareas generales
                if (isset($asignacion['tareas_generales'])) {
                    foreach ($asignacion['tareas_generales'] as $tareaGeneral) {
                        $tiempoTarea = $tareaGeneral['tiempo_estimado'] ?? 60;

                        // LIMITE ESTRICTO: no exceder horas_contratadas_dia
                        if ($tiempoTotalAsignado + $tiempoTarea > $tiempoDisponible) {
                            $tareasNoAsignadas[] = "Tarea general ID " . ($tareaGeneral['tarea_id'] ?? '?');
                            Log::warning("[TURNOS-IA] Tarea general NO asignada a {$asignacion['empleada_nombre']} - excederia jornada ({$tiempoTotalAsignado}+{$tiempoTarea} > {$tiempoDisponible}min)");
                            continue;
                        }

                        $tipoTarea = TipoTarea::find($tareaGeneral['tarea_id']);

                        if ($tipoTarea) {
                            TareaAsignada::create([
                                'turno_id' => $turno->id,
                                'tipo_tarea_id' => $tipoTarea->id,
                                'apartamento_id' => null,
                                'zona_comun_id' => null,
                                'prioridad_calculada' => $tareaGeneral['prioridad_calculada'] ?? $tipoTarea->prioridad_base,
                                'orden_ejecucion' => $tareaGeneral['orden_ejecucion'] ?? $ordenEjecucion,
                                'estado' => 'pendiente',
                                'dias_sin_limpiar' => 0
                            ]);

                            $tiempoTotalAsignado += $tiempoTarea;
                            $ordenEjecucion++;
                        }
                    }
                }

                $this->info("✅ Turno creado: {$asignacion['empleada_nombre']} - {$tiempoTotalAsignado}/{$tiempoDisponible}min asignados");
                $turnosGenerados++;
            }

            // Alerta si hay tareas que la IA asigno pero excedian la jornada
            if (!empty($tareasNoAsignadas)) {
                $msg = "SOBRECARGA IA: " . count($tareasNoAsignadas) . " tareas rechazadas por exceder jornada: " . implode(', ', array_slice($tareasNoAsignadas, 0, 10));
                Log::warning("[TURNOS-IA] {$msg}");
                $this->error($msg);
                try {
                    \App\Services\AlertaEquipoService::alertar(
                        'Turnos IA Sobrecargados',
                        $msg . "\nFecha: {$fecha->format('Y-m-d')}\nLa IA sugirio mas tareas de las que caben en la jornada.",
                        'urgente'
                    );
                } catch (\Exception $e) {
                    Log::error("[TURNOS-IA] Error enviando alerta: " . $e->getMessage());
                }
            }
            
            // Mostrar resumen de IA
            if (isset($asignacionIA['resumen'])) {
                $resumen = $asignacionIA['resumen'];
                $this->info("📊 Resumen IA: {$resumen['total_empleadas_utilizadas']} empleadas, {$resumen['edificios_completos_asignados']} edificios completos");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error creando turnos desde IA: " . $e->getMessage());
            throw $e;
        }
        
        return $turnosGenerados;
    }
    
    /**
     * Llamar a OpenAI API
     */
    private function llamarOpenAI($prompt)
    {
        // Usar AIGatewayService con fallback a Hawkins AI si OpenAI falla
        $gateway = app(\App\Services\AIGatewayService::class);

        $response = $gateway->chatCompletion([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.1,
            'max_tokens' => 2000
        ]);

        return $response['choices'][0]['message']['content']
            ?? (is_object($response) ? $response->choices[0]->message->content : '');
    }
    
    /**
     * Formatear tiempo en minutos a formato legible
     */
    private function formatearTiempo($minutos)
    {
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        
        if ($horas > 0 && $mins > 0) {
            return "{$horas}h {$mins}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$mins}m";
        }
    }
}