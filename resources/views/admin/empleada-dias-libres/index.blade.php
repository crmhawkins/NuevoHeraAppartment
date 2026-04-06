@extends('layouts.appAdmin')

@section('title', 'Gestión de Días Libres - ' . $empleadaHorario->user->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Gestión de Días Libres - {{ $empleadaHorario->user->name }}
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('admin.empleada-horarios.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Volver a Horarios
                        </a>
                        <a href="{{ route('admin.empleada-dias-libres.create', $empleadaHorario) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Configurar Semana
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Información de la empleada -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Información de la Empleada</h6>
                                    <p class="mb-1"><strong>Nombre:</strong> {{ $empleadaHorario->user->name }}</p>
                                    <p class="mb-1"><strong>Email:</strong> {{ $empleadaHorario->user->email }}</p>
                                    <p class="mb-1"><strong>Jornada:</strong> {{ $empleadaHorario->horas_contratadas_dia }}h/día</p>
                                    <p class="mb-0"><strong>Días de trabajo:</strong> {{ $empleadaHorario->dias_trabajo }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Días Libres Configurados</h6>
                                    <p class="mb-1"><strong>Semanas configuradas:</strong> {{ $diasLibresConfigurados->count() }}</p>
                                    <p class="mb-0"><strong>Última actualización:</strong> {{ $diasLibresConfigurados->max('updated_at') ? $diasLibresConfigurados->max('updated_at')->format('d/m/Y H:i') : 'Nunca' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros de fecha -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="fecha_inicio" class="form-label">Fecha Inicio:</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                           value="{{ $fechaInicio }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="fecha_fin" class="form-label">Fecha Fin:</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                           value="{{ $fechaFin }}">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-outline-primary me-2">
                                        <i class="fas fa-search me-1"></i>Filtrar
                                    </button>
                                    <a href="{{ route('admin.empleada-dias-libres.index', $empleadaHorario) }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Calendario de semanas -->
                    <div class="row">
                        @forelse($semanas as $semana)
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 {{ $diasLibresConfigurados->has($semana['lunes']->format('Y-m-d')) ? 'border-success' : 'border-light' }}">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-week me-1"></i>
                                            Semana {{ $semana['numero_semana'] }}
                                        </h6>
                                        <span class="badge bg-{{ $diasLibresConfigurados->has($semana['lunes']->format('Y-m-d')) ? 'success' : 'secondary' }}">
                                            {{ $diasLibresConfigurados->has($semana['lunes']->format('Y-m-d')) ? 'Configurada' : 'Sin configurar' }}
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title text-primary">{{ $semana['mes'] }}</h6>
                                        <p class="card-text">
                                            <strong>Rango:</strong> {{ $semana['rango'] }}
                                        </p>
                                        
                                        @if($diasLibresConfigurados->has($semana['lunes']->format('Y-m-d')))
                                            @php
                                                $configuracion = $diasLibresConfigurados[$semana['lunes']->format('Y-m-d')];
                                                $diasNombres = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                                $diasLibresNombres = collect($configuracion->dias_libres)->map(function($dia) use ($diasNombres) {
                                                    return $diasNombres[$dia];
                                                })->implode(', ');
                                            @endphp
                                            <div class="mb-2">
                                                <strong>Días libres:</strong>
                                                <span class="text-success">{{ $diasLibresNombres ?: 'Ninguno' }}</span>
                                            </div>
                                            @if($configuracion->observaciones)
                                                <div class="mb-2">
                                                    <strong>Observaciones:</strong>
                                                    <small class="text-muted">{{ $configuracion->observaciones }}</small>
                                                </div>
                                            @endif
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Actualizado: {{ $configuracion->updated_at->format('d/m/Y H:i') }}
                                                </small>
                                            </div>
                                        @else
                                            <p class="text-muted mb-0">No hay días libres configurados para esta semana.</p>
                                        @endif
                                    </div>
                                    <div class="card-footer">
                                        <div class="btn-group w-100" role="group">
                                            @if($diasLibresConfigurados->has($semana['lunes']->format('Y-m-d')))
                                                <a href="{{ route('admin.empleada-dias-libres.create', ['empleadaHorario' => $empleadaHorario, 'semana' => $semana['lunes']->format('Y-m-d')]) }}" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </a>
                                                <form method="POST" action="{{ route('admin.empleada-dias-libres.destroy', ['empleadaHorario' => $empleadaHorario, 'semanaInicio' => $semana['lunes']->format('Y-m-d')]) }}" 
                                                      class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar la configuración de días libres para esta semana?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-trash me-1"></i>Eliminar
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('admin.empleada-dias-libres.create', ['empleadaHorario' => $empleadaHorario, 'semana' => $semana['lunes']->format('Y-m-d')]) }}" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-plus me-1"></i>Configurar
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                                    <h5>No hay semanas en el rango seleccionado</h5>
                                    <p>Selecciona un rango de fechas diferente para ver las semanas disponibles.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.border-success {
    border-color: #28a745 !important;
}

.border-light {
    border-color: #f8f9fa !important;
}

.btn-group .btn {
    flex: 1;
}

.card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}
</style>
@endsection
