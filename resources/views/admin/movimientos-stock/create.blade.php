@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-plus text-primary me-2"></i>
            Crear Movimiento de Stock
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.movimientos-stock.index') }}">Movimientos de Stock</a></li>
                <li class="breadcrumb-item active" aria-current="page">Crear</li>
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

<!-- Formulario -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Información del Movimiento
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.movimientos-stock.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="articulo_id" class="form-label">
                                <i class="fas fa-box me-1"></i>
                                Artículo <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('articulo_id') is-invalid @enderror" 
                                    id="articulo_id" 
                                    name="articulo_id" 
                                    required>
                                <option value="">Seleccionar artículo</option>
                                @foreach($articulos as $articulo)
                                    <option value="{{ $articulo->id }}" 
                                            {{ old('articulo_id', request('articulo_id')) == $articulo->id ? 'selected' : '' }}>
                                        {{ $articulo->nombre }} (Stock: {{ $articulo->stock_actual }})
                                    </option>
                                @endforeach
                            </select>
                            @error('articulo_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="tipo" class="form-label">
                                <i class="fas fa-exchange-alt me-1"></i>
                                Tipo de Movimiento <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('tipo') is-invalid @enderror" 
                                    id="tipo" 
                                    name="tipo" 
                                    required>
                                <option value="">Seleccionar tipo</option>
                                <option value="entrada" {{ old('tipo') == 'entrada' ? 'selected' : '' }}>Entrada</option>
                                <option value="salida" {{ old('tipo') == 'salida' ? 'selected' : '' }}>Salida</option>
                                <option value="ajuste" {{ old('tipo') == 'ajuste' ? 'selected' : '' }}>Ajuste</option>
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cantidad" class="form-label">
                                <i class="fas fa-hashtag me-1"></i>
                                Cantidad <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control @error('cantidad') is-invalid @enderror" 
                                   id="cantidad" 
                                   name="cantidad" 
                                   value="{{ old('cantidad') }}" 
                                   min="1"
                                   required>
                            @error('cantidad')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="fecha_movimiento" class="form-label">
                                <i class="fas fa-calendar me-1"></i>
                                Fecha del Movimiento <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control @error('fecha_movimiento') is-invalid @enderror" 
                                   id="fecha_movimiento" 
                                   name="fecha_movimiento" 
                                   value="{{ old('fecha_movimiento', date('Y-m-d')) }}" 
                                   required>
                            @error('fecha_movimiento')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="proveedor_id" class="form-label">
                                <i class="fas fa-truck me-1"></i>
                                Proveedor
                            </label>
                            <select class="form-select @error('proveedor_id') is-invalid @enderror" 
                                    id="proveedor_id" 
                                    name="proveedor_id">
                                <option value="">Seleccionar proveedor (opcional)</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" 
                                            {{ old('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
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

                        <div class="col-md-6 mb-3">
                            <label for="apartamento_limpieza_id" class="form-label">
                                <i class="fas fa-home me-1"></i>
                                Limpieza de Apartamento
                            </label>
                            <select class="form-select @error('apartamento_limpieza_id') is-invalid @enderror" 
                                    id="apartamento_limpieza_id" 
                                    name="apartamento_limpieza_id">
                                <option value="">Seleccionar limpieza (opcional)</option>
                                @foreach($limpiezas as $limpieza)
                                    <option value="{{ $limpieza->id }}" 
                                            {{ old('apartamento_limpieza_id') == $limpieza->id ? 'selected' : '' }}>
                                        {{ $limpieza->apartamento->nombre ?? 'Apartamento' }} - {{ $limpieza->fecha_comienzo ? $limpieza->fecha_comienzo->format('d/m/Y') : 'Sin fecha' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('apartamento_limpieza_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">
                            <i class="fas fa-comment me-1"></i>
                            Motivo <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('motivo') is-invalid @enderror" 
                               id="motivo" 
                               name="motivo" 
                               value="{{ old('motivo') }}" 
                               placeholder="Ej: Compra, Uso en limpieza, Ajuste de inventario..."
                               required>
                        @error('motivo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label">
                            <i class="fas fa-sticky-note me-1"></i>
                            Observaciones
                        </label>
                        <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                  id="observaciones" 
                                  name="observaciones" 
                                  rows="3" 
                                  placeholder="Observaciones adicionales...">{{ old('observaciones') }}</textarea>
                        @error('observaciones')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.movimientos-stock.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Crear Movimiento
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
                    Información
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Consejo:</strong> Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Stock:</strong> Las salidas no pueden exceder el stock disponible del artículo.
                </div>
                
                <div class="alert alert-success">
                    <i class="fas fa-check me-2"></i>
                    <strong>Automático:</strong> El stock del artículo se actualizará automáticamente.
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-box me-2"></i>
                    Información del Artículo
                </h6>
            </div>
            <div class="card-body" id="articulo-info">
                <div class="text-center py-3">
                    <i class="fas fa-box fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">Selecciona un artículo para ver su información</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const articuloSelect = document.getElementById('articulo_id');
    const articuloInfo = document.getElementById('articulo-info');
    const cantidadInput = document.getElementById('cantidad');
    const tipoSelect = document.getElementById('tipo');
    
    // Información de artículos
    const articulos = @json($articulosData);
    
    // Actualizar información del artículo
    function updateArticuloInfo() {
        const articuloId = articuloSelect.value;
        const articulo = articulos.find(a => a.id == articuloId);
        
        if (articulo) {
            articuloInfo.innerHTML = `
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Stock Actual:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-${articulo.stock_actual > articulo.stock_minimo ? 'success' : (articulo.stock_actual > 0 ? 'warning' : 'danger')}">
                            ${articulo.stock_actual}
                        </span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Stock Mínimo:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-info">${articulo.stock_minimo}</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Precio:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-success">${articulo.precio_compra.toFixed(2)} €</span>
                    </div>
                </div>
                ${articulo.proveedor ? `
                <div class="row">
                    <div class="col-6">
                        <strong>Proveedor:</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">${articulo.proveedor}</small>
                    </div>
                </div>
                ` : ''}
            `;
        } else {
            articuloInfo.innerHTML = `
                <div class="text-center py-3">
                    <i class="fas fa-box fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">Selecciona un artículo para ver su información</p>
                </div>
            `;
        }
    }
    
    // Validar cantidad según tipo y stock
    function validateCantidad() {
        const articuloId = articuloSelect.value;
        const tipo = tipoSelect.value;
        const cantidad = parseInt(cantidadInput.value);
        
        if (articuloId && tipo && cantidad) {
            const articulo = articulos.find(a => a.id == articuloId);
            
            if (tipo === 'salida' && cantidad > articulo.stock_actual) {
                cantidadInput.setCustomValidity(`No hay suficiente stock. Disponible: ${articulo.stock_actual}`);
                cantidadInput.classList.add('is-invalid');
            } else {
                cantidadInput.setCustomValidity('');
                cantidadInput.classList.remove('is-invalid');
            }
        }
    }
    
    // Event listeners
    articuloSelect.addEventListener('change', updateArticuloInfo);
    tipoSelect.addEventListener('change', validateCantidad);
    cantidadInput.addEventListener('input', validateCantidad);
    
    // Validación del formulario
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const articuloId = articuloSelect.value;
        const tipo = tipoSelect.value;
        const cantidad = parseInt(cantidadInput.value);
        const motivo = document.getElementById('motivo').value;
        
        if (!articuloId || !tipo || !cantidad || !motivo) {
            e.preventDefault();
            alert('Por favor, completa todos los campos obligatorios.');
            return false;
        }
        
        if (tipo === 'salida') {
            const articulo = articulos.find(a => a.id == articuloId);
            if (cantidad > articulo.stock_actual) {
                e.preventDefault();
                alert(`No hay suficiente stock. Disponible: ${articulo.stock_actual}`);
                return false;
            }
        }
    });
    
    // Cargar información inicial si hay un artículo preseleccionado
    updateArticuloInfo();
});
</script>
@endsection