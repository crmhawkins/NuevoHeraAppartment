@extends('layouts.public-booking')

@section('title', 'Servicios Disponibles')

@section('content')
<h1 style="font-size: 32px; font-weight: 700; color: #003580; margin-bottom: 32px; text-align: center;">
    {{ __('services.title') }}
</h1>

@if($servicios->count() > 0)
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
        @foreach($servicios as $servicio)
            <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); overflow: hidden; transition: transform 0.2s;">
                @if($servicio->imagen)
                    <div style="width: 100%; height: 200px; overflow: hidden; background: #f5f5f5;">
                        <img src="{{ asset($servicio->imagen) }}" alt="{{ $servicio->getTranslated('nombre') }}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                @else
                    <div style="width: 100%; height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        @if($servicio->icono)
                            <i class="{{ $servicio->icono }}" style="font-size: 64px; color: white; opacity: 0.8;"></i>
                        @else
                            <i class="fas fa-concierge-bell" style="font-size: 64px; color: white; opacity: 0.8;"></i>
                        @endif
                    </div>
                @endif
                
                <div style="padding: 24px;">
                    <h3 style="font-size: 20px; font-weight: 700; color: #003580; margin-bottom: 12px;">
                        {{ $servicio->getTranslated('nombre') }}
                    </h3>
                    <p style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 16px;">
                        {{ $servicio->getTranslated('descripcion') }}
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 24px; font-weight: 700; color: #003580;">
                            {{ number_format($servicio->precio, 2, ',', '.') }} €
                        </span>
                        @if($servicio->esAlquilerCoche())
                            <a href="{{ route('web.servicios.reserva-rango', ['servicio' => $servicio->slug]) }}" style="background: #333; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background 0.2s;">
                                {{ __('services.buy_now') }} →
                            </a>
                        @else
                            <a href="{{ route('web.extras.buscar') }}" style="background: #333; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background 0.2s;">
                                {{ __('services.buy_now') }} →
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);">
        <i class="fas fa-concierge-bell" style="font-size: 64px; color: #ccc; margin-bottom: 16px;"></i>
        <p style="color: #666; font-size: 18px;">{{ __('services.no_services') }}</p>
    </div>
@endif
@endsection
