@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-edit me-2 text-primary"></i>
                Editar Pregunta Frecuente
            </h1>
            <p class="text-muted mb-0">Modifica los datos de la pregunta</p>
        </div>
        <a href="{{ route('admin.preguntas-frecuentes.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
    </div>

    <hr class="mb-4">

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('admin.preguntas-frecuentes.update', $pregunta->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="pregunta" class="form-label fw-semibold">
                                <i class="fas fa-question me-1 text-primary"></i>
                                Pregunta <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('pregunta') is-invalid @enderror" 
                                   id="pregunta" 
                                   name="pregunta" 
                                   value="{{ old('pregunta', $pregunta->pregunta) }}"
                                   required>
                            @error('pregunta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="respuesta" class="form-label fw-semibold">
                                <i class="fas fa-comment-dots me-1 text-primary"></i>
                                Respuesta <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('respuesta') is-invalid @enderror" 
                                      id="respuesta" 
                                      name="respuesta"
                                      rows="8"
                                      required>{{ old('respuesta', $pregunta->respuesta) }}</textarea>
                            <small class="form-text text-muted">
                                Puedes usar HTML básico para formatear la respuesta (párrafos, listas, enlaces, etc.).
                            </small>
                            @error('respuesta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="categoria" class="form-label fw-semibold">
                                    <i class="fas fa-tags me-1 text-primary"></i>
                                    Categoría
                                </label>
                                <input type="text" 
                                       class="form-control @error('categoria') is-invalid @enderror" 
                                       id="categoria" 
                                       name="categoria" 
                                       value="{{ old('categoria', $pregunta->categoria) }}"
                                       list="categorias-list"
                                       placeholder="Ej: Reservas, Cancelaciones, Pagos...">
                                <datalist id="categorias-list">
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat }}">
                                    @endforeach
                                </datalist>
                                <small class="form-text text-muted">
                                    Opcional. Agrupa preguntas por categoría.
                                </small>
                                @error('categoria')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="orden" class="form-label fw-semibold">
                                    <i class="fas fa-sort-numeric-down me-1 text-primary"></i>
                                    Orden
                                </label>
                                <input type="number" 
                                       class="form-control @error('orden') is-invalid @enderror" 
                                       id="orden" 
                                       name="orden" 
                                       value="{{ old('orden', $pregunta->orden) }}"
                                       min="0">
                                <small class="form-text text-muted">
                                    Menor número = aparece primero.
                                </small>
                                @error('orden')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="activo" 
                                       name="activo" 
                                       value="1"
                                       {{ old('activo', $pregunta->activo) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="activo">
                                    <i class="fas fa-toggle-on me-1 text-primary"></i>
                                    Pregunta Activa
                                </label>
                                <small class="form-text text-muted d-block">
                                    Si está desactivada, la pregunta no será visible públicamente.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Guardar Cambios
                            </button>
                            <a href="{{ route('admin.preguntas-frecuentes.index') }}" class="btn btn-secondary">
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

