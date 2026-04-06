@extends('layouts.appAdmin')

@section('content')
<!-- Incluye CSS de Lightbox2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
<div class="container-fluid">
    <h2 class="mb-3">{{ __('Limpieza del Apartamento') }} {{ $apartamentoLimpieza->apartamento->nombre }}</h2>
    <hr class="mb-5">

    @foreach($itemChecklists as $seccion => $items)
        <div class="row mt-4">
            <div class="col-md-12">
                <h4 class="titulo mb-0">{{ strtoupper($seccion) }}</h4>
            </div>
            @foreach($items as $item)
                <div class="col-md-3">
                    <div class="form-check form-switch mt-2">
                        <input data-id="{{ $apartamentoLimpieza->id }}" class="form-check-input" type="checkbox" id="item_{{ $item->id }}" name="items[{{ $item->id }}]"
                        {{ $apartamentoLimpieza->controles->contains('id', $item->id) ? 'checked' : '' }}>
                        <label class="form-check-label" for="item_{{ $item->id }}">{{ $item->nombre }}</label>
                    </div>
                </div>
            @endforeach
        </div>
        <hr>
    @endforeach




    <!-- Sección Observaciones -->
    <div class="row mt-4">
        <div class="col-md-12">
            <h4 class="titulo mb-0">Observaciones</h4>
        </div>
        <div class="col-md-12">
            <textarea name="observacion" id="observacion" cols="30" rows="6" class="form-control" placeholder="Escriba alguna observación...">{{ $apartamentoLimpieza->observacion }}</textarea>
        </div>
    </div>

    <!-- Sección Fotos -->
<div class="row mt-4">
    <div class="col-md-12">
        <h4 class="titulo mb-0">Imágenes</h4>
    </div>
    @if ($apartamentoLimpieza->fotos->isNotEmpty())
        @foreach ($apartamentoLimpieza->fotos as $foto)
            <div class="col-md-3">
                <!-- Mostrar el nombre del checklist relacionado con la foto a través de 'requirement_id' -->
                <h5 class="text-muted">{{ $foto->checklistRequirement->checklist->nombre ?? 'Sin sección' }}</h5> <!-- Muestra el nombre de la sección o 'Sin sección' si no existe -->
                <a href="{{ asset('storage/' . $foto->url) }}" data-lightbox="apartment-photos" data-title="{{ $foto->checklistRequirement->checklist->nombre ?? 'Imagen de limpieza' }}">
                    <img src="{{ asset('storage/' . $foto->url) }}" alt="Imagen de limpieza" class="img-fluid" />
                </a>
            </div>
        @endforeach
    @endif
</div>



<!-- Incluye el JS de Lightbox2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

</div>
@endsection
