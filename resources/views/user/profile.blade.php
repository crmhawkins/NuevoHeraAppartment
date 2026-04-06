@extends('layouts.appPersonal')

@section('title', 'Mi Perfil')

@section('content')
<div class="user-profile-container">
    <!-- Header del Perfil -->
    <div class="profile-header">
        <div class="profile-avatar">
            @if($user->avatar)
                <img src="{{ asset('storage/' . $user->avatar) }}" alt="Avatar de {{ $user->name }}" class="user-avatar">
            @else
                <i class="fas fa-user-circle"></i>
            @endif
        </div>
        <div class="profile-info">
            <h1 class="profile-name">{{ $user->name }}</h1>
            <p class="profile-role">{{ ucfirst(strtolower($user->role)) }}</p>
            <p class="profile-email">{{ $user->email }}</p>
        </div>
    </div>

    <!-- Estadísticas del Usuario -->
    <div class="stats-section">
        <h2 class="section-title">
            <i class="fas fa-chart-bar"></i>
            Estadísticas del Mes
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-primary">
                    <i class="fas fa-broom"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $stats['limpiezas_mes'] }}</div>
                    <div class="stat-label">Limpiezas</div>
                    <small class="stat-detail">{{ $stats['limpiezas_completadas_mes'] }} completadas</small>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-success">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $stats['horas_trabajadas_mes'] }}</div>
                    <div class="stat-label">Horas Trabajadas</div>
                    <small class="stat-detail">{{ $stats['dias_trabajados_mes'] }} días</small>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $stats['incidencias_total'] }}</div>
                    <div class="stat-label">Incidencias</div>
                    <small class="stat-detail">{{ $stats['incidencias_resueltas_total'] }} resueltas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Perfil -->
    <div class="profile-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-user-edit"></i>
                Información Personal
            </h2>
            <button type="button" class="apple-btn apple-btn-primary" onclick="toggleEditMode()">
                <i class="fas fa-edit"></i>
                <span id="editButtonText">Editar</span>
            </button>
        </div>
        
        <form action="{{ route('user.profile.update') }}" method="POST" id="profileForm">
            @csrf
            <div class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nombre Completo</label>
                        <input type="text" id="name" name="name" value="{{ $user->name }}" class="form-control" disabled required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ $user->email }}" class="form-control" disabled required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="tel" id="phone" name="phone" value="{{ $user->phone ?? '' }}" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label for="birth_date">Fecha de Nacimiento</label>
                        <input type="date" id="birth_date" name="birth_date" value="{{ $user->birth_date ?? '' }}" class="form-control" disabled>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Dirección</label>
                    <textarea id="address" name="address" class="form-control" disabled rows="2">{{ $user->address ?? '' }}</textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="emergency_contact">Contacto de Emergencia</label>
                        <input type="text" id="emergency_contact" name="emergency_contact" value="{{ $user->emergency_contact ?? '' }}" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label for="emergency_phone">Teléfono de Emergencia</label>
                        <input type="tel" id="emergency_phone" name="emergency_phone" value="{{ $user->emergency_phone ?? '' }}" class="form-control" disabled>
                    </div>
                </div>
                
                <div class="form-actions" id="formActions" style="display: none;">
                    <button type="submit" class="apple-btn apple-btn-success">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <button type="button" class="apple-btn apple-btn-secondary" onclick="cancelEdit()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Cambio de Contraseña -->
    <div class="profile-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-lock"></i>
                Cambio de Contraseña
            </h2>
        </div>
        
        <form action="{{ route('user.profile.password') }}" method="POST" id="passwordForm">
            @csrf
            <div class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="current_password">Contraseña Actual</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Nueva Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirmar Nueva Contraseña</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="apple-btn apple-btn-warning">
                        <i class="fas fa-key"></i>
                        Cambiar Contraseña
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Cambio de Avatar -->
    <div class="profile-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-image"></i>
                Avatar del Perfil
            </h2>
        </div>
        
        <form action="{{ route('user.profile.avatar') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
            @csrf
            <div class="profile-form">
                <div class="avatar-preview">
                    @if($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="Avatar actual" class="current-avatar">
                    @else
                        <div class="default-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    @endif
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="avatar">Seleccionar Nueva Imagen</label>
                        <input type="file" id="avatar" name="avatar" class="form-control" accept="image/*" required>
                        <small class="form-text">Formatos: JPEG, PNG, JPG, GIF. Máximo: 2MB</small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="apple-btn apple-btn-info">
                        <i class="fas fa-upload"></i>
                        Actualizar Avatar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Gestión de Vacaciones -->
    <div class="vacations-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-umbrella-beach"></i>
                Gestión de Vacaciones
            </h2>
            <a href="{{ route('holiday.create') }}" class="apple-btn apple-btn-primary">
                <i class="fas fa-plus"></i>
                <span>Hacer Petición de Vacaciones</span>
            </a>
        </div>
        
        <!-- Mostrar solo las estadísticas, sin formulario de edición -->
        <div class="vacation-stats">
            <div class="vacation-stat">
                <div class="vacation-number">{{ $vacationStats['dias_totales'] }}</div>
                <div class="vacation-label">Días Totales</div>
            </div>
            <div class="vacation-stat">
                <div class="vacation-number">{{ $vacationStats['dias_usados'] }}</div>
                <div class="vacation-label">Días Usados</div>
            </div>
            <div class="vacation-stat">
                <div class="vacation-number">{{ $vacationStats['dias_restantes'] }}</div>
                <div class="vacation-label">Días Restantes</div>
            </div>
        </div>
        
        @if($vacationStats['proximo_periodo'])
        <div class="next-vacation">
            <h3>Próximo Período de Vacaciones</h3>
            <div class="vacation-period">
                <div class="vacation-date">
                    <i class="fas fa-calendar"></i>
                    <span>{{ $vacationStats['proximo_periodo']['fecha_inicio'] }} - {{ $vacationStats['proximo_periodo']['fecha_fin'] }}</span>
                </div>
                <div class="vacation-days">
                    <i class="fas fa-clock"></i>
                    <span>{{ $vacationStats['proximo_periodo']['dias'] }} días</span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Historial de Vacaciones -->
    <div class="vacations-history">
        <h2 class="section-title">
            <i class="fas fa-history"></i>
            Historial de Vacaciones
        </h2>
        
        @if($vacations->count() > 0)
            <div class="vacations-list">
                @foreach($vacations as $vacation)
                <div class="vacation-item">
                    <div class="vacation-header">
                        <div class="vacation-dates">
                            <i class="fas fa-calendar"></i>
                            <span>{{ \Carbon\Carbon::parse($vacation->from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($vacation->to)->format('d/m/Y') }}</span>
                        </div>
                        <div class="vacation-status status-{{ strtolower($vacation->holidays_status_id == 1 ? 'aceptadas' : ($vacation->holidays_status_id == 2 ? 'denegadas' : 'pendientes')) }}">
                            {{ $vacation->holidays_status_id == 1 ? 'Aceptadas' : ($vacation->holidays_status_id == 2 ? 'Denegadas' : 'Pendientes') }}
                        </div>
                    </div>
                    <div class="vacation-details">
                        <div class="vacation-days">
                            <i class="fas fa-clock"></i>
                            <span>{{ $vacation->total_days }} días</span>
                        </div>
                        @if($vacation->half_day)
                        <div class="vacation-half-day">
                            <i class="fas fa-info-circle"></i>
                            <span>Incluye medio día</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-umbrella-beach"></i>
                <p>No hay solicitudes de vacaciones registradas</p>
            </div>
        @endif
    </div>
</div>

<!-- Alertas de éxito/error -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/user-profile.css') }}">
@endpush

@push('scripts')
<script>
let editMode = false;

function toggleEditMode() {
    editMode = !editMode;
    const inputs = document.querySelectorAll('#profileForm input, #profileForm textarea');
    const editButton = document.getElementById('editButtonText');
    const formActions = document.getElementById('formActions');
    
    inputs.forEach(input => {
        input.disabled = !editMode;
    });
    
    if (editMode) {
        editButton.textContent = 'Cancelar';
        formActions.style.display = 'flex';
    } else {
        editButton.textContent = 'Editar';
        formActions.style.display = 'none';
    }
}

function cancelEdit() {
    editMode = false;
    const inputs = document.querySelectorAll('#profileForm input, #profileForm textarea');
    const editButton = document.getElementById('editButtonText');
    const formActions = document.getElementById('formActions');
    
    inputs.forEach(input => {
        input.disabled = true;
    });
    
    editButton.textContent = 'Editar';
    formActions.style.display = 'none';
}

// Validación de contraseña
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 8 caracteres');
        return false;
    }
    
    return true;
});

// Preview de avatar
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.current-avatar') || document.querySelector('.default-avatar');
            if (preview) {
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    // Si es el div por defecto, crear una imagen
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'current-avatar';
                    img.alt = 'Preview del avatar';
                    preview.parentNode.replaceChild(img, preview);
                }
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
