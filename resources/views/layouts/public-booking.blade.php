<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="QvaJOvKK9ohbvk-q1He1trOmEYufMDHYGyKcTvjj_Wk" />
    @php
        $routeName = request()->route() ? request()->route()->getName() : null;
        $seoMeta = $routeName ? \App\Models\SeoMeta::getByRoute($routeName) : null;
    @endphp
    
    @if($seoMeta && $seoMeta->active)
        {!! render_seo_meta_tags($routeName) !!}
    @else
        <title>@yield('title', 'Apartamentos Algeciras')</title>
        <meta name="description" content="@yield('description', 'Reserva apartamentos turísticos en Algeciras. Mejores precios, ubicación céntrica y todas las comodidades.')">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @include('public.reservas.partials.booking-styles')
    <style>
        /* HEADER TIPO BOOKING.COM */
        .booking-top-header {
            background: #003580;
            color: white;
            padding: 16px 0;
            font-size: 14px;
        }
        
        .booking-top-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .booking-top-header-logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .booking-top-header-logo img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }
        
        .booking-top-header-logo:hover {
            opacity: 0.9;
        }
        
        .booking-top-header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        /* Botón Iniciar sesión estilo Booking.com */
        .booking-login-btn {
            background: white !important;
            color: #003580 !important;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s, transform 0.2s;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .booking-login-btn:hover {
            background: #F5F5F5 !important;
            color: #003580 !important;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        .booking-login-btn span,
        .booking-login-btn i {
            color: #003580 !important;
        }
        
        .booking-top-header a:not(.booking-login-btn) {
            color: white;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        
        .booking-top-header a:not(.booking-login-btn):hover {
            opacity: 0.8;
        }
        
        /* Selector de idioma con bandera */
        .booking-language-selector {
            position: relative;
            display: inline-block;
        }
        
        .booking-language-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background 0.2s;
            font-size: 14px;
        }
        
        .booking-language-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .booking-language-flag {
            width: 20px;
            height: 15px;
            border-radius: 2px;
            object-fit: cover;
        }
        
        .booking-language-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white !important;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            min-width: 200px;
            margin-top: 8px;
            display: none;
            z-index: 1000;
            overflow: hidden;
        }
        
        .booking-language-dropdown * {
            color: #333 !important;
        }
        
        .booking-language-dropdown.show {
            display: block;
        }
        
        .booking-language-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #333 !important;
            text-decoration: none;
            transition: background 0.2s;
            cursor: pointer;
        }
        
        .booking-language-item span {
            color: #333 !important;
        }
        
        .booking-language-item:hover {
            background: #F5F5F5;
        }
        
        .booking-language-item:hover span {
            color: #003580 !important;
        }
        
        .booking-language-item.active {
            background: #E9F0FF;
            color: #003580 !important;
            font-weight: 600;
        }
        
        .booking-language-item.active span {
            color: #003580 !important;
        }
        
        .booking-language-item-flag {
            width: 24px;
            height: 18px;
            border-radius: 2px;
            object-fit: cover;
        }
        
        /* Menú dentro del header */
        .booking-main-header {
            background: #003580;
            padding: 0;
        }
        
        .booking-main-header nav {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 0;
        }
        
        .booking-main-header nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            padding: 10px 18px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        
        .booking-main-header nav a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .booking-main-header nav a.active {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        
        .booking-main-header nav a i {
            font-size: 16px;
        }
        
        .booking-breadcrumb {
            background: white;
            padding: 16px 0;
            font-size: 14px;
            margin-bottom: 32px;
        }
        
        .booking-breadcrumb-content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .booking-breadcrumb a {
            color: #0071C2;
            text-decoration: none;
            padding: 4px 0;
            transition: color 0.2s;
        }
        
        .booking-breadcrumb a:hover {
            color: #0056CC;
            text-decoration: underline;
        }
        
        .booking-breadcrumb-separator {
            color: #999;
            margin: 0 4px;
        }
        
        .booking-breadcrumb strong {
            color: #333;
            font-weight: 600;
        }
        
        /* FOOTER ESTILO BOOKING.COM */
        .booking-footer {
            background: #f5f5f5;
            color: #333;
            padding: 48px 0 24px;
            margin-top: 64px;
            border-top: 1px solid #e0e0e0;
        }
        
        .booking-footer-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }
        
        .booking-footer-columns {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 32px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 1024px) {
            .booking-footer-columns {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .booking-footer-columns {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .booking-footer-columns {
                grid-template-columns: 1fr;
            }
        }
        
        .booking-footer-column h5 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #333;
            line-height: 1.4;
        }
        
        .booking-footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .booking-footer-column ul li {
            margin-bottom: 12px;
        }
        
        .booking-footer-column ul li a {
            color: #333;
            text-decoration: none;
            font-size: 14px;
            line-height: 1.5;
            transition: color 0.2s;
            display: inline-block;
        }
        
        .booking-footer-column ul li a:hover {
            color: #003580;
            text-decoration: underline;
        }
        
        .booking-footer-bottom {
            border-top: 1px solid #e0e0e0;
            padding-top: 24px;
            margin-top: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .booking-footer-copyright {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .booking-footer-currency {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #333;
            font-size: 14px;
        }
        
        .booking-footer-currency-flag {
            width: 20px;
            height: 15px;
            object-fit: cover;
            border-radius: 2px;
        }
        
        .booking-footer-funding-final {
            background: white;
            padding: 24px 16px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        
        .booking-footer-funding-final-image {
            margin-bottom: 16px;
        }
        
        .booking-footer-commerce-image {
            max-width: 68%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        
        .booking-footer-funding-final-text {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            margin: 0;
        }
        
        /* MEJORAS VISUALES */
        .booking-container-header {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }
        
        .booking-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
            width: 100%;
            box-sizing: border-box;
        }
        
        @media (max-width: 768px) {
            .booking-detail-container {
                padding: 0 10px;
            }
        }
        
        .booking-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 24px 0;
        }
        
        .booking-title-section {
            flex: 1;
        }
        
        .booking-reserve-btn-top {
            background: #0071C2;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .booking-reserve-btn-top:hover {
            background: #005fa3;
            color: white;
        }
        
        @yield('styles')
    </style>
</head>
<body style="background: white;">
    <!-- HEADER TOP (Azul oscuro) -->
    <div class="booking-top-header">
        <div class="booking-container-header">
            <div class="booking-top-header-row">
                <!-- Primera columna: Logo -->
                <div>
                    <a href="{{ route('web.index') }}" class="booking-top-header-logo">
                        <img src="{{ asset('LOGO-HAWKINS.png') }}" alt="Apartamentos Algeciras">
                    </a>
                </div>
                
                <!-- Segunda columna: Idioma, Ayuda, Iniciar sesión -->
                <div class="booking-top-header-right">
                    <!-- Selector de idioma -->
                    <div class="booking-language-selector">
                        @php
                            $currentLocale = session('locale', app()->getLocale());
                            $languages = [
                                'es' => ['name' => 'Español', 'flag' => 'https://flagcdn.com/w20/es.png'],
                                'en' => ['name' => 'English', 'flag' => 'https://flagcdn.com/w20/gb.png'],
                                'fr' => ['name' => 'Français', 'flag' => 'https://flagcdn.com/w20/fr.png'],
                                'de' => ['name' => 'Deutsch', 'flag' => 'https://flagcdn.com/w20/de.png'],
                                'it' => ['name' => 'Italiano', 'flag' => 'https://flagcdn.com/w20/it.png'],
                                'pt' => ['name' => 'Português', 'flag' => 'https://flagcdn.com/w20/pt.png'],
                            ];
                            $currentLang = $languages[$currentLocale] ?? $languages['es'];
                        @endphp
                        <button class="booking-language-btn" onclick="toggleLanguageDropdown()">
                            <img src="{{ $currentLang['flag'] }}" alt="{{ $currentLang['name'] }}" class="booking-language-flag">
                            <span>{{ $currentLang['name'] }}</span>
                            <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: 4px;"></i>
                        </button>
                        <div class="booking-language-dropdown" id="languageDropdown">
                            @foreach($languages as $code => $lang)
                                <a href="#" class="booking-language-item {{ $code === $currentLocale ? 'active' : '' }}" onclick="selectLanguage('{{ $code }}', '{{ $lang['name'] }}', event); return false;">
                                    <img src="{{ $lang['flag'] }}" alt="{{ $lang['name'] }}" class="booking-language-item-flag">
                                    <span>{{ $lang['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <a href="#" style="display: flex; align-items: center; gap: 6px; color: white; text-decoration: none;">
                        <i class="fas fa-question-circle"></i>
                        <span>{{ __('nav.help') }}</span>
                    </a>
                    @auth('cliente')
                        <a href="{{ route('web.perfil') }}" class="booking-login-btn">
                            <i class="fas fa-user"></i>
                            <span>{{ __('nav.my_profile') }}</span>
                        </a>
                        <form action="{{ route('web.logout') }}" method="POST" class="d-inline ms-2">
                            @csrf
                            <button type="submit" class="booking-login-btn" style="background: transparent; color: white; border: 1px solid white;">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>{{ __('nav.logout') }}</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('web.login') }}" class="booking-login-btn">
                            <i class="fas fa-user"></i>
                            <span>{{ __('nav.login') }}</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
    
    <!-- HEADER PRINCIPAL (Azul) - Menú integrado -->
    <div class="booking-main-header">
        <div class="booking-container-header">
            <nav>
                <a href="{{ route('web.index') }}" class="{{ request()->routeIs('web.index') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>{{ __('nav.home') }}</span>
                </a>
                <a href="{{ route('web.apartamentos') }}" class="{{ request()->routeIs('web.apartamentos') ? 'active' : '' }}">
                    <i class="fas fa-bed"></i>
                    <span>{{ __('nav.apartments') }}</span>
                </a>
                <a href="{{ route('web.reservas.portal') }}" class="{{ request()->routeIs('web.reservas.portal') ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i>
                    <span>{{ __('nav.reservation') }}</span>
                </a>
                <a href="{{ route('web.sobre-nosotros') }}" class="{{ request()->routeIs('web.sobre-nosotros') ? 'active' : '' }}">
                    <i class="fas fa-info-circle"></i>
                    <span>{{ __('nav.about') }}</span>
                </a>
                <a href="{{ route('web.contacto') }}" class="{{ request()->routeIs('web.contacto') ? 'active' : '' }}">
                    <i class="fas fa-envelope"></i>
                    <span>{{ __('nav.contact') }}</span>
                </a>
                <a href="{{ route('web.servicios') }}" class="{{ request()->routeIs('web.servicios') ? 'active' : '' }}">
                    <i class="fas fa-concierge-bell"></i>
                    <span>{{ __('Services') }}</span>
                </a>
            </nav>
        </div>
    </div>
    
    @yield('breadcrumb')
    
    <!-- HERO SECTION (fuera del contenedor para ancho completo) -->
    @yield('hero')
    
    <!-- CONTENIDO PRINCIPAL -->
    <div class="booking-detail-container" style="margin-top: 24px;">
        @yield('content')
    </div>
    
    <!-- PROGRAMA PYME DIGITAL -->
    <div class="pyme-digital-section" style="background: #ffffff; padding: 48px 0; margin-top: 64px; border-top: 1px solid #e0e0e0;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 16px;">
            <div style="text-align: center; margin-bottom: 32px;">
                <h2 style="font-size: 24px; font-weight: 700; color: #333; margin-bottom: 24px;">
                    Programa Pyme Digital de la Cámara de Comercio del Campo de Gibraltar
                </h2>
                <p style="font-size: 16px; line-height: 1.6; color: #555; max-width: 900px; margin: 0 auto 24px;">
                    IPOINT COMUNICACIÓN MASIVA S.L ha sido beneficiaria de Fondos Europeos, cuyo objetivo es la mejora de la competitividad de las PYMES, y gracias al cual ha puesto en marcha un Plan de Acción con el objetivo de reforzar la digitalización y la competitividad de las pymes durante el año 2024. Para ello ha contado con el apoyo del Programa Pyme Digital de la Cámara de Comercio del Campo de Gibraltar.
                </p>
                <div style="font-size: 18px; font-weight: 600; color: #003580; margin-top: 24px; margin-bottom: 32px;">
                    #EuropaSeSiente
                </div>
            </div>
            
            <!-- Imagen de Logos y Beneficiarios -->
            <div style="text-align: center; margin-top: 32px;">
                <img src="{{ asset('images/pyme-digital-logos-beneficiarios.png') }}" 
                     alt="Programa Pyme Digital - Beneficiarios 2024" 
                     style="max-width: 100%; height: auto; display: block; margin: 0 auto;"
                     onerror="this.style.display='none';"
                     onload="this.style.display='block';">
            </div>
            
            <!-- Sección final con imagen de comercio y texto -->
            <div style="background: white; padding: 24px 16px; text-align: center; margin-top: 32px;">
                <div style="margin-bottom: 16px;">
                    <img src="{{ asset('images/imagen_comercio.png') }}" 
                         alt="Cámara de Comercio" 
                         style="max-width: 68%; height: auto; display: block; margin: 0 auto;"
                         onerror="this.style.display='none';">
                </div>
                <div style="color: #333; font-size: 14px; font-weight: 500; margin: 0;">
                    {{ __('footer.funding_text') }}
                </div>
            </div>
        </div>
    </div>
    
    <!-- FONDOS NEXT GENERATION EU -->
    <div style="background: #ffffff; padding: 48px 0; border-top: 1px solid #e0e0e0;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 16px; text-align: center;">
            <p style="font-size: 16px; line-height: 1.6; color: #555; max-width: 900px; margin: 0 auto 32px;">
                Ipoint Comunicación Masiva SL ha recibido una ayuda de la Unión Europea con cargo al Fondo NextGenerationEU, en el marco del Plan de Recuperación, Trasformación y Resiliencia, para (denominación de la actuación/proyecto) dentro del programa de incentivos ligados al autoconsumo y almacenamiento, con fuentes de energía renovable, así como la implantación de sistemas térmicos renovables en el sector residencial del Ministerio para la Transición Ecológica y el Reto Demográfico, gestionado por la Junta de Andalucía, a través de la Agencia Andaluza de la Energía.
            </p>
            <div style="margin-top: 24px;">
                <img src="{{ asset('images/logo-apartamentos.jpeg') }}"
                     alt="Logo Apartamentos"
                     style="max-width: 600px; width: 100%; height: auto; display: block; margin: 0 auto;"
                     onerror="this.style.display='none';">
            </div>
        </div>
    </div>

    <!-- FOOTER ESTILO BOOKING.COM -->
    <div class="booking-footer">
        <div class="booking-footer-main">
            <div class="booking-footer-columns">
                <!-- Columna 1: Ayuda -->
                <div class="booking-footer-column">
                    <h5>{{ __('footer.help') }}</h5>
                    <ul>
                        <li><a href="{{ route('web.perfil') }}">{{ __('footer.manage_trips') }}</a></li>
                        <li><a href="{{ route('web.contacto') }}">{{ __('footer.contact_customer_service') }}</a></li>
                        <li><a href="{{ route('web.preguntas-frecuentes') }}">{{ __('footer.faqs') }}</a></li>
                        <li><a href="{{ route('web.politica-cancelaciones') }}">{{ __('footer.cancellation_policy') }}</a></li>
                    </ul>
                </div>
                
                <!-- Columna 2: Descubre -->
                <div class="booking-footer-column">
                    <h5>{{ __('footer.discover') }}</h5>
                    <ul>
                        <li><a href="{{ route('web.servicios') }}">{{ __('footer.our_services') }}</a></li>
                        <li><a href="{{ route('web.reservas.portal') }}">{{ __('footer.apartments') }}</a></li>
                        <li><a href="{{ route('web.sobre-nosotros') }}">{{ __('footer.about_us') }}</a></li>
                        <li><a href="{{ route('web.contacto') }}">{{ __('footer.location') }}</a></li>
                    </ul>
                </div>
                
                <!-- Columna 3: Términos y configuración -->
                <div class="booking-footer-column">
                    <h5>{{ __('footer.terms_and_settings') }}</h5>
                    <ul>
                        <li><a href="#">{{ __('footer.privacy_policy') }}</a></li>
                        <li><a href="#">{{ __('footer.terms_of_service') }}</a></li>
                        <li><a href="#">{{ __('footer.accessibility_statement') }}</a></li>
                        <li><a href="{{ route('web.politica-cancelaciones') }}">{{ __('footer.cancellation_policy') }}</a></li>
                    </ul>
                </div>
                
                <!-- Columna 4: Contacto -->
                <div class="booking-footer-column">
                    <h5>{{ __('footer.contact_us') }}</h5>
                    <ul>
                        <li><a href="tel:605379329">605 379 329</a></li>
                        <li><a href="mailto:info@apartamentosalgeciras.com">info@apartamentosalgeciras.com</a></li>
                        <li>{{ __('footer.address_suite') }}</li>
                        <li>{{ __('footer.address_costa') }}</li>
                    </ul>
                </div>
            </div>
            
            <!-- Copyright y Moneda -->
            <div class="booking-footer-bottom">
                <div class="booking-footer-copyright">
                    <div>{{ __('footer.copyright_text') }}</div>
                    <div style="margin-top: 8px;">{{ __('footer.all_rights_reserved') }}</div>
                </div>
                <div class="booking-footer-currency">
                    <img src="https://flagcdn.com/w20/es.png" alt="España" class="booking-footer-currency-flag">
                    <span>EUR</span>
                </div>
            </div>
        </div>
    </div>
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    
    <script>
        // Selector de idioma
        function toggleLanguageDropdown() {
            const dropdown = document.getElementById('languageDropdown');
            dropdown.classList.toggle('show');
        }
        
        function selectLanguage(code, name, event) {
            if (event) {
                event.preventDefault();
            }
            
            // Actualizar el botón visualmente primero
            const btn = document.querySelector('.booking-language-btn');
            const flagUrl = {
                'es': 'https://flagcdn.com/w20/es.png',
                'en': 'https://flagcdn.com/w20/gb.png',
                'fr': 'https://flagcdn.com/w20/fr.png',
                'de': 'https://flagcdn.com/w20/de.png',
                'it': 'https://flagcdn.com/w20/it.png',
                'pt': 'https://flagcdn.com/w20/pt.png'
            };
            
            const flagImg = btn.querySelector('.booking-language-flag');
            const nameSpan = btn.querySelector('span');
            
            if (flagImg && flagUrl[code]) {
                flagImg.src = flagUrl[code];
                flagImg.alt = name;
            }
            if (nameSpan) {
                nameSpan.textContent = name;
            }
            
            // Cerrar dropdown
            const dropdown = document.getElementById('languageDropdown');
            dropdown.classList.remove('show');
            
            // Actualizar clase active
            document.querySelectorAll('.booking-language-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Cambiar el idioma llamando a la ruta
            const url = '{{ route("web.language.change", ":locale") }}'.replace(':locale', code);
            window.location.href = url;
        }
        
        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(event) {
            const selector = document.querySelector('.booking-language-selector');
            const dropdown = document.getElementById('languageDropdown');
            
            if (selector && dropdown && !selector.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>
    
    @yield('scripts')
</body>
</html>

