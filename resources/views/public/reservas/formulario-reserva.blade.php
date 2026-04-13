@extends('layouts.public-booking')

@section('title', 'Completar Reserva - ' . $apartamento->titulo)

@section('breadcrumb')
<div class="booking-breadcrumb">
    <div class="booking-container-header">
        <div class="booking-breadcrumb-content">
            <a href="{{ route('web.index') }}">Inicio</a>
            <span class="booking-breadcrumb-separator">></span>
            <a href="{{ route('web.reservas.portal') }}">Apartamentos</a>
            <span class="booking-breadcrumb-separator">></span>
            <a href="{{ route('web.reservas.show', $apartamento->id) }}">{{ $apartamento->titulo }}</a>
            <span class="booking-breadcrumb-separator">></span>
            <strong>Completar Reserva</strong>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .reservation-form-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
    }
    
    /* Usar el mismo contenedor que las otras vistas */
    .booking-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
        width: 100%;
        box-sizing: border-box;
    }
    
    @media (max-width: 768px) {
        .booking-detail-container {
            padding: 0 16px;
        }
    }
    
    .reservation-summary-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        padding: 24px;
        margin-bottom: 32px;
    }
    
    .reservation-form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        padding: 32px;
    }
    
    .form-section-title {
        font-size: 20px;
        font-weight: 700;
        color: #003580;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid #E0E0E0;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    
    .form-group label .required {
        color: #dc3545;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 12px 16px;
        border: 2px solid #E0E0E0;
        border-radius: 6px;
        font-size: 16px;
        transition: border-color 0.2s;
        width: 100%;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #003580;
    }
    
    .form-group input.is-invalid,
    .form-group select.is-invalid,
    .form-group textarea.is-invalid {
        border-color: #dc3545;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 14px;
        margin-top: 4px;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #E0E0E0;
    }
    
    .summary-item:last-child {
        border-bottom: none;
        font-weight: 700;
        font-size: 18px;
        color: #003580;
        margin-top: 8px;
        padding-top: 16px;
        border-top: 2px solid #003580;
    }
    
    .submit-btn {
        background: #003580;
        color: white;
        padding: 16px 32px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 18px;
        cursor: pointer;
        width: 100%;
        margin-top: 24px;
        transition: background 0.2s, transform 0.2s;
    }
    
    .submit-btn:hover {
        background: #0056CC;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 53, 128, 0.3);
    }
    
    .submit-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }
</style>
@endsection

@section('content')
<div class="booking-detail-container" style="margin-top: 24px;">
    <!-- Resumen de la Reserva -->
    <div class="reservation-summary-card">
        <h2 style="font-size: 24px; font-weight: 700; color: #003580; margin-bottom: 20px;">
            <i class="fas fa-calendar-check me-2"></i>{{ __('reservation.summary_title') }}
        </h2>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">{{ $apartamento->titulo }}</h3>
                <div style="color: #666; line-height: 1.8;">
                    <div><strong>{{ __('reservation.checkin_label') }}:</strong> {{ $fechaEntrada->format('d/m/Y') }}</div>
                    <div><strong>{{ __('reservation.checkout_label') }}:</strong> {{ $fechaSalida->format('d/m/Y') }}</div>
                    <div><strong>{{ __('reservation.nights_label') }}:</strong> {{ $noches }}</div>
                    <div><strong>{{ __('reservation.guests_label') }}:</strong> {{ $adultos }} {{ $adultos == 1 ? __('reservation.adults') : __('reservation.adults') }}{{ $ninos > 0 ? ', ' . $ninos . ' ' . ($ninos == 1 ? __('reservation.children') : __('reservation.children')) : '' }}</div>
                </div>
            </div>
            
            <div>
                <div class="summary-item">
                    <span>{{ __('reservation.price_per_night') }}</span>
                    <span>{{ number_format($precioPorNoche, 2, ',', '.') }} €</span>
                </div>
                <div class="summary-item">
                    <span>{{ $noches }} {{ $noches == 1 ? __('reservation.nights') : __('reservation.nights') }}</span>
                    <span>{{ number_format($precioPorNoche * $noches, 2, ',', '.') }} €</span>
                </div>
                <div class="summary-item">
                    <span>{{ __('reservation.total_price') }}</span>
                    <span>{{ number_format($precioTotal, 2, ',', '.') }} €</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario de Datos -->
    <div class="reservation-form-card">
        <h2 style="font-size: 24px; font-weight: 700; color: #003580; margin-bottom: 32px;">
            <i class="fas fa-user me-2"></i>{{ __('reservation.guest_data') }}
        </h2>
        
        @if($errors->any() || session('error'))
            <div style="background: #f8d7da; color: #721c24; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                <strong><i class="fas fa-exclamation-circle me-2"></i>{{ __('reservation.errors_title') }}</strong>
                <ul style="margin: 8px 0 0 20px;">
                    @if(session('error'))
                        <li>{{ session('error') }}</li>
                    @endif
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @auth('cliente')
            @if(!$esParaMi)
                <!-- Opción para elegir si es para él o para otro -->
                <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 24px;">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #003580;">
                        <i class="fas fa-question-circle me-2"></i>{{ __('reservation.for_whom') }}
                    </h3>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                        <label style="flex: 1; min-width: 200px; cursor: pointer;">
                            <input type="radio" name="es_para_mi" value="1" {{ $esParaMi ? 'checked' : '' }} 
                                   onchange="window.location.href='{{ route('web.reservas.formulario', ['apartamento' => $apartamento->id, 'fecha_entrada' => $fechaEntrada->format('Y-m-d'), 'fecha_salida' => $fechaSalida->format('Y-m-d'), 'adultos' => $adultos, 'ninos' => $ninos, 'es_para_mi' => 1]) }}'"
                                   style="margin-right: 8px;">
                            <strong>{{ __('reservation.for_me') }}</strong>
                            <br>
                            <small style="color: #666;">{{ __('reservation.for_me_desc') }}</small>
                        </label>
                        <label style="flex: 1; min-width: 200px; cursor: pointer;">
                            <input type="radio" name="es_para_mi" value="0" {{ !$esParaMi ? 'checked' : '' }} 
                                   onchange="window.location.href='{{ route('web.reservas.formulario', ['apartamento' => $apartamento->id, 'fecha_entrada' => $fechaEntrada->format('Y-m-d'), 'fecha_salida' => $fechaSalida->format('Y-m-d'), 'adultos' => $adultos, 'ninos' => $ninos, 'es_para_mi' => 0]) }}'"
                                   style="margin-right: 8px;">
                            <strong>{{ __('reservation.for_other') }}</strong>
                            <br>
                            <small style="color: #666;">{{ __('reservation.for_other_desc') }}</small>
                        </label>
                    </div>
                </div>
            @endif
        @endauth
        
        <form action="{{ route('web.reservas.procesar') }}" method="POST" id="reservationForm">
            @csrf
            
            <!-- Datos ocultos de la reserva -->
            <input type="hidden" name="apartamento_id" value="{{ $apartamento->id }}">
            <input type="hidden" name="fecha_entrada" value="{{ $fechaEntrada->format('Y-m-d') }}">
            <input type="hidden" name="fecha_salida" value="{{ $fechaSalida->format('Y-m-d') }}">
            <input type="hidden" name="adultos" value="{{ $adultos }}">
            <input type="hidden" name="ninos" value="{{ $ninos }}">
            @if(!empty($holdToken))
                <input type="hidden" name="hold_token" value="{{ $holdToken }}">
            @endif
            @if(auth('cliente')->check())
                <input type="hidden" name="es_para_mi" value="{{ $esParaMi ? '1' : '0' }}">
            @endif
            
            @if(!auth('cliente')->check() || !$esParaMi)
                <!-- Datos Personales -->
                <h3 class="form-section-title">{{ __('reservation.personal_data') }}</h3>
            @endif
            
            @if(auth('cliente')->check() && $esParaMi)
                @php
                    $cliente = auth('cliente')->user();
                @endphp
                
                <!-- Alerta única si faltan datos -->
                @if(!empty($datosFaltantes))
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>{{ __('reservation.missing_data') }}</strong>
                        <ul style="margin: 8px 0 0 20px;">
                            @foreach($datosFaltantes as $dato)
                                <li>{{ $dato }}</li>
                            @endforeach
                        </ul>
                        <p style="margin-top: 12px; margin-bottom: 0;">
                            <a href="{{ route('web.perfil') }}?return_to_reserva=1" class="btn btn-warning btn-sm">
                                <i class="fas fa-user-edit me-2"></i>{{ __('reservation.complete_profile') }}
                            </a>
                        </p>
                    </div>
                @else
                    <div style="background: #d1ecf1; border-left: 4px solid #0dcaf0; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                        <strong><i class="fas fa-check-circle me-2"></i>{{ __('reservation.all_data_complete') }}</strong>
                    </div>
                @endif
                
                <!-- Resumen completo de TODOS los datos del cliente -->
                <div style="background: #f8f9fa; padding: 24px; border-radius: 8px; margin-bottom: 24px;">
                    <h4 style="color: #003580; margin-bottom: 20px; font-size: 20px;">
                        <i class="fas fa-user me-2"></i>{{ __('reservation.your_data') }}
                    </h4>
                    <div class="row" style="margin: 0;">
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_name') }}</strong>
                            <span style="color: {{ empty($cliente->nombre) ? '#dc3545' : '#333' }};">
                                @if($cliente->nombre){{ $cliente->nombre }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_surname') }}</strong>
                            <span style="color: {{ empty($cliente->apellido1) ? '#dc3545' : '#333' }};">
                                @if(trim(($cliente->apellido1 ?? '') . ' ' . ($cliente->apellido2 ?? ''))){{ trim(($cliente->apellido1 ?? '') . ' ' . ($cliente->apellido2 ?? '')) }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_email') }}</strong>
                            <span style="color: {{ empty($cliente->email_principal) ? '#dc3545' : '#333' }};">
                                @if($cliente->email_principal){{ $cliente->email_principal }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_phone') }}</strong>
                            <span style="color: {{ empty($cliente->telefono) ? '#dc3545' : '#333' }};">
                                @if($cliente->telefono){{ $cliente->telefono }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_mobile') }}</strong>
                            <span style="color: {{ empty($cliente->telefono_movil) ? '#dc3545' : '#333' }};">
                                @if($cliente->telefono_movil){{ $cliente->telefono_movil }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_birthdate') }}</strong>
                            <span style="color: {{ empty($cliente->fecha_nacimiento) ? '#dc3545' : '#333' }};">
                                @if($cliente->fecha_nacimiento){{ $cliente->fecha_nacimiento->format('d/m/Y') }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_birthplace') }}</strong>
                            <span style="color: {{ empty($cliente->lugar_nacimiento) ? '#dc3545' : '#333' }};">
                                @if($cliente->lugar_nacimiento){{ $cliente->lugar_nacimiento }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_nationality') }}</strong>
                            <span style="color: {{ empty($cliente->nacionalidad) ? '#dc3545' : '#333' }};">
                                @if($cliente->nacionalidad){{ $cliente->nacionalidad }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_gender') }}</strong>
                            <span style="color: {{ empty($cliente->sexo) ? '#dc3545' : '#333' }};">
                                @if($cliente->sexo){{ $cliente->sexo }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_document_type') }}</strong>
                            <span style="color: {{ empty($cliente->tipo_documento) ? '#dc3545' : '#333' }};">
                                @if($cliente->tipo_documento){{ $cliente->tipo_documento }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">Número de Documento:</strong>
                            <span style="color: {{ empty($cliente->num_identificacion) ? '#dc3545' : '#333' }};">
                                @if($cliente->num_identificacion){{ $cliente->num_identificacion }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_document_expiry') }}</strong>
                            <span style="color: {{ empty($cliente->fecha_expedicion_doc) ? '#dc3545' : '#333' }};">
                                @if($cliente->fecha_expedicion_doc){{ $cliente->fecha_expedicion_doc instanceof \Carbon\Carbon ? $cliente->fecha_expedicion_doc->format('d/m/Y') : \Carbon\Carbon::parse($cliente->fecha_expedicion_doc)->format('d/m/Y') }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_address') }}</strong>
                            <span style="color: #333;">
                                {{ $cliente->direccion ?? __('reservation.not_specified') }}
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_city') }}</strong>
                            <span style="color: {{ empty($cliente->localidad) ? '#dc3545' : '#333' }};">
                                @if($cliente->localidad){{ $cliente->localidad }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_postal_code') }}</strong>
                            <span style="color: #333;">
                                {{ $cliente->codigo_postal ?? __('reservation.not_specified') }}
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong style="color: #666; display: block; margin-bottom: 4px;">{{ __('reservation.field_province') }}</strong>
                            <span style="color: {{ empty($cliente->provincia) ? '#dc3545' : '#333' }};">
                                @if($cliente->provincia){{ $cliente->provincia }}@else<span style="color: #dc3545;">{{ __('reservation.not_specified') }}</span>@endif
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Campos ocultos con datos del cliente -->
                <input type="hidden" name="nombre" value="{{ $cliente->nombre }}">
                <input type="hidden" name="apellido1" value="{{ $cliente->apellido1 }}">
                <input type="hidden" name="apellido2" value="{{ $cliente->apellido2 }}">
                <input type="hidden" name="email" value="{{ $cliente->email_principal }}">
                <input type="hidden" name="telefono" value="{{ $cliente->telefono_movil ?? $cliente->telefono }}">
                <input type="hidden" name="tipo_documento" value="{{ $cliente->tipo_documento == 'DNI' || $cliente->tipo_documento == 'NIE' ? 'D' : ($cliente->tipo_documento == 'PASAPORTE' ? 'P' : 'D') }}">
                <input type="hidden" name="num_identificacion" value="{{ $cliente->num_identificacion }}">
                <input type="hidden" name="nacionalidad" value="{{ $cliente->nacionalidad }}">
                <input type="hidden" name="fecha_nacimiento" value="{{ $cliente->fecha_nacimiento ? $cliente->fecha_nacimiento->format('Y-m-d') : '' }}">
                <input type="hidden" name="fecha_expedicion" value="{{ $cliente->fecha_expedicion_doc ? ($cliente->fecha_expedicion_doc instanceof \Carbon\Carbon ? $cliente->fecha_expedicion_doc->format('Y-m-d') : \Carbon\Carbon::parse($cliente->fecha_expedicion_doc)->format('Y-m-d')) : '' }}">
                <input type="hidden" name="sexo" value="{{ $cliente->sexo }}">
                <input type="hidden" name="direccion" value="{{ $cliente->direccion }}">
                <input type="hidden" name="localidad" value="{{ $cliente->localidad }}">
                <input type="hidden" name="codigo_postal" value="{{ $cliente->codigo_postal }}">
                <input type="hidden" name="provincia" value="{{ $cliente->provincia }}">
                <input type="hidden" name="lugar_nacimiento" value="{{ $cliente->lugar_nacimiento }}">
                <input type="hidden" name="fecha_caducidad" value="{{ now()->addYears(10)->format('Y-m-d') }}">
            @else
                <!-- Formulario simplificado: solo datos básicos para reservar y pagar -->
                <!-- Los datos de DNI, nacionalidad, dirección, etc. se piden DESPUÉS via checkin -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">{{ __('reservation.name_label') }} <span class="required">*</span></label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            value="{{ old('nombre', '') }}"
                            required
                            placeholder="Tu nombre"
                            class="{{ $errors->has('nombre') ? 'is-invalid' : '' }}"
                        >
                        @error('nombre')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="apellido1">{{ __('reservation.surname1_label') }} <span class="required">*</span></label>
                        <input
                            type="text"
                            id="apellido1"
                            name="apellido1"
                            value="{{ old('apellido1', '') }}"
                            required
                            placeholder="Tu apellido"
                            class="{{ $errors->has('apellido1') ? 'is-invalid' : '' }}"
                        >
                        @error('apellido1')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', '') }}"
                            required
                            placeholder="tu@email.com"
                            class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                        >
                        @error('email')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="telefono">{{ __('reservation.field_phone') }} <span class="required">*</span></label>
                        <input
                            type="tel"
                            id="telefono"
                            name="telefono"
                            value="{{ old('telefono', '') }}"
                            required
                            placeholder="+34 600 000 000"
                            class="{{ $errors->has('telefono') ? 'is-invalid' : '' }}"
                        >
                        @error('telefono')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            @endif

            <!-- Cupón de Descuento -->
            <h3 class="form-section-title" style="margin-top: 32px;">
                <i class="fas fa-tag me-2"></i>¿Tienes un cupón de descuento?
            </h3>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="codigo_cupon">Código del cupón</label>
                        <input 
                            type="text" 
                            name="codigo_cupon" 
                            id="codigo_cupon" 
                            placeholder="Ej: VERANO2024"
                            value="{{ old('codigo_cupon') }}"
                            style="text-transform: uppercase;">
                        <small class="text-muted">Si tienes un código de descuento, introdúcelo aquí</small>
                        @error('codigo_cupon')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        @if(session('cupon_error'))
                            <div class="error-message">{{ session('cupon_error') }}</div>
                        @endif
                        @if(session('cupon_aplicado'))
                            <div style="color: #28a745; font-size: 14px; margin-top: 4px;">
                                <i class="fas fa-check-circle me-1"></i>{{ session('cupon_aplicado') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Botón de Envío -->
            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="fas fa-credit-card me-2"></i>
                {{ __('reservation.continue') }} - {{ number_format($precioTotal, 2, ',', '.') }} €
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>{{ __('common.processing') }}';
    });
</script>
@endsection

