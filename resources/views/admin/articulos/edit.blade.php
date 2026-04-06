@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-edit text-primary me-2"></i>
            Editar Artículo
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.articulos.index') }}">Artículos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.articulos.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
    </div>
</div>

<!-- Formulario -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-box me-2"></i>
                    Información del Artículo
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.articulos.update', $articulo->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-tag me-1"></i>
                                Nombre del Artículo <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="{{ old('nombre', $articulo->nombre) }}" 
                                   required>
                            @error('nombre')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="proveedor_id" class="form-label">
                                <i class="fas fa-truck me-1"></i>
                                Proveedor <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('proveedor_id') is-invalid @enderror" 
                                    id="proveedor_id" 
                                    name="proveedor_id" 
                                    required>
                                <option value="">Seleccionar proveedor</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" 
                                            {{ old('proveedor_id', $articulo->proveedor_id) == $proveedor->id ? 'selected' : '' }}>
                                        {{ $proveedor->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('proveedor_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="categoria" class="form-label">
                                <i class="fas fa-folder me-1"></i>
                                Categoría <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('categoria') is-invalid @enderror" 
                                   id="categoria" 
                                   name="categoria" 
                                   value="{{ old('categoria', $articulo->categoria) }}"
                                   placeholder="Ej: Limpieza, Mantenimiento, Cocina..."
                                   required>
                            @error('categoria')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="unidad_medida" class="form-label">
                                <i class="fas fa-ruler me-1"></i>
                                Unidad de Medida <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('unidad_medida') is-invalid @enderror" 
                                    id="unidad_medida" 
                                    name="unidad_medida" 
                                    required>
                                <option value="">Seleccionar unidad</option>
                                <option value="unidad" {{ old('unidad_medida', $articulo->unidad_medida) == 'unidad' ? 'selected' : '' }}>Unidad</option>
                                <option value="kg" {{ old('unidad_medida', $articulo->unidad_medida) == 'kg' ? 'selected' : '' }}>Kilogramo (kg)</option>
                                <option value="g" {{ old('unidad_medida', $articulo->unidad_medida) == 'g' ? 'selected' : '' }}>Gramo (g)</option>
                                <option value="l" {{ old('unidad_medida', $articulo->unidad_medida) == 'l' ? 'selected' : '' }}>Litro (l)</option>
                                <option value="ml" {{ old('unidad_medida', $articulo->unidad_medida) == 'ml' ? 'selected' : '' }}>Mililitro (ml)</option>
                                <option value="m" {{ old('unidad_medida', $articulo->unidad_medida) == 'm' ? 'selected' : '' }}>Metro (m)</option>
                                <option value="cm" {{ old('unidad_medida', $articulo->unidad_medida) == 'cm' ? 'selected' : '' }}>Centímetro (cm)</option>
                                <option value="m2" {{ old('unidad_medida', $articulo->unidad_medida) == 'm2' ? 'selected' : '' }}>Metro cuadrado (m²)</option>
                                <option value="pack" {{ old('unidad_medida', $articulo->unidad_medida) == 'pack' ? 'selected' : '' }}>Pack</option>
                                <option value="caja" {{ old('unidad_medida', $articulo->unidad_medida) == 'caja' ? 'selected' : '' }}>Caja</option>
                            </select>
                            @error('unidad_medida')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="precio_compra" class="form-label">
                                <i class="fas fa-euro-sign me-1"></i>
                                Precio de Compra <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('precio_compra') is-invalid @enderror" 
                                       id="precio_compra" 
                                       name="precio_compra" 
                                       value="{{ old('precio_compra', $articulo->precio_compra) }}" 
                                       step="0.01" 
                                       min="0"
                                       required>
                                <span class="input-group-text">€</span>
                            </div>
                            @error('precio_compra')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stock_actual" class="form-label">
                                <i class="fas fa-boxes me-1"></i>
                                Stock Actual
                            </label>
                            <input type="number" 
                                   class="form-control @error('stock_actual') is-invalid @enderror" 
                                   id="stock_actual" 
                                   name="stock_actual" 
                                   value="{{ old('stock_actual', $articulo->stock_actual) }}" 
                                   min="0"
                                   step="0.01"
                                   required>
                            <small class="form-text text-muted">Stock actual del artículo</small>
                            @error('stock_actual')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="stock_minimo" class="form-label">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Stock Mínimo
                            </label>
                            <input type="number" 
                                   class="form-control @error('stock_minimo') is-invalid @enderror" 
                                   id="stock_minimo" 
                                   name="stock_minimo" 
                                   value="{{ old('stock_minimo', $articulo->stock_minimo) }}" 
                                   min="0">
                            @error('stock_minimo')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">
                            <i class="fas fa-align-left me-1"></i>
                            Descripción
                        </label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="3" 
                                  placeholder="Descripción detallada del artículo...">{{ old('descripcion', $articulo->descripcion) }}</textarea>
                        @error('descripcion')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="activo" 
                                   name="activo" 
                                   value="1" 
                                   {{ old('activo', $articulo->activo) ? 'checked' : '' }}>
                            <label class="form-check-label" for="activo">
                                <i class="fas fa-check-circle me-1"></i>
                                Artículo activo
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.articulos.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Artículo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-info-circle me-2"></i>
                    Información del Artículo
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Stock Actual:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-{{ $articulo->stock_actual > $articulo->stock_minimo ? 'success' : ($articulo->stock_actual > 0 ? 'warning' : 'danger') }}">
                            {{ $articulo->stock_actual }}
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Stock Mínimo:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-info">{{ $articulo->stock_minimo }}</span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Valor Stock:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-success">{{ number_format($articulo->stock_actual * $articulo->precio_compra, 2) }} €</span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Estado:</strong>
                    </div>
                    <div class="col-6">
                        @if($articulo->activo)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Inactivo</span>
                        @endif
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Creado:</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">{{ $articulo->created_at->format('d/m/Y') }}</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <strong>Actualizado:</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">{{ $articulo->updated_at->format('d/m/Y') }}</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Últimos Movimientos
                </h6>
            </div>
            <div class="card-body">
                @if($articulo->movimientosStock->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($articulo->movimientosStock->take(5) as $movimiento)
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">
                                            <span class="badge bg-{{ $movimiento->tipo == 'entrada' ? 'success' : ($movimiento->tipo == 'salida' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($movimiento->tipo) }}
                                            </span>
                                        </h6>
                                        <small class="text-muted">{{ $movimiento->cantidad }} unidades</small>
                                    </div>
                                    <small class="text-muted">{{ $movimiento->fecha_movimiento->format('d/m') }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($articulo->movimientosStock->count() > 5)
                        <div class="text-center mt-2">
                            <small class="text-muted">Y {{ $articulo->movimientosStock->count() - 5 }} más...</small>
                        </div>
                    @endif
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-exchange-alt fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No hay movimientos registrados</p>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt me-2"></i>
                    Acciones Rápidas
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.articulos.show', $articulo->id) }}" class="btn btn-outline-info">
                        <i class="fas fa-eye me-2"></i>
                        Ver Detalles
                    </a>
                    <button type="button" 
                            class="btn btn-outline-success" 
                            onclick="reponerStock({{ $articulo->id }}, '{{ $articulo->nombre }}')">
                        <i class="fas fa-plus me-2"></i>
                        Reponer Stock
                    </button>
                    <a href="{{ route('admin.movimientos-stock.create', ['articulo_id' => $articulo->id]) }}" class="btn btn-outline-primary">
                        <i class="fas fa-exchange-alt me-2"></i>
                        Nuevo Movimiento
                    </a>
                </div>
            </div>
        </div>
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
    // Validación del formulario
    const form = document.querySelector('form');
    const nombreInput = document.getElementById('nombre');
    const categoriaInput = document.getElementById('categoria');
    const unidadMedidaSelect = document.getElementById('unidad_medida');
    const proveedorSelect = document.getElementById('proveedor_id');
    const precioInput = document.getElementById('precio_compra');
    const stockActualInput = document.getElementById('stock_actual');
    const stockMinimoInput = document.getElementById('stock_minimo');
    
    form.addEventListener('submit', function(e) {
        let hasErrors = false;
        
        // Validar nombre
        if (!nombreInput.value.trim()) {
            nombreInput.classList.add('is-invalid');
            hasErrors = true;
        }
        
        // Validar categoría
        if (!categoriaInput.value.trim()) {
            categoriaInput.classList.add('is-invalid');
            hasErrors = true;
        }
        
        // Validar unidad de medida
        if (!unidadMedidaSelect.value) {
            unidadMedidaSelect.classList.add('is-invalid');
            hasErrors = true;
        }
        
        // Validar proveedor
        if (!proveedorSelect.value) {
            proveedorSelect.classList.add('is-invalid');
            hasErrors = true;
        }
        
        // Validar precio
        if (!precioInput.value || parseFloat(precioInput.value) < 0) {
            precioInput.classList.add('is-invalid');
            hasErrors = true;
        }
        
        // Validar stock actual
        if (!stockActualInput.value || parseFloat(stockActualInput.value) < 0) {
            stockActualInput.classList.add('is-invalid');
            hasErrors = true;
        }
        
        // Validar stock mínimo
        if (!stockMinimoInput.value || parseFloat(stockMinimoInput.value) < 0) {
            stockMinimoInput.classList.add('is-invalid');
            hasErrors = true;
        }
        
        if (hasErrors) {
            e.preventDefault();
            return false;
        }
    });
    
    // Limpiar errores al escribir
    [nombreInput, categoriaInput, unidadMedidaSelect, proveedorSelect, precioInput, stockActualInput, stockMinimoInput].forEach(input => {
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
        });
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