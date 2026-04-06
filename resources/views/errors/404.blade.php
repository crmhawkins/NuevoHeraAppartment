@extends('layouts.error')

@section('title', 'Página No Encontrada')

@section('content')
<div class="error-icon">
    <i class="fas fa-search"></i>
</div>

<h1 class="error-title">404</h1>
<h2 class="error-subtitle">Página No Encontrada</h2>

<p class="error-description">
    Lo sentimos, la página que estás buscando no existe o ha sido movida. 
    Verifica la URL o utiliza el botón de abajo para volver al inicio.
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
    Error 404 - Página no encontrada
</div>
@endsection