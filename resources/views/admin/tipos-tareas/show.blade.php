@extends('layouts.appAdmin')

@section('title', 'Detalle del Tipo de Tarea')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header con acciones -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="fas fa-eye me-2"></i>Detalle del Tipo de Tarea
                        </h3>
                        <small class="text-muted">Información completa del tipo de tarea</small>
                    </div>
                    <div>
                        <a href="{{ route('admin.tipos-tareas.index') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                        <a href="{{ route('admin.tipos-tareas.edit', $tiposTarea) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Información Principal -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Información General
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nombre:</label>
                                        <p class="form-control-plaintext">{{ $tiposTarea->nombre }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Categoría:</label>
                                        <p class="form-control-plaintext">
                                            <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $tiposTarea->categoria)) }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Descripción:</label>
                                <p class="form-control-plaintext">{{ $tiposTarea->descripcion ?? 'Sin descripción' }}</p>
                            </div>
                            
                            @if($tiposTarea->instrucciones)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Instrucciones:</label>
                                <div class="form-control-plaintext bg-light p-3 rounded">
                                    {!! nl2br(e($tiposTarea->instrucciones)) !!}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Configuración de Prioridades -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>Configuración de Prioridades
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Prioridad Base:</label>
                                        <p class="form-control-plaintext">
                                            <span class="badge bg-{{ $tiposTarea->prioridad_base >= 8 ? 'danger' : ($tiposTarea->prioridad_base >= 5 ? 'warning' : 'success') }}">
                                                {{ $tiposTarea->prioridad_base }}/10
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Prioridad Máxima:</label>
                                        <p class="form-control-plaintext">
                                            <span class="badge bg-danger">{{ $tiposTarea->prioridad_maxima }}/10</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Días Máx. Sin Limpiar:</label>
                                        <p class="form-control-plaintext">{{ $tiposTarea->dias_max_sin_limpiar }} días</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Incremento por Día:</label>
                                        <p class="form-control-plaintext">+{{ $tiposTarea->incremento_prioridad_por_dia }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Lateral -->
                <div class="col-md-4">
                    <!-- Estado y Tiempo -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2"></i>Configuración de Tiempo
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tiempo Estimado:</label>
                                <p class="form-control-plaintext">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $tiposTarea->tiempo_estimado_minutos }} minutos
                                    <small class="text-muted">({{ round($tiposTarea->tiempo_estimado_minutos / 60, 1) }} horas)</small>
                                </p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Estado:</label>
                                <p class="form-control-plaintext">
                                    @if($tiposTarea->activo)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Activo
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times me-1"></i>Inactivo
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Requisitos -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cogs me-2"></i>Requisitos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Requiere Apartamento:</label>
                                <p class="form-control-plaintext">
                                    @if($tiposTarea->requiere_apartamento)
                                        <span class="badge bg-info">
                                            <i class="fas fa-home me-1"></i>Sí
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times me-1"></i>No
                                        </span>
                                    @endif
                                </p>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label fw-bold">Requiere Zona Común:</label>
                                <p class="form-control-plaintext">
                                    @if($tiposTarea->requiere_zona_comun)
                                        <span class="badge bg-info">
                                            <i class="fas fa-building me-1"></i>Sí
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times me-1"></i>No
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar me-2"></i>Estadísticas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tareas Asignadas:</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-primary fs-6">{{ $tiposTarea->tareasAsignadas->count() }}</span>
                                </p>
                            </div>
                            
                            @if($tiposTarea->tareasAsignadas->count() > 0)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Última Asignación:</label>
                                <p class="form-control-plaintext">
                                    {{ $tiposTarea->tareasAsignadas->sortByDesc('created_at')->first()->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de Tareas Asignadas -->
            @if($tiposTarea->tareasAsignadas->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Historial de Tareas Asignadas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Turno</th>
                                    <th>Empleada</th>
                                    <th>Fecha</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Orden</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tiposTarea->tareasAsignadas->sortByDesc('created_at') as $tarea)
                                <tr>
                                    <td>{{ $tarea->id }}</td>
                                    <td>
                                        <a href="{{ route('gestion.turnos.show', $tarea->turno) }}" class="text-decoration-none">
                                            Turno #{{ $tarea->turno->id }}
                                        </a>
                                    </td>
                                    <td>{{ $tarea->turno->user->name ?? 'N/A' }}</td>
                                    <td>{{ $tarea->turno->fecha->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $tarea->prioridad_calculada >= 8 ? 'danger' : ($tarea->prioridad_calculada >= 5 ? 'warning' : 'success') }}">
                                            {{ $tarea->prioridad_calculada }}/10
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $tarea->estado == 'completada' ? 'success' : ($tarea->estado == 'en_progreso' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($tarea->estado) }}
                                        </span>
                                    </td>
                                    <td>{{ $tarea->orden_ejecucion }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
