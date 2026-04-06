@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Actualizar Tarifa para una Fecha</h1>

    <form action="{{ route('rate-updates.store') }}" method="POST">
        @csrf

        <!-- Selección de Propiedad -->
        <div class="mb-3">
            <label for="property_id" class="form-label">Propiedad</label>
            <select name="property_id" id="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                <option value="" disabled selected>Seleccione una Propiedad</option>
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

        <!-- Selección de Rate Plan -->
        <div class="mb-3">
            <label for="rate_plan_id" class="form-label">Plan de Tarifas</label>
            <select name="rate_plan_id" id="rate_plan_id" class="form-select @error('rate_plan_id') is-invalid @enderror" required>
                <option value="" disabled selected>Seleccione un Plan de Tarifas</option>
                @foreach ($ratePlans as $ratePlan)
                    <option value="{{ $ratePlan->id }}" {{ old('rate_plan_id') == $ratePlan->id ? 'selected' : '' }}>
                        {{ $ratePlan->title }}
                    </option>
                @endforeach
            </select>
            @error('rate_plan_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Fecha -->
        <div class="mb-3">
            <label for="date" class="form-label">Fecha</label>
            <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date') }}" required>
            @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Tarifa -->
        <div class="mb-3">
            <label for="rate" class="form-label">Tarifa</label>
            <input type="number" step="0.01" class="form-control @error('rate') is-invalid @enderror" id="rate" name="rate" value="{{ old('rate') }}" required>
            @error('rate')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
