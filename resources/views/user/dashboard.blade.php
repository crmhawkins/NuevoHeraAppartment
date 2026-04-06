@extends('layouts.appPersonal')

@section('bienvenido')
@endsection

@section('content')
<div class="container px-4">
    <div class="d-flex justify-content-center flex-column">
        {{-- <img src="{{asset('logo_small_azul.png')}}" alt="" class="img-fluid m-auto" style="width: 60%"> --}}
        <h5 class="text-center mb-3 mt-3 fs-2"><strong>CRM</strong> de los Apartamentos <img src="{{asset('logo_small_azul.png')}}" alt="" style="max-width: 230px" class="img-fluid d-block m-auto mt-1"></h5>
    </div>
    <div class="mt-5">
        <h4 class="mb-3">Acceso directos</h4>
        <div class="d-flex justify-content-between">
            <div class="text-center">
                <a href="{{route('gestion.index')}}" class="text-decoration-none primer-color">
                    <div class="icon-container2">
                        <div class="icon2">
                          <i class="fa-solid fa-broom"> </i> <!-- Asegúrate de reemplazar con la ruta correcta de tu imagen -->
                        </div>
                    </div>
                    <div class="icon-text mt-2">Limpieza</div>
                </a>
            </div>
            <div class="text-center">
                <a href="{{route('apartamentos.index')}}" class="text-decoration-none primer-color">
                    <div class="icon-container2">
                        <div class="icon2">
                            <i class="fa-solid fa-building"></i>
                        </div>
                    </div>
                    <div class="icon-text mt-2">Pisos</div>
                </a>
            </div>
            <div class="text-center">
                <div class="icon-container2">
                  <div class="icon2">
                    <i class="fa-solid fa-book"></i>
                </div>
                </div>
                <div class="icon-text mt-2">Reservas</div>
            </div>
            <div class="text-center">
                <a href="{{route('reservas.calendar')}}" class="text-decoration-none primer-color">
                    <div class="icon-container2">
                        <div class="icon2">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                    </div>
                    <div class="icon-text mt-2">Calendario</div>
                </a>

            </div>
        </div>
    </div>
    <h4 class="mt-5">Apartamentos para Hoy</h4>

    <div class="row mb-4">
        @if (count($reservasPendientes) > 0)
            @foreach ($reservasPendientes as $reserva)
                <div class="col-12 mb-2">
                    <div class="card bg-color-cuarto border-0">
                        <div class="card-body">
                            <h5 class="primer-color">{{$reserva->apartamento->nombre}}</h5>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="">No hay apartamentos pendientes.</p>
        @endif
    </div>
    <h4 class="mt-5">Apartamentos para Mañana</h4>

    <div class="row mb-4">
        @if (count($reservasSalida) > 0)
            @foreach ($reservasSalida as $reserva_salida)
                <div class="col-12 mb-2">
                    <div class="card bg-color-cuarto border-0">
                        <div class="card-body">
                            <h5 class="primer-color">{{$reserva_salida->apartamento->nombre}}</h5>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="">No hay apartamentos pendientes.</p>
        @endif
    </div>

</div>
@endsection
