@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-user-plus me-2 text-primary"></i>
                        Nuevo Empleado
                    </h1>
                    <p class="text-muted mb-0">Registra un nuevo empleado en el sistema</p>
                </div>
                <a href="{{ route('admin.empleados.index') }}" class="btn btn-outline-secondary">
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
                        <i class="fas fa-user-edit me-2 text-primary"></i>
                        Información del Empleado
                    </h5>
                </div>
                
                <div class="card-body p-4">
                    <form action="{{ route('admin.empleados.store') }}" method="POST" id="empleado-form">
                        @csrf
                        
                        <!-- Información básica -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-signature me-2 text-primary"></i>
                                    Nombre Completo <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       placeholder="Ej: Juan Pérez García"
                                       value="{{ old('name') }}"
                                       required>
                                <div class="invalid-feedback" id="name-error">
                                    @error('name') {{ $message }} @enderror
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Nombre completo del empleado
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       placeholder="juan.perez@empresa.com"
                                       value="{{ old('email') }}"
                                       required>
                                <div class="invalid-feedback" id="email-error">
                                    @error('email') {{ $message }} @enderror
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Email único para acceso al sistema
                                </div>
                            </div>
                        </div>

                        <!-- Contraseñas -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-lock me-2 text-primary"></i>
                                    Contraseña <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Mínimo 8 caracteres"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="password-error">
                                    @error('password') {{ $message }} @enderror
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Mínimo 8 caracteres
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-lock me-2 text-primary"></i>
                                    Confirmar Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation" 
                                           placeholder="Repite la contraseña"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password-confirm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Debe coincidir con la contraseña
                                </div>
                            </div>
                        </div>

                        <!-- Rol y departamento -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label for="role" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-user-tag me-2 text-primary"></i>
                                    Rol <span class="text-danger">*</span>
                                </label>
                                <select name="role" 
                                        id="role" 
                                        class="form-select @error('role') is-invalid @enderror"
                                        required>
                                    <option value="">Seleccionar rol</option>
                                    @foreach($roles as $key => $value)
                                        <option value="{{ $key }}" {{ old('role') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="role-error">
                                    @error('role') {{ $message }} @enderror
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Define los permisos del empleado
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="departamento" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-building me-2 text-primary"></i>
                                    Departamento
                                </label>
                                <input type="text" 
                                       class="form-control @error('departamento') is-invalid @enderror" 
                                       id="departamento" 
                                       name="departamento" 
                                       placeholder="Ej: Limpieza, Mantenimiento"
                                       value="{{ old('departamento') }}">
                                <div class="invalid-feedback" id="departamento-error">
                                    @error('departamento') {{ $message }} @enderror
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Departamento al que pertenece
                                </div>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label for="telefono" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-phone me-2 text-primary"></i>
                                    Teléfono
                                </label>
                                <input type="tel" 
                                       class="form-control @error('telefono') is-invalid @enderror" 
                                       id="telefono" 
                                       name="telefono" 
                                       placeholder="+34 600 000 000"
                                       value="{{ old('telefono') }}">
                                <div class="invalid-feedback" id="telefono-error">
                                    @error('telefono') {{ $message }} @enderror
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Teléfono de contacto
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="fecha_contratacion" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-calendar-plus me-2 text-primary"></i>
                                    Fecha de Contratación
                                </label>
                                <input type="date" 
                                       class="form-control @error('fecha_contratacion') is-invalid @enderror" 
                                       id="fecha_contratacion" 
                                       name="fecha_contratacion" 
                                       value="{{ old('fecha_contratacion', date('Y-m-d')) }}">
                                <div class="invalid-feedback" id="fecha_contratacion-error">
                                    @error('fecha_contratacion') {{ $message }} @enderror
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Fecha de inicio en la empresa
                                </div>
                            </div>
                        </div>

                        <!-- Salario y estado -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label for="salario" class="form-label fw-semibold text-dark">
                                    <i class="fas fa-euro-sign me-2 text-primary"></i>
                                    Salario
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">€</span>
                                    <input type="number" 
                                           class="form-control @error('salario') is-invalid @enderror" 
                                           id="salario" 
                                           name="salario" 
                                           placeholder="0.00"
                                           step="0.01"
                                           min="0"
                                           value="{{ old('salario') }}">
                                </div>
                                <div class="invalid-feedback" id="salario-error">
                                    @error('salario') {{ $message }} @enderror
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Salario anual en euros
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mt-4 pt-3">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="activo" 
                                           name="activo" 
                                           value="1" 
                                           {{ old('activo', true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold text-dark" for="activo">
                                        <i class="fas fa-check-circle me-2 text-success"></i>
                                        Empleado activo
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    Los empleados activos pueden acceder al sistema
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex gap-3 pt-3">
                            <button type="submit" class="btn btn-primary btn-lg px-4" id="submit-btn">
                                <i class="fas fa-save me-2"></i>
                                Crear Empleado
                            </button>
                            <a href="{{ route('admin.empleados.index') }}" class="btn btn-outline-secondary btn-lg px-4">
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
                        Consejos para crear empleados
                    </h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Usa nombres completos y reales
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Asigna roles apropiados según las responsabilidades
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Establece contraseñas seguras y únicas
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Verifica que el email no esté duplicado
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>
                            Completa la información de contacto para emergencias
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
    const form = document.getElementById('empleado-form');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    const roleSelect = document.getElementById('role');
    const telefonoInput = document.getElementById('telefono');
    const departamentoInput = document.getElementById('departamento');
    const fechaContratacionInput = document.getElementById('fecha_contratacion');
    const salarioInput = document.getElementById('salario');
    const submitBtn = document.getElementById('submit-btn');

    // Toggle contraseñas
    document.getElementById('toggle-password').addEventListener('click', function() {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    document.getElementById('toggle-password-confirm').addEventListener('click', function() {
        const type = passwordConfirmInput.type === 'password' ? 'text' : 'password';
        passwordConfirmInput.type = type;
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
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
    nameInput.addEventListener('input', function() {
        validateField(this, 'name-error', function(value) {
            return value.length >= 3 && value.length <= 255;
        });
    });

    // Validar email
    emailInput.addEventListener('input', function() {
        validateField(this, 'email-error', function(value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(value);
        });
    });

    // Validar contraseña
    passwordInput.addEventListener('input', function() {
        validateField(this, 'password-error', function(value) {
            return value.length >= 8;
        });
        
        // Validar confirmación
        if (passwordConfirmInput.value) {
            validatePasswordConfirmation();
        }
    });

    // Validar confirmación de contraseña
    passwordConfirmInput.addEventListener('input', function() {
        validatePasswordConfirmation();
    });

    function validatePasswordConfirmation() {
        const password = passwordInput.value;
        const confirmation = passwordConfirmInput.value;
        
        if (confirmation && password !== confirmation) {
            passwordConfirmInput.classList.remove('is-valid');
            passwordConfirmInput.classList.add('is-invalid');
        } else if (confirmation && password === confirmation) {
            passwordConfirmInput.classList.remove('is-invalid');
            passwordConfirmInput.classList.add('is-valid');
        }
    }

    // Validar teléfono
    telefonoInput.addEventListener('input', function() {
        validateField(this, 'telefono-error', function(value) {
            return value.length === 0 || value.length <= 20;
        });
    });

    // Validar departamento
    departamentoInput.addEventListener('input', function() {
        validateField(this, 'departamento-error', function(value) {
            return value.length === 0 || value.length <= 100;
        });
    });

    // Validar salario
    salarioInput.addEventListener('input', function() {
        validateField(this, 'salario-error', function(value) {
            if (value === '') return true;
            const num = parseFloat(value);
            return !isNaN(num) && num >= 0;
        });
    });

    // Validación del formulario
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validar nombre
        if (!validateField(nameInput, 'name-error', function(value) {
            return value.length >= 3 && value.length <= 255;
        })) {
            isValid = false;
        }
        
        // Validar email
        if (!validateField(emailInput, 'email-error', function(value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(value);
        })) {
            isValid = false;
        }
        
        // Validar contraseña
        if (!validateField(passwordInput, 'password-error', function(value) {
            return value.length >= 8;
        })) {
            isValid = false;
        }
        
        // Validar confirmación de contraseña
        if (passwordInput.value !== passwordConfirmInput.value) {
            passwordConfirmInput.classList.remove('is-valid');
            passwordConfirmInput.classList.add('is-invalid');
            isValid = false;
        }
        
        // Validar rol
        if (!roleSelect.value) {
            roleSelect.classList.remove('is-valid');
            roleSelect.classList.add('is-invalid');
            isValid = false;
        } else {
            roleSelect.classList.remove('is-invalid');
            roleSelect.classList.add('is-valid');
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
            title: 'Creando empleado...',
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
