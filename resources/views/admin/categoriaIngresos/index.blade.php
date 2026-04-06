@extends('layouts.appAdmin')

@section('title', 'Categorías de Ingresos')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-folder me-2 text-info"></i>
                Categorías de Ingresos
            </h1>
            <p class="text-muted mb-0">Administra las categorías para clasificar los ingresos</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Categorías de Ingresos</li>
            </ol>
        </nav>
    </div>

    <!-- Alertas de Sesión -->
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tarjeta de Acciones -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-plus me-2 text-info"></i>
                        Gestión de Categorías
                    </h5>
                    <p class="text-muted mb-0">Crea y gestiona las categorías de ingresos</p>
                </div>
                <a href="{{ route('admin.categoriaIngresos.create') }}" class="btn btn-info btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Crear Categoría
                </a>
            </div>
        </div>
    </div>

    <!-- Tarjeta de Búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-search me-2 text-primary"></i>
                Búsqueda de Categorías
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.categoriaIngresos.index') }}" method="GET">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-primary-subtle text-primary">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Buscar categoría por nombre..." 
                           value="{{ request()->get('search') }}">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjeta de Tabla de Categorías -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-list me-2 text-primary"></i>
                Lista de Categorías
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.categoriaIngresos.index', ['sort' => 'id', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-hashtag me-1"></i>ID
                                    @if (request('sort') == 'id')
                                        <i class="fas {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} ms-1 text-primary"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.categoriaIngresos.index', ['sort' => 'nombre', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-tag me-1"></i>Nombre
                                    @if (request('sort') == 'nombre')
                                        <i class="fas {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} ms-1 text-primary"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0 text-center">
                                <i class="fas fa-building me-1"></i>Contabilizar Misma Empresa
                            </th>
                            <th scope="col" class="border-0 text-center" style="width: 200px;">
                                <i class="fas fa-cogs me-1"></i>Acciones
                            </th>
                        </tr>
                    </thead>


                <tbody>
                    @forelse ($categorias as $categoria)
                        <tr>
                            <td class="align-middle">
                                <span class="badge bg-info-subtle text-info fs-6 fw-semibold">
                                    #{{$categoria->id}}
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info-subtle rounded-circle p-2 me-2">
                                        <i class="fas fa-folder text-info"></i>
                                    </div>
                                    <span class="fw-semibold">{{$categoria->nombre}}</span>
                                </div>
                            </td>
                            <td class="align-middle text-center">
                                @if($categoria->contabilizar_misma_empresa)
                                    <span class="badge bg-warning-subtle text-warning fs-6">
                                        <i class="fas fa-building me-1"></i>
                                        Sí
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success fs-6">
                                        <i class="fas fa-chart-line me-1"></i>
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="align-middle text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{route('admin.categoriaIngresos.edit', $categoria->id)}}" 
                                       class="btn btn-outline-warning btn-sm" 
                                       title="Editar categoría">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.categoriaIngresos.destroy', $categoria->id) }}" 
                                          method="POST" 
                                          style="display: inline;" 
                                          class="delete-form">
                                        @csrf
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm delete-btn" 
                                                title="Eliminar categoría">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                                    <h5 class="text-muted">No se encontraron categorías</h5>
                                    <p class="text-muted mb-0">No hay categorías que coincidan con los criterios de búsqueda</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Enlaces de paginación -->
        @if($categorias->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $categorias->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Verificar si SweetAlert2 está definido
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 is not loaded');
            return;
        }

        // Botones de eliminar con confirmación mejorada
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const form = this.closest('form');
                const categoriaId = form.action.split('/').pop();
                
                Swal.fire({
                    title: '¿Eliminar categoría?',
                    text: `¿Estás seguro de que quieres eliminar la categoría #${categoriaId}? Esta acción no se puede deshacer.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, eliminar',
                    cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-danger btn-lg',
                        cancelButton: 'btn btn-secondary btn-lg'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Eliminando...',
                            text: 'Por favor espera mientras se procesa la solicitud',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        form.submit();
                    }
                });
            });
        });

        // Tooltips para los botones de acción
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Mejorar la experiencia de los filtros
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.closest('form').submit();
                }
            });
        }
    });

    // Confirmar eliminación con SweetAlert
    const deleteButtons = document.querySelectorAll('.delete-categoria');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const form = this.closest('form');
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection

