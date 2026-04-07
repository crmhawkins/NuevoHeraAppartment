@extends('layouts.appAdmin')

@section('content')
<!-- Fancybox CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0.27/dist/fancybox.min.css">

<!-- Fancybox JS -->
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0.27/dist/fancybox.umd.js"></script>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-eye text-primary me-2"></i>
            Detalles de la Reserva: <span class="text-primary">{{ $reserva->codigo_reserva }}</span>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reservas.index') }}">Reservas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalles</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        @if(config('services.plataforma_reservas_url'))
        <form action="{{ route('reservas.enviar-plataforma', $reserva->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-info" onclick="return confirm('¿Enviar datos de esta reserva a la plataforma externa?')" title="URL: {{ config('services.plataforma_reservas_url') }}">
                <i class="fas fa-external-link-alt me-2"></i>
                Enviar a plataforma
            </button>
        </form>
        @else
        <button type="button" class="btn btn-secondary" disabled title="Configura PLATAFORMA_RESERVAS_URL en el .env">
            <i class="fas fa-external-link-alt me-2"></i>
            Enviar a plataforma (no configurado)
        </button>
        @endif
        <a href="{{ route('reservas.edit', $reserva->id) }}" class="btn btn-warning">
            <i class="fas fa-edit me-2"></i>
            Editar
        </a>
        <a href="{{ route('reservas.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
    </div>
</div>

<!-- Session Alerts -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('plataforma_payload'))
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-code me-2"></i>Payload enviado a la plataforma</h6>
            <small class="text-muted">Última petición</small>
        </div>
        <div class="card-body">
            <pre class="mb-0 bg-dark text-light p-3 rounded small" style="max-height: 220px; overflow: auto;"><code>{{ session('plataforma_payload') }}</code></pre>
            @if (session('plataforma_response'))
                <h6 class="mt-3 mb-2"><i class="fas fa-reply me-2"></i>Respuesta de la plataforma</h6>
                <pre class="mb-0 bg-secondary text-light p-3 rounded small" style="max-height: 180px; overflow: auto;"><code>{{ session('plataforma_response') }}</code></pre>
            @endif
        </div>
    </div>
@endif

<!-- Información Principal -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-info-circle text-primary me-2"></i>
            Información de la Reserva
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-building text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Apartamento</h6>
                        <p class="mb-0 text-muted">{{ $reserva->apartamento->titulo }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-secondary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-city text-secondary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Edificio</h6>
                        <p class="mb-0 text-muted">{{ $reserva->apartamento->edificioName->nombre }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-globe text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Origen</h6>
                        <p class="mb-0 text-muted">{{ $reserva->origen }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-euro-sign text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Precio Total</h6>
                        <p class="mb-0 fw-bold fs-5 text-success">{{ number_format($reserva->precio, 2) }} €</p>
                        @if($reserva->neto)
                            <small class="text-muted">Neto: {{ number_format($reserva->neto, 2) }} €</small>
                        @endif
                    </div>
                </div>
            </div>
            
            @if($reserva->comision || $reserva->cargo_por_pago || $reserva->iva)
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-calculator text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Desglose Económico</h6>
                        <div class="small text-muted">
                            @if($reserva->comision)
                                <div>Comisión: {{ number_format($reserva->comision, 2) }} €</div>
                            @endif
                            @if($reserva->cargo_por_pago)
                                <div>Cargo por pago: {{ number_format($reserva->cargo_por_pago, 2) }} €</div>
                            @endif
                            @if($reserva->iva)
                                <div>IVA: {{ number_format($reserva->iva, 2) }} €</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-calendar-plus text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Fecha de Entrada</h6>
                        <p class="mb-0 text-muted">{{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-danger-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-calendar-minus text-danger"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Fecha de Salida</h6>
                        <p class="mb-0 text-muted">{{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-id-card text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">DNI Entregado</h6>
                        <p class="mb-0">
                            @if($reserva->dni_entregado == 1)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fas fa-check me-1"></i>Entregado
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="fas fa-times me-1"></i>No entregado
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-paper-plane text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Enviado a Webpol</h6>
                        <p class="mb-0">
                            @if($reserva->enviado_webpol == 1)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fas fa-check me-1"></i>Enviado
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="fas fa-times me-1"></i>No enviado
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-user text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Cliente</h6>
                        <p class="mb-0 text-muted">
                            {{ $reserva->cliente->alias }}
                            <a href="{{ route('clientes.show', $reserva->cliente_id) }}" class="btn btn-outline-info btn-sm ms-2">
                                <i class="fas fa-eye"></i>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-users text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Número de Adultos</h6>
                        <p class="mb-0 text-muted">{{ $reserva->numero_personas }}</p>
                    </div>
                </div>
            </div>
            
            @if($reserva->numero_ninos > 0)
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-child text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Niños</h6>
                        <p class="mb-0 text-muted">
                            {{ $reserva->numero_ninos }} niño(s)
                            @if($reserva->edades_ninos && count($reserva->edades_ninos) > 0)
                                <br><small class="text-muted">
                                    Edades: {{ implode(', ', $reserva->edades_ninos) }} años
                                </small>
                            @endif
                            @if($reserva->notas_ninos)
                                <br><small class="text-muted">
                                    <i class="fas fa-sticky-note me-1"></i>{{ $reserva->notas_ninos }}
                                </small>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @endif
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-{{ $reserva->verificado ? 'success' : 'warning' }}-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-{{ $reserva->verificado ? 'check-circle' : 'exclamation-triangle' }} text-{{ $reserva->verificado ? 'success' : 'warning' }}"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Estado de Verificación</h6>
                        <p class="mb-0">
                            @if($reserva->verificado)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fas fa-check me-1"></i>Verificada
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Pendiente de verificación
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            @if($reserva->numero_personas_plataforma && $reserva->numero_personas_plataforma != $reserva->numero_personas)
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-users-cog text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Adultos en Plataforma</h6>
                        <p class="mb-0 text-muted">{{ $reserva->numero_personas_plataforma }}</p>
                    </div>
                </div>
            </div>
            @endif
            
            @if($reserva->fecha_limpieza)
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-broom text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Fecha de Limpieza</h6>
                        <p class="mb-0 text-muted">{{ \Carbon\Carbon::parse($reserva->fecha_limpieza)->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
            @endif
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-secondary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-link text-secondary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Enlace para DNI</h6>
                        <p class="mb-0">
                            @if(!empty($reserva->token))
                                <a href="{{ config('app.url') }}/dni-scanner/{{ $reserva->token }}" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-external-link-alt me-1"></i>Ver enlace
                                </a>
                            @else
                                <span class="text-muted small"><i class="fas fa-clock me-1"></i>Sin token generado</span>
                            @endif
                        </p>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnEnviarDni" onclick="enviarEnlaceDni({{ $reserva->id }})">
                                <i class="fas fa-paper-plane me-1"></i>Enviar enlace DNI
                            </button>
                            <span id="dniEnvioResult" class="ms-2 small"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-{{ $reserva->conversacion_plataforma ? 'success' : 'danger' }}-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-{{ $reserva->conversacion_plataforma ? 'comment-slash' : 'comment' }} text-{{ $reserva->conversacion_plataforma ? 'success' : 'danger' }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-semibold">Contestaciones por Plataforma</h6>
                        <p class="mb-0">
                            @if($reserva->conversacion_plataforma)
                                <span class="badge bg-success-subtle text-success mb-2 d-inline-block">
                                    <i class="fas fa-check me-1"></i>Desactivadas
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger mb-2 d-inline-block">
                                    <i class="fas fa-times me-1"></i>Activas
                                </span>
                            @endif
                            <br>
                            <button 
                                id="toggle-conversacion-plataforma" 
                                class="btn btn-{{ $reserva->conversacion_plataforma ? 'success' : 'danger' }} btn-sm mt-1" 
                                data-reserva-id="{{ $reserva->id }}"
                                data-estado-actual="{{ $reserva->conversacion_plataforma ? '1' : '0' }}">
                                <i class="fas fa-{{ $reserva->conversacion_plataforma ? 'toggle-on' : 'toggle-off' }} me-1"></i>
                                {{ $reserva->conversacion_plataforma ? 'Activar Contestaciones' : 'Desactivar Contestaciones' }}
                            </button>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-file-invoice text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Facturación</h6>
                        <p class="mb-0">
                            @if (isset($factura))
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fas fa-check me-1"></i>Facturada: {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}
                                </span>
                            @else
                                <button id="facturar" class="btn btn-info btn-sm" data-reserva-id="{{ $reserva->id }}">
                                    <i class="fas fa-file-invoice me-1"></i>Facturar
                                </button>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        @if(count($huespedes) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <h6 class="fw-semibold mb-3">
                    <i class="fas fa-users text-primary me-2"></i>
                    Huéspedes
                </h6>
                <div class="row g-2">
                    @foreach ($huespedes as $index => $huesped)
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-2 bg-light rounded-3">
                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-user text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold">Huésped {{ $index + 1 }}</h6>
                            </div>
                            <a href="{{ route('huespedes.show', $huesped->id) }}" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Mensajes Enviados -->
@if(count($mensajes) > 0)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-comments text-primary me-2"></i>
            Mensajes Enviados
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="border-0">
                            <i class="fas fa-calendar text-primary me-1"></i>
                            Fecha de Envío
                        </th>
                        <th scope="col" class="border-0">
                            <i class="fas fa-tag text-primary me-1"></i>
                            Categoría del Mensaje
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mensajes as $mensaje)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                    <i class="fas fa-calendar text-info"></i>
                                </div>
                                <span class="fw-semibold">{{ \Carbon\Carbon::parse($mensaje->fecha_envio)->format('d/m/Y H:i') }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                    <i class="fas fa-tag text-success"></i>
                                </div>
                                <span class="fw-semibold">{{ $mensaje->categoria->nombre }}</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Documentos de Identidad -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-id-card text-primary me-2"></i>
            Documentos de Identidad
        </h5>
    </div>
    <div class="card-body">
        @if (count($photos) > 1)
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-center">
                        <h6 class="fw-semibold mb-3">
                            <i class="fas fa-id-card text-info me-2"></i>
                            DNI - Frente
                        </h6>
                        <a href="{{ asset($photos[0]->url) }}" data-fancybox="gallery" data-caption="DNI Frente">
                            <img src="{{ asset($photos[0]->url) }}" 
                                 alt="DNI Frente" 
                                 class="img-fluid rounded shadow-sm"
                                 style="object-fit: cover; object-position: center; max-height: 300px; width: 100%; cursor: pointer;">
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-center">
                        <h6 class="fw-semibold mb-3">
                            <i class="fas fa-id-card text-info me-2"></i>
                            DNI - Reverso
                        </h6>
                        <a href="{{ asset($photos[1]->url) }}" data-fancybox="gallery" data-caption="DNI Reverso">
                            <img src="{{ asset($photos[1]->url) }}" 
                                 alt="DNI Reverso" 
                                 class="img-fluid rounded shadow-sm"
                                 style="object-fit: cover; object-position: center; max-height: 300px; width: 100%; cursor: pointer;">
                        </a>
                    </div>
                </div>
            </div>
        @elseif (count($photos) == 1)
            <div class="text-center">
                <h6 class="fw-semibold mb-3">
                    <i class="fas fa-passport text-warning me-2"></i>
                    Pasaporte
                </h6>
                <a href="{{ asset($photos[0]->url) }}" data-fancybox="gallery" data-caption="Pasaporte">
                    <img src="{{ asset($photos[0]->url) }}" 
                         alt="Pasaporte" 
                         class="img-fluid rounded shadow-sm"
                         style="object-fit: cover; object-position: center; max-height: 300px; width: 100%; cursor: pointer;">
                </a>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-image fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay documentos subidos</h5>
                <p class="text-muted">No se han subido fotos de DNI o pasaporte para esta reserva.</p>
            </div>
        @endif
    </div>
</div>

<!-- Sección MIR - Servicio de Hospedajes -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-shield-alt text-primary me-2"></i>
            Comunicación MIR - Servicio de Hospedajes
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-{{ $reserva->mir_enviado ? 'success' : 'danger' }}-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-{{ $reserva->mir_enviado ? 'check' : 'times' }} text-{{ $reserva->mir_enviado ? 'success' : 'danger' }}"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Estado de Envío</h6>
                        <p class="mb-0">
                            @if($reserva->mir_enviado)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fas fa-check me-1"></i>Enviado
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="fas fa-times me-1"></i>No enviado
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            @if($reserva->mir_enviado)
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-info-circle text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Estado MIR</h6>
                        <p class="mb-0">
                            <span class="badge bg-info-subtle text-info">
                                {{ ucfirst($reserva->mir_estado ?? 'N/A') }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            @if($reserva->mir_codigo_referencia)
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-barcode text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Código de Referencia</h6>
                        <p class="mb-0 text-muted">{{ $reserva->mir_codigo_referencia }}</p>
                    </div>
                </div>
            </div>
            @endif
            
            @if($reserva->mir_fecha_envio)
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-secondary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-calendar text-secondary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Fecha de Envío</h6>
                        <p class="mb-0 text-muted">
                            {{ \Carbon\Carbon::parse($reserva->mir_fecha_envio)->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>
            </div>
            @endif
            @endif
        </div>
        
        @if($reserva->mir_respuesta)
        <div class="mb-4">
            <h6 class="fw-semibold mb-2">
                <i class="fas fa-file-code text-info me-2"></i>
                Respuesta Completa
            </h6>
            <div class="bg-light p-3 rounded-3">
                <pre class="mb-0 small" style="max-height: 200px; overflow-y: auto;">{{ json_encode(json_decode($reserva->mir_respuesta), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
        @endif
        
        <div class="d-flex gap-2">
            @if(!$reserva->mir_enviado)
            <form action="{{ route('reservas.enviar-mir', $reserva->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary" onclick="return confirm('¿Estás seguro de enviar esta reserva a MIR?')">
                    <i class="fas fa-paper-plane me-2"></i>
                    Enviar a MIR
                </button>
            </form>
            @else
            <form action="{{ route('reservas.enviar-mir', $reserva->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning" onclick="return confirm('Esta reserva ya fue enviada. ¿Deseas reenviarla?')">
                    <i class="fas fa-redo me-2"></i>
                    Reenviar a MIR
                </button>
            </form>
            @endif
            
            <a href="{{ route('configuracion.mir.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-cog me-2"></i>
                Configurar MIR
            </a>
        </div>
        
        <div class="alert alert-info mt-3 mb-0">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Nota:</strong> El envío a MIR es obligatorio según el Real Decreto 933/2021 para cumplir con la normativa de turismo de Andalucía.
        </div>
    </div>
</div>

<!-- Servicios Extras Comprados -->
@if($reserva->serviciosExtras && $reserva->serviciosExtras->where('estado', 'pagado')->count() > 0)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-gift text-primary me-2"></i>
            Servicios Extras Comprados
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Servicio</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Fecha de Pago</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reserva->serviciosExtras->where('estado', 'pagado') as $reservaServicio)
                        <tr>
                            <td>
                                <strong>{{ $reservaServicio->servicio->nombre }}</strong>
                            </td>
                            <td>
                                <small class="text-muted">{{ $reservaServicio->servicio->descripcion }}</small>
                            </td>
                            <td>
                                <strong class="text-success">{{ number_format($reservaServicio->precio, 2, ',', '.') }} €</strong>
                            </td>
                            <td>
                                <span class="badge bg-success">Pagado</span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $reservaServicio->fecha_pago ? $reservaServicio->fecha_pago->format('d/m/Y H:i') : '-' }}
                                </small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end"><strong>Total Extras:</strong></td>
                        <td colspan="3">
                            <strong class="text-success fs-5">
                                {{ number_format($reserva->serviciosExtras->where('estado', 'pagado')->sum('precio'), 2, ',', '.') }} €
                            </strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
    $(document).ready(function() {
        $('#facturar').on('click', function() {
            let reservaId = $(this).data('reserva-id'); // Obtener el ID de la reserva

            Swal.fire({
                title: '¿Facturar Reserva?',
                text: '¿Estás seguro de que deseas generar la factura para esta reserva?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0dcaf0',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-file-invoice me-2"></i>Sí, Facturar',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
                customClass: {
                    confirmButton: 'btn btn-info',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Generando la factura, por favor espere.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Enviar la solicitud POST usando Fetch
                    fetch(`{{ route('admin.facturas.facturar') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' // Incluye el token CSRF
                        },
                        body: JSON.stringify({ reserva_id: reservaId }) // Enviar el ID de la reserva
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Factura Generada!',
                                text: 'La factura se ha generado correctamente.',
                                icon: 'success',
                                confirmButtonText: '<i class="fas fa-check me-2"></i>Continuar',
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                location.reload(); // Recargar la página para actualizar el estado
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Error al generar la factura.',
                                icon: 'error',
                                confirmButtonText: '<i class="fas fa-times me-2"></i>Entendido',
                                customClass: {
                                    confirmButton: 'btn btn-danger'
                                },
                                buttonsStyling: false
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Hubo un error al procesar la solicitud.',
                            icon: 'error',
                            confirmButtonText: '<i class="fas fa-times me-2"></i>Entendido',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        });
                    });
                }
            });
        });

        // Manejar el toggle de conversacion_plataforma
        $('#toggle-conversacion-plataforma').on('click', function() {
            let reservaId = $(this).data('reserva-id');
            let estadoActual = $(this).data('estado-actual') === '1';
            // Si estadoActual es true, significa que las contestaciones están desactivadas
            // Al hacer toggle, las activaremos (nuevoEstado = false)
            // Si estadoActual es false, significa que las contestaciones están activas
            // Al hacer toggle, las desactivaremos (nuevoEstado = true)
            let nuevoEstado = !estadoActual;
            // nuevoEstado = true significa desactivar contestaciones
            // nuevoEstado = false significa activar contestaciones
            let accionTexto = nuevoEstado ? 'desactivar' : 'activar';

            Swal.fire({
                title: `¿${accionTexto.charAt(0).toUpperCase() + accionTexto.slice(1)} Contestaciones?`,
                text: `¿Estás seguro de que deseas ${accionTexto} las contestaciones automáticas por plataforma para esta reserva?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: nuevoEstado ? '#198754' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `<i class="fas fa-${nuevoEstado ? 'toggle-on' : 'toggle-off'} me-2"></i>Sí, ${accionTexto.charAt(0).toUpperCase() + accionTexto.slice(1)}`,
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
                customClass: {
                    confirmButton: `btn btn-${nuevoEstado ? 'success' : 'danger'}`,
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Actualizando el estado, por favor espere.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Enviar la solicitud POST usando Fetch
                    fetch(`{{ route('reservas.toggle-conversacion-plataforma', $reserva->id) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Estado Actualizado!',
                                text: data.message || 'El estado se ha actualizado correctamente.',
                                icon: 'success',
                                confirmButtonText: '<i class="fas fa-check me-2"></i>Continuar',
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                location.reload(); // Recargar la página para actualizar el estado
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Error al actualizar el estado.',
                                icon: 'error',
                                confirmButtonText: '<i class="fas fa-times me-2"></i>Entendido',
                                customClass: {
                                    confirmButton: 'btn btn-danger'
                                },
                                buttonsStyling: false
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Hubo un error al procesar la solicitud.',
                            icon: 'error',
                            confirmButtonText: '<i class="fas fa-times me-2"></i>Entendido',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        });
                    });
                }
            });
        });
    });

    function enviarEnlaceDni(reservaId) {
        var btn = document.getElementById('btnEnviarDni');
        var result = document.getElementById('dniEnvioResult');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enviando...';
        result.innerHTML = '';

        fetch('/enviar-dni/' + reservaId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Enviar enlace DNI';
            if (data.success) {
                result.innerHTML = '<span class="text-success"><i class="fas fa-check me-1"></i>' + data.message + '</span>';
            } else {
                result.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>' + data.message + '</span>';
            }
        })
        .catch(function(error) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Enviar enlace DNI';
            result.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>Error de conexión</span>';
        });
    }
</script>

@endsection
