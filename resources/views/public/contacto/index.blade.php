@extends('layouts.public-booking')

@section('title', 'Contacto - Apartamentos Algeciras')

@section('breadcrumb')
<div class="booking-breadcrumb">
    <div class="booking-container-header">
        <div class="booking-breadcrumb-content">
            <a href="{{ route('web.index') }}">{{ __('breadcrumb.home') }}</a>
            <span class="booking-breadcrumb-separator">{{ __('breadcrumb.separator') }}</span>
            <strong>{{ __('contact.title') }}</strong>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Hero Section */
    .contact-hero {
        background: linear-gradient(135deg, #003580 0%, #0056CC 100%);
        padding: 80px 0;
        text-align: center;
        color: white;
        margin-bottom: 60px;
    }
    
    .contact-hero h1 {
        font-size: 48px;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    /* Contenedor principal */
    .contact-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
    }
    
    /* Tarjeta de contacto */
    .contact-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
        padding: 48px;
        margin-bottom: 40px;
    }
    
    .contact-content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 48px;
    }
    
    @media (max-width: 768px) {
        .contact-content-grid {
            grid-template-columns: 1fr;
            gap: 32px;
        }
        
        .contact-card {
            padding: 32px 24px;
        }
    }
    
    /* Sección de información de contacto */
    .contact-info {
        display: flex;
        flex-direction: column;
    }
    
    .contact-info-icon {
        font-size: 64px;
        color: #003580;
        margin-bottom: 24px;
    }
    
    .contact-info-title {
        font-size: 28px;
        font-weight: 700;
        color: #003580;
        margin-bottom: 16px;
    }
    
    .contact-info-description {
        font-size: 16px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 32px;
    }
    
    .contact-details {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .contact-details li {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
        font-size: 16px;
        color: #333;
    }
    
    .contact-details li i {
        color: #003580;
        font-size: 20px;
        margin-top: 4px;
        flex-shrink: 0;
    }
    
    /* Formulario */
    .contact-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
        width: 100%;
    }
    
    .contact-form-wrapper {
        display: flex;
        flex-direction: column;
        width: 100%;
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
    
    .form-group input,
    .form-group textarea {
        padding: 12px 16px;
        border: 2px solid #E0E0E0;
        border-radius: 6px;
        font-size: 16px;
        font-family: inherit;
        transition: border-color 0.2s;
        width: 100%;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #003580;
    }
    
    .form-group textarea {
        min-height: 150px;
        resize: vertical;
    }
    
    .form-submit-btn {
        background: #003580;
        color: white;
        padding: 14px 32px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.2s, transform 0.2s;
        width: auto;
        margin-top: 10px;
        align-self: flex-end;
    }
    
    .form-submit-btn:hover {
        background: #0056CC;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 53, 128, 0.3);
    }
    
    .form-submit-btn:active {
        transform: translateY(0);
    }
    
    /* Alertas */
    .alert {
        padding: 16px 20px;
        border-radius: 6px;
        margin-bottom: 24px;
        font-size: 14px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    /* Mapa */
    .contact-map-container {
        margin-top: 40px;
        margin-bottom: 60px;
    }
    
    .contact-map-wrapper {
        width: 100%;
        height: 500px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }
    
    .contact-map-wrapper iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    
    @media (max-width: 768px) {
        .contact-map-wrapper {
            height: 400px;
        }
    }
    
    /* Errores de validación */
    .error-message {
        color: #dc3545;
        font-size: 14px;
        margin-top: 4px;
    }
    
    .form-group input.is-invalid,
    .form-group textarea.is-invalid {
        border-color: #dc3545;
    }
</style>
@endsection

@section('content')
<!-- Hero Section -->
<div class="contact-hero">
    <div class="contact-container">
        <h1>{{ __('contact.hero_title') }}</h1>
    </div>
</div>

<!-- Contenedor principal -->
<div class="contact-container">
    <!-- Tarjeta de contacto -->
    <div class="contact-card">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif
        
        <div class="contact-content-grid">
            <!-- Información de contacto (izquierda) -->
            <div class="contact-info">
                <i class="fas fa-envelope contact-info-icon"></i>
                <h2 class="contact-info-title">{{ __('contact.info_title') }}</h2>
                <p class="contact-info-description">
                    {{ __('contact.info_description') }}
                </p>
                
                <ul class="contact-details">
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>605 379 329</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>info@apartamentosalgeciras.com</span>
                    </li>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Calle Santísimo 2, 11201, Algeciras</span>
                    </li>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Calle Joaquín Costa 5, 11201, Algeciras</span>
                    </li>
                </ul>
            </div>
            
            <!-- Formulario de contacto (derecha) -->
            <div class="contact-form-wrapper">
                <form action="{{ route('web.contacto.enviar') }}" method="POST" class="contact-form">
                    @csrf
                    
                    <div class="form-group">
                        <label for="nombre">{{ __('contact.name') }}</label>
                        <input 
                            type="text" 
                            id="nombre" 
                            name="nombre" 
                            placeholder="John Doe"
                            value="{{ old('nombre') }}"
                            required
                            class="{{ $errors->has('nombre') ? 'is-invalid' : '' }}"
                        >
                        @error('nombre')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="email">{{ __('contact.email') }}</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="ejemplo@gmail.com"
                            value="{{ old('email') }}"
                            required
                            class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                        >
                        @error('email')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="asunto">{{ __('contact.subject') }}</label>
                        <input 
                            type="text" 
                            id="asunto" 
                            name="asunto" 
                            placeholder="{{ __('contact.subject_placeholder') }}"
                            value="{{ old('asunto') }}"
                            required
                            class="{{ $errors->has('asunto') ? 'is-invalid' : '' }}"
                        >
                        @error('asunto')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="mensaje">{{ __('contact.message') }}</label>
                        <textarea 
                            id="mensaje" 
                            name="mensaje" 
                            placeholder="{{ __('contact.message_placeholder') }}"
                            required
                            class="{{ $errors->has('mensaje') ? 'is-invalid' : '' }}"
                        >{{ old('mensaje') }}</textarea>
                        @error('mensaje')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <!-- reCAPTCHA v3 (oculto, funciona en segundo plano) -->
                    <div class="form-group">
                        @if(config('services.recaptcha.site_key'))
                            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                            <div style="font-size: 11px; color: #666; margin-top: 10px; text-align: right;">
                                Este sitio está protegido por reCAPTCHA y se aplican la 
                                <a href="https://policies.google.com/privacy" target="_blank" style="color: #003580;">Política de Privacidad</a> y los 
                                <a href="https://policies.google.com/terms" target="_blank" style="color: #003580;">Términos de Servicio</a> de Google.
                            </div>
                            @error('g-recaptcha-response')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        @else
                            <!-- Captcha simple alternativo cuando no está configurado reCAPTCHA -->
                            <div style="padding: 15px; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 6px;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                                    <input type="checkbox" id="captcha-checkbox" name="captcha_checkbox" required style="width: 20px; height: 20px; cursor: pointer;">
                                    <label for="captcha-checkbox" style="margin: 0; cursor: pointer; font-size: 14px; color: #333;">
                                        {{ __('contact.captcha_confirm') }}
                                    </label>
                                </div>
                                <div style="padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 12px; color: #856404;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>{{ __('contact.captcha_note') }}</strong>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="form-group" style="margin-top: 10px;">
                        <button type="submit" class="form-submit-btn">
                            {{ __('contact.send_message') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Mapa de Google Maps -->
    <div class="contact-map-container">
        <div class="contact-map-wrapper">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3168.5!2d-5.4508!3d36.1306!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd0c8e8b8b8b8b8b%3A0x8b8b8b8b8b8b8b8b!2sCalle%20Sant%C3%ADsimo%2C%202%2C%2011201%20Algeciras%2C%20C%C3%A1diz!5e0!3m2!1ses!2ses!4v1234567890123!5m2!1ses!2ses"
                width="100%" 
                height="500" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                title="Ubicación de Apartamentos Algeciras - Calle Santísimo 2, Algeciras">
            </iframe>
        </div>
        <div style="text-align: center; margin-top: 12px;">
            <a href="https://www.google.com/maps/search/?api=1&query=Calle+Sant%C3%ADsimo+2,+11201+Algeciras,+C%C3%A1diz" 
               target="_blank" 
               style="color: #003580; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-external-link-alt"></i> 
                <span>{{ __('contact.expand_map') }}</span>
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(config('services.recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<script>
    // Inicializar reCAPTCHA v3
    grecaptcha.ready(function() {
        // Ejecutar reCAPTCHA cuando se carga la página
        grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'contacto'}).then(function(token) {
            document.getElementById('g-recaptcha-response').value = token;
        });
        
        // Re-ejecutar antes de enviar el formulario
        document.querySelector('.contact-form').addEventListener('submit', function(e) {
            grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'contacto'}).then(function(token) {
                document.getElementById('g-recaptcha-response').value = token;
                // Continuar con el envío del formulario
            });
        });
    });
</script>
@endif
<script>
    // Mejorar la experiencia del formulario
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.contact-form');
        const inputs = form.querySelectorAll('input, textarea');
        
        // Limpiar clases de error al escribir
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                }
            });
        });
        
        // Validación básica del lado del cliente
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (input.hasAttribute('required') && !input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                }
            });
            
            // Validar reCAPTCHA v3
            @if(config('services.recaptcha.site_key'))
            const recaptchaToken = document.getElementById('g-recaptcha-response').value;
            if (!recaptchaToken) {
                isValid = false;
                alert('Por favor, espera a que se cargue la verificación de seguridad.');
            }
            @endif
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, completa todos los campos requeridos.');
            }
        });
    });
</script>
@endsection

