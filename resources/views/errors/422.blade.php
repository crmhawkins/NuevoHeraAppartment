@extends('layouts.error')

@section('title', 'Error de Validación')

@section('content')
<div class="error-icon">
    <i class="fas fa-exclamation-circle"></i>
</div>

<h1 class="error-title">422</h1>
<h2 class="error-subtitle">Error de Validación</h2>

<p class="error-description">
    Los datos enviados no son válidos o están incompletos. Por favor, 
    revisa la información e inténtalo de nuevo.
</p>

<div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
    <a href="{{ url('/') }}" class="btn btn-primary btn-error">
        <i class="fas fa-home me-2"></i>
        Volver al Inicio
    </a>
    <button onclick="history.back()" class="btn btn-outline-primary btn-error">
        <i class="fas fa-arrow-left me-2"></i>
        Volver Atrás
    </button>
</div>

<div class="error-code">
    Error 422 - Datos de entrada no válidos
</div>
@endsection
