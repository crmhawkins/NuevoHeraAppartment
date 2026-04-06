@extends('layouts.appAdmin')

@section('title', 'Gestión de Presupuestos')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-pie me-2 text-primary"></i>
                Gestión de Presupuestos
            </h1>
            <p class="text-muted mb-0">Administra los presupuestos y cotizaciones del sistema</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Presupuestos</li>
            </ol>
        </nav>
    </div>

    <!-- Tarjeta de Acciones -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-plus me-2 text-success"></i>
                        Gestión de Presupuestos
                    </h5>
                    <p class="text-muted mb-0">Crea y gestiona presupuestos para los clientes</p>
                </div>
                <a href="{{ route('presupuestos.create') }}" class="btn btn-success btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Crear Presupuesto
                </a>
            </div>
        </div>
    </div>

    <!-- Tabla de Presupuestos -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-list me-2 text-primary"></i>
                Lista de Presupuestos
            </h5>
        </div>
        <div class="card-body">
            @if($presupuestos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold text-dark">
                                    <i class="fas fa-hashtag me-2 text-primary"></i>ID
                                </th>
                                <th class="fw-semibold text-dark">
                                    <i class="fas fa-user me-2 text-success"></i>Cliente
                                </th>
                                <th class="fw-semibold text-dark">
                                    <i class="fas fa-calendar me-2 text-info"></i>Fecha
                                </th>
                                <th class="fw-semibold text-dark text-end">
                                    <i class="fas fa-euro-sign me-2 text-warning"></i>Total
                                </th>
                                <th class="fw-semibold text-dark text-center">
                                    <i class="fas fa-tag me-2 text-secondary"></i>Estado
                                </th>
                                <th class="fw-semibold text-dark text-center">
                                    <i class="fas fa-cogs me-2 text-secondary"></i>Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($presupuestos as $p)
                            <tr>
                                <td class="align-middle">
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ $p->id }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-user text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ $p->cliente->nombre ?? '—' }}</h6>
                                            <small class="text-muted">Cliente</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <i class="fas fa-calendar-day me-2 text-muted"></i>
                                    {{ $p->fecha }}
                                </td>
                                <td class="text-end align-middle">
                                    <span class="badge bg-warning-subtle text-warning fs-6 px-3 py-2">
                                        <i class="fas fa-euro-sign me-1"></i>
                                        {{ number_format($p->total,2) }} €
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    @if($p->estado == 'facturado')
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check me-1"></i>Facturado
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <i class="fas fa-clock me-1"></i>{{ ucfirst($p->estado) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('presupuestos.show',$p->id) }}" 
                                           class="btn btn-outline-info btn-sm" title="Ver Presupuesto">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @if($p->estado !== 'facturado')
                                            <a href="{{ route('presupuestos.edit',$p->id) }}"
                                               class="btn btn-outline-warning btn-sm" title="Editar Presupuesto">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <form action="{{ route('presupuestos.destroy',$p->id) }}"
                                                  method="POST" class="d-inline delete-form" onsubmit="return handleDeleteSubmit(event)">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm delete-btn" 
                                                        title="Eliminar Presupuesto">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>

                                            <form action="{{ route('presupuestos.facturar',$p->id) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-outline-success btn-sm" 
                                                        onclick="return confirm('¿Facturar presupuesto?')"
                                                        title="Facturar Presupuesto">
                                                    <i class="fas fa-file-invoice"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Estado Vacío -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-chart-pie fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted mb-3">No hay presupuestos registrados</h4>
                    <p class="text-muted mb-0">Crea tu primer presupuesto para comenzar a gestionar cotizaciones.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@include('sweetalert::alert')

@section('scripts')
<script>
// Hacemos la función explícitamente global, como en Metálicos
window.handleDeleteSubmit = function(e){
    e.preventDefault();
    const form = e.target;
    if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¡No podrás revertir esto!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    } else {
        if (confirm('¿Estás seguro? ¡No podrás revertir esto!')) form.submit();
    }
    return false;
}
</script>
@endsection
@endsection
