@extends('layouts.public-booking')

@section('title', __('services.availability_result') . ' - ' . $servicio->getTranslated('nombre'))

@section('content')
<div class="booking-detail-container" style="margin-top: 40px;">
    <div style="max-width: 640px; margin: 0 auto; padding: 0 16px;">
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); overflow: hidden;">
            {{-- Cabecera del servicio --}}
            @if($servicio->imagen)
                <div style="width: 100%; height: 180px; overflow: hidden; background: #f5f5f5;">
                    <img src="{{ asset($servicio->imagen) }}" alt="{{ $servicio->getTranslated('nombre') }}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            @else
                <div style="width: 100%; height: 180px; background: linear-gradient(135deg, #003580 0%, #0056b3 100%); display: flex; align-items: center; justify-content: center;">
                    @if($servicio->icono)
                        <i class="{{ $servicio->icono }}" style="font-size: 64px; color: white; opacity: 0.9;"></i>
                    @else
                        <i class="fas fa-car" style="font-size: 64px; color: white; opacity: 0.9;"></i>
                    @endif
                </div>
            @endif

            <div style="padding: 28px;">
                <h1 style="font-size: 24px; font-weight: 700; color: #003580; margin-bottom: 8px;">
                    {{ $servicio->getTranslated('nombre') }}
                </h1>
                <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
                    {{ __('services.period') }}: {{ \Carbon\Carbon::parse($fecha_entrada)->translatedFormat('d/m/Y') }} – {{ \Carbon\Carbon::parse($fecha_salida)->translatedFormat('d/m/Y') }}
                </p>

                @if($disponible)
                    <div style="background: #d4edda; color: #155724; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                        <strong><i class="fas fa-check-circle me-2"></i>{{ __('services.available') }}</strong>
                        <p style="margin: 8px 0 0 0; font-size: 14px;">{{ __('services.available_message') }}</p>
                    </div>
                @else
                    <div style="background: #f8d7da; color: #721c24; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                        <strong><i class="fas fa-times-circle me-2"></i>{{ __('services.not_available') }}</strong>
                        <p style="margin: 8px 0 0 0; font-size: 14px;">{{ __('services.not_available_message') }}</p>
                    </div>
                @endif

                <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                    <a href="{{ route('web.servicios.reserva-rango', ['servicio' => $servicio->slug]) }}" style="display: inline-flex; align-items: center; padding: 12px 20px; background: #003580; color: white; border-radius: 6px; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-calendar-alt me-2"></i>{{ __('services.try_other_dates') }}
                    </a>
                    <a href="{{ route('web.servicios') }}" style="display: inline-flex; align-items: center; padding: 12px 20px; background: #f0f0f0; color: #333; border-radius: 6px; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-arrow-left me-2"></i>{{ __('services.back_to_services') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
