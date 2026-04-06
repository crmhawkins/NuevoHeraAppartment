@extends('layouts.error')

@section('title', 'Demasiadas Solicitudes')

@section('content')
<div class="error-icon">
    <i class="fas fa-hourglass-half"></i>
</div>

<h1 class="error-title">429</h1>
<h2 class="error-subtitle">Demasiadas Solicitudes</h2>

<p class="error-description">
    Has realizado demasiadas solicitudes en un corto período de tiempo. 
    Por favor, espera unos minutos antes de intentar de nuevo.
</p>

<div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
    <a href="{{ url('/') }}" class="btn btn-primary btn-error">
        <i class="fas fa-home me-2"></i>
        Volver al Inicio
    </a>
    <button onclick="setTimeout(() => location.reload(), 30000)" class="btn btn-outline-primary btn-error">
        <i class="fas fa-clock me-2"></i>
        Esperar y Recargar
    </button>
</div>

<div class="error-code">
    Error 429 - Límite de solicitudes excedido
</div>
@endsection