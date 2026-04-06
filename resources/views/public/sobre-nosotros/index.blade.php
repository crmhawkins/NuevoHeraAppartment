@extends('layouts.public-booking')

@section('title', 'Sobre Nosotros - Apartamentos Algeciras')

@section('breadcrumb')
<div class="booking-breadcrumb">
    <div class="booking-container-header">
        <div class="booking-breadcrumb-content">
            <a href="{{ route('web.index') }}">{{ __('breadcrumb.home') }}</a>
            <span class="booking-breadcrumb-separator">{{ __('breadcrumb.separator') }}</span>
            <strong>{{ __('about.title') }}</strong>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Hero Section */
    .about-hero {
        background: linear-gradient(135deg, #003580 0%, #0056CC 100%);
        padding: 80px 0;
        text-align: center;
        color: white;
        margin-bottom: 60px;
    }
    
    .about-hero h1 {
        font-size: 48px;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    /* Secciones de contenido */
    .about-section {
        padding: 60px 0;
    }
    
    .about-section-white {
        background: white;
    }
    
    .about-section-light {
        background: #F8F9FA;
    }
    
    .about-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
    }
    
    .about-section-title {
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    
    .about-section-heading {
        font-size: 36px;
        font-weight: 700;
        color: #003580;
        margin-bottom: 24px;
    }
    
    .about-content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 48px;
        align-items: center;
        margin-top: 40px;
    }
    
    @media (max-width: 768px) {
        .about-content-grid {
            grid-template-columns: 1fr;
            gap: 32px;
        }
    }
    
    .about-text {
        font-size: 16px;
        line-height: 1.8;
        color: #333;
        margin-bottom: 24px;
    }
    
    .about-features-list {
        list-style: none;
        padding: 0;
        margin: 24px 0;
    }
    
    .about-features-list li {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        font-size: 16px;
        color: #333;
    }
    
    .about-features-list li i {
        color: #28a745;
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .about-image {
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        object-fit: cover;
    }
    
    .about-image-large {
        height: 400px;
    }
    
    .about-image-small {
        height: 200px;
        margin-top: 24px;
    }
    
    .about-phone {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 32px;
        font-size: 18px;
        font-weight: 600;
        color: #003580;
    }
    
    .about-phone i {
        font-size: 24px;
    }
    
    .about-cta-button {
        background: #003580;
        color: white;
        padding: 14px 32px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
        display: inline-block;
        margin-top: 24px;
        transition: background 0.2s, transform 0.2s;
    }
    
    .about-cta-button:hover {
        background: #0056CC;
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 53, 128, 0.3);
    }
    
    .about-services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin-top: 40px;
    }
    
    .about-service-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .about-service-item i {
        font-size: 32px;
        color: #003580;
        flex-shrink: 0;
    }
    
    .about-service-content h4 {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }
    
    .about-service-content p {
        font-size: 14px;
        color: #666;
        margin: 0;
    }
    
    .about-images-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    
    @media (max-width: 768px) {
        .about-images-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<!-- Hero Section -->
<div class="about-hero">
    <div class="about-container">
        <h1>{{ __('about.hero_title') }}</h1>
    </div>
</div>

<!-- Sección: Acerca de Apartamentos Algeciras -->
<div class="about-section about-section-white">
    <div class="about-container">
        <div class="about-section-title">{{ __('about.title') }}</div>
        <h2 class="about-section-heading">{{ __('about.title') }}</h2>
        
        <div class="about-content-grid">
            <div>
                <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop" 
                     alt="Interior de apartamento" 
                     class="about-image about-image-large">
            </div>
            
            <div>
                <p class="about-text">
                    {{ __('about.intro_text') }}
                </p>
                
                <ul class="about-features-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.central_location') }}</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.comfort_equipment') }}</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.flexible_services') }}</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.pets_allowed') }}</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.gift_vouchers') }}</span>
                    </li>
                </ul>
                
                <img src="https://images.unsplash.com/photo-1493663284031-b7e3aaa7952b?w=600&h=400&fit=crop" 
                     alt="Escritorio con decoración" 
                     class="about-image about-image-small">
                
                <div class="about-phone">
                    <i class="fas fa-phone"></i>
                    <span>605 379 329</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sección: Ubicación Inmejorable -->
<div class="about-section about-section-light">
    <div class="about-container">
        <div class="about-section-title">{{ __('about.why_choose_us') }}</div>
        <h2 class="about-section-heading">{{ __('about.location_title') }}</h2>
        
        <div class="about-content-grid">
            <div>
                <p class="about-text">
                    {{ __('about.location_text') }}
                </p>
                
                <ul class="about-features-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.comfort_guaranteed') }}</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.diverse_services') }}</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.excellent_ratings') }}</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.transparent_pricing') }}</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.friendly_service') }}</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('about.personalized_attention') }}</span>
                    </li>
                </ul>
                
                <a href="{{ route('web.reservas.portal') }}" class="about-cta-button">
                    {{ __('about.book_now') }}
                </a>
            </div>
            
            <div>
                <div class="about-images-grid">
                    <img src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=600&h=400&fit=crop" 
                         alt="Habitación con toallas" 
                         class="about-image about-image-large">
                    <img src="https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=600&h=400&fit=crop" 
                         alt="Vista de la ciudad" 
                         class="about-image about-image-small">
                </div>
            </div>
        </div>
        
        <!-- Servicios -->
        <div class="about-services-grid">
            <div class="about-service-item">
                <i class="fas fa-elevator"></i>
                <div class="about-service-content">
                    <h4>{{ __('footer.elevator_available') }}</h4>
                    <p>{{ __('about.elevator_desc') }}</p>
                </div>
            </div>
            
            <div class="about-service-item">
                <i class="fas fa-snowflake"></i>
                <div class="about-service-content">
                    <h4>{{ __('footer.heating_ac') }}</h4>
                    <p>{{ __('about.heating_desc') }}</p>
                </div>
            </div>
            
            <div class="about-service-item">
                <i class="fas fa-headset"></i>
                <div class="about-service-content">
                    <h4>{{ __('footer.service_24_7') }}</h4>
                    <p>+34 605 37 93 29</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

