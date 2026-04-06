@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header con estadísticas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-building me-2 text-primary"></i>
                        Gestión de Edificios
                    </h1>
                    <p class="text-muted mb-0">Administra todos los edificios de la plataforma</p>
                </div>
                <a href="{{ route('admin.edificio.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Edificio
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.edificios.index') }}" method="GET" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" 
                               class="form-control border-start-0 ps-0" 
                               name="search" 
                               placeholder="Buscar por nombre o clave..." 
                               value="{{ $search ?? '' }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        @if($search)
                            <a href="{{ route('admin.edificios.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de edificios -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Edificios ({{ $edificios->total() }})
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        <i class="fas fa-building me-1"></i>
                        {{ $edificios->count() }} mostrados
                    </span>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            @if($edificios->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 py-3 px-4">
                                    <a href="{{ route('admin.edificios.index', ['sort' => 'id', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <span class="fw-semibold">ID</span>
                                        @if(request('sort') == 'id')
                                            <i class="fas fa-sort-{{ request('order', 'asc') == 'asc' ? 'up' : 'down' }} ms-2 text-primary"></i>
                                        @else
                                            <i class="fas fa-sort ms-2 text-muted"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <a href="{{ route('admin.edificios.index', ['sort' => 'nombre', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
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
                                    <span class="fw-semibold">Clave</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Apartamentos</span>
                                </th>
                                <th class="border-0 py-3 px-4">
                                    <span class="fw-semibold">Checklists</span>
                                </th>
                                <th class="border-0 py-3 px-4 text-center" style="width: 200px;">
                                    <span class="fw-semibold">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($edificios as $edificio)
                                <tr class="align-middle">
                                    <td class="px-4 py-3">
                                        <span class="badge bg-light text-dark fw-semibold">#{{ $edificio->id }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-building text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark">{{ $edificio->nombre }}</h6>
                                                <small class="text-muted">Creado {{ $edificio->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="bg-light px-2 py-1 rounded">{{ $edificio->clave }}</code>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge bg-info-subtle text-info">
                                            <i class="fas fa-home me-1"></i>
                                            {{ $edificio->apartamentos->count() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="fas fa-clipboard-check me-1"></i>
                                            {{ $edificio->checklists->count() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.edificio.show', $edificio->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Ver edificio">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.edificio.edit', $edificio->id) }}" 
                                               class="btn btn-sm btn-outline-secondary" 
                                               title="Editar edificio">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger delete-btn" 
                                                    data-id="{{ $edificio->id }}"
                                                    data-name="{{ $edificio->nombre }}"
                                                    title="Eliminar edificio">
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
                        <i class="fas fa-building fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted mb-2">No se encontraron edificios</h5>
                    <p class="text-muted mb-3">
                        @if($search)
                            No hay resultados para "{{ $search }}". Intenta con otros términos.
                        @else
                            Comienza creando tu primer edificio.
                        @endif
                    </p>
                    @if(!$search)
                        <a href="{{ route('admin.edificio.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Primer Edificio
                        </a>
                    @endif
                </div>
            @endif
        </div>
        
        @if($edificios->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="text-muted mb-0">
                        Mostrando {{ $edificios->firstItem() }} a {{ $edificios->lastItem() }} de {{ $edificios->total() }} resultados
                    </p>
                    {{ $edificios->appends(['search' => $search, 'sort' => $sort, 'order' => $order])->links('pagination::bootstrap-5') }}
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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Botones de eliminar
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
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

