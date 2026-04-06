@extends('layouts.appAdmin')

@section('title', 'Gestión de Peticiones de Vacaciones')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-calendar-check me-2 text-primary"></i>
                Gestión de Peticiones de Vacaciones
            </h1>
            <p class="text-muted mb-0">Administra las solicitudes de vacaciones de los empleados</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gestión de Vacaciones</li>
            </ol>
        </nav>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="row mb-4">
        <div class="col-lg-6 col-md-12">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-clock fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $numberOfholidaysPetitions ?? 0 }}</h3>
                            <small class="opacity-75">
                                @if(($numberOfholidaysPetitions ?? 0) == 1)
                                    Petición Pendiente
                                @else
                                    Peticiones Pendientes
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-12">
            <div class="card shadow-sm border-0 bg-gradient-info text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">Estados de Peticiones</h6>
                            <small class="opacity-75">Leyenda de colores</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-warning-subtle text-warning me-2">
                            <i class="fas fa-square me-1"></i>Pendiente
                        </span>
                        <span class="badge bg-success-subtle text-success me-2">
                            <i class="fas fa-square me-1"></i>Aceptada
                        </span>
                        <span class="badge bg-danger-subtle text-danger">
                            <i class="fas fa-square me-1"></i>Denegada
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendario de Vacaciones -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                Calendario de Vacaciones
            </h5>
        </div>
        <div class="card-body">
            <div id="calendar" class="p-4 border rounded-3" style="min-height: 600px;">
                <!-- Aquí se renderizará el calendario -->
            </div>
        </div>
    </div>
    <!-- Modal de Gestión de Vacaciones -->
    <div class="modal fade" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-semibold" id="holidayModalLabel">
                        <i class="fas fa-calendar-check me-2"></i>
                        Gestión de Vacaciones
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Empleado</h6>
                                    <p class="mb-0 text-muted" id="holidayUser">-</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-calendar-plus text-info"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Fecha de Solicitud</h6>
                                    <p class="mb-0 text-muted" id="holidayCreate">-</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-calendar-day text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Fecha de Inicio</h6>
                                    <p class="mb-0 text-muted" id="holidayStart">-</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-calendar-times text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Fecha de Fin</h6>
                                    <p class="mb-0 text-muted" id="holidayEnd">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="holidayId">
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="acceptHoliday()">
                        <i class="fas fa-check me-2"></i>Aceptar
                    </button>
                    <button type="button" class="btn btn-danger" onclick="denyHoliday()">
                        <i class="fas fa-times me-2"></i>Rechazar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
@endsection

@section('scripts')
    @include('partials.toast')
    <script src="{{asset('assets/vendors/choices.js/choices.min.js')}}"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales-all.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
                initializeCalendar();
        });

        function getParameterByName(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }
        const eventDate = getParameterByName('fecha');

        function initializeCalendar() {
            var calendarEl = document.getElementById('calendar');
            if (!calendarEl) return; // Asegúrate de que el elemento existe

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialDate: eventDate || new Date(), // Usa la fecha del evento o la fecha actual
                initialView: 'dayGridMonth',
                locale: 'es',
                navLinks: true,
                contentHeight: 600,
                nowIndicator: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                events: @json($holydayEvents),
                eventClick: function(info) {
                    $('#holidayStart').text(info.event.start.toLocaleDateString());
                    $('#holidayEnd').text(info.event.extendedProps.endTrue ? new Date(info.event.extendedProps.endTrue).toLocaleDateString() : 'N/A');
                    $('#holidayUser').text(info.event.title ?? 'Usuario sin nombre');
                    $('#holidayCreate').text(info.event.extendedProps.created_at || 'N/A');
                    $('#holidayId').val(info.event.id);
                    $('#holidayModal').modal('show');
                }

            });
            calendar.render();
        }

        function acceptHoliday() {
            var holidayId = $('#holidayId').val();

            $.ajax({
                url: '/holidays/acceptHolidays',
                type: 'POST',
                data: {
                    id: holidayId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#holidayModal').modal('hide');
                    showToast(response.status === 'success' ? 'success' : 'error', response.mensaje || 'Error al aceptar la petición');

                },
                error: function(xhr) {
                    showToast('error', 'Ocurrió un error al aceptar la petición. Por favor, inténtalo de nuevo.');
                    console.error(xhr.responseText);
                }
            });
        }

        function denyHoliday() {
            var holidayId = $('#holidayId').val();

            $.ajax({
                url: '/holidays/denyHolidays',
                type: 'POST',
                data: {
                    id: holidayId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#holidayModal').modal('hide');
                    showToast(response.status === 'success' ? 'success' : 'error', response.mensaje || 'Error al denegar la petición');
                },
                error: function(xhr) {
                    showToast('error', 'Ocurrió un error al denegar la petición. Por favor, inténtalo de nuevo.');
                    console.error(xhr.responseText);
                }
            });
        }

        function showToast(icon, title) {
            Swal.fire({
                icon: icon,
                title: title,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                },
                didClose: () => {
                    if (icon === 'success') {
                        location.reload(); // Recarga solo si es éxito
                    }
                }
            });
        }
    </script>
@endsection
