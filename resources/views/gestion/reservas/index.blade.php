@extends('layouts.appPersonal')

@section('title')
    {{ __('Gestión de Reservas - ') . $hoy->format('d/m/Y') }}
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
                    <div class="row align-items-center">
                        <!-- Columna Izquierda: Título y Fecha -->
                        <div class="col-md-8">
                            <div class="header-info">
                                <div class="d-flex align-items-center">
                                    <div class="apartment-icon me-3">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </div>
                                    <div class="apartment-details">
                                        <h1 class="apartment-title mb-1">Gestión de Reservas</h1>
                                        <p class="apartment-subtitle mb-0">{{ $hoy->format('l, d \d\e F Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna Derecha: Botón Volver -->
                        <div class="col-md-4 text-end">
                            <div class="header-actions">
                                <a href="{{ route('gestion.index') }}" class="apple-btn apple-btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Volver a Gestión
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas del Día -->
        <div class="info-banner mt-3 w-75 mx-auto" style="
            background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
            border: 2px solid #2196F3;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        ">
            <div class="row">
                <div class="col-6 col-md-3">
                    <div class="text-center">
                        <i class="fas fa-calendar-plus fa-2x text-success mb-2"></i>
                        <h6 class="text-success mb-1">Entran Hoy</h6>
                        <strong>{{ $totalEntradas }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-center">
                        <i class="fas fa-calendar-minus fa-2x text-danger mb-2"></i>
                        <h6 class="text-danger mb-1">Salen Hoy</h6>
                        <strong>{{ $totalSalidas }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-center">
                        <i class="fas fa-home fa-2x text-warning mb-2"></i>
                        <h6 class="text-warning mb-1">Ocupadas</h6>
                        <strong>{{ $totalOcupadas }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-center">
                        <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                        <h6 class="text-primary mb-1">Total Hoy</h6>
                        <strong>{{ $totalReservas }}</strong>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtros por Estado -->
        <div class="apple-section mb-4 mt-4">
            <div class="section-header">
                <div class="section-title-container">
                    <i class="fas fa-filter text-primary"></i>
                    <h3>Filtros por Estado</h3>
                </div>
            </div>
            <div class="section-content">
                <div class="filtros-estado">
                    <div class="row">
                        <!-- Primera fila: Todos y Entran Hoy -->
                        <div class="col-6 col-md-6 mb-3">
                            <button type="button" class="estado-filter-btn active" data-estado="todos" onclick="filtrarPorEstado('todos')">
                                <i class="fas fa-list me-2"></i>
                                <span class="estado-count">{{ $totalReservas }}</span>
                                <span class="estado-label">Todos</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-6 mb-3">
                            <button type="button" class="estado-filter-btn" data-estado="entrada" onclick="filtrarPorEstado('entrada')">
                                <i class="fas fa-calendar-plus me-2"></i>
                                <span class="estado-count">{{ $totalEntradas }}</span>
                                <span class="estado-label">Entran Hoy</span>
                            </button>
                        </div>
                        
                        <!-- Segunda fila: Salen Hoy y Ocupadas -->
                        <div class="col-6 col-md-6 mb-3">
                            <button type="button" class="estado-filter-btn" data-estado="salida" onclick="filtrarPorEstado('salida')">
                                <i class="fas fa-calendar-minus me-2"></i>
                                <span class="estado-count">{{ $totalSalidas }}</span>
                                <span class="estado-label">Salen Hoy</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-6 mb-3">
                            <button type="button" class="estado-filter-btn" data-estado="ocupada" onclick="filtrarPorEstado('ocupada')">
                                <i class="fas fa-home me-2"></i>
                                <span class="estado-count">{{ $totalOcupadas }}</span>
                                <span class="estado-label">Ocupadas</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="apple-card-body">
            <!-- Sistema de Búsqueda Avanzada -->
            <div class="apple-section mb-4">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-search text-primary"></i>
                        <h3>Sistema de Búsqueda Avanzada</h3>
                    </div>
                </div>
                <div class="section-content">
                    <!-- Búsqueda por Término -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="terminoBusqueda" class="form-label fw-semibold">
                                <i class="fas fa-search me-2"></i>
                                Buscar por Cliente, Código, DNI, Teléfono o Email
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       id="terminoBusqueda" 
                                       placeholder="Ej: Juan, 12345, 12345678A, juan@email.com..."
                                       value="{{ $terminoBusqueda ?? '' }}">
                                <button type="button" class="apple-btn apple-btn-primary" onclick="buscarReservas()">
                                    <i class="fas fa-search me-2"></i>
                                    Buscar
                                </button>
                            </div>
                            <small class="text-muted">
                                Busca por nombre, apellidos, alias, DNI, teléfono, móvil, email o código de reserva
                            </small>
                        </div>
                    </div>
                    
                    <!-- Filtros de Fecha y Apartamento -->
                    <div class="row">
                        <div class="col-md-5">
                            <label for="fechaBusqueda" class="form-label fw-semibold">
                                <i class="fas fa-calendar me-2"></i>
                                Fecha Específica
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="fechaBusqueda" 
                                   value="{{ $fecha ?? '' }}">
                        </div>
                        <div class="col-md-5">
                            <label for="apartamentoBusqueda" class="form-label fw-semibold">
                                <i class="fas fa-home me-2"></i>
                                Filtrar por Apartamento
                            </label>
                            <select class="form-control" id="apartamentoBusqueda">
                                <option value="">Todos los apartamentos</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="apple-btn apple-btn-secondary w-100" onclick="limpiarFiltros()">
                                <i class="fas fa-times me-2"></i>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Información de Filtros Activos -->
                    <div id="filtrosActivos" class="mt-3" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Filtros activos:</strong>
                            <span id="filtrosTexto"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservas de Entrada -->
            @if($reservasEntradaHoy->count() > 0)
            <div class="apple-section mb-4" data-estado="entrada">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-calendar-plus text-success"></i>
                        <h3>Reservas que Entran Hoy ({{ $reservasEntradaHoy->count() }})</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="reservas-grid">
                        @foreach($reservasEntradaHoy as $reserva)
                        <div class="reserva-card entrada">
                            <div class="reserva-header">
                                <div class="reserva-icon">
                                    <i class="fas fa-calendar-plus text-success"></i>
                                </div>
                                <div class="reserva-info">
                                    <h5>{{ $reserva->codigo_reserva }}</h5>
                                    <p class="reserva-apartamento">{{ $reserva->apartamento->nombre ?? 'N/A' }}</p>
                                </div>
                                <div class="reserva-status">
                                    <span class="badge bg-success">Entrada</span>
                                </div>
                            </div>
                            <div class="reserva-details">
                                <div class="detail-item">
                                    <strong>Cliente:</strong>
                                    <span>{{ $reserva->cliente->nombre ?? 'N/A' }}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Hora:</strong>
                                    <span>{{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('H:i') }}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Personas:</strong>
                                    <span>{{ $reserva->numero_personas ?? 0 }}</span>
                                    @if($reserva->numero_ninos > 0)
                                        <span class="badge bg-info ms-2">
                                            <i class="fas fa-baby me-1"></i>
                                            {{ $reserva->numero_ninos }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="reserva-actions">
                                <a href="{{ route('gestion.reservas.show', $reserva->id) }}" class="apple-btn apple-btn-primary btn-sm">
                                    <i class="fas fa-eye me-2"></i>
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Reservas de Salida -->
            @if($reservasSalidaHoy->count() > 0)
            <div class="apple-section mb-4" data-estado="salida">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-calendar-minus text-danger"></i>
                        <h3>Reservas que Salen Hoy ({{ $reservasSalidaHoy->count() }})</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="reservas-grid">
                        @foreach($reservasSalidaHoy as $reserva)
                        <div class="reserva-card salida">
                            <div class="reserva-header">
                                <div class="reserva-icon">
                                    <i class="fas fa-calendar-minus text-danger"></i>
                                </div>
                                <div class="reserva-info">
                                    <h5>{{ $reserva->codigo_reserva }}</h5>
                                    <p class="reserva-apartamento">{{ $reserva->apartamento->nombre ?? 'N/A' }}</p>
                                </div>
                                <div class="reserva-status">
                                    <span class="badge bg-danger">Salida</span>
                                </div>
                            </div>
                            <div class="reserva-details">
                                <div class="detail-item">
                                    <strong>Cliente:</strong>
                                    <span>{{ $reserva->cliente->nombre ?? 'N/A' }}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Hora:</strong>
                                    <span>{{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('H:i') }}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Personas:</strong>
                                    <span>{{ $reserva->numero_personas ?? 0 }}</span>
                                    @if($reserva->numero_ninos > 0)
                                        <span class="badge bg-info ms-2">
                                            <i class="fas fa-baby me-1"></i>
                                            {{ $reserva->numero_ninos }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="reserva-actions">
                                <a href="{{ route('gestion.reservas.show', $reserva->id) }}" class="apple-btn apple-btn-primary btn-sm">
                                    <i class="fas fa-eye me-2"></i>
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Reservas Ocupadas -->
            @if($reservasOcupadas->count() > 0)
            <div class="apple-section mb-4" data-estado="ocupada">
                <div class="section-header">
                    <div class="section-title-container">
                        <i class="fas fa-home text-warning"></i>
                        <h3>Reservas Ocupadas ({{ $reservasOcupadas->count() }})</h3>
                    </div>
                </div>
                <div class="section-content">
                    <div class="reservas-grid">
                        @foreach($reservasOcupadas as $reserva)
                        <div class="reserva-card ocupada">
                            <div class="reserva-header">
                                <div class="reserva-icon">
                                    <i class="fas fa-home text-warning"></i>
                                </div>
                                <div class="reserva-info">
                                    <h5>{{ $reserva->codigo_reserva }}</h5>
                                    <p class="reserva-apartamento">{{ $reserva->apartamento->nombre ?? 'N/A' }}</p>
                                </div>
                                <div class="reserva-status">
                                    <span class="badge bg-warning">Ocupada</span>
                                </div>
                            </div>
                            <div class="reserva-details">
                                <div class="detail-item">
                                    <strong>Cliente:</strong>
                                    <span>{{ $reserva->cliente->nombre ?? 'N/A' }}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Entrada:</strong>
                                    <span>{{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Salida:</strong>
                                    <span>{{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') }}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Personas:</strong>
                                    <span>{{ $reserva->numero_personas ?? 0 }}</span>
                                    @if($reserva->numero_ninos > 0)
                                        <span class="badge bg-info ms-2">
                                            <i class="fas fa-baby me-1"></i>
                                            {{ $reserva->numero_ninos }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="reserva-actions">
                                <a href="{{ route('gestion.reservas.show', $reserva->id) }}" class="apple-btn apple-btn-primary btn-sm">
                                    <i class="fas fa-eye me-2"></i>
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Mensaje si no hay reservas -->
            @if($totalReservas == 0)
            <div class="apple-section">
                <div class="section-content text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay reservas para hoy</h4>
                    <p class="text-muted">No se encontraron reservas de entrada, salida o ocupadas para la fecha {{ $hoy->format('d/m/Y') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Estilos completos de gestion-edit -->
<style>
    /* Variables CSS Apple - IDÉNTICAS A gestion.edit */
    :root {
        --apple-blue: #007AFF;
        --apple-blue-dark: #0056CC;
        --apple-blue-light: #4DA3FF;
        --system-gray: #8E8E93;
        --system-gray-2: #AEAEB2;
        --system-gray-3: #C7C7CC;
        --system-gray-4: #D1D1D6;
        --system-gray-5: #E5E5EA;
        --system-gray-6: #F2F2F7;
        --success-green: #34C759;
        --warning-orange: #FF9500;
        --error-red: #FF3B30;
        --info-blue: #5AC8FA;
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
    padding: var(--spacing-lg);
    background: var(--system-gray-6);
    min-height: 100vh;
}

@media (min-width: 768px) {
    .apple-container {
        max-width: 720px;
        padding: calc(var(--spacing-lg) * 1.5);
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

/* Responsive para header en dos columnas */
@media (max-width: 768px) {
    .header-main .row {
        flex-direction: column;
        gap: 15px;
    }
    
    .header-main .col-md-8,
    .header-main .col-md-4 {
        width: 100%;
        text-align: center;
    }
    
    .header-actions {
        justify-content: center;
    }
    
    .apartment-title {
        font-size: 18px;
    }
    
    .apartment-subtitle {
        font-size: 13px;
    }
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

/* Grid de Reservas */
.reservas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

/* Tarjeta de Reserva */
.reserva-card {
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    padding: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.reserva-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.reserva-card.entrada {
    border-left: 4px solid #28a745;
}

.reserva-card.salida {
    border-left: 4px solid #dc3545;
}

.reserva-card.ocupada {
    border-left: 4px solid #ffc107;
}

.reserva-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.reserva-icon {
    width: 40px;
    height: 40px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.reserva-info h5 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}

.reserva-apartamento {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.reserva-status {
    margin-left: auto;
}

.reserva-details {
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item strong {
    color: #333;
    font-weight: 600;
    min-width: 80px;
}

.reserva-actions {
    text-align: center;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.9rem;
}

/* Botones Apple - IDÉNTICOS A gestion.edit */
.apple-btn {
    width: 100%;
    padding: var(--spacing-md) var(--spacing-lg);
    font-size: 17px;
    font-weight: 600;
    border: none;
    border-radius: var(--border-radius-md);
    transition: all 0.3s ease;
    cursor: pointer;
    text-transform: none;
    letter-spacing: -0.01em;
}

.apple-btn-primary {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%);
    color: #FFFFFF;
    box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
}

.apple-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 122, 255, 0.4);
}

.apple-btn-primary:active {
    transform: translateY(0);
}

.apple-btn-success {
    background: linear-gradient(135deg, var(--success-green) 0%, #30D158 100%);
    color: #FFFFFF;
    box-shadow: 0 4px 16px rgba(52, 199, 89, 0.3);
}

.apple-btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(52, 199, 89, 0.4);
}

.apple-btn-secondary {
    background: var(--system-gray-6);
    border: 1px solid var(--system-gray-4);
    color: var(--system-gray);
}

.apple-btn-secondary:hover {
    background: var(--system-gray-5);
    color: #1D1D1F;
}

    .apple-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }
    
    /* Estilos específicos para resultados de búsqueda */
    .reserva-card.busqueda {
        border: 2px solid #007AFF !important;
        box-shadow: 0 4px 20px rgba(0, 122, 255, 0.15) !important;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%) !important;
        position: relative;
        overflow: hidden;
    }
    
    .reserva-card.busqueda::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #007AFF, #4DA3FF, #007AFF);
        animation: shimmer 2s infinite;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    .reserva-card.busqueda:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 122, 255, 0.25) !important;
    }
    
    .reserva-card.busqueda .reserva-info h5 {
        color: #007AFF !important;
        font-weight: 700 !important;
        font-size: 1.2rem !important;
    }
    
    .reserva-card.busqueda .badge {
        font-size: 0.9rem !important;
        padding: 8px 12px !important;
        border-radius: 20px !important;
    }
    
    /* Filtros por Estado */
    .filtros-estado {
        margin-bottom: 20px;
    }

    .estado-filter-btn {
        width: 100%;
        padding: 12px 8px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #6c757d;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
        min-height: 80px;
    }

    .estado-filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .estado-filter-btn.active {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4);
    }

    .estado-filter-btn[data-estado="entrada"].active {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
    }

    .estado-filter-btn[data-estado="salida"].active {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
    }

    .estado-filter-btn[data-estado="ocupada"].active {
        background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        color: #212529;
        box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
    }

    .estado-count {
        display: block;
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 3px;
    }

    .estado-label {
        display: block;
        font-size: 0.8rem;
        opacity: 0.9;
        line-height: 1.2;
    }

    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }

    .loading-content {
        background: white;
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 400px;
        width: 90%;
    }

    .loading-spinner {
        margin-bottom: 20px;
    }

    .loading-spinner .spinner-border {
        width: 3rem;
        height: 3rem;
        color: var(--apple-blue);
    }

    .loading-content h3 {
        color: #1D1D1F;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .loading-subtitle {
        color: #6C6C70;
        font-size: 1rem;
        margin-bottom: 0;
    }
    
    /* Responsive - IDÉNTICO A gestion.edit */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: var(--spacing-md);
        text-align: center;
    }
    
    .header-main {
        flex-direction: column;
        gap: var(--spacing-md);
        align-items: center;
    }
    
    .apartment-title {
        font-size: 18px;
        text-align: center;
    }
    
    .apartment-subtitle {
        font-size: 13px;
        text-align: center;
    }
    
    .progress-badge {
        padding: 10px 14px;
    }
    
    .progress-count {
        font-size: 14px;
    }
    
    .progress-label {
        font-size: 10px;
    }
    
    .section-header {
        padding: 10px var(--spacing-md);
        min-height: 44px;
    }
    
    .section-title {
        font-size: 15px;
    }
    
    .section-icon {
        font-size: 14px;
    }
    
    .section-controls {
        justify-content: center;
    }
    
    .apple-card-header {
        padding: var(--spacing-md);
    }
    
    .apple-card-body {
        padding: var(--spacing-md);
    }
    
    .section-content {
        padding: var(--spacing-md);
    }
    
    .checklist-item {
        padding: 8px 0;
    }
    
    .item-label {
        font-size: 14px;
    }
    
    .apple-container {
        padding: var(--spacing-md);
    }
    
    .apple-section {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .info-banner {
        width: 95% !important;
        padding: 15px !important;
    }
    
    .info-banner .row .col-md-3 {
        margin-bottom: 15px;
    }
    
    .estado-filter-btn {
        padding: 10px 6px;
        margin-bottom: 8px;
        min-height: 70px;
    }
    
    .estado-count {
        font-size: 1.1rem;
    }
    
    .estado-label {
        font-size: 0.75rem;
    }
    
    /* En móvil, los botones se muestran en 2 columnas */
    .filtros-estado .col-6 {
        width: 50%;
    }
    
    .reservas-grid {
        grid-template-columns: 1fr;
    }
    
    .reserva-card {
        padding: 15px;
    }
    
    .reserva-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .reserva-status {
        margin-left: 0;
    }
    
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .detail-item strong {
        min-width: auto;
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
    
    .reserva-card {
        padding: 12px;
    }
    
    .reserva-info h5 {
        font-size: 1rem;
    }
    
    .reserva-apartamento {
        font-size: 0.8rem;
    }
    
    .apple-btn {
        min-width: 100px;
        padding: 8px 16px;
        font-size: 0.8rem;
    }
}
</style>

<!-- JavaScript para búsqueda avanzada -->
<script>
let apartamentos = [];

// Cargar apartamentos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vista de gestión de reservas cargada correctamente');
    console.log('Fecha:', '{{ $hoy->format("d/m/Y") }}');
    console.log('Total reservas:', {{ $totalReservas }});
    
    // Cargar apartamentos
    cargarApartamentos();
    
    // Aplicar filtros si existen
    aplicarFiltrosExistentes();
    
    // Event listeners para búsqueda en tiempo real
    document.getElementById('terminoBusqueda').addEventListener('input', function() {
        if (this.value.length >= 3) {
            buscarReservas();
        } else if (this.value.length === 0) {
            // Solo buscar si hay otros filtros activos
            const fecha = document.getElementById('fechaBusqueda').value;
            const apartamentoId = document.getElementById('apartamentoBusqueda').value;
            if (fecha || apartamentoId) {
                buscarReservas();
            }
        }
    });
    
    document.getElementById('fechaBusqueda').addEventListener('change', function() {
        // Solo buscar si hay fecha seleccionada
        if (this.value) {
            buscarReservas();
        }
    });
    
    document.getElementById('apartamentoBusqueda').addEventListener('change', function() {
        // Solo buscar si hay apartamento seleccionado
        if (this.value) {
            buscarReservas();
        }
    });
});

// Cargar lista de apartamentos
async function cargarApartamentos() {
    try {
        console.log('Iniciando carga de apartamentos...');
        const url = '{{ route("gestion.reservas.apartamentos") }}';
        console.log('URL de la petición:', url);
        
        const response = await fetch(url);
        console.log('Respuesta recibida:', response);
        console.log('Status:', response.status);
        console.log('Headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Respuesta como texto:', text);
        
        let apartamentos;
        try {
            apartamentos = JSON.parse(text);
        } catch (parseError) {
            console.error('Error parseando JSON:', parseError);
            console.error('Texto recibido:', text);
            throw new Error('Respuesta no es JSON válido');
        }
        
        console.log('Apartamentos parseados:', apartamentos);
        
        const select = document.getElementById('apartamentoBusqueda');
        if (!select) {
            console.error('No se encontró el select de apartamentos');
            return;
        }
        
        select.innerHTML = '<option value="">Todos los apartamentos</option>';
        
        if (Array.isArray(apartamentos)) {
            apartamentos.forEach(apartamento => {
                const option = document.createElement('option');
                option.value = apartamento.id;
                option.textContent = `${apartamento.nombre} - ${apartamento.edificio}`;
                select.appendChild(option);
            });
        } else {
            console.error('Apartamentos no es un array:', apartamentos);
        }
        
        // Aplicar valor si existe
        @if(isset($apartamentoId) && $apartamentoId)
            select.value = '{{ $apartamentoId }}';
        @endif
        
        console.log('Apartamentos cargados correctamente');
        
    } catch (error) {
        console.error('Error cargando apartamentos:', error);
        console.error('Stack trace:', error.stack);
    }
}

// Función principal de búsqueda
async function buscarReservas() {
    const fecha = document.getElementById('fechaBusqueda').value;
    const apartamentoId = document.getElementById('apartamentoBusqueda').value;
    const terminoBusqueda = document.getElementById('terminoBusqueda').value;
    
    // Construir URL con filtros
    const params = new URLSearchParams();
    if (fecha && fecha.trim() !== '') params.append('fecha', fecha);
    if (apartamentoId && apartamentoId.trim() !== '') params.append('apartamento_id', apartamentoId);
    if (terminoBusqueda && terminoBusqueda.trim() !== '') params.append('termino_busqueda', terminoBusqueda);
    
    // Mostrar loading
    mostrarLoading();
    
    try {
        console.log('Iniciando búsqueda con parámetros:', { fecha, apartamentoId, terminoBusqueda });
        
        const response = await fetch(`{{ route('gestion.reservas.buscar') }}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Respuesta recibida:', response.status, response.ok);
        
        if (response.ok) {
            const data = await response.json();
            console.log('Datos recibidos:', data);
            
            if (data && typeof data === 'object') {
                actualizarResultados(data);
                actualizarFiltrosActivos(fecha, apartamentoId, terminoBusqueda);
                console.log('Resultados actualizados correctamente');
            } else {
                throw new Error('Formato de datos inválido');
            }
        } else {
            const errorText = await response.text();
            console.error('Error HTTP:', response.status, errorText);
            throw new Error(`Error HTTP ${response.status}: ${errorText}`);
        }
    } catch (error) {
        console.error('Error completo:', error);
        console.error('Stack trace:', error.stack);
        
        // Mostrar error más específico
        let mensajeError = 'Error en la búsqueda. ';
        if (error.message.includes('Failed to fetch')) {
            mensajeError += 'Problema de conexión con el servidor.';
        } else if (error.message.includes('JSON')) {
            mensajeError += 'Error en el formato de respuesta.';
        } else {
            mensajeError += error.message;
        }
        
        alert(mensajeError);
    } finally {
        ocultarLoading();
    }
}

// Actualizar resultados en la página
function actualizarResultados(data) {
    try {
        console.log('Actualizando resultados con datos:', data);
        
        // Verificar que los datos sean válidos
        if (!data || typeof data !== 'object') {
            throw new Error('Datos de respuesta inválidos');
        }
        
        // Actualizar estadísticas de forma segura
        try {
            const infoBanner = document.querySelector('.info-banner .row');
            if (infoBanner) {
                const statsElements = infoBanner.querySelectorAll('.col-md-3 strong');
                if (statsElements.length >= 4) {
                    statsElements[0].textContent = data.totalEntradas || 0;
                    statsElements[1].textContent = data.totalSalidas || 0;
                    statsElements[2].textContent = data.totalOcupadas || 0;
                    statsElements[3].textContent = data.totalReservas || 0;
                    console.log('Estadísticas actualizadas:', {
                        entradas: data.totalEntradas,
                        salidas: data.totalSalidas,
                        ocupadas: data.totalOcupadas,
                        total: data.totalReservas
                    });
                } else {
                    console.warn('No se encontraron suficientes elementos de estadísticas');
                }
            } else {
                console.warn('No se encontró el banner de información');
            }
        } catch (statsError) {
            console.warn('Error actualizando estadísticas:', statsError);
        }
        
        // Actualizar secciones de reservas
        if (data.reservasEntradaHoy) {
            actualizarSeccionReservas('entrada', data.reservasEntradaHoy);
        }
        if (data.reservasSalidaHoy) {
            actualizarSeccionReservas('salida', data.reservasSalidaHoy);
        }
        if (data.reservasOcupadas) {
            actualizarSeccionReservas('ocupada', data.reservasOcupadas);
        }
        
        // Mostrar resultados de búsqueda si hay término de búsqueda
        if (data.termino_busqueda && data.reservas && data.reservas.length > 0) {
            mostrarResultadosBusqueda(data.reservas, data.termino_busqueda);
        }
        
        // Actualizar URL sin recargar la página
        try {
            const url = new URL(window.location);
            if (data.fecha) url.searchParams.set('fecha', data.fecha);
            if (data.apartamento_id) url.searchParams.set('apartamento_id', data.apartamento_id);
            if (data.termino_busqueda) url.searchParams.set('termino_busqueda', data.termino_busqueda);
            window.history.pushState({}, '', url);
        } catch (urlError) {
            console.warn('Error actualizando URL:', urlError);
        }
        
        console.log('Resultados actualizados correctamente');
        
    } catch (error) {
        console.error('Error en actualizarResultados:', error);
        throw error;
    }
}

// Mostrar resultados de búsqueda
function mostrarResultadosBusqueda(reservas, terminoBusqueda) {
    try {
        console.log('Mostrando resultados de búsqueda:', reservas.length, 'reservas para:', terminoBusqueda);
        
        // Buscar o crear contenedor de resultados de búsqueda
        let contenedorBusqueda = document.getElementById('resultados-busqueda');
        if (!contenedorBusqueda) {
            contenedorBusqueda = document.createElement('div');
            contenedorBusqueda.id = 'resultados-busqueda';
            contenedorBusqueda.className = 'apple-section mb-4';
            
            // Insertar después del sistema de búsqueda
            const sistemaBusqueda = document.querySelector('.apple-section');
            if (sistemaBusqueda && sistemaBusqueda.parentNode) {
                sistemaBusqueda.parentNode.insertBefore(contenedorBusqueda, sistemaBusqueda.nextSibling);
            }
        }
        
        // Crear contenido de resultados
        contenedorBusqueda.innerHTML = `
            <div class="section-header">
                <div class="section-title-container">
                    <i class="fas fa-search text-primary"></i>
                    <h3>🔍 Resultados de búsqueda para "${terminoBusqueda}" (${reservas.length} reserva${reservas.length !== 1 ? 's' : ''})</h3>
                </div>
            </div>
            <div class="section-content">
                <div class="reservas-grid">
                    ${reservas.map(reserva => `
                        <div class="reserva-card busqueda" style="border-left: 4px solid #007AFF;">
                            <div class="reserva-header">
                                <div class="reserva-icon">
                                    <i class="fas fa-calendar-check text-primary"></i>
                                </div>
                                <div class="reserva-info">
                                    <h5 style="color: #007AFF; font-weight: 700;">${reserva.codigo_reserva || 'N/A'}</h5>
                                    <p class="reserva-apartamento">Apartamento ID: ${reserva.apartamento_id || 'N/A'}</p>
                                </div>
                                <div class="reserva-status">
                                    <span class="badge bg-primary">✅ Encontrada</span>
                                </div>
                            </div>
                            <div class="reserva-details">
                                <div class="detail-item">
                                    <strong>Cliente ID:</strong>
                                    <span>${reserva.cliente_id || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Entrada:</strong>
                                    <span>${reserva.fecha_entrada ? new Date(reserva.fecha_entrada).toLocaleDateString('es-ES') : 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Salida:</strong>
                                    <span>${reserva.fecha_salida ? new Date(reserva.fecha_salida).toLocaleDateString('es-ES') : 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <strong>Precio:</strong>
                                    <span style="font-weight: 600; color: #28a745;">${reserva.precio || 'N/A'}€</span>
                                </div>
                            </div>
                            <div class="reserva-actions">
                                <a href="/gestion/reservas/${reserva.id}" class="apple-btn apple-btn-primary btn-sm">
                                    <i class="fas fa-eye me-2"></i>
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        console.log('Resultados de búsqueda mostrados correctamente');
        
    } catch (error) {
        console.error('Error mostrando resultados de búsqueda:', error);
    }
}

// Actualizar sección específica de reservas
function actualizarSeccionReservas(tipo, reservas) {
    try {
        console.log('Actualizando sección de reservas:', tipo, 'con', reservas.length, 'reservas');
        
        // Buscar la sección usando selectores CSS válidos
        let seccion = null;
        const secciones = document.querySelectorAll('.apple-section');
        
        for (const sec of secciones) {
            const titulo = sec.querySelector('.section-title-container h3');
            if (titulo && titulo.textContent.toLowerCase().includes(tipo.toLowerCase())) {
                seccion = sec;
                break;
            }
        }
        
        if (!seccion) {
            console.warn(`No se encontró sección para tipo: ${tipo}`);
            return;
        }
        
        const grid = seccion.querySelector('.reservas-grid');
        if (!grid) {
            console.warn(`No se encontró grid en sección: ${tipo}`);
            return;
        }
        
        if (reservas.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-4">
                    <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No hay reservas de ${tipo} para los filtros seleccionados</p>
                </div>
            `;
            console.log(`Sección ${tipo} actualizada: sin reservas`);
            return;
        }
        
        grid.innerHTML = reservas.map(reserva => `
            <div class="reserva-card ${tipo}">
                <div class="reserva-header">
                    <div class="reserva-icon">
                        <i class="fas fa-${tipo === 'entrada' ? 'calendar-plus' : tipo === 'salida' ? 'calendar-minus' : 'home'} text-${tipo === 'entrada' ? 'success' : tipo === 'salida' ? 'danger' : 'warning'}"></i>
                    </div>
                    <div class="reserva-info">
                        <h5>${reserva.codigo_reserva || 'N/A'}</h5>
                        <p class="reserva-apartamento">${reserva.apartamento?.nombre || 'Apartamento ID: ' + (reserva.apartamento_id || 'N/A')}</p>
                    </div>
                    <div class="reserva-status">
                        <span class="badge bg-${tipo === 'entrada' ? 'success' : tipo === 'salida' ? 'danger' : 'warning'}">${tipo.charAt(0).toUpperCase() + tipo.slice(1)}</span>
                    </div>
                </div>
                <div class="reserva-details">
                    <div class="detail-item">
                        <strong>Cliente:</strong>
                        <span>${reserva.cliente?.nombre || 'Cliente ID: ' + (reserva.cliente_id || 'N/A')}</span>
                    </div>
                    <div class="detail-item">
                        <strong>${tipo === 'ocupada' ? 'Entrada:' : 'Hora:'}</strong>
                        <span>${reserva[tipo === 'ocupada' ? 'fecha_entrada' : `fecha_${tipo}`] ? new Date(reserva[tipo === 'ocupada' ? 'fecha_entrada' : `fecha_${tipo}`]).toLocaleDateString('es-ES') : 'N/A'}</span>
                    </div>
                    ${tipo === 'ocupada' ? `
                    <div class="detail-item">
                        <strong>Salida:</strong>
                        <span>${reserva.fecha_salida ? new Date(reserva.fecha_salida).toLocaleDateString('es-ES') : 'N/A'}</span>
                    </div>
                    ` : ''}
                    <div class="detail-item">
                        <strong>Personas:</strong>
                        <span>${reserva.numero_personas || 0}</span>
                        ${reserva.numero_ninos > 0 ? `<span class="badge bg-info ms-2"><i class="fas fa-baby me-1"></i>${reserva.numero_ninos}</span>` : ''}
                    </div>
                </div>
                <div class="reserva-actions">
                    <a href="/gestion/reservas/${reserva.id}" class="apple-btn apple-btn-primary btn-sm">
                        <i class="fas fa-eye me-2"></i>
                        Ver Detalles
                    </a>
                </div>
            </div>
        `).join('');
        
        console.log(`Sección ${tipo} actualizada con ${reservas.length} reservas`);
        
    } catch (error) {
        console.error(`Error actualizando sección ${tipo}:`, error);
    }
}

// Formatear fecha
function formatearFecha(fecha) {
    if (!fecha) return 'N/A';
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES');
}

// Actualizar filtros activos
function actualizarFiltrosActivos(fecha, apartamentoId, terminoBusqueda) {
    const filtrosActivos = document.getElementById('filtrosActivos');
    const filtrosTexto = document.getElementById('filtrosTexto');
    
    const filtros = [];
    if (fecha && fecha.trim() !== '') filtros.push(`Fecha: ${new Date(fecha).toLocaleDateString('es-ES')}`);
    if (apartamentoId && apartamentoId.trim() !== '') {
        const apartamento = apartamentos.find(a => a.id == apartamentoId);
        if (apartamento) filtros.push(`Apartamento: ${apartamento.nombre}`);
    }
    if (terminoBusqueda && terminoBusqueda.trim() !== '') filtros.push(`Búsqueda: "${terminoBusqueda}"`);
    
    if (filtros.length > 0) {
        filtrosTexto.textContent = filtros.join(' | ');
        filtrosActivos.style.display = 'block';
    } else {
        filtrosActivos.style.display = 'none';
    }
}

// Limpiar todos los filtros
function limpiarFiltros() {
    document.getElementById('fechaBusqueda').value = '';
    document.getElementById('apartamentoBusqueda').value = '';
    document.getElementById('terminoBusqueda').value = '';
    document.getElementById('filtrosActivos').style.display = 'none';
    
    // Recargar página con filtros limpios
    window.location.href = '{{ route("gestion.reservas.index") }}';
}

// Aplicar filtros existentes si vienen de la URL
function aplicarFiltrosExistentes() {
    @if(isset($fecha) && $fecha)
        document.getElementById('fechaBusqueda').value = '{{ $fecha }}';
    @endif
    
    @if(isset($terminoBusqueda) && $terminoBusqueda)
        document.getElementById('terminoBusqueda').value = '{{ $terminoBusqueda }}';
    @endif
}

// Filtrar por estado de reserva
function filtrarPorEstado(estado) {
    // Actualizar botones activos
    document.querySelectorAll('.estado-filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-estado="${estado}"]`).classList.add('active');
    
    // Mostrar/ocultar secciones
    const secciones = document.querySelectorAll('.apple-section[data-estado]');
    
    if (estado === 'todos') {
        secciones.forEach(seccion => {
            seccion.style.display = 'block';
        });
    } else {
        secciones.forEach(seccion => {
            if (seccion.dataset.estado === estado) {
                seccion.style.display = 'block';
            } else {
                seccion.style.display = 'none';
            }
        });
    }
    
    // Actualizar contador en el botón activo
    actualizarContadorActivo(estado);
}

// Actualizar contador del botón activo
function actualizarContadorActivo(estado) {
    const btnActivo = document.querySelector(`[data-estado="${estado}"]`);
    if (!btnActivo) return;
    
    let contador = 0;
    
    if (estado === 'todos') {
        contador = {{ $totalReservas }};
    } else if (estado === 'entrada') {
        contador = {{ $totalEntradas }};
    } else if (estado === 'salida') {
        contador = {{ $totalSalidas }};
    } else if (estado === 'ocupada') {
        contador = {{ $totalOcupadas }};
    }
    
    btnActivo.querySelector('.estado-count').textContent = contador;
}

// Mostrar/ocultar loading
function mostrarLoading() {
    // Crear overlay de loading si no existe
    if (!document.getElementById('loadingOverlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                </div>
                <h3>Buscando reservas...</h3>
                <p class="loading-subtitle">Por favor, espera mientras se procesa tu búsqueda</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function ocultarLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}
</script>
@endsection
