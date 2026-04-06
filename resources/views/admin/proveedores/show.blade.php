@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-truck text-primary me-2"></i>
            {{ $proveedor->nombre }}
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.proveedores.index') }}">Proveedores</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $proveedor->nombre }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.proveedores.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
        <a href="{{ route('admin.proveedores.edit', $proveedor->id) }}" class="btn btn-primary">
            <i class="fas fa-edit me-2"></i>
            Editar
        </a>
    </div>
</div>

<!-- Información Principal -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-info-circle me-2"></i>
                    Información del Proveedor
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Nombre del Proveedor</label>
                        <p class="form-control-plaintext fw-bold">{{ $proveedor->nombre }}</p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Persona de Contacto</label>
                        <p class="form-control-plaintext">
                            @if($proveedor->contacto)
                                {{ $proveedor->contacto }}
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Teléfono</label>
                        <p class="form-control-plaintext">
                            @if($proveedor->telefono)
                                <a href="tel:{{ $proveedor->telefono }}" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i>{{ $proveedor->telefono }}
                                </a>
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Email</label>
                        <p class="form-control-plaintext">
                            @if($proveedor->email)
                                <a href="mailto:{{ $proveedor->email }}" class="text-decoration-none">
                                    <i class="fas fa-envelope me-1"></i>{{ $proveedor->email }}
                                </a>
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($proveedor->direccion)
                <div class="mb-3">
                    <label class="form-label text-muted">Dirección</label>
                    <p class="form-control-plaintext">
                        <i class="fas fa-map-marker-alt me-1"></i>{{ $proveedor->direccion }}
                    </p>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Estado</label>
                        <p class="form-control-plaintext">
                            @if($proveedor->activo)
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Activo
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="fas fa-pause me-1"></i>Inactivo
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Fecha de Creación</label>
                        <p class="form-control-plaintext">
                            <i class="fas fa-calendar me-1"></i>{{ $proveedor->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Estadísticas -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-bar me-2"></i>
                    Estadísticas
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-primary">{{ $proveedor->total_articulos }}</div>
                            <small class="text-muted">Artículos</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-success">{{ number_format($proveedor->valor_total_stock, 2) }} €</div>
                            <small class="text-muted">Valor Stock</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-info">{{ $proveedor->articulos->where('stock_actual', '>', 0)->count() }}</div>
                            <small class="text-muted">Con Stock</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-warning">{{ $proveedor->articulos->where('stock_actual', '<=', 'stock_minimo')->count() }}</div>
                            <small class="text-muted">Stock Bajo</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt me-2"></i>
                    Acciones Rápidas
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.proveedores.edit', $proveedor->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>
                        Editar Proveedor
                    </a>
                    <a href="{{ route('admin.articulos.create', ['proveedor_id' => $proveedor->id]) }}" class="btn btn-outline-success">
                        <i class="fas fa-plus me-2"></i>
                        Nuevo Artículo
                    </a>
                    <form action="{{ route('admin.proveedores.destroy', $proveedor->id) }}" 
                          method="POST" 
                          onsubmit="return confirm('¿Estás seguro de que quieres eliminar este proveedor?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash me-2"></i>
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Artículos del Proveedor -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-boxes me-2"></i>
                Artículos del Proveedor
            </h6>
            <a href="{{ route('admin.articulos.create', ['proveedor_id' => $proveedor->id]) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-2"></i>
                Nuevo Artículo
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        @if($proveedor->articulos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">Artículo</th>
                            <th class="border-0">Categoría</th>
                            <th class="border-0">Stock Actual</th>
                            <th class="border-0">Stock Mínimo</th>
                            <th class="border-0">Precio Compra</th>
                            <th class="border-0">Estado</th>
                            <th class="border-0 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proveedor->articulos as $articulo)
                            <tr>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-box"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $articulo->nombre }}</h6>
                                            @if($articulo->descripcion)
                                                <small class="text-muted">{{ Str::limit($articulo->descripcion, 50) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-secondary">{{ $articulo->categoria ?? 'Sin categoría' }}</span>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-{{ $articulo->stock_actual > $articulo->stock_minimo ? 'success' : 'warning' }}">
                                        {{ $articulo->stock_actual }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-info">{{ $articulo->stock_minimo }}</span>
                                </td>
                                <td class="align-middle">
                                    <strong>{{ number_format($articulo->precio_compra, 2) }} €</strong>
                                </td>
                                <td class="align-middle">
                                    @if($articulo->activo)
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
                                        <a href="{{ route('admin.articulos.show', $articulo->id) }}" 
                                           class="btn btn-outline-info btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.articulos.edit', $articulo->id) }}" 
                                           class="btn btn-outline-primary btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay artículos asociados</h5>
                <p class="text-muted">Comienza agregando artículos a este proveedor</p>
                <a href="{{ route('admin.articulos.create', ['proveedor_id' => $proveedor->id]) }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Crear Artículo
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