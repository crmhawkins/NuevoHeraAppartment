@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-edit text-primary me-2"></i>
            Editar Huésped: <span class="text-primary">{{ $huesped->nombre }} {{ $huesped->primer_apellido }}</span>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reservas.index') }}">Reservas</a></li>
                <li class="breadcrumb-item"><a href="{{ route('huespedes.show', $huesped->id) }}">Huésped</a></li>
                <li class="breadcrumb-item active" aria-current="page">Editar</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Session Alerts -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
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
            <i class="fas fa-user-edit text-primary me-2"></i>
            Información del Huésped
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('huespedes.update', $huesped->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row g-3">
                <!-- Información Personal -->
                <div class="col-12">
                    <h6 class="fw-semibold text-primary mb-3">
                        <i class="fas fa-user-circle me-2"></i>
                        Información Personal
                    </h6>
                </div>
                
                <!-- Nombre -->
                <div class="col-md-4">
                    <label for="nombre" class="form-label fw-semibold">
                        <i class="fas fa-user text-primary me-1"></i>
                        Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('nombre') ? 'is-invalid' : '' }}" 
                           id="nombre" 
                           name="nombre" 
                           value="{{ old('nombre', $huesped->nombre) }}" 
                           placeholder="Nombre del huésped"
                           required>
                    @error('nombre')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Primer Apellido -->
                <div class="col-md-4">
                    <label for="primer_apellido" class="form-label fw-semibold">
                        <i class="fas fa-user text-primary me-1"></i>
                        Primer Apellido <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('primer_apellido') ? 'is-invalid' : '' }}" 
                           id="primer_apellido" 
                           name="primer_apellido" 
                           value="{{ old('primer_apellido', $huesped->primer_apellido) }}" 
                           placeholder="Primer apellido"
                           required>
                    @error('primer_apellido')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Segundo Apellido -->
                <div class="col-md-4">
                    <label for="segundo_apellido" class="form-label fw-semibold">
                        <i class="fas fa-user text-primary me-1"></i>
                        Segundo Apellido
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('segundo_apellido') ? 'is-invalid' : '' }}" 
                           id="segundo_apellido" 
                           name="segundo_apellido" 
                           value="{{ old('segundo_apellido', $huesped->segundo_apellido) }}" 
                           placeholder="Segundo apellido">
                    @error('segundo_apellido')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Fecha de Nacimiento -->
                <div class="col-md-4">
                    <label for="fecha_nacimiento" class="form-label fw-semibold">
                        <i class="fas fa-birthday-cake text-primary me-1"></i>
                        Fecha de Nacimiento
                    </label>
                    <input type="date" 
                           class="form-control form-control-lg {{ $errors->has('fecha_nacimiento') ? 'is-invalid' : '' }}" 
                           id="fecha_nacimiento" 
                           name="fecha_nacimiento" 
                           value="{{ old('fecha_nacimiento', $huesped->fecha_nacimiento) }}">
                    @error('fecha_nacimiento')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Sexo -->
                <div class="col-md-4">
                    <label for="sexo" class="form-label fw-semibold">
                        <i class="fas fa-venus-mars text-primary me-1"></i>
                        Sexo
                    </label>
                    <select class="form-select form-select-lg {{ $errors->has('sexo') ? 'is-invalid' : '' }}" 
                            id="sexo" 
                            name="sexo">
                        <option value="">Seleccionar sexo...</option>
                        <option value="M" {{ old('sexo', $huesped->sexo) == 'M' ? 'selected' : '' }}>Masculino</option>
                        <option value="F" {{ old('sexo', $huesped->sexo) == 'F' ? 'selected' : '' }}>Femenino</option>
                    </select>
                    @error('sexo')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Nacionalidad -->
                <div class="col-md-4">
                    <label for="nacionalidad" class="form-label fw-semibold">
                        <i class="fas fa-flag text-primary me-1"></i>
                        Nacionalidad
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('nacionalidad') ? 'is-invalid' : '' }}" 
                           id="nacionalidad" 
                           name="nacionalidad" 
                           value="{{ old('nacionalidad', $huesped->nacionalidadStr ?? $huesped->nacionalidad) }}" 
                           placeholder="Nacionalidad">
                    @error('nacionalidad')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Documento de Identidad -->
                <div class="col-12 mt-4">
                    <h6 class="fw-semibold text-primary mb-3">
                        <i class="fas fa-id-card me-2"></i>
                        Documento de Identidad
                    </h6>
                </div>
                
                <!-- Tipo de Documento -->
                <div class="col-md-4">
                    <label for="tipo_documento" class="form-label fw-semibold">
                        <i class="fas fa-file-alt text-primary me-1"></i>
                        Tipo de Documento
                    </label>
                    <select class="form-select form-select-lg {{ $errors->has('tipo_documento') ? 'is-invalid' : '' }}" 
                            id="tipo_documento" 
                            name="tipo_documento">
                        <option value="">Seleccionar tipo...</option>
                        <option value="1" {{ old('tipo_documento', $huesped->tipo_documento) == '1' ? 'selected' : '' }}>DNI</option>
                        <option value="2" {{ old('tipo_documento', $huesped->tipo_documento) == '2' ? 'selected' : '' }}>Pasaporte</option>
                    </select>
                    @error('tipo_documento')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Número de Identificación -->
                <div class="col-md-4">
                    <label for="numero_identificacion" class="form-label fw-semibold">
                        <i class="fas fa-hashtag text-primary me-1"></i>
                        Número de Identificación
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('numero_identificacion') ? 'is-invalid' : '' }}" 
                           id="numero_identificacion" 
                           name="numero_identificacion" 
                           value="{{ old('numero_identificacion', $huesped->numero_identificacion) }}" 
                           placeholder="Número de documento">
                    @error('numero_identificacion')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Fecha de Expedición -->
                <div class="col-md-4">
                    <label for="fecha_expedicion" class="form-label fw-semibold">
                        <i class="fas fa-calendar text-primary me-1"></i>
                        Fecha de Expedición
                    </label>
                    <input type="date" 
                           class="form-control form-control-lg {{ $errors->has('fecha_expedicion') ? 'is-invalid' : '' }}" 
                           id="fecha_expedicion" 
                           name="fecha_expedicion" 
                           value="{{ old('fecha_expedicion', $huesped->fecha_expedicion) }}">
                    @error('fecha_expedicion')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Información de Contacto -->
                <div class="col-12 mt-4">
                    <h6 class="fw-semibold text-primary mb-3">
                        <i class="fas fa-address-book me-2"></i>
                        Información de Contacto
                    </h6>
                </div>
                
                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">
                        <i class="fas fa-envelope text-primary me-1"></i>
                        Email
                    </label>
                    <input type="email" 
                           class="form-control form-control-lg {{ $errors->has('email') ? 'is-invalid' : '' }}" 
                           id="email" 
                           name="email" 
                           value="{{ old('email', $huesped->email) }}" 
                           placeholder="correo@ejemplo.com">
                    @error('email')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Teléfono Móvil -->
                <div class="col-md-6">
                    <label for="telefono_movil" class="form-label fw-semibold">
                        <i class="fas fa-phone text-primary me-1"></i>
                        Teléfono Móvil
                    </label>
                    <input type="tel" 
                           class="form-control form-control-lg {{ $errors->has('telefono_movil') ? 'is-invalid' : '' }}" 
                           id="telefono_movil" 
                           name="telefono_movil" 
                           value="{{ old('telefono_movil', $huesped->telefono_movil) }}" 
                           placeholder="+34 123 456 789">
                    @error('telefono_movil')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Dirección -->
                <div class="col-md-6">
                    <label for="direccion" class="form-label fw-semibold">
                        <i class="fas fa-map-marker-alt text-primary me-1"></i>
                        Dirección
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('direccion') ? 'is-invalid' : '' }}" 
                           id="direccion" 
                           name="direccion" 
                           value="{{ old('direccion', $huesped->direccion) }}" 
                           placeholder="Dirección completa">
                    @error('direccion')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Localidad -->
                <div class="col-md-6">
                    <label for="localidad" class="form-label fw-semibold">
                        <i class="fas fa-city text-primary me-1"></i>
                        Localidad
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg {{ $errors->has('localidad') ? 'is-invalid' : '' }}" 
                           id="localidad" 
                           name="localidad" 
                           value="{{ old('localidad', $huesped->localidad) }}" 
                           placeholder="Ciudad o localidad">
                    @error('localidad')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                </div>
                
                <!-- Subida de Documentos -->
                <div class="col-12 mt-4">
                    <h6 class="fw-semibold text-primary mb-3">
                        <i class="fas fa-images me-2"></i>
                        Documentos de Identidad
                    </h6>
                </div>
                
                <!-- Fotos Actuales -->
                @if(isset($photos) && count($photos) > 0)
                <div class="col-12">
                    <div class="alert alert-info">
                        <h6 class="fw-semibold mb-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Documentos Actuales
                        </h6>
                        <div class="row g-2">
                            @foreach($photos as $photo)
                            <div class="col-md-3">
                                <div class="text-center">
                                    <img src="{{ asset('storage/' . $photo->url) }}" 
                                         alt="{{ $photo->categoria->nombre ?? 'Documento' }}" 
                                         class="img-fluid rounded border"
                                         style="max-height: 100px; width: 100%; object-fit: cover;">
                                    <small class="text-muted d-block mt-1">
                                        @if($photo->photo_categoria_id == 13)
                                            DNI - Frente
                                        @elseif($photo->photo_categoria_id == 14)
                                            DNI - Reverso
                                        @elseif($photo->photo_categoria_id == 15)
                                            Pasaporte
                                        @else
                                            {{ $photo->categoria->nombre ?? 'Documento' }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Subida de Nuevas Fotos -->
                <div class="col-md-6">
                    <label for="foto_dni_frente" class="form-label fw-semibold">
                        <i class="fas fa-camera text-primary me-1"></i>
                        {{ $huesped->tipo_documento == 1 ? 'DNI - Frente' : 'Documento' }}
                    </label>
                    <input type="file" 
                           class="form-control form-control-lg {{ $errors->has('foto_dni_frente') ? 'is-invalid' : '' }}" 
                           id="foto_dni_frente" 
                           name="foto_dni_frente" 
                           accept="image/*">
                    @error('foto_dni_frente')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                    <small class="form-text text-muted">
                        Sube una foto clara del documento (máximo 5MB)
                    </small>
                </div>
                
                @if($huesped->tipo_documento == 1)
                <div class="col-md-6">
                    <label for="foto_dni_reverso" class="form-label fw-semibold">
                        <i class="fas fa-camera text-primary me-1"></i>
                        DNI - Reverso
                    </label>
                    <input type="file" 
                           class="form-control form-control-lg {{ $errors->has('foto_dni_reverso') ? 'is-invalid' : '' }}" 
                           id="foto_dni_reverso" 
                           name="foto_dni_reverso" 
                           accept="image/*">
                    @error('foto_dni_reverso')
                        <div class="alert alert-danger alert-dismissible fade show mt-2">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @enderror
                    <small class="form-text text-muted">
                        Sube una foto clara del reverso del DNI (máximo 5MB)
                    </small>
                </div>
                @endif
            </div>
            
            <!-- Botones de Acción -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('huespedes.show', $huesped->id) }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Huésped
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
        // Preview de imágenes
        const fotoInputs = document.querySelectorAll('input[type="file"]');
        fotoInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validar tamaño (5MB máximo)
                    if (file.size > 5 * 1024 * 1024) {
                        Swal.fire({
                            title: 'Archivo demasiado grande',
                            text: 'El archivo no puede superar los 5MB.',
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                        this.value = '';
                        return;
                    }
                    
                    // Validar tipo de archivo
                    if (!file.type.startsWith('image/')) {
                        Swal.fire({
                            title: 'Tipo de archivo no válido',
                            text: 'Solo se permiten archivos de imagen.',
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                        this.value = '';
                        return;
                    }
                    
                    // Mostrar preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.createElement('div');
                        preview.className = 'mt-2 text-center';
                        preview.innerHTML = `
                            <img src="${e.target.result}" 
                                 alt="Preview" 
                                 class="img-fluid rounded border" 
                                 style="max-height: 150px; max-width: 200px;">
                            <small class="text-muted d-block mt-1">Vista previa</small>
                        `;
                        
                        // Remover preview anterior si existe
                        const existingPreview = input.parentNode.querySelector('.preview');
                        if (existingPreview) {
                            existingPreview.remove();
                        }
                        
                        preview.className += ' preview';
                        input.parentNode.appendChild(preview);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
        
        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const nombre = document.getElementById('nombre').value.trim();
                const primerApellido = document.getElementById('primer_apellido').value.trim();
                
                if (!nombre) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error de Validación',
                        text: 'Por favor, ingresa el nombre del huésped.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    document.getElementById('nombre').focus();
                    return false;
                }
                
                if (!primerApellido) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error de Validación',
                        text: 'Por favor, ingresa el primer apellido del huésped.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    document.getElementById('primer_apellido').focus();
                    return false;
                }
            });
        }
    });
</script>
@endsection
