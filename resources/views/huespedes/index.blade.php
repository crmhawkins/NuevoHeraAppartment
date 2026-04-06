@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-users text-primary me-2"></i>
            Gestión de Huéspedes
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Huéspedes</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('huespedes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Nuevo Huésped
        </a>
    </div>
</div>

<!-- Session Alerts -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
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

<!-- Actions Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-tools text-primary me-2"></i>
            Acciones
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <a href="{{ route('huespedes.create') }}" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-user-plus me-2"></i>
                    Crear Nuevo Huésped
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ route('reservas.index') }}" class="btn btn-outline-secondary btn-lg w-100">
                    <i class="fas fa-calendar me-2"></i>
                    Ver Reservas
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            Lista de Huéspedes
        </h5>
    </div>
    <div class="card-body p-0">
        @if($huespedes->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="border-0">
                                <i class="fas fa-hashtag text-primary me-1"></i>
                                ID
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-user text-primary me-1"></i>
                                Nombre Completo
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-id-card text-primary me-1"></i>
                                Documento
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-envelope text-primary me-1"></i>
                                Email
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-calendar text-primary me-1"></i>
                                Reserva
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-cog text-primary me-1"></i>
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($huespedes as $huesped)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                        <i class="fas fa-hashtag text-primary"></i>
                                    </div>
                                    <span class="fw-semibold">#{{ $huesped->id }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                        <i class="fas fa-user text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold">{{ $huesped->nombre }} {{ $huesped->primer_apellido }}</h6>
                                        @if($huesped->segundo_apellido)
                                            <small class="text-muted">{{ $huesped->segundo_apellido }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                        <i class="fas fa-{{ $huesped->tipo_documento == 1 ? 'id-card' : 'passport' }} text-warning"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-{{ $huesped->tipo_documento == 1 ? 'info' : 'warning' }}-subtle text-{{ $huesped->tipo_documento == 1 ? 'info' : 'warning' }}">
                                            {{ $huesped->tipo_documento == 1 ? 'DNI' : 'Pasaporte' }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ $huesped->numero_identificacion }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($huesped->email)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-envelope text-success"></i>
                                        </div>
                                        <a href="mailto:{{ $huesped->email }}" class="text-decoration-none">{{ $huesped->email }}</a>
                                    </div>
                                @else
                                    <span class="text-muted">No especificado</span>
                                @endif
                            </td>
                            <td>
                                @if($huesped->reserva)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-secondary-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-calendar text-secondary"></i>
                                        </div>
                                        <a href="{{ route('reservas.show', $huesped->reserva->id) }}" class="text-decoration-none">
                                            #{{ $huesped->reserva->id }}
                                        </a>
                                    </div>
                                @else
                                    <span class="text-muted">Sin reserva</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('huespedes.show', $huesped->id) }}" 
                                       class="btn btn-outline-primary btn-sm" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('huespedes.edit', $huesped->id) }}" 
                                       class="btn btn-outline-warning btn-sm" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm" 
                                            title="Eliminar"
                                            onclick="confirmDelete({{ $huesped->id }}, '{{ $huesped->nombre }} {{ $huesped->primer_apellido }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($huespedes->hasPages())
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-center">
                        {{ $huespedes->links() }}
                    </div>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay huéspedes registrados</h5>
                <p class="text-muted">Comienza creando tu primer huésped.</p>
                <a href="{{ route('huespedes.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Crear Primer Huésped
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
    function confirmDelete(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Quieres eliminar al huésped "${nombre}"? Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario para eliminar
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/huespedes/${id}`;
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                
                const tokenField = document.createElement('input');
                tokenField.type = 'hidden';
                tokenField.name = '_token';
                tokenField.value = '{{ csrf_token() }}';
                
                form.appendChild(methodField);
                form.appendChild(tokenField);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endsection
