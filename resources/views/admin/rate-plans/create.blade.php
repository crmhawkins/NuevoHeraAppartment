@extends('layouts.appAdmin')

@section('content')
<div class="container">
    <h1>Crear Rate Plan</h1>

    <form action="{{ route('channex.ratePlans.store') }}" method="POST">
        @csrf

        <!-- Título del Rate Plan -->
        <div class="mb-3">
            <label for="title" class="form-label">Título</label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Selección de Propiedad -->
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

        <!-- Selección de Tipo de Habitación -->
        <div class="mb-3">
            <label for="room_type_id" class="form-label">Tipo de Habitación</label>
            <select name="room_type_id" id="room_type_id" class="form-select @error('room_type_id') is-invalid @enderror">
                <option value="" disabled selected>Seleccione una propiedad primero</option>
            </select>
            @error('room_type_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Selección de Set de Impuestos -->
        <div class="mb-3">
            <label for="tax_set_id" class="form-label">Set de Impuestos (Opcional)</label>
            <input type="text" class="form-control @error('tax_set_id') is-invalid @enderror" id="tax_set_id" name="tax_set_id" value="{{ old('tax_set_id') }}">
            @error('tax_set_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Moneda -->
        <div class="mb-3">
            <label for="currency" class="form-label">Moneda</label>
            <select name="currency" id="currency" class="form-select @error('currency') is-invalid @enderror" required>
                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD - Dólar</option>
                <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP - Libra</option>
            </select>
            @error('currency')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Modo de Venta -->
        <div class="mb-3">
            <label for="sell_mode" class="form-label">Modo de Venta</label>
            <select name="sell_mode" id="sell_mode" class="form-select @error('sell_mode') is-invalid @enderror" required>
                <option value="per_room" {{ old('sell_mode') == 'per_room' ? 'selected' : '' }}>Por Habitación</option>
                <option value="per_person" {{ old('sell_mode') == 'per_person' ? 'selected' : '' }}>Por Persona</option>
            </select>
            @error('sell_mode')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Modo de Tarifa -->
        <div class="mb-3">
            <label for="rate_mode" class="form-label">Modo de Tarifa</label>
            <select name="rate_mode" id="rate_mode" class="form-select @error('rate_mode') is-invalid @enderror" required>
                <option value="manual" {{ old('rate_mode') == 'manual' ? 'selected' : '' }}>Manual</option>
                <option value="auto" {{ old('rate_mode') == 'auto' ? 'selected' : '' }}>Automático</option>
            </select>
            @error('rate_mode')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Opciones de Ocupación -->
        <div class="mb-3">
            <label for="options" class="form-label">Opciones de Ocupación</label>
            <textarea class="form-control @error('options') is-invalid @enderror" id="options" name="options" rows="3">{{ old('options') }}</textarea>
            <small class="form-text text-muted">Especifique las ocupaciones en formato JSON (Ejemplo: [{"occupancy": 2, "is_primary": true, "rate": 100}]).</small>
            @error('options')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
<!-- Max Stay -->
<div class="mb-3">
    <label for="max_stay" class="form-label">Max Stay</label>
    <textarea class="form-control @error('max_stay') is-invalid @enderror" id="max_stay" name="max_stay">{{ old('max_stay', '[0, 0, 0, 0, 0, 0, 0]') }}</textarea>
    <small class="form-text text-muted">Formato: [0, 0, 0, 0, 0, 0, 0]</small>
    @error('max_stay')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<!-- Min Stay Arrival -->
<div class="mb-3">
    <label for="min_stay_arrival" class="form-label">Min Stay Arrival</label>
    <textarea class="form-control @error('min_stay_arrival') is-invalid @enderror" id="min_stay_arrival" name="min_stay_arrival">{{ old('min_stay_arrival', '[1, 1, 1, 1, 1, 1, 1]') }}</textarea>
    <small class="form-text text-muted">Formato: [1, 1, 1, 1, 1, 1, 1]</small>
    @error('min_stay_arrival')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<!-- Min Stay Through -->
<div class="mb-3">
    <label for="min_stay_through" class="form-label">Min Stay Through</label>
    <textarea class="form-control @error('min_stay_through') is-invalid @enderror" id="min_stay_through" name="min_stay_through">{{ old('min_stay_through', '[1, 1, 1, 1, 1, 1, 1]') }}</textarea>
    <small class="form-text text-muted">Formato: [1, 1, 1, 1, 1, 1, 1]</small>
    @error('min_stay_through')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>


        <!-- Botones de Restricciones -->
        @php
            $booleanFields = ['closed_to_arrival', 'closed_to_departure', 'stop_sell'];
        @endphp
        @foreach ($booleanFields as $field)
            <div class="mb-3">
                <label for="{{ $field }}" class="form-label">{{ ucfirst(str_replace('_', ' ', $field)) }}</label>
                <textarea class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" rows="1">{{ old($field) }}</textarea>
                <small class="form-text text-muted">Formato: [false, false, false, false, false, false, false] para cada día de la semana.</small>
                @error($field)
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @endforeach

        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>

<!-- JavaScript para cargar dinámicamente los tipos de habitación -->
<script>
    document.getElementById('property_id').addEventListener('change', function () {
        const propertyId = this.value;
        const roomTypeSelect = document.getElementById('room_type_id');

        roomTypeSelect.innerHTML = '<option value="" disabled selected>Cargando tipos de habitación...</option>';

        fetch(`/api/room-types/${propertyId}`)
            .then(response => response.json())
            .then(data => {
                roomTypeSelect.innerHTML = '<option value="" disabled selected>Seleccione un tipo de habitación</option>';
                data.forEach(roomType => {
                    roomTypeSelect.innerHTML += `<option value="${roomType.id}">${roomType.title}</option>`;
                });
            })
            .catch(error => {
                console.error('Error al cargar los tipos de habitación:', error);
                roomTypeSelect.innerHTML = '<option value="" disabled selected>Error al cargar</option>';
            });
    });
</script>
@endsection
