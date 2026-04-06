@extends('layouts.appPersonal')

@section('title')
    {{ __('Información de la Reserva - ') . $reserva->codigo_reserva }}
@endsection

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center text-white">Bienvenid@ {{Auth::user()->name}}</h5>
@endsection

@section('content')
<div class="apple-container">
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div class="apartment-details">
                            <h1 class="apartment-title">Reserva #{{ $reserva->codigo_reserva }}</h1>
                            <p class="apartment-subtitle">Información Completa</p>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="header-actions">
                        <a href="{{ route('gestion.reservas.index') }}" class="apple-btn apple-btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver a Reservas
                        </a>
                        <a href="{{ route('gestion.index') }}" class="apple-btn apple-btn-primary">
                            <i class="fas fa-home me-2"></i>
                            Gestión
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Principal de la Reserva -->
        <div class="info-banner mt-3 w-75 mx-auto" style="
            background: linear-gradient(135deg, #E8F5E8 0%, #D4EDDA 100%);
            border: 2px solid #28a745;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
        ">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-calendar-alt fa-2x text-success mb-2"></i>
                        <h6 class="text-success mb-1">Entrada</h6>
                        <strong>{{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-calendar-times fa-2x text-danger mb-2"></i>
                        <h6 class="text-danger mb-1">Salida</h6>
                        <strong>{{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-moon fa-2x text-info mb-2"></i>
                        <h6 class="text-info mb-1">Noches</h6>
                        <strong>{{ $noches }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-users fa-2x text-warning mb-2"></i>
                        <h6 class="text-warning mb-1">Personas</h6>
                        <strong>{{ $reserva->numero_personas ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="apple-card-body">
            <!-- Código de Reserva y Estado -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="apple-info-card">
                        <div class="info-header">
                            <i class="fas fa-barcode text-primary"></i>
                            <h5>Código de Reserva</h5>
                        </div>
                        <div class="info-content">
                            <span class="badge bg-primary fs-5">{{ $reserva->codigo_reserva ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="apple-info-card">
                        <div class="info-header">
                            <i class="fas fa-info-circle text-info"></i>
                            <h5>Estado</h5>
                        </div>
                        <div class="info-content">
                            <span class="badge bg-{{ $reserva->estado && $reserva->estado->id == 1 ? 'success' : 'warning' }} fs-5">
                                {{ $reserva->estado ? $reserva->estado->nombre : 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Apartamento -->
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-home text-primary"></i>
                        <h3>Información del Apartamento</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Nombre:</strong>
                                <span>{{ $reserva->apartamento->nombre ?? 'N/A' }}</span>
                            </div>
                            <div class="info-item">
                                <strong>Edificio:</strong>
                                <span>{{ $reserva->apartamento->edificio->nombre ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Precio:</strong>
                                <span class="text-success fw-bold fs-5">
                                    {{ number_format($reserva->precio ?? 0, 2) }} €
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>Origen:</strong>
                                <span>{{ $reserva->origen ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Cliente -->
            @if($reserva->cliente)
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-user-circle text-primary"></i>
                        <h3>Información del Cliente</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Nombre:</strong>
                                <span>{{ $reserva->cliente->nombre ?? 'N/A' }}</span>
                            </div>
                            <div class="info-item">
                                <strong>Apellidos:</strong>
                                <span>{{ $reserva->cliente->apellido1 ?? '' }} {{ $reserva->cliente->apellido2 ?? '' }}</span>
                            </div>
                            <div class="info-item">
                                <strong>Email:</strong>
                                <span>{{ $reserva->cliente->email ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Teléfono:</strong>
                                <span>{{ $reserva->cliente->telefono ?? 'N/A' }}</span>
                                @if($reserva->cliente->telefono)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $reserva->cliente->telefono) }}" 
                                       target="_blank" 
                                       class="btn btn-success btn-sm ms-2">
                                        <i class="fab fa-whatsapp"></i>
                                        WhatsApp
                                    </a>
                                @endif
                            </div>
                            <div class="info-item">
                                <strong>Móvil:</strong>
                                <span>{{ $reserva->cliente->telefono_movil ?? 'N/A' }}</span>
                                @if($reserva->cliente->telefono_movil)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $reserva->cliente->telefono_movil) }}" 
                                       target="_blank" 
                                       class="btn btn-success btn-sm ms-2">
                                        <i class="fab fa-whatsapp"></i>
                                        WhatsApp
                                    </a>
                                @endif
                            </div>
                            <div class="info-item">
                                <strong>País:</strong>
                                <span>{{ $reserva->cliente->nacionalidad ?? 'N/A' }}</span>
                            </div>
                            <div class="info-item">
                                <strong>DNI:</strong>
                                <span>{{ $reserva->cliente->dni ?? 'N/A' }}</span>
                                @if($reserva->cliente->dni)
                                    <a href="https://crm.apartamentosalgeciras.com/reservas/{{ $reserva->id }}/show" 
                                       target="_blank" 
                                       class="btn btn-info btn-sm ms-2">
                                        <i class="fas fa-external-link-alt"></i>
                                        Rellenar DNI
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Información de Huéspedes - Compacta -->
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-users text-success"></i>
                        <h3>Información de Huéspedes</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="reserva-stats-compact">
                        <div class="stats-row">
                            <div class="stat-item">
                                <i class="fas fa-users text-primary"></i>
                                <span class="stat-number">{{ $reserva->numero_personas ?? 0 }}</span>
                                <span class="stat-label">Adultos</span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <i class="fas fa-baby text-success"></i>
                                <span class="stat-number">{{ $reserva->numero_ninos ?? 0 }}</span>
                                <span class="stat-label">Niños</span>
                            </div>
                        </div>
                        <div class="stats-row mt-3">
                            <div class="stat-item">
                                <i class="fas fa-clock text-warning"></i>
                                <span class="stat-number">
                                    @if($reserva->fecha_entrada == now()->toDateString())
                                        <span class="badge bg-success badge-sm">HOY</span>
                                    @else
                                        {{ \Carbon\Carbon::parse($reserva->fecha_entrada)->diffForHumans() }}
                                    @endif
                                </span>
                                <span class="stat-label">Entrada</span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <i class="fas fa-moon text-info"></i>
                                <span class="stat-number">{{ $noches }}</span>
                                <span class="stat-label">Noches</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información adicional de niños -->
                    @if($reserva->numero_ninos > 0)
                    <div class="ninos-info mt-3">
                        @if($reserva->edades_ninos)
                        <div class="info-item">
                            <strong>Edades de los niños:</strong>
                            <span>
                                @foreach(json_decode($reserva->edades_ninos) as $index => $edad)
                                    {{ $edad }} años
                                    @if($index < count(json_decode($reserva->edades_ninos)) - 1)
                                        , 
                                    @endif
                                @endforeach
                            </span>
                        </div>
                        @endif
                        @if($reserva->notas_ninos)
                        <div class="info-item">
                            <strong>Notas niños:</strong>
                            <span class="text-warning">{{ $reserva->notas_ninos }}</span>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Amenities Automáticos para Niños -->
            @if($reserva->numero_ninos > 0 && $amenitiesNinos->count() > 0)
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-gift text-warning"></i>
                        <h3>Amenities Automáticos para Niños</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="amenities-grid">
                        @foreach($amenitiesNinos as $amenity)
                        <div class="amenity-card nino">
                            <div class="amenity-header">
                                <div class="amenity-icon">
                                    <i class="fas fa-baby text-success"></i>
                                </div>
                                <div class="amenity-info">
                                    <h6>{{ $amenity->nombre }}</h6>
                                    <p class="amenity-category">{{ $amenity->categoria }}</p>
                                </div>
                            </div>
                            <div class="amenity-details">
                                <div class="detail-item">
                                    <strong>Cantidad recomendada:</strong>
                                    <span>{{ $amenity->calcularCantidadParaNinos($reserva->numero_ninos, $reserva->edades_ninos ?? []) }}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Stock disponible:</strong>
                                    <span class="text-{{ $amenity->stock_actual > 0 ? 'success' : 'danger' }}">
                                        {{ $amenity->stock_actual }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Enlaces de Gestión -->
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-link text-info"></i>
                        <h3>Enlaces de Gestión</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="gestion-links">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route('gestion.edit', $reserva->id) }}" 
                                   class="btn btn-success w-100">
                                    <i class="fas fa-edit me-2"></i>
                                    Editar Limpieza
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('gestion.index') }}" 
                                   class="btn btn-secondary w-100">
                                    <i class="fas fa-home me-2"></i>
                                    Gestión Principal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Huéspedes -->
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-users text-primary"></i>
                        <h3>Información de Huéspedes</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="guest-card">
                                <div class="guest-icon">
                                    <i class="fas fa-user fa-2x text-primary"></i>
                                </div>
                                <div class="guest-info">
                                    <h4>{{ $reserva->numero_personas ?? 0 }}</h4>
                                    <p>Adultos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="guest-card">
                                <div class="guest-icon">
                                    <i class="fas fa-baby fa-2x text-success"></i>
                                </div>
                                <div class="guest-info">
                                    <h4>{{ $reserva->numero_ninos ?? 0 }}</h4>
                                    <p>Niños</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="guest-card">
                                <div class="guest-icon">
                                    <i class="fas fa-calendar-alt fa-2x text-warning"></i>
                                </div>
                                <div class="guest-info">
                                    <h4>{{ $reserva->edades_ninos ? count($reserva->edades_ninos) : 0 }}</h4>
                                    <p>Edades de Niños</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($reserva->numero_ninos > 0 && $reserva->edades_ninos)
                    <div class="mt-4">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-birthday-cake me-2"></i>
                            Edades de los Niños
                        </h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($reserva->edades_ninos as $edad)
                                <span class="badge bg-success fs-6 p-2">
                                    @if($edad <= 2)
                                        🍼 Bebé ({{ $edad }} años)
                                    @elseif($edad <= 12)
                                        👶 Niño ({{ $edad }} años)
                                    @else
                                        🧑 Adolescente ({{ $edad }} años)
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Amenities Automáticos para Niños -->
            @if($reserva->numero_ninos > 0 && $amenitiesNinos->count() > 0)
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-baby text-success"></i>
                        <h3>Amenities Automáticos para Niños</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="amenities-ninos-grid">
                        @foreach($amenitiesNinos as $amenity)
                        <div class="amenity-nino-card">
                            <div class="amenity-nino-header">
                                <div class="amenity-nino-icon">
                                    <i class="fas fa-gift text-success"></i>
                                </div>
                                <div class="amenity-nino-info">
                                    <h6>{{ $amenity->nombre }}</h6>
                                    <small class="text-muted">{{ $amenity->categoria }}</small>
                                </div>
                            </div>
                            <div class="amenity-nino-details">
                                <div class="detail-row">
                                    <span class="detail-label">Tipo:</span>
                                    <span class="detail-value">{{ $amenity->descripcion_tipo_nino }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Rango Edad:</span>
                                    <span class="detail-value">{{ $amenity->rango_edades }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Por Niño:</span>
                                    <span class="detail-value">{{ $amenity->cantidad_por_nino }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Total:</span>
                                    <span class="detail-value fw-bold text-success">
                                        {{ $amenity->calcularCantidadParaNinos($reserva->numero_ninos, $reserva->edades_ninos ?? []) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Notas sobre Niños -->
            @if($reserva->notas_ninos)
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-sticky-note text-warning"></i>
                        <h3>Notas sobre Niños</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ $reserva->notas_ninos }}
                    </div>
                </div>
            </div>
            @endif

            <!-- Información Adicional -->
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-cogs text-primary"></i>
                        <h3>Información Adicional</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Verificado:</strong>
                                <span class="badge bg-{{ $reserva->verificado ? 'success' : 'secondary' }}">
                                    {{ $reserva->verificado ? 'Sí' : 'No' }}
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>DNI Entregado:</strong>
                                <span class="badge bg-{{ $reserva->dni_entregado ? 'success' : 'secondary' }}">
                                    {{ $reserva->dni_entregado ? 'Sí' : 'No' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Enviado Webpol:</strong>
                                <span class="badge bg-{{ $reserva->enviado_webpol ? 'success' : 'secondary' }}">
                                    {{ $reserva->enviado_webpol ? 'Sí' : 'No' }}
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>Fecha de Limpieza:</strong>
                                <span>{{ $reserva->fecha_limpieza ? \Carbon\Carbon::parse($reserva->fecha_limpieza)->format('d/m/Y') : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones de Gestión -->
            <div class="apple-section">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-tools text-primary"></i>
                        <h3>Acciones de Gestión</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('gestion.incidencias.index') }}" class="apple-btn apple-btn-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Gestionar Incidencias
                        </a>
                        @if($reserva->cliente && ($reserva->cliente->telefono || $reserva->cliente->telefono_movil))
                        <button type="button" class="apple-btn apple-btn-success" onclick="abrirWhatsApp()">
                            <i class="fab fa-whatsapp me-2"></i>
                            Contactar por WhatsApp
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos completos de gestion-edit -->
<style>
:root {
    --apple-blue: #007AFF;
    --apple-blue-dark: #0056CC;
    --success-green: #34C759;
    --warning-orange: #FF9500;
    --danger-red: #FF3B30;
    --system-gray: #8E8E93;
    --system-gray-2: #AEAEB2;
    --system-gray-6: #F2F2F7;
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --border-radius-sm: 8px;
    --border-radius-md: 12px;
    --border-radius-lg: 16px;
}

/* Contenedor Principal */
.apple-container {
    max-width: 100%;
    margin: 0 auto;
    padding: var(--spacing-md);
    background: var(--system-gray-6);
    min-height: 100vh;
}

@media (min-width: 768px) {
    .apple-container {
        max-width: 720px;
        padding: var(--spacing-lg);
    }
}

@media (min-width: 992px) {
    .apple-container {
        max-width: 960px;
        padding: calc(var(--spacing-lg) * 2);
    }
}

/* Tarjeta Principal */
.apple-card {
    background: #FFFFFF;
    border-radius: var(--border-radius-lg);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: var(--spacing-lg);
}

/* Header de Tarjeta */
.apple-card-header {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%) !important;
    padding: var(--spacing-lg) var(--spacing-lg) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--spacing-lg);
}

.header-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--spacing-lg);
    flex: 1;
}

.header-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    flex: 1;
}

.apartment-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.apartment-icon i {
    font-size: 20px;
    color: #FFFFFF;
}

.apartment-details {
    flex: 1;
}

.apartment-title {
    font-size: 20px;
    font-weight: 700;
    color: #FFFFFF;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.apartment-subtitle {
    font-size: 14px;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    letter-spacing: -0.01em;
}

.header-actions {
    display: flex;
    gap: 15px;
}

/* Cuerpo de Tarjeta */
.apple-card-body {
    padding: var(--spacing-lg);
    background: #FFFFFF;
}

/* Secciones */
.apple-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #e9ecef;
}

.section-header {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%) !important;
    padding: 12px var(--spacing-lg) !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    min-height: 48px !important;
    border-radius: 15px 15px 0 0;
    margin: -25px -25px 20px -25px;
}

.section-title-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.section-title-container i {
    font-size: 1.5rem;
    width: 40px;
    text-align: center;
    color: #FFFFFF;
}

.section-title-container h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #FFFFFF;
}

.section-content {
    color: #555;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item strong {
    color: #333;
    font-weight: 600;
    min-width: 120px;
}

.apple-info-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border: 1px solid #e9ecef;
    height: 100%;
}

.info-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.info-header i {
    font-size: 1.3rem;
}

.info-header h5 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}

.info-content {
    text-align: center;
}

.guest-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border: 1px solid #e9ecef;
    height: 100%;
    transition: transform 0.2s ease;
}

.guest-card:hover {
    transform: translateY(-5px);
}

.guest-icon {
    margin-bottom: 15px;
}

.guest-info h4 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 5px 0;
    color: #333;
}

.guest-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.amenities-ninos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.amenity-nino-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #E8F5E8;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.1);
}

.amenity-nino-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.amenity-nino-icon {
    width: 40px;
    height: 40px;
    background: #E8F5E8;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #28a745;
}

.amenity-nino-info h6 {
    margin: 0;
    font-weight: 600;
    color: #333;
}

.amenity-nino-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detail-label {
    font-weight: 600;
    color: #666;
}

.detail-value {
    color: #333;
}

/* Botones Apple-style */
.apple-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    border: none;
    border-radius: 25px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: 0.95rem;
    text-align: center;
    justify-content: center;
    min-width: 140px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.apple-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
    text-decoration: none;
}

.apple-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.apple-btn-primary:hover {
    color: white;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.apple-btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

.apple-btn-secondary:hover {
    color: white;
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
}

.apple-btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.apple-btn-success:hover {
    color: white;
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
}

.apple-btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
}

.apple-btn-warning:hover {
    color: white;
    box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
}

.apple-btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.apple-btn-danger:hover {
    color: white;
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
}

.btn-success {
    background: #28a745;
    border: none;
    border-radius: 20px;
    padding: 6px 12px;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.btn-success:hover {
    background: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .apple-container {
        padding: 10px;
    }
    
    .apple-card-header {
        padding: 20px;
    }
    
    .apartment-title {
        font-size: 18px;
        text-align: center;
    }
    
    .apartment-subtitle {
        font-size: 13px;
        text-align: center;
    }
    
    .header-content {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .header-main {
        flex-direction: column;
        gap: 20px;
        align-items: center;
    }
    
    .apple-card-body {
        padding: 20px;
    }
    
    .apple-section {
        padding: 20px;
    }
    
    .section-header {
        padding: 10px var(--spacing-md);
        min-height: 44px;
        margin: -20px -20px 20px -20px;
    }
    
    .section-title-container h3 {
        font-size: 1.3rem;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .info-item strong {
        min-width: auto;
    }
    
    .amenities-ninos-grid {
        grid-template-columns: 1fr;
    }
    
    .apple-btn {
        min-width: 120px;
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .apple-container {
        padding: 5px;
    }
    
    .apple-card-header {
        padding: 15px;
    }
    
    .apartment-title {
        font-size: 16px;
    }
    
    .apartment-subtitle {
        font-size: 12px;
    }
    
    .apple-card-body {
        padding: 15px;
    }
    
    .apple-section {
        padding: 15px;
    }
    
    .section-header {
        padding: 8px 15px;
        margin: -15px -15px 15px -15px;
    }
    
    .section-title-container h3 {
        font-size: 1.1rem;
    }
    
    .apple-info-card {
        padding: 15px;
    }
    
    .guest-card {
        padding: 20px;
    }
    
    .guest-info h4 {
        font-size: 1.5rem;
    }
    
    .apple-btn {
        min-width: 100px;
        padding: 8px 16px;
        font-size: 0.8rem;
    }
    
    .stats-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .stat-divider {
        display: none;
    }
    
    .reserva-stats-compact {
        padding: 20px;
    }
}

/* Estilos para Amenities de Niños */
.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.amenity-card.nino {
    background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
    border: 2px solid #FF9800;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.2);
    transition: all 0.3s ease;
}

.amenity-card.nino:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 152, 0, 0.3);
}

.amenity-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.amenity-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 152, 0, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.amenity-icon i {
    font-size: 1.2rem;
    color: #FF9800;
}

.amenity-info h6 {
    margin: 0;
    font-weight: 600;
    color: #1D1D1F;
}

.amenity-category {
    margin: 0;
    font-size: 0.9rem;
    color: #6C6C70;
    font-style: italic;
}

.amenity-details {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    padding: 15px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.detail-item:last-child {
    margin-bottom: 0;
}

.detail-item strong {
    font-size: 0.9rem;
    color: #1D1D1F;
}

.detail-item span {
    font-weight: 600;
    color: #FF9800;
}

/* Estilos para Estadísticas Compactas */
.reserva-stats-compact {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 15px;
    padding: 15px;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.stats-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.stats-row:first-child {
    margin-bottom: 10px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 1;
}

.stat-item i {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.stat-number {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 2px;
}

.stat-label {
    font-size: 0.8rem;
    color: #6C6C70;
    font-weight: 500;
}

.stat-divider {
    width: 1px;
    height: 40px;
    background: rgba(108, 108, 112, 0.2);
}

.badge-sm {
    font-size: 0.7rem;
    padding: 2px 6px;
}

/* Estilos para Enlaces de Gestión */
.gestion-links {
    background: rgba(0, 122, 255, 0.05);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(0, 122, 255, 0.1);
}

.gestion-links .btn {
    border-radius: 20px;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-bottom: 10px;
}

.gestion-links .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}
</style>

<!-- JavaScript para WhatsApp -->
<script>
function abrirWhatsApp() {
    const telefono = '{{ $reserva->cliente->telefono ?? $reserva->cliente->telefono_movil ?? "" }}';
    
    if (telefono) {
        // Limpiar el teléfono de caracteres no numéricos
        const telefonoLimpio = telefono.replace(/[^0-9]/g, '');
        
        // Si no empieza con 34 (España), añadirlo
        const telefonoCompleto = telefonoLimpio.startsWith('34') ? telefonoLimpio : '34' + telefonoLimpio;
        
        // Abrir WhatsApp
        const url = `https://wa.me/${telefonoCompleto}`;
        window.open(url, '_blank');
    } else {
        alert('No hay número de teléfono disponible para WhatsApp');
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vista de información de reserva cargada correctamente');
    console.log('Reserva ID:', {{ $reserva->id }});
    console.log('Cliente:', '{{ $reserva->cliente->nombre ?? "N/A" }}');
    console.log('Niños:', {{ $reserva->numero_ninos ?? 0 }});
});
</script>
@endsection
