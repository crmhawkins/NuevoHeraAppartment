@extends('layouts.appAdmin')

@section('title', 'Configuración')

@section('content')
<style>
    /* Estilos Apple para Configuración */
    .config-container {
        padding: 24px;
        background: #F2F2F7;
        min-height: calc(100vh - 80px);
    }

    .config-header {
        margin-bottom: 32px;
    }

    .config-header h1 {
        font-size: 34px;
        font-weight: 700;
        color: #1D1D1F;
        margin-bottom: 8px;
        letter-spacing: -0.01em;
    }

    .config-header p {
        font-size: 15px;
        color: #8E8E93;
        margin: 0;
    }

    /* Tabs Modernos */
    .nav-pills {
        background: #FFFFFF;
        border-radius: 12px;
        padding: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }

    .nav-pills .nav-item {
        flex: 1;
        min-width: 140px;
    }

    .nav-pills .nav-link {
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 500;
        color: #8E8E93;
        background: transparent;
        border: none;
        transition: all 0.2s ease;
        text-align: center;
        white-space: nowrap;
    }

    .nav-pills .nav-link:hover {
        color: #007AFF;
        background: rgba(0, 122, 255, 0.1);
    }

    .nav-pills .nav-link.active {
        color: #FFFFFF;
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
    }

    /* Tab Content */
    .tab-content {
        background: transparent;
        padding: 0;
    }

    .tab-pane {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Cards */
    .config-card {
        background: #FFFFFF;
        border-radius: 16px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 24px;
        overflow: hidden;
    }

    .config-card-header {
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
        border-bottom: 1px solid #E5E5EA;
        padding: 20px 24px;
    }

    .config-card-header h5 {
        font-size: 18px;
        font-weight: 600;
        color: #1D1D1F;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .config-card-header i {
        color: #007AFF;
        font-size: 20px;
    }

    .config-card-body {
        padding: 24px;
    }

    /* Formularios */
    .form-label {
        font-weight: 600;
        color: #1D1D1F;
        margin-bottom: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-label i {
        color: #007AFF;
        font-size: 16px;
    }

    .form-control,
    .form-select {
        border: 1px solid #E5E5EA;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 15px;
        background: #FFFFFF;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        outline: none;
        border-color: #007AFF;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
        transform: translateY(-1px);
    }

    .form-text {
        font-size: 13px;
        color: #8E8E93;
        margin-top: 4px;
    }

    /* Botones */
    .btn-primary {
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        border: none;
        border-radius: 12px;
        padding: 16px 24px;
        font-size: 17px;
        font-weight: 600;
        color: #FFFFFF;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 122, 255, 0.4);
        color: #FFFFFF;
    }

    .btn-secondary {
        background: #F2F2F7;
        border: 1px solid #E5E5EA;
        border-radius: 12px;
        padding: 16px 24px;
        font-size: 17px;
        font-weight: 600;
        color: #8E8E93;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: #E5E5EA;
        color: #1D1D1F;
    }

    .btn-danger {
        background: linear-gradient(135deg, #FF3B30 0%, #D70015 100%);
        border: none;
        border-radius: 12px;
        padding: 12px 20px;
        font-size: 15px;
        font-weight: 600;
        color: #FFFFFF;
        transition: all 0.3s ease;
    }

    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3);
        color: #FFFFFF;
    }

    /* Lista de Items */
    .list-item-card {
        background: #FFFFFF;
        border: 1px solid #E5E5EA;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.2s ease;
    }

    .list-item-card:hover {
        border-color: #007AFF;
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.1);
        transform: translateY(-2px);
    }

    /* Prompt Editor */
    .prompt-editor {
        border: 1px solid #E5E5EA;
        border-radius: 12px;
        padding: 16px;
        font-family: 'SF Mono', Monaco, monospace;
        font-size: 14px;
        line-height: 1.6;
        background: #F2F2F7;
        resize: vertical;
        min-height: 300px;
        transition: all 0.3s ease;
    }

    .prompt-editor:focus {
        outline: none;
        border-color: #007AFF;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
        background: #FFFFFF;
    }

    .prompt-preview {
        background: #FFFFFF;
        border: 1px solid #E5E5EA;
        border-radius: 12px;
        padding: 24px;
        min-height: 200px;
        margin-top: 16px;
    }

    .prompt-preview h1,
    .prompt-preview h2,
    .prompt-preview h3 {
        color: #1D1D1F;
        margin-top: 0;
    }

    .prompt-preview p {
        color: #1D1D1F;
        line-height: 1.6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-pills .nav-item {
            min-width: 100%;
        }
        
        .config-card-body {
            padding: 16px;
        }
    }
</style>

<div class="config-container">
    <!-- Header -->
    <div class="config-header">
        <h1>
            <i class="fas fa-cog me-2" style="color: #007AFF;"></i>
            Configuración
        </h1>
        <p>Gestiona la configuración del sistema y personaliza los ajustes según tus necesidades</p>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-user-tab" data-bs-toggle="pill" data-bs-target="#pills-user" type="button" role="tab">
                <i class="fas fa-key me-2"></i>Credenciales
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-contabilidad-tab" data-bs-toggle="pill" data-bs-target="#pills-contabilidad" type="button" role="tab">
                <i class="fas fa-calculator me-2"></i>Contabilidad
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" data-bs-target="#pills-contact" type="button" role="tab">
                <i class="fas fa-tools me-2"></i>Reparaciones
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-limpiadoras-tab" data-bs-toggle="pill" data-bs-target="#pills-limpiadoras" type="button" role="tab">
                <i class="fas fa-broom me-2"></i>Limpiadoras
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-disabled-tab" data-bs-toggle="pill" data-bs-target="#pills-disabled" type="button" role="tab">
                <i class="fas fa-bell me-2"></i>Notificaciones
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-prompt-tab" data-bs-toggle="pill" data-bs-target="#pills-prompt" type="button" role="tab">
                <i class="fas fa-robot me-2"></i>Prompt IA
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-estado-tab" data-bs-toggle="pill" data-bs-target="#pills-estado" type="button" role="tab">
                <i class="fas fa-building me-2"></i>Plataforma Estado
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-mir-tab" data-bs-toggle="pill" data-bs-target="#pills-mir" type="button" role="tab">
                <i class="fas fa-shield-alt me-2"></i>MIR Hospedajes
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-portal-tab" data-bs-toggle="pill" data-bs-target="#pills-portal" type="button" role="tab">
                <i class="fas fa-globe me-2"></i>Portal Público
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-acceso-tab" data-bs-toggle="pill" data-bs-target="#pills-acceso" type="button" role="tab">
                <i class="fas fa-door-open me-2"></i>Acceso
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="pills-tabContent">
        <!-- Tab: Credenciales Usuarios -->
        <div class="tab-pane fade" id="pills-user" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header">
                    <h5>
                        <i class="fas fa-key"></i>
                        Credenciales de Usuario - Booking y Airbnb
                    </h5>
                </div>
                <div class="config-card-body">
                    <form action="{{route('configuracion.update', $configuraciones[0]->id)}}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Usuario Booking
                                </label>
                                <input class="form-control" name="user_booking" value="{{$configuraciones[0]->user_booking}}" placeholder="Usuario de Booking.com"/>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Contraseña Booking
                                </label>
                                <input type="password" class="form-control" name="password_booking" value="{{$configuraciones[0]->password_booking}}" placeholder="Contraseña de Booking.com"/>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Usuario Airbnb
                                </label>
                                <input class="form-control" name="user_airbnb" value="{{$configuraciones[0]->user_airbnb}}" placeholder="Usuario de Airbnb"/>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Contraseña Airbnb
                                </label>
                                <input type="password" class="form-control" name="password_airbnb" value="{{$configuraciones[0]->password_airbnb}}" placeholder="Contraseña de Airbnb"/>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Actualizar Credenciales
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab: Contabilidad y Gestión -->
        <div class="tab-pane fade" id="pills-contabilidad" role="tabpanel">
            <!-- Saldo Inicial -->
            <div class="config-card">
                <div class="config-card-header">
                    <h5>
                        <i class="fas fa-money-bill-wave"></i>
                        Saldo Inicial
                    </h5>
                </div>
                <div class="config-card-body">
                    <form action="{{route('configuracion.saldoInicial')}}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-euro-sign"></i>
                                    Saldo Inicial
                                </label>
                                <input type="text" name="saldo_inicial" id="saldo_inicial" class="form-control" value="{{$saldo->saldo_inicial}}" placeholder="0.00"/>
                                <small class="form-text">Saldo inicial del año contable actual</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Saldo Inicial
                        </button>
                    </form>
                </div>
            </div>

            <!-- Año de Gestión -->
            <div class="config-card">
                <div class="config-card-header">
                    <h5>
                        <i class="fas fa-calendar-alt"></i>
                        Año de Gestión
                    </h5>
                </div>
                <div class="config-card-body">
                    <form action="{{route('configuracion.updateAnio')}}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-calendar"></i>
                                    Año de Gestión
                                </label>
                                <select name="anio" id="anio" class="form-select">
                                    <option value="">Selecciona año</option>
                                    @foreach ($anios as $item)
                                        <option @if($item == $anio) selected @endif value="{{$item}}">{{$item}}</option>
                                    @endforeach
                                </select>
                                <small class="form-text">Selecciona el año contable activo</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Año
                        </button>
                    </form>
                </div>
            </div>

            <!-- Formas de Pago -->
            <div class="config-card">
                <div class="config-card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-credit-card"></i>
                        Formas de Pago
                    </h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createForma">
                        <i class="fas fa-plus me-2"></i>Crear Método
                    </button>
                </div>
                <div class="config-card-body">
                    @if (count($formasPago) > 0)
                        <div class="list-group">
                            @foreach ($formasPago as $forma)
                                <div class="list-item-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <input id="input_formas" data-id="{{$forma->id}}" type="text" value="{{$forma->nombre}}" class="form-control me-3" style="flex: 1;"/>
                                        <button id="delete_btn" data-id="{{$forma->id}}" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-credit-card" style="font-size: 48px; color: #C7C7CC; margin-bottom: 16px;"></i>
                            <p class="text-muted">No hay métodos de pago configurados</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createForma">
                                <i class="fas fa-plus me-2"></i>Crear Primer Método
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Crear Forma de Pago -->
            <div class="modal fade" id="createForma" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content" style="border-radius: 16px; border: none;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%); border-bottom: 1px solid #E5E5EA; border-radius: 16px 16px 0 0;">
                            <h5 class="modal-title fw-semibold">
                                <i class="fas fa-credit-card me-2" style="color: #007AFF;"></i>
                                Crear Forma de Pago
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{route('formaPago.store')}}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-tag"></i>
                                        Nombre de la Forma de Pago
                                    </label>
                                    <input type="text" name="nombre" class="form-control" required placeholder="Ej: Tarjeta de Crédito"/>
                                </div>
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Crear
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Reparaciones -->
        <div class="tab-pane fade" id="pills-contact" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-tools"></i>
                        Reparaciones (Técnicos)
                    </h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTecnico">
                        <i class="fas fa-plus me-2"></i>Añadir Técnico
                    </button>
                </div>
                <div class="config-card-body">
                    @if (count($reparaciones) > 0)
                        @foreach ($reparaciones as $reparacion)
                            <div class="list-item-card mb-3">
                                <form action="{{route('configuracion.updateReparaciones',$reparacion->id )}}" method="POST">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-user"></i>
                                                Nombre
                                            </label>
                                            <input class="form-control" name="nombre" value="@isset($reparacion->nombre){{$reparacion->nombre}}@endisset"/>
                                        </div>
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-phone"></i>
                                                Teléfono
                                            </label>
                                            <input class="form-control" name="telefono" value="@isset($reparacion->telefono){{$reparacion->telefono}}@endisset"/>
                                        </div>
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-clock"></i>
                                                Hora Inicio
                                            </label>
                                            <select class="form-select" name="hora_inicio">
                                                @for ($hour = 0; $hour < 24; $hour++)
                                                    @php
                                                        $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                        $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                    @endphp
                                                    <option value="{{ $hora1 }}" @isset($reparacion->hora_inicio) @if($reparacion->hora_inicio == $hora1) selected @endif @endisset>{{ $hora1 }}</option>
                                                    <option value="{{ $hora2 }}" @isset($reparacion->hora_inicio) @if($reparacion->hora_inicio == $hora2) selected @endif @endisset>{{ $hora2 }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-clock"></i>
                                                Hora Fin
                                            </label>
                                            <select class="form-select" name="hora_fin">
                                                @for ($hour = 0; $hour < 24; $hour++)
                                                    @php
                                                        $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                        $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                    @endphp
                                                    <option value="{{ $hora1 }}" @isset($reparacion->hora_fin) @if($reparacion->hora_fin == $hora1) selected @endif @endisset>{{ $hora1 }}</option>
                                                    <option value="{{ $hora2 }}" @isset($reparacion->hora_fin) @if($reparacion->hora_fin == $hora2) selected @endif @endisset>{{ $hora2 }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-12">
                                            <label class="form-label">
                                                <i class="fas fa-calendar-week"></i>
                                                Días
                                            </label>
                                            <div class="d-flex flex-wrap gap-2">
                                                @php
                                                    $dias = ['lunes' => 'L', 'martes' => 'M', 'miercoles' => 'X', 'jueves' => 'J', 'viernes' => 'V', 'sabado' => 'S', 'domingo' => 'D'];
                                                @endphp
                                                @foreach($dias as $diaKey => $diaLabel)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="{{$diaKey}}" id="{{$diaKey}}_{{$reparacion->id}}" value="{{$loop->iteration}}" @if($reparacion->$diaKey == true) checked @endif>
                                                        <label class="form-check-label" for="{{$diaKey}}_{{$reparacion->id}}">{{$diaLabel}}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-12">
                                            <label class="form-label d-block">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                                                <i class="fas fa-save me-1"></i>Actualizar
                                            </button>
                                            <button data-id="{{$reparacion->id}}" id="eliminarTecnico" type="button" class="btn btn-danger btn-sm w-100">
                                                <i class="fas fa-trash me-1"></i>Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tools" style="font-size: 48px; color: #C7C7CC; margin-bottom: 16px;"></i>
                            <p class="text-muted">No hay técnicos de reparación configurados</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTecnico">
                                <i class="fas fa-plus me-2"></i>Añadir Primer Técnico
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Añadir Técnico -->
            <div class="modal fade" id="addTecnico" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="border-radius: 16px; border: none;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%); border-bottom: 1px solid #E5E5EA; border-radius: 16px 16px 0 0;">
                            <h5 class="modal-title fw-semibold">
                                <i class="fas fa-user-plus me-2" style="color: #007AFF;"></i>
                                Añadir Técnico
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="{{route('configuracion.storeReparaciones')}}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i>
                                            Nombre
                                        </label>
                                        <input type="text" class="form-control" name="nombre" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="fas fa-phone"></i>
                                            Teléfono
                                        </label>
                                        <input type="text" class="form-control" name="telefono" placeholder="34600600600">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="fas fa-clock"></i>
                                            Hora Inicio
                                        </label>
                                        <select class="form-select" name="hora_inicio" required>
                                            @for ($hour = 0; $hour < 24; $hour++)
                                                @php
                                                    $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                    $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                @endphp
                                                <option value="{{ $hora1 }}">{{ $hora1 }}</option>
                                                <option value="{{ $hora2 }}">{{ $hora2 }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="fas fa-clock"></i>
                                            Hora Fin
                                        </label>
                                        <select class="form-select" name="hora_fin" required>
                                            @for ($hour = 0; $hour < 24; $hour++)
                                                @php
                                                    $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                    $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                @endphp
                                                <option value="{{ $hora1 }}">{{ $hora1 }}</option>
                                                <option value="{{ $hora2 }}">{{ $hora2 }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-week"></i>
                                            Días Disponibles
                                        </label>
                                        <div class="d-flex flex-wrap gap-3">
                                            @php
                                                $dias = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo'];
                                            @endphp
                                            @foreach($dias as $diaKey => $diaLabel)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="{{$diaKey}}" id="new_{{$diaKey}}" value="{{$loop->iteration}}">
                                                    <label class="form-check-label" for="new_{{$diaKey}}">{{$diaLabel}}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 justify-content-end mt-4">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Añadir Técnico
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Limpiadoras Guardia -->
        <div class="tab-pane fade show active" id="pills-limpiadoras" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-broom"></i>
                        Limpiadoras de Guardia
                    </h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLimpiadora">
                        <i class="fas fa-plus me-2"></i>Añadir Limpiadora
                    </button>
                </div>
                <div class="config-card-body">
                    @if (count($limpiadorasGuardia) > 0)
                        @foreach ($limpiadorasGuardia as $limpiadora)
                            <div class="list-item-card mb-3">
                                <form action="{{route('configuracion.updateLimpiadora',$limpiadora->id )}}" method="POST">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-user"></i>
                                                Nombre
                                            </label>
                                            <input disabled class="form-control" value="{{ $limpiadora->usuario->name ?? 'Usuario no encontrado' }}"/>
                                        </div>
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-phone"></i>
                                                Teléfono
                                            </label>
                                            <input class="form-control" name="telefono" value="@isset($limpiadora->telefono){{$limpiadora->telefono}}@endisset"/>
                                        </div>
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-clock"></i>
                                                Hora Inicio
                                            </label>
                                            <select class="form-select" name="hora_inicio">
                                                @for ($hour = 0; $hour < 24; $hour++)
                                                    @php
                                                        $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                        $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                    @endphp
                                                    <option value="{{ $hora1 }}"{{ isset($limpiadora->hora_inicio) && $limpiadora->hora_inicio == $hora1 ? ' selected' : '' }}>{{ $hora1 }}</option>
                                                    <option value="{{ $hora2 }}"{{ isset($limpiadora->hora_inicio) && $limpiadora->hora_inicio == $hora2 ? ' selected' : '' }}>{{ $hora2 }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-clock"></i>
                                                Hora Fin
                                            </label>
                                            <select class="form-select" name="hora_fin">
                                                @for ($hour = 0; $hour < 24; $hour++)
                                                    @php
                                                        $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                        $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                    @endphp
                                                    <option value="{{ $hora1 }}"{{ isset($limpiadora->hora_fin) && $limpiadora->hora_fin == $hora1 ? ' selected' : '' }}>{{ $hora1 }}</option>
                                                    <option value="{{ $hora2 }}"{{ isset($limpiadora->hora_fin) && $limpiadora->hora_fin == $hora2 ? ' selected' : '' }}>{{ $hora2 }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-12">
                                            <label class="form-label">
                                                <i class="fas fa-calendar-week"></i>
                                                Días
                                            </label>
                                            <div class="d-flex flex-wrap gap-2">
                                                @php
                                                    $dias = ['lunes' => 'L', 'martes' => 'M', 'miercoles' => 'X', 'jueves' => 'J', 'viernes' => 'V', 'sabado' => 'S', 'domingo' => 'D'];
                                                @endphp
                                                @foreach($dias as $diaKey => $diaLabel)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="{{$diaKey}}" id="lim_{{$diaKey}}_{{$limpiadora->id}}" value="{{$loop->iteration}}" @if($limpiadora->$diaKey == true) checked @endif>
                                                        <label class="form-check-label" for="lim_{{$diaKey}}_{{$limpiadora->id}}">{{$diaLabel}}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-12">
                                            <label class="form-label d-block">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                                                <i class="fas fa-save me-1"></i>Actualizar
                                            </button>
                                            <button data-id="{{$limpiadora->id}}" id="eliminarLimpiadora" type="button" class="btn btn-danger btn-sm w-100">
                                                <i class="fas fa-trash me-1"></i>Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-broom" style="font-size: 48px; color: #C7C7CC; margin-bottom: 16px;"></i>
                            <p class="text-muted">No hay limpiadoras de guardia configuradas</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLimpiadora">
                                <i class="fas fa-plus me-2"></i>Añadir Primera Limpiadora
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Añadir Limpiadora -->
            <div class="modal fade" id="addLimpiadora" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="border-radius: 16px; border: none;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%); border-bottom: 1px solid #E5E5EA; border-radius: 16px 16px 0 0;">
                            <h5 class="modal-title fw-semibold">
                                <i class="fas fa-user-plus me-2" style="color: #007AFF;"></i>
                                Añadir Limpiadora de Guardia
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="{{route('configuracion.storeLimpiadora')}}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i>
                                            Limpiadora
                                        </label>
                                        <select name="user_id" class="form-select" required>
                                            @if (count($limpiadorasUsers) > 0)
                                                @foreach ($limpiadorasUsers as $item)
                                                    <option value="{{$item->id}}">{{$item->name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="fas fa-phone"></i>
                                            Teléfono
                                        </label>
                                        <input type="text" class="form-control" name="telefono" placeholder="34600600600">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="fas fa-clock"></i>
                                            Hora Inicio
                                        </label>
                                        <select class="form-select" name="hora_inicio" required>
                                            @for ($hour = 0; $hour < 24; $hour++)
                                                @php
                                                    $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                    $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                @endphp
                                                <option value="{{ $hora1 }}">{{ $hora1 }}</option>
                                                <option value="{{ $hora2 }}">{{ $hora2 }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="fas fa-clock"></i>
                                            Hora Fin
                                        </label>
                                        <select class="form-select" name="hora_fin" required>
                                            @for ($hour = 0; $hour < 24; $hour++)
                                                @php
                                                    $hora1 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                    $hora2 = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                                                @endphp
                                                <option value="{{ $hora1 }}">{{ $hora1 }}</option>
                                                <option value="{{ $hora2 }}">{{ $hora2 }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-week"></i>
                                            Días Disponibles
                                        </label>
                                        <div class="d-flex flex-wrap gap-3">
                                            @php
                                                $dias = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo'];
                                            @endphp
                                            @foreach($dias as $diaKey => $diaLabel)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="{{$diaKey}}" id="lim_new_{{$diaKey}}" value="{{$loop->iteration}}">
                                                    <label class="form-check-label" for="lim_new_{{$diaKey}}">{{$diaLabel}}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 justify-content-end mt-4">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Añadir Limpiadora
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Notificaciones -->
        <div class="tab-pane fade" id="pills-disabled" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-bell"></i>
                        Notificaciones
                    </h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmailModal">
                        <i class="fas fa-plus me-2"></i>Añadir Persona
                    </button>
                </div>
                <div class="config-card-body">
                    @if (count($emailsNotificaciones) > 0)
                        @foreach ($emailsNotificaciones as $person)
                            <div class="list-item-card mb-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i>
                                            Nombre
                                        </label>
                                        <input data-id="{{$person->id}}" id="input_persona" class="form-control" name="nombre" type="text" value="{{$person->nombre}}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            <i class="fas fa-envelope"></i>
                                            Email
                                        </label>
                                        <input data-id="{{$person->id}}" id="input_persona" class="form-control" name="email" type="email" value="{{$person->email}}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">
                                            <i class="fas fa-phone"></i>
                                            Teléfono
                                        </label>
                                        <input data-id="{{$person->id}}" id="input_persona" class="form-control" name="telefono" type="text" value="{{$person->telefono}}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label d-block">&nbsp;</label>
                                        <button id="deletePerson" data-id="{{$person->id}}" class="btn btn-danger btn-sm w-100">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-bell" style="font-size: 48px; color: #C7C7CC; margin-bottom: 16px;"></i>
                            <p class="text-muted">No hay personas configuradas para recibir notificaciones</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmailModal">
                                <i class="fas fa-plus me-2"></i>Añadir Primera Persona
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Añadir Email -->
            <div class="modal fade" id="addEmailModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content" style="border-radius: 16px; border: none;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%); border-bottom: 1px solid #E5E5EA; border-radius: 16px 16px 0 0;">
                            <h5 class="modal-title fw-semibold">
                                <i class="fas fa-user-plus me-2" style="color: #007AFF;"></i>
                                Añadir Persona para Notificaciones
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="{{route('configuracion.emails.add')}}">
                                @csrf
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i>
                                        Dirección de Email
                                    </label>
                                    <input type="email" class="form-control" id="emailAddress" name="email" required placeholder="ejemplo@email.com">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i>
                                        Nombre
                                    </label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Nombre completo">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i>
                                        Teléfono
                                    </label>
                                    <input type="text" class="form-control" id="telefono" name="telefono" placeholder="34600600600">
                                </div>
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button id="addEmailForm" type="button" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Añadir
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Prompt Asistente -->
        <div class="tab-pane fade" id="pills-prompt" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header">
                    <h5>
                        <i class="fas fa-robot"></i>
                        Prompt - Asistente de la Inteligencia Artificial
                    </h5>
                </div>
                <div class="config-card-body">
                    <form action="{{route('configuracion.actualizarPrompt')}}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-code"></i>
                                Editar Prompt (Markdown)
                            </label>
                            <textarea 
                                name="prompt" 
                                id="prompt" 
                                class="prompt-editor"
                                rows="15"
                                placeholder="Escribe aquí el prompt del asistente...">@if (count($prompt) > 0){{ $prompt[0]->prompt }}@endif</textarea>
                            <small class="form-text">Puedes usar formato Markdown para formatear el texto</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Prompt
                        </button>
                    </form>

                    <hr class="my-4" style="border-color: #E5E5EA;">

                    <div class="mb-3">
                        <h6 class="fw-semibold d-flex align-items-center gap-2">
                            <i class="fas fa-eye" style="color: #007AFF;"></i>
                            Vista Previa (formato Markdown)
                        </h6>
                        <small class="text-muted">Actualiza el prompt para ver la vista previa</small>
                    </div>
                    <div class="prompt-preview" id="promptPreview">
                        {!! \Illuminate\Support\Str::markdown(count($prompt) > 0 ? $prompt[0]->prompt : 'No hay contenido para mostrar. Edita el prompt arriba y guarda para ver la vista previa.') !!}
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Plataforma Estado -->
        <div class="tab-pane fade" id="pills-estado" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header">
                    <h5>
                        <i class="fas fa-building"></i>
                        Configuración Plataforma del Estado
                    </h5>
                </div>
                <div class="config-card-body">
                    <p class="text-muted mb-4">Configuración para la subida de viajeros a la plataforma del estado español.</p>
                    
                    <form action="{{ route('configuracion.updateEstado') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-barcode"></i>
                                    Código del Arrendador
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="codigo_arrendador" 
                                       value="{{ \App\Models\Setting::get('codigo_arrendador') }}"
                                       placeholder="Código asignado por la administración">
                                <small class="form-text">Código único asignado por la administración pública</small>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-tag"></i>
                                    Nombre de la Aplicación
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="aplicacion" 
                                       value="{{ \App\Models\Setting::get('aplicacion', 'HAWKINS_SUITES') }}"
                                       placeholder="HAWKINS_SUITES">
                                <small class="form-text">Identificador de la aplicación en la plataforma</small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-key"></i>
                                Credenciales de Acceso (JSON)
                            </label>
                            <textarea class="form-control" 
                                      name="credenciales" 
                                      rows="4"
                                      style="font-family: 'SF Mono', Monaco, monospace;"
                                      placeholder='{"usuario": "tu_usuario", "password": "tu_password", "endpoint": "https://api.ejemplo.com"}'>{{ \App\Models\Setting::get('credenciales') }}</textarea>
                            <small class="form-text">Credenciales en formato JSON para la conexión con la plataforma</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-certificate"></i>
                                Ruta del Certificado CA
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="ca_path" 
                                   value="{{ \App\Models\Setting::get('ca_path') }}"
                                   placeholder="/path/to/certificate.pem">
                            <small class="form-text">Ruta al certificado CA para conexiones seguras</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Configuración
                            </button>
                            <button type="button" class="btn btn-secondary" id="testConnection">
                                <i class="fas fa-plug me-2"></i>Probar Conexión
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Tab: MIR Hospedajes -->
        <div class="tab-pane fade" id="pills-mir" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header">
                    <h5>
                        <i class="fas fa-shield-alt"></i>
                        Configuración API MIR - Servicio de Hospedajes
                    </h5>
                </div>
                <div class="config-card-body">
                    <p class="text-muted mb-4">Configuración para el envío de reservas al Servicio Web del Ministerio del Interior (MIR) según el Real Decreto 933/2021.</p>
                    
                    <form action="{{ route('configuracion.mir.update') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-barcode"></i>
                                    Código Arrendador
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="mir_arrendador" 
                                       value="{{ \App\Models\Setting::get('mir_arrendador') }}"
                                       placeholder="Código asignado por el Sistema de Hospedajes">
                                <small class="form-text">Código único asignado al registrarte en la Sede Electrónica del Ministerio del Interior</small>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-tag"></i>
                                    Nombre de la Aplicación
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="mir_aplicacion" 
                                       value="{{ \App\Models\Setting::get('mir_aplicacion', 'Hawkins Suite') }}"
                                       placeholder="Hawkins Suite">
                                <small class="form-text">Nombre de tu sistema para identificación en MIR</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Usuario
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="mir_usuario" 
                                       value="{{ \App\Models\Setting::get('mir_usuario') }}"
                                       placeholder="Usuario de la Sede Electrónica">
                                <small class="form-text">Usuario obtenido tras registrarte en la Sede Electrónica del Ministerio del Interior</small>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Contraseña
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       name="mir_password" 
                                       value="{{ \App\Models\Setting::get('mir_password') }}"
                                       placeholder="Contraseña">
                                <small class="form-text">Contraseña de acceso a la API MIR</small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-server"></i>
                                Entorno
                            </label>
                            <select class="form-select" name="mir_entorno">
                                <option value="sandbox" {{ \App\Models\Setting::get('mir_entorno', 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                                    Pruebas (Sandbox)
                                </option>
                                <option value="production" {{ \App\Models\Setting::get('mir_entorno') === 'production' ? 'selected' : '' }}>
                                    Producción
                                </option>
                            </select>
                            <small class="form-text">Selecciona el entorno: Sandbox para pruebas o Producción para el entorno real</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Endpoints:</strong><br>
                            <strong>Sandbox:</strong> https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion<br>
                            <strong>Producción:</strong> https://hospedajes.ses.mir.es/hospedajes-web/ws/v1/comunicacion
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Configuración MIR
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Tab: Portal Público -->
        <div class="tab-pane fade" id="pills-portal" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header">
                    <h5>
                        <i class="fas fa-globe"></i>
                        Información del Host - Portal Público
                    </h5>
                </div>
                <div class="config-card-body">
                    <form action="{{ route('configuracion.portal-publico.update') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-building"></i>
                                    Nombre de la Empresa/Host
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="host_nombre" 
                                       value="{{ \App\Models\Setting::get('host_nombre', 'Apartamentos Algeciras') }}"
                                       placeholder="Ej: Apartamentos Algeciras">
                                <small class="form-text">Nombre que aparece en la sección "Gestionado por"</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-font"></i>
                                    Iniciales del Logo
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="host_iniciales" 
                                       value="{{ \App\Models\Setting::get('host_iniciales', 'HA') }}"
                                       placeholder="Ej: HA"
                                       maxlength="4">
                                <small class="form-text">Letras que aparecen en el círculo azul del logo (máx. 4 caracteres)</small>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-align-left"></i>
                                Descripción
                            </label>
                            <textarea class="form-control" 
                                      name="host_descripcion" 
                                      rows="3"
                                      placeholder="Ej: Alojamientos de calidad en el corazón de Algeciras">{{ \App\Models\Setting::get('host_descripcion', 'Alojamientos de calidad en el corazón de Algeciras') }}</textarea>
                            <small class="form-text">Descripción breve que aparece debajo del nombre</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-language"></i>
                                Idiomas que se Hablan
                            </label>
                            <div class="row">
                                @php
                                    $idiomasDisponibles = ['Español', 'Inglés', 'Francés', 'Alemán', 'Italiano', 'Portugués'];
                                    $idiomasSeleccionados = json_decode(\App\Models\Setting::get('host_idiomas', '["Español", "Inglés"]'), true) ?: ['Español', 'Inglés'];
                                @endphp
                                @foreach($idiomasDisponibles as $idioma)
                                    <div class="col-md-4 col-lg-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="host_idiomas[]" 
                                                   value="{{ $idioma }}"
                                                   id="idioma_{{ strtolower(str_replace('é', 'e', $idioma)) }}"
                                                   {{ in_array($idioma, $idiomasSeleccionados) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="idioma_{{ strtolower(str_replace('é', 'e', $idioma)) }}">
                                                {{ $idioma }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="form-text">Selecciona los idiomas que se mostrarán en la sección "Idiomas que habla"</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-star"></i>
                                Puntuación de los Comentarios (Opcional)
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="number" 
                                           class="form-control" 
                                           name="host_rating" 
                                           value="{{ \App\Models\Setting::get('host_rating', '') }}"
                                           step="0.1"
                                           min="0"
                                           max="10"
                                           placeholder="Ej: 7.4">
                                    <small class="form-text">Puntuación promedio (0-10)</small>
                                </div>
                                <div class="col-md-6">
                                    <input type="number" 
                                           class="form-control" 
                                           name="host_reviews_count" 
                                           value="{{ \App\Models\Setting::get('host_reviews_count', '') }}"
                                           min="0"
                                           placeholder="Ej: 1441">
                                    <small class="form-text">Número total de comentarios</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-home"></i>
                                Alojamientos Gestionados (Opcional)
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   name="host_alojamientos_count" 
                                   value="{{ \App\Models\Setting::get('host_alojamientos_count', '') }}"
                                   min="0"
                                   placeholder="Ej: 20">
                            <small class="form-text">Número de alojamientos gestionados</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="btnGuardarPortalPublico">
                                <i class="fas fa-save me-2"></i>Guardar Configuración
                            </button>
                            <a href="{{ route('configuracion.portal-publico.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                    
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const formPortal = document.querySelector('form[action*="update-portal-publico"]');
                        const btnGuardar = document.getElementById('btnGuardarPortalPublico');
                        
                        if (formPortal && btnGuardar) {
                            // Asegurar que el botón envíe el formulario
                            btnGuardar.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                
                                console.log('Botón Guardar Configuración clickeado');
                                console.log('Formulario:', formPortal);
                                console.log('Action:', formPortal.action);
                                console.log('Method:', formPortal.method);
                                
                                // Verificar token CSRF
                                const csrfToken = formPortal.querySelector('input[name="_token"]');
                                if (!csrfToken) {
                                    console.error('Token CSRF no encontrado');
                                    alert('Error: Token CSRF no encontrado. Recarga la página.');
                                    return false;
                                }
                                
                                // Mostrar datos del formulario
                                const formData = new FormData(formPortal);
                                console.log('Datos del formulario:');
                                for (let [key, value] of formData.entries()) {
                                    console.log(key + ':', value);
                                }
                                
                                // Deshabilitar botón para evitar doble envío
                                btnGuardar.disabled = true;
                                btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
                                
                                // Enviar formulario
                                formPortal.submit();
                            });
                            
                            // También manejar submit directo del formulario
                            formPortal.addEventListener('submit', function(e) {
                                console.log('Evento submit del formulario disparado');
                                // No prevenir, dejar que se envíe
                            });
                        } else {
                            console.error('Formulario o botón no encontrado');
                            console.log('formPortal:', formPortal);
                            console.log('btnGuardar:', btnGuardar);
                        }
                    });
                    </script>
                </div>
            </div>
        </div>

        <!-- Tab: Acceso a Apartamentos -->
        <div class="tab-pane fade" id="pills-acceso" role="tabpanel">
            <div class="config-card">
                <div class="config-card-header">
                    <h5>
                        <i class="fas fa-door-open"></i>
                        Método de Entrada por Bloque (Edificio)
                    </h5>
                </div>
                <div class="config-card-body">
                    <p class="text-muted mb-4">
                        Define si cada edificio entrega acceso con cerradura <strong>física</strong> o <strong>digital</strong>.
                        El modo digital queda preparado pero <strong>pendiente de integración</strong> con la plataforma que generará códigos únicos por cliente y ventanas horarias.
                    </p>

                    <form action="{{ route('configuracion.updateMetodoEntrada') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            @foreach(($edificios ?? []) as $edificio)
                                <div class="col-12">
                                    <div class="list-item-card">
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                            <div>
                                                <div class="fw-semibold text-dark">
                                                    {{ $edificio->nombre ?? ('Edificio #' . $edificio->id) }}
                                                </div>
                                                <div class="small text-muted">
                                                    Clave: {{ $edificio->clave ?? '—' }}
                                                </div>
                                            </div>

                                            <div style="min-width: 260px;">
                                                <select class="form-select" name="metodos[{{ $edificio->id }}]">
                                                    @php
                                                        $valor = old('metodos.' . $edificio->id, $edificio->metodo_entrada);
                                                    @endphp
                                                    <option value="" {{ empty($valor) ? 'selected' : '' }}>Cerradura física (por defecto)</option>
                                                    <option value="fisica" {{ $valor === 'fisica' ? 'selected' : '' }}>Cerradura física</option>
                                                    <option value="digital" {{ $valor === 'digital' ? 'selected' : '' }}>Cerradura digital</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Métodos de Entrada
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

@section('scripts')
<script>
    // Activar pestaña MIR si viene con hash
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar si hay hash en la URL
        if (window.location.hash === '#pills-mir') {
            // Activar la pestaña MIR
            const mirTab = document.getElementById('pills-mir-tab');
            const mirPane = document.getElementById('pills-mir');
            
            if (mirTab && mirPane) {
                // Remover active de todas las pestañas
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                // Activar pestaña MIR
                mirTab.classList.add('active');
                mirPane.classList.add('show', 'active');
                
                // Scroll suave a la pestaña
                setTimeout(() => {
                    mirTab.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        }

        if (window.location.hash === '#pills-acceso') {
            const tab = document.getElementById('pills-acceso-tab');
            const pane = document.getElementById('pills-acceso');
            if (tab && pane) {
                document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(paneEl => paneEl.classList.remove('show', 'active'));
                tab.classList.add('active');
                pane.classList.add('show', 'active');
                setTimeout(() => tab.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
            }
        }
        
        // También verificar localStorage
        const savedTab = localStorage.getItem('activeTab');
        if (savedTab === 'pills-mir') {
            localStorage.removeItem('activeTab');
            const mirTab = document.getElementById('pills-mir-tab');
            const mirPane = document.getElementById('pills-mir');
            
            if (mirTab && mirPane) {
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                mirTab.classList.add('active');
                mirPane.classList.add('show', 'active');
                
                setTimeout(() => {
                    mirTab.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        }
    });
document.addEventListener('DOMContentLoaded', function () {
    // Preview del Prompt en tiempo real (actualizar al guardar)
    const promptTextarea = document.getElementById('prompt');
    const promptPreview = document.getElementById('promptPreview');
    
    if (promptTextarea && promptPreview) {
        // Actualizar preview cuando se cambia el contenido (solo al perder el foco para mejor performance)
        promptTextarea.addEventListener('blur', function() {
            const content = this.value || 'No hay contenido para mostrar.';
            // Usar una simple conversión de markdown básico (o mejor, hacerlo en el servidor)
            // Por ahora solo mostramos el texto plano
            fetch('/markdown-preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ content: content })
            }).then(response => response.text())
            .then(html => {
                promptPreview.innerHTML = html;
            }).catch(() => {
                // Fallback: mostrar markdown renderizado básico
                promptPreview.innerHTML = content.replace(/\n/g, '<br>');
            });
        });
    }

    // Verificar SweetAlert2
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        return;
    }

    // Formas de Pago - Actualizar
    const inputsFormasPago = document.querySelectorAll('#input_formas');
    inputsFormasPago.forEach(function(nodo){
        $(nodo).on('change', function(){
            var nuevoValor = this.value;
            var id = $(this).attr('data-id');
            var baseUrl = "{{ route('formaPago.update', ['id' => ':id']) }}";
            var url = baseUrl.replace(':id', id);

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('nombre', nuevoValor);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        },
                        didDestroy: () => {
                            location.reload();
                        }
                    });
                    Toast.fire({
                        icon: "success",
                        title: 'Forma de Pago actualizada correctamente'
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        });
    });

    // Formas de Pago - Eliminar
    const deleteFormasPago = document.querySelectorAll('#delete_btn');
    deleteFormasPago.forEach(function(nodo){
        $(nodo).on('click', function(){
            var id = $(this).attr('data-id');
            var baseUrl = "{{ route('formaPago.delete', ['id' => ':id']) }}";
            var url = baseUrl.replace(':id', id);

            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#007AFF',
                cancelButtonColor: '#8E8E93',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    var formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: "top-end",
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didDestroy: () => {
                                    location.reload();
                                }
                            });
                            Toast.fire({
                                icon: "success",
                                title: 'Forma de Pago eliminada correctamente'
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                        }
                    });
                }
            });
        });
    });

    // Emails - Añadir
    $('#addEmailForm').on('click', function(e) {
        e.preventDefault();
        var formData = {
            email: $('#emailAddress').val(),
            nombre: $('#nombre').val(),
            telefono: $('#telefono').val(),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '{{ route("configuracion.emails.add") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didDestroy: () => {
                        window.location.href = response.redirect_url;
                    }
                });
                Toast.fire({
                    icon: "success",
                    title: 'Persona añadida correctamente'
                });
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo añadir la persona.',
                    icon: 'error',
                    confirmButtonText: 'Cerrar'
                });
            }
        });
    });

    // Emails - Eliminar
    const botonesDeleteUser = document.querySelectorAll('#deletePerson');
    botonesDeleteUser.forEach(function(nodo){
        $(nodo).on('click', function(){
            var id = $(this).attr('data-id');
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#007AFF',
                cancelButtonColor: '#8E8E93',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    var baseUrl = "{{ route('configuracion.emails.delete', ['id' => ':id']) }}";
                    var url = baseUrl.replace(':id', id);
                    var formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: "top-end",
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didDestroy: () => {
                                    location.reload();
                                }
                            });
                            Toast.fire({
                                icon: "success",
                                title: 'Persona eliminada correctamente'
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                        }
                    });
                }
            });
        });
    });

    // Emails - Actualizar
    const inputsPersonasEmails = document.querySelectorAll('#input_persona');
    inputsPersonasEmails.forEach(function(nodo){
        $(nodo).on('change', function(){
            var nuevoValor = this.value;
            var id = $(this).attr('data-id');
            var propiedad = $(this).attr('name');
            var baseUrl = "{{ route('configuracion.emails.update', ['id' => ':id']) }}";
            var url = baseUrl.replace(':id', id);

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append(propiedad, nuevoValor);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didDestroy: () => {
                            location.reload();
                        }
                    });
                    Toast.fire({
                        icon: "success",
                        title: 'Información actualizada correctamente'
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        });
    });

    // Reparaciones - Eliminar
    const botonesDeleteTecnico = document.querySelectorAll('#eliminarTecnico');
    botonesDeleteTecnico.forEach(function(nodo){
        $(nodo).on('click', function(){
            var id = $(this).attr('data-id');
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#007AFF',
                cancelButtonColor: '#8E8E93',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    var baseUrl = "{{ route('configuracion.deleteReparaciones', ['id' => ':id']) }}";
                    var url = baseUrl.replace(':id', id);
                    var formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: "top-end",
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didDestroy: () => {
                                    location.reload();
                                }
                            });
                            Toast.fire({
                                icon: "success",
                                title: 'Técnico eliminado correctamente'
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                        }
                    });
                }
            });
        });
    });

    // Limpiadoras - Eliminar
    const botonesDeleteLimpiadora = document.querySelectorAll('#eliminarLimpiadora');
    botonesDeleteLimpiadora.forEach(function(nodo){
        $(nodo).on('click', function(){
            var id = $(this).attr('data-id');
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#007AFF',
                cancelButtonColor: '#8E8E93',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    var baseUrl = "{{ route('configuracion.deleteLimpiadora', ['id' => ':id']) }}";
                    var url = baseUrl.replace(':id', id);
                    var formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: "top-end",
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didDestroy: () => {
                                    location.reload();
                                }
                            });
                            Toast.fire({
                                icon: "success",
                                title: 'Limpiadora eliminada correctamente'
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                        }
                    });
                }
            });
        });
    });
});
</script>
@endsection