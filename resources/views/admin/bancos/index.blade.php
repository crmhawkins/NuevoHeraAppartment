@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-university text-primary me-2"></i>
            Gestión de Bancos
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Bancos</li>
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
            <a href="{{ route('admin.bancos.create') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>
                Crear Banco
            </a>
        </div>
    </div>
</div>

<!-- Tarjeta de Búsqueda -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-search text-primary me-2"></i>
            Búsqueda
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.bancos.index') }}" method="GET">
            <div class="input-group input-group-lg">
                <input type="text" class="form-control" name="search" placeholder="Buscar banco..." value="{{ request()->get('search') }}">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>
                    Buscar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tarjeta Principal -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            Lista de Bancos
        </h5>
    </div>
    <div class="card-body p-0">
        @if($bancos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.bancos.index', ['sort' => 'id', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-hashtag text-primary me-1"></i>ID
                                    @if (request('sort') == 'id')
                                        @if (request('order', 'asc') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.bancos.index', ['sort' => 'nombre', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-university text-primary me-1"></i>Nombre
                                    @if (request('sort') == 'nombre')
                                        @if (request('order', 'asc') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-cogs text-primary me-1"></i>Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bancos as $banco)
                            <tr>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary fw-bold">#{{ $banco->id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-university text-info"></i>
                                        </div>
                                        <span class="fw-semibold">{{ $banco->nombre }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.bancos.edit', $banco->id) }}" 
                                           class="btn btn-outline-warning btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           title="Editar banco">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm delete-btn" 
                                                data-banco-id="{{ $banco->id }}"
                                                data-banco-nombre="{{ $banco->nombre }}"
                                                data-bs-toggle="tooltip" 
                                                title="Eliminar banco">
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
                <i class="fas fa-university fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay bancos disponibles</h5>
                <p class="text-muted">No se encontraron bancos con los filtros aplicados.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Botones de eliminar
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const bancoId = this.getAttribute('data-banco-id');
                const bancoNombre = this.getAttribute('data-banco-nombre');
                
                Swal.fire({
                    title: '¿Eliminar Banco?',
                    html: `
                        <div class="text-start">
                            <p><strong>Banco:</strong> ${bancoNombre}</p>
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
                        form.action = `{{ route('admin.bancos.destroy', '') }}/${bancoId}`;
                        
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
        });
    });
</script>
@endsection

