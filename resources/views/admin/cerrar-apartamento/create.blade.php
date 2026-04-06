@extends('layouts.appAdmin')

@section('title', 'Crear Cierre de Apartamento')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-plus-circle text-success me-2"></i>
                        Crear Cierre de Apartamento
                    </h1>
                    <p class="text-muted mb-0">Formulario para crear un nuevo cierre de apartamento</p>
                </div>
                <div>
                    <a href="{{ route('admin.cerrar-apartamento.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver al Listado
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-door-closed me-2"></i>
                        Datos del Cierre
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Mostrar errores de validación -->
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Por favor, corrige los siguientes errores:
                            </h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.cerrar-apartamento.store') }}" method="POST" id="formCerrarApartamento">
                        @csrf
                        
                        <!-- Selector de Apartamento -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label for="apartamento_id" class="form-label fw-semibold">
                                    <i class="fas fa-home text-primary me-1"></i>
                                    Apartamento
                                </label>
                                <select class="form-select form-select-lg {{ $errors->has('apartamento_id') ? 'is-invalid' : '' }}" id="apartamento_id" name="apartamento_id" required>
                                    <option value="">Seleccionar apartamento</option>
                                    @foreach($apartamentos as $apartamento)
                                        <option value="{{ $apartamento->id }}" {{ old('apartamento_id') == $apartamento->id ? 'selected' : '' }}>
                                            {{ $apartamento->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('apartamento_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Selecciona el apartamento que deseas cerrar
                                </div>
                            </div>
                        </div>

                        <!-- Fechas -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="fecha_inicio" class="form-label fw-semibold">
                                    <i class="fas fa-calendar-alt text-success me-1"></i>
                                    Fecha de Inicio
                                </label>
                                <input type="date" 
                                       class="form-control form-control-lg {{ $errors->has('fecha_inicio') ? 'is-invalid' : '' }}" 
                                       id="fecha_inicio" 
                                       name="fecha_inicio" 
                                       value="{{ old('fecha_inicio') }}"
                                       required>
                                @error('fecha_inicio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Fecha en que inicia el cierre del apartamento
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="fecha_fin" class="form-label fw-semibold">
                                    <i class="fas fa-calendar-times text-danger me-1"></i>
                                    Fecha de Fin
                                </label>
                                <input type="date" 
                                       class="form-control form-control-lg {{ $errors->has('fecha_fin') ? 'is-invalid' : '' }}" 
                                       id="fecha_fin" 
                                       name="fecha_fin" 
                                       value="{{ old('fecha_fin') }}"
                                       required>
                                @error('fecha_fin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Fecha en que termina el cierre del apartamento
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.cerrar-apartamento.index') }}" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-times me-2"></i>
                                        Cancelar
                                    </a>
                                    
                                    <button type="submit" class="btn btn-primary btn-lg" id="btnCrear">
                                        <span class="btn-text">
                                            <i class="fas fa-save me-2"></i>
                                            Crear Cierre
                                        </span>
                                        <span class="btn-loading d-none">
                                            <i class="fas fa-spinner fa-spin me-2"></i>
                                            Creando...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle text-info me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <h6 class="alert-heading mb-1">Información importante</h6>
                        <p class="mb-0 text-muted">
                            Al crear un cierre de apartamento, se creará automáticamente una reserva con el cliente "Apartamento Cerrado" 
                            y el estado "Cerrado" para el período especificado.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Establecer fecha mínima como hoy
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fecha_inicio').setAttribute('min', today);
        document.getElementById('fecha_fin').setAttribute('min', today);

        // Validar que fecha fin sea mayor que fecha inicio
        document.getElementById('fecha_inicio').addEventListener('change', function() {
            const fechaInicio = this.value;
            const fechaFin = document.getElementById('fecha_fin');
            
            if (fechaInicio) {
                fechaFin.setAttribute('min', fechaInicio);
                if (fechaFin.value && fechaFin.value <= fechaInicio) {
                    fechaFin.value = '';
                }
            }
        });

        // Manejar envío del formulario
        document.getElementById('formCerrarApartamento').addEventListener('submit', function(e) {
            const btnCrear = document.getElementById('btnCrear');
            const btnText = btnCrear.querySelector('.btn-text');
            const btnLoading = btnCrear.querySelector('.btn-loading');
            
            // Mostrar loading
            btnCrear.disabled = true;
            btnText.classList.add('d-none');
            btnLoading.classList.remove('d-none');
            
            // El formulario se enviará normalmente
        });
    });
</script>
@endsection
