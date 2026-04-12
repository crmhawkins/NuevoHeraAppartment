@extends('layouts.appAdmin')

@section('scriptHead')
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.9/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.9/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@3.10.2/dist/locale/es.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            // Mapeo de apartamento_id a colores
            var apartmentColors = {
                1: ['#769ECB', 'white'], // Color para apartamento_id 1
                2: ['#9DBAD5', 'white'], // Color para apartamento_id 2
                3: ['#FAF3DD', 'black'], // Color para apartamento_id 3
                4: ['#C8D6B9', 'black'], // Color para apartamento_id 3
                5: ['#DFD8C0', 'black'], // Color para apartamento_id 3
                6: ['#8FC1A9', 'white'], // Color para apartamento_id 3
                7: ['#7CAA98', 'white'], // Color para apartamento_id 3
                // ... más mapeos de colores para diferentes IDs de apartamento
            };
          var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            events: function(fetchInfo, successCallback, failureCallback) {
                fetch('/get-reservas')
                    .then(response => response.json())
                    .then(data => {
                        var events = data.map(function(reserva) {
                            console.log(apartmentColors[reserva.apartamento_id][1])
                            return {
                            title: reserva.cliente.alias, // o cualquier otro campo que quieras usar como título
                            start: reserva.fecha_entrada,
                            end: reserva.fecha_salida,
                            backgroundColor: apartmentColors[reserva.apartamento_id][0] || '#378006', // Color por defecto si no se encuentra un mapeo
                            borderColor: apartmentColors[reserva.apartamento_id][0] || '#378006', // Color por defecto si no se encuentra un mapeo
                            textColor: apartmentColors[reserva.apartamento_id][1] || '#378006', // Color por defecto si no se encuentra un mapeo
                            ...reserva
                            // Aquí puedes añadir más propiedades según necesites
                            };
                        });
                        successCallback(events);
                    })
                    .catch(error => {
                        failureCallback(error);
                    });
            },
            eventClick: function(info) {
                // info.event contiene la información del evento clickeado
                var eventObj = info.event;
                console.log(eventObj);

                // Función para formatear la fecha en formato YYYY-MM-DD
                function formatDate(date) {
                    var d = new Date(date),
                        month = '' + (d.getMonth() + 1),
                        day = '' + d.getDate(),
                        year = d.getFullYear();

                    if (month.length < 2) month = '0' + month;
                    if (day.length < 2) day = '0' + day;

                    return [day, month, year].join('-');
                }

                // Llena la información del modal
                var modal = $('#eventModal');
                modal.find('.modal-body').html(''); // Limpia el contenido anterior
                // Agrega la información del evento al cuerpo del modal. Puedes personalizar esto como quieras.
                modal.find('.modal-body').append('<ul class="list-group">');
                modal.find('.modal-body').append('<li class="list-group-item"><strong>Cliente:</strong> ' + eventObj.title + '</li>');
                modal.find('.modal-body').append('<li class="list-group-item"><strong>Apartamento:</strong> ' + eventObj.extendedProps.apartamento.nombre + '</li>');
                modal.find('.modal-body').append('<li class="list-group-item"><strong>Codigo de la reserva:</strong> ' + eventObj.extendedProps.codigo_reserva + '</li>');
                modal.find('.modal-body').append('<li class="list-group-item"><strong>Fecha de Entrada:</strong> ' + formatDate(eventObj.start) + '</li>');
                modal.find('.modal-body').append('<li class="list-group-item"><strong>Fecha de Salida:</strong> ' + formatDate(eventObj.end) + '</li>');
                modal.find('.modal-body').append('<li class="list-group-item"><strong>Origen:</strong> ' + eventObj.extendedProps.origen + '</li>');
                modal.find('.modal-body').append('</ul>');
                // ... Agrega más campos como necesites

                // Muestra el modal
                modal.modal('show');
            }
          });

          calendar.render();
        });
    </script>
@endsection

@section('content')
<!-- Incluir el CSS de Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- Incluir Flatpickr y la localización en español -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-calendar-check text-primary me-2"></i>
            Gestión de Reservas
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Reservas</li>
            </ol>
        </nav>
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

<!-- Tarjeta de Acciones -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-tools text-primary me-2"></i>
                Acciones
            </h5>
            <div class="d-flex gap-2">
                <a href="{{ route('reservas.create') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Crear Reserva
                </a>
                <button type="button" 
                        class="btn btn-success btn-lg" 
                        data-bs-toggle="tooltip" 
                        title="Ver ocupación de apartamentos"
                        onclick="mostrarOcupacionHoy()">
                    <i class="fas fa-calendar-check me-2"></i>
                    Ocupación Hoy
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Tarjeta de Filtros -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-filter text-primary me-2"></i>
            Filtros de Búsqueda
        </h5>
    </div>
    <div class="card-body">
        @php
            $orderDirection = request()->get('direction', 'asc') == 'asc' ? 'desc' : 'asc';
        @endphp
        
        <form action="{{ route('reservas.index') }}" method="GET" id="filtrosForm">
            <!-- Campos ocultos para mantener el orden y la dirección -->
            <input type="hidden" name="order_by" value="{{ request()->get('order_by', 'fecha_entrada') }}">
            <input type="hidden" name="direction" value="{{ request()->get('direction', 'asc') }}">
            
            <div class="row g-3">
                <div class="col-md-2">
                    <label for="perPage" class="form-label fw-semibold">
                        <i class="fas fa-list-ol text-primary me-1"></i>
                        Registros por página
                    </label>
                    <select name="perPage" id="perPage" class="form-select" onchange="this.form.submit()">
                        <option value="10" {{ request()->get('perPage') == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request()->get('perPage') == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request()->get('perPage') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request()->get('perPage') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="filtro_estado" class="form-label fw-semibold">
                        <i class="fas fa-toggle-on text-primary me-1"></i>
                        Estado
                    </label>
                    <select name="filtro_estado" id="filtro_estado" class="form-select" onchange="this.form.submit()">
                        <option value="activas" {{ request()->get('filtro_estado', 'activas') == 'activas' ? 'selected' : '' }}>Reservas Activas</option>
                        <option value="eliminadas" {{ request()->get('filtro_estado') == 'eliminadas' ? 'selected' : '' }}>Reservas Eliminadas</option>
                        <option value="todas" {{ request()->get('filtro_estado') == 'todas' ? 'selected' : '' }}>Todas las Reservas</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="search" class="form-label fw-semibold">
                        <i class="fas fa-search text-primary me-1"></i>
                        Buscar
                    </label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Buscar por cliente, código..." 
                           value="{{ request()->get('search') }}"
                           onkeypress="if(event.key==='Enter') this.form.submit()">
                </div>
                
                <div class="col-md-2">
                    <label for="filtro_apartamento" class="form-label fw-semibold">
                        <i class="fas fa-building text-primary me-1"></i>
                        Apartamento
                    </label>
                    <select class="form-select" name="filtro_apartamento" id="filtro_apartamento">
                        <option value="">Todos</option>
                        @foreach($apartamentos as $apartamento)
                            <option value="{{ $apartamento->id }}"
                                {{ request()->get('filtro_apartamento') == $apartamento->id ? 'selected' : '' }}>
                                {{ $apartamento->titulo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="fecha_entrada" class="form-label fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i>
                        Fecha Inicio
                    </label>
                    <input type="text" class="form-control" id="fecha_entrada" name="fecha_entrada" 
                           value="{{ request()->get('fecha_entrada') }}" placeholder="dd/mm/yyyy">
                </div>
                
                <div class="col-md-2">
                    <label for="fecha_salida" class="form-label fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i>
                        Fecha Fin
                    </label>
                    <input type="text" class="form-control" id="fecha_salida" name="fecha_salida" 
                           value="{{ request()->get('fecha_salida') }}" placeholder="dd/mm/yyyy">
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            Buscar
                        </button>
                        <button type="button" id="limpiarFiltros" class="btn btn-outline-secondary">
                            <i class="fas fa-trash me-2"></i>
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tarjeta Principal -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            Lista de Reservas
        </h5>
    </div>
    <div class="card-body p-0">
        @if($reservas->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'id', 'direction' => (request()->get('order_by') == 'id' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}" 
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-hashtag text-primary me-1"></i>ID
                                    @if(request()->get('order_by') == 'id')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'apartamento_id', 'direction' => (request()->get('order_by') == 'apartamento_id' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}" 
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-building text-primary me-1"></i>Apartamento
                                    @if(request()->get('order_by') == 'apartamento_id')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'cliente_id', 'direction' => (request()->get('order_by') == 'cliente_id' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}" 
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-user text-primary me-1"></i>Cliente
                                    @if(request()->get('order_by') == 'cliente_id')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-money-bill-wave text-primary me-1"></i>Pago
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'dni_entregado', 'direction' => (request()->get('order_by') == 'dni_entregado' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-id-card text-primary me-1"></i>DNI
                                    @if(request()->get('order_by') == 'dni_entregado')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'mir_enviado', 'direction' => (request()->get('order_by') == 'mir_enviado' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-building text-primary me-1"></i>MIR
                                    @if(request()->get('order_by') == 'mir_enviado')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'created_at', 'direction' => (request()->get('order_by') == 'created_at' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-calendar text-primary me-1"></i>F.Reserva
                                    @if(request()->get('order_by') == 'created_at')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'fecha_entrada', 'direction' => (request()->get('order_by') == 'fecha_entrada' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-calendar-plus text-primary me-1"></i>Entrada
                                    @if(request()->get('order_by') == 'fecha_entrada')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'fecha_salida', 'direction' => (request()->get('order_by') == 'fecha_salida' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}" 
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-calendar-minus text-primary me-1"></i>Salida
                                    @if(request()->get('order_by') == 'fecha_salida')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'origen', 'direction' => (request()->get('order_by') == 'origen' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}" 
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-globe text-primary me-1"></i>Origen
                                    @if(request()->get('order_by') == 'origen')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <a href="{{ route('reservas.index', ['order_by' => 'codigo_reserva', 'direction' => (request()->get('order_by') == 'codigo_reserva' ? $orderDirection : 'asc'), 'search' => request()->get('search'),'perPage' => request()->get('perPage'), 'fecha_entrada' => request()->get('fecha_entrada'), 'fecha_salida' => request()->get('fecha_salida')]) }}" 
                                   class="text-decoration-none text-dark fw-semibold">
                                    <i class="fas fa-barcode text-primary me-1"></i>Código
                                    @if(request()->get('order_by') == 'codigo_reserva')
                                        @if(request()->get('direction') == 'asc')
                                            <i class="fas fa-sort-up text-primary"></i>
                                        @else
                                            <i class="fas fa-sort-down text-primary"></i>
                                        @endif
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-key text-primary me-1"></i>Código
                            </th>
                            <th scope="col" class="border-0" data-bs-toggle="tooltip" title="Cerradura">
                                <i class="fas fa-lock text-primary"></i>
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-euro-sign text-primary me-1"></i>Precio
                            </th>
                            <th scope="col" class="border-0">
                                <i class="fas fa-cogs text-primary me-1"></i>Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reservas as $reserva)
                            <tr ondblclick="confirmarEliminacion({{ $reserva->id }}, '{{ $reserva->cliente->alias }}', '{{ $reserva->codigo_reserva }}')" 
                                style="cursor: pointer;" 
                                title="Doble clic para eliminar reserva">
                                <td>
                                    <span class="badge bg-primary-subtle text-primary fw-bold">#{{ $reserva->id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-building text-info"></i>
                                        </div>
                                        <span class="fw-semibold">{{ $reserva->apartamento->titulo }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-user text-success"></i>
                                        </div>
                                        <span class="fw-semibold">{{ $reserva->cliente->alias }}</span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $pagoStripe = \App\Models\Pago::where('reserva_id', $reserva->id)->where('estado', 'completado')->exists();
                                        $pagoBanco = \Illuminate\Support\Facades\Schema::hasColumn('ingresos', 'reserva_id')
                                            ? \App\Models\Ingresos::where('reserva_id', $reserva->id)->exists()
                                            : false;
                                        $pagado = $pagoStripe || $pagoBanco;
                                    @endphp
                                    @if($pagado)
                                        <span class="badge bg-success-subtle text-success" title="{{ $pagoStripe ? 'Stripe' : 'Banco' }}"><i class="fas fa-check me-1"></i>SI</span>
                                    @elseif($reserva->origen !== 'Web')
                                        <span class="badge bg-warning-subtle text-warning" title="Pendiente verificar en banco"><i class="fas fa-clock me-1"></i>?</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger"><i class="fas fa-times me-1"></i>NO</span>
                                    @endif
                                </td>
                                <td>
                                    @if($reserva->dni_entregado == 1)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check me-1"></i>SI
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="fas fa-times me-1"></i>NO
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($reserva->mir_enviado)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check me-1"></i>SI
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="fas fa-times me-1"></i>NO
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $reserva->created_at ? $reserva->created_at->format('d/m/Y') : '-' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-calendar-plus text-warning"></i>
                                        </div>
                                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-danger-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-calendar-minus text-danger"></i>
                                        </div>
                                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-globe text-info"></i>
                                        </div>
                                        <span class="fw-semibold">{{ $reserva->origen }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-secondary-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-barcode text-secondary"></i>
                                        </div>
                                        <span class="fw-semibold">{{ $reserva->codigo_reserva }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($reserva->codigo_acceso)
                                        <code class="fw-bold">{{ $reserva->codigo_acceso }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$reserva->codigo_acceso)
                                        <span class="badge bg-secondary" title="Sin código">—</span>
                                    @elseif($reserva->codigo_enviado_cerradura)
                                        <span class="badge bg-success" title="Programada en cerradura">OK</span>
                                    @else
                                        <span class="badge bg-warning text-dark" title="Sin programar en cerradura">!</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold fs-5 text-success">
                                        <i class="fas fa-euro-sign me-1"></i>{{ number_format($reserva->precio, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('reservas.show', $reserva->id) }}" 
                                           class="btn btn-outline-info btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(request()->get('filtro_estado') == 'eliminadas')
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm" 
                                                    onclick="confirmarRestauracion({{ $reserva->id }}, '{{ $reserva->cliente->alias }}', '{{ $reserva->codigo_reserva }}')"
                                                    data-bs-toggle="tooltip" 
                                                    title="Restaurar reserva">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        @else
                                            <a href="{{ route('reservas.edit', $reserva->id) }}" 
                                               class="btn btn-outline-warning btn-sm" 
                                               data-bs-toggle="tooltip" 
                                               title="Editar reserva">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm" 
                                                    onclick="confirmarEliminacion({{ $reserva->id }}, '{{ $reserva->cliente->alias }}', '{{ $reserva->codigo_reserva }}')"
                                                    ondblclick="confirmarEliminacion({{ $reserva->id }}, '{{ $reserva->cliente->alias }}', '{{ $reserva->codigo_reserva }}')"
                                                    data-bs-toggle="tooltip" 
                                                    title="Eliminar reserva (clic o doble clic)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay reservas disponibles</h5>
                <p class="text-muted">No se encontraron reservas con los filtros aplicados.</p>
            </div>
        @endif
    </div>
    
    @if($reservas->count() > 0)
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-center">
                {{ $reservas->appends(request()->except('page'))->links() }}
            </div>
        </div>
    @endif
</div>



@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Flatpickr en los campos de fecha con localización en español
        flatpickr("#fecha_entrada", {
            dateFormat: "Y-m-d",
            locale: "es", // Configurar el idioma español usando "es"
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('fecha_entrada').value = dateStr; // Actualizar el valor del input
            }
        });

        flatpickr("#fecha_salida", {
            dateFormat: "Y-m-d",
            locale: "es", // Configurar el idioma español usando "es"
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('fecha_salida').value = dateStr; // Actualizar el valor del input
            }
        });

        // Limpiar filtros
        document.getElementById('limpiarFiltros').addEventListener('click', function () {
            document.getElementById('search').value = '';
            document.getElementById('fecha_entrada').value = '';
            document.getElementById('fecha_salida').value = '';
            document.getElementById('filtro_apartamento').value = '';
            document.getElementById('filtro_estado').value = 'activas';
            window.location.href = "{{ route('reservas.index') }}";
        });

        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Función para confirmar eliminación de reserva
    function confirmarEliminacion(id, cliente, codigo) {
        Swal.fire({
            title: '⚠️ Eliminar Reserva',
            html: `
                <div class="text-center">
                    <div class="alert alert-danger mb-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h5 class="mb-2"><strong>¡ATENCIÓN!</strong></h5>
                        <p class="mb-0">Esta acción es <strong>IRREVERSIBLE</strong> y eliminará permanentemente la reserva del sistema.</p>
                    </div>
                    <div class="card border-warning">
                        <div class="card-body text-start">
                            <h6 class="card-title text-warning">
                                <i class="fas fa-info-circle me-2"></i>Detalles de la Reserva:
                            </h6>
                            <p class="mb-1"><strong>Cliente:</strong> ${cliente}</p>
                            <p class="mb-0"><strong>Código de Reserva:</strong> <code>${codigo}</code></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            La reserva será marcada como eliminada y no aparecerá en los listados activos.
                        </p>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, Eliminar Definitivamente',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
            customClass: {
                confirmButton: 'btn btn-danger btn-lg',
                cancelButton: 'btn btn-secondary btn-lg'
            },
            buttonsStyling: false,
            focusCancel: true,
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading mientras se procesa
                Swal.fire({
                    title: 'Eliminando...',
                    text: 'Por favor espera mientras se procesa la eliminación',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Crear formulario temporal para enviar la petición DELETE
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ route('reservas.destroy', '') }}/${id}`;
                
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
    }

    // Función para confirmar restauración de reserva
    function confirmarRestauracion(id, cliente, codigo) {
        Swal.fire({
            title: '¿Restaurar Reserva?',
            html: `
                <div class="text-start">
                    <p><strong>Cliente:</strong> ${cliente}</p>
                    <p><strong>Código de Reserva:</strong> ${codigo}</p>
                    <p class="text-success mt-3"><strong>La reserva volverá a estar activa en el sistema.</strong></p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-undo me-2"></i>Sí, Restaurar',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario temporal para enviar la petición POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ route('reservas.restore', '') }}/${id}`;
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                
                form.appendChild(csrfToken);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // Función para mostrar la ocupación de apartamentos (fuera de DOMContentLoaded)
    window.mostrarOcupacionHoy = function() {
        // Llenar información general
        const hoy = new Date().toLocaleDateString('es-ES');
        document.getElementById('apartamentoNombre').textContent = 'Todos los apartamentos';
        document.getElementById('fechaEntrada').textContent = hoy;

        // Obtener apartamentos con ocupación
        fetch('/get-apartamentos-ocupacion')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('apartamentosContainer');
                container.innerHTML = '';
                
                                // Ordenar apartamentos: libres primero, entradas hoy, luego ocupados desde antes
                const apartamentosOrdenados = data.sort((a, b) => {
                    const hoy = new Date();
                    hoy.setHours(0, 0, 0, 0);
                    
                    const getEstado = (apartamento) => {
                        if (!apartamento.reservas || apartamento.reservas.length === 0) {
                            return 0; // Libre
                        }
                        
                        // Buscar entrada de hoy
                        const entradaHoy = apartamento.reservas.find(reserva => {
                            const entrada = new Date(reserva.fecha_entrada);
                            entrada.setHours(0, 0, 0, 0);
                            return entrada.getTime() === hoy.getTime();
                        });
                        
                        if (entradaHoy) {
                            return 1; // Entrada hoy
                        }
                        
                        // Buscar reserva activa (ocupada actualmente)
                        const reservaActiva = apartamento.reservas.find(reserva => {
                            const entrada = new Date(reserva.fecha_entrada);
                            const salida = new Date(reserva.fecha_salida);
                            entrada.setHours(0, 0, 0, 0);
                            salida.setHours(0, 0, 0, 0);
                            return entrada <= hoy && salida > hoy;
                        });
                        
                        if (reservaActiva) {
                            return 2; // Ocupado desde antes
                        }
                        
                        return 0; // Libre
                    };
                    
                    return getEstado(a) - getEstado(b);
                });
                
                apartamentosOrdenados.forEach(apartamento => {
                    const row = document.createElement('tr');
                    
                    let estadoTexto = 'Libre';
                    let estadoColor = '';
                    let rowClass = '';
                    let fechasHtml = '-';
                    let clienteHtml = '-';
                    let codigoHtml = '-';
                    
                                           if (apartamento.reservas && apartamento.reservas.length > 0) {
                           const hoy = new Date();
                           hoy.setHours(0, 0, 0, 0);

                           // Buscar entrada de hoy
                           const entradaHoy = apartamento.reservas.find(reserva => {
                               const entrada = new Date(reserva.fecha_entrada);
                               entrada.setHours(0, 0, 0, 0);
                               return entrada.getTime() === hoy.getTime();
                           });

                           // Buscar reserva activa (ocupada actualmente)
                           // IMPORTANTE: Solo considerar reservas que estén activas HOY (entrada <= hoy < salida)
                           // Esto excluye automáticamente las que salen hoy
                           const reservaActiva = apartamento.reservas.find(reserva => {
                               const entrada = new Date(reserva.fecha_entrada);
                               const salida = new Date(reserva.fecha_salida);
                               entrada.setHours(0, 0, 0, 0);
                               salida.setHours(0, 0, 0, 0);
                               // Solo considerar reservas que estén activas HOY (entrada <= hoy < salida)
                               return entrada <= hoy && salida > hoy;
                           });

                           if (entradaHoy) {
                               // Entrada hoy
                               estadoTexto = 'Entrada hoy';
                               estadoColor = 'badge bg-warning text-dark';
                               rowClass = 'table-warning';
                               fechasHtml = `${entradaHoy.fecha_entrada} - ${entradaHoy.fecha_salida}`;
                               clienteHtml = entradaHoy.cliente_alias;
                               codigoHtml = entradaHoy.codigo_reserva;
                           } else if (reservaActiva) {
                               // Ocupado desde antes
                               estadoTexto = 'Ocupado (desde antes)';
                               estadoColor = 'badge bg-info text-white';
                               rowClass = 'table-info';
                               fechasHtml = `${reservaActiva.fecha_entrada} - ${reservaActiva.fecha_salida}`;
                               clienteHtml = reservaActiva.cliente_alias;
                               codigoHtml = reservaActiva.codigo_reserva;
                           } else {
                               // Libre (incluye apartamentos que salen hoy)
                               estadoTexto = 'Libre';
                               estadoColor = 'badge bg-success text-white';
                               rowClass = 'table-success';
                           }
                       } else {
                           // Libre
                           estadoTexto = 'Libre';
                           estadoColor = 'badge bg-success text-white';
                           rowClass = 'table-success';
                       }
                    
                    row.className = rowClass;
                    row.innerHTML = `
                        <td class="fw-semibold">
                            <i class="fas fa-building text-primary me-2"></i>
                            ${apartamento.nombre}
                        </td>
                        <td>
                            <span class="${estadoColor}">${estadoTexto}</span>
                        </td>
                        <td class="text-muted">${fechasHtml}</td>
                        <td class="text-muted">${clienteHtml}</td>
                        <td class="text-muted">${codigoHtml}</td>
                    `;
                    
                    container.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('apartamentosContainer').innerHTML = 
                    '<tr><td colspan="5" class="text-center text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar la ocupación</td></tr>';
            });

        // Mostrar el modal
        const modal = new bootstrap.Modal(document.getElementById('modalOcupacion'));
        modal.show();
    };
</script>

<!-- Modal de Ocupación -->
<div class="modal fade" id="modalOcupacion" tabindex="-1" aria-labelledby="modalOcupacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalOcupacionLabel">
                    <i class="fas fa-calendar-check me-2"></i>
                    Ocupación de Apartamentos - Hoy
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Información General -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary-subtle">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Información de Ocupación
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Fecha Consulta:</strong> <span id="fechaEntrada"></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Apartamentos:</strong> <span id="apartamentoNombre"></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Estado:</strong> <span class="text-success fw-bold">Ocupación Actual</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado de Ocupación -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-building text-primary me-2"></i>
                                    Estado de Ocupación por Apartamento
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-0">
                                                    <i class="fas fa-building text-primary me-2"></i>
                                                    Apartamento
                                                </th>
                                                <th class="border-0">
                                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                                    Estado
                                                </th>
                                                <th class="border-0">
                                                    <i class="fas fa-calendar text-primary me-2"></i>
                                                    Fechas
                                                </th>
                                                <th class="border-0">
                                                    <i class="fas fa-user text-primary me-2"></i>
                                                    Cliente
                                                </th>
                                                <th class="border-0">
                                                    <i class="fas fa-barcode text-primary me-2"></i>
                                                    Código
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="apartamentosContainer">
                                            <!-- Los apartamentos se cargarán aquí dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leyenda -->
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h6 class="mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Leyenda de Estados de Ocupación
                            </h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <span class="badge bg-success me-2">●</span>
                                    <strong>Ocupado desde antes de hoy</strong>
                                </div>
                                <div class="col-md-4">
                                    <span class="badge bg-warning me-2">●</span>
                                    <strong>Entrada hoy</strong>
                                </div>
                                <div class="col-md-4">
                                    <span class="badge bg-secondary me-2">●</span>
                                    <strong>Libre</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para el doble clic en filas de reservas */
tbody tr:hover {
    background-color: rgba(220, 53, 69, 0.05) !important;
    transition: background-color 0.2s ease;
}

tbody tr:hover .btn-outline-danger {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

/* Animación para el botón de eliminar */
.btn-outline-danger {
    transition: all 0.2s ease;
}

.btn-outline-danger:hover {
    transform: scale(1.05);
}

/* Indicador visual para doble clic */
tbody tr[title*="Doble clic"]:hover::after {
    content: " (Doble clic para eliminar)";
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(220, 53, 69, 0.9);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    z-index: 1000;
    pointer-events: none;
}
</style>

@endsection
