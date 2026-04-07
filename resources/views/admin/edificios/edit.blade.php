@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-edit me-2 text-primary"></i>
                        Editar Edificio
                    </h1>
                    <p class="text-muted mb-0">Modifica la información del edificio "{{ $edificio->nombre }}"</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.edificio.show', $edificio->id) }}" class="btn btn-outline-info">
                        <i class="fas fa-eye me-2"></i>Ver
                    </a>
                    <a href="{{ route('admin.edificios.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <!-- Formulario principal -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-building me-2 text-primary"></i>
                        Información del Edificio
                    </h5>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('admin.edificio.update', $edificio->id) }}" method="POST" id="edificio-form">
                        @csrf
                        
                        <!-- Nombre del edificio -->
                        <div class="mb-4">
                            <label for="nombre" class="form-label fw-semibold text-dark">
                                <i class="fas fa-signature me-2 text-primary"></i>
                                Nombre del Edificio
                            </label>
                            <input type="text" 
                                   class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" 
                                   name="nombre" 
                                   placeholder="Ej: Residencial Los Pinos"
                                   value="{{ old('nombre', $edificio->nombre) }}"
                                   required>
                            <div class="invalid-feedback" id="nombre-error">
                                @error('nombre') {{ $message }} @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Nombre descriptivo y fácil de recordar
                            </div>
                        </div>

                        <!-- Clave de acceso -->
                        <div class="mb-4">
                            <label for="clave" class="form-label fw-semibold text-dark">
                                <i class="fas fa-key me-2 text-primary"></i>
                                Clave de Acceso
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control @error('clave') is-invalid @enderror" 
                                       id="clave" 
                                       name="clave" 
                                       placeholder="Ej: EDIF001"
                                       value="{{ old('clave', $edificio->clave) }}"
                                       required>
                                <button type="button" 
                                        class="btn btn-outline-secondary" 
                                        id="generate-key"
                                        title="Generar nueva clave">
                                    <i class="fas fa-magic"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="clave-error">
                                @error('clave') {{ $message }} @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Clave única para identificar el edificio en el sistema
                            </div>
                        </div>

                        <!-- Código de Establecimiento MIR -->
                        <div class="mb-4">
                            <label for="codigo_establecimiento" class="form-label fw-semibold text-dark">
                                <i class="fas fa-building me-2 text-primary"></i>
                                Código de Establecimiento MIR
                            </label>
                            <input type="text" 
                                   class="form-control @error('codigo_establecimiento') is-invalid @enderror" 
                                   id="codigo_establecimiento" 
                                   name="codigo_establecimiento" 
                                   placeholder="Ej: 0000119331"
                                   value="{{ old('codigo_establecimiento', $edificio->codigo_establecimiento) }}"
                                   maxlength="50">
                            <div class="invalid-feedback" id="codigo_establecimiento-error">
                                @error('codigo_establecimiento') {{ $message }} @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Código asignado por el Sistema de Hospedajes MIR para este establecimiento (Real Decreto 933/2021)
                            </div>
                        </div>

                        <!-- Tipo de cerradura principal (puerta edificio) -->
                        <div class="mb-4">
                            <label for="tipo_cerradura_principal" class="form-label fw-semibold text-dark">
                                <i class="fas fa-door-open me-2 text-primary"></i>
                                Cerradura Puerta Principal
                            </label>
                            @php
                                $valorTipoCerradura = old('tipo_cerradura_principal', $edificio->tipo_cerradura_principal ?? 'manual');
                            @endphp
                            <div class="d-flex gap-2 flex-wrap">
                                <div class="form-check form-check-inline border rounded px-3 py-2 {{ $valorTipoCerradura === 'manual' ? 'border-primary bg-primary bg-opacity-10' : '' }}" style="cursor:pointer;">
                                    <input class="form-check-input" type="radio" name="tipo_cerradura_principal" id="cerradura_manual" value="manual" {{ $valorTipoCerradura === 'manual' ? 'checked' : '' }} onchange="toggleCerraduraEdificio()">
                                    <label class="form-check-label" for="cerradura_manual" style="cursor:pointer;">
                                        <i class="fas fa-key me-1"></i> Manual
                                    </label>
                                </div>
                                <div class="form-check form-check-inline border rounded px-3 py-2 {{ $valorTipoCerradura === 'ttlock' ? 'border-primary bg-primary bg-opacity-10' : '' }}" style="cursor:pointer;">
                                    <input class="form-check-input" type="radio" name="tipo_cerradura_principal" id="cerradura_ttlock" value="ttlock" {{ $valorTipoCerradura === 'ttlock' ? 'checked' : '' }} onchange="toggleCerraduraEdificio()">
                                    <label class="form-check-label" for="cerradura_ttlock" style="cursor:pointer;">
                                        <i class="fas fa-lock me-1"></i> TTLock
                                    </label>
                                </div>
                                <div class="form-check form-check-inline border rounded px-3 py-2 {{ $valorTipoCerradura === 'tuya' ? 'border-primary bg-primary bg-opacity-10' : '' }}" style="cursor:pointer;">
                                    <input class="form-check-input" type="radio" name="tipo_cerradura_principal" id="cerradura_tuya" value="tuya" {{ $valorTipoCerradura === 'tuya' ? 'checked' : '' }} onchange="toggleCerraduraEdificio()">
                                    <label class="form-check-label" for="cerradura_tuya" style="cursor:pointer;">
                                        <i class="fas fa-wifi me-1"></i> Tuya
                                    </label>
                                </div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Tipo de cerradura de la puerta principal del edificio
                            </div>
                        </div>

                        <!-- ID en Tuyalaravel (solo visible para TTLock/Tuya) -->
                        <div class="mb-4" id="tuyalaravel-building-section" style="{{ in_array($valorTipoCerradura, ['ttlock', 'tuya']) ? '' : 'display:none;' }}">
                            <label for="tuyalaravel_building_id" class="form-label fw-semibold text-dark">
                                <i class="fas fa-link me-2 text-primary"></i>
                                ID Edificio en App Cerraduras
                            </label>
                            <input type="number"
                                   class="form-control @error('tuyalaravel_building_id') is-invalid @enderror"
                                   id="tuyalaravel_building_id"
                                   name="tuyalaravel_building_id"
                                   placeholder="ID del building en Tuyalaravel"
                                   value="{{ old('tuyalaravel_building_id', $edificio->tuyalaravel_building_id) }}">
                            <div class="invalid-feedback">
                                @error('tuyalaravel_building_id') {{ $message }} @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                ID del edificio en la app gestora de cerraduras (Tuyalaravel)
                            </div>
                        </div>

                        <!-- Método de entrada (legacy, oculto) -->
                        <input type="hidden" name="metodo_entrada" id="metodo_entrada" value="{{ old('metodo_entrada', $edificio->metodo_entrada) }}">

                        <script>
                            function toggleCerraduraEdificio() {
                                var tipo = document.querySelector('input[name="tipo_cerradura_principal"]:checked').value;
                                var section = document.getElementById('tuyalaravel-building-section');
                                var metodoEntrada = document.getElementById('metodo_entrada');
                                section.style.display = (tipo === 'ttlock' || tipo === 'tuya') ? '' : 'none';
                                metodoEntrada.value = (tipo === 'manual') ? 'fisica' : 'digital';
                                // Highlight selected
                                document.querySelectorAll('input[name="tipo_cerradura_principal"]').forEach(function(r) {
                                    var container = r.closest('.form-check');
                                    if (r.checked) {
                                        container.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                                    } else {
                                        container.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
                                    }
                                });
                            }
                        </script>

                        <!-- Envío automático a MIR -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input type="hidden" name="mir_activo" value="0">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="mir_activo"
                                       name="mir_activo"
                                       value="1"
                                       {{ old('mir_activo', $edificio->mir_activo) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-dark" for="mir_activo">
                                    <i class="fas fa-paper-plane me-2 text-primary"></i>
                                    Envío automático a MIR
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Si está activado, las reservas de este edificio se enviarán automáticamente al sistema MIR cuando estén listas
                            </div>
                        </div>

                        <!-- Información del edificio -->
                        <div class="alert alert-info border-0">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-info-circle me-3 text-info"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">Información del Edificio</h6>
                                    <p class="mb-0 small">
                                        <strong>ID:</strong> #{{ $edificio->id }} | 
                                        <strong>Creado:</strong> {{ $edificio->created_at->format('d/m/Y H:i') }} | 
                                        <strong>Última actualización:</strong> {{ $edificio->updated_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex gap-3 pt-3">
                            <button type="submit" class="btn btn-primary btn-lg px-4" id="submit-btn">
                                <i class="fas fa-save me-2"></i>
                                Actualizar Edificio
                            </button>
                            <a href="{{ route('admin.edificios.index') }}" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        Estadísticas Rápidas
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-primary-subtle rounded">
                                <i class="fas fa-home fa-2x text-primary mb-2"></i>
                                <h4 class="mb-1 fw-bold text-primary">{{ $edificio->apartamentos->count() }}</h4>
                                <small class="text-muted">Apartamentos</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-warning-subtle rounded">
                                <i class="fas fa-clipboard-check fa-2x text-warning mb-2"></i>
                                <h4 class="mb-1 fw-bold text-warning">{{ $edificio->checklists->count() }}</h4>
                                <small class="text-muted">Checklists</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('edificio-form');
    const nombreInput = document.getElementById('nombre');
    const claveInput = document.getElementById('clave');
    const submitBtn = document.getElementById('submit-btn');
    const generateKeyBtn = document.getElementById('generate-key');

    // Generar nueva clave
    generateKeyBtn.addEventListener('click', function() {
        const nombre = nombreInput.value.trim();
        if (nombre) {
            const clave = 'EDIF' + Math.random().toString(36).substr(2, 6).toUpperCase();
            claveInput.value = clave;
            claveInput.classList.remove('is-invalid');
            document.getElementById('clave-error').textContent = '';
            
            Swal.fire({
                icon: 'success',
                title: 'Clave generada',
                text: 'Se ha generado una nueva clave automáticamente',
                timer: 2000,
                timerProgressBar: true,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Primero debes ingresar el nombre del edificio',
                confirmButtonColor: '#6c757d'
            });
        }
    });

    // Validación en tiempo real
    function validateField(input, errorId, validationFn) {
        const value = input.value.trim();
        const errorElement = document.getElementById(errorId);
        
        if (validationFn(value)) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            errorElement.textContent = '';
            return true;
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            return false;
        }
    }

    // Validar nombre
    nombreInput.addEventListener('input', function() {
        validateField(this, 'nombre-error', function(value) {
            return value.length >= 3 && value.length <= 255;
        });
    });

    // Validar clave
    claveInput.addEventListener('input', function() {
        validateField(this, 'clave-error', function(value) {
            return value.length >= 3 && value.length <= 255;
        });
    });

    // Validación del formulario
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validar nombre
        if (!validateField(nombreInput, 'nombre-error', function(value) {
            return value.length >= 3 && value.length <= 255;
        })) {
            isValid = false;
        }
        
        // Validar clave
        if (!validateField(claveInput, 'clave-error', function(value) {
            return value.length >= 3 && value.length <= 255;
        })) {
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Formulario incompleto',
                text: 'Por favor, completa todos los campos correctamente',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
        
        // Mostrar confirmación
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
        
        Swal.fire({
            title: 'Actualizando edificio...',
            text: 'Por favor espera mientras se procesa la información',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    });

    // Mostrar mensajes de SweetAlert
    @if(session('swal_success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('swal_success') }}',
            timer: 3000,
            timerProgressBar: true,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
    @endif

    @if(session('swal_error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('swal_error') }}',
            timer: 5000,
            timerProgressBar: true,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
    @endif

    // Mostrar errores de validación del servidor
    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Errores de validación',
            html: `@foreach($errors->all() as $error)<div class="text-start">• {{ $error }}</div>@endforeach`,
            confirmButtonColor: '#dc3545'
        });
    @endif
});
</script>
@endsection

