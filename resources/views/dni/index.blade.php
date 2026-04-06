<!DOCTYPE html>
<html lang="{{ $locale ?? 'es' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ __('dni.index.title') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            /* Colores Booking.com - Portal */
            --booking-blue: #003580;
            --booking-blue-hover: #004585;
            --booking-blue-light: #E9F0FF;
            --booking-yellow: #FFB700;
            --booking-success: #0D7377;
            --booking-error: #EB5757;
            --booking-gray-dark: #333333;
            --booking-gray-medium: #666666;
            --booking-gray-light: #E8E8E8;
            --booking-gray-bg: #F5F5F5;
            --booking-white: #FFFFFF;
            
            /* Espaciado */
            --spacing-xs: 8px;
            --spacing-sm: 16px;
            --spacing-md: 24px;
            --spacing-lg: 32px;
            --spacing-xl: 40px;
            
            /* Tipografía */
            --font-family: BlinkMacSystemFont, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --font-size-base: 16px;
            --font-size-small: 14px;
            --font-size-xsmall: 12px;
            
            /* Transiciones */
            --transition: all 0.2s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background-color: var(--booking-gray-bg);
            min-height: 100vh;
            padding: 0;
            margin: 0;
            color: var(--booking-gray-dark);
        }
        
        /* Header del Portal */
        .portal-header {
            background: var(--booking-blue);
            width: 100%;
            padding: var(--spacing-md) var(--spacing-md);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .portal-header-content {
            width: 100%;
            max-width: 1200px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }
        
        .portal-logo {
            width: 60%;
            max-width: 300px;
            height: auto;
            object-fit: contain;
            flex: 1;
            display: flex;
            justify-content: center;
        }
        
        .language-selector {
            position: absolute;
            right: 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        
        .language-select {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--booking-white);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: var(--font-size-small);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-family: var(--font-family);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='white' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            padding-right: 30px;
        }
        
        .language-select:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .language-select:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .language-select option {
            background: var(--booking-blue);
            color: var(--booking-white);
        }
        
        /* Container principal */
        .main-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 80px);
            padding: var(--spacing-lg) 20px var(--spacing-xl);
        }
        
        .container {
            background: var(--booking-white);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            padding: var(--spacing-xl) var(--spacing-md);
            text-align: center;
            border: 1px solid var(--booking-gray-light);
        }
        
        @media (max-width: 768px) {
            body {
                background-color: var(--booking-white);
            }
            
            .container {
                background: transparent;
                border: none;
                box-shadow: none;
                border-radius: 0;
                padding: var(--spacing-md) 0;
            }
            
            .main-wrapper {
                min-height: calc(100vh - 70px);
                padding: var(--spacing-md) var(--spacing-sm);
            }
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: var(--spacing-md);
        }
        
        h1 {
            font-size: 28px;
            color: var(--booking-blue);
            margin-bottom: var(--spacing-xs);
            font-weight: 700;
            line-height: 1.2;
        }
        
        .subtitle {
            color: var(--booking-gray-medium);
            font-size: var(--font-size-base);
            margin-bottom: var(--spacing-xl);
            line-height: 1.6;
        }
        
        .options {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .btn-primary {
            background: var(--booking-blue);
            color: var(--booking-white);
            border: none;
            padding: 16px 24px;
            border-radius: 6px;
            font-size: var(--font-size-base);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-xs);
            text-decoration: none;
            font-family: var(--font-family);
            min-height: 52px;
        }
        
        .btn-primary:hover {
            background: var(--booking-blue-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 53, 128, 0.2);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: var(--booking-white);
            color: var(--booking-blue);
            border: 2px solid var(--booking-blue);
            padding: 16px 24px;
            border-radius: 6px;
            font-size: var(--font-size-base);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-xs);
            text-decoration: none;
            font-family: var(--font-family);
            min-height: 52px;
        }
        
        .btn-secondary:hover {
            background: var(--booking-blue-light);
        }
        
        .upload-hint-text {
            color: var(--booking-gray-dark);
            font-size: var(--font-size-small);
            margin-bottom: var(--spacing-xs);
            text-align: center;
            display: none;
        }
        
        .reservation-info {
            background: var(--booking-blue-light);
            border-radius: 8px;
            padding: var(--spacing-md);
            margin-top: var(--spacing-lg);
            text-align: left;
            border: 1px solid var(--booking-blue);
        }
        
        .reservation-info h3 {
            font-size: 18px;
            color: var(--booking-blue);
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        
        .reservation-info-item {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-sm);
            color: var(--booking-gray-dark);
            font-size: var(--font-size-base);
            padding: var(--spacing-xs) 0;
            border-bottom: 1px solid rgba(0, 53, 128, 0.1);
        }
        
        .reservation-info-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }
        
        .reservation-info-label {
            font-weight: 600;
            min-width: 140px;
            color: var(--booking-blue);
            font-size: var(--font-size-small);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .reservation-info-value {
            flex: 1;
            color: var(--booking-gray-dark);
            font-weight: 500;
            font-size: var(--font-size-base);
            margin-left: var(--spacing-sm);
        }
        
        /* Modal Styles */
        .info-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-md);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .info-modal.show {
            display: flex;
            opacity: 1;
        }
        
        .info-modal-content {
            background: var(--booking-white);
            border-radius: 12px;
            padding: var(--spacing-xl);
            max-width: 500px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            transition: transform 0.3s ease;
            text-align: left;
        }
        
        .info-modal.show .info-modal-content {
            transform: scale(1);
        }
        
        .info-modal h3 {
            font-size: 24px;
            color: var(--booking-blue);
            margin-bottom: var(--spacing-md);
            font-weight: 700;
            text-align: center;
        }
        
        .info-modal ul {
            list-style: none;
            padding-left: 0;
            margin-bottom: var(--spacing-lg);
        }
        
        .info-modal li {
            color: var(--booking-gray-dark);
            font-size: var(--font-size-base);
            margin-bottom: var(--spacing-sm);
            padding-left: 30px;
            position: relative;
            line-height: 1.6;
        }
        
        .info-modal li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--booking-success);
            font-weight: bold;
            font-size: 20px;
        }
        
        .info-modal-close {
            background: var(--booking-blue);
            color: var(--booking-white);
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: var(--font-size-base);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            font-family: var(--font-family);
        }
        
        .info-modal-close:hover {
            background: var(--booking-blue-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 53, 128, 0.2);
        }
        
        /* Footer Styles */
        .portal-footer {
            background: var(--booking-gray-dark);
            color: var(--booking-white);
            padding: var(--spacing-xl) var(--spacing-md);
            margin-top: var(--spacing-xl);
            border-top: 3px solid var(--booking-blue);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: var(--spacing-lg);
        }
        
        .footer-section {
            margin-bottom: var(--spacing-md);
        }
        
        .footer-section h4 {
            color: var(--booking-white);
            font-size: var(--font-size-base);
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
        }
        
        .footer-section p {
            color: rgba(255, 255, 255, 0.8);
            font-size: var(--font-size-small);
            line-height: 1.6;
            margin-bottom: var(--spacing-xs);
            word-break: break-word;
        }
        
        .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            font-size: var(--font-size-small);
            line-height: 1.6;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: var(--spacing-xs);
            transition: var(--transition);
            word-break: break-word;
        }
        
        .footer-section a:hover {
            color: var(--booking-white);
        }
        
        .footer-section .footer-icon {
            margin-right: var(--spacing-xs);
            color: var(--booking-blue);
            display: inline-block;
            flex-shrink: 0;
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: var(--spacing-lg) auto 0;
            padding-top: var(--spacing-md);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: var(--font-size-xsmall);
        }
        
        .footer-bottom p {
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .portal-header {
                padding: var(--spacing-sm) var(--spacing-sm);
            }
            
            .portal-logo {
                width: 60%;
                max-width: 300px;
            }
            
            .language-selector {
                position: relative;
                margin-left: auto;
            }
            
            .language-select {
                font-size: var(--font-size-xsmall);
                padding: 5px 10px;
                padding-right: 25px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .subtitle {
                font-size: var(--font-size-small);
            }
            
            .btn-primary {
                font-size: var(--font-size-small);
                padding: 14px 20px;
                min-height: 48px;
            }
            
            .btn-secondary {
                background: transparent;
                border: none;
                color: var(--booking-blue);
                font-size: var(--font-size-small);
                padding: 14px 20px;
                text-decoration: none;
                min-height: 48px;
            }
            
            .btn-secondary:hover {
                background: transparent;
                color: var(--booking-blue-hover);
            }
            
            .upload-hint-text {
                display: block;
            }
            
            .reservation-info {
                background: var(--booking-white);
                border: 1px solid var(--booking-gray-light);
            }
            
            .reservation-info-item {
                flex-direction: column;
                align-items: flex-start;
                padding: var(--spacing-xs) 0;
            }
            
            .reservation-info-label {
                min-width: auto;
                margin-bottom: 4px;
                font-size: var(--font-size-xsmall);
            }
            
            .reservation-info-value {
                margin-left: 0;
                font-size: var(--font-size-small);
            }
            
            .info-modal-content {
                padding: var(--spacing-md);
                max-width: 90%;
            }
            
            .info-modal h3 {
                font-size: 20px;
            }
            
            .info-modal li {
                font-size: var(--font-size-small);
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }
            
            .footer-section {
                margin-bottom: var(--spacing-lg);
            }
            
            .footer-section:last-of-type {
                margin-bottom: var(--spacing-md);
            }
            
            .footer-section h4 {
                font-size: 13px;
                margin-bottom: var(--spacing-xs);
            }
            
            .footer-section p,
            .footer-section a {
                font-size: var(--font-size-small);
                line-height: 1.7;
                margin-bottom: 6px;
            }
            
            .portal-footer {
                padding: var(--spacing-lg) var(--spacing-md);
            }
            
            .footer-bottom {
                padding-top: var(--spacing-md);
                margin-top: var(--spacing-md);
            }
            
            .footer-bottom p {
                font-size: 11px;
                line-height: 1.6;
            }
        }
        
        @media (max-width: 480px) {
            .footer-content {
                gap: var(--spacing-md);
            }
            
            .footer-section {
                margin-bottom: var(--spacing-md);
            }
            
            .footer-section h4 {
                font-size: 12px;
            }
            
            .footer-section p,
            .footer-section a {
                font-size: 13px;
            }
            
            .portal-footer {
                padding: var(--spacing-md) var(--spacing-sm);
            }
        }
        
        @media (max-width: 480px) {
            .portal-logo {
                width: 60%;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Header del Portal -->
    <header class="portal-header">
        <div class="portal-header-content">
            <div class="portal-logo">
                <img src="{{ asset('LOGO-HAWKINS.png') }}" alt="Apartamentos Hawkins" style="max-width: 100%; height: auto;">
            </div>
            <div class="language-selector">
                <select id="languageSelect" class="language-select">
                    @php
                        $currentLocale = session('locale', $cliente->idioma ?? 'es');
                    @endphp
                    <option value="es" {{ $currentLocale == 'es' ? 'selected' : '' }}>🇪🇸 ES</option>
                    <option value="en" {{ $currentLocale == 'en' ? 'selected' : '' }}>🇺🇸 EN</option>
                    <option value="fr" {{ $currentLocale == 'fr' ? 'selected' : '' }}>🇫🇷 FR</option>
                    <option value="de" {{ $currentLocale == 'de' ? 'selected' : '' }}>🇩🇪 DE</option>
                    <option value="it" {{ $currentLocale == 'it' ? 'selected' : '' }}>🇮🇹 IT</option>
                    <option value="pt" {{ $currentLocale == 'pt' ? 'selected' : '' }}>🇵🇹 PT</option>
                </select>
            </div>
        </div>
    </header>
    
    <!-- Contenido Principal -->
    <div class="main-wrapper">
        <div class="container">
        <div class="icon">🆔</div>
        <h1>{{ __('dni.index.title') }}</h1>
        <p class="subtitle">
            {!! __('dni.index.subtitle') !!}
        </p>
        <p class="subtitle" style="margin-top: var(--spacing-xs); font-size: var(--font-size-small); color: var(--booking-gray-medium);">
            {{ __('dni.index.choose_option') }}
        </p>
        
        <div class="options">
            <a href="{{ route('dni.scanner.show', $token) }}" class="btn-primary">
                {{ __('dni.index.take_photo') }}
            </a>
            
            <p class="upload-hint-text">{{ __('dni.index.upload_hint') }}</p>
            
            <a href="{{ route('dni.scanner.upload', $token) }}" class="btn-secondary">
                {{ __('dni.index.upload_images') }}
            </a>
        </div>
        
        @if($reserva && $reserva->apartamento)
        <div class="reservation-info">
            <h3>{{ __('dni.index.reservation_info') }}</h3>
            @if($reserva->apartamento->nombre)
            <div class="reservation-info-item">
                <span class="reservation-info-label">{{ __('dni.index.apartment') }}</span>
                <span class="reservation-info-value">{{ $reserva->apartamento->nombre }}</span>
            </div>
            @endif
            @if($reserva->codigo_reserva)
            <div class="reservation-info-item">
                <span class="reservation-info-label">{{ __('dni.index.code') }}</span>
                <span class="reservation-info-value">{{ $reserva->codigo_reserva }}</span>
            </div>
                @endif
            @if($reserva->fecha_entrada)
            <div class="reservation-info-item">
                <span class="reservation-info-label">{{ __('dni.index.checkin') }}</span>
                <span class="reservation-info-value">{{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}</span>
            </div>
                @endif
            @if($reserva->fecha_salida)
            <div class="reservation-info-item">
                <span class="reservation-info-label">{{ __('dni.index.checkout') }}</span>
                <span class="reservation-info-value">{{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') }}</span>
            </div>
                @endif
            @if($reserva->numero_personas)
            <div class="reservation-info-item">
                <span class="reservation-info-label">Personas:</span>
                <span class="reservation-info-value">{{ $reserva->numero_personas }} @if($reserva->numero_ninos && $reserva->numero_ninos > 0)({{ $reserva->numero_ninos }} niños)@endif</span>
            </div>
            @endif
        </div>
        @endif
        
        <!-- Modal de Información -->
        <div id="infoModal" class="info-modal">
            <div class="info-modal-content">
                <h3>{{ __('dni.index.modal.title') }}</h3>
                <p style="font-size: var(--font-size-small); color: var(--booking-gray-medium); margin-bottom: var(--spacing-md); text-align: center; line-height: 1.6;">
                    {!! __('dni.index.modal.description') !!}
                </p>
                <ul>
                    <li>{{ __('dni.index.modal.requirement.1') }}</li>
                    <li>{{ __('dni.index.modal.requirement.2') }}</li>
                    <li>{{ __('dni.index.modal.requirement.3') }}</li>
                </ul>
                <button class="info-modal-close" onclick="cerrarInfoModal()">{{ __('dni.index.modal.button') }}</button>
            </div>
        </div>
    </div>
    </div>
    
    <!-- Footer -->
    <footer class="portal-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Soporte</h4>
                <p>
                    <a href="mailto:info@apartamentosalgeciras.com">
                        <span class="footer-icon">📧</span>
                        info@apartamentosalgeciras.com
                    </a>
                </p>
                <p>
                    <a href="tel:+34633065237">
                        <span class="footer-icon">📞</span>
                        +34 633 065 237
                    </a>
                </p>
                <p>
                    <a href="https://wa.me/34605379329" target="_blank">
                        <span class="footer-icon">💬</span>
                        WhatsApp: +34 605 37 93 29
                    </a>
                </p>
                <p>
                    <span class="footer-icon">🕐</span>
                    Atención 24/7 para emergencias
                </p>
            </div>
            
            <div class="footer-section">
                <h4>Información Legal</h4>
                <a href="#">Política de Privacidad</a>
                <a href="#">Términos y Condiciones</a>
                <a href="#">Política de Cookies</a>
                <a href="#">Aviso Legal</a>
            </div>
            
            <div class="footer-section">
                <h4>Sobre Nosotros</h4>
                <p>Apartamentos Hawkins</p>
                <p>Alojamientos turísticos en Algeciras</p>
                <p>
                    <a href="https://www.apartamentosalgeciras.com" target="_blank">
                        <span class="footer-icon">🌐</span>
                        www.apartamentosalgeciras.com
                    </a>
                </p>
                <p>
                    <span class="footer-icon">📍</span>
                    Algeciras, Cádiz, España
                </p>
            </div>
            
            <div class="footer-section">
                <h4>Cumplimiento Normativo</h4>
                <p>Registro de Turismo de Andalucía</p>
                <p>Número de Registro: VFT/CA/XXXXX</p>
                <p style="font-size: var(--font-size-xsmall); margin-top: var(--spacing-sm);">
                    Cumplimiento con Real Decreto 933/2021 y Ley 13/2011 de Turismo de Andalucía
                </p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} Apartamentos Hawkins. Todos los derechos reservados.</p>
            <p style="margin-top: var(--spacing-xs);">
                Protegemos tus datos según el RGPD y la legislación española vigente.
            </p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Abrir modal de información automáticamente
            const infoModal = document.getElementById('infoModal');
            if (infoModal) {
                // Mostrar el modal después de un pequeño delay
                setTimeout(function() {
                    infoModal.classList.add('show');
                }, 500);
                
                // Cerrar automáticamente después de 5 segundos
                setTimeout(function() {
                    cerrarInfoModal();
                }, 5000);
            }
            
            // Selector de idioma
            const languageSelect = document.getElementById('languageSelect');
            
            if (languageSelect) {
                languageSelect.addEventListener('change', function() {
                    const selectedLanguage = this.value;
                    const token = {!! json_encode($token) !!};
                    
                    // Mostrar indicador de carga
                    const originalValue = this.value;
                    this.disabled = true;
                    this.style.opacity = '0.6';
                    
                    // Hacer petición AJAX para cambiar el idioma
                    const cambiarIdiomaUrl = {!! json_encode(route('dni.cambiarIdioma')) !!};
                    const csrfToken = {!! json_encode(csrf_token()) !!};
                    
                    fetch(cambiarIdiomaUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            idioma: selectedLanguage,
                            token: token
                        })
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        console.log('Respuesta del servidor:', data);
                        if (data.success) {
                            // Redirigir a la URL completa para asegurar que se recargue con el nuevo idioma
                            const newLocale = data.locale || selectedLanguage;
                            let redirectUrl = window.location.origin + '/dni-scanner/' + token;
                            
                            // Si hay una URL de redirección en la respuesta, usarla
                            if (data.redirect) {
                                redirectUrl = data.redirect;
                            }
                            
                            // Agregar parámetros de idioma y timestamp para evitar cache
                            const separator = redirectUrl.indexOf('?') === -1 ? '?' : '&';
                            redirectUrl = redirectUrl + separator + 'lang=' + encodeURIComponent(newLocale) + '&_=' + Date.now();
                            
                            console.log('Idioma cambiado correctamente, redirigiendo');
                            window.location.href = redirectUrl;
                        } else {
                            // Restaurar el valor anterior si hay error
                            console.error('Error del servidor');
                            languageSelect.value = originalValue;
                            languageSelect.disabled = false;
                            languageSelect.style.opacity = '1';
                            const errorMsg = (data.message || 'Error desconocido').toString();
                            alert('Error al cambiar el idioma: ' + errorMsg);
                        }
                    })
                    .catch(function(error) {
                        console.error('Error al cambiar idioma');
                        // Restaurar el valor anterior
                        languageSelect.value = originalValue;
                        languageSelect.disabled = false;
                        languageSelect.style.opacity = '1';
                        alert('Error al cambiar el idioma. Por favor, intenta de nuevo.');
                    });
                    });
                }
            });
            
        // Función para cerrar el modal
        function cerrarInfoModal() {
            const infoModal = document.getElementById('infoModal');
            if (infoModal) {
                infoModal.classList.remove('show');
                // Ocultar completamente después de la animación
                setTimeout(function() {
                    infoModal.style.display = 'none';
                }, 300);
            }
        }
        
        // Cerrar al hacer clic fuera del modal
        document.addEventListener('click', function(event) {
            const infoModal = document.getElementById('infoModal');
            if (infoModal && event.target === infoModal) {
                cerrarInfoModal();
            }
    });
</script>
</body>
</html>
