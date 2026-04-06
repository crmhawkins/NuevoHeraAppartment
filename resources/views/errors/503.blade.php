@extends('layouts.error')

@section('title', 'Servicio No Disponible')

@section('content')
<div class="error-icon">
    <i class="fas fa-tools"></i>
</div>

<h1 class="error-title">503</h1>
<h2 class="error-subtitle">Servicio No Disponible</h2>

<p class="error-description">
    El servicio está temporalmente no disponible debido a mantenimiento programado 
    o sobrecarga del servidor. Por favor, inténtalo de nuevo en unos minutos.
</p>

<div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
    <a href="{{ url('/') }}" class="btn btn-primary btn-error">
        <i class="fas fa-home me-2"></i>
        Volver al Inicio
    </a>
    <button onclick="location.reload()" class="btn btn-outline-primary btn-error">
        <i class="fas fa-redo me-2"></i>
        Intentar de Nuevo
    </button>
</div>

<div class="error-code">
    Error 503 - Servicio temporalmente no disponible
</div>
@endsection