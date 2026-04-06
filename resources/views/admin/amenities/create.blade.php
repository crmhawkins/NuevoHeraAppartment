@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>
                        Crear Nuevo Amenity
                    </h1>
                    <p class="text-muted mb-0">Agrega un nuevo producto o amenity al sistema</p>
                </div>
                <a href="{{ route('admin.amenities.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                </a>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="row">
        <div class="col-12">
            <form action="{{ route('admin.amenities.store') }}" method="POST" id="amenityForm">
                @csrf
                
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
                                <label for="nombre" class="form-label fw-semibold">
                                    Nombre del Amenity <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('nombre') is-invalid @enderror" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="{{ old('nombre') }}"
                                       maxlength="255"
                                       placeholder="Ej: Jabón de manos, Toallas, etc."
                                       required>
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-lightbulb me-1 text-info"></i>
                                    Nombre descriptivo y fácil de identificar
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="categoria" class="form-label fw-semibold">
                                    Categoría <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('categoria') is-invalid @enderror" 
                                        id="categoria" 
                                        name="categoria" 
                                        required>
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categorias as $key => $categoria)
                                        <option value="{{ $key }}" {{ old('categoria') == $key ? 'selected' : '' }}>
                                            {{ $categoria }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('categoria')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="descripcion" class="form-label fw-semibold">Descripción</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                          id="descripcion" 
                                          name="descripcion" 
                                          rows="3"
                                          maxlength="1000"
                                          placeholder="Descripción detallada del amenity (opcional)">{{ old('descripcion') }}</textarea>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Máximo 1000 caracteres
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración para Niños -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-baby me-2 text-success"></i>
                            Configuración para Niños
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="es_para_ninos" name="es_para_ninos" value="1" {{ old('es_para_ninos') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="es_para_ninos">
                                        Es para niños
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Marca si este amenity es específico para niños
                                </div>
                            </div>
                            
                            <div class="col-md-6" id="tipo_nino_container" style="display: none;">
                                <label for="tipo_nino" class="form-label fw-semibold">Tipo de Niño</label>
                                <select class="form-select" id="tipo_nino" name="tipo_nino">
                                    <option value="">Seleccionar tipo</option>
                                    <option value="bebe" {{ old('tipo_nino') == 'bebe' ? 'selected' : '' }}>Bebé (0-2 años)</option>
                                    <option value="nino_pequeno" {{ old('tipo_nino') == 'nino_pequeno' ? 'selected' : '' }}>Niño pequeño (3-6 años)</option>
                                    <option value="nino_grande" {{ old('tipo_nino') == 'nino_grande' ? 'selected' : '' }}>Niño grande (7-12 años)</option>
                                    <option value="adolescente" {{ old('tipo_nino') == 'adolescente' ? 'selected' : '' }}>Adolescente (13+ años)</option>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Categoría específica de edad para el amenity
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2" id="config_ninos_container" style="display: none;">
                            <div class="col-md-4">
                                <label for="edad_minima" class="form-label fw-semibold">Edad Mínima</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="edad_minima" 
                                       name="edad_minima" 
                                       value="{{ old('edad_minima') }}"
                                       min="0" 
                                       max="17"
                                       placeholder="0">
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Edad mínima en años (opcional)
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="edad_maxima" class="form-label fw-semibold">Edad Máxima</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="edad_maxima" 
                                       name="edad_maxima" 
                                       value="{{ old('edad_maxima') }}"
                                       min="0" 
                                       max="17"
                                       placeholder="17">
                                <div class="form-text">
                                    <div class="fas fa-info-circle me-1 text-info"></i>
                                    Edad máxima en años (opcional)
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="cantidad_por_nino" class="form-label fw-semibold">Cantidad por Niño</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="cantidad_por_nino" 
                                       name="cantidad_por_nino" 
                                       value="{{ old('cantidad_por_nino', 1) }}"
                                       min="1" 
                                       max="100"
                                       required>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Cantidad que se asigna por cada niño
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="notas_ninos" class="form-label fw-semibold">Notas para Niños</label>
                                <textarea class="form-control" 
                                          id="notas_ninos" 
                                          name="notas_ninos" 
                                          rows="2"
                                          placeholder="Notas específicas para amenities de niños (opcional)">{{ old('notas_ninos') }}</textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Información adicional sobre el uso con niños
                                </div>
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
                                <label for="unidad_medida" class="form-label fw-semibold">
                                    Unidad de Medida <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('unidad_medida') is-invalid @enderror" 
                                        id="unidad_medida" 
                                        name="unidad_medida" 
                                        required>
                                    <option value="">Seleccionar unidad</option>
                                    @foreach($unidadesMedida as $key => $unidad)
                                        <option value="{{ $key }}" {{ old('unidad_medida') == $key ? 'selected' : '' }}>
                                            {{ $unidad }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('unidad_medida')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4">
                                <label for="stock_actual" class="form-label fw-semibold">
                                    Stock Inicial <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('stock_actual') is-invalid @enderror" 
                                       id="stock_actual" 
                                       name="stock_actual" 
                                       value="{{ old('stock_actual', 0) }}"
                                       step="0.01"
                                       min="0" 
                                       max="999999.99"
                                       placeholder="0.00"
                                       required>
                                @error('stock_actual')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Cantidad inicial en inventario
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="stock_minimo" class="form-label fw-semibold">
                                    Stock Mínimo <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('stock_minimo') is-invalid @enderror" 
                                       id="stock_minimo" 
                                       name="stock_minimo" 
                                       value="{{ old('stock_minimo', 0) }}"
                                       step="0.01"
                                       min="0" 
                                       max="999999.99"
                                       placeholder="0.00"
                                       required>
                                @error('stock_minimo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                                    Nivel de alerta para reposición
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="stock_maximo" class="form-label fw-semibold">Stock Máximo</label>
                                <input type="number" 
                                       class="form-control @error('stock_maximo') is-invalid @enderror" 
                                       id="stock_maximo" 
                                       name="stock_maximo" 
                                       value="{{ old('stock_maximo') }}"
                                       step="0.01"
                                       min="0" 
                                       max="999999.99"
                                       placeholder="0.00">
                                @error('stock_maximo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-arrow-up me-1 text-info"></i>
                                    Límite superior de inventario (opcional)
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="precio_compra" class="form-label fw-semibold">
                                    Precio de Compra (€) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control @error('precio_compra') is-invalid @enderror" 
                                           id="precio_compra" 
                                           name="precio_compra" 
                                           value="{{ old('precio_compra') }}"
                                           step="0.01" 
                                           min="0" 
                                           max="999999.99"
                                           placeholder="0.00"
                                           required>
                                    <span class="input-group-text">€</span>
                                </div>
                                @error('precio_compra')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-euro-sign me-1 text-info"></i>
                                    Precio por unidad
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="activo" class="form-label fw-semibold">Estado</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="activo" 
                                           name="activo" 
                                           value="1"
                                           {{ old('activo', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="activo">
                                        Amenity activo
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-toggle-on me-1 text-success"></i>
                                    Desmarca para desactivar temporalmente
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
                            <div class="col-md-6">
                                <label for="tipo_consumo" class="form-label fw-semibold">
                                    Tipo de Consumo <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('tipo_consumo') is-invalid @enderror" 
                                        id="tipo_consumo" 
                                        name="tipo_consumo" 
                                        required>
                                    <option value="">Seleccionar tipo</option>
                                    @foreach($tiposConsumo as $key => $tipo)
                                        <option value="{{ $key }}" {{ old('tipo_consumo') == $key ? 'selected' : '' }}>
                                            {{ $tipo }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tipo_consumo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    Define cómo se calcula el consumo
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="unidad_consumo" class="form-label fw-semibold">Unidad de Consumo</label>
                                <input type="text" 
                                       class="form-control @error('unidad_consumo') is-invalid @enderror" 
                                       id="unidad_consumo" 
                                       name="unidad_consumo" 
                                       value="{{ old('unidad_consumo') }}"
                                       maxlength="255"
                                       placeholder="Ej: por día, por persona, etc.">
                                @error('unidad_consumo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Campos específicos por tipo de consumo -->
                            <div class="col-md-4 consumo-por-reserva" style="display: none;">
                                <label for="consumo_por_reserva" class="form-label fw-semibold">Consumo por Reserva</label>
                                <input type="number" 
                                       class="form-control @error('consumo_por_reserva') is-invalid @enderror" 
                                       id="consumo_por_reserva" 
                                       name="consumo_por_reserva" 
                                       value="{{ old('consumo_por_reserva') }}"
                                       step="any"
                                       min="0" 
                                       max="999999.99"
                                       placeholder="0.00">
                                @error('consumo_por_reserva')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 consumo-por-reserva" style="display: none;">
                                <label for="consumo_minimo_reserva" class="form-label fw-semibold">Consumo Mínimo</label>
                                <input type="number" 
                                       class="form-control @error('consumo_minimo_reserva') is-invalid @enderror" 
                                       id="consumo_minimo_reserva" 
                                       name="consumo_minimo_reserva" 
                                       value="{{ old('consumo_minimo_reserva') }}"
                                       step="any"
                                       min="0" 
                                       max="999999.99"
                                       placeholder="0.00">
                                @error('consumo_minimo_reserva')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 consumo-por-reserva" style="display: none;">
                                <label for="consumo_maximo_reserva" class="form-label fw-semibold">Consumo Máximo</label>
                                <input type="number" 
                                       class="form-control @error('consumo_maximo_reserva') is-invalid @enderror" 
                                       id="consumo_maximo_reserva" 
                                       name="consumo_maximo_reserva" 
                                       value="{{ old('consumo_maximo_reserva') }}"
                                       step="any"
                                       min="0" 
                                       max="999999.99"
                                       placeholder="0.00">
                                @error('consumo_maximo_reserva')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 consumo-por-tiempo" style="display: none;">
                                <label for="duracion_dias" class="form-label fw-semibold">Duración en Días</label>
                                <input type="number" 
                                       class="form-control @error('duracion_dias') is-invalid @enderror" 
                                       id="duracion_dias" 
                                       name="duracion_dias" 
                                       value="{{ old('duracion_dias') }}"
                                       min="1" 
                                       max="365"
                                       placeholder="1">
                                @error('duracion_dias')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 consumo-por-persona" style="display: none;">
                                <label for="consumo_por_persona" class="form-label fw-semibold">Consumo por Persona</label>
                                <input type="number" 
                                       class="form-control @error('consumo_por_persona') is-invalid @enderror" 
                                       id="consumo_por_persona" 
                                       name="consumo_por_persona" 
                                       value="{{ old('consumo_por_persona') }}"
                                       step="0.01" 
                                       min="0" 
                                       max="999999.99"
                                       placeholder="0.00">
                                @error('consumo_por_persona')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                <label for="proveedor" class="form-label fw-semibold">Proveedor</label>
                                <input type="text" 
                                       class="form-control @error('proveedor') is-invalid @enderror" 
                                       id="proveedor" 
                                       name="proveedor" 
                                       value="{{ old('proveedor') }}"
                                       maxlength="255"
                                       placeholder="Nombre del proveedor (opcional)">
                                @error('proveedor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-truck me-1 text-info"></i>
                                    Proveedor principal del producto
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="codigo_producto" class="form-label fw-semibold">Código del Producto</label>
                                <input type="text" 
                                       class="form-control @error('codigo_producto') is-invalid @enderror" 
                                       id="codigo_producto" 
                                       name="codigo_producto" 
                                       value="{{ old('codigo_producto') }}"
                                       maxlength="255"
                                       placeholder="Código interno o SKU (opcional)">
                                @error('codigo_producto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-barcode me-1 text-info"></i>
                                    Código interno o SKU del producto
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.amenities.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">
                        <i class="fas fa-save me-2"></i>Crear Amenity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoConsumo = document.getElementById('tipo_consumo');
    const camposPorReserva = document.querySelectorAll('.consumo-por-reserva');
    const camposPorTiempo = document.querySelectorAll('.consumo-por-tiempo');
    const camposPorPersona = document.querySelectorAll('.consumo-por-persona');

    // Función para mostrar/ocultar campos según el tipo de consumo
    function toggleCamposConsumo() {
        const tipo = tipoConsumo.value;
        
        // Ocultar todos los campos
        camposPorReserva.forEach(campo => campo.style.display = 'none');
        camposPorTiempo.forEach(campo => campo.style.display = 'none');
        camposPorPersona.forEach(campo => campo.style.display = 'none');
        
        // Mostrar campos según el tipo seleccionado
        switch(tipo) {
            case 'por_reserva':
                camposPorReserva.forEach(campo => campo.style.display = 'block');
                break;
            case 'por_tiempo':
                camposPorTiempo.forEach(campo => campo.style.display = 'block');
                break;
            case 'por_persona':
                camposPorPersona.forEach(campo => campo.style.display = 'block');
                break;
        }
    }

    // Evento para cambiar tipo de consumo
    tipoConsumo.addEventListener('change', toggleCamposConsumo);
    
    // Ejecutar al cargar la página
    toggleCamposConsumo();

    // Validación en tiempo real
    const form = document.getElementById('amenityForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('is-invalid');
            } else if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
        });
    });

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar campos requeridos
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });
        
        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Por favor, completa todos los campos obligatorios.',
                confirmButtonColor: '#d33'
            });
            return;
        }
        
        // Mostrar loading
        const btnSubmit = document.getElementById('btnSubmit');
        const originalText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
        btnSubmit.disabled = true;
        
        // Enviar formulario
        form.submit();
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

    // Mostrar errores de validación
    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Errores de validación',
            text: 'Por favor, corrige los errores en el formulario.',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Aceptar'
        });
    @endif

    // Control de campos para niños
    const esParaNinosCheckbox = document.getElementById('es_para_ninos');
    const tipoNinoContainer = document.getElementById('tipo_nino_container');
    const configNinosContainer = document.getElementById('config_ninos_container');
    const tipoNinoSelect = document.getElementById('tipo_nino');
    const edadMinimaInput = document.getElementById('edad_minima');
    const edadMaximaInput = document.getElementById('edad_maxima');

    if (esParaNinosCheckbox) {
        esParaNinosCheckbox.addEventListener('change', function() {
            const esParaNinos = this.checked;
            
            if (esParaNinos) {
                tipoNinoContainer.style.display = 'block';
                configNinosContainer.style.display = 'block';
            } else {
                tipoNinoContainer.style.display = 'none';
                configNinosContainer.style.display = 'none';
            }
        });
    }

    // Auto-completar rangos de edad según tipo de niño
    if (tipoNinoSelect) {
        tipoNinoSelect.addEventListener('change', function() {
            const tipoNino = this.value;
            
            if (tipoNino) {
                switch (tipoNino) {
                    case 'bebe':
                        edadMinimaInput.value = '0';
                        edadMaximaInput.value = '2';
                        break;
                    case 'nino_pequeno':
                        edadMinimaInput.value = '3';
                        edadMaximaInput.value = '6';
                        break;
                    case 'nino_grande':
                        edadMinimaInput.value = '7';
                        edadMaximaInput.value = '12';
                        break;
                    case 'adolescente':
                        edadMinimaInput.value = '13';
                        edadMaximaInput.value = '17';
                        break;
                }
            }
        });
    }

    // Inicializar estado de campos para niños
    if (esParaNinosCheckbox && esParaNinosCheckbox.checked) {
        tipoNinoContainer.style.display = 'block';
        configNinosContainer.style.display = 'block';
    }
});
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

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    transition: all 0.2s ease-in-out;
    padding: 0.75rem 1rem;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-label {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.form-check-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #e3e6f0;
    color: #6c757d;
}

.invalid-feedback {
    font-size: 0.875rem;
    color: #dc3545;
}

.text-danger {
    color: #dc3545 !important;
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
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
    
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
