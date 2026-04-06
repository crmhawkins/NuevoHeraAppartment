@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header con estadísticas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-users me-2 text-primary"></i>
                        Gestión de Clientes
                    </h1>
                    <p class="text-muted mb-0">Administra la base de datos de clientes del sistema</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('clientes.create') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Nuevo Cliente
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard de estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $clientes->total() }}</h4>
                    <small>Total de Clientes</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $clientes->where('inactivo', '!=', 1)->count() }}</h4>
                    <small>Clientes Activos</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-user-clock fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $clientes->where('inactivo', 1)->count() }}</h4>
                    <small>Clientes Inactivos</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-globe fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $clientes->unique('nacionalidad')->count() }}</h4>
                    <small>Nacionalidades</small>
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
            <form id="filtrosForm" method="GET" action="{{ route('clientes.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label fw-semibold">Búsqueda</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   value="{{ $search }}"
                                   placeholder="Nombre, apellido, email, idioma...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="sort" class="form-label fw-semibold">Ordenar por</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="id" {{ $sort == 'id' ? 'selected' : '' }}>ID</option>
                            <option value="nombre" {{ $sort == 'nombre' ? 'selected' : '' }}>Nombre</option>
                            <option value="apellido1" {{ $sort == 'apellido1' ? 'selected' : '' }}>Primer Apellido</option>
                            <option value="email" {{ $sort == 'email' ? 'selected' : '' }}>Email</option>
                            <option value="nacionalidad" {{ $sort == 'nacionalidad' ? 'selected' : '' }}>Nacionalidad</option>
                            <option value="created_at" {{ $sort == 'created_at' ? 'selected' : '' }}>Fecha de Registro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="order" class="form-label fw-semibold">Orden</label>
                        <select class="form-select" id="order" name="order">
                            <option value="asc" {{ $order == 'asc' ? 'selected' : '' }}>Ascendente</option>
                            <option value="desc" {{ $order == 'desc' ? 'selected' : '' }}>Descendente</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filtrar
                            </button>
                            <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Limpiar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de clientes -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Lista de Clientes
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary px-3 py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ $clientes->count() }} de {{ $clientes->total() }} clientes
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($clientes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">
                                    <i class="fas fa-user me-1 text-primary"></i>
                                    Cliente
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-envelope me-1 text-primary"></i>
                                    Contacto
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-globe me-1 text-primary"></i>
                                    Nacionalidad
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-calendar me-1 text-primary"></i>
                                    Registro
                                </th>
                                <th class="border-0">
                                    <i class="fas fa-toggle-on me-1 text-primary"></i>
                                    Estado
                                </th>
                                <th class="border-0 text-center">
                                    <i class="fas fa-cogs me-1 text-primary"></i>
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientes as $cliente)
                                <tr class="align-middle">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark">
                                                    {{ $cliente->nombre }} {{ $cliente->apellido1 }}
                                                    @if($cliente->apellido2)
                                                        {{ $cliente->apellido2 }}
                                                    @endif
                                                </h6>
                                                @if($cliente->alias)
                                                    <small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i>
                                                        {{ $cliente->alias }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            <a href="mailto:{{ $cliente->email }}" class="text-decoration-none">
                                                {{ $cliente->email }}
                                            </a>
                                        </div>
                                        @if($cliente->telefono)
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                {{ $cliente->telefono }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary-subtle text-primary px-2 py-1 me-2">
                                                <i class="fas fa-flag me-1"></i>
                                                {{ $cliente->nacionalidad }}
                                            </span>
                                            @if($cliente->idiomas)
                                                <small class="text-muted">
                                                    {{ $cliente->idiomas }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-muted">
                                            <i class="fas fa-calendar-plus me-1"></i>
                                            {{ $cliente->created_at->format('d/m/Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $cliente->created_at->format('H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($cliente->inactivo)
                                            <span class="badge bg-danger px-2 py-1">
                                                <i class="fas fa-times-circle me-1"></i>
                                                Inactivo
                                            </span>
                                        @else
                                            <span class="badge bg-success px-2 py-1">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Activo
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('clientes.show', $cliente->id) }}" 
                                               class="btn btn-outline-info btn-sm" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('clientes.edit', $cliente->id) }}" 
                                               class="btn btn-outline-warning btn-sm" 
                                               title="Editar cliente">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('clientes.destroy', $cliente->id) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirmarEliminacion()">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-outline-danger btn-sm" 
                                                        title="Inactivar cliente">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <div class="text-muted">
                        Mostrando {{ $clientes->firstItem() }} a {{ $clientes->lastItem() }} de {{ $clientes->total() }} resultados
                    </div>
                    <div>
                        {{ $clientes->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <!-- Estado vacío -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-users fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted mb-2">No se encontraron clientes</h4>
                    <p class="text-muted mb-4">
                        @if($search)
                            No hay clientes que coincidan con "{{ $search }}"
                        @else
                            Comienza agregando tu primer cliente al sistema
                        @endif
                    </p>
                    <a href="{{ route('clientes.create') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Crear Primer Cliente
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // SweetAlert para mensajes de sesión
    @if(session('swal_success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('swal_success') }}',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Aceptar'
        });
    @endif

    @if(session('swal_error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('swal_error') }}',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Aceptar'
        });
    @endif

    // Filtros automáticos
    const searchInput = document.getElementById('search');
    const sortSelect = document.getElementById('sort');
    const orderSelect = document.getElementById('order');

    // Aplicar filtros automáticamente al cambiar
    [sortSelect, orderSelect].forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filtrosForm').submit();
        });
    });

    // Búsqueda con delay para evitar muchas peticiones
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filtrosForm').submit();
        }, 500);
    });
});

function confirmarEliminacion() {
    return Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción inactivará al cliente. ¿Deseas continuar?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, inactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        return result.isConfirmed;
    });
}
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    border-radius: 12px 12px 0 0 !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.avatar-sm {
    width: 40px;
    height: 40px;
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

.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #e3e6f0;
    color: #6c757d;
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
}
</style>
@endsection

