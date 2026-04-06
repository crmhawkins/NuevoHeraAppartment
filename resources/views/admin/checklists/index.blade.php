@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header con estadísticas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-clipboard-check me-2 text-primary"></i>
                        Gestión de Checklists
                    </h1>
                    <p class="text-muted mb-0">Administra todos los checklists de limpieza y mantenimiento</p>
                </div>
                <a href="{{ route('admin.checklists.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Checklist
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.checklists.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" 
                               class="form-control border-start-0 ps-0" 
                               name="search" 
                               placeholder="Buscar por nombre o edificio..." 
                               value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="edificio_id" class="form-select">
                        <option value="">Todos los edificios</option>
                        @foreach($edificios as $edificio)
                            <option value="{{ $edificio->id }}" {{ request('edificio_id') == $edificio->id ? 'selected' : '' }}>
                                {{ $edificio->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        @if($search || request('edificio_id'))
                            <a href="{{ route('admin.checklists.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de checklists -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Checklists ({{ $checklists->total() }})
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        <i class="fas fa-clipboard-check me-1"></i>
                        {{ $checklists->count() }} mostrados
                    </span>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            @if($checklists->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 py-3 px-4">
                                    <a href="{{ route('admin.checklists.index', ['sort' => 'nombre', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search'), 'edificio_id' => request('edificio_id')]) }}"
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <span class="fw-semibold">Nombre</span>
                                        @if(request('sort') == 'nombre')
                                            <i class="fas fa-sort-{{ request('order', 'asc') == 'asc' ? 'up' : 'down' }} ms-2 text-primary"></i>
                                        @else
                                            <i class="fas fa-sort ms-2 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <a href="{{ route('admin.checklists.index', ['sort' => 'edificio_id', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search'), 'edificio_id' => request('edificio_id')]) }}"
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <span class="fw-semibold">Edificio</span>
                                        @if(request('sort') == 'edificio_id')
                                            <i class="fas fa-sort-{{ request('order', 'asc') == 'asc' ? 'up' : 'down' }} ms-2 text-primary"></i>
                                        @else
                                            <i class="fas fa-sort ms-2 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Tipo</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Items</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Estado</span>
                                </th>
                                <th class="border-0 py-3 px-4 text-center" style="width: 250px;">
                                    <span class="fw-semibold">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($checklists as $checklist)
                                <tr class="align-middle">
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-clipboard-check text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark">{{ $checklist->nombre }}</h6>
                                                <small class="text-muted">
                                                    @if($checklist->descripcion)
                                                        {{ Str::limit($checklist->descripcion, 50) }}
                                                    @else
                                                        Sin descripción
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-building text-info"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark">{{ $checklist->edificio->nombre }}</h6>
                                                <small class="text-muted">ID: {{ $checklist->edificio->id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $tipoColors = [
                                                'limpieza' => 'success',
                                                'mantenimiento' => 'warning',
                                                'inspeccion' => 'info',
                                                'seguridad' => 'danger'
                                            ];
                                            $tipoColor = $tipoColors[$checklist->tipo ?? 'limpieza'] ?? 'secondary';
                                            $tipoIcon = [
                                                'limpieza' => 'broom',
                                                'mantenimiento' => 'wrench',
                                                'inspeccion' => 'search',
                                                'seguridad' => 'shield-alt'
                                            ];
                                            $tipoIconName = $tipoIcon[$checklist->tipo ?? 'limpieza'] ?? 'question';
                                        @endphp
                                        <span class="badge bg-{{ $tipoColor }}-subtle text-{{ $tipoColor }}">
                                            <i class="fas fa-{{ $tipoIconName }} me-1"></i>
                                            {{ ucfirst($checklist->tipo ?? 'limpieza') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-list me-1"></i>
                                            {{ $checklist->items->count() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($checklist->activo)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Activo
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="fas fa-pause-circle me-1"></i>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.checklists.show', $checklist->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver checklist">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.itemsChecklist.index', ['id' => $checklist->id]) }}" 
                                               class="btn btn-sm btn-outline-info" 
                                               title="Ver comprobaciones">
                                                <i class="fas fa-list-check"></i>
                                            </a>
                                            <a href="{{ route('admin.checklists.edit', $checklist->id) }}" 
                                               class="btn btn-sm btn-outline-secondary" 
                                               title="Editar checklist">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.checklists.toggle-status', $checklist->id) }}" 
                                                  method="POST" 
                                                  style="display: inline;">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-{{ $checklist->activo ? 'warning' : 'success' }}" 
                                                        title="{{ $checklist->activo ? 'Desactivar' : 'Activar' }}">
                                                    <i class="fas fa-{{ $checklist->activo ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger delete-btn" 
                                                    data-id="{{ $checklist->id }}"
                                                    data-name="{{ $checklist->nombre }}"
                                                    title="Eliminar checklist">
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
                        <i class="fas fa-clipboard-check fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted mb-2">No se encontraron checklists</h5>
                    <p class="text-muted mb-3">
                        @if($search || request('edificio_id'))
                            No hay resultados para los filtros aplicados. Intenta con otros criterios.
                        @else
                            Comienza creando tu primer checklist.
                        @endif
                    </p>
                    @if(!$search && !request('edificio_id'))
                        <a href="{{ route('admin.checklists.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Primer Checklist
                        </a>
                    @endif
                </div>
            @endif
        </div>
        
        @if($checklists->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="text-muted mb-0">
                        Mostrando {{ $checklists->firstItem() }} a {{ $checklists->lastItem() }} de {{ $checklists->total() }} resultados
                    </p>
                    {{ $checklists->appends(['search' => $search, 'sort' => $sort, 'order' => $order, 'edificio_id' => request('edificio_id')])->links('pagination::bootstrap-5') }}
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
@endsection

@include('sweetalert::alert')

<!-- Script inline para eliminar checklists -->
<script>
// Esperar a que todo esté cargado
setTimeout(function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const checklistId = this.dataset.id;
            const checklistName = this.dataset.name;
            
            // Mostrar confirmación con SweetAlert
            Swal.fire({
                title: '¿Eliminar checklist?',
                html: `¿Estás seguro de que quieres eliminar <strong>${checklistName}</strong>?<br><br>
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
                    if (form && checklistId) {
                        // Construir la URL correctamente
                        const baseUrl = '{{ url("/checklists") }}';
                        form.action = baseUrl + '/' + checklistId + '/destroy';
                        form.submit();
                    }
                }
            });
        });
    });
}, 1000);
</script>

@section('scripts')
<script>
// Mostrar mensajes de sesión con SweetAlert
@if(session('swal_success'))
    if (typeof Swal !== 'undefined') {
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
    }
@endif

@if(session('swal_error'))
    if (typeof Swal !== 'undefined') {
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
    }
@endif
</script>
@endsection

