@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-check-square me-2 text-primary"></i>
                        {{ $item->nombre }}
                    </h1>
                    <p class="text-muted mb-0">Información detallada del item</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.itemsChecklist.edit', $item->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('admin.itemsChecklist.index', ['id' => $item->checklist->id]) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna principal -->
        <div class="col-lg-8">
            <!-- Información del item -->
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
                                    <i class="fas fa-check-square fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 fw-bold text-dark">{{ $item->nombre }}</h4>
                                    <p class="text-muted mb-0">Item #{{ $item->id }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light p-3 rounded">
                                <h6 class="fw-semibold text-dark mb-2">Checklist Padre</h6>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clipboard-check me-2 text-info"></i>
                                    <span class="fw-semibold text-dark">{{ $item->checklist->nombre }}</span>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-building me-1"></i>
                                    {{ $item->checklist->edificio->nombre }}
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    @if($item->descripcion)
                        <hr class="my-4">
                        <div class="mb-3">
                            <h6 class="fw-semibold text-dark mb-2">
                                <i class="fas fa-align-left me-2 text-primary"></i>
                                Descripción
                            </h6>
                            <p class="text-muted mb-0">{{ $item->descripcion }}</p>
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
                                        'simple' => 'success',
                                        'multiple' => 'warning',
                                        'texto' => 'info',
                                        'foto' => 'danger'
                                    ];
                                    $tipoColor = $tipoColors[$item->tipo ?? 'simple'] ?? 'secondary';
                                    $tipoIcon = [
                                        'simple' => 'check-square',
                                        'multiple' => 'list-check',
                                        'texto' => 'font',
                                        'foto' => 'camera'
                                    ];
                                    $tipoIconName = $tipoIcon[$item->tipo ?? 'simple'] ?? 'question';
                                @endphp
                                <span class="badge bg-{{ $tipoColor }}-subtle text-{{ $tipoColor }}">
                                    <i class="fas fa-{{ $tipoIconName }} me-1"></i>
                                    {{ ucfirst($item->tipo ?? 'simple') }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-success-subtle p-3 rounded mb-2">
                                    <i class="fas fa-sort-numeric-up fa-2x text-success"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Orden</h6>
                                <span class="badge bg-light text-dark fw-semibold">
                                    {{ $item->orden ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-info-subtle p-3 rounded mb-2">
                                    <i class="fas fa-calendar-plus fa-2x text-info"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Fecha de Creación</h6>
                                <p class="text-muted mb-0">{{ $item->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-warning-subtle p-3 rounded mb-2">
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Antigüedad</h6>
                                <p class="text-muted mb-0">{{ $item->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Controles del item -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-clipboard-list me-2 text-primary"></i>
                        Controles del Item ({{ $totalControles }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($totalControles > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 py-3 px-4">ID</th>
                                        <th class="border-0 py-3 px-4">Estado</th>
                                        <th class="border-0 py-3 px-4">Fecha</th>
                                        <th class="border-0 py-3 px-4">Usuario</th>
                                        <th class="border-0 py-3 px-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($item->controles as $control)
                                        <tr class="align-middle">
                                            <td class="px-4 py-3">
                                                <span class="badge bg-light text-dark fw-semibold">#{{ $control->id }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($control->completado)
                                                    <span class="badge bg-success-subtle text-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Completado
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        <i class="fas fa-clock me-1"></i>
                                                        Pendiente
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <small class="text-muted">
                                                    {{ $control->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-muted">
                                                    {{ $control->usuario ?? 'Sistema' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="btn-group" role="group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-info" 
                                                            title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
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
                                <i class="fas fa-clipboard-list fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No hay controles</h5>
                            <p class="text-muted mb-3">Este item aún no tiene controles registrados.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Apartamentos asociados -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-building me-2 text-primary"></i>
                        Apartamentos Asociados
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($item->apartamentos->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 py-3 px-4">Apartamento</th>
                                        <th class="border-0 py-3 px-4">Estado</th>
                                        <th class="border-0 py-3 px-4">Última Verificación</th>
                                        <th class="border-0 py-3 px-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($item->apartamentos as $apartamento)
                                        <tr class="align-middle">
                                            <td class="px-4 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-home text-info"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold text-dark">{{ $apartamento->nombre ?? 'Apartamento #' . $apartamento->id }}</h6>
                                                        <small class="text-muted">
                                                            @if($apartamento->pivot->status)
                                                                Estado: {{ $apartamento->pivot->status }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $status = $apartamento->pivot->status ?? 'pendiente';
                                                    $statusColors = [
                                                        'completado' => 'success',
                                                        'pendiente' => 'warning',
                                                        'en_proceso' => 'info',
                                                        'rechazado' => 'danger'
                                                    ];
                                                    $statusColor = $statusColors[$status] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                    {{ ucfirst($status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <small class="text-muted">
                                                    {{ $apartamento->pivot->updated_at ? $apartamento->pivot->updated_at->format('d/m/Y H:i') : 'Nunca' }}
                                                </small>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="btn-group" role="group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary" 
                                                            title="Ver apartamento">
                                                        <i class="fas fa-eye"></i>
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
                                <i class="fas fa-building fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No hay apartamentos</h5>
                            <p class="text-muted mb-3">Este item no está asociado a ningún apartamento.</p>
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
                                <i class="fas fa-clipboard-list fa-2x text-primary mb-2"></i>
                                <h3 class="mb-1 fw-bold text-primary">{{ $totalControles }}</h3>
                                <small class="text-muted">Controles</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-success-subtle rounded">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h3 class="mb-1 fw-bold text-success">{{ $controlesCompletados }}</h3>
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
                        <div class="col-6">
                            <div class="text-center p-3 bg-warning-subtle rounded">
                                <i class="fas fa-home fa-2x text-warning mb-2"></i>
                                <h3 class="mb-1 fw-bold text-warning">{{ $item->apartamentos->count() }}</h3>
                                <small class="text-muted">Apartamentos</small>
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
                        <a href="{{ route('admin.itemsChecklist.edit', $item->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Editar Item
                        </a>
                        <a href="{{ route('admin.itemsChecklist.index', ['id' => $item->checklist->id]) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>Ver Todos los Items
                        </a>
                        <a href="{{ route('admin.checklists.show', $item->checklist->id) }}" class="btn btn-outline-info">
                            <i class="fas fa-clipboard-check me-2"></i>Ver Checklist
                        </a>
                        <form action="{{ route('admin.itemsChecklist.toggle-status', $item->id) }}" 
                              method="POST" 
                              style="display: inline;">
                            @csrf
                            <button type="submit" 
                                    class="btn btn-outline-{{ $item->activo ?? true ? 'warning' : 'success' }}">
                                <i class="fas fa-{{ $item->activo ?? true ? 'pause' : 'play' }} me-2"></i>
                                {{ $item->activo ?? true ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        <button type="button" 
                                class="btn btn-outline-danger delete-btn" 
                                data-id="{{ $item->id }}"
                                data-name="{{ $item->nombre }}">
                            <i class="fas fa-trash me-2"></i>Eliminar Item
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
                        <small class="text-muted d-block">ID del Item</small>
                        <strong class="text-dark">#{{ $item->id }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Checklist ID</small>
                        <strong class="text-dark">#{{ $item->checklist_id }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Estado</small>
                        @if($item->activo ?? true)
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
                        <small class="text-muted d-block">Obligatorio</small>
                        @if($item->obligatorio ?? false)
                            <span class="badge bg-danger-subtle text-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>Sí
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">
                                <i class="fas fa-minus-circle me-1"></i>No
                            </span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Fecha de Creación</small>
                        <strong class="text-dark">{{ $item->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Última Actualización</small>
                        <strong class="text-dark">{{ $item->updated_at->format('d/m/Y H:i') }}</strong>
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
    // Botón de eliminar item
    const deleteBtn = document.querySelector('.delete-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            const itemId = this.dataset.id;
            const itemName = this.dataset.name;
            
            Swal.fire({
                title: '⚠️ Eliminar Item del Checklist',
                html: `
                    <div class="text-center">
                        <div class="alert alert-danger mb-3">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <h5 class="mb-2"><strong>¡ATENCIÓN!</strong></h5>
                            <p class="mb-0">Esta acción es <strong>IRREVERSIBLE</strong> y eliminará permanentemente el item del checklist.</p>
                        </div>
                        <div class="card border-warning">
                            <div class="card-body text-start">
                                <h6 class="card-title text-warning">
                                    <i class="fas fa-info-circle me-2"></i>Detalles del Item:
                                </h6>
                                <p class="mb-0"><strong>Nombre:</strong> <code>${itemName}</code></p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                El item será eliminado permanentemente del checklist y no se podrá recuperar.
                            </p>
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, Eliminar Definitivamente',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
                customClass: {
                    confirmButton: 'btn btn-danger btn-lg',
                    cancelButton: 'btn btn-secondary btn-lg'
                },
                buttonsStyling: false,
                focusCancel: true,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading mientras se procesa
                    Swal.fire({
                        title: 'Eliminando...',
                        text: 'Por favor espera mientras se procesa la eliminación',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const form = document.getElementById('delete-form');
                    form.action = `{{ route('admin.itemsChecklist.destroy', '') }}/${itemId}`;
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
