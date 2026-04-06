@extends('layouts.appAdmin')

@section('title', 'Editar Incidencia')

@section('tituloSeccion', 'Editar Incidencia #' . $incidencia->id)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-edit me-2 text-warning"></i>
                        Editar Incidencia
                    </h1>
                    <p class="text-muted mb-0">Modifica los detalles de la incidencia</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.incidencias.show', $incidencia) }}" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-eye me-2"></i>Ver Detalles
                    </a>
                    <a href="{{ route('admin.incidencias.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulario Principal -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-edit me-2 text-primary"></i>Formulario de Edición
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.incidencias.update', $incidencia) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="titulo" class="form-label fw-semibold">Título *</label>
                                    <input type="text" class="form-control @error('titulo') is-invalid @enderror" 
                                           id="titulo" name="titulo" value="{{ old('titulo', $incidencia->titulo) }}" required>
                                    @error('titulo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="prioridad" class="form-label fw-semibold">Prioridad *</label>
                                    <select class="form-select @error('prioridad') is-invalid @enderror" 
                                            id="prioridad" name="prioridad" required>
                                        <option value="baja" {{ old('prioridad', $incidencia->prioridad) == 'baja' ? 'selected' : '' }}>Baja</option>
                                        <option value="media" {{ old('prioridad', $incidencia->prioridad) == 'media' ? 'selected' : '' }}>Media</option>
                                        <option value="alta" {{ old('prioridad', $incidencia->prioridad) == 'alta' ? 'selected' : '' }}>Alta</option>
                                        <option value="urgente" {{ old('prioridad', $incidencia->prioridad) == 'urgente' ? 'selected' : '' }}>Urgente</option>
                                    </select>
                                    @error('prioridad')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="estado" class="form-label fw-semibold">Estado *</label>
                                    <select class="form-select @error('estado') is-invalid @enderror" 
                                            id="estado" name="estado" required>
                                        <option value="pendiente" {{ old('estado', $incidencia->estado) == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="en_proceso" {{ old('estado', $incidencia->estado) == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                                        <option value="resuelta" {{ old('estado', $incidencia->estado) == 'resuelta' ? 'selected' : '' }}>Resuelta</option>
                                        <option value="cerrada" {{ old('estado', $incidencia->estado) == 'cerrada' ? 'selected' : '' }}>Cerrada</option>
                                    </select>
                                    @error('estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tipo" class="form-label fw-semibold">Tipo *</label>
                                    <select class="form-select @error('tipo') is-invalid @enderror" 
                                            id="tipo" name="tipo" required>
                                        <option value="apartamento" {{ old('tipo', $incidencia->tipo) == 'apartamento' ? 'selected' : '' }}>Apartamento</option>
                                        <option value="zona_comun" {{ old('tipo', $incidencia->tipo) == 'zona_comun' ? 'selected' : '' }}>Zona Común</option>
                                    </select>
                                    @error('tipo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="descripcion" class="form-label fw-semibold">Descripción *</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                              id="descripcion" name="descripcion" rows="4" required>{{ old('descripcion', $incidencia->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apartamento_id" class="form-label fw-semibold">Apartamento</label>
                                    <select class="form-select @error('apartamento_id') is-invalid @enderror" 
                                            id="apartamento_id" name="apartamento_id">
                                        <option value="">Seleccionar apartamento</option>
                                        @foreach(\App\Models\Apartamento::orderBy('nombre')->get() as $apartamento)
                                            <option value="{{ $apartamento->id }}" 
                                                    {{ old('apartamento_id', $incidencia->apartamento_id) == $apartamento->id ? 'selected' : '' }}>
                                                {{ $apartamento->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('apartamento_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="zona_comun_id" class="form-label fw-semibold">Zona Común</label>
                                    <select class="form-select @error('zona_comun_id') is-invalid @enderror" 
                                            id="zona_comun_id" name="zona_comun_id">
                                        <option value="">Seleccionar zona común</option>
                                        @foreach(\App\Models\ZonaComun::orderBy('nombre')->get() as $zona)
                                            <option value="{{ $zona->id }}" 
                                                    {{ old('zona_comun_id', $incidencia->zona_comun_id) == $zona->id ? 'selected' : '' }}>
                                                {{ $zona->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('zona_comun_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="observaciones_admin" class="form-label fw-semibold">Observaciones del Administrador</label>
                                    <textarea class="form-control @error('observaciones_admin') is-invalid @enderror" 
                                              id="observaciones_admin" name="observaciones_admin" rows="3">{{ old('observaciones_admin', $incidencia->observaciones_admin) }}</textarea>
                                    @error('observaciones_admin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1 text-info"></i>
                                        Observaciones internas para el equipo administrativo.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="empleada_id" class="form-label fw-semibold">Empleada</label>
                                    <select class="form-select @error('empleada_id') is-invalid @enderror" 
                                            id="empleada_id" name="empleada_id">
                                        <option value="">Seleccionar empleada</option>
                                        @foreach(\App\Models\User::where('role', 'USER')->where('inactive', false)->orderBy('name')->get() as $empleada)
                                            <option value="{{ $empleada->id }}" 
                                                    {{ old('empleada_id', $incidencia->empleada_id) == $empleada->id ? 'selected' : '' }}>
                                                {{ $empleada->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('empleada_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apartamento_limpieza_id" class="form-label fw-semibold">Limpieza Relacionada</label>
                                    <select class="form-select @error('apartamento_limpieza_id') is-invalid @enderror" 
                                            id="apartamento_limpieza_id" name="apartamento_limpieza_id">
                                        <option value="">Sin limpieza relacionada</option>
                                        @foreach(\App\Models\ApartamentoLimpieza::with(['apartamento', 'zonaComun'])->get() as $limpieza)
                                            <option value="{{ $limpieza->id }}" 
                                                    {{ old('apartamento_limpieza_id', $incidencia->apartamento_limpieza_id) == $limpieza->id ? 'selected' : '' }}>
                                                @if($limpieza->apartamento)
                                                    {{ $limpieza->apartamento->nombre }} - Limpieza #{{ $limpieza->id }}
                                                @elseif($limpieza->zonaComun)
                                                    {{ $limpieza->zonaComun->nombre }} - Limpieza #{{ $limpieza->id }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('apartamento_limpieza_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            @if($incidencia->estado === 'resuelta')
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="solucion" class="form-label fw-semibold">Solución Aplicada</label>
                                        <textarea class="form-control @error('solucion') is-invalid @enderror" 
                                                  id="solucion" name="solucion" rows="4">{{ old('solucion', $incidencia->solucion) }}</textarea>
                                        @error('solucion')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1 text-info"></i>
                                            Descripción de la solución aplicada para resolver la incidencia.
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center pt-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>Guardar Cambios
                                    </button>
                                    
                                    @if($incidencia->estado !== 'resuelta')
                                        <button type="button" class="btn btn-success btn-lg" onclick="mostrarModalResolver({{ $incidencia->id }})">
                                            <i class="fas fa-check me-2"></i>Marcar como Resuelta
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Información de la Incidencia -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Información Actual
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">ID</label>
                        <div class="info-value">
                            <span class="badge bg-secondary fs-6">#{{ $incidencia->id }}</span>
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Estado Actual</label>
                        <div class="info-value">
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
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Prioridad Actual</label>
                        <div class="info-value">
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
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Creada</label>
                        <div class="info-value">
                            <i class="fas fa-calendar me-2 text-muted"></i>
                            {{ $incidencia->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    
                    @if($incidencia->fecha_resolucion)
                        <div class="info-group mb-3">
                            <label class="form-label fw-semibold text-muted">Resuelta</label>
                            <div class="info-value">
                                <i class="fas fa-check-circle me-2 text-success"></i>
                                {{ $incidencia->fecha_resolucion->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Fotos Actuales -->
            @if($incidencia->fotos && count($incidencia->fotos) > 0)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-images me-2 text-primary"></i>Fotos Actuales
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @foreach($incidencia->fotos as $foto)
                                <div class="col-6">
                                    <div class="photo-item-small">
                                        <img src="{{ asset('storage/' . $foto) }}" 
                                             alt="Foto incidencia" 
                                             class="img-fluid rounded shadow-sm" 
                                             style="max-height: 100px; width: 100%; object-fit: cover;">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Historial de Cambios -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-history me-2 text-primary"></i>Historial
                    </h6>
                </div>
                <div class="card-body">
                    <div class="history-item mb-2">
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i>
                            Creada por: {{ $incidencia->empleada->name ?? 'N/A' }}
                        </small>
                    </div>
                    
                    @if($incidencia->adminResuelve)
                        <div class="history-item mb-2">
                            <small class="text-muted">
                                <i class="fas fa-check-circle me-1"></i>
                                Resuelta por: {{ $incidencia->adminResuelve->name }}
                            </small>
                        </div>
                    @endif
                    
                    <div class="history-item">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Última actualización: {{ $incidencia->updated_at->format('d/m/Y H:i') }}
                        </small>
                    </div>
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
                        <label for="solucion_modal" class="form-label fw-semibold">Solución aplicada *</label>
                        <textarea name="solucion" id="solucion_modal" class="form-control" rows="4" 
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

/* Historial */
.history-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.history-item:last-child {
    border-bottom: none;
}

/* Fotos pequeñas */
.photo-item-small {
    transition: transform 0.2s ease-in-out;
}

.photo-item-small:hover {
    transform: scale(1.05);
}

.photo-item-small img {
    transition: all 0.2s ease-in-out;
}

.photo-item-small:hover img {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Formularios */
.form-group {
    margin-bottom: 1rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
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

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
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

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.125rem;
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
    
    .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
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

// Sincronizar campos de apartamento y zona común
document.getElementById('tipo').addEventListener('change', function() {
    const tipo = this.value;
    const apartamentoSelect = document.getElementById('apartamento_id');
    const zonaComunSelect = document.getElementById('zona_comun_id');
    
    if (tipo === 'apartamento') {
        apartamentoSelect.disabled = false;
        zonaComunSelect.disabled = true;
        zonaComunSelect.value = '';
    } else if (tipo === 'zona_comun') {
        apartamentoSelect.disabled = true;
        zonaComunSelect.disabled = false;
        apartamentoSelect.value = '';
    }
});

// Limpiar formulario cuando se cierre el modal
document.getElementById('modalResolver').addEventListener('hidden.bs.modal', function () {
    document.getElementById('solucion_modal').value = '';
});

// Inicializar estado de campos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const tipo = document.getElementById('tipo').value;
    const apartamentoSelect = document.getElementById('apartamento_id');
    const zonaComunSelect = document.getElementById('zona_comun_id');
    
    if (tipo === 'apartamento') {
        zonaComunSelect.disabled = true;
    } else if (tipo === 'zona_comun') {
        apartamentoSelect.disabled = true;
    }
});
</script>
@endsection
