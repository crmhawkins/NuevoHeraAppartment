@extends('layouts.appAdmin')

@section('title', 'Detalle de Contacto')

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-envelope me-2"></i>Detalle de Contacto
        </h1>
        <div>
            <a href="{{ route('admin.contactos-web.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <!-- Información del Contacto -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Información del Contacto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong><i class="fas fa-user me-1"></i> Nombre:</strong>
                        </div>
                        <div class="col-md-9">
                            {{ $contacto->nombre }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong><i class="fas fa-envelope me-1"></i> Email:</strong>
                        </div>
                        <div class="col-md-9">
                            <a href="mailto:{{ $contacto->email }}">{{ $contacto->email }}</a>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong><i class="fas fa-tag me-1"></i> Asunto:</strong>
                        </div>
                        <div class="col-md-9">
                            {{ $contacto->asunto }}
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong><i class="fas fa-comment me-1"></i> Mensaje:</strong>
                        </div>
                        <div class="col-md-9">
                            <div class="border rounded p-3 bg-light">
                                {!! nl2br(e($contacto->mensaje)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Información Adicional -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info text-primary me-2"></i>
                        Información Adicional
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong><i class="fas fa-calendar me-1"></i> Fecha de envío:</strong><br>
                        <small class="text-muted">{{ $contacto->created_at->format('d/m/Y H:i:s') }}</small>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-eye me-1"></i> Estado:</strong><br>
                        @if($contacto->leido)
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> Leído
                            </span>
                            @if($contacto->leido_at)
                                <br><small class="text-muted">el {{ $contacto->leido_at->format('d/m/Y H:i') }}</small>
                            @endif
                            @if($contacto->leidoPor)
                                <br><small class="text-muted">por {{ $contacto->leidoPor->name }}</small>
                            @endif
                        @else
                            <span class="badge bg-warning">
                                <i class="fas fa-envelope"></i> Sin leer
                            </span>
                        @endif
                    </div>
                    
                    @if($contacto->ip_address)
                        <div class="mb-3">
                            <strong><i class="fas fa-network-wired me-1"></i> IP:</strong><br>
                            <small class="text-muted">{{ $contacto->ip_address }}</small>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs text-primary me-2"></i>
                        Acciones
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.contactos-web.toggle-leido', $contacto->id) }}" 
                          method="POST" 
                          class="mb-2">
                        @csrf
                        <button type="submit" 
                                class="btn btn-{{ $contacto->leido ? 'warning' : 'success' }} w-100">
                            <i class="fas fa-{{ $contacto->leido ? 'envelope' : 'check' }} me-1"></i>
                            {{ $contacto->leido ? 'Marcar como no leído' : 'Marcar como leído' }}
                        </button>
                    </form>
                    
                    <a href="mailto:{{ $contacto->email }}?subject=Re: {{ $contacto->asunto }}" 
                       class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-reply me-1"></i> Responder por Email
                    </a>
                    
                    <form action="{{ route('admin.contactos-web.destroy', $contacto->id) }}" 
                          method="POST" 
                          onsubmit="return confirm('¿Estás seguro de eliminar este contacto?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash me-1"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

