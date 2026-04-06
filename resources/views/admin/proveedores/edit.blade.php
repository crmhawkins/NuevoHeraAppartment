@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-edit text-primary me-2"></i>
            Editar Proveedor
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.proveedores.index') }}">Proveedores</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.proveedores.index') }}" class="btn btn-outline-secondary">
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
                    <i class="fas fa-truck me-2"></i>
                    Información del Proveedor
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.proveedores.update', $proveedor->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-building me-1"></i>
                                Nombre del Proveedor <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="{{ old('nombre', $proveedor->nombre) }}" 
                                   required>
                            @error('nombre')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="contacto" class="form-label">
                                <i class="fas fa-user me-1"></i>
                                Persona de Contacto
                            </label>
                            <input type="text" 
                                   class="form-control @error('contacto') is-invalid @enderror" 
                                   id="contacto" 
                                   name="contacto" 
                                   value="{{ old('contacto', $proveedor->contacto) }}">
                            @error('contacto')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">
                                <i class="fas fa-phone me-1"></i>
                                Teléfono
                            </label>
                            <input type="tel" 
                                   class="form-control @error('telefono') is-invalid @enderror" 
                                   id="telefono" 
                                   name="telefono" 
                                   value="{{ old('telefono', $proveedor->telefono) }}">
                            @error('telefono')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>
                                Email
                            </label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $proveedor->email) }}">
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="direccion" class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            Dirección
                        </label>
                        <textarea class="form-control @error('direccion') is-invalid @enderror" 
                                  id="direccion" 
                                  name="direccion" 
                                  rows="3">{{ old('direccion', $proveedor->direccion) }}</textarea>
                        @error('direccion')
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
                                   {{ old('activo', $proveedor->activo) ? 'checked' : '' }}>
                            <label class="form-check-label" for="activo">
                                <i class="fas fa-check-circle me-1"></i>
                                Proveedor activo
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.proveedores.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Proveedor
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
                    Información del Proveedor
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Artículos:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-info">{{ $proveedor->total_articulos }}</span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Valor Stock:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-success">{{ number_format($proveedor->valor_total_stock, 2) }} €</span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Estado:</strong>
                    </div>
                    <div class="col-6">
                        @if($proveedor->activo)
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
                        <small class="text-muted">{{ $proveedor->created_at->format('d/m/Y') }}</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <strong>Actualizado:</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">{{ $proveedor->updated_at->format('d/m/Y') }}</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-boxes me-2"></i>
                    Artículos Asociados
                </h6>
            </div>
            <div class="card-body">
                @if($proveedor->articulos->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($proveedor->articulos->take(5) as $articulo)
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">{{ $articulo->nombre }}</h6>
                                        <small class="text-muted">Stock: {{ $articulo->stock_actual }}</small>
                                    </div>
                                    <span class="badge bg-primary">{{ $articulo->stock_actual }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($proveedor->articulos->count() > 5)
                        <div class="text-center mt-2">
                            <small class="text-muted">Y {{ $proveedor->articulos->count() - 5 }} más...</small>
                        </div>
                    @endif
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No hay artículos asociados</p>
                    </div>
                @endif
            </div>
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
    
    form.addEventListener('submit', function(e) {
        if (!nombreInput.value.trim()) {
            e.preventDefault();
            nombreInput.classList.add('is-invalid');
            nombreInput.focus();
            return false;
        }
    });
    
    // Limpiar errores al escribir
    nombreInput.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
        }
    });
});
</script>
@endsection