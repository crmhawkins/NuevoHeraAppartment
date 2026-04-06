@extends('layouts.appAdmin')

@section('content')

<style>
    .inactive-sort {
        color: #0F1739;
        text-decoration: none;
    }
    .active-sort {
        color: #757191;
    }
    body { font-family: Arial, sans-serif; }
    table { width: 100%; border-collapse: collapse; white-space: nowrap; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background-color: #f2f2f2; }
    .header { background-color: #0f1739; color: white; padding: 20px 20px; margin-bottom: 1rem }
    .fondo_verde {
      background-color: #def7df !important; /* Color de fondo verde para el día de hoy */
    }
    /* Hacer sticky la primera columna (Apartamentos) */
    .apartments-column {
        white-space: nowrap;
        width: auto;
        position: sticky;
        left: 0;
        z-index: 10;
        background-color: white;
    }

    /* Drag handles for resizing */
    .drag-right, .drag-left {
        width: 10px;
        height: 100%;
        position: absolute;
        top: 0;
        cursor: ew-resize;
        z-index: 5;
    }

    .drag-right {
        right: 0;
        background-color: rgba(0, 0, 0, 0.1);
    }

    .drag-left {
        left: 0;
        background-color: rgba(0, 0, 0, 0.1);
    }

    /* Establecer el scroll horizontal */
    .table-responsive {
        overflow-x: auto;
    }
/* 
    .hbdco-new.hbdtco div {
        border-top-color: #ffbb3e;
        color: white;
    }
    .hbdci-new.hbdtci div {
        border-right-color: #ffbb3e;
        color: white;
    }
    .hbdtco div {
        border-right: 45px solid;
        border-top: 36px solid #fff;
        height: 0;
        left: 0;
        position: absolute;
        top: 0;
        width: 0;
    }
    .hb-resa-cal-table td {
        padding: 0;
    }
    .hbio {
        overflow: hidden;
        position: relative;
    }
    .hbdtci.hbdtco span {
        background: #FFF;
        display: block;
        height: 36px;
        left: 8px;
        position: absolute;
        top: -3px;
        transform: rotate(30deg);
        width: 2px;
    }
    .hbd-new .hbdlcd {
        background: #ffbb3e;
        color: white;
    }
    .hbdt a, .hbdbc {
        text-align: center;
    }
    .hbdli {
        text-indent: -5px;
    }
    .hbdbc, .hbdlcd {
        color: #fff;
        display: inline-block;
        font-weight: 600;
        position: relative;
        text-align: left;
        text-decoration: none;
        white-space: nowrap;
        z-index: 89;
    }

    .hbd-new.hbdt {
        background: #ffbb3e;
        border-right: 1px solid #ffbb3e;
    }
    .hb-resa-cal-table td {
        padding: 0;
    }
    .hbdt {
        color: #fff;
    }

    .hb-resa-cal-table td {
        padding: 0;
    }

    .hbio {
        overflow: hidden;
        position: relative;
    }
    .hbdci-new.hbdtci div {
        border-right-color: #ffbb3e;
    }

    .hbdtci div {
        border-right: 45px solid;
        border-top: 35px  solid #fff;
        height: 39px;
        left: 0;
        position: absolute;
        top: 0;
        width: 0;
    }

    #hb-resa-cal-table td.hb-resa-day-today-line {
        border-right-color: rgba(0, 0, 0, 0.2);
    }
    .hb-resa-cal-table {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-collapse: collapse;
        border-spacing: 0;
        font-size: 14px;
    }

    #hb-resa-cal-table td.hb-resa-day-label {
        border-bottom: 2px solid #E5E5E5;
        border-right: 1px solid #e5e5e5;
        height: 80px;
        text-align: center;
        width: 40px;
        min-width: 50px;
    }
    .hb-resa-day-label span span {
        display: block;
        font-size: 12px;
    }
    #hb-resa-cal-table td {
        border-bottom: 1px solid #E5E5E5;
        height: 30px;
        padding: 0 !important;
        text-align: center;
        width: 20px !important;
        min-width: 20px;
    }
    td.hbio.hbdtci.hbdci-new{
        min-width: 10px;

    } */
</style>
<style>
    table {
        border-collapse: collapse;
        width:max-content !important;
    }

    th, td {
        border: 1px solid black;
        text-align: center;
        padding: 5px;
    }

    th {
        width: 80px; /* El doble del ancho porque tiene colspan="2" */
    }

    td {
        width: 20px; /* La mitad del espacio en cada columna de 40px */
    }

    .half-width {
        width: 20px;
    }
    .tabla-reservas {
        width: 100%;
        overflow-x: scroll
    }
</style>
<div class="container-fluid">
    <div class="d-flex flex-colum mb-3">
        <h2 class="mb-0 me-3 encabezado_top">{{ __('Tabla de Reservas') }}</h2>
    </div>
    <hr class="mb-5">
    <div class="row justify-content-center">
        <div class="header d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.tablaReservas.index', ['date' => \Carbon\Carbon::createFromFormat('Y-m', $date)->subMonth()->format('Y-m')]) }}" class="btn bg-color-quinto">Mes Anterior</a>
            <h3>{{ $monthName }}</h3>
            <a href="{{ route('admin.tablaReservas.index', ['date' => \Carbon\Carbon::createFromFormat('Y-m', $date)->addMonth()->format('Y-m')]) }}" class="btn bg-color-quinto">Mes Siguiente</a>
        </div>
        @if ($apartamentos)
        <!-- Contenedor con scroll horizontal -->
        {{-- class="table-responsive p-0" --}}
        {{-- class="table-responsive p-0" --}}
        {{-- class="table table-bordered" id="hb-resa-cal-table" --}}
        <div class="tabla-reservas">
            <table>
                <thead>
                    <tr>
                        <th colspan="2">Apartamentos</th>
                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $fechaHoy = \Carbon\Carbon::now(); // Obtener la fecha actual
                                $claseDiaHoy = $day == $fechaHoy->day ? 'fondo_verde' : ''; // Agregar la clase si es el día de hoy
                            @endphp
                            <th colspan="2" class="{{ $claseDiaHoy }}">Día {{ $day }}</th>
                        @endfor
                    </tr>
                </thead>
                <style>
                    .hbdbc, .hbdlcd {
                        color: #fff;
                        display: inline-block;
                        font-weight: 600;
                        position: relative;
                        text-align: left;
                        text-decoration: none;
                        white-space: nowrap;
                        z-index: 89;
                    }
                    .hbd-new .hbdlcd {
                        background: #ffbb3e;
                    }


                    #hb-resa-cal-table td {
                        border-bottom: 1px solid #E5E5E5;
                        height: 30px;
                        padding: 0;
                        text-align: center;
                        width: 20px;
                        border: 0;
                    }
                    .hb-resa-cal-table td {
                        padding: 0;
                    }
                    .hbio {
                        overflow: hidden;
                        position: relative;
                    }


                    .hbdci-confirmed.hbdtci div {
                        border-right-color: #5dc807;
                    }

                    .hbdci-new.hbdtci div {
                        border-right-color: #ffbb3e;
                    }
                    .hbdco-new.hbdtco div {
                        border-top-color: #ffbb3e;
                    }
                    .hbdco-new-ini.hbdtco div {
                        border-top-color: white;
                    }
                    .hbdtco div {
                        border-right: 41px solid #FFF;
                        border-top: 33px solid;
                        height: 0;
                        left: 0;
                        position: absolute;
                        top: 0;
                        width: 0;
                    }
                    .hbdtci div {
                        border-right: 41px solid #FFF;
                        border-top: 33px solid;
                        height: 0;
                        left: 0;
                        position: absolute;
                        top: 0;
                        width: 0;
                    }
                    .hbdtci.hbdtco span {
                        background: #FFF;
                        display: block;
                        height: 59px;
                        left: 15px;
                        position: absolute;
                        top: -12px;
                        transform: rotate(51deg);
                        width: 2px;
                    }
                    .overlap-entry-exit {
                        background-color: #f5c6cb !important; /* Color diferente para marcar la coincidencia */
                        color: #721c24; /* Texto rojo */
                    }
                    td a {
                        color: white;
                        font-weight: 600;
                        text-decoration: none;
                    }
                
                </style>
                <tbody id="hb-resa-cal-table">
                    @foreach ($apartamentos as $apartamento)
                        <tr style="border-bottom: 2px solid rgb(231, 231, 231)">
                            <td colspan="2">{{ $apartamento->titulo }}</td>
                            @for ($day = 1; $day <= $daysInMonth; $day++)
                                @php
                                    $fechaHoy = \Carbon\Carbon::now(); // Obtener la fecha actual
                                    $claseDiaHoy = $day == $fechaHoy->day ? 'fondo_verde' : ''; // Agregar la clase si es el día de hoy
                                    $reservaSalida = null; // Reservas que salen el día actual
                                    $reservaEntrada = null; // Reservas que entran el día actual
                                    $reservaIntermedia = null; // Para detectar días intermedios entre entrada y salida
                                    $haySolape = false; // Variable para detectar solape
                
                                    // Buscar si alguna reserva sale en este día
                                    foreach ($apartamento->reservas as $itemReserva) {
                                        if ($itemReserva->fecha_salida->day == $day) {
                                            $reservaSalida = $itemReserva;
                                            if ($day == 4) {
                                                // dd('Dia 4 en Salida: '. $reservaSalida);

                                            }
                                        }
                                    }
                
                                    // Buscar si alguna reserva entra en este día
                                    foreach ($apartamento->reservas as $itemReserva) {
                                        if ($itemReserva->fecha_entrada->day == $day) {
                                            $reservaEntrada = $itemReserva;
                                        }
                
                                        // Detectar días intermedios entre entrada y salida
                                        if ($itemReserva->fecha_entrada->day < $day && $itemReserva->fecha_salida->day > $day) {
                                            $reservaIntermedia = $itemReserva;
                                        }
                                    }
                
                                    // Verificar si hay solape
                                    if ($reservaSalida && $reservaEntrada && $reservaSalida->fecha_salida->day == $reservaEntrada->fecha_entrada->day) {
                                        $haySolape = true;
                                    }
                
                                @endphp
                                    {{-- Segunda celda del día (para la entrada) --}}
                                @if ($reservaEntrada)
                                    @if ($haySolape)
                                        @if ($reservaSalida) 
                                            @if ($haySolape)
                                            <td class="hbio hbdtco hbdco-new hbdtci hbdci-new {{ $claseDiaHoy }}">
                                                <div></div>
                                                <span></span>
                                                {{-- <a href="#modalReserva{{ $reservaEntrada->id }}" data-bs-toggle="modal">{{ $reservaEntrada->id }}</a> --}}
                                            </td> 
                                            <td class="half-width {{ $claseDiaHoy }} bg-warning">
                                                <a href="#modalReserva{{ $reservaEntrada->id }}" data-bs-toggle="modal">{{ $reservaEntrada->id }}</a>
                                            </td>
                                            {{-- {{dd('Reserva Salida Solape')}} --}}
                                            @else

                                                <td class="hbio hbdtco hbdco-new {{ $claseDiaHoy }}">
                                                    <div></div>
                                                    <span></span>
                                                    {{-- <a href="#modalReserva{{ $reservaEntrada->id }}" data-bs-toggle="modal">{{ $reservaEntrada->id }}</a> --}}
                                                </td>  
                                                {{dd('Reserva Salida Sin Solape')}}

                                            @endif
                                        @endif
                                        {{-- <td class="hbio hbdtco hbdco-new hbdtci hbdci-new">
                                            <div></div>
                                            <span></span>
                                        </td>  --}}
                                        {{-- <a href="#modalReserva{{ $reservaEntrada->id }}" data-bs-toggle="modal">{{ $reservaEntrada->id }}</a> --}}
                                        {{-- {{dd('Reserva Entrada Solape: '. $day)}} --}}
                                        {{-- La reserva de entrada empieza en la segunda celda porque coincide con una salida --}}
                                        {{-- <td class="half-width {{ $claseDiaHoy }} bg-warning">
                                            <a href="#modalReserva{{ $reservaEntrada->id }}" data-bs-toggle="modal">{{ $reservaEntrada->id }}</a>
                                        </td> --}}
                                    @else
                                    
                                        <td class="hbio hbdtco hbdco-new-ini hbdtci hbdci-new {{ $claseDiaHoy }}">
                                            <div></div>
                                            <span></span>
                                            {{-- <a href="#modalReserva{{ $reservaEntrada->id }}" data-bs-toggle="modal">{{ $reservaEntrada->id }}</a> --}}
                                        </td>  
                                        <td class="half-width {{ $claseDiaHoy }} bg-warning">
                                            <a href="#modalReserva{{ $reservaEntrada->id }}" data-bs-toggle="modal">{{ $reservaEntrada->id }}</a>
                                        </td>
                                        {{-- {{dd('Reserva Entrada Sin Solape')}} --}}
                                    @endif
                                @elseif ($reservaSalida) 
                                    @if ($haySolape)
                                        {{dd('Reserva Salida Solape')}}
                                        <td class="hbio hbdtco hbdco-new hbdtci hbdci-new {{ $claseDiaHoy }}">
                                            <div></div>
                                            <span></span>
                                            {{-- <a href="#modalReserva{{ $reservaEntrada->id }}" data-bs-toggle="modal">{{ $reservaEntrada->id }}</a> --}}
                                        </td>  
                                    @else

                                        <td class="hbio hbdtco hbdco-new {{ $claseDiaHoy }}">
                                            <div></div>
                                            <span></span>
                                            {{-- <a href="#modalReserva{{ $reservaEntrada->id }}" data-bs-toggle="modal">{{ $reservaEntrada->id }}</a> --}}
                                        </td>  
                                        {{-- {{dd('Reserva Salida Sin Solape')}} --}}
                                        <td class="half-width {{ $claseDiaHoy }}"></td>

                                    @endif

                                @elseif ($reservaIntermedia)
                                    {{-- {{dd($reservaSalida)}}
                                    {{dd('Reserva Intermedia en Salida: '. $day)}} --}}

                                    <td class="half-width {{ $claseDiaHoy }} bg-warning">
                                        <a href="#modalReserva{{ $reservaIntermedia->id }}" data-bs-toggle="modal">{{ $reservaIntermedia->id }}</a>
                                    </td>
                                    <td class="half-width {{ $claseDiaHoy }} bg-warning"></td>
                                @else     
                                    <td class="half-width {{ $claseDiaHoy }}"></td>
                                    <td class="half-width {{ $claseDiaHoy }}"></td>

                                @endif
                              
                
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
                
                
            </table>
        </div>
          
        {{-- Modal para crear nueva reserva --}}
        <div class="modal fade" id="modalCrearReserva" tabindex="-1" aria-labelledby="modalCrearReservaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCrearReservaLabel">Crear Nueva Reserva</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formCrearReserva" method="POST" action="{{ route('reservas.store') }}">
                            @csrf
                            <input type="hidden" id="apartamentoId" name="apartamento_id" value="">
                            <input type="hidden" id="fechaReserva" name="fecha_reserva" value="">

                            <div class="mb-3">
                                <label for="cliente" class="form-label">Cliente</label>
                                <input type="text" class="form-control" id="cliente" name="cliente" required>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_entrada" class="form-label">Fecha de Entrada</label>
                                <input type="date" class="form-control" id="fecha_entrada" name="fecha_entrada" required>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_salida" class="form-label">Fecha de Salida</label>
                                <input type="date" class="form-control" id="fecha_salida" name="fecha_salida" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Crear Reserva</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($apartamentos as $apartamento)
            @foreach ($apartamento->reservas as $itemReserva)
              <!-- Modal para visualizar reserva existente -->
              <div class="modal fade" id="modalReserva{{ $itemReserva->id }}" tabindex="-1" aria-labelledby="modalReserva{{ $itemReserva->id }}" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalLabel{{ $itemReserva->id }}">Detalles de la Reserva</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <p><strong>Cliente:</strong> {{ $itemReserva->cliente->nombre }}</p>
                      <p><strong>Fecha de Entrada:</strong> {{ $itemReserva->fecha_entrada->format('d/m/Y') }}</p>
                      <p><strong>Fecha de Salida:</strong> {{ $itemReserva->fecha_salida->format('d/m/Y') }}</p>
                      <p><strong>Detalles adicionales:</strong> {{ $itemReserva->detalles }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
        @endforeach
      @endif
    </div>
</div>

<script>
    const reservas = @json($apartamentos);
    console.log(reservas);
    let dragType = '';  // Para saber si estamos ajustando la fecha de entrada o de salida
    let reservaId = ''; // Para saber qué reserva estamos ajustando
    let originalDay = ''; // Día original de la fecha de salida o entrada
    let dragging = false; // Variable para saber si estamos arrastrando

    // Función que se ejecuta cuando comenzamos a arrastrar
    function startDrag(event, type) {
        console.log('startDrag event:', event, 'type:', type); // Debug
        event.dataTransfer.effectAllowed = 'move';
        dragType = type;  // Puede ser 'start' para fecha de entrada o 'end' para fecha de salida
        reservaId = event.target.getAttribute('data-reserva-id');
        originalDay = event.target.closest('td').getAttribute('data-dia'); // Guardar el día original
        dragging = true; // Estamos arrastrando
        console.log('dragType:', dragType, 'reservaId:', reservaId, 'originalDay:', originalDay); // Debug
    }

    // Función para permitir el evento drop en los divs invisibles
    function allowDrop(event) {
        event.preventDefault();
        console.log('allowDrop event:', event.target); // Debug para ver el elemento
    }

    // Función para manejar el evento drop cuando se suelta en un div invisible
    function handleDrop(event, newDay) {
        event.preventDefault();
        console.log('handleDrop event:', event, 'newDay:', newDay); // Debug

        if (!dragging) return; // Verificamos si estamos arrastrando

        // Generar la fecha completa con año y mes
        const year = {{ \Carbon\Carbon::createFromFormat('Y-m', $date)->year }};
        const month = {{ \Carbon\Carbon::createFromFormat('Y-m', $date)->month }};
        const fechaCompleta = `${year}-${String(month).padStart(2, '0')}-${String(newDay).padStart(2, '0')}`;

        // Debug: Verificar la fecha antes de enviar
        console.log("Nueva fecha: ", fechaCompleta, "Reserva ID: ", reservaId, "Drag Type: ", dragType);

        // Verificar si estamos adelantando o reduciendo la fecha de salida
        if (dragType === 'end') {
            if (parseInt(newDay) < parseInt(originalDay)) {
                console.log("Estamos reduciendo la fecha de salida");
            } else {
                console.log("Estamos aumentando la fecha de salida");
            }
        } else if (dragType === 'start') {
            console.log("Cambiando la fecha de entrada");
        }

        // Hacer una llamada AJAX para actualizar la fecha de la reserva
        let url = `/reservas/update/${reservaId}`;
        let data = {
            '_token': '{{ csrf_token() }}',
            'reserva_id': reservaId,
            'new_date': fechaCompleta,  // Enviar la fecha completa (año-mes-día)
            'drag_type': dragType  // 'start' para cambiar la fecha de entrada, 'end' para cambiar la fecha de salida
        };

        // Realizamos la solicitud AJAX para actualizar la fecha
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar la página para reflejar los cambios
                location.reload();
            } else {
                alert('Error al actualizar la reserva: ' + data.message);
            }
        });

        dragging = false; // Reiniciar la variable de arrastre
    }


    // Evitar el comportamiento por defecto en el dragover
    document.addEventListener('dragover', function(event) {
        event.preventDefault();
        console.log('dragover event:', event); // Debug
    });


</script>
<script>

    // Función para abrir el modal de crear reserva con los datos de apartamento y día
    function openCrearReservaModal(apartamentoId, dia) {
        const year = {{ \Carbon\Carbon::createFromFormat('Y-m', $date)->year }};
        const month = {{ \Carbon\Carbon::createFromFormat('Y-m', $date)->month }};
        
        // Generar la fecha completa
        const fechaCompleta = `${year}-${String(month).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
        
        // Setear los valores en los inputs ocultos del formulario
        document.getElementById('apartamentoId').value = apartamentoId;
        document.getElementById('fechaReserva').value = fechaCompleta;
        document.getElementById('fecha_entrada').value = fechaCompleta;

        // Abrir el modal
        $('#modalCrearReserva').modal('show');
    }
</script>

@endsection
