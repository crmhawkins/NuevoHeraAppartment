@extends('layouts.appAdmin')

@php
    // Helper para formatear fechas de forma segura
    function formatDate($date, $format = 'd/m/Y') {
        if (!$date) return '';
        if (is_object($date)) {
            return $date->format($format);
        }
        try {
            return \Carbon\Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return '';
        }
    }
    
    // Helper para formatear fechas con hora
    function formatDateTime($date, $format = 'd/m/Y H:i') {
        if (!$date) return '';
        if (is_object($date)) {
            return $date->format($format);
        }
        try {
            return \Carbon\Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return '';
        }
    }
    
    // Helper para calcular edad
    function calculateAge($date) {
        if (!$date) return '';
        if (is_object($date)) {
            return $date->age;
        }
        try {
            return \Carbon\Carbon::parse($date)->age;
        } catch (\Exception $e) {
            return '';
        }
    }
@endphp

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-user me-2 text-primary"></i>
                        {{ $cliente->nombre }} {{ $cliente->apellido1 }}
                        @if($cliente->apellido2)
                            {{ $cliente->apellido2 }}
                        @endif
                    </h1>
                    <p class="text-muted mb-0">
                        @if($cliente->alias)
                            Alias: {{ $cliente->alias }} • 
                        @endif
                        Cliente desde {{ formatDate($cliente->created_at) }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('clientes.edit', $cliente->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard de estadísticas del cliente -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-primary text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-calendar-check fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $reservas->count() }}</h3>
                            <small class="opacity-75">Total Reservas</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        @if($reservas->count() > 0)
                            <span class="badge bg-light text-dark px-2 py-1">
                                <i class="fas fa-clock me-1"></i> Última: {{ $reservas->first()->created_at->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="badge bg-light text-dark px-2 py-1">
                                <i class="fas fa-info me-1"></i> Sin reservas
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-success text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-envelope fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $mensajes->count() }}</h3>
                            <small class="opacity-75">Mensajes</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        @if($mensajes->count() > 0)
                            <span class="badge bg-light text-dark px-2 py-1">
                                <i class="fas fa-clock me-1"></i> Último: {{ $mensajes->first()->created_at->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="badge bg-light text-dark px-2 py-1">
                                <i class="fas fa-info me-1"></i> Sin mensajes
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-camera fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $photos->count() }}</h3>
                            <small class="opacity-75">Fotos</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        @if($photos->count() > 0)
                            <span class="badge bg-light text-dark px-2 py-1">
                                <i class="fas fa-clock me-1"></i> Última: {{ $photos->first()->created_at->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="badge bg-light text-dark px-2 py-1">
                                <i class="fas fa-info me-1"></i> Sin fotos
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-0 bg-gradient-{{ $cliente->inactivo ? 'danger' : 'success' }} text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-{{ $cliente->inactivo ? 'times-circle' : 'check-circle' }} fa-2x me-3"></i>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $cliente->inactivo ? 'Inactivo' : 'Activo' }}</h3>
                            <small class="opacity-75">Estado</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-light text-dark px-2 py-1">
                            <i class="fas fa-calendar me-1"></i> Registro: {{ formatDate($cliente->created_at) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard de Estadísticas Económicas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-chart-line me-2 text-success"></i>
                        Estadísticas Económicas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Total Pagado -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="d-flex align-items-center p-3 bg-success-subtle rounded-3">
                                <div class="avatar-sm me-3">
                                    <i class="fas fa-euro-sign text-success fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold text-success">Total Pagado</h6>
                                    <h4 class="mb-0 fw-bold text-success">€{{ number_format($estadisticasEconomicas['total_pagado'], 2, ',', '.') }}</h4>
                                    <small class="text-muted">{{ $reservas->count() }} reservas</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Valor Promedio -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="d-flex align-items-center p-3 bg-info-subtle rounded-3">
                                <div class="avatar-sm me-3">
                                    <i class="fas fa-chart-bar text-info fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold text-info">Valor Promedio</h6>
                                    <h4 class="mb-0 fw-bold text-info">€{{ number_format($estadisticasEconomicas['valor_promedio_reserva'], 2, ',', '.') }}</h4>
                                    <small class="text-muted">por reserva</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reservas Activas -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="d-flex align-items-center p-3 bg-warning-subtle rounded-3">
                                <div class="avatar-sm me-3">
                                    <i class="fas fa-calendar-check text-warning fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold text-warning">Reservas Activas</h6>
                                    <h4 class="mb-0 fw-bold text-warning">{{ $estadisticasEconomicas['reservas_activas'] }}</h4>
                                    <small class="text-muted">en curso</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reservas Completadas -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="d-flex align-items-center p-3 bg-primary-subtle rounded-3">
                                <div class="avatar-sm me-3">
                                    <i class="fas fa-check-circle text-primary fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold text-primary">Completadas</h6>
                                    <h4 class="mb-0 fw-bold text-primary">{{ $estadisticasEconomicas['reservas_completadas'] }}</h4>
                                    <small class="text-muted">finalizadas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detalle Económico -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-muted">Total Neto:</td>
                                            <td class="text-end fw-bold">€{{ number_format($estadisticasEconomicas['total_neto'], 2, ',', '.') }}</td>
                                            <td class="fw-semibold text-muted">Comisiones:</td>
                                            <td class="text-end fw-bold">€{{ number_format($estadisticasEconomicas['total_comisiones'], 2, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Cargos por Pago:</td>
                                            <td class="text-end fw-bold">€{{ number_format($estadisticasEconomicas['total_cargos_pago'], 2, ',', '.') }}</td>
                                            <td class="fw-semibold text-muted">Total IVA:</td>
                                            <td class="text-end fw-bold">€{{ number_format($estadisticasEconomicas['total_iva'], 2, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Reservas Pendientes de Pago:</td>
                                            <td class="text-end fw-bold text-warning">{{ $estadisticasEconomicas['reservas_pendientes_pago'] }}</td>
                                            <td class="fw-semibold text-muted">Estado Financiero:</td>
                                            <td class="text-end">
                                                @if($estadisticasEconomicas['reservas_pendientes_pago'] > 0)
                                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                                @else
                                                    <span class="badge bg-success">Al día</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Evolución Temporal -->
    @if($reservas->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-chart-area me-2 text-info"></i>
                        Evolución Temporal de Reservas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                                <canvas id="evolucionReservas"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex flex-column gap-3">
                                <div class="text-center p-3 bg-light rounded-3">
                                    <h6 class="mb-1 text-muted">Reserva Más Alta</h6>
                                    <h4 class="mb-0 fw-bold text-success">
                                        €{{ number_format($reservas->max('precio'), 2, ',', '.') }}
                                    </h4>
                                    <small class="text-muted">
                                        {{ $reservas->where('precio', $reservas->max('precio'))->first()->fecha_entrada ?? 'N/A' }}
                                    </small>
                                </div>
                                <div class="text-center p-3 bg-light rounded-3">
                                    <h6 class="mb-1 text-muted">Reserva Más Baja</h6>
                                    <h4 class="mb-0 fw-bold text-info">
                                        €{{ number_format($reservas->min('precio'), 2, ',', '.') }}
                                    </h4>
                                    <small class="text-muted">
                                        {{ $reservas->where('precio', $reservas->min('precio'))->first()->fecha_entrada ?? 'N/A' }}
                                    </small>
                                </div>
                                <div class="text-center p-3 bg-light rounded-3">
                                    <h6 class="mb-1 text-muted">Tendencia</h6>
                                    @php
                                        $reservasOrdenadas = $reservas->sortBy('created_at');
                                        $primeraReserva = $reservasOrdenadas->first();
                                        $ultimaReserva = $reservasOrdenadas->last();
                                        $tendencia = $ultimaReserva && $primeraReserva ? 
                                            (($ultimaReserva->precio - $primeraReserva->precio) / $primeraReserva->precio) * 100 : 0;
                                    @endphp
                                    <h4 class="mb-0 fw-bold {{ $tendencia >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $tendencia >= 0 ? '+' : '' }}{{ number_format($tendencia, 1) }}%
                                    </h4>
                                    <small class="text-muted">
                                        {{ $tendencia >= 0 ? 'Crecimiento' : 'Descenso' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Información del Cliente -->
        <div class="col-lg-8">
            <!-- Información Personal -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-user me-2 text-primary"></i>
                        Información Personal
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-sm me-3">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold">Nombre Completo</h6>
                                    <p class="mb-0 text-muted">
                                        {{ $cliente->nombre }} {{ $cliente->apellido1 }}
                                        @if($cliente->apellido2)
                                            {{ $cliente->apellido2 }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Alias</h6>
                                @if($cliente->alias)
                                    <span class="badge bg-primary-subtle text-primary px-2 py-1">
                                        <i class="fas fa-tag me-1"></i> {{ $cliente->alias }}
                                    </span>
                                @else
                                    <p class="mb-0 text-muted">No especificado</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Fecha de Nacimiento</h6>
                                @if($cliente->fecha_nacimiento)
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-birthday-cake me-1"></i>
                                        {{ formatDate($cliente->fecha_nacimiento) }}
                                        <small class="text-muted">({{ calculateAge($cliente->fecha_nacimiento) }} años)</small>
                                    </p>
                                @else
                                    <p class="mb-0 text-muted">No especificada</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Sexo</h6>
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-venus-mars me-1"></i>
                                    {{ $cliente->sexo ?? 'No especificado' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Contacto -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-address-book me-2 text-primary"></i>
                        Información de Contacto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Email</h6>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    <a href="mailto:{{ $cliente->email }}" class="text-decoration-none">
                                        {{ $cliente->email }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Teléfono</h6>
                                @if($cliente->telefono)
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-phone me-2 text-primary"></i>
                                        <a href="tel:{{ $cliente->telefono }}" class="text-decoration-none">
                                            {{ $cliente->telefono }}
                                        </a>
                                    </div>
                                @else
                                    <p class="mb-0 text-muted">No especificado</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Documentación -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-id-card me-2 text-primary"></i>
                        Documentación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Tipo de Documento</h6>
                                @if($cliente->tipo_documento)
                                    <span class="badge bg-info-subtle text-info px-2 py-1">
                                        <i class="fas fa-id-card me-1"></i> {{ $cliente->tipo_documento }}
                                    </span>
                                @else
                                    <p class="mb-0 text-muted">No especificado</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Número de Identificación</h6>
                                @if($cliente->num_identificacion)
                                    <p class="mb-0 text-muted font-monospace">
                                        {{ $cliente->num_identificacion }}
                                    </p>
                                @else
                                    <p class="mb-0 text-muted">No especificado</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Fecha de Expedición</h6>
                                @if($cliente->fecha_expedicion_doc)
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ formatDate($cliente->fecha_expedicion_doc) }}
                                    </p>
                                @else
                                    <p class="mb-0 text-muted">No especificada</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Nacionalidad e Idiomas -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-globe me-2 text-primary"></i>
                        Nacionalidad e Idiomas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Nacionalidad</h6>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary-subtle text-primary px-2 py-1 me-2">
                                        <i class="fas fa-flag me-1"></i> {{ $cliente->nacionalidad }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="mb-1 fw-semibold">Idiomas</h6>
                                @if($cliente->idiomas)
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-language me-1"></i>
                                        {{ $cliente->idiomas }}
                                    </p>
                                @else
                                    <p class="mb-0 text-muted">No especificados</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Dirección -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                        Información de Dirección
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($cliente->direccion || $cliente->localidad || $cliente->codigo_postal || $cliente->provincia || $cliente->estado)
                            <div class="col-12">
                                <div class="mb-3">
                                    <h6 class="mb-1 fw-semibold">Dirección Completa</h6>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        @if($cliente->direccion)
                                            {{ $cliente->direccion }},
                                        @endif
                                        @if($cliente->localidad)
                                            {{ $cliente->localidad }}
                                        @endif
                                        @if($cliente->codigo_postal)
                                            {{ $cliente->codigo_postal }}
                                        @endif
                                        @if($cliente->provincia)
                                            , {{ $cliente->provincia }}
                                        @endif
                                        @if($cliente->estado)
                                            , {{ $cliente->estado }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="text-center py-3">
                                    <i class="fas fa-map-marker-alt fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No hay información de dirección disponible</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar con acciones rápidas -->
        <div class="col-lg-4">
            <!-- Acciones Rápidas -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-bolt me-2 text-primary"></i>
                        Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('clientes.edit', $cliente->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>Editar Cliente
                        </a>
                        <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="d-grid">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirmarEliminacion()">
                                <i class="fas fa-trash me-2"></i>Inactivar Cliente
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-cog me-2 text-primary"></i>
                        Información del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="mb-1 fw-semibold">ID del Cliente</h6>
                        <p class="mb-0 text-muted font-monospace">{{ $cliente->id }}</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="mb-1 fw-semibold">Estado</h6>
                        @if($cliente->inactivo)
                            <span class="badge bg-danger px-2 py-1">
                                <i class="fas fa-times-circle me-1"></i> Inactivo
                            </span>
                        @else
                            <span class="badge bg-success px-2 py-1">
                                <i class="fas fa-check-circle me-1"></i> Activo
                            </span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <h6 class="mb-1 fw-semibold">Fecha de Registro</h6>
                        <p class="mb-0 text-muted">
                            <i class="fas fa-calendar-plus me-1"></i>
                            {{ formatDateTime($cliente->created_at) }}
                        </p>
                    </div>
                    <div class="mb-3">
                        <h6 class="mb-1 fw-semibold">Última Actualización</h6>
                        <p class="mb-0 text-muted">
                            <i class="fas fa-calendar-check me-1"></i>
                            {{ formatDateTime($cliente->updated_at) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Actividad -->
    <div class="row mt-4">
        <!-- Reservas Recientes -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-calendar-check me-2 text-primary"></i>
                        Reservas Recientes
                    </h5>
                </div>
                <div class="card-body">
                    @if($reservas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Apartamento</th>
                                        <th>Precio</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservas->take(5) as $reserva)
                                        <tr>
                                            <td>
                                                <small class="text-muted">{{ formatDate($reserva->created_at) }}</small>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $reserva->fecha_entrada ? formatDate($reserva->fecha_entrada) : 'N/A' }} - 
                                                    {{ $reserva->fecha_salida ? formatDate($reserva->fecha_salida) : 'N/A' }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="text-primary fw-bold">
                                                    {{ $reserva->apartamento->nombre ?? 'N/A' }}
                                                </span>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $reserva->numero_personas ?? 'N/A' }} personas
                                                </small>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success">
                                                    €{{ number_format($reserva->precio, 2, ',', '.') }}
                                                </span>
                                                @if($reserva->neto && $reserva->neto != $reserva->precio)
                                                    <br>
                                                    <small class="text-muted">
                                                        Neto: €{{ number_format($reserva->neto, 2, ',', '.') }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $estadoColor = 'secondary';
                                                    $estadoText = 'Desconocido';
                                                    if($reserva->estado_id == 1) {
                                                        $estadoColor = 'warning';
                                                        $estadoText = 'Pendiente';
                                                    } elseif($reserva->estado_id == 2) {
                                                        $estadoColor = 'info';
                                                        $estadoText = 'Confirmada';
                                                    } elseif($reserva->estado_id == 3) {
                                                        $estadoColor = 'primary';
                                                        $estadoText = 'En Curso';
                                                    } elseif($reserva->estado_id == 4) {
                                                        $estadoColor = 'success';
                                                        $estadoText = 'Completada';
                                                    } elseif($reserva->estado_id == 5) {
                                                        $estadoColor = 'danger';
                                                        $estadoText = 'Cancelada';
                                                    }
                                                @endphp
                                                <span class="badge bg-{{ $estadoColor }}-subtle text-{{ $estadoColor }} px-2 py-1">
                                                    {{ $estadoText }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-outline-info btn-sm" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($reservas->count() > 5)
                            <div class="text-center mt-3">
                                <small class="text-muted">Mostrando 5 de {{ $reservas->count() }} reservas</small>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No hay reservas registradas</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Mensajes Recientes -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        Mensajes Recientes
                    </h5>
                </div>
                <div class="card-body">
                    @if($mensajes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mensajes->take(5) as $mensaje)
                                        <tr>
                                            <td>{{ $mensaje->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info px-2 py-1">
                                                    {{ ucfirst($mensaje->tipo ?? 'General') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $mensaje->enviado ? 'success' : 'warning' }}-subtle text-{{ $mensaje->enviado ? 'success' : 'warning' }} px-2 py-1">
                                                    {{ $mensaje->enviado ? 'Enviado' : 'Pendiente' }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-outline-info btn-sm" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($mensajes->count() > 5)
                            <div class="text-center mt-3">
                                <small class="text-muted">Mostrando 5 de {{ $mensajes->count() }} mensajes</small>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-envelope-open fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No hay mensajes registrados</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Fotos del Cliente -->
    @if($photos->count() > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-camera me-2 text-primary"></i>
                            Fotos del Cliente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($photos->take(6) as $photo)
                                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                                    <div class="photo-item text-center">
                                        <div class="photo-thumbnail mb-2">
                                            <a href="{{ asset($photo->url) }}" data-fancybox="gallery" data-caption="Foto del cliente - {{ $photo->created_at->format('d/m/Y H:i') }}">
                                                <img src="{{ asset($photo->url) }}" 
                                                     alt="Foto del cliente" 
                                                     class="img-fluid rounded"
                                                     style="max-width: 100px; height: 100px; object-fit: cover; cursor: pointer;">
                                            </a>
                                        </div>
                                        <small class="text-muted d-block">
                                            {{ $photo->created_at->format('d/m/Y') }}
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($photos->count() > 6)
                            <div class="text-center mt-3">
                                <small class="text-muted">Mostrando 6 de {{ $photos->count() }} fotos</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // SweetAlert para mensajes de sesión
    @if(session('swal_success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('swal_success') }}',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Aceptar'
        });
    @endif

    @if(session('swal_error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('swal_error') }}',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Aceptar'
        });
    @endif
});

function confirmarEliminacion() {
    return Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción inactivará al cliente. ¿Deseas continuar?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, inactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        return result.isConfirmed;
    });
}

// Gráfico de evolución temporal de reservas
@if($reservas->count() > 0)
document.addEventListener('DOMContentLoaded', function() {
    console.log('Iniciando gráfico de evolución temporal...');
    
    const canvas = document.getElementById('evolucionReservas');
    if (!canvas) {
        console.error('Canvas no encontrado');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('Contexto 2D no disponible');
        return;
    }
    
    // Preparar datos para el gráfico
    const reservasOrdenadas = @json($reservas->sortBy('created_at')->values());
    console.log('Reservas ordenadas:', reservasOrdenadas);
    
    const labels = reservasOrdenadas.map(r => {
        const fecha = new Date(r.created_at);
        return fecha.toLocaleDateString('es-ES', { month: 'short', year: '2-digit' });
    });
    const precios = reservasOrdenadas.map(r => r.precio);
    
    console.log('Labels:', labels);
    console.log('Precios:', precios);
    
    try {
        new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Precio de Reserva (€)',
                data: precios,
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
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#36a2eb',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Precio: €' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        drawBorder: false
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
            },
            elements: {
                point: {
                    hoverBackgroundColor: '#36a2eb'
                }
            }
        }
    });
    } catch (error) {
        console.error('Error creando el gráfico:', error);
        console.error('Stack trace:', error.stack);
    }
});
@endif

// Inicializar Fancybox para el lightbox de fotos
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si Fancybox está disponible
    if (typeof Fancybox !== 'undefined') {
        Fancybox.bind("[data-fancybox]", {
            // Configuración personalizada
            Toolbar: {
                display: {
                    left: ["infobar"],
                    middle: ["zoomIn", "zoomOut", "toggle1to1", "rotateCCW", "rotateCW", "flipX", "flipY"],
                    right: ["slideshow", "thumbs", "close"]
                }
            },
            Thumbs: {
                autoStart: false,
                hideOnClose: true
            },
            Images: {
                zoom: true
            },
            // Traducción al español
            l10n: {
                CLOSE: "Cerrar",
                NEXT: "Siguiente",
                PREV: "Anterior",
                MODAL: "Puedes cerrar esta ventana con la tecla ESC",
                ERROR: "Algo salió mal. Por favor, inténtalo de nuevo más tarde.",
                IMAGE_ERROR: "Imagen no encontrada",
                ELEMENT_NOT_FOUND: "Elemento HTML no encontrado",
                AJAX_NOT_FOUND: "Error al cargar AJAX: No encontrado",
                AJAX_FORBIDDEN: "Error al cargar AJAX: Prohibido",
                IFRAME_ERROR: "Error al cargar la página",
                TOGGLE_ZOOM: "Alternar zoom",
                TOGGLE_THUMBS: "Alternar miniaturas",
                TOGGLE_SLIDESHOW: "Alternar presentación",
                TOGGLE_FULLSCREEN: "Alternar pantalla completa",
                DOWNLOAD: "Descargar"
            }
        });
    } else {
        console.warn('Fancybox no está cargado. Asegúrate de incluir la librería Fancybox.');
    }
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    border-radius: 12px 12px 0 0 !important;
}

.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 50%;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e3e6f0;
}

.table td {
    border-bottom: 1px solid #f8f9fa;
    vertical-align: middle;
}

.photo-thumbnail {
    transition: transform 0.2s ease-in-out;
}

.photo-thumbnail:hover {
    transform: scale(1.05);
}

.photo-thumbnail a {
    display: block;
    transition: all 0.3s ease;
}

.photo-thumbnail a:hover {
    opacity: 0.8;
}

/* Animaciones */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
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
    
    .photo-item {
        margin-bottom: 1rem;
    }
    
    /* Estilos para el gráfico */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        margin: 0 auto;
    }
    
    .chart-container canvas {
        max-height: 100%;
        max-width: 100%;
    }
}
</style>
@endsection
