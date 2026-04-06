@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-file-contract me-2 text-primary"></i>
                Política de Cancelaciones y Devoluciones
            </h1>
            <p class="text-muted mb-0">Edita el contenido de la política de cancelaciones</p>
        </div>
        <a href="{{ route('admin.politica-cancelacion.edit') }}" class="btn btn-secondary">
            <i class="fas fa-redo me-2"></i>
            Recargar
        </a>
    </div>

    <hr class="mb-4">

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('admin.politica-cancelacion.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="titulo" class="form-label fw-semibold">
                                <i class="fas fa-heading me-1 text-primary"></i>
                                Título
                            </label>
                            <input type="text" 
                                   class="form-control @error('titulo') is-invalid @enderror" 
                                   id="titulo" 
                                   name="titulo" 
                                   value="{{ old('titulo', $politica->titulo) }}"
                                   required>
                            @error('titulo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="fecha_actualizacion" class="form-label fw-semibold">
                                <i class="fas fa-calendar me-1 text-primary"></i>
                                Fecha de Actualización
                            </label>
                            <input type="date" 
                                   class="form-control @error('fecha_actualizacion') is-invalid @enderror" 
                                   id="fecha_actualizacion" 
                                   name="fecha_actualizacion" 
                                   value="{{ old('fecha_actualizacion', $politica->fecha_actualizacion ? $politica->fecha_actualizacion->format('Y-m-d') : now()->format('Y-m-d')) }}">
                            @error('fecha_actualizacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="contenido" class="form-label fw-semibold">
                                <i class="fas fa-align-left me-1 text-primary"></i>
                                Contenido
                            </label>
                            <textarea class="form-control @error('contenido') is-invalid @enderror" 
                                      id="contenido" 
                                      name="contenido"
                                      rows="20"
                                      required>{{ old('contenido', $politica->contenido) }}</textarea>
                            <small class="form-text text-muted">
                                Puedes usar HTML para formatear el contenido. Usa &lt;p&gt; para párrafos, &lt;h2&gt; para títulos, &lt;ul&gt; y &lt;li&gt; para listas, etc.
                            </small>
                            @error('contenido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="activo" 
                                       name="activo" 
                                       value="1"
                                       {{ old('activo', $politica->activo) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="activo">
                                    <i class="fas fa-toggle-on me-1 text-primary"></i>
                                    Política Activa
                                </label>
                                <small class="form-text text-muted d-block">
                                    Si está desactivada, la página no será visible públicamente.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Guardar Cambios
                            </button>
                            <a href="{{ route('web.politica-cancelaciones') }}" target="_blank" class="btn btn-outline-info">
                                <i class="fas fa-external-link-alt me-2"></i>
                                Ver Vista Pública
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

