@extends('layouts.appAdmin')

@section('scriptHead')
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.9/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.9/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/list@6.1.9/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.9/locales/es.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {

            var calendarEl = document.getElementById('calendar');
            // Mapeo de apartamento_id a colores
            var apartmentColors = {
                1: ['#769ECB', 'white'], // Color para apartamento_id 1
                2: ['#9DBAD5', 'white'], // Color para apartamento_id 2
                3: ['#FAF3DD', 'black'], // Color para apartamento_id 3
                4: ['#C8D6B9', 'black'], // Color para apartamento_id 4
                5: ['#DFD8C0', 'black'], // Color para apartamento_id 5
                6: ['#8FC1A9', 'white'], // Color para apartamento_id 6
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                7: ['#7CAA98', 'white'], // Color para apartamento_id 7
                // ... más mapeos de colores para diferentes IDs de apartamento
            };
          var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            firstDay: 1, // Establece el lunes como el primer día de la semana
            events: function(fetchInfo, successCallback, failureCallback) {
                fetch('/get-reservas')
                    .then(response => response.json())
                    .then(data => {
                        var events = data.map(function(reserva) {
                            console.log(reserva)
                            console.log(apartmentColors[reserva.apartamento_id])
                            return {
                            title: reserva.cliente.alias + ' - ' + reserva.apartamento.titulo, // o cualquier otro campo que quieras usar como título
                            start: reserva.fecha_entrada,
                            end: reserva.fecha_salida,
                            backgroundColor: apartmentColors[reserva.apartamento_id] || '#378006', // Color por defecto si no se encuentra un mapeo
                            borderColor: apartmentColors[reserva.apartamento_id] || '#378006', // Color por defecto si no se encuentra un mapeo
                            textColor: apartmentColors[reserva.apartamento_id] || '#378006', // Color por defecto si no se encuentra un mapeo
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
@section('title', 'Calendario')



@section('content')
<style>
    .fc .fc-view-harness{
        height: 60vh !important;
    }
    .fc .fc-toolbar-title {
        font-size: 1.2em !important;
    }
    .fc-theme-standard .fc-list-day-cushion {
        background-color: #0F1739;
    }
    .fc-list-day-text{
        color: white;
        text-decoration: none;
        text-transform: uppercase;
    }
    .fc-list-day-side-text {
        color: white;
        text-decoration: none;
        text-transform: uppercase;
    }
</style>
<div class="container-fluid">
    <h4 class="mb-3">Calendario</h4>

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                {{-- <div class="card-header">{{ __('Nuestros Clientes') }}</div> --}}
                <div class="card-body" style="display: flex;flex-direction: column;">
                    {{--
                        1: '#769ECB', // Color para apartamento_id 1
                        2: '#9DBAD5', // Color para apartamento_id 2
                        3: '#FAF3DD', // Color para apartamento_id 3
                        4: '#C8D6B9', // Color para apartamento_id 3
                        5: '#DFD8C0', // Color para apartamento_id 3
                        6: '#8FC1A9', // Color para apartamento_id 3
                        7: '#7CAA98', // Color para apartamento_id 3,
                    --}}
                    {{-- <div class="apartamentos my-2" style="order: 1;">
                        <div class="d-inline px-2" style="background-color: #769ECB; color:white">
                            Atico
                        </div>
                        <div class="d-inline px-2" style="background-color: #9DBAD5; color:white">
                            Segundo A
                        </div>
                        <div class="d-inline px-2" style="background-color: #FAF3DD; color:black">
                            Segundo B
                        </div>
                        <div class="d-inline px-2" style="background-color: #C8D6B9; color:black">
                            Primero A
                        </div>
                        <div class="d-inline px-2" style="background-color: #DFD8C0; color:black">
                            Primero B
                        </div>
                        <div class="d-inline px-2" style="background-color: #8FC1A9; color:white">
                            Bajo A
                        </div>
                        <div class="d-inline px-2" style="background-color: #7CAA98; color:white">
                            Bajo B
                        </div>
                    </div> --}}
                    <div id='calendar' style="order: 0;"></div>

                </div>
                <div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title"  id="eventModalLabel">Detalles de la Reserva</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                      </div>
                    </div>
                  </div>
            </div>
        </div>
    </div>
</div>




@endsection
