<!DOCTYPE html>
<html lang="{{ $locale ?? 'es' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ __('dni.upload.title') }}</title>
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
            padding: var(--spacing-lg) 20px var(--spacing-xl);
            min-height: calc(100vh - 80px);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }
        
        h1 {
            font-size: 28px;
            color: var(--booking-blue);
            margin-bottom: var(--spacing-xs);
            text-align: center;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .subtitle {
            color: var(--booking-gray-medium);
            font-size: var(--font-size-base);
            margin-bottom: var(--spacing-lg);
            text-align: center;
            line-height: 1.6;
        }
        
        .person-section {
            margin-bottom: var(--spacing-xl);
            padding: var(--spacing-md);
            background: var(--booking-white);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--booking-gray-light);
        }
        
        .person-title {
            font-size: 20px;
            color: var(--booking-blue);
            margin-bottom: var(--spacing-md);
            font-weight: 600;
            padding-bottom: var(--spacing-xs);
            border-bottom: 2px solid var(--booking-blue-light);
        }
        
        .person-name {
            font-size: var(--font-size-small);
            color: var(--booking-gray-medium);
            margin-bottom: var(--spacing-md);
        }
        
        .additional-data-section {
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--booking-gray-light);
        }
        
        .additional-data-title {
            font-size: 16px;
            color: var(--booking-blue);
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: var(--spacing-md);
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: var(--booking-gray-dark);
            margin-bottom: var(--spacing-xs);
            font-size: var(--font-size-small);
        }
        
        .form-label .required {
            color: var(--booking-error);
        }
        
        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--booking-gray-light);
            border-radius: 6px;
            font-size: var(--font-size-base);
            font-family: var(--font-family);
            color: var(--booking-gray-dark);
            transition: var(--transition);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--booking-blue);
            box-shadow: 0 0 0 3px var(--booking-blue-light);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-sm);
        }
        
        .form-help {
            font-size: var(--font-size-xsmall);
            color: var(--booking-gray-medium);
            margin-top: 4px;
        }
        
        /* Modal de procesamiento */
        .processing-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .processing-modal.show {
            display: flex;
        }
        
        .processing-modal-content {
            background: var(--booking-white);
            padding: var(--spacing-xl);
            border-radius: 12px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .processing-modal-content h3 {
            color: var(--booking-blue);
            margin-bottom: var(--spacing-md);
            font-size: 20px;
        }
        
        .processing-spinner {
            border: 4px solid var(--booking-gray-light);
            border-top: 4px solid var(--booking-blue);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto var(--spacing-md);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .processing-modal-content p {
            color: var(--booking-gray-dark);
            font-size: var(--font-size-base);
            margin-bottom: var(--spacing-sm);
        }
        
        .processing-modal-content .url-info {
            font-size: var(--font-size-xsmall);
            color: var(--booking-gray-medium);
            margin-top: var(--spacing-sm);
            word-break: break-all;
        }
        
        .upload-section {
            margin-bottom: var(--spacing-md);
        }
        
        .upload-label {
            display: block;
            font-weight: 600;
            color: var(--booking-gray-dark);
            margin-bottom: var(--spacing-xs);
            font-size: var(--font-size-base);
        }
        
        .upload-label .required {
            color: var(--booking-error);
        }
        
        .upload-area {
            border: 2px dashed var(--booking-blue);
            border-radius: 8px;
            padding: var(--spacing-lg);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: var(--booking-blue-light);
        }
        
        .upload-area:hover {
            background: var(--booking-blue-light);
            border-color: var(--booking-blue-hover);
        }
        
        .upload-area.dragover {
            background: var(--booking-blue-light);
            border-color: var(--booking-blue);
            transform: scale(1.01);
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .upload-text {
            color: var(--booking-gray-dark);
            font-size: var(--font-size-base);
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .upload-hint {
            color: var(--booking-gray-medium);
            font-size: var(--font-size-small);
        }
        
        input[type="file"] {
            display: none;
        }
        
        .preview-container {
            margin-top: var(--spacing-sm);
            display: none;
        }
        
        .preview-container.active {
            display: block;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: var(--spacing-xs);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .file-name {
            color: var(--booking-blue);
            font-size: var(--font-size-small);
            margin-top: var(--spacing-xs);
            font-weight: 600;
        }
        
        .btn-submit {
            background: var(--booking-blue);
            color: var(--booking-white);
            border: none;
            padding: 16px 24px;
            border-radius: 6px;
            font-size: var(--font-size-base);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: var(--spacing-md);
            font-family: var(--font-family);
            min-height: 52px;
        }
        
        .btn-submit:hover:not(:disabled) {
            background: var(--booking-blue-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 53, 128, 0.2);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: var(--booking-gray-medium);
        }
        
        .btn-back {
            background: var(--booking-white);
            color: var(--booking-blue);
            border: 2px solid var(--booking-blue);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: var(--font-size-small);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            margin-bottom: var(--spacing-md);
            font-family: var(--font-family);
        }
        
        .btn-back:hover {
            background: var(--booking-blue-light);
        }
        
        .error-message {
            background: #FFF5F5;
            color: var(--booking-error);
            padding: var(--spacing-sm);
            border-radius: 6px;
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-small);
            border-left: 4px solid var(--booking-error);
            display: none;
        }
        
        .error-message.active {
            display: block;
        }
        
        .success-message {
            background: #E6F7F8;
            color: var(--booking-success);
            padding: var(--spacing-sm);
            border-radius: 6px;
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-small);
            border-left: 4px solid var(--booking-success);
            display: none;
        }
        
        .success-message.active {
            display: block;
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
            font-size: 14px;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            body {
                background-color: var(--booking-white);
            }
            
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
            
            .main-wrapper {
                padding: var(--spacing-md) var(--spacing-sm);
            }
            
            .container {
                padding: 0;
            }
            
            .person-section {
                background: transparent;
                border: none;
                box-shadow: none;
                padding: var(--spacing-md) 0;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .subtitle {
                font-size: var(--font-size-small);
            }
            
            .person-title {
                font-size: 18px;
            }
            
            .upload-area {
                padding: var(--spacing-md);
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .additional-data-section {
                margin-top: var(--spacing-md);
                padding-top: var(--spacing-md);
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
            .portal-logo {
                width: 60%;
                max-width: 250px;
            }
            
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
                    <option value="es" {{ session('locale') == 'es' || (!session('locale') && (!$cliente->idioma || $cliente->idioma == 'es')) ? 'selected' : '' }}>🇪🇸 ES</option>
                    <option value="en" {{ session('locale') == 'en' || ($cliente->idioma == 'en') ? 'selected' : '' }}>🇺🇸 EN</option>
                    <option value="fr" {{ session('locale') == 'fr' || ($cliente->idioma == 'fr') ? 'selected' : '' }}>🇫🇷 FR</option>
                    <option value="de" {{ session('locale') == 'de' || ($cliente->idioma == 'de') ? 'selected' : '' }}>🇩🇪 DE</option>
                    <option value="it" {{ session('locale') == 'it' || ($cliente->idioma == 'it') ? 'selected' : '' }}>🇮🇹 IT</option>
                    <option value="pt" {{ session('locale') == 'pt' || ($cliente->idioma == 'pt') ? 'selected' : '' }}>🇵🇹 PT</option>
                </select>
            </div>
        </div>
    </header>
    
    <!-- Contenido Principal -->
    <div class="main-wrapper">
        <div class="container">
            <a href="{{ route('dni.scanner.index', $token) }}" class="btn-back">{{ __('dni.upload.back') }}</a>
            
            <h1>{{ __('dni.upload.title') }}</h1>
            <p class="subtitle">
                {{ __('dni.upload.subtitle') }}
            </p>
            
            <div class="error-message" id="errorMessage"></div>
            <div class="success-message" id="successMessage"></div>
            
            <!-- Modal de procesamiento -->
            <div id="processingModal" class="processing-modal" style="display: none;">
                <div class="processing-modal-content">
                    <div class="processing-spinner">
                        <div class="spinner"></div>
                    </div>
                    <h3>{{ __('dni.upload.processing.title') }}</h3>
                    <p>{{ __('dni.upload.processing.message') }}</p>
                    <div id="processingStatus" style="margin-top: 15px; font-size: 14px; color: var(--booking-gray-medium);"></div>
                    <div id="processingUrl" style="margin-top: 10px; font-size: 12px; color: var(--booking-gray-medium); font-family: monospace; word-break: break-all;"></div>
                </div>
            </div>
            
            <form id="uploadForm" enctype="multipart/form-data" novalidate onsubmit="event.preventDefault(); return false;">
                @csrf
                
                @php
                    $adultoIndex = 1;
                @endphp
                
                {{-- Cliente Principal --}}
                <div class="person-section">
                    <div class="person-title">{{ __('dni.upload.adult.main', ['number' => $adultoIndex]) }}</div>
                    <div class="person-name">
                        {{ $cliente->nombre }} {{ $cliente->apellido1 }} @if($cliente->apellido2){{ $cliente->apellido2 }}@endif
                    </div>
                    
                    <div class="upload-section">
                        <label class="upload-label">
                            {{ __('dni.upload.image.front') }} <span class="required">*</span>
                        </label>
                        <div class="upload-area" id="frontalArea_0" onclick="document.getElementById('frontalFile_0').click()">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">{{ __('dni.upload.image.click') }}</div>
                            <div class="upload-hint">{{ __('dni.upload.image.hint') }}</div>
                            <input type="file" id="frontalFile_0" name="frontal[0]" accept="image/jpeg,image/jpg,image/png">
                            <div class="preview-container" id="frontalPreview_0">
                                <img class="preview-image" id="frontalImage_0" src="" alt="{{ __('dni.upload.image.preview.front') }}">
                                <div class="file-name" id="frontalName_0"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="upload-section">
                        <label class="upload-label">
                            {{ __('dni.upload.image.rear') }} <span class="required">({{ __('dni.upload.image.optional') }})</span>
                        </label>
                        <div class="upload-area" id="traseraArea_0" onclick="document.getElementById('traseraFile_0').click()">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">{{ __('dni.upload.image.click') }}</div>
                            <div class="upload-hint">{{ __('dni.upload.image.hint') }}</div>
                            <input type="file" id="traseraFile_0" name="trasera[0]" accept="image/jpeg,image/jpg,image/png">
                            <div class="preview-container" id="traseraPreview_0">
                                <img class="preview-image" id="traseraImage_0" src="" alt="{{ __('dni.upload.image.preview.rear') }}">
                                <div class="file-name" id="traseraName_0"></div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="persona_tipo[0]" value="cliente">
                    <input type="hidden" name="persona_id[0]" value="{{ $cliente->id }}">
                    
                    {{-- Datos adicionales para Cliente Principal --}}
                    <div class="additional-data-section" id="additional-data-0" style="display: none; opacity: 0; visibility: hidden;">
                        <div class="additional-data-title">{{ __('dni.upload.extracted.title') }}</div>
                        <div id="extracted-data-display-0" style="background: var(--booking-gray-light); padding: var(--spacing-md); border-radius: 6px; margin-bottom: var(--spacing-md);">
                            <p style="color: var(--booking-gray-medium); font-size: var(--font-size-small); margin-bottom: var(--spacing-sm);">
                                <strong>{{ __('dni.upload.extracted.label') }}</strong>
                            </p>
                            <div id="extracted-data-content-0" style="font-size: var(--font-size-small); color: var(--booking-gray-dark);">
                                <!-- Los datos se mostrarán aquí cuando se procesen -->
                            </div>
                        </div>
                        <div class="additional-data-title">{{ __('dni.upload.additional.title') }}</div>
                        <p style="color: var(--booking-gray-medium); font-size: var(--font-size-small); margin-bottom: var(--spacing-md);">
                            {{ __('dni.upload.additional.message') }}
                        </p>
                        
                            <div class="form-group">
                                <label class="form-label">
                                    {{ __('dni.upload.phone.label') }} <span class="required">*</span>
                                </label>
                                <input type="tel" 
                                       class="form-input" 
                                       name="telefono_movil[0]" 
                                       value="{{ $cliente->telefono_movil ?? '' }}"
                                       placeholder="+34 600 000 000"
                                       required>
                                <div class="form-help">{{ __('dni.upload.phone.help.main') }}</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    {{ __('dni.upload.email.label') }} <span class="required">*</span>
                                </label>
                                <input type="email" 
                                       class="form-input" 
                                       name="email[0]" 
                                       value="{{ $cliente->email ?? '' }}"
                                       placeholder="email@ejemplo.com"
                                       required>
                                <div class="form-help">{{ __('dni.upload.email.help.main') }}</div>
                            </div>
                        
                        {{-- Campos dinámicos para dirección (se mostrarán si no se extrajeron del DNI) --}}
                        <div id="addressFields_0" style="display: none;">
                            <!-- Los campos de dirección se mostrarán aquí dinámicamente si no se extrajeron -->
                        </div>
                        
                        {{-- Campos ocultos para dirección (se guardan automáticamente del DNI si están disponibles) --}}
                        <input type="hidden" name="direccion[0]" id="hidden_direccion_0" value="{{ $cliente->direccion ?? '' }}">
                        <input type="hidden" name="localidad[0]" id="hidden_localidad_0" value="{{ $cliente->localidad ?? '' }}">
                        <input type="hidden" name="codigo_postal[0]" id="hidden_codigo_postal_0" value="{{ $cliente->codigo_postal ?? '' }}">
                        <input type="hidden" name="provincia[0]" id="hidden_provincia_0" value="{{ $cliente->provincia ?? '' }}">
                    </div>
                </div>
                
                @php
                    $adultoIndex++;
                @endphp
                
                {{-- Huéspedes (Acompañantes) --}}
                @for($i = 1; $i < $numeroAdultos; $i++)
                    @php
                        $huesped = $huespedes->get($i - 1) ?? null;
                    @endphp
                    
                    <div class="person-section">
                        <div class="person-title">{{ __('dni.upload.adult.companion', ['number' => $adultoIndex]) }}</div>
                        @if($huesped)
                            <div class="person-name">
                                {{ $huesped->nombre }} {{ $huesped->primer_apellido }} @if($huesped->segundo_apellido){{ $huesped->segundo_apellido }}@endif
                            </div>
                        @else
                            <div class="person-name" style="color: var(--booking-gray-medium);">
                                {{ __('dni.upload.pending.info') }}
                            </div>
                        @endif
                        
                        <div class="upload-section">
                            <label class="upload-label">
                                {{ __('dni.upload.image.front') }} <span class="required">*</span>
                            </label>
                            <div class="upload-area" id="frontalArea_{{ $i }}" onclick="document.getElementById('frontalFile_{{ $i }}').click()">
                                <div class="upload-icon">📷</div>
                                <div class="upload-text">{{ __('dni.upload.image.click') }}</div>
                                <div class="upload-hint">{{ __('dni.upload.image.hint') }}</div>
                                <input type="file" id="frontalFile_{{ $i }}" name="frontal[{{ $i }}]" accept="image/jpeg,image/jpg,image/png">
                                <div class="preview-container" id="frontalPreview_{{ $i }}">
                                    <img class="preview-image" id="frontalImage_{{ $i }}" src="" alt="{{ __('dni.upload.image.preview.front') }}">
                                    <div class="file-name" id="frontalName_{{ $i }}"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="upload-section">
                            <label class="upload-label">
                                {{ __('dni.upload.image.rear') }} <span class="required">({{ __('dni.upload.image.optional') }})</span>
                            </label>
                            <div class="upload-area" id="traseraArea_{{ $i }}" onclick="document.getElementById('traseraFile_{{ $i }}').click()">
                                <div class="upload-icon">📷</div>
                                <div class="upload-text">{{ __('dni.upload.image.click') }}</div>
                                <div class="upload-hint">{{ __('dni.upload.image.hint') }}</div>
                                <input type="file" id="traseraFile_{{ $i }}" name="trasera[{{ $i }}]" accept="image/jpeg,image/jpg,image/png">
                                <div class="preview-container" id="traseraPreview_{{ $i }}">
                                    <img class="preview-image" id="traseraImage_{{ $i }}" src="" alt="{{ __('dni.upload.image.preview.rear') }}">
                                    <div class="file-name" id="traseraName_{{ $i }}"></div>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="persona_tipo[{{ $i }}]" value="huesped">
                        @if($huesped)
                            <input type="hidden" name="persona_id[{{ $i }}]" value="{{ $huesped->id }}">
                        @endif
                        
                        {{-- Datos adicionales para Huéspedes (Acompañantes) --}}
                        <div class="additional-data-section" id="additional-data-{{ $i }}" style="display: none; opacity: 0; visibility: hidden;">
                            <div class="additional-data-title">{{ __('dni.upload.extracted.title') }}</div>
                            <div id="extracted-data-display-{{ $i }}" style="background: var(--booking-gray-light); padding: var(--spacing-md); border-radius: 6px; margin-bottom: var(--spacing-md);">
                                <p style="color: var(--booking-gray-medium); font-size: var(--font-size-small); margin-bottom: var(--spacing-sm);">
                                    <strong>{{ __('dni.upload.extracted.label') }}</strong>
                                </p>
                                <div id="extracted-data-content-{{ $i }}" style="font-size: var(--font-size-small); color: var(--booking-gray-dark);">
                                    <!-- Los datos se mostrarán aquí cuando se procesen -->
                                </div>
                            </div>
                            <div class="additional-data-title">{{ __('dni.upload.additional.title') }}</div>
                            <p style="color: var(--booking-gray-medium); font-size: var(--font-size-small); margin-bottom: var(--spacing-md);">
                                {{ __('dni.upload.additional.message') }}
                            </p>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    {{ __('dni.upload.relationship.label') }} <span class="required">*</span>
                                </label>
                                <select class="form-input" name="relacion_parentesco[{{ $i }}]" required>
                                    <option value="">{{ __('dni.upload.relationship.select') }}</option>
                                    <option value="Cónyuge" {{ ($huesped && $huesped->relacion_parentesco == 'Cónyuge') ? 'selected' : '' }}>Cónyuge</option>
                                    <option value="Hijo/a" {{ ($huesped && $huesped->relacion_parentesco == 'Hijo/a') ? 'selected' : '' }}>Hijo/a</option>
                                    <option value="Padre/Madre" {{ ($huesped && $huesped->relacion_parentesco == 'Padre/Madre') ? 'selected' : '' }}>Padre/Madre</option>
                                    <option value="Hermano/a" {{ ($huesped && $huesped->relacion_parentesco == 'Hermano/a') ? 'selected' : '' }}>Hermano/a</option>
                                    <option value="Otro familiar" {{ ($huesped && $huesped->relacion_parentesco == 'Otro familiar') ? 'selected' : '' }}>Otro familiar</option>
                                    <option value="Amigo/a" {{ ($huesped && $huesped->relacion_parentesco == 'Amigo/a') ? 'selected' : '' }}>Amigo/a</option>
                                    <option value="Otro" {{ ($huesped && $huesped->relacion_parentesco == 'Otro') ? 'selected' : '' }}>Otro</option>
                                </select>
                                <div class="form-help">Relación con el cliente principal</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    {{ __('dni.upload.phone.label') }} <span style="color: var(--booking-gray-medium); font-size: var(--font-size-xsmall);">(al menos uno requerido)</span>
                                </label>
                                <input type="tel" 
                                       class="form-input" 
                                       name="telefono_movil[{{ $i }}]" 
                                       value="{{ $huesped->telefono_movil ?? '' }}"
                                       placeholder="+34 600 000 000">
                                <div class="form-help">{{ __('dni.upload.phone.help.companion') }}</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    {{ __('dni.upload.email.label') }} <span style="color: var(--booking-gray-medium); font-size: var(--font-size-xsmall);">(al menos uno requerido)</span>
                                </label>
                                <input type="email" 
                                       class="form-input" 
                                       name="email[{{ $i }}]" 
                                       value="{{ $huesped->email ?? '' }}"
                                       placeholder="email@ejemplo.com">
                                <div class="form-help">{{ __('dni.upload.email.help.companion') }}</div>
                            </div>
                            
                            {{-- Campos dinámicos para dirección (se mostrarán si no se extrajeron del DNI) --}}
                            <div id="addressFields_{{ $i }}" style="display: none;">
                                <!-- Los campos de dirección se mostrarán aquí dinámicamente si no se extrajeron -->
                            </div>
                            
                            {{-- Campos ocultos para dirección (se guardan automáticamente del DNI si están disponibles) --}}
                            <input type="hidden" name="direccion[{{ $i }}]" id="hidden_direccion_{{ $i }}" value="{{ $huesped->direccion ?? '' }}">
                            <input type="hidden" name="localidad[{{ $i }}]" id="hidden_localidad_{{ $i }}" value="{{ $huesped->localidad ?? '' }}">
                            <input type="hidden" name="codigo_postal[{{ $i }}]" id="hidden_codigo_postal_{{ $i }}" value="{{ $huesped->codigo_postal ?? '' }}">
                            <input type="hidden" name="provincia[{{ $i }}]" id="hidden_provincia_{{ $i }}" value="{{ $huesped->provincia ?? '' }}">
                        </div>
                    </div>
                    
                    @php
                        $adultoIndex++;
                    @endphp
                @endfor
                
                <button type="button" class="btn-submit" id="submitBtn">{{ __('dni.upload.submit') }}</button>
            </form>
        </div>
    </div>
    
    <!-- Modal de Procesamiento -->
    <div id="processingModal" class="processing-modal">
        <div class="processing-modal-content">
            <div class="processing-spinner"></div>
            <h3>Procesando Documento</h3>
            <p>Estamos analizando tu documento con IA...</p>
            <p style="font-size: var(--font-size-small); color: var(--booking-gray-medium);">
                Por favor, no cierres esta ventana
            </p>
            <div class="url-info" id="processingUrl"></div>
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
        const token = '{{ $token }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const numeroAdultos = {{ $numeroAdultos }};
        const storageKey = `dni_upload_state_${token}`;
        
        // Traducciones para JavaScript
        const translations = {
            submit: '{{ __('dni.upload.submit') }}',
            submitSaving: '{{ __('dni.upload.submit.saving') }}',
            success: '{{ __('dni.upload.success') }}',
            errorConnection: '{{ __('dni.upload.error.connection') }}',
            errorPhoneMain: '{{ __('dni.upload.error.phone.main') }}',
            errorEmailMain: '{{ __('dni.upload.error.email.main') }}',
            errorContactCompanion: '{{ __('dni.upload.error.contact.companion') }}',
            errorEmailInvalid: '{{ __('dni.upload.error.email.invalid') }}',
            errorPostalRequired: '{{ __('dni.upload.error.postal.required') }}',
            errorPostalFormat: '{{ __('dni.upload.error.postal.format') }}',
            removeImage: '{{ __('dni.upload.remove.image') }}',
            languageError: '{{ __('dni.upload.language.error') }}',
            languageErrorUnknown: '{{ __('dni.upload.language.error.unknown') }}'
        };
        
        // Sistema de persistencia en localStorage
        function saveImageState(index, type, imageData, extractedData) {
            try {
                let state = JSON.parse(localStorage.getItem(storageKey) || '{}');
                if (!state[index]) state[index] = {};
                if (!state[index][type]) state[index][type] = {};
                
                state[index][type] = {
                    imageData: imageData, // base64
                    extractedData: extractedData || {},
                    timestamp: new Date().toISOString(),
                    processed: true
                };
                
                localStorage.setItem(storageKey, JSON.stringify(state));
                console.log('💾 Estado guardado en localStorage:', { index, type });
            } catch (e) {
                console.error('Error guardando en localStorage:', e);
            }
        }
        
        function loadImageState() {
            try {
                const state = JSON.parse(localStorage.getItem(storageKey) || '{}');
                console.log('📂 Estado cargado desde localStorage:', state);
                return state;
            } catch (e) {
                console.error('Error cargando desde localStorage:', e);
                return {};
            }
        }
        
        function clearImageState(index, type) {
            try {
                let state = JSON.parse(localStorage.getItem(storageKey) || '{}');
                if (state[index] && state[index][type]) {
                    delete state[index][type];
                    if (Object.keys(state[index]).length === 0) {
                        delete state[index];
                    }
                    localStorage.setItem(storageKey, JSON.stringify(state));
                    console.log('🗑️ Estado eliminado de localStorage:', { index, type });
                }
            } catch (e) {
                console.error('Error eliminando de localStorage:', e);
            }
        }
        
        function clearAllImageState() {
            try {
                localStorage.removeItem(storageKey);
                console.log('🗑️ Todo el estado eliminado de localStorage');
            } catch (e) {
                console.error('Error eliminando todo el estado:', e);
            }
        }
        
        // Restaurar imágenes procesadas al cargar
        function restoreProcessedImages() {
            const state = loadImageState();
            
            for (let i = 0; i < numeroAdultos; i++) {
                if (!state[i]) continue;
                
                // Restaurar frontal
                if (state[i].frontal && state[i].frontal.imageData) {
                    restoreImagePreview(i, 'frontal', state[i].frontal.imageData, state[i].frontal.extractedData);
                    if (processingState[i]) {
                        processingState[i].frontal.processed = true;
                    } else {
                        processingState[i] = {
                            frontal: { processed: true, processing: false },
                            trasera: { processed: false, processing: false }
                        };
                    }
                }
                
                // Restaurar trasera
                if (state[i].trasera && state[i].trasera.imageData) {
                    restoreImagePreview(i, 'trasera', state[i].trasera.imageData, state[i].trasera.extractedData);
                    if (processingState[i]) {
                        processingState[i].trasera.processed = true;
                    } else {
                        if (!processingState[i]) {
                            processingState[i] = {
                                frontal: { processed: false, processing: false },
                                trasera: { processed: false, processing: false }
                            };
                        }
                        processingState[i].trasera.processed = true;
                    }
                }
                
                // Restaurar datos extraídos si existen
                if (state[i].frontal && state[i].frontal.extractedData) {
                    // Asegurar que extractedDataStore esté inicializado
                    if (!extractedDataStore[i]) {
                        extractedDataStore[i] = { front: {}, rear: {} };
                    }
                    extractedDataStore[i].front = { ...extractedDataStore[i].front, ...state[i].frontal.extractedData };
                    updateExtractedData(i, 'front', state[i].frontal.extractedData);
                }
                if (state[i].trasera && state[i].trasera.extractedData) {
                    // Asegurar que extractedDataStore esté inicializado
                    if (!extractedDataStore[i]) {
                        extractedDataStore[i] = { front: {}, rear: {} };
                    }
                    extractedDataStore[i].rear = { ...extractedDataStore[i].rear, ...state[i].trasera.extractedData };
                    updateExtractedData(i, 'rear', state[i].trasera.extractedData);
                }
                
                // Verificar si se puede mostrar el formulario
                // Para pasaportes: solo se necesita el frontal
                // Para DNI/NIE: se necesita frontal y trasera (aunque la trasera es opcional)
                const frontData = state[i].frontal?.extractedData || {};
                const tipoDocumento = frontData.tipo_documento || '';
                const esPasaporte = tipoDocumento.toLowerCase().includes('pasaporte') || 
                                  tipoDocumento.toLowerCase().includes('passport');
                
                if (state[i].frontal) {
                    // Si es pasaporte, mostrar formulario solo con frontal
                    if (esPasaporte) {
                        checkIfBothImagesProcessed(i);
                    } else if (state[i].trasera) {
                        // Si es DNI/NIE y tiene ambas, mostrar formulario
                        checkIfBothImagesProcessed(i);
                    } else {
                        // Si es DNI/NIE pero solo tiene frontal, también mostrar (trasera es opcional)
                        checkIfBothImagesProcessed(i);
                    }
                    
                    // Mostrar datos extraídos y verificar campos faltantes
                    setTimeout(() => {
                        displayExtractedData(i);
                        if (typeof checkMissingFields === 'function') {
                            checkMissingFields(i);
                        }
                    }, 300);
                }
            }
        }
        
        function restoreImagePreview(index, type, imageData, extractedData) {
            const imageElement = document.getElementById(type + 'Image_' + index);
            const previewContainer = document.getElementById(type + 'Preview_' + index);
            const fileNameElement = document.getElementById(type + 'Name_' + index);
            
            if (imageElement && imageData) {
                imageElement.src = imageData;
            }
            
            if (previewContainer) {
                previewContainer.classList.add('active');
                
                // Agregar indicador de éxito
                if (!previewContainer.querySelector('.process-success')) {
                    const successBadge = document.createElement('div');
                    successBadge.className = 'process-success';
                    successBadge.innerHTML = '<span style="color: green; font-weight: bold;">✓ Procesado</span>';
                    successBadge.style.position = 'absolute';
                    successBadge.style.top = '10px';
                    successBadge.style.right = '10px';
                    successBadge.style.background = 'rgba(255,255,255,0.9)';
                    successBadge.style.padding = '5px 10px';
                    successBadge.style.borderRadius = '4px';
                    successBadge.style.zIndex = '10';
                    previewContainer.style.position = 'relative';
                    previewContainer.appendChild(successBadge);
                }
                
                // Agregar botones de eliminar/reemplazar
                addImageControls(previewContainer, index, type);
            }
            
            if (fileNameElement) {
                fileNameElement.textContent = 'Imagen procesada';
            }
        }
        
        function addImageControls(container, index, type) {
            // Eliminar controles existentes
            const existingControls = container.querySelector('.image-controls');
            if (existingControls) {
                existingControls.remove();
            }
            
            // Buscar el elemento file-name donde está el texto "Imagen procesada"
            const fileNameElement = container.querySelector('.file-name');
            if (!fileNameElement) {
                console.warn('No se encontró el elemento file-name para insertar los controles');
                return;
            }
            
            // Crear contenedor de controles con mejor estilo
            const controls = document.createElement('div');
            controls.className = 'image-controls';
            controls.style.cssText = 'display: flex; justify-content: center; align-items: center; gap: 12px; margin: 12px 0; padding: 8px 0;';
            
            // Botón Eliminar con mejor estilo
            const removeBtn = document.createElement('button');
            removeBtn.innerHTML = '🗑️ Eliminar';
            removeBtn.className = 'btn-image-control btn-remove';
            removeBtn.style.cssText = 'padding: 10px 20px; background: linear-gradient(135deg, #EB5757 0%, #C94A4A 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; box-shadow: 0 2px 8px rgba(235, 87, 87, 0.3); transition: all 0.3s ease; min-width: 120px;';
            removeBtn.onmouseenter = function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(235, 87, 87, 0.4)';
            };
            removeBtn.onmouseleave = function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 8px rgba(235, 87, 87, 0.3)';
            };
            removeBtn.onclick = (e) => {
                e.stopPropagation();
                if (confirm(translations.removeImage)) {
                    removeImage(index, type);
                }
            };
            
            // Botón Reemplazar con mejor estilo
            const replaceBtn = document.createElement('button');
            replaceBtn.innerHTML = '🔄 Reemplazar';
            replaceBtn.className = 'btn-image-control btn-replace';
            replaceBtn.style.cssText = 'padding: 10px 20px; background: linear-gradient(135deg, #003580 0%, #002654 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; box-shadow: 0 2px 8px rgba(0, 53, 128, 0.3); transition: all 0.3s ease; min-width: 120px;';
            replaceBtn.onmouseenter = function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0, 53, 128, 0.4)';
            };
            replaceBtn.onmouseleave = function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 8px rgba(0, 53, 128, 0.3)';
            };
            replaceBtn.onclick = (e) => {
                e.stopPropagation();
                replaceImage(index, type);
            };
            
            controls.appendChild(removeBtn);
            controls.appendChild(replaceBtn);
            
            // Insertar los controles ANTES del texto "Imagen procesada"
            fileNameElement.parentNode.insertBefore(controls, fileNameElement);
        }
        
        function removeImage(index, type) {
            // Limpiar estado
            if (processingState[index] && processingState[index][type]) {
                processingState[index][type].processed = false;
            }
            
            // Limpiar localStorage
            clearImageState(index, type);
            
            // Limpiar datos extraídos
            if (type === 'frontal') {
                extractedDataStore[index].front = {};
            } else {
                extractedDataStore[index].rear = {};
            }
            
            // Limpiar preview
            const area = document.getElementById(type + 'Area_' + index);
            const originalHTML = area.innerHTML;
            area.innerHTML = originalHTML;
            
            // Limpiar input file
            const fileInput = document.getElementById(type + 'File_' + index);
            if (fileInput) {
                fileInput.value = '';
            }
            
            // Ocultar sección de datos adicionales si ambas están eliminadas
            if (!processingState[index].frontal.processed && !processingState[index].trasera.processed) {
                const additionalSection = document.getElementById(`additional-data-${index}`);
                if (additionalSection) {
                    additionalSection.style.display = 'none';
                }
            }
            
            showSuccess(`Imagen ${type === 'frontal' ? 'frontal' : 'trasera'} eliminada. Puedes subir una nueva.`);
        }
        
        function replaceImage(index, type) {
            // Simular click en el input file
            const fileInput = document.getElementById(type + 'File_' + index);
            if (fileInput) {
                fileInput.click();
            }
        }
        
        // Inicializar eventos para todos los inputs de archivo
        for (let i = 0; i < numeroAdultos; i++) {
            // Frontal
            const frontalFile = document.getElementById('frontalFile_' + i);
            if (frontalFile) {
                frontalFile.addEventListener('change', function(e) {
                    handleFileSelect(e.target.files[0], 'frontal', i);
                });
            }
            
            // Trasera
            const traseraFile = document.getElementById('traseraFile_' + i);
            if (traseraFile) {
                traseraFile.addEventListener('change', function(e) {
                    if (e.target.files[0]) {
                        handleFileSelect(e.target.files[0], 'trasera', i);
                    }
                });
            }
            
            // Drag and drop
            ['frontalArea', 'traseraArea'].forEach(areaType => {
                const area = document.getElementById(areaType + '_' + i);
                const fileInput = document.getElementById(areaType.replace('Area', 'File') + '_' + i);
                
                if (area && fileInput) {
                    area.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        area.classList.add('dragover');
                    });
                    
                    area.addEventListener('dragleave', function(e) {
                        e.preventDefault();
                        area.classList.remove('dragover');
                    });
                    
                    area.addEventListener('drop', function(e) {
                        e.preventDefault();
                        area.classList.remove('dragover');
                        
                        const files = e.dataTransfer.files;
                        if (files.length > 0) {
                            const fileType = areaType === 'frontalArea' ? 'frontal' : 'trasera';
                            fileInput.files = files;
                            handleFileSelect(files[0], fileType, i);
                        }
                    });
                }
            });
        }
        
        // Estado de procesamiento por persona
        const processingState = {};
        for (let i = 0; i < numeroAdultos; i++) {
            processingState[i] = {
                frontal: { processed: false, processing: false },
                trasera: { processed: false, processing: false }
            };
        }
        
        function handleFileSelect(file, type, index) {
            if (!file) return;
            
            // Validar tamaño (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showError('El archivo es demasiado grande. Máximo 5MB.');
                return;
            }
            
            // Validar tipo
            if (!file.type.match('image.*')) {
                showError('Solo se permiten imágenes.');
                return;
            }
            
            // Mostrar preview
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const imageElement = document.getElementById(type + 'Image_' + index);
                    const previewContainer = document.getElementById(type + 'Preview_' + index);
                    const fileNameElement = document.getElementById(type + 'Name_' + index);
                    
                    if (imageElement) {
                        imageElement.src = e.target.result;
                    } else {
                        console.warn('Elemento de imagen no encontrado al mostrar preview:', type + 'Image_' + index);
                    }
                    
                    if (previewContainer) {
                        previewContainer.classList.add('active');
                    } else {
                        console.warn('Contenedor de preview no encontrado:', type + 'Preview_' + index);
                    }
                    
                    if (fileNameElement) {
                        fileNameElement.textContent = file.name;
                    } else {
                        console.warn('Elemento de nombre de archivo no encontrado:', type + 'Name_' + index);
                    }
                } catch (error) {
                    console.error('Error mostrando preview de imagen:', error);
                }
            };
            reader.onerror = function(error) {
                console.error('Error leyendo archivo para preview:', error);
            };
            reader.readAsDataURL(file);
            
            // Procesar imagen inmediatamente
            processImageImmediately(file, type, index);
        }
        
        async function processImageImmediately(file, type, index) {
            // Validar que el estado existe
            if (!processingState[index]) {
                processingState[index] = {
                    frontal: { processed: false, processing: false },
                    trasera: { processed: false, processing: false }
                };
            }
            
            // Marcar como procesando
            if (processingState[index][type]) {
                processingState[index][type].processing = true;
            } else {
                console.error('Tipo de imagen no reconocido:', type);
                return;
            }
            
            // Obtener datos de la persona
            const personaTipo = document.querySelector(`input[name="persona_tipo[${index}]"]`).value;
            const personaId = document.querySelector(`input[name="persona_id[${index}]"]`)?.value || null;
            
            // Mostrar indicador de carga en el área
            const area = document.getElementById(type + 'Area_' + index);
            const originalHTML = area.innerHTML;
            area.innerHTML = '<div style="text-align: center; padding: 20px;"><div class="spinner-border text-primary" role="status"><span class="sr-only">Procesando...</span></div><p style="margin-top: 10px;">Procesando imagen con IA...</p></div>';
            
            // Mostrar modal bloqueante
            const processingModal = document.getElementById('processingModal');
            const processingStatus = document.getElementById('processingStatus');
            const processingUrl = document.getElementById('processingUrl');
            const apiUrl = `/dni-scanner/${token}/process-single-image`;
            
            // Mostrar modal y actualizar estado
            processingModal.style.display = 'flex';
            if (processingStatus) {
                processingStatus.textContent = `Procesando ${type === 'frontal' ? 'imagen frontal' : 'imagen trasera'}...`;
            }
            if (processingUrl) {
                processingUrl.textContent = `URL: ${apiUrl}`;
            }
            
            // Bloquear interacción con el resto de la página
            document.body.style.overflow = 'hidden';
            document.body.style.pointerEvents = 'none';
            processingModal.style.pointerEvents = 'auto';
            
            // Preparar FormData
            const formData = new FormData();
            formData.append('image', file);
            formData.append('side', type === 'frontal' ? 'front' : 'rear');
            formData.append('persona_index', index);
            formData.append('persona_tipo', personaTipo);
            if (personaId) {
                formData.append('persona_id', personaId);
            }
            
            console.log('📤 Enviando imagen a IA:', {
                url: apiUrl,
                side: type === 'frontal' ? 'front' : 'rear',
                personaTipo: personaTipo,
                personaId: personaId,
                fileName: file.name,
                fileSize: file.size
            });
            
            if (processingStatus) {
                processingStatus.textContent = 'Enviando imagen a la IA...';
            }
            
            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });
                
                console.log('📥 Respuesta recibida:', {
                    status: response.status,
                    statusText: response.statusText,
                    ok: response.ok,
                    headers: Object.fromEntries(response.headers.entries())
                });
                
                if (processingStatus) {
                    processingStatus.textContent = 'Analizando documento con IA...';
                }
                
                const result = await response.json();
                
                console.log('📋 Resultado completo:', result);
                console.log('🔗 URL de la petición:', window.location.origin + apiUrl);
                
                // Ocultar modal
                processingModal.style.display = 'none';
                document.body.style.overflow = '';
                document.body.style.pointerEvents = '';
                
                if (result.success) {
                    console.log('✅ Procesamiento exitoso');
                    // Marcar como procesado
                    if (processingState[index] && processingState[index][type]) {
                        processingState[index][type].processed = true;
                        processingState[index][type].processing = false;
                    }
                    
                    // Restaurar preview con check de éxito
                    area.innerHTML = originalHTML;
                    
                    // Esperar a que el DOM se actualice antes de acceder a los elementos
                    setTimeout(() => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            try {
                                const imageElement = document.getElementById(type + 'Image_' + index);
                                const previewContainer = document.getElementById(type + 'Preview_' + index);
                                const fileNameElement = document.getElementById(type + 'Name_' + index);
                                
                                if (imageElement) {
                                    imageElement.src = e.target.result;
                                } else {
                                    console.warn('Elemento de imagen no encontrado:', type + 'Image_' + index);
                                }
                                
                                if (previewContainer) {
                                    previewContainer.classList.add('active');
                                    
                                    // Agregar indicador de éxito
                                    if (!previewContainer.querySelector('.process-success')) {
                                        const successBadge = document.createElement('div');
                                        successBadge.className = 'process-success';
                                        successBadge.innerHTML = '<span style="color: green; font-weight: bold;">✓ Procesado</span>';
                                        successBadge.style.position = 'absolute';
                                        successBadge.style.top = '10px';
                                        successBadge.style.right = '10px';
                                        successBadge.style.background = 'rgba(255,255,255,0.9)';
                                        successBadge.style.padding = '5px 10px';
                                        successBadge.style.borderRadius = '4px';
                                        successBadge.style.zIndex = '10';
                                        previewContainer.style.position = 'relative';
                                        previewContainer.appendChild(successBadge);
                                    }
                                } else {
                                    console.warn('Contenedor de preview no encontrado:', type + 'Preview_' + index);
                                }
                                
                                if (fileNameElement) {
                                    fileNameElement.textContent = file.name;
                                } else {
                                    console.warn('Elemento de nombre de archivo no encontrado:', type + 'Name_' + index);
                                }
                            } catch (domError) {
                                console.error('Error actualizando DOM después de procesar:', domError);
                            }
                        };
                        reader.onerror = function(error) {
                            console.error('Error leyendo archivo:', error);
                        };
                        reader.readAsDataURL(file);
                    }, 150);
                    
                    // Guardar imagen en localStorage para persistencia
                    const reader2 = new FileReader();
                    reader2.onload = function(e) {
                        // Guardar estado en localStorage
                        saveImageState(index, type, e.target.result, result.extracted_data);
                    };
                    reader2.readAsDataURL(file);
                    
                    // Actualizar campos con datos extraídos
                    if (result.extracted_data) {
                        const side = type === 'frontal' ? 'front' : 'rear';
                        console.log('📊 Datos extraídos recibidos del backend:', {
                            side: side,
                            data: result.extracted_data,
                            tiene_lugar_nacimiento: !!result.extracted_data.lugar_nacimiento
                        });
                        updateExtractedData(index, side, result.extracted_data);
                    }
                    
                    // Agregar botones de control a la imagen
                    setTimeout(() => {
                        const previewContainer = document.getElementById(type + 'Preview_' + index);
                        if (previewContainer) {
                            addImageControls(previewContainer, index, type);
                        }
                    }, 200);
                    
                    // Verificar tipo de documento
                    const frontData = extractedDataStore[index]?.front || {};
                    const tipoDocumento = frontData.tipo_documento || '';
                    const esPasaporte = tipoDocumento.toLowerCase().includes('pasaporte') || 
                                      tipoDocumento.toLowerCase().includes('passport');
                    
                    // Para pasaportes: mostrar formulario solo con frontal procesado
                    // Para DNI/NIE: esperar a que se procese también la trasera (si se sube)
                    if (esPasaporte) {
                        // Pasaporte: mostrar formulario inmediatamente
                        checkIfBothImagesProcessed(index);
                    } else {
                        // DNI/NIE: verificar si ambas están procesadas
                        checkIfBothImagesProcessed(index);
                    }
                    
                    showSuccess(`Imagen ${type === 'frontal' ? 'frontal' : 'trasera'} procesada correctamente`);
                } else {
                    // Ocultar modal antes de mostrar error
                    processingModal.style.display = 'none';
                    document.body.style.overflow = '';
                    document.body.style.pointerEvents = '';
                    
                    console.error('❌ Error en procesamiento:', {
                        message: result.message,
                        error_type: result.error_type,
                        error: result.error,
                        full_result: result,
                        url_peticion: window.location.origin + apiUrl
                    });
                    
                    // Mostrar respuesta RAW de la IA Hawkins en consola
                    console.error('🔴 RESPUESTA RAW DE LA IA HAWKINS:', {
                        'URL de la IA': result.ai_url || 'N/A',
                        'HTTP Code de la IA': result.ai_http_code || 'N/A',
                        'Respuesta RAW completa': result.ai_raw_response || 'N/A',
                        'Respuesta parseada': result.ai_response_parsed || 'N/A',
                        'Error Type': result.error_type,
                        'Error': result.error,
                        'Respuesta completa del servidor': result
                    });
                    
                    // Si hay respuesta RAW, mostrarla de forma destacada
                    if (result.ai_raw_response) {
                        console.group('📡 RESPUESTA COMPLETA DE LA IA HAWKINS');
                        console.log('URL:', result.ai_url);
                        console.log('HTTP Code:', result.ai_http_code);
                        console.log('Respuesta RAW:', result.ai_raw_response);
                        if (result.ai_response_parsed) {
                            console.log('Respuesta parseada:', result.ai_response_parsed);
                        }
                        console.groupEnd();
                    }
                    
                    // Mostrar error completo en consola
                    console.error('🔴 DETALLES COMPLETOS DEL ERROR:', {
                        'URL Laravel': window.location.origin + apiUrl,
                        'URL IA Hawkins': result.ai_url || 'N/A',
                        'Status HTTP Laravel': response?.status,
                        'Status HTTP IA': result.ai_http_code || 'N/A',
                        'Status Text': response?.statusText,
                        'Error Type': result.error_type,
                        'Mensaje': result.message,
                        'Error completo': result.error,
                        'Resultado completo': result
                    });
                    
                    if (processingState[index] && processingState[index][type]) {
                        processingState[index][type].processing = false;
                    }
                    
                    // Restaurar área pero mantener opción de reintentar
                    area.innerHTML = originalHTML;
                    
                    // Agregar botón de reintentar al área
                    const retryBtn = document.createElement('button');
                    retryBtn.innerHTML = '🔄 Reintentar';
                    retryBtn.style.cssText = 'margin-top: 10px; padding: 10px 20px; background: #003580; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;';
                    retryBtn.onclick = () => {
                        const fileInput = document.getElementById(type + 'File_' + index);
                        if (fileInput) {
                            fileInput.click();
                        }
                    };
                    area.appendChild(retryBtn);
                    
                    // Mostrar mensaje de error con opción de WhatsApp
                    let errorMessage = result.message || 'Error procesando la imagen';
                    
                    // Agregar detalles del error en consola Y en el mensaje
                    if (result.error) {
                        console.error('🔴 Error detallado:', result.error);
                        errorMessage += '<br><br><strong>Detalles del error:</strong><br>';
                        errorMessage += '<code style="background: #f5f5f5; padding: 5px; border-radius: 4px; font-size: 12px; display: block; margin-top: 10px;">' + 
                                       (typeof result.error === 'string' ? result.error : JSON.stringify(result.error, null, 2)) + 
                                       '</code>';
                    }
                    
                    // Mostrar URLs y respuesta RAW de la IA en el mensaje de error
                    errorMessage += '<br><br><strong>URLs y Respuesta de la IA:</strong><br>';
                    errorMessage += '<code style="background: #f5f5f5; padding: 5px; border-radius: 4px; font-size: 12px; display: block; margin-top: 10px; word-break: break-all; max-height: 200px; overflow-y: auto;">';
                    errorMessage += '<strong>URL Laravel:</strong> ' + (window.location.origin + apiUrl) + '<br><br>';
                    if (result.ai_url) {
                        errorMessage += '<strong>URL IA Hawkins:</strong> ' + result.ai_url + '<br>';
                        if (result.ai_http_code) {
                            errorMessage += '<strong>HTTP Code IA:</strong> ' + result.ai_http_code + '<br>';
                        }
                        errorMessage += '<br>';
                    }
                    if (result.ai_raw_response) {
                        errorMessage += '<strong>Respuesta RAW de la IA:</strong><br>';
                        errorMessage += '<pre style="background: #f0f0f0; padding: 10px; border-radius: 4px; margin-top: 5px; white-space: pre-wrap; word-break: break-all; font-size: 11px;">' + 
                                       (typeof result.ai_raw_response === 'string' ? result.ai_raw_response : JSON.stringify(result.ai_raw_response, null, 2)) + 
                                       '</pre>';
                    }
                    if (result.ai_response_parsed) {
                        errorMessage += '<br><strong>Respuesta parseada de la IA:</strong><br>';
                        errorMessage += '<pre style="background: #f0f0f0; padding: 10px; border-radius: 4px; margin-top: 5px; white-space: pre-wrap; word-break: break-all; font-size: 11px;">' + 
                                       JSON.stringify(result.ai_response_parsed, null, 2) + 
                                       '</pre>';
                    }
                    errorMessage += '</code>';
                    
                    if (result.error_type === 'invalid_data' || result.error_type === 'ai_error' || result.error_type === 'ai_not_configured' || result.error_type === 'connection_error' || result.error_type === 'parse_error' || result.error_type === 'ai_not_implemented') {
                        errorMessage += '<br><br>';
                        errorMessage += '<strong>Opciones:</strong><br>';
                        errorMessage += '1. Puedes hacer clic en "Reintentar" para volver a intentar<br>';
                        errorMessage += '2. Si el problema persiste, envía las imágenes del DNI por WhatsApp al +34 605 37 93 29 y las procesaremos manualmente';
                        
                        // Mostrar botón de WhatsApp
                        errorMessage += '<br><br>';
                        errorMessage += '<a href="https://wa.me/34605379329?text=' + encodeURIComponent('Hola, tengo problemas al subir mi DNI. Mi código de reserva es: ' + token) + '" target="_blank" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background: #25D366; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">📱 Enviar por WhatsApp</a>';
                    }
                    
                    showError(errorMessage);
                }
            } catch (error) {
                console.error('❌ Error de excepción:', {
                    error: error,
                    message: error.message,
                    stack: error.stack,
                    url_peticion: window.location.origin + apiUrl
                });
                
                // Mostrar error completo en consola
                console.error('🚨 ERROR EXCEPCIONAL COMPLETO:', {
                    'URL de la petición': window.location.origin + apiUrl,
                    'Tipo de error': error.name,
                    'Mensaje': error.message,
                    'Stack trace': error.stack,
                    'Error completo': error
                });
                
                // Ocultar modal
                processingModal.style.display = 'none';
                document.body.style.overflow = '';
                document.body.style.pointerEvents = '';
                
                if (processingState[index] && processingState[index][type]) {
                    processingState[index][type].processing = false;
                }
                area.innerHTML = originalHTML;
                
                let errorMessage = 'Error de conexión al procesar la imagen';
                if (error.message) {
                    errorMessage += '<br><br><strong>Error:</strong><br>';
                    errorMessage += '<code style="background: #f5f5f5; padding: 5px; border-radius: 4px; font-size: 12px; display: block; margin-top: 10px;">' + error.message + '</code>';
                }
                
                // Mostrar URL de la petición
                errorMessage += '<br><br><strong>URL de la petición:</strong><br>';
                errorMessage += '<code style="background: #f5f5f5; padding: 5px; border-radius: 4px; font-size: 12px; display: block; margin-top: 10px; word-break: break-all;">' + 
                               (window.location.origin + apiUrl) + 
                               '</code>';
                
                // Mostrar también la URL de la IA en el modal si está disponible
                if (processingUrl) {
                    processingUrl.textContent = `URL Laravel: ${window.location.origin + apiUrl}`;
                }
                
                showError(errorMessage);
            }
        }
        
        // Almacenar datos extraídos para mostrar después
        const extractedDataStore = {};
        for (let i = 0; i < numeroAdultos; i++) {
            extractedDataStore[i] = { front: {}, rear: {} };
        }
        
        function updateExtractedData(index, side, data) {
            console.log('Actualizando datos extraídos:', { index, side, data });
            
            // Guardar datos en el store
            if (data) {
                extractedDataStore[index][side] = { ...extractedDataStore[index][side], ...data };
                
                // IMPORTANTE: El lugar de nacimiento viene del reverso pero debe mostrarse con los datos del frontal
                if (side === 'rear' && data.lugar_nacimiento) {
                    console.log('📍 Lugar de nacimiento extraído del reverso:', data.lugar_nacimiento);
                    // Actualizar el store del frontal para que se muestre correctamente
                    if (!extractedDataStore[index].front) {
                        extractedDataStore[index].front = {};
                    }
                    extractedDataStore[index].front.lugar_nacimiento = data.lugar_nacimiento;
                }
            }
            
            if (side === 'front') {
                // Actualizar nombre en la sección de la persona correspondiente
                const personSections = document.querySelectorAll('.person-section');
                if (personSections[index]) {
                    const personName = personSections[index].querySelector('.person-name');
                    if (personName && data.nombre) {
                        const name = (data.nombre || '') + ' ' + (data.apellido1 || data.primer_apellido || '') + ' ' + (data.apellido2 || data.segundo_apellido || '');
                        personName.textContent = name.trim();
                    }
                }
                
                // Mostrar datos extraídos en la sección de visualización
                displayExtractedData(index);
            } else if (side === 'rear') {
                // Actualizar campos ocultos de dirección (se guardan automáticamente)
                if (data.direccion) {
                    const direccionInput = document.getElementById(`hidden_direccion_${index}`);
                    if (direccionInput) {
                        direccionInput.value = data.direccion;
                    }
                }
                if (data.localidad) {
                    const localidadInput = document.getElementById(`hidden_localidad_${index}`);
                    if (localidadInput) {
                        localidadInput.value = data.localidad;
                    }
                }
                if (data.codigo_postal) {
                    const codigoInput = document.getElementById(`hidden_codigo_postal_${index}`);
                    if (codigoInput) {
                        codigoInput.value = data.codigo_postal;
                    }
                }
                if (data.provincia) {
                    const provinciaInput = document.getElementById(`hidden_provincia_${index}`);
                    if (provinciaInput) {
                        provinciaInput.value = data.provincia;
                    }
                }
                
                // Actualizar visualización de datos extraídos (incluye lugar de nacimiento si está disponible)
                displayExtractedData(index);
                
                // Verificar qué campos faltan después de procesar el reverso
                checkMissingFields(index);
            }
        }
        
        function displayExtractedData(index) {
            const contentDiv = document.getElementById(`extracted-data-content-${index}`);
            if (!contentDiv) return;
            
            const frontData = extractedDataStore[index].front;
            const rearData = extractedDataStore[index].rear;
            
            let html = '<div style="display: grid; gap: var(--spacing-xs);">';
            
            // Datos del frontal
            if (frontData.nombre || frontData.apellido1 || frontData.primer_apellido) {
                html += '<div><strong>Nombre:</strong> ' + (frontData.nombre || '') + ' ' + (frontData.apellido1 || frontData.primer_apellido || '') + ' ' + (frontData.apellido2 || frontData.segundo_apellido || '') + '</div>';
            }
            if (frontData.num_identificacion || frontData.numero_identificacion) {
                html += '<div><strong>DNI/NIE:</strong> ' + (frontData.num_identificacion || frontData.numero_identificacion || '') + '</div>';
            }
            if (frontData.fecha_nacimiento) {
                html += '<div><strong>Fecha de Nacimiento:</strong> ' + frontData.fecha_nacimiento + '</div>';
            }
            if (frontData.lugar_nacimiento) {
                html += '<div><strong>Lugar de Nacimiento:</strong> ' + frontData.lugar_nacimiento + '</div>';
            }
            if (frontData.sexo) {
                html += '<div><strong>Sexo:</strong> ' + frontData.sexo + '</div>';
            }
            if (frontData.nacionalidad) {
                html += '<div><strong>Nacionalidad:</strong> ' + frontData.nacionalidad + '</div>';
            }
            if (frontData.fecha_expedicion_doc || frontData.fecha_expedicion) {
                html += '<div><strong>Fecha de Expedición:</strong> ' + (frontData.fecha_expedicion_doc || frontData.fecha_expedicion || '') + '</div>';
            }
            if (frontData.fecha_caducidad) {
                const fechaCad = new Date(frontData.fecha_caducidad);
                const hoy = new Date();
                const caducado = fechaCad < hoy;
                const style = caducado ? 'color: red; font-weight: bold;' : '';
                html += '<div><strong>Fecha de Caducidad:</strong> <span style="' + style + '">' + frontData.fecha_caducidad + (caducado ? ' (CADUCADO)' : '') + '</span></div>';
            }
            if (frontData.tipo_documento) {
                html += '<div><strong>Tipo de Documento:</strong> ' + frontData.tipo_documento + '</div>';
            }
            
            // Datos del reverso (dirección) - se muestran en datos extraídos
            if (rearData.direccion || rearData.localidad || rearData.codigo_postal || rearData.provincia) {
                html += '<div style="margin-top: var(--spacing-sm); padding-top: var(--spacing-sm); border-top: 1px solid var(--booking-gray-light);"><strong>Dirección:</strong></div>';
                if (rearData.direccion) {
                    html += '<div><strong>Calle:</strong> ' + rearData.direccion + '</div>';
                }
                if (rearData.localidad || rearData.codigo_postal || rearData.provincia) {
                    const addressParts = [rearData.localidad, rearData.codigo_postal, rearData.provincia].filter(Boolean);
                    if (addressParts.length > 0) {
                        html += '<div>' + addressParts.join(', ') + '</div>';
                    }
                }
            }
            
            html += '</div>';
            contentDiv.innerHTML = html;
            
            // Verificar campos faltantes después de mostrar datos extraídos
            if (typeof checkMissingFields === 'function') {
                checkMissingFields(index);
            }
        }
        
        // Verificar campos faltantes y mostrarlos en datos adicionales
        function checkMissingFields(index) {
            const frontData = extractedDataStore[index]?.front || {};
            const rearData = extractedDataStore[index]?.rear || {};
            
            // Verificar tipo de documento
            const tipoDocumento = frontData.tipo_documento || '';
            const esPasaporte = tipoDocumento.toLowerCase().includes('pasaporte') || 
                              tipoDocumento.toLowerCase().includes('passport');
            
            // Campos que pueden faltar
            const faltantes = {
                direccion: !rearData.direccion || rearData.direccion.trim() === '',
                localidad: !rearData.localidad || rearData.localidad.trim() === '',
                codigo_postal: !rearData.codigo_postal || rearData.codigo_postal.trim() === '',
                provincia: !rearData.provincia || rearData.provincia.trim() === '',
                lugar_nacimiento: !frontData.lugar_nacimiento || frontData.lugar_nacimiento.trim() === ''
            };
            
            // Si es pasaporte, es más probable que falten datos de dirección
            // Si es DNI pero no se procesó el reverso, también pueden faltar
            const addressFieldsContainer = document.getElementById(`addressFields_${index}`);
            if (addressFieldsContainer) {
                // Limpiar campos existentes
                addressFieldsContainer.innerHTML = '';
                addressFieldsContainer.style.display = 'none';
                
                // Si falta algún campo de dirección, mostrarlos
                if (faltantes.direccion || faltantes.localidad || faltantes.codigo_postal || faltantes.provincia) {
                    addressFieldsContainer.style.display = 'block';
                    
                    let html = '<div style="margin-top: var(--spacing-md); padding-top: var(--spacing-md); border-top: 1px solid var(--booking-gray-light);">';
                    html += '<div style="font-weight: bold; margin-bottom: var(--spacing-sm); color: var(--booking-gray-dark);">📍 Dirección (no se pudo extraer del documento)</div>';
                    
                    if (faltantes.direccion) {
                        html += `
                            <div class="form-group">
                                <label class="form-label">
                                    Dirección <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       class="form-input" 
                                       name="direccion_manual[${index}]" 
                                       id="direccion_manual_${index}"
                                       placeholder="Calle, número, piso, puerta"
                                       required>
                                <div class="form-help">Dirección completa (calle, número, piso, puerta)</div>
                            </div>
                        `;
                    }
                    
                    if (faltantes.localidad) {
                        html += `
                            <div class="form-group">
                                <label class="form-label">
                                    Localidad <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       class="form-input" 
                                       name="localidad_manual[${index}]" 
                                       id="localidad_manual_${index}"
                                       placeholder="Ciudad"
                                       required>
                                <div class="form-help">Ciudad o localidad</div>
                            </div>
                        `;
                    }
                    
                    if (faltantes.codigo_postal) {
                        // Obtener valor del campo hidden si existe
                        const codigoHidden = document.getElementById(`hidden_codigo_postal_${index}`);
                        const codigoValue = codigoHidden && codigoHidden.value ? codigoHidden.value : '';
                        
                        html += `
                            <div class="form-group">
                                <label class="form-label">
                                    Código Postal <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       class="form-input" 
                                       name="codigo_postal_manual[${index}]" 
                                       id="codigo_postal_manual_${index}"
                                       value="${codigoValue}"
                                       placeholder="41001"
                                       pattern="[0-9]{5}"
                                       maxlength="5"
                                       required>
                                <div class="form-help">Código postal (5 dígitos) - Obligatorio según Real Decreto 933/2021 para todos los huéspedes</div>
                            </div>
                        `;
                    }
                    
                    if (faltantes.provincia) {
                        html += `
                            <div class="form-group">
                                <label class="form-label">
                                    Provincia <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       class="form-input" 
                                       name="provincia_manual[${index}]" 
                                       id="provincia_manual_${index}"
                                       placeholder="Sevilla"
                                       required>
                                <div class="form-help">Provincia</div>
                            </div>
                        `;
                    }
                    
                    html += '</div>';
                    addressFieldsContainer.innerHTML = html;
                }
                
                // Si falta lugar de nacimiento, mostrarlo también
                if (faltantes.lugar_nacimiento) {
                    const lugarNacimientoContainer = document.getElementById(`lugar_nacimiento_manual_${index}`);
                    if (!lugarNacimientoContainer) {
                        const lugarNacimientoHtml = `
                            <div class="form-group" id="lugar_nacimiento_manual_${index}">
                                <label class="form-label">
                                    Lugar de Nacimiento
                                </label>
                                <input type="text" 
                                       class="form-input" 
                                       name="lugar_nacimiento_manual[${index}]" 
                                       id="lugar_nacimiento_manual_input_${index}"
                                       placeholder="Ciudad, Provincia, País">
                                <div class="form-help">Lugar de nacimiento (no se pudo extraer del documento - opcional pero recomendado)</div>
                            </div>
                        `;
                        if (addressFieldsContainer) {
                            addressFieldsContainer.insertAdjacentHTML('beforeend', lugarNacimientoHtml);
                        }
                    }
                }
                
                // Pre-rellenar campos manuales con valores de campos hidden después de crearlos
                setTimeout(() => {
                    prefillManualFieldsForIndex(index);
                }, 100);
            }
        }
        
        // Función para pre-rellenar campos manuales de un índice específico
        function prefillManualFieldsForIndex(index) {
            const codigoManual = document.getElementById(`codigo_postal_manual_${index}`);
            const codigoHidden = document.getElementById(`hidden_codigo_postal_${index}`);
            if (codigoManual && codigoHidden && codigoHidden.value && !codigoManual.value) {
                codigoManual.value = codigoHidden.value;
                console.log(`📝 Prellenado código postal manual ${index} con valor: ${codigoHidden.value}`);
            }
            
            const localidadManual = document.getElementById(`localidad_manual_${index}`);
            const localidadHidden = document.getElementById(`hidden_localidad_${index}`);
            if (localidadManual && localidadHidden && localidadHidden.value && !localidadManual.value) {
                localidadManual.value = localidadHidden.value;
            }
            
            const direccionManual = document.getElementById(`direccion_manual_${index}`);
            const direccionHidden = document.getElementById(`hidden_direccion_${index}`);
            if (direccionManual && direccionHidden && direccionHidden.value && !direccionManual.value) {
                direccionManual.value = direccionHidden.value;
            }
            
            const provinciaManual = document.getElementById(`provincia_manual_${index}`);
            const provinciaHidden = document.getElementById(`hidden_provincia_${index}`);
            if (provinciaManual && provinciaHidden && provinciaHidden.value && !provinciaManual.value) {
                provinciaManual.value = provinciaHidden.value;
            }
        }
        
        function checkIfBothImagesProcessed(index) {
            const state = processingState[index];
            const frontProcessed = state ? state.frontal.processed : false;
            const rearProcessed = state ? state.trasera.processed : false;
            
            // Verificar tipo de documento para determinar si se requiere la trasera
            const frontData = extractedDataStore[index]?.front || {};
            const tipoDocumento = frontData.tipo_documento || '';
            const esPasaporte = tipoDocumento.toLowerCase().includes('pasaporte') || 
                              tipoDocumento.toLowerCase().includes('passport');
            
            console.log('Verificando imágenes procesadas:', { 
                index, 
                state, 
                frontProcessed, 
                rearProcessed,
                tipoDocumento,
                esPasaporte
            });
            
            // Para pasaporte: solo se necesita el frontal procesado
            // Para DNI/NIE: se necesita frontal procesado (la trasera es opcional pero recomendada)
            const puedeMostrarFormulario = frontProcessed; // Siempre mostrar si el frontal está procesado
            
            // Mostrar sección de datos adicionales si el frontal está procesado
            // Y si es pasaporte, ya está listo; si es DNI, también debe estar la trasera
            const personSections = document.querySelectorAll('.person-section');
            console.log('Secciones de persona encontradas:', personSections.length);
            
            if (personSections[index] && state && puedeMostrarFormulario) {
                const additionalSection = personSections[index].querySelector('.additional-data-section');
                console.log('Sección adicional encontrada:', additionalSection);
                
                if (additionalSection) {
                    additionalSection.style.display = 'block';
                    additionalSection.style.opacity = '1';
                    additionalSection.style.transition = 'opacity 0.3s';
                    additionalSection.style.visibility = 'visible';
                    
                    // Si es pasaporte, ocultar o deshabilitar el campo de imagen trasera
                    if (esPasaporte) {
                        const traseraArea = document.getElementById(`traseraArea_${index}`);
                        const traseraLabel = traseraArea?.previousElementSibling;
                        if (traseraArea && traseraLabel) {
                            traseraArea.style.opacity = '0.5';
                            traseraArea.style.pointerEvents = 'none';
                            traseraArea.title = 'No se requiere imagen trasera para pasaporte';
                            if (traseraLabel.tagName === 'LABEL') {
                                traseraLabel.innerHTML = 'Imagen Trasera <span style="color: var(--booking-gray-medium); font-size: var(--font-size-xsmall);">(No requerida para pasaporte)</span>';
                            }
                        }
                    }
                    
                    // Scroll suave a la sección
                    setTimeout(() => {
                        additionalSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 300);
                } else {
                    console.error('No se encontró la sección de datos adicionales para el índice:', index);
                }
            } else {
                console.log('Condiciones no cumplidas:', {
                    hasSection: !!personSections[index],
                    hasState: !!state,
                    frontProcessed,
                    rearProcessed,
                    esPasaporte,
                    puedeMostrarFormulario
                });
            }
        }
        
        // Función para eliminar required de inputs de archivo
        function removeRequiredFromFileInputs() {
            for (let i = 0; i < numeroAdultos; i++) {
                const frontalFile = document.getElementById(`frontalFile_${i}`);
                const traseraFile = document.getElementById(`traseraFile_${i}`);
                if (frontalFile) {
                    frontalFile.removeAttribute('required');
                    frontalFile.required = false;
                }
                if (traseraFile) {
                    traseraFile.removeAttribute('required');
                    traseraFile.required = false;
                }
            }
        }
        
        // Eliminar required inmediatamente
        removeRequiredFromFileInputs();
        
        // También eliminar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', removeRequiredFromFileInputs);
        } else {
            removeRequiredFromFileInputs();
        }
        
        // También prevenir cualquier submit del formulario
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                console.log('⚠️ Submit del formulario interceptado, previniendo...');
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }, true); // Usar capture phase para interceptar antes
        }
        
        // Guardar datos - Usar click del botón en lugar de submit del formulario
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn) {
            console.error('❌ Botón submitBtn no encontrado');
        } else {
            submitBtn.addEventListener('click', async function(e) {
                console.log('🔵 Botón Guardar Datos clickeado');
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Asegurar que los inputs de archivo no tienen required
                removeRequiredFromFileInputs();
                
                // Verificar que se eliminaron correctamente
                for (let i = 0; i < numeroAdultos; i++) {
                    const frontalFile = document.getElementById(`frontalFile_${i}`);
                    const traseraFile = document.getElementById(`traseraFile_${i}`);
                    if (frontalFile && (frontalFile.hasAttribute('required') || frontalFile.required)) {
                        console.warn(`⚠️ Input frontalFile_${i} todavía tiene required, eliminando...`);
                        frontalFile.removeAttribute('required');
                        frontalFile.required = false;
                    }
                    if (traseraFile && (traseraFile.hasAttribute('required') || traseraFile.required)) {
                        console.warn(`⚠️ Input traseraFile_${i} todavía tiene required, eliminando...`);
                        traseraFile.removeAttribute('required');
                        traseraFile.required = false;
                    }
                }
                
                console.log('🔵 Validaciones de archivos eliminadas, continuando con validación de datos...');
            
            // Validar que todos los frontales estén procesados
            // Para pasaportes, solo se requiere el frontal; para DNI/NIE, se recomienda también la trasera
            let allFrontalsProcessed = true;
            let missingFrontals = [];
            
            for (let i = 0; i < numeroAdultos; i++) {
                if (!processingState[i] || !processingState[i].frontal || !processingState[i].frontal.processed) {
                    allFrontalsProcessed = false;
                    missingFrontals.push(i + 1);
                }
            }
            
            if (!allFrontalsProcessed) {
                const personaNombre = missingFrontals.length === 1 ? 
                    (missingFrontals[0] === 1 ? 'Cliente Principal' : `Adulto ${missingFrontals[0]}`) :
                    'algunas personas';
                showError(`Por favor, espera a que se procesen todas las imágenes frontales antes de continuar. Falta procesar la imagen frontal de ${personaNombre}.`);
                return;
            }
            
            // Verificar tipo de documento para cada persona
            // Para pasaportes, no se requiere la trasera; para DNI/NIE es opcional pero recomendada
            for (let i = 0; i < numeroAdultos; i++) {
                const frontData = extractedDataStore[i]?.front || {};
                const tipoDocumento = frontData.tipo_documento || '';
                const esPasaporte = tipoDocumento.toLowerCase().includes('pasaporte') || 
                                  tipoDocumento.toLowerCase().includes('passport');
                
                if (!esPasaporte) {
                    // Para DNI/NIE, advertir si falta la trasera (pero no bloquear)
                    const rearProcessed = processingState[i]?.trasera?.processed || false;
                    if (!rearProcessed) {
                        console.warn(`Advertencia: No se ha procesado la imagen trasera para el Adulto ${i + 1} (DNI/NIE). Se recomienda subirla para completar todos los datos.`);
                    }
                }
            }
            
            // Validar datos de contacto
            // Cliente principal (índice 0): ambos campos obligatorios
            // Acompañantes (índice > 0): al menos uno de los dos según Real Decreto 933/2021
            for (let i = 0; i < numeroAdultos; i++) {
                const telefonoInput = document.querySelector(`input[name="telefono_movil[${i}]"]`);
                const emailInput = document.querySelector(`input[name="email[${i}]"]`);
                
                const telefono = telefonoInput ? telefonoInput.value.trim() : '';
                const email = emailInput ? emailInput.value.trim() : '';
                
                if (i === 0) {
                    // Cliente principal: ambos campos obligatorios
                    if (!telefono) {
                        showError(translations.errorPhoneMain);
                        return;
                    }
                    if (!email) {
                        showError(translations.errorEmailMain);
                        return;
                    }
                } else {
                    // Acompañantes: al menos uno de los dos
                    if (!telefono && !email) {
                        showError(translations.errorContactCompanion.replace(':number', i + 1));
                        return;
                    }
                }
                
                // Validar formato de email si se proporciona
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    const personaNombre = i === 0 ? 'Cliente Principal' : `Adulto ${i + 1}`;
                    showError(translations.errorEmailInvalid.replace(':person', personaNombre));
                    return;
                }
                
                // Validar código postal (obligatorio según Real Decreto 933/2021)
                const codigoHidden = document.getElementById(`hidden_codigo_postal_${i}`);
                const codigoManual = document.getElementById(`codigo_postal_manual_${i}`);
                const codigoPostal = codigoManual && codigoManual.value ? codigoManual.value : (codigoHidden ? codigoHidden.value : '');
                if (!codigoPostal || codigoPostal.trim() === '') {
                    const personaNombre = i === 0 ? 'Cliente Principal' : `Adulto ${i + 1}`;
                    showError(translations.errorPostalRequired.replace(':person', personaNombre));
                    return;
                }
                
                // Validar formato de código postal (5 dígitos)
                if (codigoPostal && !/^\d{5}$/.test(codigoPostal)) {
                    const personaNombre = i === 0 ? 'Cliente Principal' : `Adulto ${i + 1}`;
                    showError(translations.errorPostalFormat.replace(':person', personaNombre));
                    return;
                }
            }
            
            // Recopilar solo los datos adicionales (las imágenes ya están procesadas)
            const formData = new FormData();
            
            // Agregar datos adicionales
            for (let i = 0; i < numeroAdultos; i++) {
                formData.append('persona_tipo[' + i + ']', document.querySelector(`input[name="persona_tipo[${i}]"]`).value);
                const personaIdInput = document.querySelector(`input[name="persona_id[${i}]"]`);
                if (personaIdInput) {
                    formData.append('persona_id[' + i + ']', personaIdInput.value);
                }
                
                // Datos adicionales
                const telefonoInput = document.querySelector(`input[name="telefono_movil[${i}]"]`);
                if (telefonoInput && telefonoInput.value) {
                    formData.append('telefono_movil[' + i + ']', telefonoInput.value);
                }
                
                const emailInput = document.querySelector(`input[name="email[${i}]"]`);
                if (emailInput && emailInput.value) {
                    formData.append('email[' + i + ']', emailInput.value);
                }
                
                // Dirección: puede venir de campos ocultos (extraída del DNI) o de campos manuales
                const direccionHidden = document.getElementById(`hidden_direccion_${i}`);
                const direccionManual = document.getElementById(`direccion_manual_${i}`);
                const direccion = direccionManual && direccionManual.value ? direccionManual.value : (direccionHidden ? direccionHidden.value : '');
                if (direccion) {
                    formData.append('direccion[' + i + ']', direccion);
                }
                
                const localidadHidden = document.getElementById(`hidden_localidad_${i}`);
                const localidadManual = document.getElementById(`localidad_manual_${i}`);
                const localidad = localidadManual && localidadManual.value ? localidadManual.value : (localidadHidden ? localidadHidden.value : '');
                if (localidad) {
                    formData.append('localidad[' + i + ']', localidad);
                }
                
                const codigoHidden = document.getElementById(`hidden_codigo_postal_${i}`);
                const codigoManual = document.getElementById(`codigo_postal_manual_${i}`);
                const codigoPostal = codigoManual && codigoManual.value ? codigoManual.value : (codigoHidden ? codigoHidden.value : '');
                if (codigoPostal) {
                    formData.append('codigo_postal[' + i + ']', codigoPostal);
                }
                
                const provinciaHidden = document.getElementById(`hidden_provincia_${i}`);
                const provinciaManual = document.getElementById(`provincia_manual_${i}`);
                const provincia = provinciaManual && provinciaManual.value ? provinciaManual.value : (provinciaHidden ? provinciaHidden.value : '');
                if (provincia) {
                    formData.append('provincia[' + i + ']', provincia);
                }
                
                // Lugar de nacimiento manual (si no se extrajo)
                const lugarNacimientoManual = document.getElementById(`lugar_nacimiento_manual_input_${i}`);
                if (lugarNacimientoManual && lugarNacimientoManual.value) {
                    formData.append('lugar_nacimiento_manual[' + i + ']', lugarNacimientoManual.value);
                }
                
                // Solo para huéspedes
                if (i > 0) {
                    const parentescoInput = document.querySelector(`select[name="relacion_parentesco[${i}]"]`);
                    if (parentescoInput && parentescoInput.value) {
                        formData.append('relacion_parentesco[' + i + ']', parentescoInput.value);
                    }
                }
            }
            
            // Mostrar indicador de carga (submitBtn ya está definido arriba)
            const originalBtnText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = translations.submitSaving;
            
            hideError();
            hideSuccess();
            
            try {
                const response = await fetch(`/dni-scanner/${token}/save-additional-data`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message || translations.success);
                    
                    // Redirigir después de 2 segundos
                    setTimeout(() => {
                        window.location.href = `/dni-scanner/${token}`;
                    }, 2000);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                    showError(result.message || translations.errorConnection);
                }
            } catch (error) {
                console.error('❌ Error en submit:', error);
                console.error('❌ Stack trace:', error.stack);
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
                showError(translations.errorConnection + ': ' + error.message);
            }
            }); // cierra el addEventListener
        } // cierra el else
        
        function showProcessingModal(message) {
            const modal = document.getElementById('processingModal');
            const statusDiv = document.getElementById('processingStatus');
            if (modal) {
                modal.style.display = 'flex';
                if (statusDiv && message) {
                    statusDiv.textContent = message;
                }
                // Bloquear scroll del body
                document.body.style.overflow = 'hidden';
            }
        }
        
        function hideProcessingModal() {
            const modal = document.getElementById('processingModal');
            if (modal) {
                modal.style.display = 'none';
                // Restaurar scroll del body
                document.body.style.overflow = 'auto';
            }
        }
        
        function updateProcessingStatus(message) {
            const statusDiv = document.getElementById('processingStatus');
            if (statusDiv) {
                statusDiv.textContent = message;
            }
        }
        
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.innerHTML = message; // Usar innerHTML para permitir HTML (botón de WhatsApp)
            errorDiv.classList.add('active');
            
            // Scroll al mensaje de error
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        function hideError() {
            document.getElementById('errorMessage').classList.remove('active');
        }
        
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.classList.add('active');
        }
        
        function hideSuccess() {
            document.getElementById('successMessage').classList.remove('active');
        }
        
        // Cargar estado guardado al iniciar la página
        function initializePage() {
            console.log('📂 Cargando estado guardado desde localStorage...');
            // Esperar un momento para que el DOM esté completamente listo
            setTimeout(() => {
                restoreProcessedImages();
            }, 300);
        }
        
        // Ejecutar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePage);
        } else {
            // DOM ya está listo
            initializePage();
        }
        
        // También intentar restaurar después de un delay adicional por si acaso
        setTimeout(() => {
            if (document.readyState === 'complete') {
                console.log('📂 Restaurando estado (retry)...');
                restoreProcessedImages();
            }
        }, 1000);
        
        // Selector de idioma
        document.addEventListener('DOMContentLoaded', function() {
            const languageSelect = document.getElementById('languageSelect');
            
            if (languageSelect) {
                languageSelect.addEventListener('change', function() {
                    const selectedLanguage = this.value;
                    const token = @json($token);
                    
                    const originalValue = this.value;
                    this.disabled = true;
                    this.style.opacity = '0.6';
                    
                    fetch(@json(route('dni.cambiarIdioma')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            idioma: selectedLanguage,
                            token: token
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            this.value = originalValue;
                            this.disabled = false;
                            this.style.opacity = '1';
                            alert(translations.languageError.replace(':message', data.message || 'Error desconocido'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.value = originalValue;
                        this.disabled = false;
                        this.style.opacity = '1';
                        alert(translations.languageErrorUnknown);
                    });
                });
            }
        });
    </script>
</body>
</html>
