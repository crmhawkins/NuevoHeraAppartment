@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-box text-primary me-2"></i>
            {{ $articulo->nombre }}
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.articulos.index') }}">Artículos</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $articulo->nombre }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.articulos.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
        <a href="{{ route('admin.articulos.edit', $articulo->id) }}" class="btn btn-primary">
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
                    Información del Artículo
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Nombre del Artículo</label>
                        <p class="form-control-plaintext fw-bold">{{ $articulo->nombre }}</p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Proveedor</label>
                        <p class="form-control-plaintext">
                            @if($articulo->proveedor)
                                <span class="badge bg-primary">{{ $articulo->proveedor->nombre }}</span>
                            @else
                                <span class="text-muted">Sin proveedor</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Categoría</label>
                        <p class="form-control-plaintext">
                            @if($articulo->categoria)
                                <span class="badge bg-secondary">{{ $articulo->categoria }}</span>
                            @else
                                <span class="text-muted">Sin categoría</span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Precio de Compra</label>
                        <p class="form-control-plaintext">
                            <strong>{{ number_format($articulo->precio_compra, 2) }} €</strong>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Stock Actual</label>
                        <p class="form-control-plaintext">
                            <span class="badge bg-{{ $articulo->stock_actual > $articulo->stock_minimo ? 'success' : ($articulo->stock_actual > 0 ? 'warning' : 'danger') }} fs-6">
                                {{ $articulo->stock_actual }} unidades
                            </span>
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Stock Mínimo</label>
                        <p class="form-control-plaintext">
                            <span class="badge bg-info fs-6">{{ $articulo->stock_minimo }} unidades</span>
                        </p>
                    </div>
                </div>

                @if($articulo->descripcion)
                <div class="mb-3">
                    <label class="form-label text-muted">Descripción</label>
                    <p class="form-control-plaintext">{{ $articulo->descripcion }}</p>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Estado</label>
                        <p class="form-control-plaintext">
                            @if($articulo->activo)
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
                            <i class="fas fa-calendar me-1"></i>{{ $articulo->created_at->format('d/m/Y H:i') }}
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
                            <div class="h4 mb-0 text-primary">{{ $articulo->stock_actual }}</div>
                            <small class="text-muted">Stock Actual</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-success">{{ number_format($articulo->stock_actual * $articulo->precio_compra, 2) }} €</div>
                            <small class="text-muted">Valor Stock</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-info">{{ $articulo->movimientosStock->count() }}</div>
                            <small class="text-muted">Movimientos</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-warning">{{ $articulo->movimientosStock->where('tipo', 'entrada')->sum('cantidad') }}</div>
                            <small class="text-muted">Total Entradas</small>
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
                    <a href="{{ route('admin.articulos.edit', $articulo->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>
                        Editar Artículo
                    </a>
                    <button type="button" 
                            class="btn btn-outline-success" 
                            onclick="reponerStock({{ $articulo->id }}, '{{ $articulo->nombre }}')">
                        <i class="fas fa-plus me-2"></i>
                        Reponer Stock
                    </button>
                    <a href="{{ route('admin.movimientos-stock.create', ['articulo_id' => $articulo->id]) }}" class="btn btn-outline-info">
                        <i class="fas fa-exchange-alt me-2"></i>
                        Nuevo Movimiento
                    </a>
                    <form action="{{ route('admin.articulos.destroy', $articulo->id) }}" 
                          method="POST" 
                          onsubmit="return confirm('¿Estás seguro de que quieres eliminar este artículo?')">
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

<!-- Movimientos de Stock -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-exchange-alt me-2"></i>
                Movimientos de Stock
            </h6>
            <a href="{{ route('admin.movimientos-stock.create', ['articulo_id' => $articulo->id]) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-2"></i>
                Nuevo Movimiento
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        @if($articulo->movimientosStock->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">Fecha</th>
                            <th class="border-0">Tipo</th>
                            <th class="border-0">Cantidad</th>
                            <th class="border-0">Motivo</th>
                            <th class="border-0">Usuario</th>
                            <th class="border-0">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($articulo->movimientosStock as $movimiento)
                            <tr>
                                <td class="align-middle">
                                    <i class="fas fa-calendar me-1"></i>{{ $movimiento->fecha_movimiento->format('d/m/Y') }}
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-{{ $movimiento->tipo == 'entrada' ? 'success' : ($movimiento->tipo == 'salida' ? 'danger' : 'warning') }}">
                                        <i class="fas fa-{{ $movimiento->tipo == 'entrada' ? 'arrow-up' : ($movimiento->tipo == 'salida' ? 'arrow-down' : 'exchange-alt') }} me-1"></i>
                                        {{ ucfirst($movimiento->tipo) }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <strong class="text-{{ $movimiento->tipo == 'entrada' ? 'success' : ($movimiento->tipo == 'salida' ? 'danger' : 'warning') }}">
                                        {{ $movimiento->tipo == 'entrada' ? '+' : ($movimiento->tipo == 'salida' ? '-' : '') }}{{ $movimiento->cantidad }}
                                    </strong>
                                </td>
                                <td class="align-middle">
                                    {{ $movimiento->motivo }}
                                </td>
                                <td class="align-middle">
                                    @if($movimiento->user)
                                        <span class="badge bg-info">{{ $movimiento->user->name }}</span>
                                    @else
                                        <span class="text-muted">Sistema</span>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    <a href="{{ route('admin.movimientos-stock.show', $movimiento->id) }}" 
                                       class="btn btn-outline-info btn-sm" 
                                       data-bs-toggle="tooltip" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay movimientos registrados</h5>
                <p class="text-muted">Comienza creando el primer movimiento de stock</p>
                <a href="{{ route('admin.movimientos-stock.create', ['articulo_id' => $articulo->id]) }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Crear Movimiento
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