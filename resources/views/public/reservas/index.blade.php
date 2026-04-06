@extends('layouts.public-booking')

@section('title', __('home.page_title'))

@section('styles')
    /* HERO SECTION - Ancho completo */
    .booking-portal-hero {
        width: 100%;
        margin: 0;
        padding: 60px 0;
        background: #003580;
        position: relative;
    }
    
    /* BARRA DE BÚSQUEDA PROMINENTE ESTILO BOOKING.COM */
    .booking-portal-search-bar {
        background: white;
        border: 3px solid #FFB700;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        width: 100%;
        box-sizing: border-box;
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
    
    @media (max-width: 968px) {
        .booking-portal-search-form {
            grid-template-columns: 1fr;
        }
    }
    
    /* SECCIÓN DE SERVICIOS ADICIONALES */
    .booking-portal-services-section {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 16px;
    }
    
    .booking-portal-services-title {
        font-size: 28px;
        font-weight: 700;
        color: #333;
        margin-bottom: 24px;
    }
    
    .booking-portal-services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .booking-portal-service-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        height: 280px;
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .booking-portal-service-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }
    
    .booking-portal-service-card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }
    
    .booking-portal-service-card-overlay {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.7) 30%, rgba(0, 0, 0, 0.5) 60%, rgba(0, 0, 0, 0.3) 100%);
        padding: 24px;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }
    
    .booking-portal-service-card-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 8px;
        color: white;
    }
    
    .booking-portal-service-card-description {
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 16px;
        color: rgba(255, 255, 255, 0.95);
    }
    
    .booking-portal-service-card-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: white;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: gap 0.2s ease;
    }
    
    .booking-portal-service-card-button:hover {
        gap: 12px;
        color: white;
        text-decoration: none;
    }
    
    .booking-portal-service-card-button i {
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .booking-portal-services-grid {
            grid-template-columns: 1fr;
        }
        
        .booking-portal-service-card {
            height: 240px;
        }
    }
    
    /* SECCIÓN DE CARACTERÍSTICAS */
    .booking-portal-features-section {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 16px;
    }
    
    .booking-portal-features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 32px;
    }
    
    .booking-portal-feature-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 24px;
    }
    
    .booking-portal-feature-icon {
        width: 64px;
        height: 64px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .booking-portal-feature-icon i {
        font-size: 48px;
        color: #333;
    }
    
    .booking-portal-feature-title {
        font-size: 18px;
        font-weight: 700;
        color: #333;
        margin-bottom: 8px;
    }
    
    .booking-portal-feature-subtitle {
        font-size: 14px;
        color: #666;
        line-height: 1.5;
    }
    
    @media (max-width: 768px) {
        .booking-portal-features-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
    }
    
    @media (max-width: 480px) {
        .booking-portal-features-grid {
            grid-template-columns: 1fr;
        }
    }
@endsection

@section('hero')
<!-- HERO SECTION CON FORMULARIO DE BÚSQUEDA -->
<div class="booking-portal-hero" style="position: relative; background: #003580; padding: 60px 0; width: 100%;">
    <div class="booking-container-header" style="position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 0 16px; width: 100%;">
        <!-- TÍTULO -->
        <div style="margin-bottom: 40px; text-align: center;">
            <h1 style="font-size: 48px; font-weight: 700; margin: 0; color: white;">{{ __('home.hero_title') }}</h1>
        </div>
        
        <!-- FORMULARIO DE BÚSQUEDA EN HERO -->
        <div class="booking-portal-search-bar" style="max-width: 900px; margin: 0 auto;">
        <form method="GET" action="{{ route('web.reservas.portal') }}" class="booking-portal-search-form" id="hero-search-form">
            <div class="booking-portal-search-field">
                <label for="fecha_entrada_hero">Fechas</label>
                <input type="text" 
                       id="fecha_entrada_hero" 
                       value=""
                       placeholder="{{ __('portal.dates_placeholder') }}"
                       required
                       readonly>
                <input type="hidden" 
                       id="fecha_entrada_hidden_hero" 
                       name="fecha_entrada" 
                       value="">
                <input type="hidden" 
                       id="fecha_salida_hero" 
                       name="fecha_salida" 
                       value="">
            </div>
            <div class="booking-portal-search-field booking-portal-search-field-huespedes">
                <label for="huespedes_hero">{{ __('portal.guests') }}</label>
                <div style="position: relative;">
                    <input type="text" 
                           id="huespedes_hero" 
                           name="huespedes" 
                           value="2 {{ __('reservation.adults') }}"
                           placeholder="{{ __('portal.guests_placeholder') }}"
                           readonly
                           onclick="const modal = document.getElementById('huespedes-modal-hero'); if(modal) { modal.style.display = 'flex'; }">
                    <i class="fas fa-chevron-down chevron-icon"></i>
                </div>
                <input type="hidden" id="adultos_hero" name="adultos" value="2">
                <input type="hidden" id="ninos_hero" name="ninos" value="0">
            </div>
            <button type="submit" class="booking-portal-search-submit">
                <i class="fas fa-search"></i>
                {{ __('portal.search') }}
            </button>
        </form>
        </div>
    </div>
</div>
@endsection

@section('content')
<!-- CONTENIDO PRINCIPAL -->
<div class="booking-detail-container" style="margin-top: 0;">
    <!-- Modal para seleccionar huéspedes -->
    <div id="huespedes-modal-hero" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 24px; border-radius: 8px; max-width: 400px; width: 90%;">
            <h3 style="margin: 0 0 20px 0;">{{ __('portal.select_guests') }}</h3>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <label>{{ __('portal.adults_label') }}</label>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button" onclick="changeGuests('adultos_hero', -1)" style="width: 32px; height: 32px; border: 2px solid #E0E0E0; background: white; border-radius: 4px; cursor: pointer;">-</button>
                    <span id="adultos_hero-count">2</span>
                    <button type="button" onclick="changeGuests('adultos_hero', 1)" style="width: 32px; height: 32px; border: 2px solid #E0E0E0; background: white; border-radius: 4px; cursor: pointer;">+</button>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <label>{{ __('portal.children_label') }}</label>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button" onclick="changeGuests('ninos_hero', -1)" style="width: 32px; height: 32px; border: 2px solid #E0E0E0; background: white; border-radius: 4px; cursor: pointer;">-</button>
                    <span id="ninos_hero-count">0</span>
                    <button type="button" onclick="changeGuests('ninos_hero', 1)" style="width: 32px; height: 32px; border: 2px solid #E0E0E0; background: white; border-radius: 4px; cursor: pointer;">+</button>
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('huespedes-modal-hero').style.display='none'" style="padding: 10px 20px; border: 2px solid #E0E0E0; background: white; border-radius: 6px; cursor: pointer; font-weight: 600;">{{ __('portal.cancel') }}</button>
                <button type="button" onclick="saveGuests('hero')" style="padding: 10px 20px; background: #003580; color: white; border: none; border-radius: 6px; cursor: pointer;">{{ __('portal.apply') }}</button>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE SERVICIOS ADICIONALES -->
    <div class="booking-portal-services-section">
        <h2 class="booking-portal-services-title">{{ __('reservations.services_title') }}</h2>
        <div class="booking-portal-services-grid">
            <!-- Check-in temprano -->
            <div class="booking-portal-service-card">
                <img src="{{ asset('check-in.jpeg') }}" alt="{{ __('reservations.early_checkin') }}" class="booking-portal-service-card-image">
                <div class="booking-portal-service-card-overlay">
                    <h3 class="booking-portal-service-card-title">{{ __('reservations.early_checkin') }}</h3>
                    <p class="booking-portal-service-card-description">{{ __('reservations.early_checkin_desc') }}</p>
                    <a href="{{ route('web.extras.buscar') }}" class="booking-portal-service-card-button">
                        {{ __('reservations.buy_now') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Check-out tardío -->
            <div class="booking-portal-service-card">
                <img src="{{ asset('check-out.jpeg') }}" alt="{{ __('reservations.late_checkout') }}" class="booking-portal-service-card-image">
                <div class="booking-portal-service-card-overlay">
                    <h3 class="booking-portal-service-card-title">{{ __('reservations.late_checkout') }}</h3>
                    <p class="booking-portal-service-card-description">{{ __('reservations.late_checkout_desc') }}</p>
                    <a href="{{ route('web.extras.buscar') }}" class="booking-portal-service-card-button">
                        {{ __('reservations.buy_now') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Limpieza extra -->
            <div class="booking-portal-service-card">
                <img src="{{ asset('limpieza-extra.jpeg') }}" alt="{{ __('reservations.extra_cleaning') }}" class="booking-portal-service-card-image">
                <div class="booking-portal-service-card-overlay">
                    <h3 class="booking-portal-service-card-title">{{ __('reservations.extra_cleaning') }}</h3>
                    <p class="booking-portal-service-card-description">{{ __('reservations.extra_cleaning_desc') }}</p>
                    <a href="{{ route('web.extras.buscar') }}" class="booking-portal-service-card-button">
                        {{ __('reservations.buy_now') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Mascotas -->
            <div class="booking-portal-service-card">
                <img src="{{ asset('mascotas-perro.png') }}" alt="{{ __('reservations.pets') }}" class="booking-portal-service-card-image">
                <div class="booking-portal-service-card-overlay">
                    <h3 class="booking-portal-service-card-title">{{ __('reservations.pets') }}</h3>
                    <p class="booking-portal-service-card-description">{{ __('reservations.pets_desc') }}</p>
                    <a href="{{ route('web.extras.buscar') }}" class="booking-portal-service-card-button">
                        {{ __('reservations.buy_now') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Ropa de cama & toallas -->
            <div class="booking-portal-service-card">
                <img src="{{ asset('ropa-cama.jpeg') }}" alt="{{ __('reservations.bedding_towels') }}" class="booking-portal-service-card-image">
                <div class="booking-portal-service-card-overlay">
                    <h3 class="booking-portal-service-card-title">{{ __('reservations.bedding_towels') }}</h3>
                    <p class="booking-portal-service-card-description">{{ __('reservations.bedding_towels_desc') }}</p>
                    <a href="{{ route('web.extras.buscar') }}" class="booking-portal-service-card-button">
                        {{ __('reservations.buy_now') }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN INFORMATIVA -->
    <div style="background: #F5F5F5; padding: 60px 0;">
        <div class="booking-container-header" style="max-width: 1200px; margin: 0 auto; padding: 0 16px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px;">
                <div style="background: #003580; color: white; padding: 40px; border-radius: 12px; display: flex; flex-direction: column; gap: 20px;">
                    <div style="font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">{{ __('reservations.comfort_title') }}</div>
                    <h3 style="font-size: 28px; font-weight: 700; margin: 0; color: white;">{{ __('reservations.comfort_heading') }}</h3>
                    <p style="opacity: 0.9; line-height: 1.6;">{{ __('reservations.comfort_text') }}</p>
                </div>
                <div style="background: #003580; color: white; padding: 40px; border-radius: 12px; display: flex; flex-direction: column; gap: 20px;">
                    <div style="font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">{{ __('reservations.location_title') }}</div>
                    <h3 style="font-size: 28px; font-weight: 700; margin: 0; color: white;">{{ __('reservations.location_heading') }}</h3>
                    <p style="opacity: 0.9; line-height: 1.6;">{{ __('reservations.location_text') }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SECCIÓN DE CARACTERÍSTICAS -->
    <div class="booking-portal-features-section">
        <div class="booking-portal-features-grid">
            <!-- Ascensor disponible -->
            <div class="booking-portal-feature-block">
                <div class="booking-portal-feature-icon">
                    <i class="fas fa-elevator"></i>
                </div>
                <h3 class="booking-portal-feature-title">{{ __('footer.elevator_available') }}</h3>
                <p class="booking-portal-feature-subtitle">{{ __('footer.ease_accessibility') }}</p>
            </div>
            
            <!-- Calefacción/Aire acondicionado -->
            <div class="booking-portal-feature-block">
                <div class="booking-portal-feature-icon">
                    <i class="fas fa-snowflake"></i>
                </div>
                <h3 class="booking-portal-feature-title">{{ __('footer.heating_ac') }}</h3>
                <p class="booking-portal-feature-subtitle">{{ __('footer.comfort_season') }}</p>
            </div>
            
            <!-- Mascotas permitidas -->
            <div class="booking-portal-feature-block">
                <div class="booking-portal-feature-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <h3 class="booking-portal-feature-title">{{ __('footer.pets_allowed') }}</h3>
                <p class="booking-portal-feature-subtitle">{{ __('footer.with_supplement') }}</p>
            </div>
            
            <!-- Servicio 24/7 -->
            <div class="booking-portal-feature-block">
                <div class="booking-portal-feature-icon">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h3 class="booking-portal-feature-title">{{ __('footer.service_24_7') }}</h3>
                <p class="booking-portal-feature-subtitle">+34 605 37 93 29</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para formatear fecha sin problemas de zona horaria
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Inicializar Flatpickr para el formulario hero
        const fechaEntradaInput = document.getElementById('fecha_entrada_hero');
        if (fechaEntradaInput) {
            const fechaSalidaInput = document.getElementById('fecha_salida_hero');
            const fechaEntradaHidden = document.getElementById('fecha_entrada_hidden_hero');
            
            const flatpickrInstance = flatpickr("#fecha_entrada_hero", {
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
            
            // Interceptar el submit del formulario
            const searchForm = document.getElementById('hero-search-form');
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
        }
        
        // Gestión de huéspedes
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
        
        // Guardar huéspedes
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
        
        // Cerrar modal al hacer click fuera
        const modalHero = document.getElementById('huespedes-modal-hero');
        if (modalHero) {
            modalHero.style.display = 'none';
            modalHero.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        }
    });
</script>
@endsection

