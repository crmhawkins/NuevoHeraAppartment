@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-exchange-alt text-primary me-2"></i>
            Movimientos de Stock
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Movimientos de Stock</li>
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
                <a href="{{ route('admin.movimientos-stock.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Movimiento
                </a>
                <a href="{{ route('admin.articulos.index') }}" class="btn btn-outline-info btn-lg">
                    <i class="fas fa-boxes me-2"></i>
                    Artículos
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
                            Total Movimientos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $estadisticas['total_movimientos'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
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
                            Entradas
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $estadisticas['entradas'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Salidas
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $estadisticas['salidas'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
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
                            Ajustes
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $estadisticas['ajustes'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
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
        <form method="GET" action="{{ route('admin.movimientos-stock.index') }}">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo">
                        <option value="">Todos los tipos</option>
                        <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entrada</option>
                        <option value="salida" {{ request('tipo') == 'salida' ? 'selected' : '' }}>Salida</option>
                        <option value="ajuste" {{ request('tipo') == 'ajuste' ? 'selected' : '' }}>Ajuste</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="articulo_id" class="form-label">Artículo</label>
                    <select class="form-select" id="articulo_id" name="articulo_id">
                        <option value="">Todos los artículos</option>
                        @foreach($articulos as $articulo)
                            <option value="{{ $articulo->id }}" {{ request('articulo_id') == $articulo->id ? 'selected' : '' }}>
                                {{ $articulo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_desde" 
                           name="fecha_desde" 
                           value="{{ request('fecha_desde') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_hasta" 
                           name="fecha_hasta" 
                           value="{{ request('fecha_hasta') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="motivo" class="form-label">Motivo</label>
                    <input type="text" 
                           class="form-control" 
                           id="motivo" 
                           name="motivo" 
                           value="{{ request('motivo') }}"
                           placeholder="Buscar por motivo...">
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>
                        Filtrar
                    </button>
                    <a href="{{ route('admin.movimientos-stock.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>
                        Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Movimientos -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-2"></i>
            Lista de Movimientos
        </h6>
    </div>
    <div class="card-body p-0">
        @if($movimientos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">Fecha</th>
                            <th class="border-0">Artículo</th>
                            <th class="border-0">Tipo</th>
                            <th class="border-0">Cantidad</th>
                            <th class="border-0">Motivo</th>
                            <th class="border-0">Usuario</th>
                            <th class="border-0 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movimientos as $movimiento)
                            <tr>
                                <td class="align-middle">
                                    <i class="fas fa-calendar me-1"></i>{{ $movimiento->fecha_movimiento->format('d/m/Y') }}
                                    <br>
                                    <small class="text-muted">{{ $movimiento->fecha_movimiento->format('H:i') }}</small>
                                </td>
                                <td class="align-middle">
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
            
            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4">
                {{ $movimientos->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay movimientos registrados</h5>
                <p class="text-muted">Comienza creando el primer movimiento de stock</p>
                <a href="{{ route('admin.movimientos-stock.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Crear Movimiento
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