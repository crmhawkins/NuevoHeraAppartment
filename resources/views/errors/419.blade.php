@extends('layouts.error')

@section('title', 'Token Expirado')

@section('content')
<div class="error-icon">
    <i class="fas fa-clock"></i>
</div>

<h1 class="error-title">419</h1>
<h2 class="error-subtitle">Token de Seguridad Expirado</h2>

<p class="error-description">
    Tu sesión ha expirado por seguridad. Esto suele ocurrir cuando has estado 
    inactivo durante mucho tiempo. Por favor, vuelve a iniciar sesión.
</p>

<div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
    <a href="{{ url('/') }}" class="btn btn-primary btn-error">
        <i class="fas fa-home me-2"></i>
        Volver al Inicio
    </a>
    <button onclick="location.reload()" class="btn btn-outline-primary btn-error">
        <i class="fas fa-redo me-2"></i>
        Recargar Página
    </button>
</div>

<div class="error-code">
    Error 419 - Token de autenticación expirado
</div>
@endsection