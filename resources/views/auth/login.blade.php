<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Hawkins Suite - Acceso al Sistema</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
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
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                color: var(--gray-900);
                position: relative;
            }

            .container {
                width: 100%;
                max-width: 400px;
                margin: 0 auto;
                position: relative;
                z-index: 2;
            }

            .logo-above-card {
                text-align: center;
                margin-bottom: 2rem;
            }

            .logo-above-card img {
                width: 120px;
                height: auto;
                filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
            }

            .card {
                background: var(--white);
                border-radius: 16px;
                box-shadow: var(--shadow-xl);
                overflow: hidden;
                position: relative;
            }

            .card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--primary) 0%, var(--success) 100%);
            }

            .card-header {
                padding: 2rem 2rem 1.5rem;
                text-align: center;
                background: var(--gray-50);
            }

            .card-icon {
                width: 64px;
                height: 64px;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--primary-light);
                border-radius: 16px;
                color: var(--primary);
            }

            .logo {
                width: 64px;
                height: 64px;
                margin: 0 auto 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--white);
                border-radius: 16px;
                box-shadow: var(--shadow-md);
            }

            .logo img {
                width: 48px;
                height: 48px;
                object-fit: contain;
            }

            .title {
                font-size: 1.5rem;
                font-weight: 700;
                color: var(--gray-900);
                margin-bottom: 0.5rem;
            }

            .subtitle {
                font-size: 0.875rem;
                color: var(--gray-600);
                line-height: 1.5;
            }

            .card-body {
                padding: 1.5rem 2rem 2rem;
            }

            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-label {
                display: block;
                font-size: 0.875rem;
                font-weight: 500;
                color: var(--gray-700);
                margin-bottom: 0.5rem;
            }

            .input-wrapper {
                position: relative;
                display: flex;
                align-items: center;
            }

            .input-icon {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                width: 20px;
                height: 20px;
                color: var(--gray-400);
                pointer-events: none;
                transition: color 0.2s ease;
                z-index: 1;
            }

            .form-input {
                width: 100%;
                padding: 0.875rem 1rem 0.875rem 3rem;
                border: 2px solid var(--gray-200);
                border-radius: 8px;
                font-size: 0.875rem;
                color: var(--gray-900);
                background: var(--white);
                transition: all 0.2s ease;
                font-family: inherit;
            }

            .form-input::placeholder {
                color: var(--gray-400);
            }

            .form-input:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px var(--primary-light);
            }

            .form-input:focus + .input-icon {
                color: var(--primary);
            }

            .form-input.error {
                border-color: var(--error);
                box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
            }

            .form-input.error + .input-icon {
                color: var(--error);
            }

            .error-message {
                margin-top: 0.5rem;
                font-size: 0.75rem;
                color: var(--error);
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .form-check {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 1.5rem;
            }

            .form-check-input {
                width: 16px;
                height: 16px;
                accent-color: var(--primary);
                cursor: pointer;
            }

            .form-check-label {
                color: var(--gray-700);
                font-size: 0.875rem;
                cursor: pointer;
                user-select: none;
            }

            .btn {
                width: 100%;
                padding: 0.875rem 1.5rem;
                border: none;
                border-radius: 8px;
                font-size: 0.875rem;
                font-weight: 600;
                font-family: inherit;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                text-decoration: none;
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

            .btn-primary:active {
                transform: translateY(0);
            }

            .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none !important;
            }

            .btn-spinner {
                width: 16px;
                height: 16px;
                border: 2px solid transparent;
                border-top: 2px solid currentColor;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            .footer {
                text-align: center;
                margin-top: 1.5rem;
                padding-top: 1.5rem;
                border-top: 1px solid var(--gray-200);
            }

            .footer a {
                color: var(--primary);
                text-decoration: none;
                font-size: 0.875rem;
                font-weight: 500;
                transition: color 0.2s ease;
            }

            .footer a:hover {
                color: var(--primary-dark);
                text-decoration: underline;
            }

            .back-link {
                position: absolute;
                top: 2rem;
                left: 2rem;
                color: var(--white);
                text-decoration: none;
                font-size: 0.875rem;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                opacity: 0.9;
                transition: all 0.2s ease;
                background: rgba(255, 255, 255, 0.1);
                padding: 0.75rem 1rem;
                border-radius: 8px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                z-index: 10;
            }

            .back-link:hover {
                opacity: 1;
                background: rgba(255, 255, 255, 0.2);
                transform: translateY(-1px);
            }

            @media (max-width: 768px) {
                .back-link {
                    top: 1rem;
                    left: 1rem;
                    font-size: 0.8rem;
                    padding: 0.5rem 0.75rem;
                }
            }

            @media (max-width: 640px) {
                .container {
                    max-width: 100%;
                    padding: 0 1rem;
                }
                
                .card-header,
                .card-body {
                    padding: 1.5rem;
                }
                
                .back-link {
                    position: fixed;
                    top: 1rem;
                    left: 1rem;
                }
            }

            @media (max-width: 480px) {
                body {
                    padding: 0.5rem;
                }
                
                .card-header,
                .card-body {
                    padding: 1rem;
                }
                
                .title {
                    font-size: 1.25rem;
                }
                
                .back-link {
                    top: 0.5rem;
                    left: 0.5rem;
                    font-size: 0.75rem;
                    padding: 0.5rem 0.75rem;
                }
            }
        </style>
    </head>
    <body>
        <a href="{{ url('/') }}" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Volver al Inicio
        </a>

        <div class="container">
            <!-- Logo por encima de la card -->
            <div class="logo-above-card">
                <img src="{{asset('logo_hawkins_white_center.png')}}" alt="Hawkins Suite">
                <p style="color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-top: 0.5rem;">v2.2</p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h1 class="title">Acceder al Sistema</h1>
                    <p class="subtitle">
                        Gestión integral de apartamentos turísticos
                    </p>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-error mb-3" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--error); color: var(--error); padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem;">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Acceso estándar: certificado HawCert --}}
                    <p class="subtitle" style="margin-bottom: 1.25rem;">Suba su certificado o use una clave de acceso.</p>

                    <form method="POST" action="{{ route('hawcert.login-with-certificate') }}" enctype="multipart/form-data" id="hawcertCertForm">
                        @csrf
                        <div class="form-group">
                            <label for="certificate" class="form-label">Certificado (archivo .pem, .crt o .cer)</label>
                            <input type="file" id="certificate" name="certificate" class="form-input @error('certificate') error @enderror" accept=".pem,.crt,.cer,application/x-pem-file,application/x-x509-ca-cert" style="padding: 0.75rem 1rem;">
                            @error('certificate')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary" id="hawcertSubmitBtn">
                            <span class="btn-text">Acceder con certificado</span>
                            <span class="btn-spinner" style="display: none;"></span>
                        </button>
                    </form>

                    <p class="subtitle" style="margin: 1rem 0 0.5rem; font-size: 0.8rem;">O si tiene una clave de acceso de un solo uso:</p>
                    <form method="POST" action="{{ route('hawcert.validate-key') }}" id="hawcertKeyForm">
                        @csrf
                        <div class="form-group">
                            <input type="text" id="hawcert_key" name="key" class="form-input @error('key') error @enderror" placeholder="ak_..." value="{{ old('key') }}" maxlength="51" style="padding-left: 1rem;">
                            @error('key')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.5rem 1rem;">Validar clave</button>
                    </form>

                    <div class="footer" style="margin-top: 1.5rem; padding-top: 1rem;">
                        <button type="button" class="login-fallback-toggle" id="loginFallbackToggle" style="background: none; border: none; color: var(--gray-500); font-size: 0.8rem; cursor: pointer; text-decoration: underline;">
                            Si falla el certificado: iniciar sesión con usuario y contraseña
                        </button>
                    </div>

                    <div id="loginFallbackPanel" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--gray-200);">
                        <form method="POST" action="{{ route('login') }}" id="loginForm">
                            @csrf
                            <div class="form-group">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    <input type="email" id="email" name="email" class="form-input @error('email') error @enderror" value="{{ old('email') }}" placeholder="tu@email.com" required autocomplete="email">
                                </div>
                                @error('email')<div class="error-message">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><circle cx="12" cy="16" r="1"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <input type="password" id="password" name="password" class="form-input @error('password') error @enderror" placeholder="••••••••" required autocomplete="current-password">
                                </div>
                                @error('password')<div class="error-message">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">Mantener sesión activa</label>
                            </div>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <span class="btn-text">Iniciar Sesión</span>
                                <span class="btn-spinner" style="display: none;"></span>
                            </button>
                        </form>
                        @if (Route::has('password.request'))
                            <div style="margin-top: 0.75rem;">
                                <a href="{{ route('password.request') }}" style="font-size: 0.8rem; color: var(--primary);">¿Olvidó su contraseña?</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const loginForm = document.getElementById('loginForm');
                if (loginForm) {
                    const submitBtn = document.getElementById('submitBtn');
                    if (submitBtn) {
                        const btnText = submitBtn.querySelector('.btn-text');
                        const btnSpinner = submitBtn.querySelector('.btn-spinner');
                        loginForm.addEventListener('submit', function() {
                            submitBtn.disabled = true;
                            if (btnText) btnText.style.display = 'none';
                            if (btnSpinner) btnSpinner.style.display = 'inline-block';
                        });
                    }
                }

                const hawcertCertForm = document.getElementById('hawcertCertForm');
                if (hawcertCertForm) {
                    const hawcertSubmitBtn = document.getElementById('hawcertSubmitBtn');
                    if (hawcertSubmitBtn) {
                        hawcertCertForm.addEventListener('submit', function() {
                            hawcertSubmitBtn.disabled = true;
                            const t = hawcertSubmitBtn.querySelector('.btn-text');
                            const s = hawcertSubmitBtn.querySelector('.btn-spinner');
                            if (t) t.style.display = 'none';
                            if (s) s.style.display = 'inline-block';
                        });
                    }
                }

                const loginFallbackToggle = document.getElementById('loginFallbackToggle');
                const loginFallbackPanel = document.getElementById('loginFallbackPanel');
                if (loginFallbackToggle && loginFallbackPanel) {
                    if (document.querySelector('#loginForm .error-message')) {
                        loginFallbackPanel.style.display = 'block';
                    }
                    loginFallbackToggle.addEventListener('click', function() {
                        loginFallbackPanel.style.display = loginFallbackPanel.style.display === 'none' ? 'block' : 'none';
                    });
                }

                document.querySelectorAll('.form-input').forEach(function(input) {
                    input.addEventListener('input', function() {
                        if (this.classList.contains('error')) {
                            this.classList.remove('error');
                            const err = this.closest('.form-group')?.querySelector('.error-message');
                            if (err) err.remove();
                        }
                    });
                });
            });
        </script>
    </body>
</html>
