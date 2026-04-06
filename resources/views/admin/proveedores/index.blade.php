@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-truck text-primary me-2"></i>
            Gestión de Proveedores
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Proveedores</li>
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
            <a href="{{ route('admin.proveedores.create') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>
                Nuevo Proveedor
            </a>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Proveedores
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $proveedores->total() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-truck fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Proveedores Activos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $proveedores->where('activo', true)->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total Artículos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $proveedores->sum('total_articulos') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-boxes fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Valor Total Stock
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($proveedores->sum('valor_total_stock'), 2) }} €
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Proveedores -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-2"></i>
            Lista de Proveedores
        </h6>
    </div>
    <div class="card-body p-0">
        @if($proveedores->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">Nombre</th>
                            <th class="border-0">Contacto</th>
                            <th class="border-0">Teléfono</th>
                            <th class="border-0">Email</th>
                            <th class="border-0">Artículos</th>
                            <th class="border-0">Estado</th>
                            <th class="border-0 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proveedores as $proveedor)
                            <tr>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $proveedor->nombre }}</h6>
                                            @if($proveedor->direccion)
                                                <small class="text-muted">{{ $proveedor->direccion }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    @if($proveedor->contacto)
                                        <span class="text-dark">{{ $proveedor->contacto }}</span>
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($proveedor->telefono)
                                        <a href="tel:{{ $proveedor->telefono }}" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>{{ $proveedor->telefono }}
                                        </a>
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($proveedor->email)
                                        <a href="mailto:{{ $proveedor->email }}" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>{{ $proveedor->email }}
                                        </a>
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-info">{{ $proveedor->total_articulos }} artículos</span>
                                </td>
                                <td class="align-middle">
                                    @if($proveedor->activo)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Activo
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-pause me-1"></i>Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.proveedores.show', $proveedor->id) }}" 
                                           class="btn btn-outline-info btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.proveedores.edit', $proveedor->id) }}" 
                                           class="btn btn-outline-primary btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.proveedores.destroy', $proveedor->id) }}" 
                                              method="POST" 
                                              style="display: inline;"
                                              onsubmit="return confirm('¿Estás seguro de que quieres eliminar este proveedor?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-outline-danger btn-sm" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Eliminar">
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
            <div class="d-flex justify-content-center mt-4">
                {{ $proveedores->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay proveedores registrados</h5>
                <p class="text-muted">Comienza creando tu primer proveedor</p>
                <a href="{{ route('admin.proveedores.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Crear Proveedor
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection