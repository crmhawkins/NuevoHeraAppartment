@extends('layouts.appAdmin')

@section('title', 'Detalle del Turno')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-check me-2"></i>Detalle del Turno
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('gestion.turnos.edit', $turno) }}" class="btn btn-secondary">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                        <a href="{{ route('gestion.turnos.index', ['fecha' => $turno->fecha->format('Y-m-d')]) }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Información del Turno</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Empleada:</strong></td>
                                    <td>{{ $turno->user->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha:</strong></td>
                                    <td>{{ $turno->fecha->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Horario:</strong></td>
                                    <td>{{ $turno->hora_inicio->format('H:i') }} - {{ $turno->hora_fin->format('H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $turno->estado === 'completado' ? 'success' : ($turno->estado === 'en_progreso' ? 'warning' : 'primary') }}">
                                            {{ ucfirst($turno->estado) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Horas Trabajadas:</strong></td>
                                    <td>{{ $turno->horas_trabajadas }}h</td>
                                </tr>
                                <tr>
                                    <td><strong>Progreso:</strong></td>
                                    <td>
                                        <div class="progress" style="width: 200px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $turno->progreso }}%">
                                                {{ $turno->progreso }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Estadísticas</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="card bg-primary text-white text-center">
                                        <div class="card-body">
                                            <h4>{{ $turno->total_tareas }}</h4>
                                            <p class="mb-0">Total Tareas</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-success text-white text-center">
                                        <div class="card-body">
                                            <h4>{{ $turno->tareas_completadas }}</h4>
                                            <p class="mb-0">Completadas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <div class="card bg-warning text-white text-center">
                                        <div class="card-body">
                                            <h4>{{ $turno->tareas_pendientes }}</h4>
                                            <p class="mb-0">Pendientes</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-info text-white text-center">
                                        <div class="card-body">
                                            <h4>{{ $turno->tiempo_estimado_total_formateado }}</h4>
                                            <p class="mb-0">Tiempo Est.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($turno->observaciones)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Observaciones</h5>
                                <div class="alert alert-info">
                                    {{ $turno->observaciones }}
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Tareas Asignadas</h5>
                            @if($turno->tareasAsignadas->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Orden</th>
                                                <th>Tipo de Tarea</th>
                                                <th>Elemento</th>
                                                <th>Prioridad</th>
                                                <th>Tiempo Est.</th>
                                                <th>Estado</th>
                                                <th>Tiempo Real</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($turno->tareasAsignadas->sortBy('orden_ejecucion') as $tarea)
                                                <tr>
                                                    <td>{{ $tarea->orden_ejecucion }}</td>
                                                    <td>{{ $tarea->tipoTarea->nombre }}</td>
                                                    <td>{{ $tarea->elemento_nombre }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $tarea->prioridad_calculada >= 8 ? 'danger' : ($tarea->prioridad_calculada >= 6 ? 'warning' : 'success') }}">
                                                            {{ $tarea->prioridad_calculada }}/10
                                                        </span>
                                                    </td>
                                                    <td>{{ $tarea->tiempo_estimado_formateado }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $tarea->estado === 'completada' ? 'success' : ($tarea->estado === 'en_progreso' ? 'warning' : 'primary') }}">
                                                            {{ ucfirst($tarea->estado) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $tarea->tiempo_real_formateado }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay tareas asignadas a este turno.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
