@extends('layouts.appPersonal')

@section('title', 'Todas las incidencias - Mantenimiento')

@section('content')
<div class="apple-container mantenimiento-incidencias-page">
    <div class="apple-card">
        <div class="apple-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fa-solid fa-exclamation-triangle"></i>
                        </div>
                        <div class="apartment-details">
                            <h1 class="apartment-title">Todas las incidencias</h1>
                            <p class="apartment-subtitle">Total: {{ $incidencias->total() }} incidencias</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('mantenimiento.incidencias.create') }}" class="apple-btn apple-btn-primary">
                    <i class="fas fa-plus me-1"></i> Nueva Incidencia
                </a>
                <a href="{{ route('mantenimiento.dashboard') }}" class="apple-btn apple-btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Inicio
                </a>
            </div>
        </div>

        <div class="apple-stats-section">
            <div class="apple-stat-item">
                <div class="stat-content">
                    <div class="stat-number">{{ $estadisticas['pendientes'] }}</div>
                    <div class="stat-label">Pendientes</div>
                </div>
                <div class="stat-icon stat-pending"><i class="fas fa-clock"></i></div>
            </div>
            <div class="apple-stat-item">
                <div class="stat-content">
                    <div class="stat-number">{{ $estadisticas['en_proceso'] }}</div>
                    <div class="stat-label">En proceso</div>
                </div>
                <div class="stat-icon stat-process"><i class="fas fa-tools"></i></div>
            </div>
            <div class="apple-stat-item">
                <div class="stat-content">
                    <div class="stat-number">{{ $estadisticas['resueltas'] }}</div>
                    <div class="stat-label">Resueltas</div>
                </div>
                <div class="stat-icon stat-resolved"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>

    {{-- Filtro por estado --}}
    <form method="GET" class="mb-3">
        <select name="estado" class="form-select form-select-sm w-auto d-inline-block" onchange="this.form.submit()">
            <option value="">Todas</option>
            <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendientes</option>
            <option value="en_proceso" {{ request('estado') === 'en_proceso' ? 'selected' : '' }}>En proceso</option>
            <option value="resuelta" {{ request('estado') === 'resuelta' ? 'selected' : '' }}>Resueltas</option>
        </select>
    </form>

    <div class="apple-card">
        <div class="apple-card-body">
            @if($incidencias->count() > 0)
                <div class="apple-list">
                    @foreach($incidencias as $incidencia)
                        <a href="{{ route('mantenimiento.incidencias.show', $incidencia) }}" class="apple-list-item apple-list-item-{{ $incidencia->estado === 'pendiente' ? 'warning' : ($incidencia->estado === 'en_proceso' ? 'info' : ($incidencia->estado === 'resuelta' ? 'success' : 'secondary')) }} text-decoration-none text-dark d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div class="apple-list-content flex-grow-1">
                                <div class="apple-list-title">
                                    {{ $incidencia->titulo }}
                                    @if($incidencia->prioridad === 'urgente')
                                        <span class="badge bg-danger ms-2"><i class="fas fa-exclamation"></i> Urgente</span>
                                    @elseif($incidencia->prioridad === 'alta')
                                        <span class="badge bg-warning ms-2"><i class="fas fa-exclamation-triangle"></i> Alta</span>
                                    @endif
                                </div>
                                <div class="apple-list-subtitle">
                                    <div class="incident-details">
                                        <span class="incident-type">
                                            <i class="fas fa-{{ $incidencia->tipo === 'apartamento' ? 'building' : 'users' }}"></i>
                                            {{ $incidencia->tipo === 'apartamento' ? 'Apartamento' : 'Zona Común' }}
                                        </span>
                                        @if($incidencia->apartamento)
                                            <span class="incident-location"><i class="fas fa-map-marker-alt"></i> {{ $incidencia->apartamento->nombre }}</span>
                                        @elseif($incidencia->zonaComun)
                                            <span class="incident-location"><i class="fas fa-map-marker-alt"></i> {{ $incidencia->zonaComun->nombre }}</span>
                                        @endif
                                        <span class="incident-date"><i class="fas fa-calendar"></i> {{ \Carbon\Carbon::parse($incidencia->created_at)->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <div class="incident-description">{{ Str::limit($incidencia->descripcion, 100) }}</div>
                                </div>
                            </div>
                            <div class="apple-list-action">
                                <span class="badge bg-{{ $incidencia->estado === 'pendiente' ? 'warning' : ($incidencia->estado === 'en_proceso' ? 'info' : 'success') }}">{{ ucfirst(str_replace('_', ' ', $incidencia->estado)) }}</span>
                                <i class="fas fa-chevron-right ms-2 text-muted"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
                @if($incidencias->hasPages())
                    <div class="apple-pagination mt-4">{{ $incidencias->links() }}</div>
                @endif
            @else
                <div class="apple-empty-state">
                    <div class="empty-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="empty-title">No hay incidencias</div>
                    <div class="empty-subtitle">No se encontraron incidencias con los filtros aplicados.</div>
                    <a href="{{ route('mantenimiento.dashboard') }}" class="apple-btn apple-btn-primary mt-3"><i class="fas fa-arrow-left me-1"></i> Volver al inicio</a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.mantenimiento-incidencias-page { background: linear-gradient(180deg, #E3F2FD 0%, #BBDEFB 50%, #E8F4FC 100%); min-height: calc(100vh - 140px); padding-bottom: 2rem; }
body:has(.mantenimiento-incidencias-page) .contendor { background: transparent; }
.apple-card { background: #fff; border-radius: 15px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); margin-bottom: 20px; overflow: hidden; }
.mantenimiento-incidencias-page .apple-card-header { background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%) !important; padding: 20px; color: #fff !important; }
.mantenimiento-incidencias-page .apple-card-header .apartment-title,
.mantenimiento-incidencias-page .apple-card-header .apple-card-title { color: #fff !important; }
.mantenimiento-incidencias-page .apple-card-header .apartment-subtitle { color: #cbd5e1 !important; }
.apartment-icon { width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.apartment-icon i { font-size: 20px; color: #fff; }
.apartment-title { font-size: 20px; font-weight: 700; color: #fff; margin: 0 0 4px 0; }
.apartment-subtitle { font-size: 14px; color: rgba(255,255,255,0.9); margin: 0; }
.apple-card-body { padding: 20px; background: #fff; }
.apple-stats-section { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; padding: 20px; background: #fff; }
.apple-stat-item { background: #f8f9fa; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between; }
.stat-number { font-size: 1.8em; font-weight: bold; color: #333; }
.stat-label { color: #666; font-size: 0.85em; }
.stat-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; }
.stat-pending { background: #FF9800; }
.stat-process { background: #2196F3; }
.stat-resolved { background: #4CAF50; }
.apple-list { display: flex; flex-direction: column; gap: 12px; }
.apple-list-item { border-radius: 12px; padding: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: all 0.2s; }
.apple-list-item:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.apple-list-item-warning { border-left: 4px solid #FF9800; }
.apple-list-item-info { border-left: 4px solid #2196F3; }
.apple-list-item-success { border-left: 4px solid #4CAF50; }
.apple-list-item-secondary { border-left: 4px solid #9E9E9E; }
.incident-details { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 8px; }
.incident-type, .incident-location, .incident-date { font-size: 0.85em; color: #666; }
.incident-description { color: #555; font-size: 0.95em; }
.apple-empty-state { text-align: center; padding: 40px 20px; }
.empty-icon { font-size: 3em; color: #ccc; margin-bottom: 16px; }
.empty-title { font-size: 1.25em; font-weight: 600; color: #333; margin-bottom: 8px; }
.empty-subtitle { color: #666; }
@media (max-width: 768px) { .apple-stats-section { grid-template-columns: 1fr; } }
</style>
@endpush
@endsection
