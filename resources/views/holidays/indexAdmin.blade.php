@extends('layouts.appAdmin')

@section('title', 'Gestión de Vacaciones')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-umbrella-beach me-2 text-primary"></i>
                Gestión de Vacaciones
            </h1>
            <p class="text-muted mb-0">Administra las vacaciones de los empleados</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Vacaciones</li>
            </ol>
        </nav>
    </div>

    <!-- Tarjeta Principal -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-list me-2 text-primary"></i>
                Lista de Vacaciones
            </h5>
        </div>
        <div class="card-body">
            <!-- Filtros y Búsqueda -->
            <form method="GET" action="{{ route('holiday.admin.index') }}" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="perPage" class="form-label fw-semibold">Elementos por página</label>
                        <select name="perPage" id="perPage" class="form-select" onchange="this.form.submit()">
                            <option value="10" {{ request('perPage', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('perPage') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('perPage') == 50 ? 'selected' : '' }}>50</option>
                            <option value="all" {{ request('perPage') == 'all' ? 'selected' : '' }}>Todos</option>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label for="buscar" class="form-label fw-semibold">Buscar empleado</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" name="buscar" id="buscar" class="form-control"
                                   value="{{ request('buscar') }}" placeholder="Escriba el nombre del empleado...">
                        </div>
                    </div>
                </div>
            </form>

            <!-- Tabla de Datos -->
            @if ($holidays->count())
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold text-dark">
                                    <i class="fas fa-user me-2 text-primary"></i>Empleado
                                </th>
                                <th class="fw-semibold text-dark">
                                    <i class="fas fa-calendar-check me-2 text-success"></i>Días Disponibles
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($holidays as $holiday)
                                <tr>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">
                                                    {{ $holiday->adminUser ? optional($holiday->adminUser)->name.' '.optional($holiday->adminUser)->surname : 'Usuario Borrado' }}
                                                </h6>
                                                <small class="text-muted">Empleado</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-success-subtle text-success fs-6 px-3 py-2">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            {{ number_format($holiday->quantity, 2) }} días
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if($perPage !== 'all')
                    <div class="d-flex justify-content-center mt-4">
                        {{ $holidays->links() }}
                    </div>
                @endif
            @else
                <!-- Estado Vacío -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-umbrella-beach fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted mb-3">No hay registros de vacaciones</h4>
                    <p class="text-muted mb-0">No se encontraron empleados con vacaciones registradas.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('partials.toast')
<script>

    $('#denyHolidays').on('click', function(e){
        e.preventDefault();
        let id = $(this).data('id'); // Usa $(this) para obtener el atributo data-id
        botonAceptar(id);
    })

    function botonAceptar(id){
        // Salta la alerta para confirmar la eliminacion
        Swal.fire({
            title: "¿Va a rechazar ésta petición de vacaciones.?",
            showDenyButton: false,
            showCancelButton: true,
            confirmButtonText: "Rechazar petición",
            cancelButtonText: "Cancelar",
            // denyButtonText: `No Borrar`
        }).then((result) => {
            /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                // Llamamos a la funcion para borrar el usuario
                $.when( denyHolidays(id) ).then(function( data, textStatus, jqXHR ) {
                    console.log(data)
                    if (!data.status) {
                        // Si recibimos algun error
                        Toast.fire({
                            icon: "error",
                            title: data.mensaje
                        })
                    } else {
                        // Todo a ido bien
                        Toast.fire({
                            icon: "success",
                            title: data.mensaje
                        })
                        .then(() => {
                            window.location.href = "{{ route('holiday.admin.petitions') }}";
                        })
                    }
                });
            }
        });
    }

    function denyHolidays(id) {
        // Ruta de la peticion
        const url = '{{route("holiday.admin.denyHolidays")}}';
        // Peticion
        return $.ajax({
            type: "POST",
            url: url,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            data: {
                'id': id,
            },
            dataType: "json"
        });
    }
</script>

@endsection

