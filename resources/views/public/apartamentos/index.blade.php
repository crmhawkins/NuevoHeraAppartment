@extends('layouts.public-booking')

@section('title', 'Apartamentos - Apartamentos Algeciras')

@section('breadcrumb')
<div class="booking-breadcrumb">
    <div class="booking-container-header">
        <div class="booking-breadcrumb-content">
            <a href="{{ route('web.index') }}">{{ __('breadcrumb.home') }}</a>
            <span class="booking-breadcrumb-separator">{{ __('breadcrumb.separator') }}</span>
            <strong>{{ __('portal.title') }}</strong>
        </div>
    </div>
</div>
@endsection

@section('styles')
    .booking-apartamentos-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
    }
    
    .booking-apartamentos-header {
        margin-bottom: 40px;
        text-align: center;
    }
    
    .booking-apartamentos-title {
        font-size: 36px;
        font-weight: 700;
        color: #333;
        margin-bottom: 12px;
    }
    
    .booking-apartamentos-subtitle {
        font-size: 18px;
        color: #666;
    }
    
    .booking-apartamentos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 32px;
        margin-bottom: 60px;
    }
    
    .booking-apartamento-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        flex-direction: column;
    }
    
    .booking-apartamento-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }
    
    .booking-apartamento-image {
        width: 100%;
        height: 250px;
        object-fit: cover;
        background: #E0E0E0;
    }
    
    .booking-apartamento-content {
        padding: 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .booking-apartamento-title {
        font-size: 24px;
        font-weight: 700;
        color: #003580;
        margin-bottom: 12px;
    }
    
    .booking-apartamento-title:hover {
        text-decoration: underline;
    }
    
    .booking-apartamento-location {
        font-size: 14px;
        color: #666;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .booking-apartamento-location i {
        color: #003580;
    }
    
    .booking-apartamento-description {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 20px;
        flex: 1;
    }
    
    .booking-apartamento-features {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
        padding: 16px;
        background: #F8F9FA;
        border-radius: 8px;
    }
    
    .booking-apartamento-feature-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #333;
    }
    
    .booking-apartamento-feature-item i {
        color: #003580;
        width: 20px;
        text-align: center;
    }
    
    .booking-apartamento-services {
        margin-bottom: 20px;
    }
    
    .booking-apartamento-services-title {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 12px;
    }
    
    .booking-apartamento-services-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .booking-apartamento-service-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #E9F0FF;
        border: 1px solid #B3D4FF;
        border-radius: 6px;
        font-size: 12px;
        color: #003580;
        font-weight: 500;
    }
    
    .booking-apartamento-service-badge i {
        font-size: 14px;
    }
    
    .booking-apartamento-button {
        background: #003580;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        width: 100%;
    }
    
    .booking-apartamento-button:hover {
        background: #004585;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 53, 128, 0.2);
        color: white;
        text-decoration: none;
    }
    
    @media (max-width: 768px) {
        .booking-apartamentos-grid {
            grid-template-columns: 1fr;
        }
        
        .booking-apartamento-features {
            grid-template-columns: 1fr;
        }
    }
@endsection

@section('content')
<div class="booking-detail-container" style="margin-top: 24px;">
    <div class="booking-apartamentos-container">
        <div class="booking-apartamentos-header">
            <h1 class="booking-apartamentos-title">{{ __('apartments.title') }}</h1>
            <p class="booking-apartamentos-subtitle">{{ __('apartments.subtitle') }}</p>
        </div>
        
        @if($apartamentos->isNotEmpty())
            <div class="booking-apartamentos-grid">
                @foreach($apartamentos as $apartamento)
                    <div class="booking-apartamento-card" onclick="window.location.href='{{ route('web.reservas.show', $apartamento->id) }}'">
                        <!-- Imagen -->
                        <div style="position: relative; width: 100%; height: 250px; overflow: hidden;">
                            @if($apartamento->photos && $apartamento->photos->first())
                                <img src="{{ asset('storage/' . $apartamento->photos->first()->path) }}" 
                                     alt="{{ $apartamento->titulo ?: $apartamento->nombre }}" 
                                     class="booking-apartamento-image">
                            @else
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 64px; opacity: 0.3; background: #E0E0E0;">
                                    🏠
                                </div>
                            @endif
                        </div>
                        
                        <!-- Contenido -->
                        <div class="booking-apartamento-content">
                            <h3 class="booking-apartamento-title">
                                {{ $apartamento->titulo ?: $apartamento->nombre }}
                            </h3>
                            
                            <div class="booking-apartamento-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>{{ optional($apartamento->edificioName)->nombre ?? 'Algeciras' }}</span>
                            </div>
                            
                            @if($apartamento->description)
                                <div class="booking-apartamento-description">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($apartamento->getTranslated('description')), 120) }}
                                </div>
                            @endif
                            
                            <!-- Características principales -->
                            <div class="booking-apartamento-features">
                                @if($apartamento->bedrooms)
                                    <div class="booking-apartamento-feature-item">
                                        <i class="fas fa-bed"></i>
                                        <span>{{ $apartamento->bedrooms }} {{ $apartamento->bedrooms == 1 ? __('apartments.bedroom') : __('apartments.bedrooms') }}</span>
                                    </div>
                                @endif
                                
                                @if($apartamento->bathrooms)
                                    <div class="booking-apartamento-feature-item">
                                        <i class="fas fa-bath"></i>
                                        <span>{{ number_format($apartamento->bathrooms, 1, ',', '.') }} {{ $apartamento->bathrooms == 1 ? __('apartments.bathroom') : __('apartments.bathrooms') }}</span>
                                    </div>
                                @endif
                                
                                @if($apartamento->max_guests)
                                    <div class="booking-apartamento-feature-item">
                                        <i class="fas fa-users"></i>
                                        <span>{{ __('apartments.up_to_guests', ['count' => $apartamento->max_guests]) }}</span>
                                    </div>
                                @endif
                                
                                @if($apartamento->size)
                                    <div class="booking-apartamento-feature-item">
                                        <i class="fas fa-ruler-combined"></i>
                                        <span>{{ $apartamento->size }} {{ __('apartments.square_meters') }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Servicios -->
                            @php
                                $serviciosComunes = [];
                                if ($apartamento->wifi) $serviciosComunes[] = ['icono' => '<i class="fas fa-wifi"></i>', 'nombre' => __('apartments.wifi')];
                                if ($apartamento->parking) $serviciosComunes[] = ['icono' => '<i class="fas fa-parking"></i>', 'nombre' => __('apartments.service_parking')];
                                if ($apartamento->air_conditioning) $serviciosComunes[] = ['icono' => '<i class="fas fa-snowflake"></i>', 'nombre' => __('apartments.service_ac')];
                                if ($apartamento->tv) $serviciosComunes[] = ['icono' => '<i class="fas fa-tv"></i>', 'nombre' => __('apartments.service_tv')];
                                if ($apartamento->kitchen) $serviciosComunes[] = ['icono' => '<i class="fas fa-utensils"></i>', 'nombre' => __('apartments.service_kitchen')];
                                if ($apartamento->washing_machine) $serviciosComunes[] = ['icono' => '<i class="fas fa-tshirt"></i>', 'nombre' => __('apartments.service_washing_machine')];
                                if ($apartamento->elevator) $serviciosComunes[] = ['icono' => '<i class="fas fa-elevator"></i>', 'nombre' => __('apartments.service_elevator')];
                                if ($apartamento->pets_allowed) $serviciosComunes[] = ['icono' => '<i class="fas fa-paw"></i>', 'nombre' => __('apartments.service_pets')];
                            @endphp
                            
                            @if(!empty($serviciosComunes))
                                <div class="booking-apartamento-services">
                                    <div class="booking-apartamento-services-title">{{ __('apartments.included_services') }}</div>
                                    <div class="booking-apartamento-services-list">
                                        @foreach(array_slice($serviciosComunes, 0, 6) as $servicio)
                                            <span class="booking-apartamento-service-badge">
                                                {!! $servicio['icono'] !!}
                                                <span>{{ $servicio['nombre'] }}</span>
                                            </span>
                                        @endforeach
                                        @if(count($serviciosComunes) > 6)
                                            <span class="booking-apartamento-service-badge">
                                                +{{ count($serviciosComunes) - 6 }} {{ __('apartments.more') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            
                            <a href="{{ route('web.reservas.show', $apartamento->id) }}" class="booking-apartamento-button">
                                {{ __('apartments.view_details_book') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-home" style="font-size: 64px; color: #E0E0E0; margin-bottom: 24px;"></i>
                <h3 style="color: #666; margin-bottom: 12px;">{{ __('apartments.no_apartments') }}</h3>
                <p style="color: #999;">{{ __('apartments.coming_soon') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hacer las tarjetas clickeables
        document.querySelectorAll('.booking-apartamento-card').forEach(card => {
            card.style.cursor = 'pointer';
        });
    });
</script>
@endsection



