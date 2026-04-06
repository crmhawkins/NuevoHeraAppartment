@extends('layouts.appPersonal')

@section('title', 'Mis Turnos')

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center text-white">Mis Turnos - {{ $user->name }}</h5>
@endsection

@section('content')
<div class="apple-container">
    <!-- Header con estadísticas -->
    <div class="apple-card mb-4">
        <div class="apple-card-header">
            <h4 class="mb-0">
                <i class="fas fa-calendar-week"></i> 
                Resumen de la Semana
            </h4>
        </div>
        <div class="apple-card-body">
            <div class="row text-center">
                <div class="col-6 col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-primary">{{ $estadisticas['total_turnos'] }}</div>
                        <div class="stat-label">Total Turnos</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-success">{{ $estadisticas['turnos_completados'] }}</div>
                        <div class="stat-label">Completados</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-warning">{{ $estadisticas['turnos_pendientes'] }}</div>
                        <div class="stat-label">Pendientes</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number text-info">{{ $estadisticas['dias_libres'] }}</div>
                        <div class="stat-label">Días Libres</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Controles de navegación de semana -->
    <div class="apple-card mb-4">
        <div class="apple-card-body">
            <div class="row align-items-center">
                <div class="col-4">
                    <a href="{{ route('gestion.mis-turnos', ['semana' => $semanaOffset - 1]) }}" 
                       class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-chevron-left"></i> Semana Anterior
                    </a>
                </div>
                <div class="col-4 text-center">
                    <h5 class="mb-0">
                        {{ $inicioSemana->format('d/m') }} - {{ $finSemana->format('d/m/Y') }}
                    </h5>
                    @if($semanaOffset == 0)
                        <small class="text-muted">Semana Actual</small>
                    @elseif($semanaOffset > 0)
                        <small class="text-muted">{{ $semanaOffset }} semana{{ $semanaOffset > 1 ? 's' : '' }} siguiente{{ $semanaOffset > 1 ? 's' : '' }}</small>
                    @else
                        <small class="text-muted">{{ abs($semanaOffset) }} semana{{ abs($semanaOffset) > 1 ? 's' : '' }} anterior{{ abs($semanaOffset) > 1 ? 'es' : '' }}</small>
                    @endif
                </div>
                <div class="col-4">
                    <a href="{{ route('gestion.mis-turnos', ['semana' => $semanaOffset + 1]) }}" 
                       class="btn btn-outline-primary btn-sm w-100">
                        Semana Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12 text-center">
                    <a href="{{ route('gestion.mis-turnos') }}" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-calendar-day"></i> Ir a Semana Actual
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendario semanal -->
    <div class="apple-card">
        <div class="apple-card-header">
            <h4 class="mb-0">
                <i class="fas fa-calendar-alt"></i> 
                Calendario Semanal
            </h4>
        </div>
        <div class="apple-card-body p-0">
            <div class="week-calendar">
                @foreach($diasSemana as $dia)
                    <div class="day-card {{ $dia['es_hoy'] ? 'today' : '' }} {{ $dia['es_libre'] ? 'free-day' : '' }}">
                        <div class="day-header">
                            <div class="day-name">{{ $dia['nombre_dia'] }}</div>
                            <div class="day-number">{{ $dia['numero_dia'] }}</div>
                            @if($dia['es_hoy'])
                                <div class="today-badge">HOY</div>
                            @endif
                            @if($dia['es_libre'])
                                <div class="free-badge">
                                    <i class="fas fa-umbrella-beach"></i>
                                    LIBRE
                                </div>
                            @endif
                        </div>
                        
                        <div class="day-content">
                            @if($dia['turnos']->count() > 0)
                                @foreach($dia['turnos'] as $turno)
                                    <div class="turno-item {{ $turno->estado }}">
                                        <div class="turno-time">
                                            <i class="fas fa-clock"></i>
                                            {{ \Carbon\Carbon::parse($turno->hora_inicio)->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($turno->hora_fin)->format('H:i') }}
                                        </div>
                                        <div class="turno-status">
                                            @switch($turno->estado)
                                                @case('pendiente')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock"></i> Pendiente
                                                    </span>
                                                    @break
                                                @case('en_progreso')
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-play"></i> En Progreso
                                                    </span>
                                                    @break
                                                @case('completado')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Completado
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ ucfirst($turno->estado) }}</span>
                                            @endswitch
                                        </div>
                                        
                                        @if($turno->tareasAsignadas->count() > 0)
                                            <div class="turno-tasks">
                                                <small class="text-muted">
                                                    <i class="fas fa-tasks"></i> 
                                                    {{ $turno->tareasAsignadas->count() }} tarea(s)
                                                </small>
                                                <div class="task-list">
                                                    @foreach($turno->tareasAsignadas as $tarea)
                                                        <div class="task-item">
                                                            @if($tarea->apartamento_id)
                                                                <i class="fas fa-building text-primary"></i>
                                                                {{ $tarea->apartamento->titulo ?? 'Apartamento' }}
                                                            @elseif($tarea->zona_comun_id)
                                                                <i class="fas fa-users text-info"></i>
                                                                {{ $tarea->zonaComun->nombre ?? 'Zona Común' }}
                                                            @else
                                                                <i class="fas fa-tasks text-secondary"></i>
                                                                {{ $tarea->tipoTarea->nombre ?? 'Tarea' }}
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="no-turnos">
                                    @if($dia['es_libre'])
                                        <i class="fas fa-umbrella-beach text-success"></i>
                                        <small>Día libre</small>
                                    @else
                                        <div class="turno-item default-schedule">
                                            <div class="turno-time">
                                                <i class="fas fa-clock"></i>
                                                {{ \Carbon\Carbon::parse($horarioPorDefecto['hora_inicio'])->format('H:i') }} - 
                                                {{ \Carbon\Carbon::parse($horarioPorDefecto['hora_fin'])->format('H:i') }}
                                            </div>
                                            <div class="turno-status">
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-calendar-day"></i> Horario habitual
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
.apple-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.stat-card {
    padding: 15px;
    border-radius: 10px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.week-calendar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    padding: 20px;
}

.day-card {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    background: white;
    transition: all 0.3s ease;
    min-height: 200px;
}

.day-card.today {
    border-color: #007AFF;
    box-shadow: 0 4px 15px rgba(0, 122, 255, 0.2);
}

.day-card.free-day {
    border-color: #28a745;
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
}

.day-header {
    padding: 15px;
    text-align: center;
    border-bottom: 1px solid #e9ecef;
    position: relative;
}

.day-name {
    font-weight: bold;
    color: #495057;
    margin-bottom: 5px;
}

.day-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #007AFF;
}

.today-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #007AFF;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: bold;
}

.free-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #28a745;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: bold;
}

.day-content {
    padding: 15px;
    min-height: 120px;
}

.turno-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 10px;
    border-left: 4px solid #007AFF;
}

.turno-item.default-schedule {
    background: #f8f9fa;
    border-left: 4px solid #6c757d;
    opacity: 0.8;
}

.turno-item.default-schedule .turno-time {
    color: #6c757d;
}

.turno-item.completado {
    border-left-color: #28a745;
    background: #f8fff9;
}

.turno-item.en_progreso {
    border-left-color: #17a2b8;
    background: #f0f9ff;
}

.turno-item.pendiente {
    border-left-color: #ffc107;
    background: #fffdf0;
}

.turno-time {
    font-weight: bold;
    color: #495057;
    margin-bottom: 5px;
}

.turno-status {
    margin-bottom: 8px;
}

.turno-tasks {
    margin-top: 8px;
}

.task-list {
    margin-top: 5px;
}

.task-item {
    padding: 3px 0;
    font-size: 0.85rem;
    color: #6c757d;
}

.no-turnos {
    text-align: center;
    color: #6c757d;
    padding: 20px 0;
}

.no-turnos i {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .week-calendar {
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 10px;
    }
    
    .day-card {
        min-height: 150px;
    }
    
    .stat-card {
        padding: 10px;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}

@media (max-width: 576px) {
    .apple-container {
        padding: 10px;
    }
    
    .day-header {
        padding: 10px;
    }
    
    .day-content {
        padding: 10px;
    }
}
</style>
@endsection