@extends('layouts.error')

@section('title', 'Error')

@section('content')
<div class="error-icon">
    <i class="fas fa-exclamation-triangle"></i>
</div>

<h1 class="error-title">{{ $exception->getStatusCode() ?? 'Error' }}</h1>
<h2 class="error-subtitle">Ha Ocurrido un Error</h2>

<p class="error-description">
    @if($exception->getStatusCode() == 500)
        Ha ocurrido un error inesperado en el servidor. Nuestro equipo técnico ha sido 
        notificado y está trabajando para solucionarlo.
    @elseif($exception->getStatusCode() == 404)
        La página que estás buscando no existe o ha sido movida.
    @elseif($exception->getStatusCode() == 403)
        No tienes permisos para acceder a esta página o recurso.
    @elseif($exception->getStatusCode() == 419)
        Tu sesión ha expirado por seguridad. Por favor, vuelve a iniciar sesión.
    @elseif($exception->getStatusCode() == 503)
        El servicio está temporalmente no disponible debido a mantenimiento.
    @else
        Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo más tarde.
    @endif
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
    Error {{ $exception->getStatusCode() ?? 'Desconocido' }} - {{ $exception->getMessage() ?? 'Error inesperado' }}
</div>
@endsection
