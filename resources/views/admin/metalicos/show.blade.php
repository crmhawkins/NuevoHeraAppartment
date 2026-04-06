@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-eye text-primary me-2"></i>
            Detalles del Metálico #{{ $metalico->id }}
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('metalicos.index') }}">Metálicos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalles</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Session Alerts -->
@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Tarjeta de Acciones -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-tools text-primary me-2"></i>
                Acciones
            </h5>
            <div class="btn-group" role="group">
                <a href="{{ route('metalicos.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Listado
                </a>
                <a href="{{ route('metalicos.edit', $metalico) }}" class="btn btn-warning btn-lg">
                    <i class="fas fa-edit me-2"></i>
                    Editar Metálico
                </a>
                <button type="button" 
                        class="btn btn-danger btn-lg delete-btn" 
                        data-metalico-id="{{ $metalico->id }}"
                        data-metalico-titulo="{{ $metalico->titulo }}">
                    <i class="fas fa-trash me-2"></i>
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tarjeta Principal de Información -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-info-circle text-primary me-2"></i>
            Información del Metálico
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <!-- ID -->
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-hashtag text-primary"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">ID</h6>
                        <span class="badge bg-primary-subtle text-primary fw-bold fs-6">#{{ $metalico->id }}</span>
                    </div>
                </div>
            </div>

            <!-- Título -->
            <div class="col-md-9">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-tag text-info"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Título</h6>
                        <h5 class="mb-0 fw-semibold">{{ $metalico->titulo }}</h5>
                    </div>
                </div>
            </div>

            <!-- Importe -->
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-{{ $metalico->tipo === 'ingreso' ? 'success' : 'danger' }}-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-euro-sign text-{{ $metalico->tipo === 'ingreso' ? 'success' : 'danger' }}"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Importe</h6>
                        <h4 class="mb-0 fw-bold text-{{ $metalico->tipo === 'ingreso' ? 'success' : 'danger' }}">
                            {{ $metalico->tipo === 'ingreso' ? '+' : '-' }}{{ number_format($metalico->importe, 2) }} €
                        </h4>
                    </div>
                </div>
            </div>

            <!-- Tipo -->
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-{{ $metalico->tipo === 'ingreso' ? 'success' : 'danger' }}-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-exchange-alt text-{{ $metalico->tipo === 'ingreso' ? 'success' : 'danger' }}"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Tipo</h6>
                        @if($metalico->tipo === 'ingreso')
                            <span class="badge bg-success-subtle text-success fs-6">
                                <i class="fas fa-arrow-up me-1"></i>Ingreso
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger fs-6">
                                <i class="fas fa-arrow-down me-1"></i>Gasto
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Fecha -->
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-calendar text-warning"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Fecha de Ingreso</h6>
                        <h6 class="mb-0 fw-semibold">{{ \Carbon\Carbon::parse($metalico->fecha_ingreso)->format('d/m/Y') }}</h6>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($metalico->fecha_ingreso)->format('H:i') }}</small>
                    </div>
                </div>
            </div>

            <!-- Reserva ID (si existe) -->
            @if($metalico->reserva_id)
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-bed text-info"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Reserva Asociada</h6>
                        <span class="badge bg-info-subtle text-info fs-6">#{{ $metalico->reserva_id }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Fechas de creación y actualización -->
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-secondary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-clock text-secondary"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Última Actualización</h6>
                        <h6 class="mb-0 fw-semibold">{{ \Carbon\Carbon::parse($metalico->updated_at)->format('d/m/Y H:i') }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tarjeta de Observaciones (si existen) -->
@if($metalico->observaciones)
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-comment-alt text-primary me-2"></i>
            Observaciones
        </h5>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-start">
            <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                <i class="fas fa-sticky-note text-muted"></i>
            </div>
            <div class="flex-grow-1">
                <p class="mb-0 text-muted">{{ $metalico->observaciones }}</p>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Botón de eliminar
        const deleteButton = document.querySelector('.delete-btn');
        if (deleteButton) {
            deleteButton.addEventListener('click', function (event) {
                event.preventDefault();
                const metalicoId = this.getAttribute('data-metalico-id');
                const metalicoTitulo = this.getAttribute('data-metalico-titulo');
                
                Swal.fire({
                    title: '¿Eliminar Metálico?',
                    html: `
                        <div class="text-start">
                            <p><strong>Metálico:</strong> ${metalicoTitulo}</p>
                            <p class="text-danger mt-3"><strong>Esta acción no se puede deshacer.</strong></p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, Eliminar',
                    cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Crear formulario temporal para enviar la petición DELETE
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `{{ route('metalicos.destroy', '') }}/${metalicoId}`;
                        
                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = '{{ csrf_token() }}';
                        
                        const methodField = document.createElement('input');
                        methodField.type = 'hidden';
                        methodField.name = '_method';
                        methodField.value = 'DELETE';
                        
                        form.appendChild(csrfToken);
                        form.appendChild(methodField);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        }
    });
</script>
@endsection
