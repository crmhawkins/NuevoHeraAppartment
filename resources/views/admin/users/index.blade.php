@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header con estadísticas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-users-cog me-2 text-primary"></i>
                        Gestión de Empleados
                    </h1>
                    <p class="text-muted mb-0">Administra todo el personal de la empresa</p>
                </div>
                <a href="{{ route('admin.empleados.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Empleado
                </a>
            </div>
        </div>
    </div>

    <!-- Dashboard de estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary-subtle">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-users fa-2x text-white"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-primary">{{ $totalEmpleados }}</h3>
                            <p class="text-muted mb-0">Total Empleados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success-subtle">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-success rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-user-check fa-2x text-white"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-success">{{ $empleadosActivos }}</h3>
                            <p class="text-muted mb-0">Empleados Activos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-warning-subtle">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-warning rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-user-clock fa-2x text-white"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-warning">{{ $empleadosInactivos }}</h3>
                            <p class="text-muted mb-0">Empleados Inactivos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-info-subtle">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-info rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-tags fa-2x text-white"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-info">{{ $rolesDisponibles->count() }}</h3>
                            <p class="text-muted mb-0">Roles Disponibles</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.empleados.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" 
                               class="form-control border-start-0 ps-0" 
                               name="search" 
                               placeholder="Buscar por nombre o email..." 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="">Todos los roles</option>
                        @foreach($rolesDisponibles as $role)
                            <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="active" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="1" {{ request('active') == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ request('active') == '0' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        @if(request('search') || request('role') || request('active'))
                            <a href="{{ route('admin.empleados.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Acciones masivas -->
    <div class="card shadow-sm border-0 mb-4" id="bulk-actions" style="display: none;">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <i class="fas fa-users me-2 text-primary"></i>
                        <span id="selected-count">0</span> empleados seleccionados
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="bulkAction('activate')">
                        <i class="fas fa-check me-2"></i>Activar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="bulkAction('deactivate')">
                        <i class="fas fa-pause me-2"></i>Desactivar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkAction('delete')">
                        <i class="fas fa-trash me-2"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de empleados -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Empleados ({{ $users->total() }})
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        <i class="fas fa-users me-1"></i>
                        {{ $users->count() }} mostrados
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleBulkSelection()">
                        <i class="fas fa-check-square me-2"></i>Selección Múltiple
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 py-3 px-4" style="width: 50px;">
                                    <div class="form-check" id="select-all-container" style="display: none;">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <a href="{{ route('admin.empleados.index', ['sort' => 'name', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search'), 'role' => request('role'), 'active' => request('active')]) }}"
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <span class="fw-semibold">Empleado</span>
                                        @if(request('sort') == 'name')
                                            <i class="fas fa-sort-{{ request('order', 'asc') == 'asc' ? 'up' : 'down' }} ms-2 text-primary"></i>
                                        @else
                                            <i class="fas fa-sort ms-2 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <a href="{{ route('admin.empleados.index', ['sort' => 'role', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search'), 'role' => request('role'), 'active' => request('active')]) }}"
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <span class="fw-semibold">Rol</span>
                                        @if(request('sort') == 'role')
                                            <i class="fas fa-sort-{{ request('order', 'asc') == 'asc' ? 'up' : 'down' }} ms-2 text-primary"></i>
                                        @else
                                            <i class="fas fa-sort ms-2 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Estado</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Último Acceso</span>
                                </th>
                                <th class="border-0 py-3 px-4 text-center" style="width: 250px;">
                                    <span class="fw-semibold">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="align-middle">
                                    <td class="px-4 py-3">
                                        <div class="form-check bulk-checkbox" style="display: none;">
                                            <input class="form-check-input" type="checkbox" value="{{ $user->id }}">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark">{{ $user->name }}</h6>
                                                <small class="text-muted">{{ $user->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
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
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($user->inactive)
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="fas fa-pause-circle me-1"></i>
                                                Inactivo
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Activo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <small class="text-muted">
                                            @if($user->last_login_at)
                                                {{ \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() }}
                                            @else
                                                Nunca
                                            @endif
                                        </small>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.empleados.show', $user->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver empleado">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.empleados.edit', $user->id) }}" 
                                               class="btn btn-sm btn-outline-secondary" 
                                               title="Editar empleado">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.empleados.toggle-status', $user->id) }}" 
                                                  method="POST" 
                                                  style="display: inline;">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-{{ $user->inactive ? 'success' : 'warning' }}" 
                                                        title="{{ $user->inactive ? 'Activar' : 'Desactivar' }}">
                                                    <i class="fas fa-{{ $user->inactive ? 'play' : 'pause' }}"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.empleados.reset-password', $user->id) }}" 
                                                  method="POST" 
                                                  style="display: inline;">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-info" 
                                                        title="Restablecer contraseña">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                            </form>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger delete-btn" 
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->name }}"
                                                    title="Eliminar empleado">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Estado vacío -->
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-users fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted mb-2">No se encontraron empleados</h5>
                    <p class="text-muted mb-3">
                        @if(request('search') || request('role') || request('active'))
                            No hay resultados para los filtros aplicados. Intenta con otros criterios.
                        @else
                            Comienza creando tu primer empleado.
                        @endif
                    </p>
                    @if(!request('search') && !request('role') && !request('active'))
                        <a href="{{ route('admin.empleados.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Primer Empleado
                        </a>
                    @endif
                </div>
            @endif
        </div>
        
        @if($users->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="text-muted mb-0">
                        Mostrando {{ $users->firstItem() }} a {{ $users->lastItem() }} de {{ $users->total() }} resultados
                    </p>
                    {{ $users->appends(['search' => request('search'), 'role' => request('role'), 'active' => request('active'), 'sort' => $sort, 'order' => $order])->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Formulario oculto para eliminar -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Formulario oculto para acciones masivas -->
<form id="bulk-action-form" method="POST" action="{{ route('admin.empleados.bulk-action') }}" style="display: none;">
    @csrf
    <input type="hidden" name="action" id="bulk-action-type">
    <input type="hidden" name="users" id="bulk-action-users">
</form>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let bulkSelectionMode = false;

    // Botones de eliminar
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
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
    });

    // Toggle selección múltiple
    window.toggleBulkSelection = function() {
        bulkSelectionMode = !bulkSelectionMode;
        const checkboxes = document.querySelectorAll('.bulk-checkbox');
        const selectAllContainer = document.getElementById('select-all-container');
        const bulkActions = document.getElementById('bulk-actions');
        
        if (bulkSelectionMode) {
            checkboxes.forEach(cb => cb.style.display = 'block');
            selectAllContainer.style.display = 'block';
            bulkActions.style.display = 'block';
        } else {
            checkboxes.forEach(cb => {
                cb.style.display = 'none';
                cb.checked = false;
            });
            document.getElementById('select-all').checked = false;
            selectAllContainer.style.display = 'none';
            bulkActions.style.display = 'none';
            updateSelectedCount();
        }
    };

    // Seleccionar todos
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.bulk-checkbox input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });

    // Actualizar contador de seleccionados
    function updateSelectedCount() {
        const selectedCheckboxes = document.querySelectorAll('.bulk-checkbox input[type="checkbox"]:checked');
        const count = selectedCheckboxes.length;
        document.getElementById('selected-count').textContent = count;
        
        if (count === 0) {
            document.getElementById('bulk-actions').style.display = 'none';
        }
    }

    // Event listeners para checkboxes individuales
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('bulk-checkbox')) {
            updateSelectedCount();
        }
    });

    // Acciones masivas
    window.bulkAction = function(action) {
        const selectedCheckboxes = document.querySelectorAll('.bulk-checkbox input[type="checkbox"]:checked');
        const userIds = Array.from(selectedCheckboxes).map(cb => cb.value);
        
        if (userIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin selección',
                text: 'Debes seleccionar al menos un empleado.',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        let actionText = '';
        let confirmText = '';
        let icon = 'warning';
        
        switch(action) {
            case 'activate':
                actionText = 'activar';
                confirmText = `¿Estás seguro de que quieres activar ${userIds.length} empleado(s)?`;
                icon = 'question';
                break;
            case 'deactivate':
                actionText = 'desactivar';
                confirmText = `¿Estás seguro de que quieres desactivar ${userIds.length} empleado(s)?`;
                icon = 'question';
                break;
            case 'delete':
                actionText = 'eliminar';
                confirmText = `¿Estás seguro de que quieres eliminar ${userIds.length} empleado(s)? Esta acción no se puede deshacer.`;
                icon = 'warning';
                break;
        }

        Swal.fire({
            title: `Confirmar ${actionText}`,
            text: confirmText,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: action === 'delete' ? '#dc3545' : '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${actionText}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('bulk-action-type').value = action;
                document.getElementById('bulk-action-users').value = JSON.stringify(userIds);
                document.getElementById('bulk-action-form').submit();
            }
        });
    };

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
