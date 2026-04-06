@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-edit me-2 text-primary"></i>
                Editar Página Legal
            </h1>
            <p class="text-muted mb-0">Modifica los datos de la página</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('web.pagina-legal.show', $paginasLegale->slug) }}" 
               target="_blank" 
               class="btn btn-outline-info">
                <i class="fas fa-external-link-alt me-2"></i>
                Ver Vista Pública
            </a>
            <a href="{{ route('admin.paginas-legales.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Volver
            </a>
        </div>
    </div>

    <hr class="mb-4">

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('admin.paginas-legales.update', $paginasLegale->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="titulo" class="form-label fw-semibold">
                                <i class="fas fa-heading me-1 text-primary"></i>
                                Título <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('titulo') is-invalid @enderror" 
                                   id="titulo" 
                                   name="titulo" 
                                   value="{{ old('titulo', $paginasLegale->titulo) }}"
                                   required>
                            @error('titulo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="slug" class="form-label fw-semibold">
                                <i class="fas fa-link me-1 text-primary"></i>
                                Slug (URL)
                            </label>
                            <input type="text" 
                                   class="form-control @error('slug') is-invalid @enderror" 
                                   id="slug" 
                                   name="slug" 
                                   value="{{ old('slug', $paginasLegale->slug) }}"
                                   placeholder="Se generará automáticamente si se deja vacío">
                            <small class="form-text text-muted">
                                URL amigable para la página. Se generará automáticamente desde el título si se deja vacío.
                            </small>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="contenido" class="form-label fw-semibold">
                                <i class="fas fa-align-left me-1 text-primary"></i>
                                Contenido <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('contenido') is-invalid @enderror" 
                                      id="contenido" 
                                      name="contenido"
                                      rows="20"
                                      required>{{ old('contenido', $paginasLegale->contenido) }}</textarea>
                            <small class="form-text text-muted">
                                Puedes usar HTML para formatear el contenido. Usa &lt;p&gt; para párrafos, &lt;h2&gt; para títulos, &lt;ul&gt; y &lt;li&gt; para listas, etc.
                            </small>
                            @error('contenido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label for="fecha_actualizacion" class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1 text-primary"></i>
                                    Fecha de Actualización
                                </label>
                                <input type="date" 
                                       class="form-control @error('fecha_actualizacion') is-invalid @enderror" 
                                       id="fecha_actualizacion" 
                                       name="fecha_actualizacion" 
                                       value="{{ old('fecha_actualizacion', $paginasLegale->fecha_actualizacion ? $paginasLegale->fecha_actualizacion->format('Y-m-d') : now()->format('Y-m-d')) }}">
                                @error('fecha_actualizacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-4">
                                <label for="orden" class="form-label fw-semibold">
                                    <i class="fas fa-sort-numeric-down me-1 text-primary"></i>
                                    Orden
                                </label>
                                <input type="number" 
                                       class="form-control @error('orden') is-invalid @enderror" 
                                       id="orden" 
                                       name="orden" 
                                       value="{{ old('orden', $paginasLegale->orden) }}"
                                       min="0">
                                <small class="form-text text-muted">
                                    Menor número = aparece primero en el sidebar.
                                </small>
                                @error('orden')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="activo" 
                                           name="activo" 
                                           value="1"
                                           {{ old('activo', $paginasLegale->activo) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="activo">
                                        <i class="fas fa-toggle-on me-1 text-primary"></i>
                                        Página Activa
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        Si está desactivada, la página no será visible públicamente.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="mostrar_en_sidebar" 
                                           name="mostrar_en_sidebar" 
                                           value="1"
                                           {{ old('mostrar_en_sidebar', $paginasLegale->mostrar_en_sidebar) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="mostrar_en_sidebar">
                                        <i class="fas fa-list me-1 text-primary"></i>
                                        Mostrar en Sidebar
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        Si está marcado, aparecerá en el menú lateral de información legal.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Guardar Cambios
                            </button>
                            <a href="{{ route('admin.paginas-legales.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

