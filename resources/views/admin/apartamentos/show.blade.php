@extends('layouts.appAdmin')

@section('title', 'Detalles del Apartamento')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-building me-2 text-primary"></i>
                        {{ $apartamento->titulo ?? $apartamento->nombre }}
                    </h1>
                    <p class="text-muted mb-0">Información detallada y estadísticas del apartamento</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('apartamentos.admin.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <a href="{{ route('apartamentos.admin.edit', $apartamento->id) }}" class="btn btn-warning btn-lg">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    @if($apartamento->id_channex)
                        <button class="btn btn-info btn-lg" onclick="registrarWebhooks({{ $apartamento->id }})">
                            <i class="fas fa-sync me-2"></i>Registrar Webhooks
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas con filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-chart-line me-2 text-primary"></i>
                            Estadísticas del Apartamento
                        </h5>
                        <form method="GET" class="d-flex gap-2">
                            <select name="año" class="form-select form-select-sm" style="width: auto;">
                                @for($i = date('Y'); $i >= 2020; $i--)
                                    <option value="{{ $i }}" {{ $año == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            <select name="mes" class="form-select form-select-sm" style="width: auto;">
                                <option value="">Todo el año</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $mes == $i ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create($año, $i, 1)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter me-1"></i>Filtrar
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Total Reservas -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-primary-subtle rounded-3">
                                <i class="fas fa-calendar-check text-primary fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-primary">{{ $estadisticas['total_reservas'] }}</h4>
                                <small class="text-muted">Total Reservas</small>
                            </div>
                        </div>
                        
                        <!-- Total Ingresos -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-success-subtle rounded-3">
                                <i class="fas fa-euro-sign text-success fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-success">€{{ number_format($estadisticas['total_ingresos'], 2, ',', '.') }}</h4>
                                <small class="text-muted">Total Ingresos</small>
                            </div>
                        </div>
                        
                        <!-- Ingresos Netos -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-info-subtle rounded-3">
                                <i class="fas fa-chart-bar text-info fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-info">€{{ number_format($estadisticas['ingresos_netos'], 2, ',', '.') }}</h4>
                                <small class="text-muted">Ingresos Netos</small>
                            </div>
                        </div>
                        
                        <!-- Días de Ocupación -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-warning-subtle rounded-3">
                                <i class="fas fa-bed text-warning fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-warning">{{ $estadisticas['ocupacion_dias'] }}</h4>
                                <small class="text-muted">Días Ocupados</small>
                            </div>
                        </div>
                        
                        <!-- Promedio por Reserva -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-secondary-subtle rounded-3">
                                <i class="fas fa-calculator text-secondary fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-secondary">€{{ number_format($estadisticas['promedio_por_reserva'], 2, ',', '.') }}</h4>
                                <small class="text-muted">Promedio/Reserva</small>
                            </div>
                        </div>
                        
                        <!-- Mes Más Ocupado -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-danger-subtle rounded-3">
                                <i class="fas fa-star text-danger fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-danger">{{ $estadisticas['mes_mas_ocupado']['reservas'] }}</h4>
                                <small class="text-muted">{{ $estadisticas['mes_mas_ocupado']['nombre'] }}</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estadísticas de Ocupación por Personas -->
                    @if($estadisticas['estadisticas_personas']['total_con_datos'] > 0)
                    <div class="row g-3 mt-3">
                        <div class="col-12">
                            <h6 class="fw-semibold text-dark mb-3">
                                <i class="fas fa-users me-2 text-primary"></i>
                                Estadísticas de Ocupación por Número de Personas
                            </h6>
                        </div>
                        
                        <!-- Media de Adultos -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-primary-subtle rounded-3">
                                <i class="fas fa-user-friends text-primary fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-primary">{{ $estadisticas['estadisticas_personas']['media_adultos'] }}</h4>
                                <small class="text-muted">Media Adultos/Reserva</small>
                            </div>
                        </div>
                        
                        <!-- 1 Persona -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-info-subtle rounded-3">
                                <i class="fas fa-user text-info fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-info">{{ $estadisticas['estadisticas_personas']['porcentajes']['1_persona'] }}%</h4>
                                <small class="text-muted">1 Persona ({{ $estadisticas['estadisticas_personas']['conteos']['1_persona'] }})</small>
                            </div>
                        </div>
                        
                        <!-- 2 Personas -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-success-subtle rounded-3">
                                <i class="fas fa-user-friends text-success fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-success">{{ $estadisticas['estadisticas_personas']['porcentajes']['2_personas'] }}%</h4>
                                <small class="text-muted">2 Personas ({{ $estadisticas['estadisticas_personas']['conteos']['2_personas'] }})</small>
                            </div>
                        </div>
                        
                        <!-- 3 Personas -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-warning-subtle rounded-3">
                                <i class="fas fa-users text-warning fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-warning">{{ $estadisticas['estadisticas_personas']['porcentajes']['3_personas'] }}%</h4>
                                <small class="text-muted">3 Personas ({{ $estadisticas['estadisticas_personas']['conteos']['3_personas'] }})</small>
                            </div>
                        </div>
                        
                        <!-- 4 Personas -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-danger-subtle rounded-3">
                                <i class="fas fa-users text-danger fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-danger">{{ $estadisticas['estadisticas_personas']['porcentajes']['4_personas'] }}%</h4>
                                <small class="text-muted">4 Personas ({{ $estadisticas['estadisticas_personas']['conteos']['4_personas'] }})</small>
                            </div>
                        </div>
                        
                        <!-- Total con Datos -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="text-center p-3 bg-secondary-subtle rounded-3">
                                <i class="fas fa-chart-pie text-secondary fa-2x mb-2"></i>
                                <h4 class="mb-1 fw-bold text-secondary">{{ $estadisticas['estadisticas_personas']['total_con_datos'] }}</h4>
                                <small class="text-muted">Reservas con Datos</small>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Gráfico de reservas por mes -->
                    @if($estadisticas['total_reservas'] > 0)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="fw-semibold text-dark mb-3">
                                <i class="fas fa-chart-area me-2 text-primary"></i>
                                Evolución de Reservas por Mes - {{ $año }}
                            </h6>
                            <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                                <canvas id="reservasPorMes"></canvas>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna principal -->
        <div class="col-lg-8">
            <!-- Información General -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Título</label>
                                <div class="info-value">
                                    {{ $apartamento->titulo ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Tipo de Propiedad</label>
                                <div class="info-value">
                                    <span class="badge bg-primary">{{ $apartamento->property_type ?? 'No especificado' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Edificio</label>
                                <div class="info-value">
                                    {{ $apartamento->edificioRel ? $apartamento->edificioRel->nombre : 'No asignado' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Claves de Acceso</label>
                                <div class="info-value">
                                    {{ $apartamento->claves ?? 'No especificadas' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ubicación -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>Ubicación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Dirección</label>
                                <div class="info-value">
                                    {{ $apartamento->address ?? 'No especificada' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Ciudad</label>
                                <div class="info-value">
                                    {{ $apartamento->city ?? 'No especificada' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Código Postal</label>
                                <div class="info-value">
                                    {{ $apartamento->postal_code ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">País</label>
                                <div class="info-value">
                                    {{ $apartamento->country ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Plataforma del Estado -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-government me-2 text-primary"></i>Plataforma del Estado
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Código del Establecimiento</label>
                                <div class="info-value">
                                    {{ $apartamento->codigo_establecimiento ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">País (ISO3)</label>
                                <div class="info-value">
                                    <span class="badge bg-info">{{ $apartamento->pais_iso3 ?? 'No especificado' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Código Municipio INE</label>
                                <div class="info-value">
                                    {{ $apartamento->codigo_municipio_ine ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Nombre del Municipio</label>
                                <div class="info-value">
                                    {{ $apartamento->nombre_municipio ?? 'No especificado' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Tipo de Establecimiento</label>
                                <div class="info-value">
                                    @if($apartamento->tipo_establecimiento)
                                        <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $apartamento->tipo_establecimiento)) }}</span>
                                    @else
                                        No especificado
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Características del Apartamento -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-bed me-2 text-primary"></i>Características
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="info-group text-center">
                                <div class="feature-icon mb-2">
                                    <i class="fas fa-bed fa-2x text-primary"></i>
                                </div>
                                <label class="form-label fw-semibold text-muted">Habitaciones</label>
                                <div class="info-value">
                                    <span class="badge bg-primary fs-5">{{ $apartamento->bedrooms ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-group text-center">
                                <div class="feature-icon mb-2">
                                    <i class="fas fa-bath fa-2x text-info"></i>
                                </div>
                                <label class="form-label fw-semibold text-muted">Baños</label>
                                <div class="info-value">
                                    <span class="badge bg-info fs-5">{{ $apartamento->bathrooms ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-group text-center">
                                <div class="feature-icon mb-2">
                                    <i class="fas fa-users fa-2x text-success"></i>
                                </div>
                                <label class="form-label fw-semibold text-muted">Huéspedes</label>
                                <div class="info-value">
                                    <span class="badge bg-success fs-5">{{ $apartamento->max_guests ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-group text-center">
                                <div class="feature-icon mb-2">
                                    <i class="fas fa-ruler-combined fa-2x text-warning"></i>
                                </div>
                                <label class="form-label fw-semibold text-muted">Tamaño</label>
                                <div class="info-value">
                                    <span class="badge bg-warning fs-5">{{ $apartamento->size ?? 'N/A' }} m²</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descripción -->
            @if($apartamento->description)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-align-left me-2 text-primary"></i>Descripción
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-group">
                            <div class="info-value">
                                {{ $apartamento->description }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- IDs Externos -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-link me-2 text-primary"></i>IDs Externos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">ID Booking</label>
                                <div class="info-value">
                                    @if($apartamento->id_booking)
                                        <code class="bg-light px-2 py-1 rounded">{{ $apartamento->id_booking }}</code>
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">ID Airbnb</label>
                                <div class="info-value">
                                    @if($apartamento->id_airbnb)
                                        <code class="bg-light px-2 py-1 rounded">{{ $apartamento->id_airbnb }}</code>
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">ID Web</label>
                                <div class="info-value">
                                    @if($apartamento->id_web)
                                        <code class="bg-light px-2 py-1 rounded">{{ $apartamento->id_web }}</code>
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado de Sincronización -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-sync me-2 text-primary"></i>Estado de Sincronización
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Channex ID</label>
                                <div class="info-value">
                                    @if($apartamento->id_channex)
                                        <span class="badge bg-success fs-6">{{ $apartamento->id_channex }}</span>
                                        <small class="text-muted d-block mt-1">Sincronizado</small>
                                    @else
                                        <span class="badge bg-warning fs-6">No sincronizado</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Última Sincronización</label>
                                <div class="info-value">
                                    @if($apartamento->updated_at)
                                        <i class="fas fa-clock me-2 text-muted"></i>
                                        {{ $apartamento->updated_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-muted">Nunca</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Información del Apartamento -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Información del Apartamento
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">ID</label>
                        <div class="info-value">
                            <span class="badge bg-secondary fs-6">#{{ $apartamento->id }}</span>
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Nombre Interno</label>
                        <div class="info-value">
                            {{ $apartamento->nombre ?? 'No especificado' }}
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Creado</label>
                        <div class="info-value">
                            <i class="fas fa-calendar me-2 text-muted"></i>
                            {{ $apartamento->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    
                    <div class="info-group mb-3">
                        <label class="form-label fw-semibold text-muted">Última Actualización</label>
                        <div class="info-value">
                            <i class="fas fa-clock me-2 text-muted"></i>
                            {{ $apartamento->updated_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-tools me-2 text-primary"></i>Acciones Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('apartamentos.admin.edit', $apartamento->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Editar Apartamento
                        </a>
                        
                        @if($apartamento->id_channex)
                            <button class="btn btn-info" onclick="registrarWebhooks({{ $apartamento->id }})">
                                <i class="fas fa-sync me-2"></i>Registrar Webhooks
                            </button>
                        @endif
                        
                        <a href="{{ route('apartamentos.admin.estadisticas', $apartamento->id) }}" class="btn btn-outline-info">
                            <i class="fas fa-chart-bar me-2"></i>Ver Estadísticas
                        </a>
                    </div>
                </div>
            </div>

            <!-- Información del Edificio -->
            @if($apartamento->edificioRel)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-building me-2 text-primary"></i>Información del Edificio
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="info-group mb-3">
                            <label class="form-label fw-semibold text-muted">Nombre</label>
                            <div class="info-value">
                                <span class="badge bg-primary">{{ $apartamento->edificioRel->nombre ?? 'Sin nombre' }}</span>
                            </div>
                        </div>
                        
                        @if($apartamento->edificioRel->direccion)
                            <div class="info-group mb-3">
                                <label class="form-label fw-semibold text-muted">Dirección</label>
                                <div class="info-value">
                                    {{ $apartamento->edificioRel->direccion }}
                                </div>
                            </div>
                        @endif
                        
                        @if($apartamento->edificioRel->ciudad)
                            <div class="info-group">
                                <label class="form-label fw-semibold text-muted">Ciudad</label>
                                <div class="info-value">
                                    {{ $apartamento->edificioRel->ciudad }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-building me-2 text-primary"></i>Información del Edificio
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="info-group">
                            <div class="info-value">
                                <span class="text-muted">No hay edificio asignado a este apartamento</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Estilos para grupos de información */
.info-group {
    margin-bottom: 1rem;
}

.info-group label {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.info-value {
    font-size: 1rem;
    color: #495057;
}

/* Iconos de características */
.feature-icon {
    opacity: 0.8;
}

/* Botones */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.125rem;
}

/* Cards */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

/* Badges */
.badge {
    font-size: 0.75em;
    font-weight: 500;
}

/* Código */
code {
    font-size: 0.875rem;
    color: #495057;
}

/* Responsive */
@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between .btn {
        width: 100%;
    }
    
    .col-lg-8, .col-lg-4 {
        margin-bottom: 1rem;
    }
    
    .feature-icon {
        font-size: 1.5rem !important;
    }
}

/* Gráfico responsive */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

@media (max-width: 768px) {
    .chart-container {
        height: 250px;
    }
}
</style>

<script>
// Gráfico de reservas por mes
@if($estadisticas['total_reservas'] > 0)
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('reservasPorMes');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const datos = @json($estadisticas['reservas_por_mes']);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: datos.map(d => d.mes),
            datasets: [{
                label: 'Reservas',
                data: datos.map(d => d.reservas),
                borderColor: '#36a2eb',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#36a2eb',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }, {
                label: 'Ingresos (€)',
                data: datos.map(d => d.ingresos),
                borderColor: '#4bc0c0',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                pointBackgroundColor: '#4bc0c0',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#36a2eb',
                    borderWidth: 1,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return value;
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return '€' + value.toFixed(0);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
@endif
</script>
@endsection

@section('scriptHead')
<script>
// Función para registrar webhooks
function registrarWebhooks(apartamentoId) {
    Swal.fire({
        title: 'Registrando Webhooks',
        text: 'Por favor espera mientras se registran los webhooks...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/apartamentos/admin/${apartamentoId}/webhooks`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        let successCount = 0;
        let errorCount = 0;
        
        data.forEach(item => {
            if (item.status === 'success') successCount++;
            else errorCount++;
        });

        Swal.fire({
            title: 'Webhooks Registrados',
            html: `
                <div class="text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success fa-2x"></i>
                    </div>
                    <p><strong>${successCount}</strong> webhooks registrados exitosamente</p>
                    ${errorCount > 0 ? `<p class="text-warning"><strong>${errorCount}</strong> webhooks con errores</p>` : ''}
                </div>
            `,
            icon: 'success',
            confirmButtonText: 'Aceptar'
        });
    })
    .catch(error => {
        Swal.fire({
            title: 'Error',
            text: 'Error al registrar los webhooks: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    });
}
</script>
@endsection
