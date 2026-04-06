@extends('layouts.appAdmin')

@section('title', 'Gestión de Limpiezas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-microscope"></i> Gestión de Limpiezas
                </h1>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-info" onclick="cargarEstadisticas()">
                        <i class="fas fa-chart-bar"></i> Estadísticas
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarDatos()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>

            <!-- Resumen Estadístico -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Limpiezas Hoy
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $limpiezasHoy }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-purple shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-purple text-uppercase mb-1">
                                        Zonas Comunes Hoy
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $zonasHoy }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-building fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Apartamentos Hoy
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $apartamentosHoy }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-home fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Completadas Hoy
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $completadasHoy }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Con Consentimiento
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $conConsentimiento }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-filter"></i> Filtros
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.limpiezas.index') }}" id="filtrosForm">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="{{ $filtros['fecha_desde'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                                       value="{{ $filtros['fecha_hasta'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label for="empleada" class="form-label">Empleada</label>
                                <select class="form-control" id="empleada" name="empleada">
                                    <option value="">Todas</option>
                                    @foreach($empleadas as $empleada)
                                        <option value="{{ $empleada->id }}" 
                                                {{ $filtros['empleada'] == $empleada->id ? 'selected' : '' }}>
                                            {{ $empleada->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="apartamento" class="form-label">Apartamento/Zona Común</label>
                                <select class="form-control" id="apartamento" name="apartamento">
                                    <option value="">Todos</option>
                                    <option value="zona_comun" {{ $filtros['apartamento'] == 'zona_comun' ? 'selected' : '' }}>
                                        🏢 Todas las Zonas Comunes
                                    </option>
                                    @foreach($apartamentos as $apartamento)
                                        <option value="{{ $apartamento->id }}" 
                                                {{ $filtros['apartamento'] == $apartamento->id ? 'selected' : '' }}>
                                            🏠 {{ $apartamento->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    @foreach($estados as $estado)
                                        <option value="{{ $estado->id }}" 
                                                {{ $filtros['estado'] == $estado->id ? 'selected' : '' }}>
                                            {{ $estado->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="con_fotos" class="form-label">Con Fotos</label>
                                <select class="form-control" id="con_fotos" name="con_fotos">
                                    <option value="">Todos</option>
                                    <option value="si" {{ $filtros['con_fotos'] == 'si' ? 'selected' : '' }}>Sí</option>
                                    <option value="no" {{ $filtros['con_fotos'] == 'no' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-2">
                                <label for="con_analisis" class="form-label">Con Análisis</label>
                                <select class="form-control" id="con_analisis" name="con_analisis">
                                    <option value="">Todos</option>
                                    <option value="si" {{ $filtros['con_analisis'] == 'si' ? 'selected' : '' }}>Sí</option>
                                    <option value="no" {{ $filtros['con_analisis'] == 'no' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_hoy" class="form-label">Solo Hoy</label>
                                <select class="form-control" id="fecha_hoy" name="fecha_hoy">
                                    <option value="">Todas las fechas</option>
                                    <option value="si" {{ $filtros['fecha_hoy'] == 'si' ? 'selected' : '' }}>Solo limpiezas de hoy</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="con_consentimiento" class="form-label">Consentimiento</label>
                                <select class="form-control" id="con_consentimiento" name="con_consentimiento">
                                    <option value="">Todos</option>
                                    <option value="si" {{ $filtros['con_consentimiento'] == 'si' ? 'selected' : '' }}>Con Consentimiento</option>
                                    <option value="no" {{ $filtros['con_consentimiento'] == 'no' ? 'selected' : '' }}>Sin Consentimiento</option>
                                </select>
                            </div>
                            <div class="col-md-10 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Aplicar Filtros
                                </button>
                                <a href="{{ route('admin.limpiezas.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Limpiezas -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Limpiezas ({{ $limpiezas->total() }})
                    </h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary">{{ $limpiezas->count() }} mostradas</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($limpiezas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="limpiezasTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Elemento</th>
                                        <th>Tipo</th>
                                        <th>Empleada</th>
                                        <th>Estado</th>
                                        <th>Fecha Asignación</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Items Completados</th>
                                        <th>Consentimiento</th>
                                        <th>Fotos</th>
                                        <th>Análisis</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($limpiezas as $limpieza)
                                        <tr class="@if($limpieza->zonaComun) zona-comun-row @elseif($limpieza->apartamento) apartamento-row @endif" 
                                            data-debug="tipo:{{ $limpieza->tipo_limpieza ?? 'null' }}, apartamento_id:{{ $limpieza->apartamento_id ?? 'null' }}, zona_comun_id:{{ $limpieza->zona_comun_id ?? 'null' }}">
                                            <td>
                                                <strong>#{{ $limpieza->id }}</strong>
                                            </td>
                                            <td>
                                                @if($limpieza->apartamento)
                                                    <span class="badge bg-info">
                                                        {{ $limpieza->apartamento->nombre }}
                                                    </span>
                                                @elseif($limpieza->zonaComun)
                                                    <span class="badge bg-purple zona-comun-badge">
                                                        <i class="fas fa-building me-1"></i>
                                                        {{ $limpieza->zonaComun->nombre }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        N/A 
                                                        @if($limpieza->tipo_limpieza)
                                                            ({{ $limpieza->tipo_limpieza }})
                                                        @endif
                                                        <br>
                                                        <small>
                                                            apartamento_id: {{ $limpieza->apartamento_id ?? 'null' }}<br>
                                                            zona_comun_id: {{ $limpieza->zona_comun_id ?? 'null' }}<br>
                                                            tipo_limpieza: {{ $limpieza->tipo_limpieza ?? 'null' }}
                                                        </small>
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($limpieza->tipo_limpieza === 'zona_comun')
                                                    <span class="badge zona-comun-tipo-badge">
                                                        <i class="fas fa-building me-1"></i> Zona Común
                                                    </span>
                                                @else
                                                    <span class="badge zona-apartamento-tipo-badge">
                                                        <i class="fas fa-home me-1"></i> Apartamento
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($limpieza->empleada)
                                                    <span class="badge bg-success">
                                                        {{ $limpieza->empleada->name }}
                                                    </span>
                                                @elseif($limpieza->user)
                                                    <span class="badge bg-info">
                                                        {{ $limpieza->user->name }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">No asignada</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($limpieza->estado)
                                                    <span class="badge bg-{{ $limpieza->estado->id == 1 ? 'warning' : ($limpieza->estado->id == 2 ? 'info' : 'success') }}">
                                                        {{ $limpieza->estado->nombre }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted" title="Cuándo se asignó la limpieza">
                                                    {{ $limpieza->created_at ? $limpieza->created_at->format('d/m/Y H:i') : 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $limpieza->fecha_comienzo ? $limpieza->fecha_comienzo->format('d/m/Y H:i') : 'N/A' }}
                                            </td>
                                            <td>
                                                {{ $limpieza->fecha_fin ? $limpieza->fecha_fin->format('d/m/Y H:i') : 'N/A' }}
                                            </td>
                                            <td>
                                                @php
                                                    // Use preloaded checklist counts to avoid N+1 queries
                                                    if ($limpieza->tarea_asignada_id) {
                                                        $counts = $checklistCounts[$limpieza->tarea_asignada_id] ?? null;
                                                        $itemsCompletados = $counts ? $counts->completados : 0;
                                                        $totalItems = $counts ? $counts->total : 0;
                                                    } else {
                                                        // Lógica antigua: usar itemsMarcados (already eager loaded)
                                                        $itemsCompletados = $limpieza->itemsMarcados->where('estado', 1)->pluck('item_id')->filter()->unique()->count();
                                                        $totalItems = $limpieza->itemsMarcados->pluck('item_id')->filter()->unique()->count();
                                                    }
                                                    $porcentaje = $totalItems > 0 ? round(($itemsCompletados / $totalItems) * 100, 1) : 0;
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 60px; height: 20px;">
                                                        <div class="progress-bar bg-{{ $porcentaje == 100 ? 'success' : ($porcentaje > 50 ? 'warning' : 'danger') }}" 
                                                             style="width: {{ $porcentaje }}%"></div>
                                                    </div>
                                                    <span class="badge bg-{{ $porcentaje == 100 ? 'success' : ($porcentaje > 50 ? 'warning' : 'danger') }}">
                                                        {{ $porcentaje }}%
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                @if($limpieza->consentimiento_finalizacion)
                                                    <div class="d-flex flex-column align-items-center">
                                                        <span class="badge bg-warning mb-1">
                                                            <i class="fas fa-exclamation-triangle me-1"></i> Con Consentimiento
                                                        </span>
                                                        @if($limpieza->fecha_consentimiento)
                                                            <small class="text-muted">
                                                                {{ $limpieza->fecha_consentimiento->format('d/m/Y H:i') }}
                                                            </small>
                                                        @endif
                                                        @if($limpieza->motivo_consentimiento)
                                                            <small class="text-muted" title="{{ $limpieza->motivo_consentimiento }}">
                                                                <i class="fas fa-info-circle me-1"></i> Ver motivo
                                                            </small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i> Sin Consentimiento
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($limpieza->total_fotos > 0)
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-camera"></i> {{ $limpieza->total_fotos }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($limpieza->analisis->count() > 0)
                                                    @php
                                                        $aprobadas = $limpieza->analisis->where('cumple_estandares', true)->count();
                                                        $rechazadas = $limpieza->analisis->where('cumple_estandares', false)->count();
                                                        $bajoResponsabilidad = $limpieza->analisis->where('continuo_bajo_responsabilidad', true)->count();
                                                    @endphp
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-success">{{ $aprobadas }} ✓</span>
                                                        @if($rechazadas > 0)
                                                            <span class="badge bg-danger">{{ $rechazadas }} ✗</span>
                                                        @endif
                                                        @if($bajoResponsabilidad > 0)
                                                            <span class="badge bg-warning">{{ $bajoResponsabilidad }} ⚠️</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="badge bg-secondary">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.limpiezas.show', $limpieza->id) }}" 
                                                       class="btn btn-sm btn-primary" title="Ver Detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('gestion.edit', $limpieza->id) }}" 
                                                       class="btn btn-sm btn-info" title="Editar Limpieza" target="_blank">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if($limpieza->analisis->count() > 0)
                                                        <a href="{{ route('limpiezas.analisis') }}?limpieza_id={{ $limpieza->id }}" 
                                                           class="btn btn-sm btn-warning" title="Ver Análisis">
                                                            <i class="fas fa-microscope"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $limpiezas->appends($filtros)->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay limpiezas disponibles</h5>
                            <p class="text-muted">Aplica otros filtros o espera a que se realicen nuevas limpiezas</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Estadísticas -->
<div class="modal fade" id="estadisticasModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar"></i> Estadísticas de Limpiezas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="estadisticasContent">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos de la tabla */
.table {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table thead {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table thead th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
    border: none;
    padding: 12px 8px;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fc;
}

.table tbody td {
    padding: 12px 8px;
    vertical-align: middle;
    border-color: #e9ecef;
}

/* Estilos de progreso */
.progress {
    background-color: #e9ecef;
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
}

/* Estilos de badges */
.badge {
    font-size: 0.8em;
    font-weight: 500;
}

/* Estilos de botones */
.btn-group .btn {
    margin-right: 2px;
}

/* Estilos de la tabla ya están definidos arriba */

.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}
</style>

<script>
function cargarEstadisticas() {
    const modal = document.getElementById('estadisticasModal');
    const content = document.getElementById('estadisticasContent');
    
    content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando estadísticas...</p></div>';
    
    console.log('🔄 Iniciando carga de estadísticas...');
    
    fetch('{{ route("admin.limpiezas.estadisticas") }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            console.log('📡 Respuesta del servidor:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Datos recibidos:', data);
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Limpiezas
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">${data.total_limpiezas}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Hoy
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">${data.limpiezas_hoy}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Esta Semana
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">${data.limpiezas_semana}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Análisis IA
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">${data.analisis_stats.total}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-robot fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-chart-pie"></i> Estados de Limpieza</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Estado</th>
                                        <th>Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.estados_stats.map(estado => `
                                        <tr>
                                            <td>${estado.nombre}</td>
                                            <td><span class="badge bg-primary">${estado.total}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-users"></i> Empleadas Más Activas</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Empleada</th>
                                        <th>Limpiezas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.empleadas_activas.map(emp => `
                                        <tr>
                                            <td>${emp.empleada ? emp.empleada.name : 'N/A'}</td>
                                            <td><span class="badge bg-success">${emp.total}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <h6><i class="fas fa-microscope"></i> Análisis de Fotos</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-success">${data.analisis_stats.aprobadas}</div>
                                    <small class="text-muted">Aprobadas</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-danger">${data.analisis_stats.rechazadas}</div>
                                    <small class="text-muted">Rechazadas</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-warning">${data.analisis_stats.bajo_responsabilidad}</div>
                                    <small class="text-muted">Bajo Responsabilidad</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-info">${data.analisis_stats.total}</div>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> Error al cargar las estadísticas</h6>
                    <p class="mb-0">${error.message}</p>
                    <button class="btn btn-sm btn-outline-danger mt-2" onclick="cargarEstadisticas()">
                        <i class="fas fa-redo"></i> Reintentar
                    </button>
                </div>
            `;
        });
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

function exportarDatos() {
    // Implementar exportación de datos
    alert('Función de exportación en desarrollo');
}

// Inicializar DataTable
document.addEventListener('DOMContentLoaded', function() {
            if (typeof $ !== 'undefined') {
            $('#limpiezasTable').DataTable({
                language: {
                    "sProcessing":     "Procesando...",
                    "sLengthMenu":     "Mostrar _MENU_ registros",
                    "sZeroRecords":    "No se encontraron resultados",
                    "sEmptyTable":     "Ningún dato disponible en esta tabla",
                    "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                    "sInfoPostFix":    "",
                    "sSearch":         "Buscar:",
                    "sUrl":            "",
                    "sInfoThousands":  ",",
                    "sLoadingRecords": "Cargando...",
                    "oPaginate": {
                        "sFirst":    "Primero",
                        "sLast":     "Último",
                        "sNext":     "Siguiente",
                        "sPrevious": "Anterior"
                    },
                    "oAria": {
                        "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                },
                pageLength: 25,
                order: [[0, 'desc']],
                "drawCallback": function(settings) {
                    // Forzar aplicación de estilos después de cada redibujado
                    setTimeout(function() {
                        aplicarEstilosFilas();
                    }, 100);
                }
            });
        } else {
            console.warn('jQuery no está disponible, DataTable no se inicializará');
        }
});

// Función para aplicar estilos a las filas - MÁS AGRESIVA
function aplicarEstilosFilas() {
    const filas = document.querySelectorAll('#limpiezasTable tbody tr');
    filas.forEach(fila => {
        // FORZAR estilos directamente en el elemento
        if (fila.classList.contains('zona-comun-row')) {
            // Zona común - PÚRPURA
            fila.style.setProperty('background-color', 'rgba(139, 92, 246, 0.12)', 'important');
            fila.style.setProperty('border-left', '4px solid #8B5CF6', 'important');
            fila.style.setProperty('border-right', '4px solid #8B5CF6', 'important');
            
            // Aplicar también a todas las celdas de la fila
            const celdas = fila.querySelectorAll('td');
            celdas.forEach(celda => {
                celda.style.setProperty('background-color', 'rgba(139, 92, 246, 0.08)', 'important');
                celda.style.setProperty('border-color', 'rgba(139, 92, 246, 0.3)', 'important');
            });
            
        } else if (fila.classList.contains('apartamento-row')) {
            // Apartamento - AZUL
            fila.style.setProperty('background-color', 'rgba(59, 130, 246, 0.08)', 'important');
            fila.style.setProperty('border-left', '4px solid #3B82F6', 'important');
            fila.style.setProperty('border-right', '4px solid #3B82F6', 'important');
            
            // Aplicar también a todas las celdas de la fila
            const celdas = fila.querySelectorAll('td');
            celdas.forEach(celda => {
                celda.style.setProperty('background-color', 'rgba(59, 130, 246, 0.05)', 'important');
                celda.style.setProperty('border-color', 'rgba(59, 130, 246, 0.3)', 'important');
            });
        }
    });
}

// Aplicar estilos cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(aplicarEstilosFilas, 500);
    
    // Observador de mutaciones para detectar cambios en DataTables
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // Si se agregaron o removieron nodos, aplicar estilos
                setTimeout(aplicarEstilosFilas, 100);
            }
        });
    });
    
    // Observar cambios en la tabla
    const tabla = document.querySelector('#limpiezasTable tbody');
    if (tabla) {
        observer.observe(tabla, {
            childList: true,
            subtree: true
        });
    }
    
    // Aplicar estilos cada 2 segundos como respaldo
    setInterval(aplicarEstilosFilas, 2000);
});
</script>
@endsection

@section('styles')
<style>
/* Estilos para diferenciar zonas comunes */
.zona-comun-badge {
    background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%) !important;
    color: white !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3) !important;
    font-weight: 500 !important;
    padding: 8px 12px !important;
    border-radius: 20px !important;
    transition: all 0.3s ease !important;
}

/* Badges de tipo personalizados */
.zona-comun-tipo-badge {
    background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%) !important;
    color: white !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3) !important;
    font-weight: 500 !important;
    padding: 6px 10px !important;
    border-radius: 15px !important;
    transition: all 0.3s ease !important;
}

.zona-apartamento-tipo-badge {
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%) !important;
    color: white !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3) !important;
    font-weight: 500 !important;
    padding: 6px 10px !important;
    border-radius: 15px !important;
    transition: all 0.3s ease !important;
}

.zona-comun-tipo-badge:hover,
.zona-apartamento-tipo-badge:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
}

.zona-comun-badge:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4) !important;
}

.zona-comun-badge i {
    font-size: 12px !important;
    opacity: 0.9 !important;
}

/* Estilos para filas de zonas comunes - MÁS ESPECÍFICOS */
table#limpiezasTable tbody tr.zona-comun-row {
    background-color: rgba(139, 92, 246, 0.08) !important;
    border-left: 4px solid #8B5CF6 !important;
}

table#limpiezasTable tbody tr.zona-comun-row:hover {
    background-color: rgba(139, 92, 246, 0.15) !important;
    border-left: 4px solid #7C3AED !important;
}

/* Estilos para filas de apartamentos - MÁS ESPECÍFICOS */
table#limpiezasTable tbody tr.apartamento-row {
    background-color: rgba(59, 130, 246, 0.05) !important;
    border-left: 4px solid #3B82F6 !important;
}

table#limpiezasTable tbody tr.apartamento-row:hover {
    background-color: rgba(59, 130, 246, 0.12) !important;
    border-left: 4px solid #2563EB !important;
}

/* Estilos para filas alternadas (zebra) - SOBRESCRIBIR */
table#limpiezasTable tbody tr:nth-child(even) {
    background-color: inherit !important;
}

table#limpiezasTable tbody tr:nth-child(odd) {
    background-color: inherit !important;
}

/* Estilos para el filtro de zonas comunes */
select option[value="zona_comun"] {
    background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%) !important;
    color: white !important;
    font-weight: 500 !important;
}

/* Estilos para apartamentos en el filtro */
select option:not([value="zona_comun"]):not([value=""]) {
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%) !important;
    color: white !important;
    font-weight: 500 !important;
}

/* Estilos para las tarjetas de resumen */
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-purple {
    border-left: 0.25rem solid #8B5CF6 !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

/* Hover effects para las tarjetas */
.card:hover {
    transform: translateY(-2px);
    transition: transform 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

/* Estilos adicionales para DataTables - MÁS AGRESIVOS */
.dataTables_wrapper .dataTables_scrollBody {
    background: transparent !important;
}

/* SOBRESCRIBIR COMPLETAMENTE los estilos de DataTables */
#limpiezasTable tbody tr.zona-comun-row,
#limpiezasTable tbody tr.zona-comun-row td,
#limpiezasTable tbody tr.zona-comun-row th {
    background-color: rgba(139, 92, 246, 0.12) !important;
    border-color: rgba(139, 92, 246, 0.4) !important;
}

#limpiezasTable tbody tr.apartamento-row,
#limpiezasTable tbody tr.apartamento-row td,
#limpiezasTable tbody tr.apartamento-row th {
    background-color: rgba(59, 130, 246, 0.08) !important;
    border-color: rgba(59, 130, 246, 0.4) !important;
}

/* Hover effects - SOBRESCRIBIR */
#limpiezasTable tbody tr.zona-comun-row:hover,
#limpiezasTable tbody tr.zona-comun-row:hover td {
    background-color: rgba(139, 92, 246, 0.2) !important;
    border-color: rgba(139, 92, 246, 0.6) !important;
}

#limpiezasTable tbody tr.apartamento-row:hover,
#limpiezasTable tbody tr.apartamento-row:hover td {
    background-color: rgba(59, 130, 246, 0.15) !important;
    border-color: rgba(59, 130, 246, 0.6) !important;
}

/* SOBRESCRIBIR estilos de filas alternadas de DataTables */
#limpiezasTable tbody tr:nth-child(even),
#limpiezasTable tbody tr:nth-child(odd) {
    background-color: inherit !important;
}

/* SOBRESCRIBIR estilos de hover por defecto de DataTables */
#limpiezasTable tbody tr:hover {
    background-color: inherit !important;
}
</style>
@endsection
