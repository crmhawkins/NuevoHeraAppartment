<!DOCTYPE html>
<html lang="{{ $idioma ?? 'es' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ __('gracias.title') }}</title>
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
            padding: var(--spacing-sm) 20px;
            height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .container {
            max-width: 480px;
            margin: 0 auto;
            width: 100%;
        }
        
        .success-card {
            background: var(--booking-white);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: var(--spacing-md);
            text-align: center;
        }
        
        .success-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 12px;
            background: linear-gradient(135deg, var(--booking-success) 0%, #0A5D61 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }
        
        h1 {
            font-size: 22px;
            color: var(--booking-blue);
            margin-bottom: 8px;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .subtitle {
            color: var(--booking-gray-medium);
            font-size: 14px;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .message {
            color: var(--booking-gray-dark);
            font-size: 13px;
            margin-bottom: 12px;
            line-height: 1.4;
            text-align: left;
        }
        
        .info-section {
            background: var(--booking-gray-bg);
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 12px;
            text-align: left;
        }
        
        .info-section p {
            color: var(--booking-gray-dark);
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
        }
        
        .info-section a {
            color: var(--booking-blue);
            text-decoration: none;
            font-weight: 600;
        }
        
        .info-section a:hover {
            text-decoration: underline;
        }
        
        .whatsapp-section {
            background: var(--booking-blue-light);
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .whatsapp-section p {
            color: var(--booking-gray-dark);
            font-size: 12px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .btn-whatsapp {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            border: none;
            color: var(--booking-white);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 211, 102, 0.3);
            color: var(--booking-white);
        }
        
        .ia-note {
            color: var(--booking-gray-medium);
            font-size: 11px;
            margin-top: 8px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .main-wrapper {
                padding: var(--spacing-sm) 16px;
            }
            
            .success-card {
                padding: var(--spacing-md);
            }
            
            h1 {
                font-size: 20px;
            }
            
            .success-icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
        }
        
        /* Asegurar que no haya scroll */
        html, body {
            overflow-x: hidden;
            height: 100%;
        }
        
        body {
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="portal-header">
        <div class="portal-header-content">
            <div class="portal-logo">
                <img src="{{ asset('logo-hawkins-suite_white.png') }}" alt="Hawkins Suites" style="max-width: 200px; height: auto;">
            </div>
            <div class="language-selector">
                <select id="languageSelect" class="language-select">
                    <option value="es" {{ ($idioma ?? 'es') === 'es' ? 'selected' : '' }}>🇪🇸 ES</option>
                    <option value="en" {{ ($idioma ?? 'es') === 'en' ? 'selected' : '' }}>🇺🇸 EN</option>
                    <option value="fr" {{ ($idioma ?? 'es') === 'fr' ? 'selected' : '' }}>🇫🇷 FR</option>
                    <option value="de" {{ ($idioma ?? 'es') === 'de' ? 'selected' : '' }}>🇩🇪 DE</option>
                    <option value="it" {{ ($idioma ?? 'es') === 'it' ? 'selected' : '' }}>🇮🇹 IT</option>
                    <option value="pt" {{ ($idioma ?? 'es') === 'pt' ? 'selected' : '' }}>🇵🇹 PT</option>
                </select>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="container">
            <div class="success-card">
                <div class="success-icon">
                    ✓
                </div>
                
                <h1>{{ __('gracias.title') }}</h1>
                <p class="subtitle">{{ __('gracias.subtitle') }}</p>
                
                <p class="message">{{ __('gracias.message') }}</p>
                
                <div class="info-section">
                    <p>
                        {{ __('gracias.info') }}
                        <a href="{{ route('gracias.contacto') }}">{{ __('gracias.contacto') }}</a>
                        {{ __('gracias.telefono') }}
                        <a href="tel:+34605379329">+34 605 37 93 29</a>
                        {{ __('gracias.horario') }}
                    </p>
                </div>
                
                <div class="whatsapp-section">
                    <p>{{ __('gracias.whatsapp') }}</p>
                    <a href="https://wa.me/34605379329" target="_blank" class="btn-whatsapp">
                        <span>💬</span>
                        {{ __('gracias.ir_whatsapp') }}
                    </a>
                </div>
                
                <p class="ia-note">{{ __('gracias.ia') }}</p>
            </div>
        </div>
    </div>


    <script>
        // Selector de idioma
        document.addEventListener('DOMContentLoaded', function() {
            const languageSelect = document.getElementById('languageSelect');
            
            if (languageSelect) {
                languageSelect.addEventListener('change', function() {
                    const selectedLanguage = this.value;
                    const currentUrl = window.location.href;
                    const urlParts = currentUrl.split('/');
                    // Reemplazar el idioma en la URL
                    urlParts[urlParts.length - 1] = selectedLanguage;
                    const newUrl = urlParts.join('/');
                    
                    window.location.href = newUrl;
                });
            }
        });
    </script>
</body>
</html>
