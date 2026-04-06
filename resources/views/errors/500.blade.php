@extends('layouts.error')

@section('title', 'Error del Servidor')

@section('content')
<div class="error-icon">
    <i class="fas fa-exclamation-triangle"></i>
</div>

<h1 class="error-title">500</h1>
<h2 class="error-subtitle">Error del Servidor</h2>

<p class="error-description">
    Ha ocurrido un error inesperado en el servidor. Nuestro equipo técnico ha sido 
    notificado y está trabajando para solucionarlo. Por favor, inténtalo de nuevo más tarde.
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
    Error 500 - Error interno del servidor
</div>
@endsection