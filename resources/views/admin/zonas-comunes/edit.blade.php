@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-edit text-primary me-2"></i>
            Editar Zona Común
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.zonas-comunes.index') }}">Zonas Comunes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
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

<!-- Formulario Principal -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-building text-primary me-2"></i>
            Información de la Zona Común
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.zonas-comunes.update', $zonaComun->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <!-- Nombre -->
                <div class="col-md-6">
                    <label for="nombre" class="form-label fw-semibold">
                        <i class="fas fa-tag text-primary me-1"></i>
                        Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('nombre') ? 'is-invalid' : '' }}" 
                           id="nombre" 
                           name="nombre" 
                           value="{{ old('nombre', $zonaComun->nombre) }}" 
                           placeholder="Ej: Recepción, Piscina, Gimnasio..."
                           required>
                    @error('nombre')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Tipo -->
                <div class="col-md-6">
                    <label for="tipo" class="form-label fw-semibold">
                        <i class="fas fa-cog text-primary me-1"></i>
                        Tipo <span class="text-danger">*</span>
                    </label>
                    <select class="form-select form-select-lg {{ $errors->has('tipo') ? 'is-invalid' : '' }}" 
                            id="tipo" 
                            name="tipo" 
                            required>
                        <option value="">Seleccionar tipo...</option>
                        <option value="zona_comun" {{ old('tipo', $zonaComun->tipo) == 'zona_comun' ? 'selected' : '' }}>
                            Zona Común
                        </option>
                        <option value="area_servicio" {{ old('tipo', $zonaComun->tipo) == 'area_servicio' ? 'selected' : '' }}>
                            Área de Servicio
                        </option>
                        <option value="recepcion" {{ old('tipo', $zonaComun->tipo) == 'recepcion' ? 'selected' : '' }}>
                            Recepción
                        </option>
                        <option value="piscina" {{ old('tipo', $zonaComun->tipo) == 'piscina' ? 'selected' : '' }}>
                            Piscina
                        </option>
                        <option value="gimnasio" {{ old('tipo', $zonaComun->tipo) == 'gimnasio' ? 'selected' : '' }}>
                            Gimnasio
                        </option>
                        <option value="terraza" {{ old('tipo', $zonaComun->tipo) == 'terraza' ? 'selected' : '' }}>
                            Terraza
                        </option>
                    </select>
                    @error('tipo')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Ubicación -->
                <div class="col-md-6">
                    <label for="ubicacion" class="form-label fw-semibold">
                        <i class="fas fa-map-marker-alt text-primary me-1"></i>
                        Ubicación
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('ubicacion') ? 'is-invalid' : '' }}" 
                           id="ubicacion" 
                           name="ubicacion" 
                           value="{{ old('ubicacion', $zonaComun->ubicacion) }}" 
                           placeholder="Ej: Planta baja, Primer piso...">
                    @error('ubicacion')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>

                <!-- Orden -->
                <div class="col-md-6">
                    <label for="orden" class="form-label fw-semibold">
                        <i class="fas fa-sort-numeric-up text-primary me-1"></i>
                        Orden
                    </label>
                    <input type="number" 
                           class="form-control form-control-lg {{ $errors->has('orden') ? 'is-invalid' : '' }}" 
                           id="orden" 
                           name="orden" 
                           value="{{ old('orden', $zonaComun->orden ?? 0) }}" 
                           min="0"
                           placeholder="0">
                    @error('orden')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                    <small class="form-text text-muted">
                        Número para ordenar las zonas comunes (0 = sin orden específico)
                    </small>
                </div>

                <!-- Descripción -->
                <div class="col-12">
                    <label for="descripcion" class="form-label fw-semibold">
                        <i class="fas fa-align-left text-primary me-1"></i>
                        Descripción
                    </label>
                    <textarea class="form-control {{ $errors->has('descripcion') ? 'is-invalid' : '' }}" 
                              id="descripcion" 
                              name="descripcion" 
                              rows="4" 
                              placeholder="Descripción detallada de la zona común...">{{ old('descripcion', $zonaComun->descripcion) }}</textarea>
                    @error('descripcion')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.zonas-comunes.index') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver al Listado
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Zona Común
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-resize textarea
        const textarea = document.getElementById('descripcion');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const nombre = document.getElementById('nombre').value.trim();
                const tipo = document.getElementById('tipo').value;

                if (!nombre) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error de Validación',
                        text: 'Por favor, ingresa el nombre de la zona común.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    document.getElementById('nombre').focus();
                    return false;
                }

                if (!tipo) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error de Validación',
                        text: 'Por favor, selecciona el tipo de zona común.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    document.getElementById('tipo').focus();
                    return false;
                }
            });
        }
    });
</script>

<style>
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px 10px 0 0 !important;
    border: none;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: #6c757d;
    border: none;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}
</style>
@endsection
