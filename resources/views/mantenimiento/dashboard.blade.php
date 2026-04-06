@extends('layouts.appPersonal')

@section('title', 'Dashboard - Mantenimiento')

@section('content')
<div class="limpiadora-dashboard mantenimiento-dashboard">
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 15px; border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <i class="fas fa-check-circle"></i>
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header del Dashboard -->
    <div class="dashboard-header" style="background: linear-gradient(135deg, #F5A623 0%, #E07C1C 100%);">
        <div class="welcome-section">
            <h1>¡Hola, {{ Auth::user()->name }}!</h1>
            <p>{{ $datos['diaSemana'] }}, {{ $datos['hoy'] }}</p>
            <p class="mb-0 mt-2 opacity-90"><i class="fas fa-tools me-1"></i> Panel de Mantenimiento</p>
        </div>
    </div>

    <!-- 1. Apartamentos limpiados hoy (primero) -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="apple-card-title">
                <i class="fas fa-broom"></i>
                <span>Apartamentos limpiados hoy</span>
            </div>
        </div>
        <div class="apple-card-body">
            @if($datos['apartamentosLimpiadosHoy']->count() > 0)
                <div class="tareas-list">
                    @foreach($datos['apartamentosLimpiadosHoy'] as $limpieza)
                        <a href="{{ route('mantenimiento.limpieza.ver', $limpieza->id) }}" class="tarea-item text-decoration-none text-dark limpieza-item">
                            <div class="tarea-header">
                                <div class="tarea-info">
                                    <div class="tarea-tipo">
                                        <i class="fas fa-home"></i>
                                        <span class="tarea-nombre">{{ $limpieza->apartamento?->nombre ?? $limpieza->apartamento?->titulo ?? 'Apartamento #'.$limpieza->apartamento_id }}</span>
                                        @if($limpieza->apartamento && $limpieza->apartamento->edificioName)
                                            <small class="elemento-nombre">{{ $limpieza->apartamento->edificioName->nombre }}</small>
                                        @endif
                                    </div>
                                    @if($limpieza->empleada)
                                        <small class="text-muted">Limpieza por {{ $limpieza->empleada->name }}</small>
                                    @endif
                                </div>
                                @if($limpieza->status_id == 3)
                                    <span class="badge bg-success">Completada</span>
                                @else
                                    <span class="badge bg-info">En limpieza</span>
                                @endif
                                <i class="fas fa-chevron-right text-muted ms-2"></i>
                            </div>
                            @if($limpieza->fecha_fin)
                                <div class="tarea-observaciones"><small class="text-muted">Finalizada {{ $limpieza->fecha_fin->format('H:i') }}</small></div>
                            @endif
                        </a>
                    @endforeach
                </div>
                <p class="text-muted small mt-2 mb-0">Pulsa en un apartamento para ver todo el detalle de la limpieza (checklist, fotos, etc.).</p>
            @else
                <p class="text-muted mb-0">No hay apartamentos limpiados hoy.</p>
            @endif
        </div>
    </div>

    <!-- 2. Reservas que entran hoy -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="apple-card-title">
                <i class="fas fa-calendar-check"></i>
                <span>Reservas que entran hoy</span>
            </div>
        </div>
        <div class="apple-card-body">
            @if($datos['reservasEntradaHoy']->count() > 0)
                <div class="tareas-list">
                    @foreach($datos['reservasEntradaHoy'] as $reserva)
                        <div class="tarea-item reserva-item">
                            <div class="tarea-header">
                                <div class="tarea-info">
                                    <div class="tarea-tipo">
                                        <i class="fas fa-key"></i>
                                        <span class="tarea-nombre">{{ $reserva->apartamento?->nombre ?? $reserva->apartamento?->titulo ?? 'Apartamento' }}</span>
                                        @if($reserva->apartamento && $reserva->apartamento->edificioName)
                                            <small class="elemento-nombre">{{ $reserva->apartamento->edificioName->nombre }}</small>
                                        @endif
                                    </div>
                                    @if($reserva->cliente)
                                        <small class="text-muted">
                                            {{ $reserva->cliente->nombre ?? $reserva->cliente->alias ?? 'Cliente' }}{{ $reserva->cliente->apellido1 ? ' '.$reserva->cliente->apellido1 : '' }}
                                            @if($reserva->codigo_reserva) · {{ $reserva->codigo_reserva }} @endif
                                        </small>
                                    @endif
                                </div>
                                <span class="badge bg-info">Entrada hoy</span>
                            </div>
                            <div class="tarea-observaciones">
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}
                                    @if($reserva->fecha_salida) → Salida {{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') }} @endif
                                </small>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted mb-0">No hay reservas con entrada hoy.</p>
            @endif
        </div>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 166, 35, 0.2); color: #E07C1C;">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">—</div>
                <div class="stat-label">Tareas hoy</div>
                <small class="stat-detail">Próximamente</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 166, 35, 0.2); color: #E07C1C;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">—</div>
                <div class="stat-label">Completadas</div>
                <small class="stat-detail">Próximamente</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 166, 35, 0.2); color: #E07C1C;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $datos['incidenciasPendientesCount'] }}</div>
                <div class="stat-label">Incidencias sin ver</div>
                <small class="stat-detail">pendientes de revisar</small>
            </div>
        </div>
    </div>

    <!-- Incidencias pendientes y botón Ver todas -->
    <div class="apple-card">
        <div class="apple-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="apple-card-title">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Incidencias pendientes</span>
            </div>
            <a href="{{ route('mantenimiento.incidencias.index') }}" class="apple-btn apple-btn-primary">
                <i class="fas fa-list me-1"></i> Ver todas las incidencias
            </a>
        </div>
        <div class="apple-card-body">
            @if($datos['incidenciasPendientes']->count() > 0)
                <div class="tareas-list">
                    @foreach($datos['incidenciasPendientes'] as $incidencia)
                        <a href="{{ route('mantenimiento.incidencias.show', $incidencia) }}" class="tarea-item pendiente text-decoration-none text-dark">
                            <div class="tarea-header">
                                <div class="tarea-info">
                                    <div class="tarea-tipo">
                                        <i class="fas fa-{{ $incidencia->tipo === 'apartamento' ? 'home' : 'building' }}"></i>
                                        <span class="tarea-nombre">{{ $incidencia->titulo }}</span>
                                        <small class="elemento-nombre">{{ $incidencia->apartamento?->nombre ?? $incidencia->zonaComun?->nombre ?? '—' }}</small>
                                    </div>
                                    <span class="badge bg-{{ $incidencia->prioridad === 'urgente' ? 'danger' : ($incidencia->prioridad === 'alta' ? 'warning' : 'info') }}">
                                        {{ ucfirst($incidencia->prioridad) }}
                                    </span>
                                </div>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($incidencia->created_at)->format('d/m/Y H:i') }}</small>
                            </div>
                            @if($incidencia->descripcion)
                                <div class="tarea-observaciones"><small class="text-muted">{{ Str::limit($incidencia->descripcion, 80) }}</small></div>
                            @endif
                        </a>
                    @endforeach
                </div>
                <div class="mt-3 text-center">
                    <a href="{{ route('mantenimiento.incidencias.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-list me-1"></i> Ver todas las incidencias
                    </a>
                </div>
            @else
                <p class="text-muted mb-0">No hay incidencias pendientes.</p>
                <a href="{{ route('mantenimiento.incidencias.index') }}" class="apple-btn apple-btn-primary mt-3">
                    <i class="fas fa-list me-1"></i> Ver todas las incidencias
                </a>
            @endif
        </div>
    </div>

    <!-- Bloque informativo -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="apple-card-title">
                <i class="fas fa-tools"></i>
                <span>Bienvenido al panel de Mantenimiento</span>
            </div>
        </div>
        <div class="apple-card-body">
            <p class="text-muted mb-0">
                Aquí podrás ver tus tareas de mantenimiento asignadas, incidencias y accesos rápidos cuando estén configurados.
            </p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/limpiadora-dashboard.css') }}">
<style>
/* Fondo celeste para el dashboard de mantenimiento */
.mantenimiento-dashboard {
    background: linear-gradient(180deg, #E3F2FD 0%, #BBDEFB 50%, #E8F4FC 100%);
    min-height: calc(100vh - 140px);
    padding-bottom: 2rem;
}
body:has(.mantenimiento-dashboard) .contendor {
    background: transparent;
}
/* Espacio entre cards: que no queden pegadas */
.mantenimiento-dashboard .dashboard-header {
    margin-bottom: 1.5rem;
}
.mantenimiento-dashboard .apple-card {
    margin-bottom: 1.5rem !important;
}
.mantenimiento-dashboard .apple-card:last-of-type {
    margin-bottom: 0;
}
/* Padding lateral en cards para que el contenido no quede pegado */
.mantenimiento-dashboard .apple-card-header {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
}
.mantenimiento-dashboard .apple-card-body {
    padding: 1.25rem 1.5rem;
}
.mantenimiento-dashboard .tarea-item {
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}
.mantenimiento-dashboard .tareas-list {
    gap: 1rem;
}
.mantenimiento-dashboard .stats-grid {
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.mantenimiento-dashboard .stat-card {
    padding: 1.25rem 1rem;
}
</style>
@endpush
