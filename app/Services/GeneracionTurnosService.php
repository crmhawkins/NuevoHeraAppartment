<?php

namespace App\Services;

use App\Models\TurnoTrabajo;
use App\Models\TareaAsignada;
use App\Models\TipoTarea;
use App\Models\Apartamento;
use App\Models\ZonaComun;
use App\Models\EmpleadaHorario;
use App\Models\HolidaysPetitions;
use App\Models\Edificio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GeneracionTurnosService
{
    /**
     * Generar turnos inteligentes para una fecha específica
     */
    public function generarTurnosInteligentes($fecha, $forzarRegeneracion = false)
    {
        $fechaCarbon = Carbon::parse($fecha);
        $diaSemana = $fechaCarbon->dayOfWeek; // 0 = domingo, 1 = lunes, etc.
        $esFinDeSemana = $diaSemana == 0 || $diaSemana == 6; // Domingo o sábado

        Log::info("🚀 Generando turnos inteligentes para {$fecha} (Día: {$diaSemana}, Fin de semana: " . ($esFinDeSemana ? 'Sí' : 'No') . ", Forzar: " . ($forzarRegeneracion ? 'Sí' : 'No') . ")");

        // 0. Si se fuerza regeneración, eliminar turnos existentes
        if ($forzarRegeneracion) {
            $turnosExistentes = TurnoTrabajo::where('fecha', $fechaCarbon)->get();
            Log::info("🔍 Encontrados " . $turnosExistentes->count() . " turnos existentes para {$fecha}");
            
            foreach ($turnosExistentes as $turno) {
                Log::info("🗑️ Eliminando turno ID {$turno->id} - Usuario {$turno->user_id} - {$turno->hora_inicio}-{$turno->hora_fin}");
            }
            
            $turnosEliminados = TurnoTrabajo::where('fecha', $fechaCarbon)->delete();
            Log::info("✅ Eliminados {$turnosEliminados} turnos existentes para {$fecha}");
        }

        // 1. Obtener empleadas disponibles
        $empleadasDisponibles = $this->obtenerEmpleadasDisponibles($fechaCarbon);
        
        if ($empleadasDisponibles->isEmpty()) {
            Log::warning("⚠️ No hay empleadas disponibles para {$fecha}");
            return ['success' => false, 'message' => 'No hay empleadas disponibles'];
        }

        // 2. Verificar vacaciones
        $empleadasEnVacaciones = $this->obtenerEmpleadasEnVacaciones($fechaCarbon);
        $empleadasActivas = $empleadasDisponibles->whereNotIn('user_id', $empleadasEnVacaciones->pluck('admin_user_id'));

        Log::info("👥 Empleadas disponibles: {$empleadasActivas->count()}");
        Log::info("🏖️ Empleadas en vacaciones: {$empleadasEnVacaciones->count()}");

        // 3. Generar turnos según la lógica
        $turnosGenerados = [];

        if ($esFinDeSemana) {
            // LÓGICA FINES DE SEMANA: Una sola empleada = toda la limpieza
            $turnosGenerados = $this->generarTurnosFinDeSemana($fechaCarbon, $empleadasActivas);
        } else {
            // LÓGICA ENTRE SEMANA: Considerar vacaciones y horas contratadas
            $turnosGenerados = $this->generarTurnosEntreSemana($fechaCarbon, $empleadasActivas, $empleadasEnVacaciones);
        }

        return $turnosGenerados;
    }

    /**
     * Obtener empleadas disponibles para una fecha
     */
    private function obtenerEmpleadasDisponibles($fecha)
    {
        return EmpleadaHorario::where('activo', true)
            ->with(['user' => function($query) {
                // Solo usuarios activos (no inactivos)
                $query->where(function($q) {
                    $q->where('inactive', '=', 0)
                      ->orWhereNull('inactive');
                });
            }])
            ->get()
            ->filter(function($empleada) use ($fecha) {
                // Verificar que el usuario existe y está activo
                if (!$empleada->user || $empleada->user->inactive) {
                    return false;
                }
                
                $diaSemana = $fecha->dayOfWeek;
                $diasColumnas = [
                    1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 
                    4 => 'jueves', 5 => 'viernes', 6 => 'sabado', 0 => 'domingo'
                ];
                $columnaDia = $diasColumnas[$diaSemana] ?? 'lunes';
                
                return $empleada->$columnaDia == true;
            });
    }

    /**
     * Obtener empleadas en vacaciones para una fecha
     */
    private function obtenerEmpleadasEnVacaciones($fecha)
    {
        return HolidaysPetitions::where('from', '<=', $fecha)
            ->where('to', '>=', $fecha)
            ->where('holidays_status_id', 3) // 3 = Aprobada (según el código existente)
            ->with('adminUser')
            ->get();
    }

    /**
     * Generar turnos para fines de semana
     */
    private function generarTurnosFinDeSemana($fecha, $empleadasActivas)
    {
        $turnosGenerados = [];
        
        // Solo una empleada trabaja los fines de semana
        $empleadaPrincipal = $empleadasActivas->first();
        
        if (!$empleadaPrincipal) {
            return ['success' => false, 'message' => 'No hay empleada disponible para fin de semana'];
        }

        // Crear turno de 7 horas (máximo para fin de semana)
        $turno = $this->crearTurno($empleadaPrincipal, $fecha, '08:00', '15:00');
        
        // Asignar TODAS las tareas de limpieza de apartamentos y zonas comunes
        $tareasAsignadas = $this->asignarTodasLasTareasLimpieza($turno, 7.0);
        
        $turnosGenerados[] = [
            'turno' => $turno,
            'tareas' => $tareasAsignadas,
            'empleada' => $empleadaPrincipal->user->name,
            'horas' => 7.0,
            'tipo' => 'fin_semana'
        ];

        Log::info("✅ Turno fin de semana generado para {$empleadaPrincipal->user->name}: 7h con todas las tareas");

        return ['success' => true, 'turnos' => $turnosGenerados];
    }

    /**
     * Generar turnos para entre semana
     */
    private function generarTurnosEntreSemana($fecha, $empleadasActivas, $empleadasEnVacaciones)
    {
        $turnosGenerados = [];
        $hayVacaciones = $empleadasEnVacaciones->isNotEmpty();

        // Ordenar empleadas por horas contratadas (más horas primero) para asignar tareas importantes primero
        $empleadasOrdenadas = $empleadasActivas->sortByDesc('horas_contratadas_dia');
        
        foreach ($empleadasOrdenadas as $index => $empleada) {
            $horasContratadas = $empleada->horas_contratadas_dia;
            $horasAsignar = $this->calcularHorasAsignar($horasContratadas, $hayVacaciones);
            
            if ($horasAsignar <= 0) {
                continue; // No asignar turno si no hay horas suficientes
            }

            // Crear turno
            $horaInicio = $this->calcularHoraInicio($empleada);
            $horaFin = $this->calcularHoraFin($horaInicio, $horasAsignar);
            
            $turno = $this->crearTurno($empleada, $fecha, $horaInicio, $horaFin);
            
            // Asignar tareas según prioridad y horas disponibles
            // Solo la primera empleada (más horas) recibe tareas de lavandería
            $esPrimeraEmpleada = $index === 0;
            $tareasAsignadas = $this->asignarTareasPorPrioridad($turno, $horasAsignar, $hayVacaciones, $esPrimeraEmpleada);
            
            $turnosGenerados[] = [
                'turno' => $turno,
                'tareas' => $tareasAsignadas,
                'empleada' => $empleada->user->name,
                'horas' => $horasAsignar,
                'tipo' => $hayVacaciones ? 'con_vacaciones' : 'normal'
            ];

            Log::info("✅ Turno entre semana generado para {$empleada->user->name}: {$horasAsignar}h");
        }

        return ['success' => true, 'turnos' => $turnosGenerados];
    }

    /**
     * Calcular horas a asignar según la lógica de negocio
     */
    private function calcularHorasAsignar($horasContratadas, $hayVacaciones)
    {
        if ($hayVacaciones) {
            // Si hay vacaciones: empleada de 8h trabaja 7h, empleada de 6h trabaja 4h
            return $horasContratadas >= 8 ? 7.0 : 4.0;
        } else {
            // Sin vacaciones: horas normales contratadas
            return $horasContratadas;
        }
    }

    /**
     * Calcular hora de inicio según empleada
     */
    private function calcularHoraInicio($empleada)
    {
        // Hora de inicio por defecto: 8:00
        return $empleada->hora_inicio ?? '08:00';
    }

    /**
     * Calcular hora de fin
     */
    private function calcularHoraFin($horaInicio, $horas)
    {
        $inicio = Carbon::createFromFormat('H:i', $horaInicio);
        $fin = $inicio->addHours($horas);
        return $fin->format('H:i');
    }

    /**
     * Crear turno en la base de datos
     */
    private function crearTurno($empleada, $fecha, $horaInicio, $horaFin)
    {
        // Verificar si ya existe un turno para esta fecha y usuario
        $turnoExistente = TurnoTrabajo::where('fecha', $fecha)
            ->where('user_id', $empleada->user_id)
            ->first();
            
        if ($turnoExistente) {
            Log::warning("⚠️ Ya existe un turno para {$empleada->user->name} en {$fecha->format('Y-m-d')}");
            return $turnoExistente; // Retornar el turno existente en lugar de crear uno nuevo
        }
        
        return TurnoTrabajo::create([
            'fecha' => $fecha,
            'user_id' => $empleada->user_id,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'estado' => 'programado',
            'fecha_creacion' => now()
        ]);
    }

    /**
     * Asignar todas las tareas de limpieza (para fines de semana)
     */
    private function asignarTodasLasTareasLimpieza($turno, $horasDisponibles)
    {
        $tareasAsignadas = [];
        $orden = 1;

        // 1. Obtener apartamentos que necesitan limpieza
        $apartamentos = Apartamento::all();
        
        foreach ($apartamentos as $apartamento) {
            $tipoTarea = TipoTarea::where('categoria', 'limpieza_apartamento')
                ->where('activo', true)
                ->first();
                
            if ($tipoTarea) {
                $tarea = TareaAsignada::create([
                    'turno_id' => $turno->id,
                    'tipo_tarea_id' => $tipoTarea->id,
                    'apartamento_id' => $apartamento->id,
                    'prioridad_calculada' => 10, // Máxima prioridad
                    'orden_ejecucion' => $orden++,
                    'estado' => 'pendiente'
                ]);
                $tareasAsignadas[] = $tarea;
            }
        }

        // 2. Obtener zonas comunes que necesitan limpieza
        $zonasComunes = ZonaComun::where('activo', true)->get();
        
        foreach ($zonasComunes as $zonaComun) {
            $tipoTarea = TipoTarea::where('categoria', 'limpieza_zona_comun')
                ->where('activo', true)
                ->first();
                
            if ($tipoTarea) {
                $tarea = TareaAsignada::create([
                    'turno_id' => $turno->id,
                    'tipo_tarea_id' => $tipoTarea->id,
                    'zona_comun_id' => $zonaComun->id,
                    'prioridad_calculada' => 9, // Alta prioridad
                    'orden_ejecucion' => $orden++,
                    'estado' => 'pendiente'
                ]);
                $tareasAsignadas[] = $tarea;
            }
        }

        return $tareasAsignadas;
    }

    /**
     * Asignar tareas por prioridad (para entre semana)
     */
    private function asignarTareasPorPrioridad($turno, $horasDisponibles, $hayVacaciones, $esPrimeraEmpleada = false)
    {
        $tareasAsignadas = [];
        $orden = 1;
        $tiempoAsignado = 0;
        $tiempoDisponible = $horasDisponibles * 60; // Convertir a minutos

        Log::info("🎯 Asignando tareas para turno {$turno->id}: {$horasDisponibles}h disponibles, vacaciones: " . ($hayVacaciones ? 'Sí' : 'No'));

        // 1. PRIORIDAD 1: Limpieza de apartamentos (si hay vacaciones, máxima prioridad)
        $prioridadApartamentos = $hayVacaciones ? 10 : 8;
        $tareasApartamentos = $this->asignarTareasApartamentos($turno, $prioridadApartamentos, $orden, $tiempoDisponible);
        $tareasAsignadas = array_merge($tareasAsignadas, $tareasApartamentos['tareas']);
        $orden = $tareasApartamentos['siguiente_orden'];
        $tiempoAsignado += $tareasApartamentos['tiempo_usado'];

        Log::info("🏠 Apartamentos asignados: " . count($tareasApartamentos['tareas']) . " tareas, tiempo usado: {$tareasApartamentos['tiempo_usado']}min");

        // 2. PRIORIDAD 2: Limpieza de zonas comunes
        $tiempoRestante = $tiempoDisponible - $tiempoAsignado;
        if ($tiempoRestante > 0) {
            $tareasZonas = $this->asignarTareasZonasComunes($turno, 7, $orden, $tiempoRestante);
            $tareasAsignadas = array_merge($tareasAsignadas, $tareasZonas['tareas']);
            $orden = $tareasZonas['siguiente_orden'];
            $tiempoAsignado += $tareasZonas['tiempo_usado'];
            
            Log::info("🏢 Zonas comunes asignadas: " . count($tareasZonas['tareas']) . " tareas, tiempo usado: {$tareasZonas['tiempo_usado']}min");
        }

        // 3. PRIORIDAD 3: Lavandería (solo edificio Costa y cocina) - PRIORIDAD MÁXIMA
        // Solo se asigna a la primera empleada (con más horas)
        $tiempoRestante = $tiempoDisponible - $tiempoAsignado;
        if ($tiempoRestante > 0 && $esPrimeraEmpleada) {
            $tareasLavanderia = $this->asignarTareasLavanderia($turno, 10, $orden, $tiempoRestante);
            $tareasAsignadas = array_merge($tareasAsignadas, $tareasLavanderia['tareas']);
            $tiempoAsignado += $tareasLavanderia['tiempo_usado'];
            
            Log::info("🧺 Lavandería asignada a primera empleada: " . count($tareasLavanderia['tareas']) . " tareas, tiempo usado: {$tareasLavanderia['tiempo_usado']}min");
        }

        // 4. VALIDACIÓN: Verificar que no se exceda el tiempo disponible
        $tiempoTotalAsignado = $tiempoAsignado;
        if ($tiempoTotalAsignado > $tiempoDisponible) {
            Log::warning("⚠️ ADVERTENCIA: Tiempo asignado ({$tiempoTotalAsignado}min) excede tiempo disponible ({$tiempoDisponible}min)");
        }

        Log::info("✅ Total tareas asignadas: " . count($tareasAsignadas) . ", tiempo total: {$tiempoTotalAsignado}min de {$tiempoDisponible}min disponibles");

        return $tareasAsignadas;
    }

    /**
     * Asignar tareas de apartamentos
     */
    private function asignarTareasApartamentos($turno, $prioridad, $ordenInicial, $tiempoDisponible)
    {
        $tareas = [];
        $orden = $ordenInicial;
        $tiempoUsado = 0;

        // Obtener apartamentos ordenados por nombre
        $apartamentos = Apartamento::orderBy('nombre', 'asc')
            ->get();

        // Obtener tipo de tarea de limpieza de apartamentos
        $tipoTarea = TipoTarea::where('categoria', 'limpieza_apartamento')
            ->where('activo', true)
            ->first();

        if (!$tipoTarea) {
            Log::warning("⚠️ No se encontró tipo de tarea 'limpieza_apartamento'");
            return [
                'tareas' => [],
                'siguiente_orden' => $orden,
                'tiempo_usado' => 0
            ];
        }

        Log::info("🏠 Procesando " . $apartamentos->count() . " apartamentos para limpieza");

        foreach ($apartamentos as $apartamento) {
            // Verificar si hay tiempo suficiente
            if ($tiempoUsado + $tipoTarea->tiempo_estimado_minutos <= $tiempoDisponible) {
                try {
                    $tarea = TareaAsignada::create([
                        'turno_id' => $turno->id,
                        'tipo_tarea_id' => $tipoTarea->id,
                        'apartamento_id' => $apartamento->id,
                        'prioridad_calculada' => $prioridad,
                        'orden_ejecucion' => $orden++,
                        'estado' => 'pendiente'
                    ]);
                    $tareas[] = $tarea;
                    $tiempoUsado += $tipoTarea->tiempo_estimado_minutos;
                    
                    Log::debug("✅ Tarea creada para apartamento {$apartamento->titulo} (orden: {$tarea->orden_ejecucion})");
                } catch (\Exception $e) {
                    Log::error("❌ Error creando tarea para apartamento {$apartamento->id}: " . $e->getMessage());
                }
            } else {
                Log::info("⏰ Sin tiempo suficiente para apartamento {$apartamento->titulo} (necesita {$tipoTarea->tiempo_estimado_minutos}min, restan " . ($tiempoDisponible - $tiempoUsado) . "min)");
                break; // No hay más tiempo disponible
            }
        }

        Log::info("🏠 Apartamentos procesados: " . count($tareas) . " tareas creadas, tiempo usado: {$tiempoUsado}min");

        return [
            'tareas' => $tareas,
            'siguiente_orden' => $orden,
            'tiempo_usado' => $tiempoUsado
        ];
    }

    /**
     * Asignar tareas de zonas comunes
     */
    private function asignarTareasZonasComunes($turno, $prioridad, $ordenInicial, $tiempoDisponible)
    {
        $tareas = [];
        $orden = $ordenInicial;
        $tiempoUsado = 0;

        $zonasComunes = ZonaComun::where('activo', true)
            ->orderBy('prioridad_limpieza', 'desc')
            ->get();

        foreach ($zonasComunes as $zonaComun) {
            $tipoTarea = TipoTarea::where('categoria', 'limpieza_zona_comun')
                ->where('activo', true)
                ->first();

            if ($tipoTarea && $tiempoUsado + $tipoTarea->tiempo_estimado_minutos <= $tiempoDisponible) {
                $tarea = TareaAsignada::create([
                    'turno_id' => $turno->id,
                    'tipo_tarea_id' => $tipoTarea->id,
                    'zona_comun_id' => $zonaComun->id,
                    'prioridad_calculada' => $prioridad,
                    'orden_ejecucion' => $orden++,
                    'estado' => 'pendiente'
                ]);
                $tareas[] = $tarea;
                $tiempoUsado += $tipoTarea->tiempo_estimado_minutos;
            }
        }

        return [
            'tareas' => $tareas,
            'siguiente_orden' => $orden,
            'tiempo_usado' => $tiempoUsado
        ];
    }

    /**
     * Asignar tareas de lavandería (solo edificio Costa y cocina)
     */
    private function asignarTareasLavanderia($turno, $prioridad, $ordenInicial, $tiempoDisponible)
    {
        $tareas = [];
        $orden = $ordenInicial;
        $tiempoUsado = 0;

        // Buscar edificio Costa
        $edificioCosta = Edificio::where('nombre', 'like', '%Costa%')->first();
        
        if ($edificioCosta) {
            $tipoTarea = TipoTarea::where('nombre', 'like', '%Lavandería%')
                ->where('activo', true)
                ->first();

            if ($tipoTarea && $tiempoUsado + $tipoTarea->tiempo_estimado_minutos <= $tiempoDisponible) {
                $tarea = TareaAsignada::create([
                    'turno_id' => $turno->id,
                    'tipo_tarea_id' => $tipoTarea->id,
                    'edificio_id' => $edificioCosta->id,
                    'prioridad_calculada' => $prioridad,
                    'orden_ejecucion' => $orden++,
                    'estado' => 'pendiente'
                ]);
                $tareas[] = $tarea;
                $tiempoUsado += $tipoTarea->tiempo_estimado_minutos;
            }
        }

        return [
            'tareas' => $tareas,
            'siguiente_orden' => $orden,
            'tiempo_usado' => $tiempoUsado
        ];
    }
}
