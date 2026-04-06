@extends('layouts.appAdmin')

@section('title', 'Jornada Laboral - Empleados')

@section('tituloSeccion', 'Jornada Laboral - Empleados')

@section('content')
<div class="container-fluid">
    <!-- Header principal -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        Jornada Laboral
                    </h1>
                    <p class="text-muted mb-0">Seguimiento del trabajo y limpiezas realizadas por los empleados</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.jornada.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-sync-alt me-2"></i>Actualizar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-filter me-2 text-primary"></i>
                Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.jornada.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha_inicio" class="form-label fw-semibold">
                        <i class="fas fa-calendar-alt me-2 text-success"></i>
                        Buscar por día
                    </label>
                    <input type="date" 
                           class="form-control form-control-lg" 
                           name="fecha_inicio" 
                           value="{{ $request->fecha_inicio }}"
                           id="fecha_inicio">
                </div>
                
                <div class="col-md-4">
                    <label for="mes" class="form-label fw-semibold">
                        <i class="fas fa-calendar-month me-2 text-info"></i>
                        Buscar por mes
                    </label>
                    <select name="mes" id="mes" class="form-select form-select-lg">
                        <option value="">-- Seleccione el Mes --</option>
                        @php
                            $meses = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];
                        @endphp
                        @foreach ($meses as $numero => $nombre)
                            <option value="{{ $numero }}" {{ $request->mes == $numero ? 'selected' : '' }}>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-search me-2"></i>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Empleados -->
    @if ($users->isNotEmpty())
        @foreach ($users as $user)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3">
                                <i class="fas fa-user fa-2x text-white"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 text-white fw-bold">{{ $user->name }}</h4>
                                <p class="mb-0 text-white-50">Empleado del sistema</p>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            @php 
                                $totalMinutosMes = 0;
                                // Función helper para formatear tiempo
                                $formatearTiempo = function($minutos) {
                                    $horas = floor($minutos / 60);
                                    $mins = $minutos % 60;
                                    if ($horas > 0 && $mins > 0) {
                                        return "{$horas}h {$mins}m";
                                    } elseif ($horas > 0) {
                                        return "{$horas}h";
                                    } else {
                                        return "{$mins}m";
                                    }
                                };
                            @endphp
                            @foreach ($user->jornada as $itemJornada)
                                @php
                                    $entrada = \Carbon\Carbon::parse($itemJornada->hora_entrada);
                                    $salida = \Carbon\Carbon::parse($itemJornada->hora_salida);
                                    $minutosTrabajados = $salida->diffInMinutes($entrada, true);
                                    $totalMinutosMes += $minutosTrabajados;
                                @endphp
                            @endforeach
                            
                            <div class="bg-white bg-opacity-25 rounded p-3">
                                <div class="h3 mb-0 text-white fw-bold">{{ $formatearTiempo($totalMinutosMes) }}</div>
                                <small class="text-white-50">Horas del Mes</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    @if ($user->jornada->isNotEmpty())
                        <div class="accordion" id="accordion_{{ $user->id }}">
                            @foreach ($user->jornada as $itemJornada)
                                @php
                                    $entrada = \Carbon\Carbon::parse($itemJornada->hora_entrada);
                                    $salida = \Carbon\Carbon::parse($itemJornada->hora_salida);
                                    $minutosTrabajados = $salida->diffInMinutes($entrada, true);
                                    $horas = floor($minutosTrabajados / 60);
                                    $mins = $minutosTrabajados % 60;
                                    $tiempoFormateado = ($horas > 0 && $mins > 0) ? "{$horas}h {$mins}m" : (($horas > 0) ? "{$horas}h" : "{$mins}m");
                                    $limpiezasDelDia = $itemJornada->limpiezas;
                                @endphp
                                
                                <div class="accordion-item border-0 mb-3">
                                    <h2 class="accordion-header" id="heading_{{ $itemJornada->id }}">
                                        <button class="accordion-button collapsed bg-light" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#collapse_{{ $itemJornada->id }}">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                                        <i class="fas fa-calendar-day text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1 fw-bold text-dark">{{ $entrada->format('d/m/Y') }}</h6>
                                                        <div class="d-flex gap-3 text-muted">
                                                            <span>
                                                                <i class="fas fa-sign-in-alt text-success me-1"></i>
                                                                {{ $entrada->format('H:i') }}
                                                            </span>
                                                            <span>
                                                                <i class="fas fa-arrow-right me-1"></i>
                                                            </span>
                                                            <span>
                                                                <i class="fas fa-sign-out-alt text-danger me-1"></i>
                                                                {{ $salida->format('H:i') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="badge bg-primary fs-6 px-3 py-2">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $tiempoFormateado }}
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    
                                    <div id="collapse_{{ $itemJornada->id }}" 
                                         class="accordion-collapse collapse" 
                                         data-bs-parent="#accordion_{{ $user->id }}">
                                        <div class="accordion-body">
                                            <!-- Tareas Asignadas -->
                                            @if ($itemJornada->turnoTrabajo && $itemJornada->turnoTrabajo->tareasAsignadas->isNotEmpty())
                                                <div class="mb-4">
                                                    <h6 class="fw-bold text-success mb-3">
                                                        <i class="fas fa-tasks me-2"></i>
                                                        Tareas Asignadas
                                                    </h6>
                                                    
                                                    <div class="table-responsive">
                                                        <table class="table table-hover align-middle">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th style="width: 5%;">#</th>
                                                                    <th style="width: 25%;">Tarea</th>
                                                                    <th style="width: 30%;">Elemento</th>
                                                                    <th style="width: 15%;">Estado</th>
                                                                    <th style="width: 15%;">Tiempo Estimado</th>
                                                                    <th style="width: 15%;">Tiempo Real</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($itemJornada->turnoTrabajo->tareasAsignadas as $index => $tarea)
                                                                    @php
                                                                        $tiempoEstimado = $tarea->tipoTarea ? $tarea->tipoTarea->tiempo_estimado_minutos : 0;
                                                                        $horasEstimadas = floor($tiempoEstimado / 60);
                                                                        $minsEstimadas = $tiempoEstimado % 60;
                                                                        $tiempoEstimadoFormateado = ($horasEstimadas > 0 && $minsEstimadas > 0) ? "{$horasEstimadas}h {$minsEstimadas}m" : (($horasEstimadas > 0) ? "{$horasEstimadas}h" : "{$minsEstimadas}m");
                                                                        
                                                                        // Usar el accessor que calcula desde fecha_inicio_real y fecha_fin_real
                                                                        $tiempoRealFormateado = $tarea->tiempo_real_formateado;
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="text-muted">{{ $tarea->orden_ejecucion ?? ($index + 1) }}</td>
                                                                        <td>
                                                                            <strong>{{ $tarea->tipoTarea->nombre ?? 'Sin tipo' }}</strong>
                                                                        </td>
                                                                        <td>
                                                                            @if ($tarea->apartamento)
                                                                                <i class="fas fa-building text-primary me-1"></i>
                                                                                <strong>{{ $tarea->apartamento->nombre ?? $tarea->apartamento->titulo }}</strong>
                                                                                @if ($tarea->apartamento->edificioName)
                                                                                    <br><small class="text-muted">{{ $tarea->apartamento->edificioName->nombre }}</small>
                                                                                @endif
                                                                            @elseif ($tarea->zonaComun)
                                                                                <i class="fas fa-users text-info me-1"></i>
                                                                                <strong>{{ $tarea->zonaComun->nombre }}</strong>
                                                                            @else
                                                                                <span class="text-muted">Sin elemento específico</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            @if ($tarea->estado === 'completada')
                                                                                <span class="badge bg-success">
                                                                                    <i class="fas fa-check me-1"></i>Completada
                                                                                </span>
                                                                            @elseif ($tarea->estado === 'en_progreso')
                                                                                <span class="badge bg-warning">
                                                                                    <i class="fas fa-spinner me-1"></i>En Progreso
                                                                                </span>
                                                                            @elseif ($tarea->estado === 'pendiente')
                                                                                <span class="badge bg-secondary">
                                                                                    <i class="fas fa-clock me-1"></i>Pendiente
                                                                                </span>
                                                                            @else
                                                                                <span class="badge bg-danger">
                                                                                    <i class="fas fa-times me-1"></i>Cancelada
                                                                                </span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <i class="fas fa-clock text-muted me-1"></i>
                                                                            <span class="text-muted">{{ $tiempoEstimadoFormateado }}</span>
                                                                        </td>
                                                                        <td>
                                                                            @if ($tarea->tiempo_real_minutos)
                                                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                                                <strong>{{ $tiempoRealFormateado }}</strong>
                                                                                @php
                                                                                    // Obtener fechas reales, o desde ApartamentoLimpieza si no existen
                                                                                    $fechaInicio = $tarea->fecha_inicio_real;
                                                                                    $fechaFin = $tarea->fecha_fin_real;
                                                                                    
                                                                                    if (!$fechaInicio) {
                                                                                        $apartamentoLimpieza = \App\Models\ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();
                                                                                        if ($apartamentoLimpieza && $apartamentoLimpieza->created_at) {
                                                                                            $fechaInicio = $apartamentoLimpieza->created_at;
                                                                                        }
                                                                                    }
                                                                                    
                                                                                    if (!$fechaFin) {
                                                                                        $apartamentoLimpieza = \App\Models\ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();
                                                                                        if ($apartamentoLimpieza) {
                                                                                            $fechaFin = $apartamentoLimpieza->fecha_fin ?? $apartamentoLimpieza->updated_at;
                                                                                        }
                                                                                    }
                                                                                @endphp
                                                                                @if ($fechaInicio && $fechaFin)
                                                                                    <br><small class="text-muted">
                                                                                        {{ $fechaInicio->format('H:i') }} - {{ $fechaFin->format('H:i') }}
                                                                                    </small>
                                                                                @endif
                                                                            @elseif ($tarea->estado === 'en_progreso')
                                                                                <i class="fas fa-spinner text-warning me-1"></i>
                                                                                <span class="text-warning">{{ $tiempoRealFormateado }}</span>
                                                                                @php
                                                                                    $fechaInicio = $tarea->fecha_inicio_real;
                                                                                    if (!$fechaInicio) {
                                                                                        $apartamentoLimpieza = \App\Models\ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();
                                                                                        if ($apartamentoLimpieza && $apartamentoLimpieza->created_at) {
                                                                                            $fechaInicio = $apartamentoLimpieza->created_at;
                                                                                        }
                                                                                    }
                                                                                @endphp
                                                                                @if ($fechaInicio)
                                                                                    <br><small class="text-muted">
                                                                                        Inicio: {{ $fechaInicio->format('H:i') }}
                                                                                    </small>
                                                                                @endif
                                                                            @elseif ($tarea->estado === 'completada')
                                                                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                                                                <span class="text-warning">{{ $tiempoRealFormateado }}</span>
                                                                            @else
                                                                                <span class="text-muted">{{ $tiempoRealFormateado }}</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Limpiezas Realizadas (mantener compatibilidad) -->
                                            @if ($limpiezasDelDia->isNotEmpty())
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-primary mb-3">
                                                        <i class="fas fa-broom me-2"></i>
                                                        Limpiezas Realizadas
                                                    </h6>
                                                    
                                                    <div class="row g-3">
                                                        @foreach ($limpiezasDelDia as $limpieza)
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="card border-0 shadow-sm h-100">
                                                                    <div class="card-body">
                                                                        <div class="d-flex align-items-start mb-3">
                                                                            <div class="bg-light rounded-circle p-2 me-3">
                                                                                @if ($limpieza->tipo_limpieza === 'zona_comun')
                                                                                    <i class="fas fa-users text-info"></i>
                                                                                @else
                                                                                    <i class="fas fa-building text-primary"></i>
                                                                                @endif
                                                                            </div>
                                                                            
                                                                            <div class="flex-grow-1">
                                                                                <h6 class="fw-bold mb-1">
                                                                                    @if ($limpieza->apartamento)
                                                                                        {{ $limpieza->apartamento->nombre ?? $limpieza->apartamento->titulo }}
                                                                                    @elseif ($limpieza->zonaComun)
                                                                                        {{ $limpieza->zonaComun->nombre }}
                                                                                    @else
                                                                                        Elemento no especificado
                                                                                    @endif
                                                                                </h6>
                                                                                
                                                                                <div class="mb-2">
                                                                                    @if ($limpieza->tipo_limpieza === 'zona_comun')
                                                                                        <span class="badge bg-info">Zona Común</span>
                                                                                    @else
                                                                                        <span class="badge bg-primary">Apartamento</span>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="text-center">
                                                                            <a href="{{ route('admin.limpiezas.show', $limpieza->id) }}" 
                                                                               class="btn btn-primary btn-sm w-100">
                                                                                <i class="fas fa-eye me-1"></i>
                                                                                Ver Detalles
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Mensaje si no hay tareas ni limpiezas -->
                                            @if ((!$itemJornada->turnoTrabajo || $itemJornada->turnoTrabajo->tareasAsignadas->isEmpty()) && $limpiezasDelDia->isEmpty())
                                                <div class="text-center py-4">
                                                    <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                                    <p class="text-muted mb-0">No se registraron tareas asignadas ni limpiezas en este día</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay jornadas registradas</h5>
                            <p class="text-muted mb-0">Este empleado no tiene fichajes para el período seleccionado</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay empleados</h5>
                <p class="text-muted mb-0">No se encontraron empleados activos en el sistema</p>
            </div>
        </div>
    @endif
</div>

<style>
/* Estilos personalizados para mejorar la apariencia */
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #495057;
    box-shadow: none;
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
}

.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
}

.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .d-flex.gap-3 {
        gap: 0.5rem !important;
    }
    
    .accordion-button .d-flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .badge.bg-primary {
        font-size: 0.875rem !important;
        padding: 0.5rem 1rem !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus en el primer campo de fecha si está vacío
    const fechaInput = document.getElementById('fecha_inicio');
    if (fechaInput && !fechaInput.value) {
        fechaInput.focus();
    }
    
    // Mejorar la experiencia de los acordeones
    const accordionButtons = document.querySelectorAll('.accordion-button');
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Agregar clase activa al botón
            this.classList.toggle('active');
        });
    });
});
</script>
@endsection
