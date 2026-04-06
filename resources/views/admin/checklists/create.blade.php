@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>
                        Nuevo Checklist
                    </h1>
                    <p class="text-muted mb-0">Crea un nuevo checklist para la plataforma</p>
                </div>
                <a href="{{ route('admin.checklists.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <!-- Formulario principal -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-clipboard-check me-2 text-primary"></i>
                        Información del Checklist
                    </h5>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('admin.checklists.store') }}" method="POST" id="checklist-form">
                        @csrf
                        
                        <!-- Edificio -->
                        <div class="mb-4">
                            <label for="edificio_id" class="form-label fw-semibold text-dark">
                                <i class="fas fa-building me-2 text-primary"></i>
                                Edificio
                            </label>
                            <select name="edificio_id" 
                                    id="edificio_id" 
                                    class="form-select @error('edificio_id') is-invalid @enderror" 
                                    required>
                                <option value="">Selecciona un edificio</option>
                                @foreach ($edificios as $edificio)
                                    <option value="{{ $edificio->id }}" {{ old('edificio_id') == $edificio->id ? 'selected' : '' }}>
                                        {{ $edificio->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="edificio_id-error">
                                @error('edificio_id') {{ $message }} @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Selecciona el edificio donde se aplicará este checklist
                            </div>
                        </div>

                        <!-- Nombre del checklist -->
                        <div class="mb-4">
                            <label for="nombre" class="form-label fw-semibold text-dark">
                                <i class="fas fa-signature me-2 text-primary"></i>
                                Nombre del Checklist
                            </label>
                            <input type="text" 
                                   class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" 
                                   name="nombre" 
                                   placeholder="Ej: Checklist de Limpieza Diaria"
                                   value="{{ old('nombre') }}"
                                   required>
                            <div class="invalid-feedback" id="nombre-error">
                                @error('nombre') {{ $message }} @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Nombre descriptivo del checklist
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="mb-4">
                            <label for="descripcion" class="form-label fw-semibold text-dark">
                                <i class="fas fa-align-left me-2 text-primary"></i>
                                Descripción
                            </label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="3" 
                                      placeholder="Descripción opcional del checklist...">{{ old('descripcion') }}</textarea>
                            <div class="invalid-feedback" id="descripcion-error">
                                @error('descripcion') {{ $message }} @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Descripción opcional para explicar el propósito del checklist
                            </div>
                        </div>

                        <!-- Tipo de checklist -->
                        <div class="mb-4">
                            <label for="tipo" class="form-label fw-semibold text-dark">
                                <i class="fas fa-tags me-2 text-primary"></i>
                                Tipo de Checklist
                            </label>
                            <select name="tipo" 
                                    id="tipo" 
                                    class="form-select @error('tipo') is-invalid @enderror">
                                <option value="limpieza" {{ old('tipo') == 'limpieza' ? 'selected' : '' }}>Limpieza</option>
                                <option value="mantenimiento" {{ old('tipo') == 'mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                                <option value="inspeccion" {{ old('tipo') == 'inspeccion' ? 'selected' : '' }}>Inspección</option>
                                <option value="seguridad" {{ old('tipo') == 'seguridad' ? 'selected' : '' }}>Seguridad</option>
                            </select>
                            <div class="invalid-feedback" id="tipo-error">
                                @error('tipo') {{ $message }} @enderror
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Categoría del checklist para mejor organización
                            </div>
                        </div>

                        <!-- Estado activo -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="activo" 
                                       name="activo" 
                                       value="1" 
                                       {{ old('activo') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-dark" for="activo">
                                    <i class="fas fa-toggle-on me-2 text-success"></i>
                                    Checklist activo
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Los checklists activos están disponibles para su uso
                            </div>
                        </div>

                        <!-- Requisitos de fotos -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-semibold text-dark">
                                    <i class="fas fa-camera me-2 text-primary"></i>
                                    Requisitos de Fotos
                                </h6>
                                <button type="button" 
                                        id="add-photo-requirement" 
                                        class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Agregar Foto
                                </button>
                            </div>
                            
                            <div id="photo-requirements">
                                <!-- Los requisitos de fotos se agregarán dinámicamente aquí -->
                            </div>
                            
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1 text-muted"></i>
                                Define qué fotos se requieren para completar este checklist
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex gap-3 pt-3">
                            <button type="submit" class="btn btn-primary btn-lg px-4" id="submit-btn">
                                <i class="fas fa-save me-2"></i>
                                Crear Checklist
                            </button>
                            <a href="{{ route('admin.checklists.index') }}" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h6 class="fw-semibold text-dark mb-3">
                        <i class="fas fa-lightbulb me-2 text-warning"></i>
                        Consejos para crear checklists
                    </h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Usa nombres claros y descriptivos
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Selecciona el tipo apropiado para mejor organización
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Define requisitos de fotos específicos
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Puedes agregar items del checklist después de crearlo
                        </li>
                    </ul>
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
    const form = document.getElementById('checklist-form');
    const edificioSelect = document.getElementById('edificio_id');
    const nombreInput = document.getElementById('nombre');
    const descripcionInput = document.getElementById('descripcion');
    const tipoSelect = document.getElementById('tipo');
    const submitBtn = document.getElementById('submit-btn');
    const addPhotoBtn = document.getElementById('add-photo-requirement');
    const photoRequirements = document.getElementById('photo-requirements');

    // Agregar requisito de foto
    addPhotoBtn.addEventListener('click', function() {
        const photoIndex = photoRequirements.children.length;
        const newPhotoField = `
            <div class="photo-requirement-wrapper card border-0 bg-light mb-3" data-index="${photoIndex}">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-dark">
                            <i class="fas fa-camera me-2 text-primary"></i>
                            Requisito de Foto #${photoIndex + 1}
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-photo-requirement">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Nombre de la Foto</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="photo_names[]" 
                                   placeholder="Ej: Foto del baño">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Cantidad</label>
                            <input type="number" 
                                   class="form-control" 
                                   name="photo_quantities[]" 
                                   value="1" 
                                   min="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Descripción</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="photo_descriptions[]" 
                                   placeholder="Ej: Tomar foto de la limpieza final del baño">
                        </div>
                    </div>
                </div>
            </div>
        `;
        photoRequirements.insertAdjacentHTML('beforeend', newPhotoField);
    });

    // Eliminar requisito de foto
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-photo-requirement')) {
            const wrapper = event.target.closest('.photo-requirement-wrapper');
            wrapper.remove();
            
            // Renumerar los índices
            const wrappers = photoRequirements.querySelectorAll('.photo-requirement-wrapper');
            wrappers.forEach((wrapper, index) => {
                wrapper.dataset.index = index;
                const title = wrapper.querySelector('h6');
                title.innerHTML = `<i class="fas fa-camera me-2 text-primary"></i>Requisito de Foto #${index + 1}`;
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

    // Validar edificio
    edificioSelect.addEventListener('change', function() {
        validateField(this, 'edificio_id-error', function(value) {
            return value !== '';
        });
    });

    // Validar nombre
    nombreInput.addEventListener('input', function() {
        validateField(this, 'nombre-error', function(value) {
            return value.length >= 3 && value.length <= 255;
        });
    });

    // Validar descripción
    descripcionInput.addEventListener('input', function() {
        validateField(this, 'descripcion-error', function(value) {
            return value.length === 0 || value.length <= 1000;
        });
    });

    // Validación del formulario
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validar edificio
        if (!validateField(edificioSelect, 'edificio_id-error', function(value) {
            return value !== '';
        })) {
            isValid = false;
        }
        
        // Validar nombre
        if (!validateField(nombreInput, 'nombre-error', function(value) {
            return value.length >= 3 && value.length <= 255;
        })) {
            isValid = false;
        }
        
        // Validar descripción
        if (!validateField(descripcionInput, 'descripcion-error', function(value) {
            return value.length === 0 || value.length <= 1000;
        })) {
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Formulario incompleto',
                text: 'Por favor, completa todos los campos obligatorios correctamente',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
        
        // Mostrar confirmación
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
        
        Swal.fire({
            title: 'Creando checklist...',
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
