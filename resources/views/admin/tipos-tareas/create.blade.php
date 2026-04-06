@extends('layouts.appAdmin')

@section('title', 'Crear Tipo de Tarea')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus me-2"></i>Crear Tipo de Tarea
                    </h3>
                </div>
                
                <form action="{{ route('admin.tipos-tareas.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                           id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                              id="descripcion" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-select @error('categoria') is-invalid @enderror" id="categoria" name="categoria" required>
                                        <option value="">Seleccionar categoría...</option>
                                        @foreach($categorias as $key => $nombre)
                                            <option value="{{ $key }}" {{ old('categoria') == $key ? 'selected' : '' }}>
                                                {{ $nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('categoria')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="prioridad_base" class="form-label">Prioridad Base <span class="text-danger">*</span></label>
                                            <select class="form-select @error('prioridad_base') is-invalid @enderror" id="prioridad_base" name="prioridad_base" required>
                                                @for($i = 1; $i <= 10; $i++)
                                                    <option value="{{ $i }}" {{ old('prioridad_base', 5) == $i ? 'selected' : '' }}>
                                                        {{ $i }} - {{ $i >= 8 ? 'Alta' : ($i >= 6 ? 'Media' : 'Baja') }}
                                                    </option>
                                                @endfor
                                            </select>
                                            @error('prioridad_base')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="tiempo_estimado_minutos" class="form-label">Tiempo Estimado (min) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('tiempo_estimado_minutos') is-invalid @enderror" 
                                                   id="tiempo_estimado_minutos" name="tiempo_estimado_minutos" 
                                                   value="{{ old('tiempo_estimado_minutos', 30) }}" min="1" required>
                                            @error('tiempo_estimado_minutos')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Configuración de Prioridad Dinámica</h6>
                                
                                <div class="mb-3">
                                    <label for="dias_max_sin_limpiar" class="form-label">Días Máximos sin Limpiar</label>
                                    <input type="number" class="form-control @error('dias_max_sin_limpiar') is-invalid @enderror" 
                                           id="dias_max_sin_limpiar" name="dias_max_sin_limpiar" 
                                           value="{{ old('dias_max_sin_limpiar') }}" min="1">
                                    <small class="form-text text-muted">Dejar vacío para prioridad fija</small>
                                    @error('dias_max_sin_limpiar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="incremento_prioridad_por_dia" class="form-label">Incremento por Día <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('incremento_prioridad_por_dia') is-invalid @enderror" 
                                                   id="incremento_prioridad_por_dia" name="incremento_prioridad_por_dia" 
                                                   value="{{ old('incremento_prioridad_por_dia', 1) }}" min="0" required>
                                            @error('incremento_prioridad_por_dia')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="prioridad_maxima" class="form-label">Prioridad Máxima <span class="text-danger">*</span></label>
                                            <select class="form-select @error('prioridad_maxima') is-invalid @enderror" id="prioridad_maxima" name="prioridad_maxima" required>
                                                @for($i = 1; $i <= 10; $i++)
                                                    <option value="{{ $i }}" {{ old('prioridad_maxima', 10) == $i ? 'selected' : '' }}>
                                                        {{ $i }}
                                                    </option>
                                                @endfor
                                            </select>
                                            @error('prioridad_maxima')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="instrucciones" class="form-label">Instrucciones</label>
                                    <textarea class="form-control @error('instrucciones') is-invalid @enderror" 
                                              id="instrucciones" name="instrucciones" rows="4">{{ old('instrucciones') }}</textarea>
                                    @error('instrucciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- Campos ocultos para asegurar que siempre se envíen -->
                                <input type="hidden" name="requiere_apartamento" value="0">
                                <input type="hidden" name="requiere_zona_comun" value="0">
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="requiere_apartamento" name="requiere_apartamento" value="1"
                                                   {{ old('requiere_apartamento') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="requiere_apartamento">
                                                Requiere Apartamento
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="requiere_zona_comun" name="requiere_zona_comun" value="1"
                                                   {{ old('requiere_zona_comun') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="requiere_zona_comun">
                                                Requiere Zona Común
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.tipos-tareas.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Crear Tipo de Tarea
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar errores de validación si existen
    @if($errors->any())
        console.log('Errores de validación:', @json($errors->all()));
        
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
    
    // Validación de prioridad máxima
    const prioridadBase = document.getElementById('prioridad_base');
    const prioridadMaxima = document.getElementById('prioridad_maxima');
    
    function validarPrioridades() {
        if (prioridadBase.value && prioridadMaxima.value) {
            if (parseInt(prioridadMaxima.value) < parseInt(prioridadBase.value)) {
                prioridadMaxima.setCustomValidity('La prioridad máxima debe ser mayor o igual a la prioridad base');
            } else {
                prioridadMaxima.setCustomValidity('');
            }
        }
    }
    
    prioridadBase.addEventListener('change', validarPrioridades);
    prioridadMaxima.addEventListener('change', validarPrioridades);
    
    // Validación inicial
    validarPrioridades();
});
</script>
@endsection
