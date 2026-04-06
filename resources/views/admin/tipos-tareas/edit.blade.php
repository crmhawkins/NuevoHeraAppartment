@extends('layouts.appAdmin')

@section('title', 'Editar Tipo de Tarea')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>Editar Tipo de Tarea
                        </h3>
                        <small class="text-muted">Modificar la configuración del tipo de tarea</small>
                    </div>
                    <div>
                        <a href="{{ route('admin.tipos-tareas.index') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                        <a href="{{ route('admin.tipos-tareas.show', $tiposTarea) }}" class="btn btn-info">
                            <i class="fas fa-eye me-1"></i>Ver Detalle
                        </a>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.tipos-tareas.update', $tiposTarea) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <!-- Información Principal -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Información General
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control @error('nombre') is-invalid @enderror" 
                                                   id="nombre" 
                                                   name="nombre" 
                                                   value="{{ old('nombre', $tiposTarea->nombre) }}" 
                                                   required>
                                            @error('nombre')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                                            <select class="form-select @error('categoria') is-invalid @enderror" 
                                                    id="categoria" 
                                                    name="categoria" 
                                                    required>
                                                <option value="">Seleccionar categoría...</option>
                                                @foreach($categorias as $key => $nombre)
                                                    <option value="{{ $key }}" 
                                                            {{ old('categoria', $tiposTarea->categoria) == $key ? 'selected' : '' }}>
                                                        {{ $nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('categoria')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              rows="3">{{ old('descripcion', $tiposTarea->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="instrucciones" class="form-label">Instrucciones</label>
                                    <textarea class="form-control @error('instrucciones') is-invalid @enderror" 
                                              id="instrucciones" 
                                              name="instrucciones" 
                                              rows="4" 
                                              placeholder="Instrucciones específicas para realizar esta tarea...">{{ old('instrucciones', $tiposTarea->instrucciones) }}</textarea>
                                    @error('instrucciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Configuración de Prioridades -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Configuración de Prioridades
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="prioridad_base" class="form-label">Prioridad Base <span class="text-danger">*</span></label>
                                            <input type="number" 
                                                   class="form-control @error('prioridad_base') is-invalid @enderror" 
                                                   id="prioridad_base" 
                                                   name="prioridad_base" 
                                                   value="{{ old('prioridad_base', $tiposTarea->prioridad_base) }}" 
                                                   min="1" 
                                                   max="10" 
                                                   required>
                                            <small class="form-text text-muted">Prioridad inicial (1-10)</small>
                                            @error('prioridad_base')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="prioridad_maxima" class="form-label">Prioridad Máxima <span class="text-danger">*</span></label>
                                            <input type="number" 
                                                   class="form-control @error('prioridad_maxima') is-invalid @enderror" 
                                                   id="prioridad_maxima" 
                                                   name="prioridad_maxima" 
                                                   value="{{ old('prioridad_maxima', $tiposTarea->prioridad_maxima) }}" 
                                                   min="1" 
                                                   max="10" 
                                                   required>
                                            <small class="form-text text-muted">Prioridad máxima alcanzable</small>
                                            @error('prioridad_maxima')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="dias_max_sin_limpiar" class="form-label">Días Máx. Sin Limpiar</label>
                                            <input type="number" 
                                                   class="form-control @error('dias_max_sin_limpiar') is-invalid @enderror" 
                                                   id="dias_max_sin_limpiar" 
                                                   name="dias_max_sin_limpiar" 
                                                   value="{{ old('dias_max_sin_limpiar', $tiposTarea->dias_max_sin_limpiar) }}" 
                                                   min="0">
                                            <small class="form-text text-muted">Días antes de incrementar prioridad</small>
                                            @error('dias_max_sin_limpiar')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="incremento_prioridad_por_dia" class="form-label">Incremento por Día</label>
                                            <input type="number" 
                                                   class="form-control @error('incremento_prioridad_por_dia') is-invalid @enderror" 
                                                   id="incremento_prioridad_por_dia" 
                                                   name="incremento_prioridad_por_dia" 
                                                   value="{{ old('incremento_prioridad_por_dia', $tiposTarea->incremento_prioridad_por_dia) }}" 
                                                   min="0">
                                            <small class="form-text text-muted">Puntos de prioridad por día</small>
                                            @error('incremento_prioridad_por_dia')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Lateral -->
                    <div class="col-md-4">
                        <!-- Configuración de Tiempo -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>Configuración de Tiempo
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="tiempo_estimado_minutos" class="form-label">Tiempo Estimado (minutos) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control @error('tiempo_estimado_minutos') is-invalid @enderror" 
                                           id="tiempo_estimado_minutos" 
                                           name="tiempo_estimado_minutos" 
                                           value="{{ old('tiempo_estimado_minutos', $tiposTarea->tiempo_estimado_minutos) }}" 
                                           min="1" 
                                           required>
                                    <small class="form-text text-muted">Tiempo estimado en minutos</small>
                                    @error('tiempo_estimado_minutos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="activo" 
                                               name="activo" 
                                               value="1" 
                                               {{ old('activo', $tiposTarea->activo) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="activo">
                                            Activo
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Los tipos inactivos no aparecerán en nuevas asignaciones</small>
                                </div>
                            </div>
                        </div>

                        <!-- Requisitos -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cogs me-2"></i>Requisitos
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Campos ocultos para asegurar que siempre se envíen -->
                                <input type="hidden" name="requiere_apartamento" value="0">
                                <input type="hidden" name="requiere_zona_comun" value="0">
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="requiere_apartamento" 
                                               name="requiere_apartamento" 
                                               value="1" 
                                               {{ old('requiere_apartamento', $tiposTarea->requiere_apartamento) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="requiere_apartamento">
                                            Requiere Apartamento
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Esta tarea debe asignarse a un apartamento específico</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="requiere_zona_comun" 
                                               name="requiere_zona_comun" 
                                               value="1" 
                                               {{ old('requiere_zona_comun', $tiposTarea->requiere_zona_comun) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="requiere_zona_comun">
                                            Requiere Zona Común
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Esta tarea debe asignarse a una zona común específica</small>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Guardar Cambios
                                    </button>
                                    <a href="{{ route('admin.tipos-tareas.show', $tiposTarea) }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const prioridadBase = document.getElementById('prioridad_base');
    const prioridadMaxima = document.getElementById('prioridad_maxima');
    
    // Validar que la prioridad base no sea mayor que la máxima
    function validarPrioridades() {
        const base = parseInt(prioridadBase.value);
        const maxima = parseInt(prioridadMaxima.value);
        
        if (base > maxima) {
            prioridadBase.setCustomValidity('La prioridad base no puede ser mayor que la prioridad máxima');
        } else {
            prioridadBase.setCustomValidity('');
        }
    }
    
    prioridadBase.addEventListener('input', validarPrioridades);
    prioridadMaxima.addEventListener('input', validarPrioridades);
    
    // Validación inicial
    validarPrioridades();
    
    // Mostrar errores de validación si existen
    @if($errors->any())
        console.log('Errores de validación del servidor:', @json($errors->all()));
        
        // Mostrar alerta con errores
        Swal.fire({
            title: 'Error de Validación',
            html: `
                <div class="text-start">
                    <p>Por favor corrige los siguientes errores:</p>
                    <ul class="list-unstyled">
                        @foreach($errors->all() as $error)
                            <li class="text-danger">• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            `,
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
    @endif
});
</script>
@endsection
