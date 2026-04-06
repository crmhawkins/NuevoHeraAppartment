@extends('layouts.appAdmin')

@section('title', 'Crear Apartamento')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-plus-circle me-2 text-success"></i>
                        Crear Nuevo Apartamento
                    </h1>
                    <p class="text-muted mb-0">Completa la información del apartamento para registrarlo en Channex</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('apartamentos.admin.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <button id="formGuardar" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-edit me-2 text-primary"></i>Información del Apartamento
            </h5>
        </div>
        <div class="card-body">
            <form id="form" action="{{ route('apartamentos.admin.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

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
                                   value="{{ old('title') }}"
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
                                <option value="apartment" {{ old('property_type') == 'apartment' ? 'selected' : '' }}>Apartamento</option>
                                <option value="hotel" {{ old('property_type') == 'hotel' ? 'selected' : '' }}>Hotel</option>
                                <option value="hostel" {{ old('property_type') == 'hostel' ? 'selected' : '' }}>Hostel</option>
                                <option value="villa" {{ old('property_type') == 'villa' ? 'selected' : '' }}>Villa</option>
                                <option value="guest_house" {{ old('property_type') == 'guest_house' ? 'selected' : '' }}>Casa de Huéspedes</option>
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
                                        <option value="{{ $edificio->id }}" {{ old('edificio_id') == $edificio->id ? 'selected' : '' }}>
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
                                   value="{{ old('nombre') }}"
                                   placeholder="Nombre interno del apartamento">
                            @error('nombre')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
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
                                   value="{{ old('address') }}"
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
                                   value="{{ old('city') }}"
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
                            <label for="postal_code" class="form-label fw-semibold">
                                <i class="fas fa-mail-bulk me-1 text-primary"></i>Código Postal
                            </label>
                            <input type="text" 
                                   class="form-control @error('postal_code') is-invalid @enderror" 
                                   id="postal_code" 
                                   name="postal_code" 
                                   value="{{ old('postal_code') }}"
                                   placeholder="Código postal">
                            @error('postal_code')
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
                                   value="{{ old('country', 'Spain') }}"
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
                                   value="{{ old('codigo_establecimiento') }}"
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
                                   value="{{ old('pais_iso3', 'ESP') }}"
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
                                   value="{{ old('codigo_municipio_ine') }}"
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
                                   value="{{ old('nombre_municipio') }}"
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
                                <option value="hotel" {{ old('tipo_establecimiento') == 'hotel' ? 'selected' : '' }}>Hotel</option>
                                <option value="apartamento" {{ old('tipo_establecimiento') == 'apartamento' ? 'selected' : '' }}>Apartamento</option>
                                <option value="casa_rural" {{ old('tipo_establecimiento') == 'casa_rural' ? 'selected' : '' }}>Casa Rural</option>
                                <option value="pension" {{ old('tipo_establecimiento') == 'pension' ? 'selected' : '' }}>Pensión</option>
                                <option value="hostal" {{ old('tipo_establecimiento') == 'hostal' ? 'selected' : '' }}>Hostal</option>
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
                                <i class="fas fa-bed me-1 text-primary"></i>Habitaciones <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control @error('bedrooms') is-invalid @enderror" 
                                   id="bedrooms" 
                                   name="bedrooms" 
                                   value="{{ old('bedrooms') }}"
                                   placeholder="Número de habitaciones"
                                   min="1"
                                   required>
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
                                <i class="fas fa-bath me-1 text-primary"></i>Baños <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control @error('bathrooms') is-invalid @enderror" 
                                   id="bathrooms" 
                                   name="bathrooms" 
                                   value="{{ old('bathrooms') }}"
                                   placeholder="Número de baños"
                                   min="1"
                                   step="0.5"
                                   required>
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
                                <i class="fas fa-users me-1 text-primary"></i>Huéspedes Máximos <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control @error('max_guests') is-invalid @enderror" 
                                   id="max_guests" 
                                   name="max_guests" 
                                   value="{{ old('max_guests') }}"
                                   placeholder="Número máximo de huéspedes"
                                   min="1"
                                   required>
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
                                   value="{{ old('size') }}"
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
                                <i class="fas fa-align-left me-1 text-primary"></i>Descripción <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4"
                                      placeholder="Descripción detallada del apartamento"
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                </div>
                            @enderror
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
                                   value="{{ old('id_booking') }}"
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
                                   value="{{ old('id_airbnb') }}"
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
                                   value="{{ old('id_web') }}"
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
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Guardar Apartamento
                            </button>
                        </div>
                    </div>
                </div>
                        {{-- Campos Booking.com --}}
                        @php
                            $apartamento = new \App\Models\Apartamento(); // Crear instancia vacía para el partial
                        @endphp
                        @include('admin.apartamentos.partials.booking-fields')
                        
                        <!-- Servicios del Apartamento -->
                        @php $serviciosSeleccionados = []; @endphp
                        @include('admin.apartamentos.partials.servicios-select')
                        
                        <!-- Gestión de Fotos (Opcional en creación) -->
                        <div class="mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-primary text-white py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-images me-2"></i>
                                        Galería de Fotos (Opcional)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Nota:</strong> Puedes subir fotos ahora o hacerlo después de crear el apartamento desde la página de edición.
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-upload me-1 text-primary"></i>
                                            Subir Fotos (Múltiples)
                                        </label>
                                        <input type="file" 
                                               class="form-control" 
                                               id="photosInput" 
                                               name="photos[]" 
                                               accept="image/jpeg,image/jpg,image/png,image/webp"
                                               multiple>
                                        <small class="form-text text-muted">
                                            Selecciona una o más fotos (máx. 5MB cada una). Formatos: JPG, PNG, WEBP.
                                            <br>
                                            <strong>Las fotos se subirán automáticamente al crear el apartamento.</strong>
                                        </small>
                                        <div class="mt-2">
                                            <div id="photosPreview" class="row g-2 mt-2"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <small>Después de crear el apartamento, podrás gestionar las fotos (marcar principal, eliminar, reordenar) desde la página de edición.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Campos Booking.com --}}
                        @php
                            $apartamento = new \App\Models\Apartamento(); // Crear instancia vacía para el partial
                        @endphp
                        @include('admin.apartamentos.partials.booking-fields')
                        
                        <!-- Servicios del Apartamento -->
                        @php $serviciosSeleccionados = []; @endphp
                        @include('admin.apartamentos.partials.servicios-select')
                    </form>
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

/* Responsive */
@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between .btn {
        width: 100%;
    }
    
    .col-md-6, .col-md-4 {
        margin-bottom: 1rem;
    }
}
</style>
@endsection

@section('scriptHead')
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
    
    // Preview de fotos antes de subir
    const photosInput = document.getElementById('photosInput');
    const photosPreview = document.getElementById('photosPreview');
    
    if (photosInput && photosPreview) {
        photosInput.addEventListener('change', function(e) {
            photosPreview.innerHTML = '';
            
            if (this.files.length > 0) {
                Array.from(this.files).forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const col = document.createElement('div');
                            col.className = 'col-md-3 col-lg-2';
                            col.innerHTML = `
                                <div class="card">
                                    <img src="${e.target.result}" 
                                         class="card-img-top" 
                                         alt="Preview ${index + 1}"
                                         style="height: 100px; object-fit: cover;">
                                    <div class="card-body p-2">
                                        <small class="text-muted d-block text-truncate" title="${file.name}">
                                            ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                                        </small>
                                        <small class="text-muted">
                                            ${(file.size / 1024 / 1024).toFixed(2)} MB
                                        </small>
                                    </div>
                                </div>
                            `;
                            photosPreview.appendChild(col);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    }
});
</script>
@endsection
