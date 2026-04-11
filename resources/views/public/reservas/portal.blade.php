@extends('layouts.public-booking')

@section('title', 'Apartamentos Disponibles - Apartamentos Algeciras')

@section('breadcrumb')
@if($fechaEntrada && $fechaSalida)
<div class="booking-breadcrumb">
    <div class="booking-container-header">
        <div class="booking-breadcrumb-content">
            <a href="{{ route('web.index') }}">{{ __('breadcrumb.home') }}</a>
            <span class="booking-breadcrumb-separator">{{ __('breadcrumb.separator') }}</span>
            <strong>{{ __('portal.title') }}</strong>
        </div>
    </div>
</div>
@endif
@endsection

@section('styles')
    /* BARRA DE BÚSQUEDA PROMINENTE ESTILO BOOKING.COM */
    .booking-detail-container .booking-portal-search-container {
        max-width: 1200px !important;
        margin: 24px auto 24px auto !important;
        padding: 0 16px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    
    .booking-portal-search-bar {
        background: white;
        border: 3px solid #FFB700;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        width: 100%;
        box-sizing: border-box;
    }
    
    .booking-portal-hero .booking-portal-search-bar {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }
    
    .booking-portal-search-form {
        display: grid;
        grid-template-columns: 2fr 1.5fr 1fr;
        gap: 16px;
        align-items: end;
    }
    
    .booking-portal-search-field {
        display: flex;
        flex-direction: column;
    }
    
    .booking-portal-search-field label {
        font-size: 12px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .booking-portal-search-field input,
    .booking-portal-search-field select {
        padding: 12px 16px;
        border: 2px solid #E0E0E0;
        border-radius: 6px;
        font-size: 16px;
        font-family: var(--font-family);
        transition: all 0.2s;
        background: white;
        color: #333;
        width: 100%;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }
    
    .booking-portal-search-field input[readonly] {
        cursor: pointer;
        background: white;
    }
    
    .booking-portal-search-field-huespedes {
        position: relative;
    }
    
    .booking-portal-search-field-huespedes input {
        padding-right: 40px;
    }
    
    .booking-portal-search-field-huespedes .chevron-icon {
        position: absolute;
        right: 16px;
        bottom: 12px;
        color: #666;
        pointer-events: none;
        font-size: 12px;
        z-index: 1;
    }
    
    .booking-portal-search-field input:focus,
    .booking-portal-search-field select:focus {
        outline: none;
        border-color: #003580;
        box-shadow: 0 0 0 3px rgba(0, 53, 128, 0.1);
    }
    
    .booking-portal-search-submit {
        background: #003580;
        color: white;
        border: none;
        padding: 12px 32px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s;
        height: fit-content;
        white-space: nowrap;
    }
    
    .booking-portal-search-submit:hover {
        background: #004585;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 53, 128, 0.2);
    }
    
    /* LAYOUT DE DOS COLUMNAS */
    .booking-portal-main-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
        width: 100%;
        box-sizing: border-box;
    }
    
    /* SIDEBAR IZQUIERDA - FILTROS */
    .booking-portal-sidebar {
        background: white;
        border-radius: 8px;
        padding: 20px;
        height: fit-content;
        position: sticky;
        top: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .booking-portal-sidebar h3 {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin: 0 0 20px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid #E0E0E0;
    }
    
    .booking-portal-filter-group {
        margin-bottom: 24px;
    }
    
    .booking-portal-filter-group h4 {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin: 0 0 12px 0;
    }
    
    .booking-portal-filter-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        cursor: pointer;
    }
    
    .booking-portal-filter-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .booking-portal-filter-checkbox label {
        font-size: 14px;
        color: #666;
        cursor: pointer;
        flex: 1;
    }
    
    .booking-portal-filter-count {
        color: #999;
        font-size: 12px;
    }
    
    /* MAPA PEQUEÑO */
    .booking-portal-mini-map {
        width: 100%;
        height: 200px;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
        background: #E0E0E0;
        position: relative;
    }
    
    .booking-portal-mini-map iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    
    .booking-portal-map-button {
        position: absolute;
        bottom: 12px;
        left: 50%;
        transform: translateX(-50%);
        background: #003580;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    
    /* CONTENIDO PRINCIPAL - RESULTADOS */
    .booking-portal-results {
        min-height: 400px;
    }
    
    .booking-portal-results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .booking-portal-results-title {
        font-size: 22px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }
    
    .booking-portal-sort {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .booking-portal-sort select {
        padding: 8px 16px;
        border: 2px solid #E0E0E0;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
    }
    
    /* TARJETAS DE APARTAMENTOS ESTILO BOOKING.COM */
    .booking-portal-properties-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .booking-portal-property-card {
        background: white;
        border: 1px solid #E0E0E0;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        cursor: pointer;
    }
    
    .booking-portal-property-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }
    
    .booking-portal-property-image {
        width: 280px;
        min-width: 280px;
        height: 220px;
        background: #E0E0E0;
        position: relative;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .booking-portal-property-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .booking-portal-property-favorite {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 32px;
        height: 32px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.2s;
    }
    
    .booking-portal-property-favorite:hover {
        transform: scale(1.1);
    }
    
    .booking-portal-property-favorite i {
        color: #666;
        font-size: 16px;
    }
    
    .booking-portal-property-favorite.active i {
        color: #FF385C;
    }
    
    .booking-portal-property-content {
        flex: 1;
        padding: 20px;
        display: flex;
        flex-direction: column;
    }
    
    .booking-portal-property-price-section {
        width: 200px;
        min-width: 200px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: flex-end;
        border-left: 1px solid #E0E0E0;
        flex-shrink: 0;
    }
    
    .booking-portal-property-header {
        margin-bottom: 12px;
    }
    
    .booking-portal-property-title {
        font-size: 20px;
        font-weight: 600;
        color: #003580;
        margin: 0 0 8px 0;
        line-height: 1.3;
    }
    
    .booking-portal-property-title:hover {
        text-decoration: underline;
    }
    
    .booking-portal-property-location {
        font-size: 14px;
        color: #666;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .booking-portal-property-location a {
        color: #0071C2;
        text-decoration: none;
    }
    
    .booking-portal-property-location a:hover {
        text-decoration: underline;
    }
    
    .booking-portal-property-details {
        font-size: 14px;
        color: #666;
        margin-bottom: 12px;
        line-height: 1.6;
    }
    
    .booking-portal-property-features-list {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .booking-portal-property-feature {
        font-size: 13px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .booking-portal-property-feature i {
        color: #003580;
        font-size: 14px;
    }
    
    /* SERVICIOS POPULARES EN TARJETAS */
    .booking-portal-property-services {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #E0E0E0;
    }
    
    .booking-portal-property-services-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    
    .booking-portal-property-service-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        background: #F8F9FA;
        border: 1px solid #E0E0E0;
        border-radius: 4px;
        font-size: 12px;
        color: #333;
        transition: all 0.2s ease;
    }
    
    .booking-portal-property-service-item:hover {
        border-color: #003580;
        background: #F0F4F8;
    }
    
    .booking-portal-property-service-item i,
    .booking-portal-property-service-item span[style*="font-size"] {
        color: #003580 !important;
        font-size: 14px !important;
        width: 16px;
        text-align: center;
    }
    
    .booking-portal-property-service-item span:not([style*="font-size"]) {
        font-weight: 500;
        color: #333;
        font-size: 12px;
    }
    
    .booking-portal-property-service-more {
        font-size: 12px;
        color: #666;
        font-weight: 500;
        padding: 6px 10px;
    }
    
    .booking-portal-property-price {
        text-align: right;
        margin-bottom: 16px;
    }
    
    .booking-portal-property-price-label {
        font-size: 12px;
        color: #666;
        margin-bottom: 4px;
    }
    
    .booking-portal-property-price-amount {
        font-size: 24px;
        font-weight: 700;
        color: #333;
        margin-bottom: 4px;
    }
    
    .booking-portal-property-price-note {
        font-size: 12px;
        color: #999;
    }
    
    .booking-portal-property-button {
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
        width: 100%;
        text-align: center;
    }
    
    .booking-portal-property-button:hover {
        background: #004585;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 53, 128, 0.2);
    }
    
    /* RESPONSIVE */
    @media (max-width: 968px) {
        .booking-portal-main-layout {
            grid-template-columns: 1fr;
        }
        
        .booking-portal-sidebar {
            position: static;
            order: 2;
        }
        
        .booking-portal-search-form {
            grid-template-columns: 1fr;
        }
        
        .booking-portal-property-card {
            flex-direction: column;
        }
        
        .booking-portal-property-image {
            width: 100%;
            min-width: 100%;
            height: 250px;
        }
        
        .booking-portal-property-price-section {
            width: 100%;
            min-width: 100%;
            border-left: none;
            border-top: 1px solid #E0E0E0;
            align-items: flex-start;
        }
        
        .booking-portal-property-price {
            text-align: left;
        }
        
    .booking-portal-property-button {
        width: 100%;
        }
    }
    
@endsection

@section('content')
@php
    $fechaEntrada = $fechaEntrada ?? null;
    $fechaSalida = $fechaSalida ?? null;
    $adultos = $adultos ?? 2;
    $ninos = $ninos ?? 0;
@endphp

<!-- CONTENIDO PRINCIPAL -->
<div class="booking-detail-container" style="margin-top: 0;">
    <!-- BUSCADOR COMPACTO -->
    <div class="booking-portal-search-container" style="max-width: 1200px; margin: 24px auto; padding: 0 16px;">
        <div class="booking-portal-search-bar" style="background: white; border: 2px solid #E0E0E0; border-radius: 8px; padding: 16px;">
            <form method="GET" action="{{ route('web.reservas.portal') }}" class="booking-portal-search-form" id="compact-search-form">
                <div class="booking-portal-search-field">
                    <label for="fecha_entrada">{{ __('portal.dates') }}</label>
                    <input type="text" 
                           id="fecha_entrada" 
                           value="{{ $fechaEntrada && $fechaSalida ? $fechaEntrada . ' - ' . $fechaSalida : '' }}"
                           placeholder="{{ __('portal.dates_placeholder') }}"
                           required
                           readonly>
                    <input type="hidden" 
                           id="fecha_entrada_hidden" 
                           name="fecha_entrada" 
                           value="{{ $fechaEntrada ?? '' }}">
                    <input type="hidden" 
                           id="fecha_salida" 
                           name="fecha_salida" 
                           value="{{ $fechaSalida ?? '' }}">
                </div>
                <div class="booking-portal-search-field booking-portal-search-field-huespedes">
                    <label for="huespedes">{{ __('portal.guests') }}</label>
                    <div style="position: relative;">
                        <input type="text" 
                               id="huespedes" 
                               name="huespedes" 
                               value="{{ ($adultos ?? 2) }} {{ __('reservation.adults') }}{{ ($ninos ?? 0) > 0 ? ', ' . ($ninos ?? 0) . ' ' . (($ninos ?? 0) == 1 ? __('reservation.children') : __('reservation.children')) : '' }}"
                               placeholder="{{ __('portal.guests_placeholder') }}"
                               readonly
                               onclick="const modal = document.getElementById('huespedes-modal'); if(modal) { modal.style.display = 'flex'; }">
                        <i class="fas fa-chevron-down chevron-icon"></i>
                    </div>
                    <input type="hidden" id="adultos" name="adultos" value="{{ $adultos ?? 2 }}">
                    <input type="hidden" id="ninos" name="ninos" value="{{ $ninos ?? 0 }}">
                </div>
                <button type="submit" class="booking-portal-search-submit">
                    <i class="fas fa-search"></i>
                    {{ __('portal.search') }}
                </button>
            </form>
        </div>
    </div>

    <!-- Modal para seleccionar huéspedes -->
    <div id="huespedes-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 24px; border-radius: 8px; max-width: 400px; width: 90%;">
            <h3 style="margin: 0 0 20px 0;">{{ __('portal.select_guests') }}</h3>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <label>{{ __('portal.adults_label') }}</label>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button" onclick="changeGuests('adultos', -1)" style="width: 32px; height: 32px; border: 2px solid #E0E0E0; background: white; border-radius: 4px; cursor: pointer;">-</button>
                    <span id="adultos-count">{{ $adultos ?? 2 }}</span>
                    <button type="button" onclick="changeGuests('adultos', 1)" style="width: 32px; height: 32px; border: 2px solid #E0E0E0; background: white; border-radius: 4px; cursor: pointer;">+</button>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <label>{{ __('portal.children_label') }}</label>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button" onclick="changeGuests('ninos', -1)" style="width: 32px; height: 32px; border: 2px solid #E0E0E0; background: white; border-radius: 4px; cursor: pointer;">-</button>
                    <span id="ninos-count">{{ $ninos ?? 0 }}</span>
                    <button type="button" onclick="changeGuests('ninos', 1)" style="width: 32px; height: 32px; border: 2px solid #E0E0E0; background: white; border-radius: 4px; cursor: pointer;">+</button>
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('huespedes-modal').style.display='none'" style="padding: 10px 20px; border: 2px solid #E0E0E0; background: white; border-radius: 6px; cursor: pointer; font-weight: 600;">{{ __('portal.cancel') }}</button>
                <button type="button" onclick="saveGuests()" style="padding: 10px 20px; background: #003580; color: white; border: none; border-radius: 6px; cursor: pointer;">{{ __('portal.apply') }}</button>
            </div>
        </div>
    </div>

    <!-- LAYOUT PRINCIPAL: SIDEBAR + RESULTADOS -->
        <div class="booking-portal-main-layout">
            <!-- SIDEBAR IZQUIERDA - FILTROS -->
            <aside class="booking-portal-sidebar">
                <!-- Mapa pequeño -->
                <div class="booking-portal-mini-map">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3168.5!2d-5.45!3d36.13!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzYsMDcnNDgiTiA1wrAyNycwMCJX!5e0!3m2!1ses!2ses!4v1234567890123!5m2!1ses!2ses" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                    <button class="booking-portal-map-button" onclick="window.open('https://www.google.com/maps?q=Algeciras', '_blank')">
                        {{ __('portal.view_on_map') }}
                    </button>
                </div>
                
                <h3>{{ __('portal.filter_by') }}</h3>
                
                <!-- Presupuesto -->
                <div class="booking-portal-filter-group">
                    <h4>{{ __('portal.your_budget') }}</h4>
                    <div style="padding: 12px 0;">
                        <input type="range" min="0" max="200" value="100" style="width: 100%;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #666; margin-top: 8px;">
                            <span>€0</span>
                            <span>€200+</span>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros populares -->
                <div class="booking-portal-filter-group">
                    <h4>{{ __('portal.popular_filters') }}</h4>
                    <div class="booking-portal-filter-checkbox">
                        <input type="checkbox" id="filter-wifi">
                        <label for="filter-wifi">
                            {{ __('portal.free_wifi') }}
                            <span class="booking-portal-filter-count">(42)</span>
                        </label>
                    </div>
                    <div class="booking-portal-filter-checkbox">
                        <input type="checkbox" id="filter-parking">
                        <label for="filter-parking">
                            {{ __('apartments.parking') }}
                            <span class="booking-portal-filter-count">(18)</span>
                        </label>
                    </div>
                    <div class="booking-portal-filter-checkbox">
                        <input type="checkbox" id="filter-ac">
                        <label for="filter-ac">
                            {{ __('apartments.air_conditioning') }}
                            <span class="booking-portal-filter-count">(36)</span>
                        </label>
                    </div>
                    <div class="booking-portal-filter-checkbox">
                        <input type="checkbox" id="filter-breakfast">
                        <label for="filter-breakfast">
                            {{ __('portal.breakfast_included') }}
                            <span class="booking-portal-filter-count">(8)</span>
                        </label>
                    </div>
                </div>
            </aside>
            
            <!-- CONTENIDO PRINCIPAL - RESULTADOS -->
            <div class="booking-portal-results">
                <div class="booking-portal-results-header">
                    <h2 class="booking-portal-results-title">
                        Algeciras: {{ isset($apartamentosDisponibles) ? $apartamentosDisponibles->count() : 0 }} {{ isset($apartamentosDisponibles) && $apartamentosDisponibles->count() == 1 ? __('portal.apartment_found') : __('portal.apartments_found') }}
                    </h2>
                    <div class="booking-portal-sort">
                        <label for="sort-select" style="font-size: 14px; color: #666;">{{ __('portal.sort_by') }}</label>
                        <select id="sort-select" style="padding: 8px 16px; border: 2px solid #E0E0E0; border-radius: 6px; font-size: 14px; cursor: pointer;">
                            <option>{{ __('portal.our_featured') }}</option>
                            <option>{{ __('portal.price_lowest') }}</option>
                            <option>{{ __('portal.price_highest') }}</option>
                            <option>{{ __('portal.best_rated') }}</option>
                        </select>
                    </div>
                </div>
                
                @if(isset($apartamentosDisponibles) && $apartamentosDisponibles->isNotEmpty())
                    <div class="booking-portal-properties-list">
                        @foreach($apartamentosDisponibles as $ap)
                            <div class="booking-portal-property-card">
                                <!-- Imagen con carousel -->
                                <div class="booking-portal-property-image" style="position: relative; overflow: hidden;">
                                    @if($ap->photos && $ap->photos->count() > 0)
                                        @foreach($ap->photos as $photoIdx => $photo)
                                            <img src="{{ asset('storage/' . $photo->path) }}"
                                                 alt="{{ $ap->titulo ?? $ap->nombre }}"
                                                 class="carousel-img-{{ $ap->id }}"
                                                 style="{{ $photoIdx > 0 ? 'display:none;' : '' }}position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;">
                                        @endforeach
                                        @if($ap->photos->count() > 1)
                                            <button onclick="carouselNav({{ $ap->id }}, -1)" style="position:absolute;left:4px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.85);border:none;border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:14px;z-index:2;box-shadow:0 1px 4px rgba(0,0,0,0.2);">&#10094;</button>
                                            <button onclick="carouselNav({{ $ap->id }}, 1)" style="position:absolute;right:4px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.85);border:none;border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:14px;z-index:2;box-shadow:0 1px 4px rgba(0,0,0,0.2);">&#10095;</button>
                                            <div style="position:absolute;bottom:8px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.5);color:white;padding:2px 8px;border-radius:10px;font-size:11px;z-index:2;">
                                                <span id="carousel-counter-{{ $ap->id }}">1</span>/{{ $ap->photos->count() }}
                                            </div>
                                        @endif
                                    @else
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 64px; opacity: 0.3;">
                                            🏠
                                        </div>
                                    @endif
                                    <div class="booking-portal-property-favorite" onclick="toggleFavorite(this)" style="z-index:3;">
                                        <i class="far fa-heart"></i>
                                    </div>
                                </div>
                                
                                <!-- Contenido -->
                                <div class="booking-portal-property-content">
                                    <div class="booking-portal-property-header">
                                        <h3 class="booking-portal-property-title">
                                            <a href="{{ route('web.reservas.show', array_filter(['id' => $ap->id, 'fecha_entrada' => $fechaEntrada ?? null, 'fecha_salida' => $fechaSalida ?? null, 'adultos' => $adultos ?? null, 'ninos' => $ninos ?? null])) }}" style="color: inherit; text-decoration: none;">
                                                {{ $ap->titulo ?? $ap->nombre }}
                                            </a>
                                        </h3>
                                        <div class="booking-portal-property-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>{{ optional($ap->edificioName)->nombre ?? 'Algeciras' }}</span>
                                            <a href="#">{{ __('portal.show_on_map') }}</a>
                                        </div>
                                        <div class="booking-portal-property-details">
                                            @if($ap->bedrooms && $ap->bathrooms && $ap->size)
                                                {{ __('portal.entire_apartment') }} • {{ $ap->bedrooms }} {{ $ap->bedrooms == 1 ? __('portal.bedroom') : __('portal.bedrooms') }} • {{ number_format($ap->bathrooms, 0) }} {{ $ap->bathrooms == 1 ? __('portal.bathroom') : __('portal.bathrooms') }} • {{ $ap->size }} m²
                                            @elseif($ap->description)
                                                {{ \Illuminate\Support\Str::limit(strip_tags($ap->getTranslated('description')), 100) }}
                                            @endif
                                        </div>
                                        <div class="booking-portal-property-features-list">
                                            @if($ap->max_guests)
                                                <div class="booking-portal-property-feature">
                                                    <i class="fas fa-users"></i>
                                                    <span>{{ __('portal.up_to_guests', ['count' => $ap->max_guests]) }}</span>
                                                </div>
                                            @endif
                                            @if($ap->bedrooms)
                                                <div class="booking-portal-property-feature">
                                                    <i class="fas fa-bed"></i>
                                                    <span>{{ $ap->bedrooms }} {{ $ap->bedrooms == 1 ? __('portal.room') : __('portal.rooms') }}</span>
                                                </div>
                                            @endif
                                            @if($ap->bathrooms)
                                                <div class="booking-portal-property-feature">
                                                    <i class="fas fa-bath"></i>
                                                    <span>{{ number_format($ap->bathrooms, 1, ',', '.') }} {{ $ap->bathrooms == 1 ? __('portal.bathroom') : __('portal.bathrooms') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Servicios Populares (Lista estática) -->
                                        @php
                                            // Lista de servicios comunes para mostrar en las tarjetas
                                            $serviciosComunes = [
                                                ['icono' => '<i class="fas fa-wifi"></i>', 'nombre' => __('portal.free_wifi')],
                                                ['icono' => '<i class="fas fa-parking"></i>', 'nombre' => __('apartments.parking')],
                                                ['icono' => '<i class="fas fa-snowflake"></i>', 'nombre' => __('apartments.air_conditioning')],
                                                ['icono' => '<i class="fas fa-tv"></i>', 'nombre' => __('apartments.tv')],
                                                ['icono' => '<i class="fas fa-utensils"></i>', 'nombre' => __('apartments.kitchen')],
                                                ['icono' => '<i class="fas fa-tshirt"></i>', 'nombre' => __('apartments.washing_machine')],
                                                ['icono' => '<i class="fas fa-swimming-pool"></i>', 'nombre' => __('apartment_detail.swimming_pool')],
                                                ['icono' => '<i class="fas fa-elevator"></i>', 'nombre' => __('apartments.elevator')],
                                            ];
                                            
                                            // Mostrar hasta 4 servicios (puedes personalizar esta lista por apartamento si quieres)
                                            $serviciosAMostrar = array_slice($serviciosComunes, 0, 4);
                                        @endphp
                                        
                                        @if(!empty($serviciosAMostrar))
                                            <div class="booking-portal-property-services" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #E0E0E0;">
                                                <div class="booking-portal-property-services-list">
                                                    @foreach($serviciosAMostrar as $servicio)
                                                        <div class="booking-portal-property-service-item">
                                                            {!! $servicio['icono'] !!}
                                                            <span>{{ $servicio['nombre'] }}</span>
                                                        </div>
                                                    @endforeach
                                                    @if(count($serviciosComunes) > 4)
                                                        <div class="booking-portal-property-service-more">
                                                            +{{ count($serviciosComunes) - 4 }} {{ __('portal.more') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        <!-- Ver información completa -->
                                        <div style="margin-top: 12px;">
                                            <a href="javascript:void(0)" onclick="mostrarInfoApartamento({{ $ap->id }})"
                                               style="color: #003580; font-weight: 600; font-size: 13px; text-decoration: none;">
                                                <i class="fas fa-info-circle" style="margin-right: 4px;"></i>Ver información completa
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal de información del apartamento (oculto) -->
                                <div id="info-modal-{{ $ap->id }}" style="display:none;">
                                    <h3>{{ $ap->titulo ?? $ap->nombre }}</h3>
                                    <p><strong>Ubicación:</strong> {{ optional($ap->edificioName)->nombre ?? 'Algeciras' }}</p>
                                    @if($ap->bedrooms)<p><strong>Dormitorios:</strong> {{ $ap->bedrooms }}</p>@endif
                                    @if($ap->bathrooms)<p><strong>Baños:</strong> {{ number_format($ap->bathrooms, 1) }}</p>@endif
                                    @if($ap->size)<p><strong>Superficie:</strong> {{ $ap->size }} m²</p>@endif
                                    @if($ap->max_guests)<p><strong>Máximo huéspedes:</strong> {{ $ap->max_guests }}</p>@endif
                                    @if($ap->description)
                                        <hr>
                                        <h4>Descripción</h4>
                                        <div>{!! $ap->getTranslated('description') !!}</div>
                                    @endif
                                    <hr>
                                    <h4>Servicios incluidos</h4>
                                    <p>WiFi gratis, Aire acondicionado, TV, Cocina, Ascensor, Parking</p>
                                </div>

                                <!-- Sección de Precio y Botón -->
                                <div class="booking-portal-property-price-section">
                                    <div class="booking-portal-property-price">
                                        <div class="booking-portal-property-price-label">{{ __('portal.price_per_night') }}</div>
                                        @if(isset($ap->precio_por_noche) && $ap->precio_por_noche)
                                            <div class="booking-portal-property-price-amount">€ {{ number_format($ap->precio_por_noche, 2, ',', '.') }}</div>
                                        @else
                                            <div class="booking-portal-property-price-amount" style="font-size: 14px; color: #999;">{{ __('common.pending_calculation') }}</div>
                                        @endif
                                        <div class="booking-portal-property-price-note">{{ __('portal.includes_taxes') }}</div>
                                    </div>
                                    @if(!empty($fechaEntrada) && !empty($fechaSalida))
                                        <a href="{{ route('web.reservas.formulario', array_filter(['apartamento' => $ap->id, 'fecha_entrada' => $fechaEntrada, 'fecha_salida' => $fechaSalida, 'adultos' => $adultos ?? 2, 'ninos' => $ninos ?? 0])) }}"
                                           class="booking-portal-property-button" style="background: #28a745;">
                                            <i class="fas fa-credit-card" style="margin-right: 6px;"></i>Reservar
                                        </a>
                                    @else
                                        <a href="javascript:void(0)" onclick="alertSeleccionarFechas()"
                                           class="booking-portal-property-button" style="background: #28a745;">
                                            <i class="fas fa-credit-card" style="margin-right: 6px;"></i>Reservar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="booking-empty-state">
                        <i class="fas fa-search"></i>
                        <h3>{{ __('portal.no_apartments') }}</h3>
                        <p>{{ __('portal.no_apartments_message') }}</p>
                        <div style="margin-top: var(--spacing-md);">
                            <a href="{{ route('web.index') }}" class="booking-btn booking-btn-primary">
                                <i class="fas fa-redo"></i>
                                {{ __('portal.new_search') }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
</div>
@endsection

@section('scripts')
<script>
    // Carousel de imágenes por apartamento
    var carouselState = {};
    function carouselNav(apId, dir) {
        var imgs = document.querySelectorAll('.carousel-img-' + apId);
        if (imgs.length <= 1) return;
        if (!carouselState[apId]) carouselState[apId] = 0;
        imgs[carouselState[apId]].style.display = 'none';
        carouselState[apId] = (carouselState[apId] + dir + imgs.length) % imgs.length;
        imgs[carouselState[apId]].style.display = 'block';
        var counter = document.getElementById('carousel-counter-' + apId);
        if (counter) counter.textContent = carouselState[apId] + 1;
    }

    // Alerta de seleccionar fechas
    function alertSeleccionarFechas() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Selecciona las fechas',
                text: 'Debe seleccionar la fecha de entrada y salida antes de reservar.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#003580'
            });
        } else {
            alert('Debe seleccionar la fecha de entrada y salida antes de reservar.');
        }
    }

    // Modal de información del apartamento
    function mostrarInfoApartamento(apId) {
        var modal = document.getElementById('info-modal-' + apId);
        if (!modal) return;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '',
                html: modal.innerHTML,
                width: '600px',
                showConfirmButton: true,
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#003580',
                customClass: { htmlContainer: 'text-left' }
            });
        } else {
            alert(modal.textContent);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Función para formatear fecha sin problemas de zona horaria
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Función para inicializar Flatpickr en un formulario
        function initFlatpickr(inputId, hiddenInputId, salidaInputId, formId) {
            const fechaEntradaInput = document.getElementById(inputId);
            if (!fechaEntradaInput) return null;
            
            const fechaSalidaInput = document.getElementById(salidaInputId);
            const fechaEntradaHidden = document.getElementById(hiddenInputId);
            
            const flatpickrInstance = flatpickr("#" + inputId, {
                locale: "es",
                dateFormat: "Y-m-d",
                minDate: "today",
                mode: "range",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        const fechaInicio = formatDate(selectedDates[0]);
                        const fechaFin = formatDate(selectedDates[1]);
                        fechaEntradaInput.value = `${fechaInicio} - ${fechaFin}`;
                        if (fechaEntradaHidden) fechaEntradaHidden.value = fechaInicio;
                        if (fechaSalidaInput) fechaSalidaInput.value = fechaFin;
                    } else if (selectedDates.length === 1) {
                        const fecha = formatDate(selectedDates[0]);
                        fechaEntradaInput.value = fecha;
                        if (fechaEntradaHidden) fechaEntradaHidden.value = fecha;
                        if (fechaSalidaInput) fechaSalidaInput.value = '';
                    }
                }
            });
            
            // Si hay fechas pre-cargadas, establecerlas
            @if(isset($fechaEntrada) && isset($fechaSalida) && $fechaEntrada && $fechaSalida)
                if (inputId === 'fecha_entrada') {
                    const fechaInicio = new Date('{{ $fechaEntrada }}');
                    const fechaFin = new Date('{{ $fechaSalida }}');
                    flatpickrInstance.setDate([fechaInicio, fechaFin], false);
                }
            @endif
            
            // Interceptar el submit del formulario
            const searchForm = document.getElementById(formId);
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    const selectedDates = flatpickrInstance.selectedDates;
                    if (selectedDates.length === 2) {
                        const fechaInicio = formatDate(selectedDates[0]);
                        const fechaFin = formatDate(selectedDates[1]);
                        if (fechaEntradaHidden) fechaEntradaHidden.value = fechaInicio;
                        if (fechaSalidaInput) fechaSalidaInput.value = fechaFin;
                    }
                });
            }
            
            return flatpickrInstance;
        }
        
        // Inicializar Flatpickr para el formulario compacto (si existe)
        initFlatpickr('fecha_entrada', 'fecha_entrada_hidden', 'fecha_salida', 'compact-search-form');
        
        // Gestión de huéspedes (genérico)
        window.changeGuests = function(type, delta) {
            const countEl = document.getElementById(type + '-count');
            const hiddenInput = document.getElementById(type);
            if (!countEl || !hiddenInput) return;
            
            let current = parseInt(countEl.textContent) || 0;
            current += delta;
            if (current < 0) current = 0;
            if (type.includes('adultos') && current < 1) current = 1;
            if (type.includes('ninos') && current > 10) current = 10;
            countEl.textContent = current;
            hiddenInput.value = current;
        };
        
        // Guardar huéspedes (para búsqueda activa)
        window.saveGuests = function(suffix = '') {
            const adultosId = suffix ? 'adultos' + suffix : 'adultos';
            const ninosId = suffix ? 'ninos' + suffix : 'ninos';
            const huespedesId = suffix ? 'huespedes' + suffix : 'huespedes';
            const modalId = suffix ? 'huespedes-modal-' + suffix : 'huespedes-modal';
            
            const adultosCountEl = document.getElementById(adultosId + '-count');
            const ninosCountEl = document.getElementById(ninosId + '-count');
            
            if (!adultosCountEl || !ninosCountEl) return;
            
            const adultos = parseInt(adultosCountEl.textContent) || 2;
            const ninos = parseInt(ninosCountEl.textContent) || 0;
            
            const adultosInput = document.getElementById(adultosId);
            const ninosInput = document.getElementById(ninosId);
            
            if (adultosInput) adultosInput.value = adultos;
            if (ninosInput) ninosInput.value = ninos;
            
            let text = adultos + ' ' + (adultos === 1 ? 'adulto' : 'adultos');
            if (ninos > 0) {
                text += ', ' + ninos + ' ' + (ninos === 1 ? 'niño' : 'niños');
            }
            
            const huespedesInput = document.getElementById(huespedesId);
            if (huespedesInput) {
                huespedesInput.value = text;
            }
            
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        };
        
        // Cerrar modales al hacer click fuera
        const modal = document.getElementById('huespedes-modal');
        if (modal) {
            modal.style.display = 'none';
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        }
        
        
        // Toggle favoritos
        window.toggleFavorite = function(element) {
            element.classList.toggle('active');
            const icon = element.querySelector('i');
            if (element.classList.contains('active')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
        };
    });
</script>
@endsection
