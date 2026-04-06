<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Hawkins Suite - Apartamentos Turísticos</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        
        <!-- CSS del Style Guide de Limpieza -->
        <link rel="stylesheet" href="{{ asset('css/gestion-buttons.css') }}">
        
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])

        @yield('scriptHead')
        
        <style>
            :root {
                --primary: #007AFF;
                --primary-dark: #0056CC;
                --primary-light: #E3F2FD;
                --success: #10B981;
                --error: #EF4444;
                --warning: #F59E0B;
                --gray-50: #F9FAFB;
                --gray-100: #F3F4F6;
                --gray-200: #E5E7EB;
                --gray-300: #D1D5DB;
                --gray-400: #9CA3AF;
                --gray-500: #6B7280;
                --gray-600: #4B5563;
                --gray-700: #374151;
                --gray-800: #1F2937;
                --gray-900: #111827;
                --white: #FFFFFF;
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            html {
                font-size: 16px;
                line-height: 1.5;
                scroll-behavior: smooth;
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: var(--white);
                color: var(--gray-900);
                line-height: 1.6;
                overflow-x: hidden;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 1rem;
            }

            /* Header */
            .header {
                background: var(--white);
                border-bottom: 1px solid var(--gray-200);
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1000;
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.95);
            }

            .header-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 0;
            }

            .logo {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                text-decoration: none;
                color: var(--gray-900);
            }

            .logo img {
                width: 40px;
                height: 40px;
                object-fit: contain;
            }

            .logo-text {
                font-size: 1.25rem;
                font-weight: 700;
                color: var(--primary);
            }

            .nav {
                display: flex;
                align-items: center;
                gap: 2rem;
            }

            .nav-link {
                color: var(--gray-600);
                text-decoration: none;
                font-weight: 500;
                transition: color 0.2s ease;
                font-size: 0.875rem;
            }

            .nav-link:hover {
                color: var(--primary);
            }

            .auth-buttons {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .btn {
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 8px;
                font-size: 0.875rem;
                font-weight: 600;
                font-family: inherit;
                cursor: pointer;
                transition: all 0.2s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }

            .btn-primary {
                background: var(--primary);
                color: var(--white);
            }

            .btn-primary:hover {
                background: var(--primary-dark);
                transform: translateY(-1px);
                box-shadow: var(--shadow-lg);
            }

            .btn-secondary {
                background: var(--gray-100);
                color: var(--gray-700);
                border: 1px solid var(--gray-200);
            }

            .btn-secondary:hover {
                background: var(--gray-200);
                color: var(--gray-800);
            }

            /* Hero Section */
            .hero {
                padding: 8rem 0 4rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: var(--white);
                text-align: center;
                position: relative;
                overflow: hidden;
            }

            .hero::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
                pointer-events: none;
            }

            .hero-content {
                position: relative;
                z-index: 1;
                max-width: 800px;
                margin: 0 auto;
            }

            .hero-title {
                font-size: 3.5rem;
                font-weight: 800;
                margin-bottom: 1.5rem;
                line-height: 1.2;
            }

            .hero-subtitle {
                font-size: 1.25rem;
                font-weight: 400;
                margin-bottom: 2.5rem;
                opacity: 0.9;
                line-height: 1.6;
            }

            .hero-buttons {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 1rem;
                flex-wrap: wrap;
            }

            .btn-hero {
                padding: 1rem 2rem;
                font-size: 1rem;
                font-weight: 600;
            }

            .btn-hero-primary {
                background: var(--white);
                color: var(--primary);
            }

            .btn-hero-primary:hover {
                background: var(--gray-100);
                transform: translateY(-2px);
                box-shadow: var(--shadow-xl);
            }

            .btn-hero-secondary {
                background: transparent;
                color: var(--white);
                border: 2px solid var(--white);
            }

            .btn-hero-secondary:hover {
                background: var(--white);
                color: var(--primary);
                transform: translateY(-2px);
            }

            /* Features Section */
            .features {
                padding: 5rem 0;
                background: var(--gray-50);
            }

            .section-header {
                text-align: center;
                margin-bottom: 4rem;
            }

            .section-title {
                font-size: 2.5rem;
                font-weight: 700;
                color: var(--gray-900);
                margin-bottom: 1rem;
            }

            .section-subtitle {
                font-size: 1.125rem;
                color: var(--gray-600);
                max-width: 600px;
                margin: 0 auto;
            }

            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
                margin-top: 3rem;
            }

            .feature-card {
                background: var(--white);
                padding: 2rem;
                border-radius: 16px;
                box-shadow: var(--shadow);
                transition: all 0.3s ease;
                border: 1px solid var(--gray-200);
            }

            .feature-card:hover {
                transform: translateY(-4px);
                box-shadow: var(--shadow-xl);
                border-color: var(--primary-light);
            }

            .feature-icon {
                width: 64px;
                height: 64px;
                background: var(--primary-light);
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 1.5rem;
            }

            .feature-icon svg {
                width: 32px;
                height: 32px;
                color: var(--primary);
            }

            .feature-title {
                font-size: 1.25rem;
                font-weight: 600;
                color: var(--gray-900);
                margin-bottom: 1rem;
            }

            .feature-description {
                color: var(--gray-600);
                line-height: 1.6;
            }

            /* CTA Section */
            .cta {
                padding: 5rem 0;
                background: var(--gray-900);
                color: var(--white);
                text-align: center;
            }

            .cta-title {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
            }

            .cta-subtitle {
                font-size: 1.125rem;
                opacity: 0.8;
                margin-bottom: 2.5rem;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }

            /* Footer */
            .footer {
                background: var(--gray-800);
                color: var(--gray-300);
                padding: 3rem 0 2rem;
            }

            .footer-content {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem;
                margin-bottom: 2rem;
            }

            .footer-section h3 {
                color: var(--white);
                font-size: 1.125rem;
                font-weight: 600;
                margin-bottom: 1rem;
            }

            .footer-section p,
            .footer-section a {
                color: var(--gray-400);
                text-decoration: none;
                line-height: 1.6;
                margin-bottom: 0.5rem;
                display: block;
            }

            .footer-section a:hover {
                color: var(--white);
            }

            .footer-bottom {
                border-top: 1px solid var(--gray-700);
                padding-top: 2rem;
                text-align: center;
                color: var(--gray-400);
                font-size: 0.875rem;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .hero-title {
                    font-size: 2.5rem;
                }
                
                .hero-subtitle {
                    font-size: 1.125rem;
                }
                
                .section-title {
                    font-size: 2rem;
                }
                
                .hero-buttons {
                    flex-direction: column;
                    align-items: stretch;
                }
                
                .btn-hero {
                    width: 100%;
                }
                
                .nav {
                    display: none;
                }
            }

            @media (max-width: 480px) {
                .hero {
                    padding: 6rem 0 3rem;
                }
                
                .hero-title {
                    font-size: 2rem;
                }
                
                .features-grid {
                    grid-template-columns: 1fr;
                }
                
                .container {
                    padding: 0 0.75rem;
                }
            }

            /* Animations */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .fade-in-up {
                animation: fadeInUp 0.6s ease-out;
            }

            .fade-in-up-delay-1 {
                animation: fadeInUp 0.6s ease-out 0.2s both;
            }

            .fade-in-up-delay-2 {
                animation: fadeInUp 0.6s ease-out 0.4s both;
            }
        </style>
    </head>
    <body>
        <!-- Header -->
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <a href="{{ url('/') }}" class="logo">
                        <img src="{{asset('logo_hawkins_white_center.png')}}" alt="Hawkins Suite">
                        <span class="logo-text">Hawkins Suite</span>
                    </a>
                    
                    <nav class="nav">
                        <a href="#features" class="nav-link">Características</a>
                        <a href="#about" class="nav-link">Nosotros</a>
                        <a href="#contact" class="nav-link">Contacto</a>
                    </nav>
                    
                    <div class="auth-buttons">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/home') }}" class="btn btn-primary">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-primary">Iniciar Sesión</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-secondary">Registrarse</a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title fade-in-up">
                        Gestión Integral de Apartamentos Turísticos
                    </h1>
                    <p class="hero-subtitle fade-in-up-delay-1">
                        Plataforma profesional para la administración completa de propiedades turísticas. 
                        Optimiza operaciones, mejora la experiencia del huésped y maximiza la rentabilidad.
                    </p>
                    <div class="hero-buttons fade-in-up-delay-2">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/home') }}" class="btn btn-hero btn-hero-primary">
                                    Ir al Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-hero btn-hero-primary">
                                    Comenzar Ahora
                                </a>
                                <a href="#features" class="btn btn-hero btn-hero-secondary">
                                    Conocer Más
                                </a>
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features" id="features">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">¿Por qué elegir Hawkins Suite?</h2>
                    <p class="section-subtitle">
                        Nuestra plataforma ofrece herramientas avanzadas diseñadas específicamente para 
                        la gestión eficiente de apartamentos turísticos
                    </p>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                                <path d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">Gestión de Reservas</h3>
                        <p class="feature-description">
                            Sistema de reservas inteligente con calendario integrado, 
                            sincronización multi-canal y gestión automática de disponibilidad.
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">Control de Calidad</h3>
                        <p class="feature-description">
                            Herramientas de inspección y mantenimiento para garantizar 
                            la excelencia en cada estancia de tus huéspedes.
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">Análisis y Reportes</h3>
                        <p class="feature-description">
                            Dashboard con métricas en tiempo real, análisis de rendimiento 
                            y reportes detallados para optimizar tu negocio.
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                <path d="M16 3.13a4 4 0 010 7.75"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">Gestión de Equipos</h3>
                        <p class="feature-description">
                            Coordinación eficiente de limpieza, mantenimiento y atención 
                            al cliente con asignación inteligente de tareas.
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">Integración Multi-Canal</h3>
                        <p class="feature-description">
                            Sincronización automática con las principales plataformas 
                            de reservas para maximizar tu visibilidad y ocupación.
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <h3 class="feature-title">Seguridad Avanzada</h3>
                        <p class="feature-description">
                            Protección de datos con encriptación de nivel bancario, 
                            respaldos automáticos y control de acceso granular.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta">
            <div class="container">
                <h2 class="cta-title">¿Listo para transformar tu negocio?</h2>
                <p class="cta-subtitle">
                    Únete a cientos de propietarios que ya confían en Hawkins Suite 
                    para gestionar sus apartamentos turísticos de manera profesional.
                </p>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/home') }}" class="btn btn-hero btn-hero-primary">
                            Ir al Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-hero btn-hero-primary">
                            Comenzar Gratis
                        </a>
                    @endauth
                @endif
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>Hawkins Suite</h3>
                        <p>Plataforma líder en gestión de apartamentos turísticos. 
                           Herramientas profesionales para optimizar tu negocio.</p>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Producto</h3>
                        <a href="#features">Características</a>
                        <a href="#pricing">Precios</a>
                        <a href="#integrations">Integraciones</a>
                        <a href="#updates">Actualizaciones</a>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Soporte</h3>
                        <a href="#help">Centro de Ayuda</a>
                        <a href="#docs">Documentación</a>
                        <a href="#contact">Contacto</a>
                        <a href="#status">Estado del Sistema</a>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Empresa</h3>
                        <a href="#about">Nosotros</a>
                        <a href="#careers">Carreras</a>
                        <a href="#press">Prensa</a>
                        <a href="#partners">Socios</a>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; {{ date('Y') }} Hawkins Suite. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>

        <script>
            // Smooth scrolling for navigation links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Header background on scroll
            window.addEventListener('scroll', function() {
                const header = document.querySelector('.header');
                if (window.scrollY > 50) {
                    header.style.background = 'rgba(255, 255, 255, 0.98)';
                    header.style.boxShadow = 'var(--shadow-md)';
                } else {
                    header.style.background = 'rgba(255, 255, 255, 0.95)';
                    header.style.boxShadow = 'none';
                }
            });

            // Intersection Observer for animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe feature cards
            document.querySelectorAll('.feature-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s ease-out';
                observer.observe(card);
            });
        </script>
    </body>
</html>
