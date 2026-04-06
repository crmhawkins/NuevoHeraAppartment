@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-exchange-alt text-primary me-2"></i>
            Movimiento de Stock #{{ $movimiento->id }}
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.movimientos-stock.index') }}">Movimientos de Stock</a></li>
                <li class="breadcrumb-item active" aria-current="page">#{{ $movimiento->id }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.movimientos-stock.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
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
                    Información del Movimiento
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Artículo</label>
                        <p class="form-control-plaintext">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $movimiento->articulo->nombre }}</h6>
                                    @if($movimiento->proveedor)
                                        <small class="text-muted">{{ $movimiento->proveedor->nombre }}</small>
                                    @endif
                                </div>
                            </div>
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Tipo de Movimiento</label>
                        <p class="form-control-plaintext">
                            <span class="badge bg-{{ $movimiento->tipo == 'entrada' ? 'success' : ($movimiento->tipo == 'salida' ? 'danger' : 'warning') }} fs-6">
                                <i class="fas fa-{{ $movimiento->tipo == 'entrada' ? 'arrow-up' : ($movimiento->tipo == 'salida' ? 'arrow-down' : 'exchange-alt') }} me-1"></i>
                                {{ ucfirst($movimiento->tipo) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Cantidad</label>
                        <p class="form-control-plaintext">
                            <strong class="text-{{ $movimiento->tipo == 'entrada' ? 'success' : ($movimiento->tipo == 'salida' ? 'danger' : 'warning') }} fs-5">
                                {{ $movimiento->tipo == 'entrada' ? '+' : ($movimiento->tipo == 'salida' ? '-' : '') }}{{ $movimiento->cantidad }} unidades
                            </strong>
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Fecha del Movimiento</label>
                        <p class="form-control-plaintext">
                            <i class="fas fa-calendar me-1"></i>{{ $movimiento->fecha_movimiento->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Motivo</label>
                        <p class="form-control-plaintext fw-bold">{{ $movimiento->motivo }}</p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Usuario</label>
                        <p class="form-control-plaintext">
                            @if($movimiento->user)
                                <span class="badge bg-info">{{ $movimiento->user->name }}</span>
                            @else
                                <span class="text-muted">Sistema</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($movimiento->observaciones)
                <div class="mb-3">
                    <label class="form-label text-muted">Observaciones</label>
                    <p class="form-control-plaintext">{{ $movimiento->observaciones }}</p>
                </div>
                @endif

                @if($movimiento->apartamentoLimpieza)
                <div class="mb-3">
                    <label class="form-label text-muted">Limpieza Asociada</label>
                    <p class="form-control-plaintext">
                        <i class="fas fa-home me-1"></i>
                        {{ $movimiento->apartamentoLimpieza->apartamento->nombre ?? 'Apartamento' }} - 
                        {{ $movimiento->apartamentoLimpieza->fecha_limpieza->format('d/m/Y') }}
                    </p>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Fecha de Creación</label>
                        <p class="form-control-plaintext">
                            <i class="fas fa-calendar me-1"></i>{{ $movimiento->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Última Actualización</label>
                        <p class="form-control-plaintext">
                            <i class="fas fa-calendar me-1"></i>{{ $movimiento->updated_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Estadísticas del Artículo -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-box me-2"></i>
                    Estado del Artículo
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-primary">{{ $movimiento->articulo->stock_actual }}</div>
                            <small class="text-muted">Stock Actual</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-info">{{ $movimiento->articulo->stock_minimo }}</div>
                            <small class="text-muted">Stock Mínimo</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-success">{{ number_format($movimiento->articulo->precio_compra, 2) }} €</div>
                            <small class="text-muted">Precio</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h4 mb-0 text-warning">{{ number_format($movimiento->articulo->stock_actual * $movimiento->articulo->precio_compra, 2) }} €</div>
                            <small class="text-muted">Valor Stock</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del Movimiento -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-bar me-2"></i>
                    Impacto del Movimiento
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Stock Anterior:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-secondary">
                            {{ $movimiento->tipo == 'entrada' ? $movimiento->articulo->stock_actual - $movimiento->cantidad : $movimiento->articulo->stock_actual + $movimiento->cantidad }}
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Movimiento:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-{{ $movimiento->tipo == 'entrada' ? 'success' : ($movimiento->tipo == 'salida' ? 'danger' : 'warning') }}">
                            {{ $movimiento->tipo == 'entrada' ? '+' : ($movimiento->tipo == 'salida' ? '-' : '') }}{{ $movimiento->cantidad }}
                        </span>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <strong>Stock Actual:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-primary">{{ $movimiento->articulo->stock_actual }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt me-2"></i>
                    Acciones
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.articulos.show', $movimiento->articulo->id) }}" class="btn btn-outline-info">
                        <i class="fas fa-box me-2"></i>
                        Ver Artículo
                    </a>
                    <a href="{{ route('admin.movimientos-stock.create', ['articulo_id' => $movimiento->articulo->id]) }}" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>
                        Nuevo Movimiento
                    </a>
                    <a href="{{ route('admin.movimientos-stock.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>
                        Ver Todos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Historial de Movimientos del Artículo -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-history me-2"></i>
            Historial de Movimientos - {{ $movimiento->articulo->nombre }}
        </h6>
    </div>
    <div class="card-body p-0">
        @if($movimiento->articulo->movimientosStock->count() > 0)
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
                        @foreach($movimiento->articulo->movimientosStock->sortByDesc('fecha_movimiento') as $historial)
                            <tr class="{{ $historial->id == $movimiento->id ? 'table-primary' : '' }}">
                                <td class="align-middle">
                                    <i class="fas fa-calendar me-1"></i>{{ $historial->fecha_movimiento->format('d/m/Y') }}
                                    <br>
                                    <small class="text-muted">{{ $historial->fecha_movimiento->format('H:i') }}</small>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-{{ $historial->tipo == 'entrada' ? 'success' : ($historial->tipo == 'salida' ? 'danger' : 'warning') }}">
                                        <i class="fas fa-{{ $historial->tipo == 'entrada' ? 'arrow-up' : ($historial->tipo == 'salida' ? 'arrow-down' : 'exchange-alt') }} me-1"></i>
                                        {{ ucfirst($historial->tipo) }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <strong class="text-{{ $historial->tipo == 'entrada' ? 'success' : ($historial->tipo == 'salida' ? 'danger' : 'warning') }}">
                                        {{ $historial->tipo == 'entrada' ? '+' : ($historial->tipo == 'salida' ? '-' : '') }}{{ $historial->cantidad }}
                                    </strong>
                                </td>
                                <td class="align-middle">
                                    {{ $historial->motivo }}
                                </td>
                                <td class="align-middle">
                                    @if($historial->user)
                                        <span class="badge bg-info">{{ $historial->user->name }}</span>
                                    @else
                                        <span class="text-muted">Sistema</span>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    @if($historial->id == $movimiento->id)
                                        <span class="badge bg-primary">Actual</span>
                                    @else
                                        <a href="{{ route('admin.movimientos-stock.show', $historial->id) }}" 
                                           class="btn btn-outline-info btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
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
                <p class="text-muted">Este es el primer movimiento de este artículo</p>
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