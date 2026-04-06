@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-eye text-primary me-2"></i>
            Ver Zona Común
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.zonas-comunes.index') }}">Zonas Comunes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ver</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.zonas-comunes.edit', $zonaComun->id) }}" class="btn btn-warning">
            <i class="fas fa-edit me-2"></i>
            Editar
        </a>
        <a href="{{ route('admin.zonas-comunes.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver al Listado
        </a>
    </div>
</div>

<!-- Session Alerts -->
@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Información Principal -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>
                    Información de la Zona Común
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Nombre -->
                    <div class="col-md-6">
                        <div class="info-item">
                            <label class="form-label fw-semibold text-muted">
                                <i class="fas fa-tag text-primary me-1"></i>
                                Nombre
                            </label>
                            <p class="form-control-plaintext fw-bold fs-5">{{ $zonaComun->nombre }}</p>
                        </div>
                    </div>

                    <!-- Tipo -->
                    <div class="col-md-6">
                        <div class="info-item">
                            <label class="form-label fw-semibold text-muted">
                                <i class="fas fa-cog text-primary me-1"></i>
                                Tipo
                            </label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-info fs-6">
                                    @switch($zonaComun->tipo)
                                        @case('zona_comun')
                                            <i class="fas fa-building me-1"></i>Zona Común
                                            @break
                                        @case('area_servicio')
                                            <i class="fas fa-tools me-1"></i>Área de Servicio
                                            @break
                                        @case('recepcion')
                                            <i class="fas fa-concierge-bell me-1"></i>Recepción
                                            @break
                                        @case('piscina')
                                            <i class="fas fa-swimming-pool me-1"></i>Piscina
                                            @break
                                        @case('gimnasio')
                                            <i class="fas fa-dumbbell me-1"></i>Gimnasio
                                            @break
                                        @case('terraza')
                                            <i class="fas fa-umbrella me-1"></i>Terraza
                                            @break
                                        @default
                                            <i class="fas fa-question me-1"></i>{{ $zonaComun->tipo }}
                                    @endswitch
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Ubicación -->
                    @if($zonaComun->ubicacion)
                    <div class="col-md-6">
                        <div class="info-item">
                            <label class="form-label fw-semibold text-muted">
                                <i class="fas fa-map-marker-alt text-primary me-1"></i>
                                Ubicación
                            </label>
                            <p class="form-control-plaintext fw-semibold">{{ $zonaComun->ubicacion }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Orden -->
                    @if($zonaComun->orden)
                    <div class="col-md-6">
                        <div class="info-item">
                            <label class="form-label fw-semibold text-muted">
                                <i class="fas fa-sort-numeric-up text-primary me-1"></i>
                                Orden
                            </label>
                            <p class="form-control-plaintext fw-semibold">
                                <span class="badge bg-secondary fs-6">{{ $zonaComun->orden }}</span>
                            </p>
                        </div>
                    </div>
                    @endif

                    <!-- Descripción -->
                    @if($zonaComun->descripcion)
                    <div class="col-12">
                        <div class="info-item">
                            <label class="form-label fw-semibold text-muted">
                                <i class="fas fa-align-left text-primary me-1"></i>
                                Descripción
                            </label>
                            <div class="form-control-plaintext bg-light p-3 rounded">
                                {{ $zonaComun->descripcion }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Lateral -->
    <div class="col-lg-4">
        <!-- Información del Sistema -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    Información del Sistema
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12">
                        <small class="text-muted">ID:</small>
                        <p class="fw-semibold mb-0">#{{ $zonaComun->id }}</p>
                    </div>
                    <div class="col-12">
                        <small class="text-muted">Creado:</small>
                        <p class="fw-semibold mb-0">{{ $zonaComun->created_at ? $zonaComun->created_at->format('d/m/Y H:i') : 'N/A' }}</p>
                    </div>
                    <div class="col-12">
                        <small class="text-muted">Última actualización:</small>
                        <p class="fw-semibold mb-0">{{ $zonaComun->updated_at ? $zonaComun->updated_at->format('d/m/Y H:i') : 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="fas fa-bolt text-primary me-2"></i>
                    Acciones Rápidas
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.zonas-comunes.edit', $zonaComun->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>
                        Editar Zona Común
                    </a>
                    <button type="button" 
                            class="btn btn-danger" 
                            onclick="confirmarEliminacion({{ $zonaComun->id }}, '{{ $zonaComun->nombre }}')">
                        <i class="fas fa-trash me-2"></i>
                        Eliminar
                    </button>
                    <a href="{{ route('admin.zonas-comunes.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>
                        Ver Todas las Zonas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminación -->
<form id="formEliminar" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
    console.log('Script de show.blade.php cargado');
    
    function confirmarEliminacion(id, nombre) {
        console.log('Función confirmarEliminacion llamada con ID:', id, 'Nombre:', nombre);
        
        Swal.fire({
            title: '¿Eliminar Zona Común?',
            html: `
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <p>¿Estás seguro de que deseas eliminar la zona común:</p>
                    <strong>"${nombre}"</strong>
                    <p class="text-muted mt-2">Esta acción no se puede deshacer.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, eliminar',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('Usuario confirmó eliminación');
                
                // Mostrar loading
                Swal.fire({
                    title: 'Eliminando...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Enviar petición AJAX
                fetch(`/admin/zonas-comunes/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Respuesta del servidor:', data);
                    if (data.success) {
                        Swal.fire({
                            title: 'Eliminada',
                            text: data.message || 'La zona común ha sido eliminada correctamente',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '{{ route("admin.zonas-comunes.index") }}';
                        });
                    } else {
                        Swal.fire({
                            title: 'Advertencia',
                            text: data.message || 'No se pudo eliminar la zona común',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo eliminar la zona común',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            } else {
                console.log('Usuario canceló eliminación');
            }
        });
    }
</script>

<style>
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
    border: none;
}

.info-item {
    margin-bottom: 1rem;
}

.form-control-plaintext {
    border: none;
    background: transparent;
    padding: 0;
    margin: 0;
}

.badge {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-warning {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    border: none;
    color: white;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    border: none;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
}

.btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    border-color: #6c757d;
    color: white;
}
</style>
@endsection
