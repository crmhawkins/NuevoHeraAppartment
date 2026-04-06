@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-building text-primary me-2"></i>
            Gestión de Zonas Comunes
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Zonas Comunes</li>
            </ol>
        </nav>
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

<!-- Tarjeta de Acciones -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-tools text-primary me-2"></i>
                Acciones
            </h5>
            <div class="btn-group" role="group">
                <a href="{{ route('admin.zonas-comunes.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nueva Zona Común
                </a>
                <a href="{{ route('admin.checklists-zonas-comunes.index') }}" class="btn btn-outline-info btn-lg">
                    <i class="fas fa-list-check me-2"></i>
                    Checklists
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tarjeta Principal -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            Lista de Zonas Comunes ({{ $zonasComunes->total() }})
        </h5>
    </div>
    <div class="card-body p-0">
        @if($zonasComunes->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="border-0">
                                <i class="fas fa-hashtag text-primary me-1"></i>ID
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-tag text-primary me-1"></i>Nombre
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-cog text-primary me-1"></i>Tipo
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-map-marker-alt text-primary me-1"></i>Ubicación
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-toggle-on text-primary me-1"></i>Estado
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-sort-numeric-up text-primary me-1"></i>Orden
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-broom text-primary me-1"></i>Limpiezas
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-cogs text-primary me-1"></i>Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($zonasComunes as $zona)
                            <tr>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary fw-bold">#{{ $zona->id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-building text-info"></i>
                                        </div>
                                        <div>
                                            <span class="fw-semibold">{{ $zona->nombre }}</span>
                                            @if($zona->descripcion)
                                                <br><small class="text-muted">{{ Str::limit($zona->descripcion, 50) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">
                                        {{ ucfirst(str_replace('_', ' ', $zona->tipo)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $zona->ubicacion ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @if($zona->activo)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check me-1"></i>Activa
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="fas fa-times me-1"></i>Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $zona->orden }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $zona->limpiezas->count() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.zonas-comunes.show', $zona->id) }}" 
                                           class="btn btn-outline-info btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.zonas-comunes.edit', $zona->id) }}" 
                                           class="btn btn-outline-warning btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           title="Editar zona común">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.zonas-comunes.toggle-status', $zona->id) }}" 
                                              method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" 
                                                    class="btn btn-outline-{{ $zona->activo ? 'warning' : 'success' }} btn-sm" 
                                                    data-bs-toggle="tooltip" 
                                                    title="{{ $zona->activo ? 'Desactivar' : 'Activar' }}">
                                                <i class="fas fa-{{ $zona->activo ? 'pause' : 'play' }}"></i>
                                            </button>
                                        </form>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm" 
                                                onclick="eliminarZonaComun({{ $zona->id }}, '{{ $zona->nombre }}')"
                                                data-bs-toggle="tooltip" 
                                                title="Eliminar zona común">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4">
                {{ $zonasComunes->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay zonas comunes disponibles</h5>
                <p class="text-muted">Crea la primera zona común para comenzar</p>
                <a href="{{ route('admin.zonas-comunes.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Crear Zona Común
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@include('sweetalert::alert')

<script>
console.log('Script inline cargado');

// Función global para eliminar zona común
function eliminarZonaComun(zonaId, zonaNombre) {
    console.log('Función eliminarZonaComun llamada con ID:', zonaId, 'Nombre:', zonaNombre);
    
    Swal.fire({
        title: '¿Eliminar Zona Común?',
        html: `
            <div class="text-start">
                <p><strong>Zona Común:</strong> ${zonaNombre}</p>
                <p class="text-danger mt-3"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, Eliminar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
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
            fetch(`{{ route('admin.zonas-comunes.destroy', ':id') }}`.replace(':id', zonaId), {
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
                        location.reload();
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

// Verificar que la función esté disponible
console.log('Función eliminarZonaComun definida:', typeof eliminarZonaComun);
</script>


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

/* Estilos de badges */
.badge {
    font-size: 0.8em;
    font-weight: 500;
}

/* Estilos de botones */
.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>
