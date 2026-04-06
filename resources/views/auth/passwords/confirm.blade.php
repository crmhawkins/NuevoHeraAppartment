<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Hawkins Suite - Confirmar Contraseña</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        
        <!-- CSS del Style Guide de Limpieza -->
        <link rel="stylesheet" href="{{ asset('css/gestion-buttons.css') }}">
        
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])

        @yield('scriptHead')
        
        <style>
            /* Estilos específicos de la página de confirmación de contraseña */
            :root {
                --hawkins-primary: #007AFF;
                --hawkins-secondary: #0056CC;
                --hawkins-accent: #4DA3FF;
                --hawkins-success: #28a745;
                --hawkins-warning: #ffc107;
                --hawkins-danger: #dc3545;
                --hawkins-light: #F2F2F7;
                --hawkins-dark: #1D1D1F;
                --hawkins-gray: #6C6C70;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            html, body {
                height: 100%;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                overflow-x: hidden;
            }

            .confirm-container {
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .confirm-container::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
                pointer-events: none;
            }

            /* Floating elements */
            .floating-element {
                position: absolute;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                animation: float 6s ease-in-out infinite;
            }

            .floating-element:nth-child(1) {
                width: 100px;
                height: 100px;
                top: 15%;
                left: 10%;
                animation-delay: 0s;
            }

            .floating-element:nth-child(2) {
                width: 150px;
                height: 150px;
                top: 70%;
                right: 10%;
                animation-delay: 2s;
            }

            .floating-element:nth-child(3) {
                width: 80px;
                height: 80px;
                bottom: 15%;
                left: 15%;
                animation-delay: 4s;
            }

            @keyframes float {
                0%, 100% { transform: translateY(0px) rotate(0deg); }
                50% { transform: translateY(-20px) rotate(180deg); }
            }

            .confirm-card {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(25px);
                -webkit-backdrop-filter: blur(25px);
                border-radius: 25px;
                padding: 40px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 450px;
                position: relative;
                z-index: 10;
                animation: slideInUp 0.8s ease-out;
            }

            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .confirm-header {
                text-align: center;
                margin-bottom: 40px;
            }

            .confirm-logo {
                margin-bottom: 20px;
            }

            .confirm-logo img {
                height: 80px;
                width: auto;
                filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
            }

            .confirm-title {
                color: white;
                font-size: 2rem;
                font-weight: 700;
                margin-bottom: 10px;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            }

            .confirm-subtitle {
                color: rgba(255, 255, 255, 0.8);
                font-size: 1rem;
                font-weight: 400;
                line-height: 1.6;
            }

            .form-group {
                margin-bottom: 25px;
                position: relative;
            }

            .form-input {
                width: 100%;
                padding: 18px 20px;
                background: rgba(255, 255, 255, 0.15);
                border: 2px solid rgba(255, 255, 255, 0.2);
                border-radius: 15px;
                color: white;
                font-size: 1rem;
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }

            .form-input::placeholder {
                color: rgba(255, 255, 255, 0.7);
            }

            .form-input:focus {
                outline: none;
                border-color: var(--hawkins-accent);
                background: rgba(255, 255, 255, 0.2);
                box-shadow: 0 0 20px rgba(77, 163, 255, 0.3);
                transform: translateY(-2px);
            }

            .form-input.error {
                border-color: var(--hawkins-danger);
                background: rgba(220, 53, 69, 0.1);
            }

            .input-icon {
                position: absolute;
                left: 20px;
                top: 50%;
                transform: translateY(-50%);
                color: rgba(255, 255, 255, 0.7);
                font-size: 1.1rem;
                transition: color 0.3s ease;
            }

            .form-input:focus + .input-icon {
                color: var(--hawkins-accent);
            }

            .form-input.with-icon {
                padding-left: 55px;
            }

            .error-message {
                color: var(--hawkins-danger);
                font-size: 0.9rem;
                margin-top: 8px;
                display: flex;
                align-items: center;
                gap: 8px;
                animation: shake 0.5s ease-in-out;
            }

            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }

            .confirm-btn {
                width: 100%;
                padding: 18px;
                background: linear-gradient(135deg, var(--hawkins-primary) 0%, var(--hawkins-secondary) 100%);
                border: none;
                border-radius: 15px;
                color: white;
                font-size: 1.1rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 8px 25px rgba(0, 122, 255, 0.3);
                position: relative;
                overflow: hidden;
                margin-bottom: 20px;
            }

            .confirm-btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }

            .confirm-btn:hover::before {
                left: 100%;
            }

            .confirm-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 35px rgba(0, 122, 255, 0.4);
            }

            .confirm-btn:active {
                transform: translateY(-1px);
            }

            .forgot-password {
                text-align: center;
                margin-top: 20px;
            }

            .forgot-password a {
                color: rgba(255, 255, 255, 0.8);
                text-decoration: none;
                font-size: 0.9rem;
                transition: color 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .forgot-password a:hover {
                color: var(--hawkins-accent);
                text-decoration: none;
            }

            .back-to-home {
                position: absolute;
                top: 30px;
                left: 30px;
                z-index: 20;
            }

            .back-btn {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 12px 20px;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 25px;
                color: white;
                text-decoration: none;
                font-size: 0.9rem;
                font-weight: 500;
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }

            .back-btn:hover {
                background: rgba(255, 255, 255, 0.2);
                border-color: rgba(255, 255, 255, 0.3);
                color: white;
                text-decoration: none;
                transform: translateY(-2px);
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .confirm-container {
                    padding: 15px;
                }
                
                .confirm-card {
                    padding: 30px 25px;
                    margin: 20px;
                }
                
                .confirm-title {
                    font-size: 1.7rem;
                }
                
                .form-input {
                    padding: 16px 18px;
                    font-size: 0.95rem;
                }
                
                .form-input.with-icon {
                    padding-left: 50px;
                }
                
                .back-to-home {
                    top: 20px;
                    left: 20px;
                }
                
                .back-btn {
                    padding: 10px 16px;
                    font-size: 0.85rem;
                }
            }

            @media (max-width: 480px) {
                .confirm-card {
                    padding: 25px 20px;
                    margin: 15px;
                }
                
                .confirm-title {
                    font-size: 1.5rem;
                }
                
                .confirm-logo img {
                    height: 60px;
                }
                
                .form-input {
                    padding: 15px 16px;
                }
                
                .confirm-btn {
                    padding: 16px;
                    font-size: 1rem;
                }
            }

            /* Loading state */
            .confirm-btn.loading {
                pointer-events: none;
                opacity: 0.8;
            }

            .confirm-btn.loading::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 20px;
                height: 20px;
                border: 2px solid transparent;
                border-top: 2px solid white;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: translate(-50%, -50%) rotate(0deg); }
                100% { transform: translate(-50%, -50%) rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <div class="confirm-container">
            <!-- Floating elements for background -->
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>

            <!-- Back to home button -->
            <div class="back-to-home">
                <a href="{{ url('/') }}" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>

            <!-- Confirm Card -->
            <div class="confirm-card">
                <div class="confirm-header">
                    <div class="confirm-logo">
                        <img src="{{asset('logo_hawkins_white_center.png')}}" alt="Hawkins Suite">
                    </div>
                    <h1 class="confirm-title">Confirmar Contraseña</h1>
                    <p class="confirm-subtitle">
                        Por favor confirma tu contraseña antes de continuar
                    </p>
                </div>

                <form method="POST" action="{{ route('password.confirm') }}" id="confirmForm">
                    @csrf

                    <div class="form-group">
                        <input 
                            placeholder="{{ __('Password') }}" 
                            id="password" 
                            type="password" 
                            class="form-input with-icon @error('password') error @enderror" 
                            name="password" 
                            required 
                            autocomplete="current-password"
                        >
                        <i class="fas fa-lock input-icon"></i>
                        
                        @error('password')
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>{{ $message }}</strong>
                            </div>
                        @enderror
                    </div>

                    <button type="submit" class="confirm-btn" id="confirmButton">
                        <span class="btn-text">{{ __('Confirmar Contraseña') }}</span>
                    </button>

                    @if (Route::has('password.request'))
                        <div class="forgot-password">
                            <a href="{{ route('password.request') }}">
                                <i class="fas fa-key"></i>
                                {{ __('¿Olvidó su Contraseña?') }}
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <script>
            // Confirm form handling with animations
            document.addEventListener('DOMContentLoaded', function() {
                const confirmForm = document.getElementById('confirmForm');
                const confirmButton = document.getElementById('confirmButton');
                const btnText = confirmButton.querySelector('.btn-text');

                confirmForm.addEventListener('submit', function(e) {
                    // Add loading state
                    confirmButton.classList.add('loading');
                    btnText.style.opacity = '0';
                    
                    // Simulate loading time (remove in production)
                    setTimeout(() => {
                        confirmButton.classList.remove('loading');
                        btnText.style.opacity = '1';
                        
                        // Submit the form
                        confirmForm.submit();
                    }, 1000);
                    
                    e.preventDefault();
                });

                // Input focus effects
                const inputs = document.querySelectorAll('.form-input');
                inputs.forEach(input => {
                    input.addEventListener('focus', function() {
                        this.parentElement.classList.add('focused');
                    });
                    
                    input.addEventListener('blur', function() {
                        this.parentElement.classList.remove('focused');
                    });
                });
            });
        </script>

        @yield('scripts')
    </body>
</html>
