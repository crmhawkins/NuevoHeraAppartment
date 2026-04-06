@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-gift me-2 text-primary"></i>
                        {{ $amenity->nombre }}
                    </h1>
                    <p class="text-muted mb-0">{{ $amenity->descripcion ?: 'Sin descripción' }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.amenities.edit', $amenity->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('admin.amenities.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard de estadísticas del amenity -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-info text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-boxes fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $amenity->stock_actual }}</h3>
                            <small class="opacity-75">{{ $amenity->unidad_medida }}</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        @if($amenity->stock_actual <= $amenity->stock_minimo)
                            <span class="badge bg-danger px-2 py-1">
                                <i class="fas fa-exclamation-triangle me-1"></i> Stock Bajo
                            </span>
                        @elseif($amenity->stock_maximo && $amenity->stock_actual >= $amenity->stock_maximo)
                            <span class="badge bg-warning px-2 py-1">
                                <i class="fas fa-arrow-up me-1"></i> Stock Alto
                            </span>
                        @else
                            <span class="badge bg-success px-2 py-1">
                                <i class="fas fa-check me-1"></i> Stock Normal
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-success text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-euro-sign fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ number_format($valorStockActual, 2) }}€</h3>
                            <small class="opacity-75">Valor del Stock</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="opacity-75">{{ number_format($amenity->precio_compra, 2) }}€ por {{ $amenity->unidad_medida }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-minus-circle fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $totalConsumos }}</h3>
                            <small class="opacity-75">Total Consumos</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="opacity-75">{{ $consumoTotal }} {{ $amenity->unidad_medida }} consumidos</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-primary text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-plus-circle fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $totalReposiciones }}</h3>
                            <small class="opacity-75">Total Reposiciones</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="opacity-75">{{ $reposicionTotal }} {{ $amenity->unidad_medida }} repuestos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Información del Amenity -->
        <div class="col-lg-8">
            <!-- Información General -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-sm me-3">
                                    <i class="fas fa-gift text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold">Nombre</h6>
                                    <p class="mb-0 text-muted">{{ $amenity->nombre }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Categoría</h6>
                                <span class="badge bg-primary-subtle text-primary px-2 py-1">
                                    {{ ucfirst($amenity->categoria) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Descripción</h6>
                                <p class="mb-0 text-muted">
                                    {{ $amenity->descripcion ?: 'Sin descripción disponible' }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Tipo de Consumo</h6>
                                @switch($amenity->tipo_consumo)
                                    @case('por_reserva')
                                        <span class="badge bg-info-subtle text-info px-2 py-1">
                                            <i class="fas fa-calendar-check me-1"></i> Por Reserva
                                        </span>
                                        @break
                                    @case('por_tiempo')
                                        <span class="badge bg-warning-subtle text-warning px-2 py-1">
                                            <i class="fas fa-clock me-1"></i> Por Tiempo
                                        </span>
                                        @break
                                    @case('por_persona')
                                        <span class="badge bg-success-subtle text-success px-2 py-1">
                                            <i class="fas fa-users me-1"></i> Por Persona
                                        </span>
                                        @break
                                @endswitch
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Estado</h6>
                                @if($amenity->activo)
                                    <span class="badge bg-success px-2 py-1">
                                        <i class="fas fa-check-circle me-1"></i> Activo
                                    </span>
                                @else
                                    <span class="badge bg-danger px-2 py-1">
                                        <i class="fas fa-times-circle me-1"></i> Inactivo
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Stock -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-boxes me-2 text-primary"></i>
                        Configuración de Stock
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="mb-1 fw-semibold">Stock Actual</h6>
                                <h3 class="mb-1 text-primary">{{ $amenity->stock_actual }}</h3>
                                <small class="text-muted">{{ $amenity->unidad_medida }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="mb-1 fw-semibold">Stock Mínimo</h6>
                                <h3 class="mb-1 text-warning">{{ $amenity->stock_minimo }}</h3>
                                <small class="text-muted">{{ $amenity->unidad_medida }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="mb-1 fw-semibold">Stock Máximo</h6>
                                <h3 class="mb-1 text-info">
                                    {{ $amenity->stock_maximo ?: 'No definido' }}
                                </h3>
                                <small class="text-muted">{{ $amenity->stock_maximo ? $amenity->unidad_medida : '' }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Unidad de Medida</h6>
                                <p class="mb-0 text-muted">{{ ucfirst($amenity->unidad_medida) }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Precio de Compra</h6>
                                <p class="mb-0 text-success fw-bold">{{ number_format($amenity->precio_compra, 2) }} €</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Consumo -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-calculator me-2 text-primary"></i>
                        Configuración de Consumo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($amenity->tipo_consumo === 'por_reserva')
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="mb-1 fw-semibold">Consumo por Reserva</h6>
                                    <p class="mb-0 text-muted">{{ $amenity->consumo_por_reserva ?: 'No definido' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="mb-1 fw-semibold">Consumo Mínimo</h6>
                                    <p class="mb-0 text-muted">{{ $amenity->consumo_minimo_reserva ?: 'No definido' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="mb-1 fw-semibold">Consumo Máximo</h6>
                                    <p class="mb-0 text-muted">{{ $amenity->consumo_maximo_reserva ?: 'No definido' }}</p>
                                </div>
                            </div>
                        @elseif($amenity->tipo_consumo === 'por_tiempo')
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="mb-1 fw-semibold">Duración en Días</h6>
                                    <p class="mb-0 text-muted">{{ $amenity->duracion_dias ?: 'No definido' }}</p>
                                </div>
                            </div>
                        @elseif($amenity->tipo_consumo === 'por_persona')
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="mb-1 fw-semibold">Consumo por Persona</h6>
                                    <p class="mb-0 text-muted">{{ $amenity->consumo_por_persona ?: 'No definido' }}</p>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Unidad de Consumo</h6>
                                <p class="mb-0 text-muted">{{ $amenity->unidad_consumo ?: 'No definido' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>
                        Información Adicional
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Proveedor</h6>
                                <p class="mb-0 text-muted">{{ $amenity->proveedor ?: 'No especificado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Código del Producto</h6>
                                <p class="mb-0 text-muted">{{ $amenity->codigo_producto ?: 'No especificado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Fecha de Creación</h6>
                                <p class="mb-0 text-muted">{{ $amenity->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Última Actualización</h6>
                                <p class="mb-0 text-muted">{{ $amenity->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar con acciones rápidas -->
        <div class="col-lg-4">
            <!-- Acciones Rápidas -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-bolt me-2 text-primary"></i>
                        Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" onclick="registrarReposicion({{ $amenity->id }})">
                            <i class="fas fa-plus me-2"></i>Registrar Reposición
                        </button>
                        <button type="button" class="btn btn-warning" onclick="registrarConsumo({{ $amenity->id }})">
                            <i class="fas fa-minus me-2"></i>Registrar Consumo
                        </button>
                        <form action="{{ route('admin.amenities.toggle-status', $amenity->id) }}" method="POST" class="d-grid">
                            @csrf
                            <button type="submit" class="btn btn-{{ $amenity->activo ? 'secondary' : 'success' }}">
                                <i class="fas fa-{{ $amenity->activo ? 'times' : 'check' }} me-2"></i>
                                {{ $amenity->activo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        <a href="{{ route('admin.amenities.edit', $amenity->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>Editar Amenity
                        </a>
                    </div>
                </div>
            </div>

            <!-- Estadísticas de Consumo -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>
                        Estadísticas de Consumo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">Total Consumido</span>
                            <span class="fw-semibold">{{ $consumoTotal }} {{ $amenity->unidad_medida }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: {{ $consumoTotal > 0 ? min(100, ($consumoTotal / ($amenity->stock_actual + $consumoTotal)) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">Total Repuesto</span>
                            <span class="fw-semibold">{{ $reposicionTotal }} {{ $amenity->unidad_medida }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $reposicionTotal > 0 ? min(100, ($reposicionTotal / ($amenity->stock_actual + $reposicionTotal)) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">Stock Disponible</span>
                            <span class="fw-semibold">{{ $amenity->stock_actual }} {{ $amenity->unidad_medida }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: {{ $amenity->stock_actual > 0 ? min(100, ($amenity->stock_actual / ($amenity->stock_actual + $consumoTotal)) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-cog me-2 text-primary"></i>
                        Información del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-semibold">ID del Amenity</h6>
                        <p class="mb-0 text-muted font-monospace">{{ $amenity->id }}</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="mb-1 fw-semibold">Tipo de Consumo</h6>
                        <p class="mb-0 text-muted">{{ ucfirst(str_replace('_', ' ', $amenity->tipo_consumo)) }}</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="mb-1 fw-semibold">Estado del Stock</h6>
                        @if($amenity->stock_actual <= $amenity->stock_minimo)
                            <span class="badge bg-danger px-2 py-1">
                                <i class="fas fa-exclamation-triangle me-1"></i> Crítico
                            </span>
                        @elseif($amenity->stock_maximo && $amenity->stock_actual >= $amenity->stock_maximo)
                            <span class="badge bg-warning px-2 py-1">
                                <i class="fas fa-arrow-up me-1"></i> Alto
                            </span>
                        @else
                            <span class="badge bg-success px-2 py-1">
                                <i class="fas fa-check me-1"></i> Normal
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Consumos y Reposiciones -->
    <div class="row mt-4">
        <!-- Consumos Recientes -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-minus-circle me-2 text-warning"></i>
                        Consumos Recientes
                    </h5>
                    <a href="{{ route('admin.amenities.consumos', $amenity->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-list me-1"></i>
                        Ver todos los registros
                    </a>
                </div>
                <div class="card-body">
                    @if($consumosRecientes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Cantidad</th>
                                        <th>Tipo</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consumosRecientes as $consumo)
                                        <tr>
                                            <td>{{ $consumo->fecha_consumo->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="text-danger fw-bold">{{ $consumo->cantidad_consumida }}</span>
                                                {{ $amenity->unidad_medida }}
                                            </td>
                                            <td>
                                                <span class="badge bg-warning-subtle text-warning px-2 py-1">
                                                    {{ ucfirst($consumo->tipo_consumo) }}
                                                </span>
                                            </td>
                                            <td>{{ $consumo->user->name ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-minus-circle fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No hay consumos registrados</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Reposiciones Recientes -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-plus-circle me-2 text-success"></i>
                        Reposiciones Recientes
                    </h5>
                </div>
                <div class="card-body">
                    @if($reposicionesRecientes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Cantidad</th>
                                        <th>Precio</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reposicionesRecientes as $reposicion)
                                        <tr>
                                            <td>{{ $reposicion->fecha_reposicion->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="text-success fw-bold">{{ $reposicion->cantidad_reponida }}</span>
                                                {{ $amenity->unidad_medida }}
                                            </td>
                                            <td>{{ number_format($reposicion->precio_unitario, 2) }}€</td>
                                            <td>{{ $reposicion->user->name ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-plus-circle fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No hay reposiciones registradas</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Reposición Rápida -->
<div class="modal fade" id="modalReposicionRapida" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus text-success me-2"></i>
                    Reposición Rápida
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formReposicionRapida" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cantidad_reponida" class="form-label fw-semibold">
                            Cantidad a Reponer <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="cantidad_reponida" name="cantidad_reponida" 
                               required step="0.01" min="0.01" max="999999.99" placeholder="Ingresa la cantidad">
                    </div>
                    <div class="mb-3">
                        <label for="precio_unitario" class="form-label fw-semibold">
                            Precio Unitario (€) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="precio_unitario" name="precio_unitario" 
                               required step="0.01" min="0" max="999999.99" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label for="proveedor" class="form-label fw-semibold">Proveedor</label>
                        <input type="text" class="form-control" id="proveedor" name="proveedor" 
                               maxlength="255" placeholder="Nombre del proveedor (opcional)">
                    </div>
                    <div class="mb-3">
                        <label for="numero_factura" class="form-label fw-semibold">Número de Factura</label>
                        <input type="text" class="form-control" id="numero_factura" name="numero_factura" 
                               maxlength="255" placeholder="Número de factura (opcional)">
                    </div>
                    <div class="mb-3">
                        <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" 
                                  rows="3" maxlength="500" placeholder="Observaciones adicionales (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Registrar Reposición
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Consumo Rápido -->
<div class="modal fade" id="modalConsumoRapido" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-minus text-warning me-2"></i>
                    Consumo Rápido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formConsumoRapido" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cantidad_consumida" class="form-label fw-semibold">
                            Cantidad Consumida <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="cantidad_consumida" name="cantidad_consumida" 
                               required min="1" max="{{ $amenity->stock_actual }}" placeholder="Ingresa la cantidad consumida">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1 text-info"></i>
                            Stock disponible: {{ $amenity->stock_actual }} {{ $amenity->unidad_medida }}
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="tipo_consumo" class="form-label fw-semibold">
                            Tipo de Consumo <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="tipo_consumo" name="tipo_consumo" required>
                            <option value="">Seleccionar tipo</option>
                            <option value="reserva">Reserva</option>
                            <option value="limpieza">Limpieza</option>
                            <option value="ajuste">Ajuste</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" 
                                  rows="3" maxlength="500" placeholder="Observaciones adicionales (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i> Registrar Consumo
                    </button>
                </div>
            </form>
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
});

function registrarReposicion(amenityId) {
    $('#formReposicionRapida').attr('action', `/admin/amenities/${amenityId}/reposicion`);
    $('#modalReposicionRapida').modal('show');
}

function registrarConsumo(amenityId) {
    $('#formConsumoRapido').attr('action', `/admin/amenities/${amenityId}/consumo`);
    $('#modalConsumoRapido').modal('show');
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

.bg-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.progress {
    border-radius: 10px;
    background-color: #f8f9fa;
}

.progress-bar {
    border-radius: 10px;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e3e6f0;
}

.table td {
    border-bottom: 1px solid #f8f9fa;
    vertical-align: middle;
}

.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
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

/* Animaciones */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
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
}
</style>
@endsection
