@extends('layouts.appAdmin')

@section('title', 'Tipos de Tareas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-list-ul me-2"></i>Tipos de Tareas
                    </h3>
                    <a href="{{ route('admin.tipos-tareas.create') }}" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Nuevo Tipo de Tarea
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Buscar por nombre..." value="{{ $search }}" onchange="filtrar()">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" onchange="filtrar()">
                                <option value="">Todas las categorías</option>
                                @foreach($categorias as $key => $nombre)
                                    <option value="{{ $key }}" {{ $categoria == $key ? 'selected' : '' }}>{{ $nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" onchange="filtrar()">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ request('activo') == '1' ? 'selected' : '' }}>Activos</option>
                                <option value="0" {{ request('activo') == '0' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </div>
                    </div>

                    @if($tiposTareas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Prioridad Base</th>
                                        <th>Tiempo Estimado</th>
                                        <th>Prioridad Dinámica</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tiposTareas as $tipo)
                                        <tr>
                                            <td>
                                                <strong>{{ $tipo->nombre }}</strong>
                                                @if($tipo->descripcion)
                                                    <br><small class="text-muted">{{ Str::limit($tipo->descripcion, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $tipo->categoria_descripcion }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $tipo->prioridad_base >= 8 ? 'danger' : ($tipo->prioridad_base >= 6 ? 'warning' : 'success') }}">
                                                    {{ $tipo->prioridad_base }}/10
                                                </span>
                                            </td>
                                            <td>{{ $tipo->tiempo_estimado_formateado }}</td>
                                            <td>
                                                @if($tipo->necesitaActualizacionPrioridad())
                                                    <i class="fas fa-chart-line text-success" title="Prioridad dinámica activa"></i>
                                                    <small class="text-muted">
                                                        +{{ $tipo->incremento_prioridad_por_dia }}/día
                                                        (máx: {{ $tipo->prioridad_maxima }})
                                                    </small>
                                                @else
                                                    <span class="text-muted">Fija</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $tipo->activo ? 'success' : 'secondary' }}">
                                                    {{ $tipo->activo ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.tipos-tareas.show', $tipo) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.tipos-tareas.edit', $tipo) }}" class="btn btn-outline-secondary btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-{{ $tipo->activo ? 'warning' : 'success' }} btn-sm" 
                                                            onclick="toggleActive({{ $tipo->id }})">
                                                        <i class="fas fa-{{ $tipo->activo ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info btn-sm" 
                                                            onclick="duplicar({{ $tipo->id }})">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center">
                            {{ $tiposTareas->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-list-ul fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay tipos de tareas</h4>
                            <p class="text-muted">Crea tu primer tipo de tarea para comenzar</p>
                            <a href="{{ route('admin.tipos-tareas.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Crear Tipo de Tarea
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .card {
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .btn {
        transition: all 0.3s ease;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .badge {
        border-radius: 20px;
        padding: 6px 12px;
        font-weight: 500;
    }
    
    .table {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 600;
    }
    
    .table tbody tr {
        transition: all 0.3s ease;
    }
    
    .table tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.1);
        transform: scale(1.01);
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@endsection

@section('scripts')
<script>
// Configuración global de SweetAlert2
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

function mostrarNotificacion(tipo, titulo, mensaje) {
    Toast.fire({
        icon: tipo,
        title: titulo,
        text: mensaje
    });
}

function filtrar() {
    const search = document.querySelector('input[type="text"]').value;
    const categoria = document.querySelector('select').value;
    const activo = document.querySelectorAll('select')[1].value;
    
    mostrarNotificacion('info', 'Filtrando...', 'Aplicando filtros de búsqueda');
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (categoria) params.append('categoria', categoria);
    if (activo) params.append('activo', activo);
    
    window.location.href = `{{ route('admin.tipos-tareas.index') }}?${params.toString()}`;
}

function limpiarFiltros() {
    mostrarNotificacion('info', 'Limpiando filtros...', 'Restableciendo vista');
    window.location.href = '{{ route("admin.tipos-tareas.index") }}';
}

function toggleActive(id) {
    Swal.fire({
        title: '🔄 Cambiar Estado',
        html: `
            <div class="text-center">
                <i class="fas fa-toggle-on fa-3x text-primary mb-3"></i>
                <p>¿Estás seguro de que quieres cambiar el estado de este tipo de tarea?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Esto afectará la disponibilidad del tipo de tarea en la generación de turnos
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-2"></i>Sí, Cambiar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        customClass: {
            popup: 'animate__animated animate__zoomIn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Cambiando estado...',
                html: '<div class="loading-spinner mx-auto"></div><p class="mt-3">Procesando...</p>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            fetch(`/admin/tipos-tareas/${id}/toggle-active`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Estado Actualizado!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Continuar',
                        customClass: {
                            popup: 'animate__animated animate__bounceIn'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Error al cambiar el estado',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        }
    });
}

function duplicar(id) {
    Swal.fire({
        title: '📋 Duplicar Tipo de Tarea',
        html: `
            <div class="text-center">
                <i class="fas fa-copy fa-3x text-info mb-3"></i>
                <p>¿Estás seguro de que quieres duplicar este tipo de tarea?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Se creará una copia exacta que podrás modificar después
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-copy me-2"></i>Sí, Duplicar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        customClass: {
            popup: 'animate__animated animate__zoomIn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Duplicando...',
                html: '<div class="loading-spinner mx-auto"></div><p class="mt-3">Creando copia...</p>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            window.location.href = `/admin/tipos-tareas/${id}/duplicar`;
        }
    });
}

// Añadir animaciones a las filas de la tabla
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.1}s`;
        row.classList.add('fade-in');
    });
    
    // Añadir efectos de hover a los botones
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endsection
