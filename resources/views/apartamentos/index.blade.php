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
        <h4 class="mb-3">Apartamentos</h4>
        <div class=" row d-flex justify-content-between">
            @if (count($pisos) > 0)
                @foreach ($pisos as $piso)
                    <div class="col-12 mb-2">
                        <div class="card bg-color-cuarto border-0">
                            <div class="card-body">
                                <h5 class="primer-color">{{$piso->titulo}}</h5>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="">No hay apartamentos pendientes.</p>
            @endif
        </div>
    </div>
</div>
@endsection
