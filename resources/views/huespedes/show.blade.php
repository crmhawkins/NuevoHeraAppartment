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
            <i class="fas fa-user text-primary me-2"></i>
            Detalles del Huésped: <span class="text-primary">{{ $huesped->nombre }} {{ $huesped->primer_apellido }}</span>
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reservas.index') }}">Reservas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Huésped</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('huespedes.edit', $huesped->id) }}" class="btn btn-warning">
            <i class="fas fa-edit me-2"></i>
            Editar
        </a>
        <a href="{{ route('reservas.show', $huesped->reserva_id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver a Reserva
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

<!-- Información Personal -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-user-circle text-primary me-2"></i>
            Información Personal
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- Nombre -->
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-user text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Nombre</h6>
                        <p class="mb-0 text-muted">{{ $huesped->nombre ?? 'No especificado' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Primer Apellido -->
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-user text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Primer Apellido</h6>
                        <p class="mb-0 text-muted">{{ $huesped->primer_apellido ?? 'No especificado' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Segundo Apellido -->
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-secondary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-user text-secondary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Segundo Apellido</h6>
                        <p class="mb-0 text-muted">{{ $huesped->segundo_apellido ?? 'No especificado' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Fecha de Nacimiento -->
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-birthday-cake text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Fecha de Nacimiento</h6>
                        <p class="mb-0 text-muted">
                            {{ $huesped->fecha_nacimiento ? \Carbon\Carbon::parse($huesped->fecha_nacimiento)->format('d/m/Y') : 'No especificada' }}
                            @if($huesped->fecha_nacimiento)
                                <br><small class="text-muted">Edad: {{ \Carbon\Carbon::parse($huesped->fecha_nacimiento)->age }} años</small>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Sexo -->
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-{{ $huesped->sexo == 'M' ? 'mars' : ($huesped->sexo == 'F' ? 'venus' : 'genderless') }} text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Sexo</h6>
                        <p class="mb-0 text-muted">
                            @if($huesped->sexo == 'M')
                                <span class="badge bg-primary-subtle text-primary">Masculino</span>
                            @elseif($huesped->sexo == 'F')
                                <span class="badge bg-pink-subtle text-pink">Femenino</span>
                            @else
                                No especificado
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Nacionalidad -->
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-flag text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Nacionalidad</h6>
                        <p class="mb-0 text-muted">{{ $huesped->nacionalidadStr ?? $huesped->nacionalidad ?? 'No especificada' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Información de Documento -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-id-card text-primary me-2"></i>
            Documento de Identidad
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- Tipo de Documento -->
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-file-alt text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Tipo de Documento</h6>
                        <p class="mb-0">
                            @if ($huesped->tipo_documento == 1)
                                <span class="badge bg-info-subtle text-info">
                                    <i class="fas fa-id-card me-1"></i>DNI
                                </span>
                            @elseif ($huesped->tipo_documento == 2)
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="fas fa-passport me-1"></i>Pasaporte
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">No especificado</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Número de Identificación -->
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-hashtag text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Número de Identificación</h6>
                        <p class="mb-0 text-muted">{{ $huesped->numero_identificacion ?? 'No especificado' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Fecha de Expedición -->
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-calendar text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Fecha de Expedición</h6>
                        <p class="mb-0 text-muted">{{ $huesped->fecha_expedicion ? \Carbon\Carbon::parse($huesped->fecha_expedicion)->format('d/m/Y') : 'No especificada' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Información de Contacto -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-address-book text-primary me-2"></i>
            Información de Contacto
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- Email -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-envelope text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Email</h6>
                        <p class="mb-0 text-muted">
                            @if($huesped->email)
                                <a href="mailto:{{ $huesped->email }}" class="text-decoration-none">{{ $huesped->email }}</a>
                            @else
                                No especificado
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Teléfono Móvil -->
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-phone text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Teléfono Móvil</h6>
                        <p class="mb-0 text-muted">
                            @if($huesped->telefono_movil)
                                <a href="tel:{{ $huesped->telefono_movil }}" class="text-decoration-none">{{ $huesped->telefono_movil }}</a>
                            @else
                                No especificado
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Dirección -->
            @if($huesped->direccion)
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-info-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-map-marker-alt text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Dirección</h6>
                        <p class="mb-0 text-muted">{{ $huesped->direccion }}</p>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Localidad -->
            @if($huesped->localidad)
            <div class="col-md-6">
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <div class="avatar-sm bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-city text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 fw-semibold">Localidad</h6>
                        <p class="mb-0 text-muted">{{ $huesped->localidad }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Documentos de Identidad -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-images text-primary me-2"></i>
            Documentos de Identidad
        </h5>
    </div>
    <div class="card-body">
        @isset($photos)
            @if (count($photos) > 0)
                <div class="row g-3">
                    @foreach($photos as $photo)
                        <div class="col-md-6">
                            <div class="text-center">
                                <h6 class="fw-semibold mb-3">
                                    @if($photo->photo_categoria_id == 13)
                                        <i class="fas fa-id-card text-info me-2"></i>
                                        DNI - Frente
                                    @elseif($photo->photo_categoria_id == 14)
                                        <i class="fas fa-id-card text-info me-2"></i>
                                        DNI - Reverso
                                    @elseif($photo->photo_categoria_id == 15)
                                        <i class="fas fa-passport text-warning me-2"></i>
                                        Pasaporte
                                    @else
                                        <i class="fas fa-image text-secondary me-2"></i>
                                        {{ $photo->categoria->nombre ?? 'Documento' }}
                                    @endif
                                </h6>
                                <a href="{{ asset($photo->url) }}" data-fancybox="gallery" data-caption="{{ $photo->categoria->nombre ?? 'Documento' }}">
                                    <img src="{{ asset($photo->url) }}" 
                                         alt="{{ $photo->categoria->nombre ?? 'Documento' }}" 
                                         class="img-fluid rounded shadow-sm"
                                         style="object-fit: cover; object-position: center; max-height: 300px; width: 100%; cursor: pointer;">
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-image fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay documentos subidos</h5>
                    <p class="text-muted">No se han subido fotos de DNI o pasaporte para este huésped.</p>
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-image fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay documentos subidos</h5>
                <p class="text-muted">No se han subido fotos de DNI o pasaporte para este huésped.</p>
            </div>
        @endisset
    </div>
</div>

@endsection
