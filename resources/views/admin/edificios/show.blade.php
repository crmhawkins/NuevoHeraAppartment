@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-building me-2 text-primary"></i>
                        {{ $edificio->nombre }}
                    </h1>
                    <p class="text-muted mb-0">Información detallada del edificio</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.edificio.edit', $edificio->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('admin.edificios.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna principal -->
        <div class="col-lg-8">
            <!-- Información del edificio -->
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
                                    <i class="fas fa-building fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 fw-bold text-dark">{{ $edificio->nombre }}</h4>
                                    <p class="text-muted mb-0">Edificio #{{ $edificio->id }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded">
                                <h6 class="fw-semibold text-dark mb-2">Clave de Acceso</h6>
                                <code class="fs-5 fw-bold text-primary">{{ $edificio->clave }}</code>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-primary-subtle p-3 rounded mb-2">
                                    <i class="fas fa-calendar-plus fa-2x text-primary"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Fecha de Creación</h6>
                                <p class="text-muted mb-0">{{ $edificio->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-success-subtle p-3 rounded mb-2">
                                    <i class="fas fa-calendar-check fa-2x text-success"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Última Actualización</h6>
                                <p class="text-muted mb-0">{{ $edificio->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="bg-info-subtle p-3 rounded mb-2">
                                    <i class="fas fa-clock fa-2x text-info"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">Antigüedad</h6>
                                <p class="text-muted mb-0">{{ $edificio->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Apartamentos del edificio -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-home me-2 text-primary"></i>
                            Apartamentos ({{ $edificio->apartamentos->count() }})
                        </h5>
                        <a href="{{ route('apartamentos.admin.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Nuevo Apartamento
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($edificio->apartamentos->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 py-3 px-4">ID</th>
                                        <th class="border-0 py-3 px-4">Nombre</th>
                                        <th class="border-0 py-3 px-4">Estado</th>
                                        <th class="border-0 py-3 px-4">Precio</th>
                                        <th class="border-0 py-3 px-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($edificio->apartamentos as $apartamento)
                                        <tr class="align-middle">
                                            <td class="px-4 py-3">
                                                <span class="badge bg-light text-dark fw-semibold">#{{ $apartamento->id }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-home text-info"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold text-dark">{{ $apartamento->nombre ?? 'Sin nombre' }}</h6>
                                                        <small class="text-muted">{{ $apartamento->titulo ?? 'Sin título' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($apartamento->estado)
                                                    <span class="badge bg-success-subtle text-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        {{ $apartamento->estado }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                        <i class="fas fa-question-circle me-1"></i>
                                                        Sin estado
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($apartamento->precio)
                                                    <span class="fw-semibold text-success">€{{ number_format($apartamento->precio, 2) }}</span>
                                                @else
                                                    <span class="text-muted">Sin precio</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('apartamentos.admin.show', $apartamento->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Ver apartamento">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('apartamentos.admin.edit', $apartamento->id) }}" 
                                                       class="btn btn-sm btn-outline-secondary" 
                                                       title="Editar apartamento">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
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
                                <i class="fas fa-home fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No hay apartamentos</h5>
                            <p class="text-muted mb-3">Este edificio aún no tiene apartamentos registrados.</p>
                            <a href="{{ route('apartamentos.admin.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primer Apartamento
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Checklists del edificio -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-clipboard-check me-2 text-primary"></i>
                            Checklists ({{ $edificio->checklists->count() }})
                        </h5>
                        <a href="#" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Nuevo Checklist
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($edificio->checklists->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 py-3 px-4">ID</th>
                                        <th class="border-0 py-3 px-4">Nombre</th>
                                        <th class="border-0 py-3 px-4">Estado</th>
                                        <th class="border-0 py-3 px-4">Fecha</th>
                                        <th class="border-0 py-3 px-4 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($edificio->checklists as $checklist)
                                        <tr class="align-middle">
                                            <td class="px-4 py-3">
                                                <span class="badge bg-light text-dark fw-semibold">#{{ $checklist->id }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-clipboard-check text-warning"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold text-dark">{{ $checklist->nombre ?? 'Sin nombre' }}</h6>
                                                        <small class="text-muted">{{ $checklist->descripcion ?? 'Sin descripción' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Pendiente
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <small class="text-muted">{{ $checklist->created_at->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="#" class="btn btn-sm btn-outline-primary" title="Ver checklist">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="#" class="btn btn-sm btn-outline-secondary" title="Editar checklist">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
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
                                <i class="fas fa-clipboard-check fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No hay checklists</h5>
                            <p class="text-muted mb-3">Este edificio aún no tiene checklists registrados.</p>
                            <a href="#" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primer Checklist
                            </a>
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
                                <i class="fas fa-home fa-2x text-primary mb-2"></i>
                                <h3 class="mb-1 fw-bold text-primary">{{ $totalApartamentos }}</h3>
                                <small class="text-muted">Apartamentos</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-warning-subtle rounded">
                                <i class="fas fa-clipboard-check fa-2x text-warning mb-2"></i>
                                <h3 class="mb-1 fw-bold text-warning">{{ $totalChecklists }}</h3>
                                <small class="text-muted">Checklists</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-success-subtle rounded">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h3 class="mb-1 fw-bold text-success">{{ $apartamentosActivos }}</h3>
                                <small class="text-muted">Activos</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-info-subtle rounded">
                                <i class="fas fa-percentage fa-2x text-info mb-2"></i>
                                <h3 class="mb-1 fw-bold text-info">
                                    {{ $totalApartamentos > 0 ? round(($apartamentosActivos / $totalApartamentos) * 100) : 0 }}%
                                </h3>
                                <small class="text-muted">Ocupación</small>
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
                        <a href="{{ route('apartamentos.admin.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nuevo Apartamento
                        </a>
                        <a href="#" class="btn btn-outline-warning">
                            <i class="fas fa-clipboard-check me-2"></i>Nuevo Checklist
                        </a>
                        <a href="{{ route('admin.edificio.edit', $edificio->id) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-edit me-2"></i>Editar Edificio
                        </a>
                        <button type="button" 
                                class="btn btn-outline-danger delete-btn" 
                                data-id="{{ $edificio->id }}"
                                data-name="{{ $edificio->nombre }}">
                            <i class="fas fa-trash me-2"></i>Eliminar Edificio
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
                        <small class="text-muted d-block">ID del Edificio</small>
                        <strong class="text-dark">#{{ $edificio->id }}</strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Clave de Acceso</small>
                        <code class="text-primary">{{ $edificio->clave }}</code>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Fecha de Creación</small>
                        <strong class="text-dark">{{ $edificio->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Última Actualización</small>
                        <strong class="text-dark">{{ $edificio->updated_at->format('d/m/Y H:i') }}</strong>
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
    // Botón de eliminar
    const deleteBtn = document.querySelector('.delete-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            const edificioId = this.dataset.id;
            const edificioName = this.dataset.name;
            
            Swal.fire({
                title: '¿Eliminar edificio?',
                html: `¿Estás seguro de que quieres eliminar <strong>${edificioName}</strong>?<br><br>
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
                    form.action = `{{ route('admin.edificio.destroy', '') }}/${edificioId}`;
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
