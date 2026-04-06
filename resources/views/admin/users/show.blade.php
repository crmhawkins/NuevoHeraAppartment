@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-user me-2 text-primary"></i>
                        {{ $user->name }}
                    </h1>
                    <p class="text-muted mb-0">Información detallada del empleado</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.empleados.edit', $user->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('admin.empleados.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna principal -->
        <div class="col-lg-8">
            <!-- Información del empleado -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        Información General
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-lg bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-user fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 fw-bold text-dark">{{ $user->name }}</h4>
                                    <p class="text-muted mb-0">Empleado #{{ $user->id }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light p-3 rounded">
                                <h6 class="fw-semibold text-dark mb-2">Estado del Sistema</h6>
                                @if($user->inactive)
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="fas fa-pause-circle me-1"></i>Inactivo
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="fas fa-check-circle me-1"></i>Activo
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="fw-semibold text-dark mb-2">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    Email
                                </h6>
                                <p class="text-muted mb-0">{{ $user->email }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="fw-semibold text-dark mb-2">
                                    <i class="fas fa-user-tag me-2 text-primary"></i>
                                    Rol
                                </h6>
                                @php
                                    $roleColors = [
                                        'ADMIN' => 'danger',
                                        'USER' => 'primary',
                                        'LIMPIEZA' => 'success',
                                        'MANTENIMIENTO' => 'warning',
                                        'RECEPCION' => 'info',
                                        'SUPERVISOR' => 'secondary'
                                    ];
                                    $roleColor = $roleColors[$user->role] ?? 'secondary';
                                    $roleIcon = [
                                        'ADMIN' => 'shield-alt',
                                        'USER' => 'user',
                                        'LIMPIEZA' => 'broom',
                                        'MANTENIMIENTO' => 'wrench',
                                        'RECEPCION' => 'concierge-bell',
                                        'SUPERVISOR' => 'user-tie'
                                    ];
                                    $roleIconName = $roleIcon[$user->role] ?? 'user';
                                @endphp
                                <span class="badge bg-{{ $roleColor }}-subtle text-{{ $roleColor }}">
                                    <i class="fas fa-{{ $roleIconName }} me-1"></i>
                                    {{ ucfirst($user->role) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($user->departamento || $user->telefono)
                        <hr class="my-4">
                        <div class="row g-4">
                            @if($user->departamento)
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h6 class="fw-semibold text-dark mb-2">
                                            <i class="fas fa-building me-2 text-primary"></i>
                                            Departamento
                                        </h6>
                                        <p class="text-muted mb-0">{{ $user->departamento }}</p>
                                    </div>
                                </div>
                            @endif
                            @if($user->telefono)
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h6 class="fw-semibold text-dark mb-2">
                                            <i class="fas fa-phone me-2 text-primary"></i>
                                            Teléfono
                                        </h6>
                                        <p class="text-muted mb-0">{{ $user->telefono }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($user->fecha_contratacion || $user->salario)
                        <hr class="my-4">
                        <div class="row g-4">
                            @if($user->fecha_contratacion)
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h6 class="fw-semibold text-dark mb-2">
                                            <i class="fas fa-calendar-plus me-2 text-primary"></i>
                                            Fecha de Contratación
                                        </h6>
                                        <p class="text-muted mb-0">{{ \Carbon\Carbon::parse($user->fecha_contratacion)->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                            @endif
                            @if($user->salario)
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h6 class="fw-semibold text-dark mb-2">
                                            <i class="fas fa-euro-sign me-2 text-primary"></i>
                                            Salario
                                        </h6>
                                        <p class="text-muted mb-0">{{ number_format($user->salario, 2) }} €/año</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actividad del empleado -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Actividad del Empleado
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($totalFichajes > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 py-3 px-4">Fecha</th>
                                        <th class="border-0 py-3 px-4">Hora Inicio</th>
                                        <th class="border-0 py-3 px-4">Hora Fin</th>
                                        <th class="border-0 py-3 px-4">Duración</th>
                                        <th class="border-0 py-3 px-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($user->fichajes()->latest()->take(10)->get() as $fichaje)
                                        <tr class="align-middle">
                                            <td class="px-4 py-3">
                                                <small class="text-muted">
                                                    {{ $fichaje->created_at->format('d/m/Y') }}
                                                </small>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-success">
                                                    {{ $fichaje->hora_inicio ? \Carbon\Carbon::parse($fichaje->hora_inicio)->format('H:i') : 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-danger">
                                                    {{ $fichaje->hora_fin ? \Carbon\Carbon::parse($fichaje->hora_fin)->format('H:i') : 'En curso' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($fichaje->hora_inicio && $fichaje->hora_fin)
                                                    @php
                                                        $inicio = \Carbon\Carbon::parse($fichaje->hora_inicio);
                                                        $fin = \Carbon\Carbon::parse($fichaje->hora_fin);
                                                        $duracion = $inicio->diffInMinutes($fin);
                                                    @endphp
                                                    <span class="text-info">{{ $duracion }} min</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($fichaje->hora_fin)
                                                    <span class="badge bg-success-subtle text-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Completado
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        <i class="fas fa-clock me-1"></i>
                                                        En curso
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-chart-line fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">Sin actividad</h5>
                            <p class="text-muted mb-3">Este empleado aún no tiene fichajes registrados.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Historial de accesos -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-history me-2 text-primary"></i>
                        Historial de Accesos
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 py-3 px-4">Fecha</th>
                                    <th class="border-0 py-3 px-4">Hora</th>
                                    <th class="border-0 py-3 px-4">IP</th>
                                    <th class="border-0 py-3 px-4">Navegador</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($user->last_login_at)
                                    <tr class="align-middle">
                                        <td class="px-4 py-3">
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($user->last_login_at)->format('d/m/Y') }}
                                            </small>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-primary">
                                                {{ \Carbon\Carbon::parse($user->last_login_at)->format('H:i') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-muted">N/A</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-muted">N/A</span>
                                        </td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="fas fa-history fa-3x text-muted"></i>
                                            </div>
                                            <h5 class="text-muted mb-2">Sin accesos</h5>
                                            <p class="text-muted mb-3">Este empleado aún no ha accedido al sistema.</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar con estadísticas -->
        <div class="col-lg-4">
            <!-- Resumen estadístico -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>
                        Resumen Estadístico
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-primary-subtle rounded">
                                <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                                <h3 class="mb-1 fw-bold text-primary">{{ $totalFichajes }}</h3>
                                <small class="text-muted">Fichajes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-success-subtle rounded">
                                <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                <h3 class="mb-1 fw-bold text-success">{{ $fichajesEsteMes }}</h3>
                                <small class="text-muted">Este Mes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-info-subtle rounded">
                                <i class="fas fa-hourglass-half fa-2x text-info mb-2"></i>
                                <h3 class="mb-1 fw-bold text-info">{{ round($horasTrabajadas, 1) }}</h3>
                                <small class="text-muted">Horas</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-warning-subtle rounded">
                                <i class="fas fa-user-clock fa-2x text-warning mb-2"></i>
                                <h3 class="mb-1 fw-bold text-warning">{{ $user->created_at->diffInDays(now()) }}</h3>
                                <small class="text-muted">Días</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-bolt me-2 text-primary"></i>
                        Acciones Rápidas
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.empleados.edit', $user->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Editar Empleado
                        </a>
                        <a href="{{ route('admin.empleados.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>Ver Todos los Empleados
                        </a>
                        <form action="{{ route('admin.empleados.toggle-status', $user->id) }}" 
                              method="POST" 
                              style="display: inline;">
                            @csrf
                            <button type="submit" 
                                    class="btn btn-outline-{{ $user->inactive ? 'success' : 'warning' }}">
                                <i class="fas fa-{{ $user->inactive ? 'play' : 'pause' }} me-2"></i>
                                {{ $user->inactive ? 'Activar' : 'Desactivar' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.empleados.reset-password', $user->id) }}" 
                              method="POST" 
                              style="display: inline;">
                            @csrf
                            <button type="submit" 
                                    class="btn btn-outline-info">
                                <i class="fas fa-key me-2"></i>Restablecer Contraseña
                            </button>
                        </form>
                        <button type="button" 
                                class="btn btn-outline-danger delete-btn" 
                                data-id="{{ $user->id }}"
                                data-name="{{ $user->name }}">
                            <i class="fas fa-trash me-2"></i>Eliminar Empleado
                        </button>
                    </div>
                </div>
            </div>

            <!-- Información del sistema -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-cog me-2 text-primary"></i>
                        Información del Sistema
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <small class="text-muted d-block">ID del Empleado</small>
                        <strong class="text-dark">#{{ $user->id }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Estado</small>
                        @if($user->inactive)
                            <span class="badge bg-secondary-subtle text-secondary">
                                <i class="fas fa-pause-circle me-1"></i>Inactivo
                            </span>
                        @else
                            <span class="badge bg-success-subtle text-success">
                                <i class="fas fa-check-circle me-1"></i>Activo
                            </span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Email Verificado</small>
                        @if($user->email_verified_at)
                            <span class="badge bg-success-subtle text-success">
                                <i class="fas fa-check-circle me-1"></i>Sí
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>No
                            </span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Fecha de Creación</small>
                        <strong class="text-dark">{{ $user->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Última Actualización</small>
                        <strong class="text-dark">{{ $user->updated_at->format('d/m/Y H:i') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminar -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Botón de eliminar empleado
    const deleteBtn = document.querySelector('.delete-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            const userId = this.dataset.id;
            const userName = this.dataset.name;
            
            Swal.fire({
                title: '¿Eliminar empleado?',
                html: `¿Estás seguro de que quieres eliminar <strong>${userName}</strong>?<br><br>
                       <small class="text-muted">Esta acción no se puede deshacer.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, eliminar',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-form');
                    form.action = `{{ route('admin.empleados.destroy', '') }}/${userId}`;
                    form.submit();
                }
            });
        });
    }

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
});
</script>
@endsection
