@extends('layouts.error')

@section('title', 'Acceso Denegado')

@section('content')
<div class="error-icon">
    <i class="fas fa-lock"></i>
</div>

<h1 class="error-title">403</h1>
<h2 class="error-subtitle">Acceso Denegado</h2>

<p class="error-description">
    No tienes permisos para acceder a esta página o recurso. Si crees que esto 
    es un error, contacta con el administrador del sistema.
</p>

<div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
    <a href="{{ url('/') }}" class="btn btn-primary btn-error">
        <i class="fas fa-home me-2"></i>
        Volver al Inicio
    </a>
    <button onclick="history.back()" class="btn btn-outline-primary btn-error">
        <i class="fas fa-arrow-left me-2"></i>
        Página Anterior
    </button>
</div>

<div class="error-code">
    Error 403 - Acceso denegado
</div>
@endsection