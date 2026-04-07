@extends('layouts.appAdmin')

@section('title', 'Editar Apartamento')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-edit me-2 text-warning"></i>
                        Editar Apartamento
                    </h1>
                    <p class="text-muted mb-0">Modifica la información del apartamento y sincroniza los cambios con Channex</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('apartamentos.admin.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <button id="formGuardar" class="btn btn-warning btn-lg">
                        <i class="fas fa-save me-2"></i>Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulario Principal -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-edit me-2 text-primary"></i>Formulario de Edición
                    </h5>
                </div>
                <div class="card-body">
                    <form id="form" action="{{ route('apartamentos.admin.update', $apartamento->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @if ($errors->any())
                            <div class="alert alert-danger border-0">
                                <h6 class="alert-heading fw-semibold">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Errores de Validación
                                </h6>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Información General -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3 fw-semibold">
                                    <i class="fas fa-info-circle me-2"></i>Información General
                                </h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="title" class="form-label fw-semibold">
                                        <i class="fas fa-tag me-1 text-primary"></i>Título <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('title') is-invalid @enderror" 
                                           id="title" 
                                           name="title" 
                                           value="{{ old('title', $apartamento->titulo) }}"
                                           placeholder="Título principal de la propiedad"
                                           required>
                                    @error('title')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="property_type" class="form-label fw-semibold">
                                        <i class="fas fa-home me-1 text-primary"></i>Tipo de Propiedad <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('property_type') is-invalid @enderror" id="property_type" name="property_type" required>
                                        <option value="">Selecciona el tipo de propiedad</option>
                                        <option value="apartment" {{ old('property_type', $apartamento->property_type) == 'apartment' ? 'selected' : '' }}>Apartamento</option>
                                        <option value="hotel" {{ old('property_type', $apartamento->property_type) == 'hotel' ? 'selected' : '' }}>Hotel</option>
                                        <option value="hostel" {{ old('property_type', $apartamento->property_type) == 'hostel' ? 'selected' : '' }}>Hostel</option>
                                        <option value="villa" {{ old('property_type', $apartamento->property_type) == 'villa' ? 'selected' : '' }}>Villa</option>
                                        <option value="guest_house" {{ old('property_type', $apartamento->property_type) == 'guest_house' ? 'selected' : '' }}>Casa de Huéspedes</option>
                                    </select>
                                    @error('property_type')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="edificio_id" class="form-label fw-semibold">
                                        <i class="fas fa-building me-1 text-primary"></i>Edificio <span class="text-danger">*</span>
                                    </label>
                                    <select name="edificio_id" id="edificio_id" class="form-select @error('edificio_id') is-invalid @enderror" required>
                                        <option value="">Selecciona un edificio</option>
                                        @if (count($edificios) > 0)
                                            @foreach ($edificios as $edificio)
                                                <option value="{{ $edificio->id }}" {{ old('edificio_id', $apartamento->edificio_id) == $edificio->id ? 'selected' : '' }}>
                                                    {{ $edificio->nombre }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('edificio_id')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="nombre" class="form-label fw-semibold">
                                        <i class="fas fa-home me-1 text-primary"></i>Nombre Interno
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('nombre') is-invalid @enderror" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="{{ old('nombre', $apartamento->nombre) }}"
                                           placeholder="Nombre interno del apartamento">
                                    @error('nombre')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="claves" class="form-label fw-semibold">
                                        <i class="fas fa-key me-1 text-primary"></i>Claves <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('claves') is-invalid @enderror"
                                           id="claves"
                                           name="claves"
                                           value="{{ old('claves', $apartamento->claves) }}"
                                           placeholder="Claves del apartamento"
                                           required>
                                    @error('claves')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Tipo de cerradura del apartamento -->
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-lock me-1 text-primary"></i>Tipo de Cerradura
                                    </label>
                                    @php $tipoCerradura = old('tipo_cerradura', $apartamento->tipo_cerradura ?? 'manual'); @endphp
                                    <div class="d-flex gap-2 flex-wrap">
                                        <div class="form-check form-check-inline border rounded px-3 py-2 {{ $tipoCerradura === 'manual' ? 'border-primary bg-primary bg-opacity-10' : '' }}" style="cursor:pointer;">
                                            <input class="form-check-input" type="radio" name="tipo_cerradura" id="apt_cerradura_manual" value="manual" {{ $tipoCerradura === 'manual' ? 'checked' : '' }} onchange="toggleCerraduraApt()">
                                            <label class="form-check-label" for="apt_cerradura_manual" style="cursor:pointer;"><i class="fas fa-key me-1"></i> Manual</label>
                                        </div>
                                        <div class="form-check form-check-inline border rounded px-3 py-2 {{ $tipoCerradura === 'ttlock' ? 'border-primary bg-primary bg-opacity-10' : '' }}" style="cursor:pointer;">
                                            <input class="form-check-input" type="radio" name="tipo_cerradura" id="apt_cerradura_ttlock" value="ttlock" {{ $tipoCerradura === 'ttlock' ? 'checked' : '' }} onchange="toggleCerraduraApt()">
                                            <label class="form-check-label" for="apt_cerradura_ttlock" style="cursor:pointer;"><i class="fas fa-lock me-1"></i> TTLock</label>
                                        </div>
                                        <div class="form-check form-check-inline border rounded px-3 py-2 {{ $tipoCerradura === 'tuya' ? 'border-primary bg-primary bg-opacity-10' : '' }}" style="cursor:pointer;">
                                            <input class="form-check-input" type="radio" name="tipo_cerradura" id="apt_cerradura_tuya" value="tuya" {{ $tipoCerradura === 'tuya' ? 'checked' : '' }} onchange="toggleCerraduraApt()">
                                            <label class="form-check-label" for="apt_cerradura_tuya" style="cursor:pointer;"><i class="fas fa-wifi me-1"></i> Tuya</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ID Lock en Tuyalaravel (visible solo para TTLock/Tuya) -->
                            <div class="col-md-6 mb-3" id="tuyalaravel-lock-section" style="{{ in_array($tipoCerradura, ['ttlock', 'tuya']) ? '' : 'display:none;' }}">
                                <div class="form-group">
                                    <label for="tuyalaravel_lock_id" class="form-label fw-semibold">
                                        <i class="fas fa-link me-1 text-primary"></i>ID Cerradura en App Cerraduras
                                    </label>
                                    <input type="number"
                                           class="form-control @error('tuyalaravel_lock_id') is-invalid @enderror"
                                           id="tuyalaravel_lock_id"
                                           name="tuyalaravel_lock_id"
                                           placeholder="ID del lock en Tuyalaravel"
                                           value="{{ old('tuyalaravel_lock_id', $apartamento->tuyalaravel_lock_id) }}">
                                    @error('tuyalaravel_lock_id')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                    <div class="form-text">ID del lock en la app gestora de cerraduras (Tuyalaravel)</div>
                                </div>
                            </div>

                            <!-- ttlock_lock_id legacy (hidden, se sincroniza con tuyalaravel_lock_id) -->
                            <input type="hidden" name="ttlock_lock_id" id="ttlock_lock_id" value="{{ old('ttlock_lock_id', $apartamento->ttlock_lock_id) }}">

                            <script>
                                function toggleCerraduraApt() {
                                    var tipo = document.querySelector('input[name="tipo_cerradura"]:checked').value;
                                    document.getElementById('tuyalaravel-lock-section').style.display = (tipo === 'ttlock' || tipo === 'tuya') ? '' : 'none';
                                    // Sync legacy field
                                    var tuyaId = document.getElementById('tuyalaravel_lock_id').value;
                                    document.getElementById('ttlock_lock_id').value = tuyaId;
                                    // Highlight
                                    document.querySelectorAll('input[name="tipo_cerradura"]').forEach(function(r) {
                                        var c = r.closest('.form-check');
                                        if (r.checked) c.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                                        else c.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
                                    });
                                }
                                // Sync on tuyalaravel_lock_id change
                                document.addEventListener('DOMContentLoaded', function() {
                                    var tuyaInput = document.getElementById('tuyalaravel_lock_id');
                                    if (tuyaInput) {
                                        tuyaInput.addEventListener('change', function() {
                                            document.getElementById('ttlock_lock_id').value = this.value;
                                        });
                                    }
                                });
                            </script>
                        </div>

                        <!-- Ubicación -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3 fw-semibold">
                                    <i class="fas fa-map-marker-alt me-2"></i>Ubicación
                                </h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="address" class="form-label fw-semibold">
                                        <i class="fas fa-home me-1 text-primary"></i>Dirección <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('address') is-invalid @enderror" 
                                           id="address" 
                                           name="address" 
                                           value="{{ old('address', $apartamento->address) }}"
                                           placeholder="Dirección completa del apartamento"
                                           required>
                                    @error('address')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="city" class="form-label fw-semibold">
                                        <i class="fas fa-city me-1 text-primary"></i>Ciudad <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('city') is-invalid @enderror" 
                                           id="city" 
                                           name="city" 
                                           value="{{ old('city', $apartamento->city) }}"
                                           placeholder="Ciudad del apartamento"
                                           required>
                                    @error('city')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="zip_code" class="form-label fw-semibold">
                                        <i class="fas fa-mail-bulk me-1 text-primary"></i>Código Postal
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('zip_code') is-invalid @enderror" 
                                           id="zip_code" 
                                           name="zip_code" 
                                           value="{{ old('zip_code', $apartamento->zip_code) }}"
                                           placeholder="Código postal">
                                    @error('zip_code')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="country" class="form-label fw-semibold">
                                        <i class="fas fa-globe me-1 text-primary"></i>País <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('country') is-invalid @enderror" 
                                           id="country" 
                                           name="country" 
                                           value="{{ old('country', $apartamento->country ?? 'Spain') }}"
                                           placeholder="País del apartamento"
                                           required>
                                    @error('country')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Información para Plataforma del Estado -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3 fw-semibold">
                                    <i class="fas fa-government me-2"></i>Plataforma del Estado
                                </h6>
                                <p class="text-muted small mb-3">Información requerida para la subida de viajeros a la plataforma del estado.</p>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="codigo_establecimiento" class="form-label fw-semibold">
                                        <i class="fas fa-id-card me-1 text-primary"></i>Código del Establecimiento
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('codigo_establecimiento') is-invalid @enderror" 
                                           id="codigo_establecimiento" 
                                           name="codigo_establecimiento" 
                                           value="{{ old('codigo_establecimiento', $apartamento->codigo_establecimiento) }}"
                                           placeholder="Código asignado por la administración">
                                    @error('codigo_establecimiento')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="pais_iso3" class="form-label fw-semibold">
                                        <i class="fas fa-flag me-1 text-primary"></i>País (ISO3)
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('pais_iso3') is-invalid @enderror" 
                                           id="pais_iso3" 
                                           name="pais_iso3" 
                                           value="{{ old('pais_iso3', $apartamento->pais_iso3 ?? 'ESP') }}"
                                           placeholder="ESP"
                                           maxlength="3">
                                    @error('pais_iso3')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="codigo_municipio_ine" class="form-label fw-semibold">
                                        <i class="fas fa-map-pin me-1 text-primary"></i>Código Municipio INE
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('codigo_municipio_ine') is-invalid @enderror" 
                                           id="codigo_municipio_ine" 
                                           name="codigo_municipio_ine" 
                                           value="{{ old('codigo_municipio_ine', $apartamento->codigo_municipio_ine) }}"
                                           placeholder="Código INE del municipio">
                                    @error('codigo_municipio_ine')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="nombre_municipio" class="form-label fw-semibold">
                                        <i class="fas fa-city me-1 text-primary"></i>Nombre del Municipio
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('nombre_municipio') is-invalid @enderror" 
                                           id="nombre_municipio" 
                                           name="nombre_municipio" 
                                           value="{{ old('nombre_municipio', $apartamento->nombre_municipio) }}"
                                           placeholder="Nombre del municipio">
                                    @error('nombre_municipio')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="tipo_establecimiento" class="form-label fw-semibold">
                                        <i class="fas fa-building me-1 text-primary"></i>Tipo de Establecimiento
                                    </label>
                                    <select class="form-select @error('tipo_establecimiento') is-invalid @enderror" 
                                            id="tipo_establecimiento" 
                                            name="tipo_establecimiento">
                                        <option value="">Seleccionar tipo (opcional)</option>
                                        <option value="hotel" {{ old('tipo_establecimiento', $apartamento->tipo_establecimiento) == 'hotel' ? 'selected' : '' }}>Hotel</option>
                                        <option value="apartamento" {{ old('tipo_establecimiento', $apartamento->tipo_establecimiento) == 'apartamento' ? 'selected' : '' }}>Apartamento</option>
                                        <option value="casa_rural" {{ old('tipo_establecimiento', $apartamento->tipo_establecimiento) == 'casa_rural' ? 'selected' : '' }}>Casa Rural</option>
                                        <option value="pension" {{ old('tipo_establecimiento', $apartamento->tipo_establecimiento) == 'pension' ? 'selected' : '' }}>Pensión</option>
                                        <option value="hostal" {{ old('tipo_establecimiento', $apartamento->tipo_establecimiento) == 'hostal' ? 'selected' : '' }}>Hostal</option>
                                    </select>
                                    @error('tipo_establecimiento')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Detalles del Apartamento -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3 fw-semibold">
                                    <i class="fas fa-bed me-2"></i>Detalles del Apartamento
                                </h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="bedrooms" class="form-label fw-semibold">
                                        <i class="fas fa-bed me-1 text-primary"></i>Habitaciones
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('bedrooms') is-invalid @enderror" 
                                           id="bedrooms" 
                                           name="bedrooms" 
                                           value="{{ old('bedrooms', $apartamento->bedrooms) }}"
                                           placeholder="Número de habitaciones"
                                           min="1">
                                    @error('bedrooms')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="bathrooms" class="form-label fw-semibold">
                                        <i class="fas fa-bath me-1 text-primary"></i>Baños
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('bathrooms') is-invalid @enderror" 
                                           id="bathrooms" 
                                           name="bathrooms" 
                                           value="{{ old('bathrooms', $apartamento->bathrooms) }}"
                                           placeholder="Número de baños"
                                           min="1"
                                           step="0.5">
                                    @error('bathrooms')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="max_guests" class="form-label fw-semibold">
                                        <i class="fas fa-users me-1 text-primary"></i>Huéspedes Máximos
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('max_guests') is-invalid @enderror" 
                                           id="max_guests" 
                                           name="max_guests" 
                                           value="{{ old('max_guests', $apartamento->max_guests) }}"
                                           placeholder="Número máximo de huéspedes"
                                           min="1">
                                    @error('max_guests')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="size" class="form-label fw-semibold">
                                        <i class="fas fa-ruler-combined me-1 text-primary"></i>Tamaño (m²)
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('size') is-invalid @enderror" 
                                           id="size" 
                                           name="size" 
                                           value="{{ old('size', $apartamento->size) }}"
                                           placeholder="Tamaño en metros cuadrados"
                                           min="1"
                                           step="0.1">
                                    @error('size')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3 fw-semibold">
                                    <i class="fas fa-align-left me-2"></i>Descripción
                                </h6>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="form-group">
                                    <label for="description" class="form-label fw-semibold">
                                        <i class="fas fa-align-left me-1 text-primary"></i>Descripción
                                    </label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="4"
                                              placeholder="Descripción detallada del apartamento">{{ old('description', $apartamento->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Campos Booking.com - Reglas y Políticas (VISIBLE PRIMERO) --}}
                        @include('admin.apartamentos.partials.booking-fields')
                        
                        <!-- Servicios del Apartamento -->
                        @include('admin.apartamentos.partials.servicios-select')
                        
                        <!-- Gestión de Fotos -->
                        <div class="mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-primary text-white py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-images me-2"></i>
                                        Galería de Fotos
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Zona de Drag and Drop -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-upload me-1 text-primary"></i>
                                            Subir Fotos (Múltiples)
                                        </label>
                                        
                                        <!-- Zona de arrastrar y soltar -->
                                        <div id="dropZone" 
                                             class="drop-zone border-2 border-dashed rounded p-4 text-center mb-3"
                                             style="border-color: #dee2e6; background-color: #f8f9fa; transition: all 0.3s ease; cursor: pointer; min-height: 150px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <div id="dropZoneContent">
                                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                                <p class="mb-2 fw-semibold">Arrastra y suelta las fotos aquí</p>
                                                <p class="text-muted small mb-3">o</p>
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('photosInput').click()">
                                                    <i class="fas fa-folder-open me-2"></i>Seleccionar archivos
                                                </button>
                                                <p class="text-muted small mt-3 mb-0">
                                                    Formatos: JPG, PNG, WEBP | Máx. 5MB por archivo
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- Input oculto -->
                                        <input type="file" 
                                               class="d-none" 
                                               id="photosInput" 
                                               name="photos[]" 
                                               accept="image/jpeg,image/jpg,image/png,image/webp"
                                               multiple>
                                        
                                        <!-- Vista previa de archivos seleccionados -->
                                        <div id="filesPreview" class="row g-2 mb-3" style="display: none;">
                                            <div class="col-12">
                                                <p class="mb-2 fw-semibold">
                                                    <i class="fas fa-images me-2"></i>Archivos seleccionados:
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- Botón de subir -->
                                        <div class="mt-2">
                                            <button type="button" 
                                                    class="btn btn-primary btn-sm" 
                                                    id="uploadBtn"
                                                    onclick="uploadPhotos()"
                                                    style="display: none;">
                                                <i class="fas fa-upload me-2"></i>Subir Fotos
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="photosContainer" class="row g-3">
                                        @if(isset($photos) && $photos->count() > 0)
                                            @foreach($photos as $photo)
                                                <div class="col-md-3 col-lg-2 photo-item" data-photo-id="{{ $photo->id }}">
                                                    <div class="card h-100">
                                                        <div class="card-img-wrapper position-relative">
                                                            @if($photo->path)
                                                                <img src="{{ asset('storage/' . $photo->path) }}" 
                                                                     class="card-img-top" 
                                                                     alt="Foto {{ $photo->id }}"
                                                                     style="height: 150px; object-fit: cover;">
                                                            @elseif($photo->url)
                                                                <img src="{{ $photo->url }}" 
                                                                     class="card-img-top" 
                                                                     alt="Foto {{ $photo->id }}"
                                                                     style="height: 150px; object-fit: cover;">
                                                            @else
                                                                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 150px;">
                                                                    <i class="fas fa-image text-muted"></i>
                                                                </div>
                                                            @endif
                                                            @if($photo->is_primary)
                                                                <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                                                    <i class="fas fa-star"></i> Principal
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="card-body p-2">
                                                            <div class="btn-group w-100" role="group">
                                                                @if(!$photo->is_primary)
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-primary" 
                                                                            onclick="setPrimaryPhoto({{ $photo->id }})"
                                                                            title="Marcar como principal">
                                                                        <i class="fas fa-star"></i>
                                                                    </button>
                                                                @endif
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-outline-danger" 
                                                                        onclick="deletePhoto({{ $photo->id }})"
                                                                        title="Eliminar">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="col-12">
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    No hay fotos subidas. Sube fotos para que aparezcan en la página pública.
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lugares Cercanos y FAQs -->
                        <div class="mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-info text-white py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        Contenido Público
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Lugares cercanos y FAQs:</strong> Gestiona los lugares cercanos y preguntas frecuentes que aparecerán en la página pública del apartamento.
                                    </div>
                                    
                                    <div class="d-flex gap-3 flex-wrap">
                                        <a href="{{ route('admin.lugares-cercanos.index', $apartamento->id) }}" 
                                           class="btn btn-outline-primary" 
                                           target="_blank">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            Gestionar Lugares Cercanos
                                            <span class="badge bg-primary ms-2">{{ $apartamento->lugaresCercanos()->count() }}</span>
                                        </a>
                                        
                                        <a href="{{ route('admin.faq-apartamentos.index', $apartamento->id) }}" 
                                           class="btn btn-outline-info" 
                                           target="_blank">
                                            <i class="fas fa-question-circle me-2"></i>
                                            Gestionar Preguntas Frecuentes
                                            <span class="badge bg-info ms-2">{{ $apartamento->faqs()->count() }}</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IDs Externos -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3 fw-semibold">
                                    <i class="fas fa-link me-2"></i>IDs Externos
                                </h6>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="id_booking" class="form-label fw-semibold">
                                        <i class="fas fa-key me-1 text-primary"></i>ID Booking
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('id_booking') is-invalid @enderror" 
                                           id="id_booking" 
                                           name="id_booking" 
                                           value="{{ old('id_booking', $apartamento->id_booking) }}"
                                           placeholder="ID de Booking.com">
                                    @error('id_booking')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="id_airbnb" class="form-label fw-semibold">
                                        <i class="fas fa-bed me-1 text-primary"></i>ID Airbnb
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('id_airbnb') is-invalid @enderror" 
                                           id="id_airbnb" 
                                           name="id_airbnb" 
                                           value="{{ old('id_airbnb', $apartamento->id_airbnb) }}"
                                           placeholder="ID de Airbnb">
                                    @error('id_airbnb')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="id_web" class="form-label fw-semibold">
                                        <i class="fas fa-globe me-1 text-primary"></i>ID Web
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('id_web') is-invalid @enderror" 
                                           id="id_web" 
                                           name="id_web" 
                                           value="{{ old('id_web', $apartamento->id_web) }}"
                                           placeholder="ID de la web propia">
                                    @error('id_web')
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center pt-3">
                                    <a href="{{ route('apartamentos.admin.index') }}" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-warning btn-lg">
                                        <i class="fas fa-save me-2"></i>Actualizar Apartamento
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Información Actual -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Información Actual
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">ID</label>
                        <div class="info-value">
                            <span class="badge bg-secondary fs-6">#{{ $apartamento->id }}</span>
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Título</label>
                        <div class="info-value">
                            {{ $apartamento->titulo ?? 'No especificado' }}
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Tipo</label>
                        <div class="info-value">
                            <span class="badge bg-primary">{{ $apartamento->property_type ?? 'No especificado' }}</span>
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Edificio</label>
                        <div class="info-value">
                            {{ $apartamento->edificio->nombre ?? 'No asignado' }}
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Creado</label>
                        <div class="info-value">
                            <i class="fas fa-calendar me-2 text-muted"></i>
                            {{ $apartamento->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <label class="form-label fw-semibold text-muted">Última Actualización</label>
                        <div class="info-value">
                            <i class="fas fa-clock me-2 text-muted"></i>
                            {{ $apartamento->updated_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado de Sincronización -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-sync me-2 text-primary"></i>Estado de Sincronización
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Channex ID</label>
                        <div class="info-value">
                            @if($apartamento->id_channex)
                                <span class="badge bg-success fs-6">{{ $apartamento->id_channex }}</span>
                                <small class="text-muted d-block mt-1">Sincronizado</small>
                            @else
                                <span class="badge bg-warning fs-6">No sincronizado</span>
                            @endif
                        </div>
                    </div>
                    
                    @if($apartamento->id_channex)
                        <div class="alert alert-info border-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>Los cambios se sincronizarán automáticamente con Channex</small>
                        </div>
                    @else
                        <div class="alert alert-warning border-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <small>Este apartamento no está sincronizado con Channex</small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-tools me-2 text-primary"></i>Acciones Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('apartamentos.admin.show', $apartamento->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-2"></i>Ver Detalles
                        </a>
                        
                        <a href="{{ route('apartamentos.admin.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>Lista de Apartamentos
                        </a>
                        
                        @if($apartamento->id_channex)
                            <button class="btn btn-outline-info" onclick="registrarWebhooks({{ $apartamento->id }})">
                                <i class="fas fa-sync me-2"></i>Registrar Webhooks
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Formularios */
.form-group {
    margin-bottom: 1rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
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

/* Botones */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.125rem;
}

/* Cards */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

/* Alertas */
.alert {
    border-radius: 8px;
}

/* Estilos para grupos de información */
.info-group {
    margin-bottom: 1rem;
}

.info-group label {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.info-value {
    font-size: 1rem;
    color: #495057;
}

/* Badges */
.badge {
    font-size: 0.75em;
    font-weight: 500;
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
    
    .col-lg-8, .col-lg-4 {
        margin-bottom: 1rem;
    }
    
    .col-md-6, .col-md-4 {
        margin-bottom: 1rem;
    }
}

/* Estilos para Drag and Drop */
.drop-zone {
    transition: all 0.3s ease;
}

.drop-zone:hover {
    border-color: #667eea !important;
    background-color: #e7f1ff !important;
}

.drop-zone.drag-over {
    border-color: #667eea !important;
    background-color: #e7f1ff !important;
    transform: scale(1.02);
}

#filesPreview .card {
    transition: transform 0.2s ease;
}

#filesPreview .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>
@endsection

@section('scriptHead')
<script>
    const apartamentoId = {{ $apartamento->id }};
    let selectedFiles = [];
    
    // Inicializar drag and drop
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('dropZone');
        const photosInput = document.getElementById('photosInput');
        const filesPreview = document.getElementById('filesPreview');
        const uploadBtn = document.getElementById('uploadBtn');
        
        if (!dropZone || !photosInput) return;
        
        // Prevenir comportamiento por defecto del navegador
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Efectos visuales al arrastrar
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.style.borderColor = '#667eea';
                dropZone.style.backgroundColor = '#e7f1ff';
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.style.borderColor = '#dee2e6';
                dropZone.style.backgroundColor = '#f8f9fa';
            }, false);
        });
        
        // Manejar archivos soltados
        dropZone.addEventListener('drop', handleDrop, false);
        dropZone.addEventListener('click', () => photosInput.click());
        
        // Manejar selección de archivos
        photosInput.addEventListener('change', handleFiles);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles({ target: { files } });
        }
        
        function handleFiles(e) {
            const files = Array.from(e.target.files || []);
            if (files.length === 0) return;
            
            // Validar archivos
            const validFiles = [];
            const invalidFiles = [];
            
            files.forEach(file => {
                const isValidType = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'].includes(file.type);
                const isValidSize = file.size <= 5 * 1024 * 1024; // 5MB
                
                if (isValidType && isValidSize) {
                    validFiles.push(file);
                } else {
                    invalidFiles.push({
                        name: file.name,
                        reason: !isValidType ? 'Formato no válido' : 'Archivo muy grande (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)'
                    });
                }
            });
            
            // Mostrar errores si hay archivos inválidos
            if (invalidFiles.length > 0) {
                const errorMessages = invalidFiles.map(f => `${f.name}: ${f.reason}`).join('<br>');
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivos inválidos',
                    html: `Los siguientes archivos no se pueden subir:<br>${errorMessages}`
                });
            }
            
            if (validFiles.length === 0) return;
            
            // Actualizar archivos seleccionados
            selectedFiles = validFiles;
            
            // Actualizar el input
            const dataTransfer = new DataTransfer();
            validFiles.forEach(file => dataTransfer.items.add(file));
            photosInput.files = dataTransfer.files;
            
            // Mostrar vista previa
            showFilesPreview(validFiles);
            
            // Mostrar botón de subir
            uploadBtn.style.display = 'inline-block';
        }
        
        function showFilesPreview(files) {
            filesPreview.innerHTML = '<div class="col-12"><p class="mb-2 fw-semibold"><i class="fas fa-images me-2"></i>Archivos seleccionados:</p></div>';
            filesPreview.style.display = 'block';
            
            files.forEach((file, index) => {
                const col = document.createElement('div');
                col.className = 'col-md-3 col-sm-4 col-6';
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    col.innerHTML = `
                        <div class="card position-relative">
                            <img src="${e.target.result}" class="card-img-top" style="height: 100px; object-fit: cover;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="removeFile(${index})" style="opacity: 0.9;">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="card-body p-2">
                                <small class="text-muted d-block text-truncate" title="${file.name}">${file.name}</small>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                            </div>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
                filesPreview.appendChild(col);
            });
        }
        
        // Función global para remover archivo
        window.removeFile = function(index) {
            selectedFiles.splice(index, 1);
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            photosInput.files = dataTransfer.files;
            
            if (selectedFiles.length === 0) {
                filesPreview.style.display = 'none';
                uploadBtn.style.display = 'none';
            } else {
                showFilesPreview(selectedFiles);
            }
        };
    });
    
    // Subir fotos
    function uploadPhotos() {
        const input = document.getElementById('photosInput');
        if (!input) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el input de fotos'
            });
            return;
        }
        
        const files = input.files;
        
        if (!files || files.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin fotos',
                text: 'Por favor selecciona al menos una foto para subir.'
            });
            return;
        }
        
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('photos[]', files[i]);
        }
        
        Swal.fire({
            title: 'Subiendo fotos...',
            text: `Subiendo ${files.length} foto(s), por favor espera`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch(`/apartamentos/${apartamentoId}/photos/upload`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error('El servidor devolvió una respuesta no válida. Por favor, recarga la página e intenta de nuevo.');
            }
            
            const data = await response.json();
            
            if (!response.ok) {
                // Manejar errores de validación
                if (data.errors) {
                    const errorMessages = Object.values(data.errors).flat().join('<br>');
                    throw new Error(errorMessages);
                }
                throw new Error(data.message || 'Error al subir las fotos');
            }
            
            return data;
        })
        .then(data => {
            Swal.close();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message || 'Fotos subidas correctamente',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al subir las fotos'
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: error.message || 'Error al subir las fotos'
            });
        });
    }
    
    // Eliminar foto
    function deletePhoto(photoId) {
        Swal.fire({
            title: '¿Eliminar foto?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/apartamentos/${apartamentoId}/photos/${photoId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminada!',
                            text: 'La foto ha sido eliminada'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al eliminar la foto'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al eliminar la foto: ' + error.message
                    });
                });
            }
        });
    }
    
    // Establecer foto principal
    function setPrimaryPhoto(photoId) {
        fetch(`/apartamentos/${apartamentoId}/photos/${photoId}/primary`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Actualizado!',
                    text: 'Foto principal actualizada',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al actualizar la foto principal'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error: ' + error.message
            });
        });
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.getElementById('form');
    const formGuardar = document.getElementById('formGuardar');
    
    formGuardar.addEventListener('click', function() {
        if (form.checkValidity()) {
            form.submit();
        } else {
            form.reportValidity();
        }
    });
    
    // Auto-completar país si está vacío
    const countryField = document.getElementById('country');
    if (!countryField.value) {
        countryField.value = 'Spain';
    }
});

// Función para registrar webhooks
function registrarWebhooks(apartamentoId) {
    Swal.fire({
        title: 'Registrando Webhooks',
        text: 'Por favor espera mientras se registran los webhooks...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/apartamentos/admin/${apartamentoId}/webhooks`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        let successCount = 0;
        let errorCount = 0;
        
        data.forEach(item => {
            if (item.status === 'success') successCount++;
            else errorCount++;
        });

        Swal.fire({
            title: 'Webhooks Registrados',
            html: `
                <div class="text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success fa-2x"></i>
                    </div>
                    <p><strong>${successCount}</strong> webhooks registrados exitosamente</p>
                    ${errorCount > 0 ? `<p class="text-warning"><strong>${errorCount}</strong> webhooks con errores</p>` : ''}
                </div>
            `,
            icon: 'success',
            confirmButtonText: 'Aceptar'
        });
    })
    .catch(error => {
        Swal.fire({
            title: 'Error',
            text: 'Error al registrar los webhooks: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    });
}
</script>
@endsection
