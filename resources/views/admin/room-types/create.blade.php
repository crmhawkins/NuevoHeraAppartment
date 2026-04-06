@extends('layouts.appAdmin')

@section('content')
<div class="container">
    <h1>Crear Room Type</h1>

    <form action="{{ route('channex.roomTypes.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Título -->
        <div class="mb-3">
            <label for="title" class="form-label">Título</label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Propiedad -->
        <div class="mb-3">
            <label for="property_id" class="form-label">Propiedad</label>
            <select name="property_id" id="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                <option value="" disabled selected>Seleccione una propiedad</option>
                @foreach ($properties as $property)
                    <option value="{{ $property->id }}" {{ old('property_id') == $property->id ? 'selected' : '' }}>
                        {{ $property->nombre }}
                    </option>
                @endforeach
            </select>
            @error('property_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Cantidad de Habitaciones -->
        <div class="mb-3">
            <label for="count_of_rooms" class="form-label">Cantidad de Habitaciones</label>
            <input type="number" class="form-control @error('count_of_rooms') is-invalid @enderror" id="count_of_rooms" name="count_of_rooms" value="{{ old('count_of_rooms') }}" required>
            @error('count_of_rooms')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Ocupación Máxima Adultos -->
        <div class="mb-3">
            <label for="occ_adults" class="form-label">Ocupación Máxima Adultos</label>
            <input type="number" class="form-control @error('occ_adults') is-invalid @enderror" id="occ_adults" name="occ_adults" value="{{ old('occ_adults') }}" required>
            @error('occ_adults')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Ocupación Máxima Niños -->
        <div class="mb-3">
            <label for="occ_children" class="form-label">Ocupación Máxima Niños</label>
            <input type="number" class="form-control @error('occ_children') is-invalid @enderror" id="occ_children" name="occ_children" value="{{ old('occ_children') }}">
            @error('occ_children')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Ocupación Máxima Bebés -->
        <div class="mb-3">
            <label for="occ_infants" class="form-label">Ocupación Máxima Bebés</label>
            <input type="number" class="form-control @error('occ_infants') is-invalid @enderror" id="occ_infants" name="occ_infants" value="{{ old('occ_infants') }}">
            @error('occ_infants')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Ocupación Predeterminada -->
        <div class="mb-3">
            <label for="default_occupancy" class="form-label">Ocupación Predeterminada</label>
            <input type="number" class="form-control @error('default_occupancy') is-invalid @enderror" id="default_occupancy" name="default_occupancy" value="{{ old('default_occupancy') }}" required>
            @error('default_occupancy')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Instalaciones -->
        <div class="mb-3">
            <label for="facilities" class="form-label">Instalaciones</label>
            <textarea class="form-control @error('facilities') is-invalid @enderror" id="facilities" name="facilities">{{ old('facilities') }}</textarea>
            <small class="form-text text-muted">Formato: ["Instalación 1", "Instalación 2"]</small>
            @error('facilities')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Tipo de Habitación -->
        <div class="mb-3">
            <label for="room_kind" class="form-label">Tipo de Habitación</label>
            <select name="room_kind" id="room_kind" class="form-select @error('room_kind') is-invalid @enderror">
                <option value="room" {{ old('room_kind') == 'room' ? 'selected' : '' }}>Habitación</option>
                <option value="suite" {{ old('room_kind') == 'suite' ? 'selected' : '' }}>Suite</option>
            </select>
            @error('room_kind')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Capacidad -->
        <div class="mb-3">
            <label for="capacity" class="form-label">Capacidad</label>
            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity') }}">
            @error('capacity')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Descripción -->
        <div class="mb-3">
            <label for="description" class="form-label">Descripción</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description">{{ old('description') }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Fotos -->
        <div class="mb-3">
            <label for="photos" class="form-label">Fotos</label>
            <input type="file" class="form-control @error('photos.*.url') is-invalid @enderror" name="photos[][url]" multiple>
            <small class="form-text text-muted">Adjunta varias fotos.</small>
            @error('photos.*.url')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Botón de Enviar -->
        <button type="submit" class="btn btn-primary">Guardar Room Type</button>
    </form>
</div>
@endsection
