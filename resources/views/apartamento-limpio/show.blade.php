<!DOCTYPE html>
<html lang="{{ $locale ?? 'es' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ __('apartamento-limpio.title') }}</title>
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
            padding: var(--spacing-md) 20px;
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            overflow-y: auto;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }
        
        .content-card {
            background: var(--booking-white);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }
        
        .header-section {
            text-align: center;
            margin-bottom: var(--spacing-md);
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
        
        .apartamento-info {
            background: var(--booking-gray-bg);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: var(--spacing-md);
            text-align: left;
        }
        
        .apartamento-info p {
            color: var(--booking-gray-dark);
            font-size: 13px;
            line-height: 1.5;
            margin: 4px 0;
        }
        
        .apartamento-info strong {
            color: var(--booking-blue);
            font-weight: 600;
        }
        
        /* Galería de fotos */
        .gallery-section {
            margin-top: var(--spacing-md);
        }
        
        .gallery-title {
            font-size: 18px;
            color: var(--booking-blue);
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            margin-top: var(--spacing-sm);
        }
        
        .gallery-item {
            position: relative;
            width: 100%;
            padding-top: 100%; /* Aspect ratio 1:1 */
            overflow: hidden;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            background: var(--booking-gray-light);
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .gallery-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-photos {
            text-align: center;
            padding: var(--spacing-lg);
            color: var(--booking-gray-medium);
        }
        
        .no-photos-icon {
            font-size: 48px;
            margin-bottom: var(--spacing-sm);
            opacity: 0.5;
        }
        
        .no-photos p {
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            cursor: pointer;
        }
        
        .lightbox-content {
            position: relative;
            margin: auto;
            padding: 20px;
            width: 90%;
            max-width: 900px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .lightbox-img {
            width: 100%;
            height: auto;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 40px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
        }
        
        .lightbox-close:hover {
            opacity: 0.7;
        }
        
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            padding: 10px 15px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            user-select: none;
        }
        
        .lightbox-nav:hover {
            background: rgba(0, 0, 0, 0.7);
        }
        
        .lightbox-prev {
            left: 20px;
        }
        
        .lightbox-next {
            right: 20px;
        }
        
        @media (max-width: 768px) {
            .main-wrapper {
                padding: var(--spacing-sm) 16px;
            }
            
            .content-card {
                padding: var(--spacing-sm);
            }
            
            h1 {
                font-size: 20px;
            }
            
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 8px;
            }
            
            .lightbox-close {
                top: 10px;
                right: 20px;
                font-size: 30px;
            }
            
            .lightbox-nav {
                font-size: 24px;
                padding: 8px 12px;
            }
            
            .lightbox-prev {
                left: 10px;
            }
            
            .lightbox-next {
                right: 10px;
            }
        }
        
        html, body {
            overflow-x: hidden;
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
                    <option value="es" {{ ($locale ?? 'es') === 'es' ? 'selected' : '' }}>🇪🇸 ES</option>
                    <option value="en" {{ ($locale ?? 'es') === 'en' ? 'selected' : '' }}>🇺🇸 EN</option>
                    <option value="fr" {{ ($locale ?? 'es') === 'fr' ? 'selected' : '' }}>🇫🇷 FR</option>
                    <option value="de" {{ ($locale ?? 'es') === 'de' ? 'selected' : '' }}>🇩🇪 DE</option>
                    <option value="it" {{ ($locale ?? 'es') === 'it' ? 'selected' : '' }}>🇮🇹 IT</option>
                    <option value="pt" {{ ($locale ?? 'es') === 'pt' ? 'selected' : '' }}>🇵🇹 PT</option>
                </select>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="container">
            <div class="content-card">
                <div class="header-section">
                    <div class="success-icon">
                        🏠
                    </div>
                    
                    <h1>{{ __('apartamento-limpio.title') }}</h1>
                    <p class="subtitle">{{ __('apartamento-limpio.subtitle') }}</p>
                </div>
                
                @if($apartamento)
                <div class="apartamento-info">
                    <p><strong>{{ __('apartamento-limpio.apartamento') }}:</strong> {{ $apartamento->nombre ?? $apartamento->titulo ?? 'N/A' }}</p>
                    @if($fecha_limpieza)
                    <p><strong>{{ __('apartamento-limpio.fecha_limpieza') }}:</strong> {{ \Carbon\Carbon::parse($fecha_limpieza)->format('d/m/Y') }}</p>
                    @endif
                </div>
                @endif
                
                @if($fotos && $fotos->count() > 0)
                <div class="gallery-section">
                    <h2 class="gallery-title">{{ __('apartamento-limpio.galeria_titulo') }}</h2>
                    <div class="gallery-grid" id="galleryGrid">
                        @foreach($fotos as $index => $foto)
                        <div class="gallery-item" data-index="{{ $index }}" onclick="openLightbox({{ $index }})">
                            <img src="{{ asset($foto->url) }}" alt="{{ __('apartamento-limpio.foto') }} {{ $index + 1 }}" loading="lazy">
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="no-photos">
                    <div class="no-photos-icon">📷</div>
                    <p>{{ __('apartamento-limpio.sin_fotos') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <div class="lightbox-content" onclick="event.stopPropagation()">
            <img id="lightboxImg" class="lightbox-img" src="" alt="">
            <span class="lightbox-nav lightbox-prev" onclick="event.stopPropagation(); changeImage(-1)">&#10094;</span>
            <span class="lightbox-nav lightbox-next" onclick="event.stopPropagation(); changeImage(1)">&#10095;</span>
        </div>
    </div>

    <script>
        // Datos de las fotos para el lightbox
        const fotos = @json($fotos->map(function($foto) {
            return asset($foto->url);
        })->values());
        
        let currentImageIndex = 0;

        function openLightbox(index) {
            currentImageIndex = index;
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightboxImg');
            lightbox.style.display = 'block';
            lightboxImg.src = fotos[index];
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function changeImage(direction) {
            currentImageIndex += direction;
            if (currentImageIndex < 0) {
                currentImageIndex = fotos.length - 1;
            } else if (currentImageIndex >= fotos.length) {
                currentImageIndex = 0;
            }
            document.getElementById('lightboxImg').src = fotos[currentImageIndex];
        }

        // Cerrar con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowLeft') {
                changeImage(-1);
            } else if (e.key === 'ArrowRight') {
                changeImage(1);
            }
        });

        // Selector de idioma
        document.addEventListener('DOMContentLoaded', function() {
            const languageSelect = document.getElementById('languageSelect');
            
            if (languageSelect) {
                languageSelect.addEventListener('change', function() {
                    const selectedLanguage = this.value;
                    const currentUrl = window.location.href;
                    const url = new URL(currentUrl);
                    url.searchParams.set('lang', selectedLanguage);
                    window.location.href = url.toString();
                });
            }
        });
    </script>
</body>
</html>




