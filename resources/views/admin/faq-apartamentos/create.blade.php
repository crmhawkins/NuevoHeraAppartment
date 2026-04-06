@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-plus me-2 text-primary"></i>
                Nueva Pregunta Frecuente
            </h1>
            <p class="text-muted mb-0">Añade una pregunta frecuente para {{ $apartamento->titulo ?? $apartamento->nombre }}</p>
        </div>
        <a href="{{ route('admin.faq-apartamentos.index', $apartamento->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <hr class="mb-4">

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('admin.faq-apartamentos.store', $apartamento->id) }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="pregunta" class="form-label fw-semibold">
                                <i class="fas fa-question-circle me-1 text-primary"></i>
                                Pregunta <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('pregunta') is-invalid @enderror" 
                                   id="pregunta" 
                                   name="pregunta" 
                                   value="{{ old('pregunta') }}"
                                   placeholder="Ej: ¿Cuánto cuesta alojarse en este apartamento?"
                                   required>
                            @error('pregunta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="respuesta" class="form-label fw-semibold">
                                <i class="fas fa-align-left me-1 text-primary"></i>
                                Respuesta <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('respuesta') is-invalid @enderror" 
                                      id="respuesta" 
                                      name="respuesta" 
                                      rows="8"
                                      required>{{ old('respuesta') }}</textarea>
                            <small class="form-text text-muted">
                                Describe la respuesta de forma clara y concisa.
                            </small>
                            @error('respuesta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                        Pregunta activa
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Solo las preguntas activas se mostrarán.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.faq-apartamentos.index', $apartamento->id) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Guardar Pregunta
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
                        <strong>Ejemplos de preguntas:</strong>
                    </p>
                    <ul class="small text-muted">
                        <li>¿Cuánto cuesta alojarse en...?</li>
                        <li>¿Cuál es la política de cancelación?</li>
                        <li>¿A qué hora es el check-in y check-out?</li>
                        <li>¿Se admiten mascotas?</li>
                        <li>¿Se puede fumar?</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection




