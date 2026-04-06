@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-plus me-2 text-primary"></i>
                Nuevo Lugar Cercano
            </h1>
            <p class="text-muted mb-0">Añade un lugar cercano para {{ $apartamento->titulo ?? $apartamento->nombre }}</p>
        </div>
        <a href="{{ route('admin.lugares-cercanos.index', $apartamento->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <hr class="mb-4">

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('admin.lugares-cercanos.store', $apartamento->id) }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="nombre" class="form-label fw-semibold">
                                <i class="fas fa-tag me-1 text-primary"></i>
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="{{ old('nombre') }}"
                                   placeholder="Ej: Parque Natural de Los Alcornocales, Restaurante La Buganvilla, Playa del Rinconcillo"
                                   required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="categoria" class="form-label fw-semibold">
                                <i class="fas fa-folder me-1 text-primary"></i>
                                Categoría <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('categoria') is-invalid @enderror" 
                                    id="categoria" 
                                    name="categoria" 
                                    required>
                                <option value="">Selecciona una categoría</option>
                                <option value="que_hay_cerca" {{ old('categoria') == 'que_hay_cerca' ? 'selected' : '' }}>¿Qué hay cerca?</option>
                                <option value="restaurantes" {{ old('categoria') == 'restaurantes' ? 'selected' : '' }}>Restaurantes y cafeterías</option>
                                <option value="transporte" {{ old('categoria') == 'transporte' ? 'selected' : '' }}>Transporte público</option>
                                <option value="playas" {{ old('categoria') == 'playas' ? 'selected' : '' }}>Playas en la zona</option>
                                <option value="aeropuertos" {{ old('categoria') == 'aeropuertos' ? 'selected' : '' }}>Aeropuertos más cercanos</option>
                            </select>
                            @error('categoria')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="tipo" class="form-label fw-semibold">
                                <i class="fas fa-info-circle me-1 text-primary"></i>
                                Tipo
                            </label>
                            <input type="text" 
                                   class="form-control @error('tipo') is-invalid @enderror" 
                                   id="tipo" 
                                   name="tipo" 
                                   value="{{ old('tipo') }}"
                                   placeholder="Ej: Restaurante, Tren, Parque, Playa, Aeropuerto"
                                   list="tiposList">
                            <datalist id="tiposList">
                                <option value="Restaurante">
                                <option value="Tren">
                                <option value="Parque">
                                <option value="Playa">
                                <option value="Aeropuerto">
                                <option value="Helipuerto">
                                <option value="Autobús">
                                <option value="Metro">
                            </datalist>
                            <small class="form-text text-muted">
                                Tipo específico del lugar (opcional, aparece antes del nombre).
                            </small>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="distancia" class="form-label fw-semibold">
                                    <i class="fas fa-ruler me-1 text-primary"></i>
                                    Distancia
                                </label>
                                <input type="number" 
                                       class="form-control @error('distancia') is-invalid @enderror" 
                                       id="distancia" 
                                       name="distancia" 
                                       value="{{ old('distancia') }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="Ej: 2.8, 550">
                                @error('distancia')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="unidad_distancia" class="form-label fw-semibold">
                                    <i class="fas fa-ruler-combined me-1 text-primary"></i>
                                    Unidad
                                </label>
                                <select class="form-select @error('unidad_distancia') is-invalid @enderror" 
                                        id="unidad_distancia" 
                                        name="unidad_distancia">
                                    <option value="km" {{ old('unidad_distancia', 'km') == 'km' ? 'selected' : '' }}>Kilómetros (km)</option>
                                    <option value="m" {{ old('unidad_distancia') == 'm' ? 'selected' : '' }}>Metros (m)</option>
                                </select>
                                @error('unidad_distancia')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="orden" class="form-label fw-semibold">
                                    <i class="fas fa-sort-numeric-down me-1 text-primary"></i>
                                    Orden
                                </label>
                                <input type="number" 
                                       class="form-control @error('orden') is-invalid @enderror" 
                                       id="orden" 
                                       name="orden" 
                                       value="{{ old('orden', 0) }}"
                                       min="0">
                                <small class="form-text text-muted">
                                    Menor número = aparece primero.
                                </small>
                                @error('orden')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-toggle-on me-1 text-primary"></i>
                                    Estado
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="activo" 
                                           name="activo" 
                                           {{ old('activo', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="activo">
                                        Lugar activo
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Solo los lugares activos se mostrarán.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.lugares-cercanos.index', $apartamento->id) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Guardar Lugar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Información
                    </h5>
                    <hr>
                    <p class="card-text">
                        <strong>Apartamento:</strong><br>
                        {{ $apartamento->titulo ?? $apartamento->nombre }}
                    </p>
                    <hr>
                    <p class="card-text">
                        <strong>Categorías disponibles:</strong>
                    </p>
                    <ul class="small text-muted">
                        <li><strong>¿Qué hay cerca?</strong> - Parques, lugares de interés</li>
                        <li><strong>Restaurantes y cafeterías</strong> - Restaurantes cercanos</li>
                        <li><strong>Transporte público</strong> - Trenes, autobuses, metro</li>
                        <li><strong>Playas en la zona</strong> - Playas cercanas</li>
                        <li><strong>Aeropuertos más cercanos</strong> - Aeropuertos y helipuertos</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection




