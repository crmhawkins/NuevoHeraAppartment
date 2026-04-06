@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-clipboard-check me-2 text-primary"></i>
                        {{ $checklist->nombre }}
                    </h1>
                    <p class="text-muted mb-0">Información detallada del checklist</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.checklists.edit', $checklist->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('admin.checklists.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna principal -->
        <div class="col-lg-8">
            <!-- Información del checklist -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        Información General
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-lg bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-clipboard-check fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 fw-bold text-dark">{{ $checklist->nombre }}</h4>
                                    <p class="text-muted mb-0">Checklist #{{ $checklist->id }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded">
                                <h6 class="fw-semibold text-dark mb-2">Edificio Asignado</h6>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-building me-2 text-info"></i>
                                    <span class="fw-semibold text-dark">{{ $checklist->edificio->nombre }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($checklist->descripcion)
                        <hr class="my-4">
                        <div class="mb-3">
                            <h6 class="fw-semibold text-dark mb-2">
                                <i class="fas fa-align-left me-2 text-primary"></i>
                                Descripción
                            </h6>
                            <p class="text-muted mb-0">{{ $checklist->descripcion }}</p>
                        </div>
                    @endif
                    
                    <hr class="my-4">
                    
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-primary-subtle p-3 rounded mb-2">
                                    <i class="fas fa-tags fa-2x text-primary"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Tipo</h6>
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
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-success-subtle p-3 rounded mb-2">
                                    <i class="fas fa-calendar-plus fa-2x text-success"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Fecha de Creación</h6>
                                <p class="text-muted mb-0">{{ $checklist->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-info-subtle p-3 rounded mb-2">
                                    <i class="fas fa-calendar-check fa-2x text-info"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Última Actualización</h6>
                                <p class="text-muted mb-0">{{ $checklist->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-warning-subtle p-3 rounded mb-2">
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Antigüedad</h6>
                                <p class="text-muted mb-0">{{ $checklist->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items del checklist -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-list-check me-2 text-primary"></i>
                            Items del Checklist ({{ $checklist->items->count() }})
                        </h5>
                        <a href="{{ route('admin.itemsChecklist.create', ['id' => $checklist->id]) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Nuevo Item
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($checklist->items->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 py-3 px-4">ID</th>
                                        <th class="border-0 py-3 px-4">Nombre</th>
                                        <th class="border-0 py-3 px-4">Estado</th>
                                        <th class="border-0 py-3 px-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($checklist->items as $item)
                                        <tr class="align-middle">
                                            <td class="px-4 py-3">
                                                <span class="badge bg-light text-dark fw-semibold">#{{ $item->id }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-check-square text-info"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold text-dark">{{ $item->nombre }}</h6>
                                                        <small class="text-muted">Creado {{ $item->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    Activo
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.itemsChecklist.edit', $item->id) }}" 
                                                       class="btn btn-sm btn-outline-secondary" 
                                                       title="Editar item">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger delete-item-btn" 
                                                            data-id="{{ $item->id }}"
                                                            data-name="{{ $item->nombre }}"
                                                            title="Eliminar item">
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
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-list-check fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No hay items</h5>
                            <p class="text-muted mb-3">Este checklist aún no tiene items registrados.</p>
                            <a href="{{ route('admin.itemsChecklist.create', ['id' => $checklist->id]) }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primer Item
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Requisitos de fotos -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-camera me-2 text-primary"></i>
                        Requisitos de Fotos ({{ $checklist->photoRequirements->count() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($checklist->photoRequirements->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 py-3 px-4">Nombre</th>
                                        <th class="border-0 py-3 px-4">Descripción</th>
                                        <th class="border-0 py-3 px-4">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($checklist->photoRequirements as $requirement)
                                        <tr class="align-middle">
                                            <td class="px-4 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-camera text-warning"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold text-dark">{{ $requirement->nombre }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-muted">
                                                    {{ $requirement->descripcion ?? 'Sin descripción' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-image me-1"></i>
                                                    {{ $requirement->cantidad }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-camera fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No hay requisitos de fotos</h5>
                            <p class="text-muted mb-3">Este checklist no tiene requisitos de fotos definidos.</p>
                        </div>
                    @endif
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
                                <i class="fas fa-list-check fa-2x text-primary mb-2"></i>
                                <h3 class="mb-1 fw-bold text-primary">{{ $totalItems }}</h3>
                                <small class="text-muted">Items</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-warning-subtle rounded">
                                <i class="fas fa-camera fa-2x text-warning mb-2"></i>
                                <h3 class="mb-1 fw-bold text-warning">{{ $totalPhotoRequirements }}</h3>
                                <small class="text-muted">Fotos</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-success-subtle rounded">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h3 class="mb-1 fw-bold text-success">{{ $itemsCompletados }}</h3>
                                <small class="text-muted">Completados</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-info-subtle rounded">
                                <i class="fas fa-percentage fa-2x text-info mb-2"></i>
                                <h3 class="mb-1 fw-bold text-info">{{ $porcentajeCompletado }}%</h3>
                                <small class="text-muted">Progreso</small>
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
                        <a href="{{ route('admin.itemsChecklist.create', ['id' => $checklist->id]) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nuevo Item
                        </a>
                        <a href="{{ route('admin.checklists.edit', $checklist->id) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-edit me-2"></i>Editar Checklist
                        </a>
                        <a href="{{ route('admin.itemsChecklist.index', ['id' => $checklist->id]) }}" class="btn btn-outline-info">
                            <i class="fas fa-list-check me-2"></i>Gestionar Items
                        </a>
                        <form action="{{ route('admin.checklists.toggle-status', $checklist->id) }}" 
                              method="POST" 
                              style="display: inline;">
                            @csrf
                            <button type="submit" 
                                    class="btn btn-outline-{{ $checklist->activo ? 'warning' : 'success' }}">
                                <i class="fas fa-{{ $checklist->activo ? 'pause' : 'play' }} me-2"></i>
                                {{ $checklist->activo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        <button type="button" 
                                class="btn btn-outline-danger delete-btn" 
                                data-id="{{ $checklist->id }}"
                                data-name="{{ $checklist->nombre }}">
                            <i class="fas fa-trash me-2"></i>Eliminar Checklist
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
                        <small class="text-muted d-block">ID del Checklist</small>
                        <strong class="text-dark">#{{ $checklist->id }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Edificio ID</small>
                        <strong class="text-dark">#{{ $checklist->edificio_id }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Estado</small>
                        @if($checklist->activo)
                            <span class="badge bg-success-subtle text-success">
                                <i class="fas fa-check-circle me-1"></i>Activo
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">
                                <i class="fas fa-pause-circle me-1"></i>Inactivo
                            </span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Fecha de Creación</small>
                        <strong class="text-dark">{{ $checklist->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Última Actualización</small>
                        <strong class="text-dark">{{ $checklist->updated_at->format('d/m/Y H:i') }}</strong>
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
    // Botón de eliminar checklist
    const deleteBtn = document.querySelector('.delete-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            const checklistId = this.dataset.id;
            const checklistName = this.dataset.name;
            
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
                    form.action = `{{ route('admin.checklists.destroy', '') }}/${checklistId}`;
                    form.submit();
                }
            });
        });
    }

    // Botones de eliminar items
    const deleteItemButtons = document.querySelectorAll('.delete-item-btn');
    deleteItemButtons.forEach(button => {
        button.addEventListener('click', function () {
            const itemId = this.dataset.id;
            const itemName = this.dataset.name;
            
            Swal.fire({
                title: '¿Eliminar item?',
                html: `¿Estás seguro de que quieres eliminar <strong>${itemName}</strong>?<br><br>
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
                    // Aquí deberías hacer la llamada para eliminar el item
                    // Por ahora solo mostramos un mensaje
                    Swal.fire({
                        icon: 'info',
                        title: 'Función no implementada',
                        text: 'La eliminación de items se maneja desde la vista de gestión de items.',
                        confirmButtonColor: '#6c757d'
                    });
                }
            });
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
});
</script>
@endsection
