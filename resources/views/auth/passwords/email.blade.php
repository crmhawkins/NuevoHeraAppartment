<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Hawkins Suite - Restablecer Contraseña</title>

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
            }

            .container {
                width: 100%;
                max-width: 400px;
                margin: 0 auto;
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

            .alert {
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1.5rem;
                font-size: 0.875rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .alert-success {
                background: #ECFDF5;
                color: #065F46;
                border: 1px solid #A7F3D0;
            }

            .alert-success i {
                color: var(--success);
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
                top: 1rem;
                left: 1rem;
                color: var(--white);
                text-decoration: none;
                font-size: 0.875rem;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                opacity: 0.9;
                transition: opacity 0.2s ease;
            }

            .back-link:hover {
                opacity: 1;
            }

            @media (max-width: 640px) {
                .container {
                    max-width: 100%;
                }
                
                .card-header,
                .card-body {
                    padding: 1.5rem;
                }
                
                .back-link {
                    position: relative;
                    top: auto;
                    left: auto;
                    margin-bottom: 1rem;
                    color: var(--gray-600);
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
            }
        </style>
    </head>
    <body>
        <a href="{{ url('/') }}" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>

        <div class="container">
            <div class="card">
                <div class="card-header">
                    <div class="logo">
                        <img src="{{asset('logo_hawkins_white_center.png')}}" alt="Hawkins Suite">
                    </div>
                    <h1 class="title">Restablecer Contraseña</h1>
                    <p class="subtitle">
                        Ingresa tu correo electrónico y te enviaremos un enlace seguro para restablecer tu contraseña
                    </p>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" id="resetForm">
                        @csrf

                        <div class="form-group">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-input @error('email') error @enderror" 
                                    value="{{ old('email') }}" 
                                    placeholder="tu@email.com"
                                    required 
                                    autocomplete="email" 
                                    autofocus
                                >
                            </div>
                            
                            @error('email')
                                <div class="error-message">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="8" x2="12" y2="12"/>
                                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                                    </svg>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="btn-text">Enviar Enlace de Restablecimiento</span>
                            <span class="btn-spinner" style="display: none;"></span>
                        </button>
                    </form>

                    <div class="footer">
                        <a href="{{ route('login') }}">
                            ← Volver al inicio de sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('resetForm');
                const submitBtn = document.getElementById('submitBtn');
                const btnText = submitBtn.querySelector('.btn-text');
                const btnSpinner = submitBtn.querySelector('.btn-spinner');

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnSpinner.style.display = 'inline-block';
                    
                    // Submit form
                    setTimeout(() => {
                        form.submit();
                    }, 500);
                });

                // Real-time validation
                const emailInput = document.getElementById('email');
                emailInput.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        this.classList.remove('error');
                        const errorMsg = this.parentNode.querySelector('.error-message');
                        if (errorMsg) {
                            errorMsg.remove();
                        }
                    }
                });
            });
        </script>
    </body>
</html>
