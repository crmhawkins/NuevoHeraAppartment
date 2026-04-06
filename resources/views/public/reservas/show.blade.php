<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $apartamento->titulo ?? $apartamento->nombre }} - Apartamentos Algeciras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @include('public.reservas.partials.booking-styles')
    <style>
        /* Estilos específicos para página de detalles */
        .property-detail-header {
            background: var(--booking-white);
            padding: var(--spacing-lg) 0;
            border-bottom: 1px solid var(--booking-gray-light);
        }
        
        .property-title-section h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--booking-gray-dark);
            margin: 0 0 var(--spacing-xs) 0;
        }
        
        .property-location-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--booking-gray-medium);
            font-size: var(--font-size-base);
        }
        
        .property-gallery {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--spacing-sm);
            margin: var(--spacing-lg) 0;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .property-gallery-main {
            height: 500px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .property-gallery-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .property-gallery-thumbs {
            display: grid;
            grid-template-rows: repeat(4, 1fr);
            gap: var(--spacing-sm);
        }
        
        .property-gallery-thumb {
            height: 118px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            cursor: pointer;
            overflow: hidden;
            border-radius: 8px;
            position: relative;
        }
        
        .property-gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .property-gallery-thumb:hover img {
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .property-gallery {
                grid-template-columns: 1fr;
            }
            
            .property-gallery-thumbs {
                grid-template-columns: repeat(4, 1fr);
                grid-template-rows: 1fr;
            }
            
            .property-gallery-thumb {
                height: 80px;
            }
            
            .property-gallery-main {
                height: 300px;
            }
        }
        
        .property-info-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--spacing-lg);
            margin: var(--spacing-lg) 0;
        }
        
        @media (max-width: 992px) {
            .property-info-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .property-details-section {
            background: var(--booking-white);
            border-radius: 12px;
            padding: var(--spacing-lg);
            border: 1px solid var(--booking-gray-light);
        }
        
        .property-details-section h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--booking-gray-dark);
            margin: 0 0 var(--spacing-md) 0;
            padding-bottom: var(--spacing-md);
            border-bottom: 2px solid var(--booking-gray-light);
        }
        
        .property-features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-md);
            margin: var(--spacing-md) 0;
        }
        
        .property-feature-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            background: var(--booking-gray-bg);
            border-radius: 8px;
        }
        
        .property-feature-item i {
            color: var(--booking-blue);
            font-size: 20px;
            width: 24px;
        }
        
        .property-feature-item strong {
            color: var(--booking-gray-dark);
            margin-right: var(--spacing-xs);
        }
        
        .property-feature-item span {
            color: var(--booking-gray-medium);
        }
        
        .property-description {
            color: var(--booking-gray-dark);
            line-height: 1.8;
            font-size: var(--font-size-base);
            margin: var(--spacing-md) 0;
        }
        
        .booking-reservation-card {
            background: var(--booking-white);
            border: 2px solid var(--booking-blue);
            border-radius: 12px;
            padding: var(--spacing-lg);
            position: sticky;
            top: var(--spacing-lg);
        }
        
        .booking-reservation-card h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--booking-gray-dark);
            margin: 0 0 var(--spacing-md) 0;
        }
        
        .reservation-summary {
            background: var(--booking-gray-bg);
            padding: var(--spacing-md);
            border-radius: 8px;
            margin: var(--spacing-md) 0;
        }
        
        .reservation-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-small);
        }
        
        .reservation-summary-item:last-child {
            margin-bottom: 0;
            padding-top: var(--spacing-sm);
            border-top: 1px solid var(--booking-gray-light);
            font-weight: 600;
            font-size: var(--font-size-base);
        }
        
        .reservation-price-total {
            font-size: 28px;
            font-weight: 700;
            color: var(--booking-blue);
            margin: var(--spacing-md) 0;
        }
        
        /* Estilos para Markdown */
        .property-description h1,
        .property-description h2,
        .property-description h3,
        .property-description h4 {
            margin-top: var(--spacing-md);
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--booking-gray-dark);
        }
        
        .property-description h1 {
            font-size: 24px;
            border-bottom: 2px solid var(--booking-gray-light);
            padding-bottom: var(--spacing-xs);
        }
        
        .property-description h2 {
            font-size: 20px;
            margin-top: var(--spacing-lg);
        }
        
        .property-description h3 {
            font-size: 18px;
        }
        
        .property-description h4 {
            font-size: 16px;
        }
        
        .property-description p {
            margin-bottom: var(--spacing-sm);
            line-height: 1.8;
        }
        
        .property-description ul,
        .property-description ol {
            margin: var(--spacing-sm) 0;
            padding-left: var(--spacing-lg);
        }
        
        .property-description li {
            margin-bottom: var(--spacing-xs);
            line-height: 1.6;
        }
        
        .property-description hr {
            border: none;
            border-top: 2px solid var(--booking-gray-light);
            margin: var(--spacing-md) 0;
        }
        
        .property-description strong {
            font-weight: 600;
            color: var(--booking-gray-dark);
        }
        
        .property-description em {
            font-style: italic;
        }
        
        .property-description a {
            color: var(--booking-blue);
            text-decoration: underline;
        }
        
        .property-description a:hover {
            color: var(--booking-blue-dark);
        }
    </style>
</head>
<body>
    <div class="booking-portal">
        <!-- Header -->
        <div class="property-detail-header">
            <div class="booking-container">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="property-title-section">
                        <h1>{{ $apartamento->titulo ?? $apartamento->nombre }}</h1>
                        <div class="property-location-badge">
                            <i class="fas fa-map-marker-alt"></i>
                            {{ optional($apartamento->edificioName)->nombre ?? 'Algeciras' }}
                            @if($apartamento->address)
                                · {{ $apartamento->address }}
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('web.reservas.portal', request()->only(['fecha_entrada', 'fecha_salida', 'adultos', 'ninos'])) }}" 
                       class="booking-btn booking-btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        {{ __('apartment_detail.back_to_results') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="booking-container">
            <!-- Gallery -->
            @if($apartamento->photos && $apartamento->photos->isNotEmpty())
                <div class="property-gallery">
                    <div class="property-gallery-main">
                        <img id="mainImage" 
                             src="{{ asset('storage/' . $apartamento->photos->first()->path) }}" 
                             alt="{{ $apartamento->titulo ?? $apartamento->nombre }}">
                    </div>
                    <div class="property-gallery-thumbs">
                        @foreach($apartamento->photos->take(4) as $photo)
                            <div class="property-gallery-thumb" onclick="changeMainImage('{{ asset('storage/' . $photo->path) }}')">
                                <img src="{{ asset('storage/' . $photo->path) }}" alt="Thumbnail">
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="property-gallery">
                    <div class="property-gallery-main">
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 120px; opacity: 0.3;">
                            🏠
                        </div>
                    </div>
                </div>
            @endif

            <!-- Content Grid -->
            <div class="property-info-grid">
                <!-- Left Column: Details -->
                <div>
                    <!-- Description -->
                    @if($apartamento->description)
                        <div class="property-details-section">
                            <h2>
                                <i class="fas fa-info-circle me-2"></i>
                                {{ __('apartment_detail.about_apartment') }}
                            </h2>
                            <div class="property-description">
                                {!! $apartamento->description !!}
                            </div>
                        </div>
                    @endif

                    <!-- Features -->
                    <div class="property-details-section">
                        <h2>
                            <i class="fas fa-list me-2"></i>
                            {{ __('apartment_detail.features') }}
                        </h2>
                        <div class="property-features-grid">
                            @if($apartamento->max_guests)
                                <div class="property-feature-item">
                                    <i class="fas fa-users"></i>
                                    <div>
                                        <strong>{{ __('apartment_detail.capacity') }}</strong>
                                        <span>{{ __('apartments.up_to_guests', ['count' => $apartamento->max_guests]) }}</span>
                                    </div>
                                </div>
                            @endif
                            @if($apartamento->bedrooms)
                                <div class="property-feature-item">
                                    <i class="fas fa-bed"></i>
                                    <div>
                                        <strong>{{ __('apartment_detail.rooms') }}</strong>
                                        <span>{{ $apartamento->bedrooms }} {{ $apartamento->bedrooms == 1 ? __('apartment_detail.room') : __('apartment_detail.rooms_plural') }}</span>
                                    </div>
                                </div>
                            @endif
                            @if($apartamento->bathrooms)
                                <div class="property-feature-item">
                                    <i class="fas fa-bath"></i>
                                    <div>
                                        <strong>{{ __('apartments.bathrooms') }}:</strong>
                                        <span>{{ number_format($apartamento->bathrooms, 1, ',', '.') }} {{ $apartamento->bathrooms == 1 ? __('apartments.bathroom') : __('apartments.bathrooms') }}</span>
                                    </div>
                                </div>
                            @endif
                            @if($apartamento->size)
                                <div class="property-feature-item">
                                    <i class="fas fa-ruler-combined"></i>
                                    <div>
                                        <strong>{{ __('apartment_detail.surface') }}</strong>
                                        <span>{{ $apartamento->size }} {{ __('apartments.square_meters') }}</span>
                                    </div>
                                </div>
                            @endif
                            @if($apartamento->property_type)
                                <div class="property-feature-item">
                                    <i class="fas fa-home"></i>
                                    <div>
                                        <strong>Tipo:</strong>
                                        <span>{{ ucfirst($apartamento->property_type) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Amenities / Servicios -->
                    @php
                        $amenities = [];
                        if ($apartamento->wifi) $amenities[] = ['icon' => 'wifi', 'label' => __('apartments.wifi') . ($apartamento->wifi_free ? __('apartment_detail.wifi_free') : '')];
                        if ($apartamento->parking) $amenities[] = ['icon' => 'parking', 'label' => __('apartments.parking') . ($apartamento->parking_free ? __('apartment_detail.parking_free') : '')];
                        if ($apartamento->air_conditioning) $amenities[] = ['icon' => 'snowflake', 'label' => __('apartments.air_conditioning')];
                        if ($apartamento->heating) $amenities[] = ['icon' => 'thermometer-half', 'label' => __('apartment_detail.heating')];
                        if ($apartamento->kitchen) $amenities[] = ['icon' => 'utensils', 'label' => __('apartments.kitchen') . ($apartamento->kitchen_fully_equipped ? __('apartment_detail.fully_equipped') : '')];
                        if ($apartamento->tv) $amenities[] = ['icon' => 'tv', 'label' => __('apartments.tv') . ($apartamento->cable_tv ? __('apartment_detail.cable_tv') : '')];
                        if ($apartamento->dishwasher) $amenities[] = ['icon' => 'utensils', 'label' => __('apartment_detail.dishwasher')];
                        if ($apartamento->washing_machine) $amenities[] = ['icon' => 'tshirt', 'label' => __('apartments.washing_machine')];
                        if ($apartamento->balcony) $amenities[] = ['icon' => 'door-open', 'label' => __('apartment_detail.balcony')];
                        if ($apartamento->terrace) $amenities[] = ['icon' => 'home', 'label' => __('apartment_detail.terrace')];
                        if ($apartamento->swimming_pool) $amenities[] = ['icon' => 'swimming-pool', 'label' => __('apartment_detail.swimming_pool')];
                        if ($apartamento->elevator) $amenities[] = ['icon' => 'arrow-up', 'label' => __('apartments.elevator')];
                        if ($apartamento->pets_allowed) $amenities[] = ['icon' => 'paw', 'label' => __('apartment_detail.pets_allowed')];
                        if ($apartamento->safe) $amenities[] = ['icon' => 'lock', 'label' => __('apartment_detail.safe')];
                        if ($apartamento->workspace) $amenities[] = ['icon' => 'laptop', 'label' => __('apartment_detail.workspace')];
                    @endphp
                    
                    @if(count($amenities) > 0)
                        <div class="property-details-section">
                            <h2>
                                <i class="fas fa-star me-2"></i>
                                {{ __('apartment_detail.amenities') }}
                            </h2>
                            <div class="property-features-grid">
                                @foreach($amenities as $amenity)
                                    <div class="property-feature-item">
                                        <i class="fas fa-{{ $amenity['icon'] }}"></i>
                                        <span>{{ $amenity['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Check-in / Check-out -->
                    @if($apartamento->check_in_time || $apartamento->check_out_time)
                        <div class="property-details-section">
                            <h2>
                                <i class="fas fa-calendar-check me-2"></i>
                                {{ __('apartment_detail.checkin_checkout') }}
                            </h2>
                            <div class="property-features-grid">
                                @if($apartamento->check_in_time)
                                    <div class="property-feature-item">
                                        <i class="fas fa-sign-in-alt"></i>
                                        <div>
                                            <strong>{{ __('apartment_detail.checkin') }}</strong>
                                            <span>{{ \Carbon\Carbon::parse($apartamento->check_in_time)->format('H:i') }}</span>
                                        </div>
                                    </div>
                                @endif
                                @if($apartamento->check_out_time)
                                    <div class="property-feature-item">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <div>
                                            <strong>{{ __('apartment_detail.checkout') }}</strong>
                                            <span>{{ \Carbon\Carbon::parse($apartamento->check_out_time)->format('H:i') }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @if($apartamento->check_in_instructions)
                                <div class="property-description mt-3">
                                    <strong>{{ __('apartment_detail.checkin_instructions') }}</strong><br>
                                    {{ $apartamento->check_in_instructions }}
                                </div>
                            @endif
                            @if($apartamento->check_out_instructions)
                                <div class="property-description mt-2">
                                    <strong>{{ __('apartment_detail.checkout_instructions') }}</strong><br>
                                    {{ $apartamento->check_out_instructions }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Reglas de la casa -->
                    @if($apartamento->house_rules)
                        <div class="property-details-section">
                            <h2>
                                <i class="fas fa-gavel me-2"></i>
                                {{ __('apartment_detail.house_rules') }}
                            </h2>
                            <div class="property-description" style="line-height: 1.8;">
                                {!! \App\Helpers\MarkdownHelper::toHtml($apartamento->house_rules) !!}
                            </div>
                        </div>
                    @endif

                    <!-- Política de cancelación -->
                    @if($apartamento->cancellation_policy)
                        <div class="property-details-section">
                            <h2>
                                <i class="fas fa-info-circle me-2"></i>
                                {{ __('apartment_detail.cancellation_policy') }}
                            </h2>
                            <div class="property-description">
                                <strong>{{ ucfirst(str_replace('_', ' ', $apartamento->cancellation_policy)) }}</strong>
                                @if($apartamento->cancellation_deadline)
                                    <br><small>{{ str_replace(':days', $apartamento->cancellation_deadline, __('apartment_detail.free_cancellation')) }}</small>
                                @endif
                                @if($apartamento->cancellation_details)
                                    <br><br>{{ $apartamento->cancellation_details }}
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Ubicación cercana -->
                    @if($apartamento->nearest_beach_distance || $apartamento->nearest_airport_distance || $apartamento->public_transport_nearby)
                        <div class="property-details-section">
                            <h2>
                                <i class="fas fa-map-marked-alt me-2"></i>
                                {{ __('apartment_detail.what_nearby') }}
                            </h2>
                            <div class="property-features-grid">
                                @if($apartamento->nearest_beach_name)
                                    <div class="property-feature-item">
                                        <i class="fas fa-umbrella-beach"></i>
                                        <div>
                                            <strong>{{ $apartamento->nearest_beach_name }}</strong>
                                            @if($apartamento->nearest_beach_distance)
                                                <span> - {{ $apartamento->nearest_beach_distance }} km</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                @if($apartamento->nearest_airport_name)
                                    <div class="property-feature-item">
                                        <i class="fas fa-plane"></i>
                                        <div>
                                            <strong>{{ $apartamento->nearest_airport_name }}</strong>
                                            @if($apartamento->nearest_airport_distance)
                                                <span> - {{ $apartamento->nearest_airport_distance }} km</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                @if($apartamento->public_transport_nearby)
                                    <div class="property-feature-item">
                                        <i class="fas fa-bus"></i>
                                        <span>{{ __('apartment_detail.public_transport') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Location -->
                    @if($apartamento->address || ($apartamento->latitude && $apartamento->longitude))
                        <div class="property-details-section">
                            <h2>
                                <i class="fas fa-map-marker-alt me-2"></i>
                                {{ __('apartment_detail.location') }}
                            </h2>
                            @if($apartamento->address)
                                <p class="property-description">
                                    <strong>{{ __('apartment_detail.address') }}</strong> {{ $apartamento->address }}
                                </p>
                            @endif
                            @if($apartamento->latitude && $apartamento->longitude)
                                <div style="height: 300px; background: var(--booking-gray-bg); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--booking-gray-medium);">
                                    <div>
                                        <i class="fas fa-map" style="font-size: 48px; margin-bottom: var(--spacing-sm);"></i>
                                        <p>{{ __('apartment_detail.interactive_map') }}</p>
                                        <small>{{ __('apartment_detail.coordinates') }} {{ $apartamento->latitude }}, {{ $apartamento->longitude }}</small>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Important Information -->
                    @if($apartamento->important_information)
                        <div class="property-details-section">
                            <h2>
                                <i class="fas fa-exclamation-circle me-2"></i>
                                {{ __('apartment_detail.important_info') }}
                            </h2>
                            <div class="property-description">
                                {!! $apartamento->important_information !!}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Column: Reservation Card -->
                <div>
                    <div class="booking-reservation-card">
                        <h3>
                            <i class="fas fa-calendar-check me-2"></i>
                            {{ __('apartment_detail.book_now') }}
                        </h3>
                        
                        @if($fechaEntrada && $fechaSalida)
                            <div class="reservation-summary">
                                <div class="reservation-summary-item">
                                    <span>{{ __('apartment_detail.checkin') }}</span>
                                    <strong>{{ \Carbon\Carbon::parse($fechaEntrada)->format('d/m/Y') }}</strong>
                                </div>
                                <div class="reservation-summary-item">
                                    <span>{{ __('apartment_detail.checkout') }}</span>
                                    <strong>{{ \Carbon\Carbon::parse($fechaSalida)->format('d/m/Y') }}</strong>
                                </div>
                                <div class="reservation-summary-item">
                                    <span>{{ __('apartment_detail.guests') }}</span>
                                    <strong>{{ $adultos }} {{ $adultos == 1 ? __('reservation.adult') : __('reservation.adults') }}{{ $ninos > 0 ? ', ' . $ninos . ' ' . ($ninos == 1 ? __('reservation.child') : __('reservation.children')) : '' }}</strong>
                                </div>
                                <div class="reservation-summary-item">
                                    <span>{{ __('apartment_detail.nights') }}</span>
                                    <strong>{{ \Carbon\Carbon::parse($fechaEntrada)->diffInDays(\Carbon\Carbon::parse($fechaSalida)) }}</strong>
                                </div>
                            </div>
                            
                            <div class="reservation-price-total">
                                {{ __('apartment_detail.total') }}: <span style="color: var(--booking-error);">{{ __('common.pending_calculation') }}</span>
                            </div>
                            
                            <button class="booking-btn booking-btn-primary" style="width: 100%;" disabled>
                                <i class="fas fa-lock me-2"></i>
                                {{ __('apartment_detail.book_now') }}
                            </button>
                        @else
                            <div class="booking-alert booking-alert-info">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>{{ __('apartment_detail.select_dates') }}</strong>
                                    <p style="margin: 0; margin-top: 8px;">{{ __('apartment_detail.complete_form') }}</p>
                                </div>
                            </div>
                        @endif
                        
                        <a href="{{ route('web.reservas.portal', request()->only(['fecha_entrada', 'fecha_salida', 'adultos', 'ninos'])) }}" 
                           class="booking-btn booking-btn-secondary" style="width: 100%; margin-top: var(--spacing-sm);">
                            <i class="fas fa-search me-2"></i>
                            {{ __('apartment_detail.modify_dates') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
        }
    </script>
</body>
</html>


