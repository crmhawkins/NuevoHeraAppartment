@extends('layouts.appAdmin')

@section('title', 'Gestión de Petición de Vacaciones')

@section('content')
<div class="container-fluid">
    <!-- Header de la Página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-calendar-check me-2 text-primary"></i>
                Gestión de Petición de Vacaciones
            </h1>
            <p class="text-muted mb-0">Revisa y gestiona la solicitud de vacaciones</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('holiday.admin.petitions') }}">Gestión de Vacaciones</a></li>
                <li class="breadcrumb-item active" aria-current="page">Petición</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('holiday.admin.acceptHolidays', $holidayPetition->id) }}" method="POST">
        @csrf
        <div class="row">
            <!-- Información de la Petición -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            Detalles de la Petición
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($holidayPetition)
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                        <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-calendar-alt text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">Período de Vacaciones</h6>
                                            <p class="mb-0 text-muted">
                                                {{ Carbon\Carbon::parse($holidayPetition->from)->format('d/m/Y') }} - 
                                                {{ Carbon\Carbon::parse($holidayPetition->to)->format('d/m/Y') }}
                                            </p>
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
                                            <p class="mb-0 text-muted">
                                                {{ Carbon\Carbon::parse($holidayPetition->created_at)->format('d/m/Y H:i:s') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                        <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-clock text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">Duración</h6>
                                            <p class="mb-0 text-muted">
                                                @if($holidayPetition->half_day)
                                                    <strong>Medio día</strong>
                                                @elseif($holidayPetition->total_days == 1)
                                                    <strong>1 día</strong>
                                                @else
                                                    <strong>{{ $holidayPetition->total_days }} días</strong>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos ocultos -->
                            <input type="hidden" name="id" value="{{ $holidayPetition->id }}" />
                            <input type="hidden" name="admin_user_id" value="{{ $holidayPetition->admin_user_id }}" />
                            <input type="hidden" name="holidays_status_id" value="{{ $holidayPetition->holidays_status_id }}" />
                            <input type="hidden" name="from" value="{{ $holidayPetition->from }}" />
                            <input type="hidden" name="to" value="{{ $holidayPetition->to }}" />
                            <input type="hidden" name="total_days" value="{{ $holidayPetition->total_days }}" />
                            <input type="hidden" name="half_day" value="{{ $holidayPetition->half_day }}" />
                        @endif
                    </div>
                </div>
            </div>

            <!-- Panel de Acciones -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-cogs me-2 text-primary"></i>
                            Acciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            @if($holidayPetition->holidays_status_id != 1)
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-check me-2"></i>
                                    Aceptar Petición
                                </button>
                            @endif
                            @if($holidayPetition->holidays_status_id != 2)
                                <button type="button" id="denyHolidays" data-id="{{ $holidayPetition->id }}" class="btn btn-outline-danger btn-lg">
                                    <i class="fas fa-times me-2"></i>
                                    Rechazar Petición
                                </button>
                            @endif
                            <a href="{{ route('holiday.admin.petitions') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>
                                Volver al Listado
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
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

