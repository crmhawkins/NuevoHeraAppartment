@extends('layouts.appAdmin')

@section('title', 'Gestión de Incidencias')

@section('tituloSeccion', 'Gestión de Incidencias')

@section('content')
<div class="container-fluid">
    <!-- Header con estadísticas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                        Gestión de Incidencias
                    </h1>
                    <p class="text-muted mb-0">Gestiona y resuelve incidencias reportadas por el personal</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.incidencias.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-sync-alt me-2"></i>Actualizar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard de estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $estadisticas['total'] }}</h4>
                    <small>Total</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $estadisticas['pendientes'] }}</h4>
                    <small>Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-danger text-white">
                <div class="card-body text-center">
                    <i class="fas fa-fire fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $estadisticas['urgentes'] }}</h4>
                    <small>Urgentes</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-day fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $estadisticas['hoy'] }}</h4>
                    <small>Hoy</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $estadisticas['resueltas_hoy'] }}</h4>
                    <small>Resueltas Hoy</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-filter me-2 text-primary"></i>
                Filtros y Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.incidencias.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label for="estado" class="form-label fw-semibold">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente" {{ $filtros['estado'] == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="en_proceso" {{ $filtros['estado'] == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                        <option value="resuelta" {{ $filtros['estado'] == 'resuelta' ? 'selected' : '' }}>Resuelta</option>
                        <option value="cerrada" {{ $filtros['estado'] == 'cerrada' ? 'selected' : '' }}>Cerrada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="prioridad" class="form-label fw-semibold">Prioridad</label>
                    <select name="prioridad" id="prioridad" class="form-select">
                        <option value="">Todas</option>
                        <option value="baja" {{ $filtros['prioridad'] == 'baja' ? 'selected' : '' }}>Baja</option>
                        <option value="media" {{ $filtros['prioridad'] == 'media' ? 'selected' : '' }}>Media</option>
                        <option value="alta" {{ $filtros['prioridad'] == 'alta' ? 'selected' : '' }}>Alta</option>
                        <option value="urgente" {{ $filtros['prioridad'] == 'urgente' ? 'selected' : '' }}>Urgente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tipo" class="form-label fw-semibold">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="apartamento" {{ $filtros['tipo'] == 'apartamento' ? 'selected' : '' }}>Apartamento</option>
                        <option value="zona_comun" {{ $filtros['tipo'] == 'zona_comun' ? 'selected' : '' }}>Zona Común</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="empleada" class="form-label fw-semibold">Empleada</label>
                    <select name="empleada" id="empleada" class="form-select">
                        <option value="">Todas</option>
                        @foreach($empleadas as $empleada)
                            <option value="{{ $empleada->id }}" {{ $filtros['empleada'] == $empleada->id ? 'selected' : '' }}>
                                {{ $empleada->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fecha_desde" class="form-label fw-semibold">Desde</label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="{{ $filtros['fecha_desde'] }}">
                </div>
                <div class="col-md-2">
                    <label for="fecha_hasta" class="form-label fw-semibold">Hasta</label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="{{ $filtros['fecha_hasta'] }}">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="hoy" value="si" id="hoy" {{ $filtros['hoy'] == 'si' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="hoy">
                            <i class="fas fa-calendar-day me-1 text-info"></i>
                            Solo incidencias de hoy
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filtrar
                        </button>
                        <a href="{{ route('admin.incidencias.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de incidencias -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Lista de Incidencias
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary px-3 py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ $incidencias->count() }} de {{ $incidencias->total() }} incidencias
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($incidencias->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">
                                    <i class="fas fa-hashtag me-1 text-primary"></i>
                                    ID
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-exclamation-triangle me-1 text-primary"></i>
                                    Incidencia
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-tag me-1 text-primary"></i>
                                    Tipo
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-flag me-1 text-primary"></i>
                                    Prioridad
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-info-circle me-1 text-primary"></i>
                                    Estado
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-user me-1 text-primary"></i>
                                    Empleada
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-calendar me-1 text-primary"></i>
                                    Fecha
                                </th>
                                <th class="border-0 text-center">
                                    <i class="fas fa-cogs me-1 text-primary"></i>
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incidencias as $incidencia)
                                <tr class="align-middle">
                                    <td>
                                        <span class="badge bg-secondary fs-6">#{{ $incidencia->id }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0 fw-semibold text-dark">{{ $incidencia->titulo }}</h6>
                                            @if($incidencia->apartamento)
                                                <small class="text-muted">
                                                    <i class="fas fa-home me-1"></i>{{ $incidencia->apartamento->nombre }}
                                                </small>
                                            @elseif($incidencia->zonaComun)
                                                <small class="text-muted">
                                                    <i class="fas fa-building me-1"></i>{{ $incidencia->zonaComun->nombre }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($incidencia->tipo == 'apartamento')
                                            <span class="badge bg-info">
                                                <i class="fas fa-home me-1"></i>Apartamento
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-building me-1"></i>Zona Común
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $prioridadClass = [
                                                'baja' => 'bg-success',
                                                'media' => 'bg-info',
                                                'alta' => 'bg-warning',
                                                'urgente' => 'bg-danger'
                                            ];
                                        @endphp
                                        <span class="badge {{ $prioridadClass[$incidencia->prioridad] }}">
                                            {{ ucfirst($incidencia->prioridad) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $estadoClass = [
                                                'pendiente' => 'bg-warning',
                                                'en_proceso' => 'bg-info',
                                                'resuelta' => 'bg-success',
                                                'cerrada' => 'bg-secondary'
                                            ];
                                        @endphp
                                        <span class="badge {{ $estadoClass[$incidencia->estado] }}">
                                            {{ ucfirst(str_replace('_', ' ', $incidencia->estado)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($incidencia->empleada)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    @if($incidencia->empleada->avatar)
                                                        <img src="{{ asset('storage/' . $incidencia->empleada->avatar) }}" 
                                                             alt="Avatar" class="rounded-circle" width="32" height="32">
                                                    @else
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 32px; height: 32px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $incidencia->empleada->name }}</div>
                                                    <small class="text-muted">{{ $incidencia->empleada->email }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $incidencia->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $incidencia->created_at->format('H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.incidencias.show', $incidencia) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.incidencias.edit', $incidencia) }}" 
                                               class="btn btn-sm btn-outline-warning" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($incidencia->estado !== 'resuelta')
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success" 
                                                        title="Marcar como resuelta"
                                                        onclick="mostrarModalResolver({{ $incidencia->id }})">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="d-flex justify-content-center py-4">
                    {{ $incidencias->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 fw-semibold">No hay incidencias</h4>
                        <p class="text-muted">No se encontraron incidencias con los filtros aplicados.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal para resolver incidencia -->
<div class="modal fade" id="modalResolver" tabindex="-1" aria-labelledby="modalResolverLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formResolver" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="modalResolverLabel">
                        <i class="fas fa-check-circle me-2 text-success"></i>Resolver Incidencia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="solucion" class="form-label fw-semibold">Solución aplicada *</label>
                        <textarea name="solucion" id="solucion" class="form-control" rows="4" 
                                  placeholder="Describe la solución aplicada para resolver la incidencia..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Marcar como Resuelta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Gradientes personalizados */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

/* Avatar pequeño */
.avatar-sm {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 50%;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e3e6f0;
    padding: 1rem;
}

.table td {
    border-bottom: 1px solid #f8f9fa;
    padding: 1rem;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fc;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    transition: all 0.2s ease-in-out;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Estado vacío */
.empty-state {
    padding: 2rem;
}

.empty-state i {
    opacity: 0.7;
}

/* Animaciones */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

/* Paginación personalizada */
.pagination {
    margin-bottom: 0;
}

.page-link {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    color: #667eea;
    margin: 0 2px;
}

.page-link:hover {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
}

.page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}

/* Responsive */
@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between .btn {
        width: 100%;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .col-lg-2 {
        margin-bottom: 1rem;
    }
}
</style>
@endsection

@section('scriptHead')
<script>
function mostrarModalResolver(incidenciaId) {
    const modal = document.getElementById('modalResolver');
    const form = document.getElementById('formResolver');
    
    // Actualizar la acción del formulario
    form.action = `/admin/incidencias/${incidenciaId}/resolver`;
    
    // Mostrar el modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

// Limpiar formulario cuando se cierre el modal
document.getElementById('modalResolver').addEventListener('hidden.bs.modal', function () {
    document.getElementById('solucion').value = '';
});
</script>
@endsection
