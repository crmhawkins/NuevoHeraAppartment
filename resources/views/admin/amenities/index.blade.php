@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header con estadísticas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-gift me-2 text-primary"></i>
                        Gestión de Amenities
                    </h1>
                    <p class="text-muted mb-0">Administra todos los amenities y productos de consumo</p>
                </div>
                <a href="{{ route('admin.amenities.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Amenity
                </a>
            </div>
        </div>
    </div>

    <!-- Dashboard de estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-primary text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-gift fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $totalAmenities }}</h3>
                            <small class="opacity-75">Total Amenities</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-success text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $amenitiesActivos }}</h3>
                            <small class="opacity-75">Activos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $stockBajoCount }}</h3>
                            <small class="opacity-75">Stock Bajo</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-info text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-euro-sign fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ number_format($valorTotal, 2) }}€</h3>
                            <small class="opacity-75">Valor Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda avanzada -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form id="filtrosForm" method="GET" action="{{ route('admin.amenities.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   class="form-control border-start-0 ps-0" 
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Buscar por nombre, categoría, descripción o proveedor...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="categoria" class="form-select">
                            <option value="">Todas las categorías</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria }}" {{ request('categoria') == $categoria ? 'selected' : '' }}>
                                    {{ ucfirst($categoria) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activos</option>
                            <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="stock" class="form-select">
                            <option value="">Todos</option>
                            <option value="bajo" {{ request('stock') == 'bajo' ? 'selected' : '' }}>Stock Bajo</option>
                            <option value="normal" {{ request('stock') == 'normal' ? 'selected' : '' }}>Stock Normal</option>
                            <option value="alto" {{ request('stock') == 'alto' ? 'selected' : '' }}>Stock Alto</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Filtrar
                            </button>
                            <a href="{{ route('admin.amenities.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Amenities -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Lista de Amenities ({{ $amenities->total() }})
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        <i class="fas fa-gift me-1"></i>
                        {{ $amenities->count() }} mostrados
                    </span>
                    @if($stockBajo->count() > 0)
                        <button type="button" class="btn btn-warning btn-sm" onclick="mostrarStockBajo()">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Stock Bajo ({{ $stockBajo->count() }})
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($amenities->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tablaAmenities">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 py-3 px-4">
                                    <div class="d-flex align-items-center">
                                        <span class="fw-semibold">Nombre</span>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'nombre', 'order' => request('sort') == 'nombre' && request('order') == 'asc' ? 'desc' : 'asc']) }}" 
                                           class="ms-2 text-muted">
                                            <i class="fas fa-sort{{ request('sort') == 'nombre' ? (request('order') == 'asc' ? '-up' : '-down') : '' }}"></i>
                                        </a>
                                    </div>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <div class="d-flex align-items-center">
                                        <span class="fw-semibold">Categoría</span>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'categoria', 'order' => request('sort') == 'categoria' && request('order') == 'asc' ? 'desc' : 'asc']) }}" 
                                           class="ms-2 text-muted">
                                            <i class="fas fa-sort{{ request('sort') == 'categoria' ? (request('sort') == 'asc' ? '-up' : '-down') : '' }}"></i>
                                        </a>
                                    </div>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Tipo Consumo</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <div class="d-flex align-items-center">
                                        <span class="fw-semibold">Stock</span>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'stock_actual', 'order' => request('sort') == 'stock_actual' && request('order') == 'asc' ? 'desc' : 'asc']) }}" 
                                           class="ms-2 text-muted">
                                            <i class="fas fa-sort{{ request('sort') == 'stock_actual' ? (request('order') == 'asc' ? '-up' : '-down') : '' }}"></i>
                                        </a>
                                    </div>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <div class="d-flex align-items-center">
                                        <span class="fw-semibold">Precio</span>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'precio_compra', 'order' => request('sort') == 'precio_compra' && request('order') == 'asc' ? 'desc' : 'asc']) }}" 
                                           class="ms-2 text-muted">
                                            <i class="fas fa-sort{{ request('sort') == 'precio_compra' ? (request('order') == 'asc' ? '-up' : '-down') : '' }}"></i>
                                        </a>
                                    </div>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Estado</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($amenities as $amenity)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <i class="fas fa-gift text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $amenity->nombre }}</h6>
                                                @if($amenity->descripcion)
                                                    <small class="text-muted">{{ Str::limit($amenity->descripcion, 50) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary px-2 py-1">
                                            {{ ucfirst($amenity->categoria) }}
                                        </span>
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <strong class="fs-6">{{ $amenity->stock_actual }}</strong>
                                                <small class="text-muted ms-1">{{ $amenity->unidad_medida }}</small>
                                            </div>
                                            <div class="stock-indicator">
                                                @if($amenity->stock_actual <= $amenity->stock_minimo)
                                                    <span class="badge bg-danger px-2 py-1">
                                                        <i class="fas fa-exclamation-triangle me-1"></i> Bajo
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
                                    </td>
                                    <td>
                                        <strong class="text-success">{{ number_format($amenity->precio_compra, 2) }} €</strong>
                                    </td>
                                    <td>
                                        @if($amenity->activo)
                                            <span class="badge bg-success px-2 py-1">
                                                <i class="fas fa-check-circle me-1"></i> Activo
                                            </span>
                                        @else
                                            <span class="badge bg-danger px-2 py-1">
                                                <i class="fas fa-times-circle me-1"></i> Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.amenities.show', $amenity->id) }}" 
                                               class="btn btn-outline-info btn-sm" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.amenities.edit', $amenity->id) }}" 
                                               class="btn btn-outline-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-success btn-sm" 
                                                    onclick="registrarReposicion({{ $amenity->id }})" title="Registrar reposición">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                                    onclick="registrarConsumo({{ $amenity->id }})" title="Registrar consumo">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <form action="{{ route('admin.amenities.toggle-status', $amenity->id) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-{{ $amenity->activo ? 'secondary' : 'success' }} btn-sm" 
                                                        title="{{ $amenity->activo ? 'Desactivar' : 'Activar' }}">
                                                    <i class="fas fa-{{ $amenity->activo ? 'times' : 'check' }}"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="confirmarEliminacion({{ $amenity->id }}, '{{ $amenity->nombre }}')" 
                                                    title="Eliminar">
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
                    {{ $amenities->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-gift fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No se encontraron amenities</h5>
                    <p class="text-muted">Intenta ajustar los filtros o crear un nuevo amenity.</p>
                    <a href="{{ route('admin.amenities.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Crear Primer Amenity
                    </a>
                </div>
            @endif
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
                               required min="1" placeholder="Ingresa la cantidad consumida">
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

<!-- Modal de Stock Bajo -->
<div class="modal fade" id="modalStockBajo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Productos con Stock Bajo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($stockBajo->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockBajo as $amenity)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-gift text-primary me-2"></i>
                                                <strong>{{ $amenity->nombre }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-danger fw-bold">{{ $amenity->stock_actual }}</span>
                                            {{ $amenity->unidad_medida }}
                                        </td>
                                        <td>{{ $amenity->stock_minimo }} {{ $amenity->unidad_medida }}</td>
                                        <td>
                                            <span class="badge bg-danger px-2 py-1">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Crítico
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success btn-sm" 
                                                    onclick="registrarReposicion({{ $amenity->id }})">
                                                <i class="fas fa-plus me-1"></i> Reponer
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5 class="text-success">¡Excelente!</h5>
                        <p class="text-muted">Todos los productos tienen stock suficiente.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#tablaAmenities').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        pageLength: 25,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [6] } // Columna de acciones no ordenable
        ]
    });

    // Filtros en tiempo real
    $('#filtrosForm select').on('change', function() {
        $('#filtrosForm').submit();
    });

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

function mostrarStockBajo() {
    $('#modalStockBajo').modal('show');
}

function confirmarEliminacion(amenityId, nombre) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Quieres eliminar el amenity "${nombre}"? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario temporal para eliminar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/amenities/${amenityId}`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 50%;
}

.stock-indicator {
    min-width: 60px;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}

.btn-group .btn {
    margin-right: 2px;
    border-radius: 6px !important;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.table td {
    vertical-align: middle;
}

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

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e3e6f0;
}

.table td {
    border-bottom: 1px solid #f8f9fa;
}

.pagination {
    gap: 0.25rem;
}

.page-link {
    border-radius: 6px;
    border: 1px solid #e3e6f0;
    color: #667eea;
    transition: all 0.2s ease-in-out;
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
</style>
@endsection
