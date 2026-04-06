@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-boxes text-primary me-2"></i>
            Gestión de Artículos
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Artículos</li>
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
                <a href="{{ route('admin.articulos.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Artículo
                </a>
                <a href="{{ route('admin.movimientos-stock.index') }}" class="btn btn-outline-info btn-lg">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Movimientos
                </a>
            </div>
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
                            Total Artículos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $articulos->total() }}
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
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Con Stock
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $articulos->where('stock_actual', '>', 0)->count() }}
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
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Stock Bajo
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $articulos->where('stock_actual', '<=', 'stock_minimo')->count() }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                            Valor Total
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($articulos->sum(function($a) { return $a->stock_actual * $a->precio_compra; }), 2) }} €
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

<!-- Filtros -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-filter me-2"></i>
            Filtros
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.articulos.index') }}">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="proveedor_id" class="form-label">Proveedor</label>
                    <select class="form-select" id="proveedor_id" name="proveedor_id">
                        <option value="">Todos los proveedores</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                                {{ $proveedor->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas las categorías</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria }}" {{ request('categoria') == $categoria ? 'selected' : '' }}>
                                {{ $categoria }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="stock" class="form-label">Estado Stock</label>
                    <select class="form-select" id="stock" name="stock">
                        <option value="">Todos</option>
                        <option value="con_stock" {{ request('stock') == 'con_stock' ? 'selected' : '' }}>Con Stock</option>
                        <option value="sin_stock" {{ request('stock') == 'sin_stock' ? 'selected' : '' }}>Sin Stock</option>
                        <option value="stock_bajo" {{ request('stock') == 'stock_bajo' ? 'selected' : '' }}>Stock Bajo</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="activo" class="form-label">Estado</label>
                    <select class="form-select" id="activo" name="activo">
                        <option value="">Todos</option>
                        <option value="1" {{ request('activo') == '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('activo') == '0' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>
                        Filtrar
                    </button>
                    <a href="{{ route('admin.articulos.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>
                        Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Artículos -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-2"></i>
            Lista de Artículos
        </h6>
    </div>
    <div class="card-body p-0">
        @if($articulos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">Artículo</th>
                            <th class="border-0">Proveedor</th>
                            <th class="border-0">Categoría</th>
                            <th class="border-0">Stock</th>
                            <th class="border-0">Precio</th>
                            <th class="border-0">Estado</th>
                            <th class="border-0 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($articulos as $articulo)
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
                                    @if($articulo->proveedor)
                                        <span class="badge bg-primary">{{ $articulo->proveedor->nombre }}</span>
                                    @else
                                        <span class="text-muted">Sin proveedor</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($articulo->categoria)
                                        <span class="badge bg-secondary">{{ $articulo->categoria }}</span>
                                    @else
                                        <span class="text-muted">Sin categoría</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-{{ $articulo->stock_actual > $articulo->stock_minimo ? 'success' : ($articulo->stock_actual > 0 ? 'warning' : 'danger') }}">
                                            {{ $articulo->stock_actual }}
                                        </span>
                                        <small class="text-muted ms-2">/ {{ $articulo->stock_minimo }}</small>
                                    </div>
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
                                        <button type="button" 
                                                class="btn btn-outline-success btn-sm" 
                                                data-bs-toggle="tooltip" 
                                                title="Reponer stock"
                                                onclick="reponerStock({{ $articulo->id }}, '{{ $articulo->nombre }}')">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <form action="{{ route('admin.articulos.destroy', $articulo->id) }}" 
                                              method="POST" 
                                              style="display: inline;"
                                              onsubmit="return confirm('¿Estás seguro de que quieres eliminar este artículo?')">
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
                {{ $articulos->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-boxes fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay artículos registrados</h5>
                <p class="text-muted">Comienza creando tu primer artículo</p>
                <a href="{{ route('admin.articulos.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Crear Artículo
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Modal para Reponer Stock -->
<div class="modal fade" id="reponerStockModal" tabindex="-1" aria-labelledby="reponerStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reponerStockModalLabel">Reponer Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reponerStockForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad a reponer</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo</label>
                        <input type="text" class="form-control" id="motivo" name="motivo" value="Reposición manual" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Reponer Stock</button>
                </div>
            </form>
        </div>
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

function reponerStock(articuloId, nombreArticulo) {
    document.getElementById('reponerStockModalLabel').textContent = 'Reponer Stock - ' + nombreArticulo;
    document.getElementById('reponerStockForm').action = '{{ route("admin.articulos.reponer-stock", ":id") }}'.replace(':id', articuloId);
    document.getElementById('cantidad').value = '';
    document.getElementById('motivo').value = 'Reposición manual';
    
    var modal = new bootstrap.Modal(document.getElementById('reponerStockModal'));
    modal.show();
}
</script>
@endsection