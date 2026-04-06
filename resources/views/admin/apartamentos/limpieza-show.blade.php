@extends('layouts.appAdmin')

@section('title')
    {{ __('Limpieza - ') . $apartamentoLimpieza->apartamento->nombre }}
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header con botones de acción -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="mb-1">{{ __('Resumen de Limpieza del Apartamento') }}</h2>
                    <p class="text-muted mb-0">
                        Apartamento: <strong>{{ $apartamentoLimpieza->apartamento->nombre }}</strong> | 
                        @if($apartamentoLimpieza->reserva)
                            Reserva: <strong>{{ $apartamentoLimpieza->reserva->codigo_reserva ?? 'N/A' }}</strong>
                        @else
                            Sin reserva activa
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('amenity.limpieza.show', $apartamentoLimpieza->id) }}" class="btn btn-primary">
                        <i class="fas fa-pump-soap me-2"></i>Gestionar Amenities
                    </a>
                    <a href="{{ route('apartamentos.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
    <hr class="mb-4">

    @foreach ($checklists as $checklist)
        @php
            $nombreHabitacion = strtolower(str_replace(' ', '_', $checklist->nombre));
            $nombreHabitacion = strtr($nombreHabitacion, [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
                'ñ' => 'n', 'Ñ' => 'n'
            ]);
            $excluirCamara = in_array($nombreHabitacion, ['canape', 'armario', 'perchero', 'amenities', 'ascensor', 'escalera']);
        @endphp

        <div class="fila">
            <div class="header_sub mb-3">
                <div class="row bg-color-quinto m-1 text-white align-items-center">
                    <div class="col-8">
                        <h3 class="titulo mb-0">{{ strtoupper($checklist->nombre) }}</h3>
                    </div>
                    @if (!$excluirCamara)
                        <div class="col-4 text-end">
                            <a href="{{ route('fotos.' . $nombreHabitacion, [
                                'id' => $apartamentoLimpieza->id,
                                'cat' => $checklist->id,
                            ]) }}" class="btn btn-foto">
                                <i class="fa-solid fa-camera"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="content-check mx-2">
                @foreach ($checklist->items as $item)
                    @php
                        $estado = $itemsExistentes[$item->id] ?? 0;
                    @endphp
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox"
                            id="item_{{ $item->id }}"
                            disabled
                            {{ $estado ? 'checked' : '' }}>
                        <label class="form-check-label" for="item_{{ $item->id }}">{{ $item->nombre }}</label>
                    </div>
                @endforeach
            </div>
            <hr>
        </div>
    @endforeach

    @if($apartamentoLimpieza->observacion)
        <div class="row mt-4">
            <div class="col-md-12">
                <h4 class="titulo mb-0">OBSERVACIONES</h4>
                <div class="form-check ps-0 mt-2">
                    <textarea class="form-control" cols="30" rows="5" readonly>{{ $apartamentoLimpieza->observacion }}</textarea>
                </div>
            </div>
        </div>
        <hr>
    @endif

    @if($fotos && $fotos->count())
        <div class="row mt-4">
            <div class="col-md-12">
                <h4 class="titulo mb-0">IMÁGENES</h4>
            </div>

            @foreach ($fotos as $foto)
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header text-center fw-bold">
                            {{ $foto->categoria->nombre ?? 'Sin categoría' }}
                        </div>
                        <img src="{{ asset($foto->url) }}" class="card-img-top" alt="foto">
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection

@include('sweetalert::alert')
