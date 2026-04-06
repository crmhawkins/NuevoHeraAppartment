@extends('layouts.appAdmin')

@section('title', 'Detalles de Incidencia')

@section('tituloSeccion', 'Detalles de Incidencia #' . $incidencia->id)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                        {{ $incidencia->titulo }}
                    </h1>
                    <p class="text-muted mb-0">Detalles completos de la incidencia</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.incidencias.edit', $incidencia) }}" class="btn btn-warning btn-lg">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('admin.incidencias.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información Principal -->
        <div class="col-lg-8">
            <!-- Detalles de la Incidencia -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Información de la Incidencia
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Descripción</label>
                                <div class="info-value">
                                    <p class="mb-0">{{ $incidencia->descripcion }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Estado</label>
                                <div class="info-value">
                                    @php
                                        $estadoClass = [
                                            'pendiente' => 'bg-warning',
                                            'en_proceso' => 'bg-info',
                                            'resuelta' => 'bg-success',
                                            'cerrada' => 'bg-secondary'
                                        ];
                                    @endphp
                                    <span class="badge {{ $estadoClass[$incidencia->estado] }} fs-6">
                                        {{ ucfirst(str_replace('_', ' ', $incidencia->estado)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Prioridad</label>
                                <div class="info-value">
                                    @php
                                        $prioridadClass = [
                                            'baja' => 'bg-success',
                                            'media' => 'bg-info',
                                            'alta' => 'bg-warning',
                                            'urgente' => 'bg-danger'
                                        ];
                                    @endphp
                                    <span class="badge {{ $prioridadClass[$incidencia->prioridad] }} fs-6">
                                        {{ ucfirst($incidencia->prioridad) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Tipo</label>
                                <div class="info-value">
                                    @if($incidencia->tipo == 'apartamento')
                                        <span class="badge bg-info fs-6">
                                            <i class="fas fa-home me-1"></i>Apartamento
                                        </span>
                                    @else
                                        <span class="badge bg-warning fs-6">
                                            <i class="fas fa-building me-1"></i>Zona Común
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Fecha de Creación</label>
                                <div class="info-value">
                                    <i class="fas fa-calendar me-2 text-muted"></i>
                                    {{ $incidencia->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Última Actualización</label>
                                <div class="info-value">
                                    <i class="fas fa-clock me-2 text-muted"></i>
                                    {{ $incidencia->updated_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($incidencia->solucion)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="info-group">
                                    <label class="form-label fw-semibold text-muted">Solución Aplicada</label>
                                    <div class="info-value solution-box">
                                        <i class="fas fa-check-circle me-2 text-success"></i>
                                        {{ $incidencia->solucion }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($incidencia->observaciones_admin)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="info-group">
                                    <label class="form-label fw-semibold text-muted">Observaciones del Administrador</label>
                                    <div class="info-value admin-notes">
                                        <i class="fas fa-comment me-2 text-info"></i>
                                        {{ $incidencia->observaciones_admin }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Fotos de la Incidencia -->
            @if($incidencia->fotos && count($incidencia->fotos) > 0)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-images me-2 text-primary"></i>Fotos de la Incidencia
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($incidencia->fotos as $foto)
                                <div class="col-md-4 col-sm-6">
                                    <div class="photo-item">
                                        <img src="{{ asset('storage/' . $foto) }}" 
                                             alt="Foto incidencia" 
                                             class="img-fluid rounded shadow-sm" 
                                             style="max-height: 200px; width: 100%; object-fit: cover; cursor: pointer;"
                                             onclick="abrirModalFoto('{{ asset('storage/' . $foto) }}')">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Información del Elemento -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Información del Elemento
                    </h6>
                </div>
                <div class="card-body">
                    @if($incidencia->apartamento)
                        <div class="info-group mb-3">
                            <label class="form-label fw-semibold text-muted">Tipo</label>
                            <div class="info-value">
                                <span class="badge bg-info">Apartamento</span>
                            </div>
                        </div>
                        <div class="info-group mb-3">
                            <label class="form-label fw-semibold text-muted">Apartamento</label>
                            <div class="info-value">
                                <span class="badge bg-primary fs-6">{{ $incidencia->apartamento->nombre }}</span>
                            </div>
                        </div>
                        <div class="info-group mb-3">
                            <label class="form-label fw-semibold text-muted">Edificio</label>
                            <div class="info-value">
                                {{ $incidencia->apartamento->edificio->nombre ?? 'Sin edificio' }}
                            </div>
                        </div>
                    @elseif($incidencia->zonaComun)
                        <div class="info-group mb-3">
                            <label class="form-label fw-semibold text-muted">Tipo</label>
                            <div class="info-value">
                                <span class="badge bg-warning">Zona Común</span>
                            </div>
                        </div>
                        <div class="info-group mb-3">
                            <label class="form-label fw-semibold text-muted">Zona Común</label>
                            <div class="info-value">
                                <span class="badge bg-warning fs-6">{{ $incidencia->zonaComun->nombre }}</span>
                            </div>
                        </div>
                    @endif

                    @if($incidencia->limpieza)
                        <div class="info-group mb-3">
                            <label class="form-label fw-semibold text-muted">Limpieza Relacionada</label>
                            <div class="info-value">
                                <span class="badge bg-primary">ID: {{ $incidencia->limpieza->id }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Información de la Empleada -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-user me-2 text-primary"></i>Empleada que Reportó
                    </h6>
                </div>
                <div class="card-body text-center">
                    @if($incidencia->empleada)
                        <div class="avatar-large mb-3">
                            @if($incidencia->empleada->avatar)
                                <img src="{{ asset('storage/' . $incidencia->empleada->avatar) }}" 
                                     alt="Avatar" class="rounded-circle shadow-sm" width="80" height="80">
                            @else
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            @endif
                        </div>
                        <div class="employee-info">
                            <h6 class="fw-semibold mb-1">{{ $incidencia->empleada->name }}</h6>
                            <p class="text-muted mb-2">{{ $incidencia->empleada->email }}</p>
                            @if($incidencia->empleada->telefono)
                                <p class="text-muted mb-0">
                                    <i class="fas fa-phone me-1"></i>{{ $incidencia->empleada->telefono }}
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="empty-state-small">
                            <i class="fas fa-user-slash text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">Sin empleada asignada</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Información de Resolución -->
            @if($incidencia->estado === 'resuelta' && $incidencia->adminResuelve)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-check-circle me-2 text-success"></i>Resuelta por
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="admin-info">
                            <h6 class="fw-semibold mb-1">{{ $incidencia->adminResuelve->name }}</h6>
                            <p class="text-muted mb-2">{{ $incidencia->adminResuelve->email }}</p>
                            <p class="text-success mb-0">
                                <i class="fas fa-calendar me-1"></i>
                                {{ $incidencia->fecha_resolucion->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Acciones Rápidas -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-tools me-2 text-primary"></i>Acciones Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    @if($incidencia->estado !== 'resuelta')
                        <button type="button" 
                                class="btn btn-success w-100 mb-3" 
                                onclick="mostrarModalResolver({{ $incidencia->id }})">
                            <i class="fas fa-check me-2"></i>Marcar como Resuelta
                        </button>
                    @endif

                    @if($incidencia->estado === 'pendiente')
                        <button type="button" 
                                class="btn btn-info w-100 mb-3" 
                                onclick="cambiarEstado('en_proceso')">
                            <i class="fas fa-play me-2"></i>Marcar en Proceso
                        </button>
                    @endif

                    @if($incidencia->estado === 'en_proceso')
                        <button type="button" 
                                class="btn btn-warning w-100 mb-3" 
                                onclick="cambiarEstado('pendiente')">
                            <i class="fas fa-pause me-2"></i>Volver a Pendiente
                        </button>
                    @endif

                    @if($incidencia->estado === 'resuelta')
                        <button type="button" 
                                class="btn btn-secondary w-100 mb-3" 
                                onclick="cambiarEstado('cerrada')">
                            <i class="fas fa-lock me-2"></i>Cerrar Incidencia
                        </button>
                    @endif

                    <!-- Botón para notificar técnicos -->
                    @if($incidencia->esReparacion() || $incidencia->tipo === 'averia' || $incidencia->tipo === 'reparacion')
                        @if($incidencia->fueNotificadaATecnicos())
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Notificada a técnicos</strong>
                                <br>
                                <small class="text-muted">
                                    Fecha: {{ $incidencia->tecnico_notificado_at->format('d/m/Y H:i') }}
                                    @if($incidencia->tecnicos_notificados_array)
                                        | Técnicos: {{ count($incidencia->tecnicos_notificados_array) }}
                                    @endif
                                </small>
                            </div>
                            <form action="{{ route('admin.incidencias.notificar-tecnicos', $incidencia) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" 
                                        class="btn btn-outline-primary w-100 mb-3" 
                                        onclick="return confirm('¿Reenviar notificación a técnicos?')">
                                    <i class="fas fa-bell me-2"></i>Reenviar Notificación
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.incidencias.notificar-tecnicos', $incidencia) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" 
                                        class="btn btn-primary w-100 mb-3" 
                                        onclick="return confirm('¿Notificar a técnicos sobre esta incidencia?')">
                                    <i class="fas fa-bell me-2"></i>Notificar a Técnicos
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
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

<!-- Modal para ver fotos -->
<div class="modal fade" id="modalFoto" tabindex="-1" aria-labelledby="modalFotoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="modalFotoLabel">Foto de la Incidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="fotoModal" src="" alt="Foto incidencia" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Estilos para grupos de información */
.info-group {
    margin-bottom: 1rem;
}

.info-group label {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.info-value {
    font-size: 1rem;
    color: #495057;
}

.solution-box {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    padding: 1rem;
    color: #155724;
}

.admin-notes {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border: 1px solid #bee5eb;
    border-radius: 8px;
    padding: 1rem;
    color: #0c5460;
}

/* Avatar grande */
.avatar-large {
    width: 80px;
    height: 80px;
    margin: 0 auto;
}

.avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 2rem;
}

/* Estado vacío pequeño */
.empty-state-small {
    padding: 1rem;
}

.empty-state-small i {
    opacity: 0.5;
}

/* Fotos */
.photo-item {
    transition: transform 0.2s ease-in-out;
}

.photo-item:hover {
    transform: scale(1.05);
}

.photo-item img {
    transition: all 0.2s ease-in-out;
}

.photo-item:hover img {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

/* Botones */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Cards */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
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
    
    .col-lg-8, .col-lg-4 {
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

function cambiarEstado(nuevoEstado) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.incidencias.update", $incidencia) }}';
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'PUT';
    
    const estadoField = document.createElement('input');
    estadoField.type = 'hidden';
    estadoField.name = 'estado';
    estadoField.value = nuevoEstado;
    
    form.appendChild(csrfToken);
    form.appendChild(methodField);
    form.appendChild(estadoField);
    
    document.body.appendChild(form);
    form.submit();
}

function abrirModalFoto(src) {
    document.getElementById('fotoModal').src = src;
    const modal = new bootstrap.Modal(document.getElementById('modalFoto'));
    modal.show();
}

// Limpiar formulario cuando se cierre el modal
document.getElementById('modalResolver').addEventListener('hidden.bs.modal', function () {
    document.getElementById('solucion').value = '';
});
</script>
@endsection

