@extends('layouts.appAdmin')

@section('title', 'Gestión de Ingresos')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-arrow-up me-2 text-success"></i>
                Gestión de Ingresos
            </h1>
            <p class="text-muted mb-0">Administra todos los ingresos del sistema</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ingresos</li>
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
                        Gestión de Ingresos
                    </h5>
                    <p class="text-muted mb-0">Crea y gestiona los ingresos del sistema</p>
                </div>
                <a href="{{ route('admin.ingresos.create') }}" class="btn btn-success btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Crear Ingreso
                </a>
            </div>
        </div>
    </div>

    <!-- Tarjeta de Filtros -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-filter me-2 text-primary"></i>
                Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label for="perPage" class="form-label fw-semibold">Registros por página</label>
                    <form action="{{ route('admin.ingresos.index') }}" method="GET" id="perPageForm">
                        <input type="hidden" name="search" value="{{ request()->get('search') }}">
                        <input type="hidden" name="order_by" value="{{ request()->get('order_by') }}">
                        <input type="hidden" name="direction" value="{{ request()->get('direction') }}">
                        <input type="hidden" name="month" value="{{ request()->get('month') }}">
                        <input type="hidden" name="category" value="{{ request()->get('category') }}">
                        <input type="hidden" name="estado_id" value="{{ request()->get('estado_id') }}">
                        <select name="perPage" id="perPage" class="form-select" onchange="this.form.submit()">
                            <option value="10" {{ request()->get('perPage') == 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ request()->get('perPage') == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ request()->get('perPage') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request()->get('perPage') == 100 ? 'selected' : '' }}>100</option>
                            <option value="-1" {{ request()->get('perPage') == -1 ? 'selected' : '' }}>Todo</option>
                        </select>
                    </form>
                </div>
                <div class="col-md-10">
                    <form action="{{ route('admin.ingresos.index') }}" method="GET">
                        <input type="hidden" name="order_by" value="{{ request()->get('order_by', 'fecha_entrada') }}">
                        <input type="hidden" name="direction" value="{{ request()->get('direction', 'asc') }}">
                        <input type="hidden" name="perPage" value="{{ request()->get('perPage') }}">
                        
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label fw-semibold">
                                    <i class="fas fa-search me-2 text-primary"></i>Búsqueda
                                </label>
                                <input type="text" class="form-control" name="search" placeholder="Buscar ingresos..." value="{{ request()->get('search') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="estado_id" class="form-label fw-semibold">
                                    <i class="fas fa-tag me-2 text-warning"></i>Estado
                                </label>
                                <select class="form-select" name="estado_id">
                                    <option value="">Todos los estados</option>
                                    @foreach ($estados as $estado)
                                        <option value="{{ $estado->id }}" {{ request('estado_id') == $estado->id ? 'selected' : '' }}>{{ $estado->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="category" class="form-label fw-semibold">
                                    <i class="fas fa-folder me-2 text-info"></i>Categoría
                                </label>
                                <select class="form-select" name="category">
                                    <option value="">Todas las categorías</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}" {{ request('category') == $categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="month" class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-2 text-success"></i>Mes
                                </label>
                                <select class="form-select" name="month">
                                    <option value="">Todos los meses</option>
                                    @php
                                    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                                    @endphp
                                    @for ($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" @if (request('month') == $i) selected @endif>{{ $meses[$i - 1] }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                        </div>
        </div>
    </div>

    <!-- Tarjeta de Tabla de Ingresos -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-list me-2 text-primary"></i>
                Lista de Ingresos
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.ingresos.index', ['sort' => 'id', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-hashtag me-1"></i>ID
                                    @if (request('sort') == 'id')
                                        <i class="fas {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} ms-1 text-primary"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.ingresos.index', ['sort' => 'estado_id', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-tag me-1"></i>Estado
                                    @if (request('sort') == 'estado_id')
                                        <i class="fas {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} ms-1 text-primary"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.ingresos.index', ['sort' => 'categoria_id', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-folder me-1"></i>Categoría
                                    @if (request('sort') == 'categoria_id')
                                        <i class="fas {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} ms-1 text-primary"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.ingresos.index', ['sort' => 'nombre', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-file-text me-1"></i>Nombre
                                    @if (request('sort') == 'nombre')
                                        <i class="fas {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} ms-1 text-primary"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.ingresos.index', ['sort' => 'date', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-calendar me-1"></i>Fecha
                                    @if (request('sort') == 'date')
                                        <i class="fas {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} ms-1 text-primary"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('admin.ingresos.index', ['sort' => 'quantity', 'order' => request('order', 'asc') == 'asc' ? 'desc' : 'asc', 'search' => request('search')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-euro-sign me-1"></i>Importe
                                    @if (request('sort') == 'quantity')
                                        <i class="fas {{ request('order', 'asc') == 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} ms-1 text-primary"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0 text-center" style="width: 200px;">
                                <i class="fas fa-cogs me-1"></i>Acciones
                            </th>
                        </tr>
                    </thead>


                                    <tbody>
                        @forelse ($ingresos as $ingreso)
                            <tr>
                                <td class="align-middle">
                                    <span class="badge bg-primary-subtle text-primary fs-6 fw-semibold">
                                        #{{$ingreso->id}}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    @if ($ingreso->estado_id)
                                        @if ($ingreso->estado_id == 1)
                                            <span class="badge bg-warning-subtle text-warning fw-semibold">
                                                <i class="fas fa-clock me-1"></i>{{$ingreso->estado->nombre}}
                                            </span>
                                        @elseif ($ingreso->estado_id == 2)
                                            <span class="badge bg-primary-subtle text-primary fw-semibold">
                                                <i class="fas fa-check me-1"></i>{{$ingreso->estado->nombre}}
                                            </span>
                                        @elseif ($ingreso->estado_id == 3)
                                            <span class="badge bg-success-subtle text-success fw-semibold">
                                                <i class="fas fa-check-circle me-1"></i>{{$ingreso->estado->nombre}}
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger fw-semibold">
                                                <i class="fas fa-times me-1"></i>{{$ingreso->estado->nombre}}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold">
                                            <i class="fas fa-question me-1"></i>Sin Estado
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if ($ingreso->categoria_id != null)
                                        <div class="d-flex align-items-center">
                                            <div class="bg-info-subtle rounded-circle p-2 me-2">
                                                <i class="fas fa-folder text-info"></i>
                                            </div>
                                            <span class="fw-semibold">{{$ingreso->categoria->nombre}}</span>
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">
                                            <i class="fas fa-minus me-1"></i>Sin Categoría
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-subtle rounded-circle p-2 me-2">
                                            <i class="fas fa-file-text text-primary"></i>
                                        </div>
                                        <span class="fw-semibold">{{$ingreso->title}}</span>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    @if (!empty($ingreso->date))
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success-subtle rounded-circle p-2 me-2">
                                                <i class="fas fa-calendar text-success"></i>
                                            </div>
                                            <span class="fw-semibold">{{ \Carbon\Carbon::parse($ingreso->date)->format('d-m-Y') }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">
                                            <i class="fas fa-calendar-times me-1"></i>Sin fecha establecida
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success-subtle rounded-circle p-2 me-2">
                                            <i class="fas fa-euro-sign text-success"></i>
                                        </div>
                                        <span class="fw-bold fs-5 text-success">{{$ingreso->quantity}} €</span>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{route('admin.ingresos.edit', $ingreso->id)}}" 
                                           class="btn btn-outline-warning btn-sm" 
                                           title="Editar ingreso">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.ingresos.destroy', $ingreso->id) }}" 
                                              method="POST" 
                                              style="display: inline;" 
                                              class="delete-form">
                                            @csrf
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm delete-btn" 
                                                    title="Eliminar ingreso">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                        <h5 class="text-muted">No se encontraron ingresos</h5>
                                        <p class="text-muted mb-0">No hay ingresos que coincidan con los criterios de búsqueda</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end fw-bold fs-5">
                            <i class="fas fa-calculator me-2 text-primary"></i>
                            TOTAL:
                        </td>
                        <td class="fw-bold fs-4 text-success">
                            <i class="fas fa-euro-sign me-1"></i>
                            {{ $totalQuantity }} €
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($ingresos instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="d-flex justify-content-center mt-4">
            {{ $ingresos->appends(request()->except('page'))->links() }}
        </div>
    @endif
</div>
@endsection

@include('sweetalert::alert')

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
                const ingresoId = form.action.split('/').pop();
                
                Swal.fire({
                    title: '¿Eliminar ingreso?',
                    text: `¿Estás seguro de que quieres eliminar el ingreso #${ingresoId}? Esta acción no se puede deshacer.`,
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
</script>
@endsection

