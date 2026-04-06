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
                            <h1 class="apartment-title">Reserva #{{ $reserva->id }}</h1>
                            <p class="apartment-subtitle">Gestión de Reserva</p>
                        </div>
                    </div>
                    
                    <!-- Botón Volver -->
                    <div class="header-actions">
                        <a href="{{ route('gestion.index') }}" class="apple-btn apple-btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver a Gestión
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
                        <strong>{{ $reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') : 'N/A' }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-calendar-times fa-2x text-danger mb-2"></i>
                        <h6 class="text-danger mb-1">Salida</h6>
                        <strong>{{ $reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') : 'N/A' }}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-moon fa-2x text-info mb-2"></i>
                        <h6 class="text-info mb-1">Noches</h6>
                        <strong>
                            @if($reserva->fecha_entrada && $reserva->fecha_salida)
                                {{ \Carbon\Carbon::parse($reserva->fecha_entrada)->diffInDays($reserva->fecha_salida) }}
                            @else
                                N/A
                            @endif
                        </strong>
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
                            <span class="badge bg-{{ $reserva->status == 'new' ? 'success' : 'warning' }} fs-5">
                                {{ ucfirst($reserva->status ?? 'N/A') }}
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
                                <strong>Dirección:</strong>
                                <span>{{ $reserva->apartamento->direccion ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Precio Total:</strong>
                                <span class="text-success fw-bold fs-5">
                                    {{ number_format($reserva->precio_total ?? 0, 2) }} €
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>Neto:</strong>
                                <span>{{ number_format($reserva->neto ?? 0, 2) }} €</span>
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
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
                        <a href="{{ route('gestion.edit', $reserva->apartamento->id ?? 1) }}" class="apple-btn apple-btn-primary">
                            <i class="fas fa-broom me-2"></i>
                            Gestionar Limpieza
                        </a>
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

<!-- Estilos específicos para esta vista -->
<style>
.apple-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.apple-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.apple-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    position: relative;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.apartment-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.apartment-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.apartment-subtitle {
    font-size: 1.2rem;
    margin: 0;
    opacity: 0.9;
}

.header-actions {
    display: flex;
    gap: 15px;
}

.apple-card-body {
    padding: 30px;
}

.apple-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #e9ecef;
}

.section-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
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
}

.section-title-container h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
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
}

.apple-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.apple-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
}

.apple-btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

.apple-btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
    color: white;
}

.apple-btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.apple-btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
    color: white;
}

.apple-btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
}

.apple-btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
    color: white;
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

@media (max-width: 768px) {
    .apple-container {
        padding: 10px;
    }
    
    .apple-card-header {
        padding: 20px;
    }
    
    .apartment-title {
        font-size: 1.8rem;
    }
    
    .header-content {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .apple-card-body {
        padding: 20px;
    }
    
    .apple-section {
        padding: 20px;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .info-item strong {
        min-width: auto;
    }
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
});
</script>
@endsection
