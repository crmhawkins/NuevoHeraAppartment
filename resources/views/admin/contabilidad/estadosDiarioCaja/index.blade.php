@extends('layouts.appAdmin')

@section('title', 'Estados del Diario')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-bar me-2 text-primary"></i>
                Estados del Diario
            </h1>
            <p class="text-muted mb-0">Gestiona los estados contables del sistema</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Estados del Diario</li>
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
                        <i class="fas fa-plus me-2 text-success"></i>
                        Gestión de Estados
                    </h5>
                    <p class="text-muted mb-0">Crea y gestiona los estados contables</p>
                </div>
                <a href="{{ route('admin.estadosDiario.create') }}" class="btn btn-success btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Crear Estado
                </a>
            </div>
        </div>
    </div>

    <!-- Tarjeta de Búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-search me-2 text-primary"></i>
                Buscar Estados
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.estadosDiario.index') }}" method="GET">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" name="search" placeholder="Buscar estado por nombre..." value="{{ request()->get('search') }}">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Estados -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-list me-2 text-primary"></i>
                Lista de Estados
            </h5>
        </div>
        <div class="card-body">
            @if($estados->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold text-dark">
                                    <i class="fas fa-hashtag me-2 text-primary"></i>ID
                                </th>
                                <th class="fw-semibold text-dark">
                                    <i class="fas fa-tag me-2 text-success"></i>Nombre
                                </th>
                                <th class="fw-semibold text-dark text-center">
                                    <i class="fas fa-cogs me-2 text-secondary"></i>Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($estados as $estado)
                                <tr>
                                    <td class="align-middle">
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ $estado->id }}
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-tag text-success"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $estado->nombre }}</h6>
                                                <small class="text-muted">Estado contable</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.estadosDiario.edit', $estado->id) }}" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-edit me-1"></i>Editar
                                            </a>
                                            <form action="{{ route('admin.estadosDiario.destroy', $estado->id) }}" method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-outline-danger btn-sm delete-btn">
                                                    <i class="fas fa-trash me-1"></i>Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $estados->appends(request()->input())->links() }}
                </div>
            @else
                <!-- Estado Vacío -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-chart-bar fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted mb-3">No hay estados registrados</h4>
                    <p class="text-muted mb-0">No se encontraron estados para los filtros seleccionados.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@include('sweetalert::alert')

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function (event) {
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
    });
</script>
@endsection
