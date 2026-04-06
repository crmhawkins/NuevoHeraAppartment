@extends('layouts.public-booking')

@section('title', 'Comprar Extras para tu Reserva')

@section('content')
<div class="booking-detail-container" style="margin-top: 40px;">
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); padding: 40px;">
            <h1 style="font-size: 28px; font-weight: 700; color: #003580; margin-bottom: 8px; text-align: center;">
                <i class="fas fa-gift me-2"></i>{{ __('extras.title') }}
            </h1>
            <p style="text-align: center; color: #666; margin-bottom: 32px;">
                {{ __('extras.subtitle') }}
            </p>
            
            @if($errors->any() || session('error'))
                <div style="background: #f8d7da; color: #721c24; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                    <strong><i class="fas fa-exclamation-circle me-2"></i>Error:</strong>
                    <ul style="margin: 8px 0 0 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        @if(session('error'))
                            <li>{{ session('error') }}</li>
                        @endif
                    </ul>
                </div>
            @endif
            
            <form action="{{ route('web.extras.mostrar-servicios') }}" method="POST">
                @csrf
                
                <div style="margin-bottom: 24px;">
                    <label for="codigo_reserva" style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">
                        {{ __('extras.reservation_code') }} <span style="color: #dc3545;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="codigo_reserva" 
                        name="codigo_reserva" 
                        value="{{ old('codigo_reserva') }}"
                        placeholder="{{ __('extras.reservation_code_placeholder') }}"
                        required
                        style="width: 100%; padding: 14px 16px; border: 2px solid #E0E0E0; border-radius: 6px; font-size: 16px; transition: border-color 0.2s;"
                        class="{{ $errors->has('codigo_reserva') ? 'is-invalid' : '' }}"
                    >
                    @error('codigo_reserva')
                        <span style="color: #dc3545; font-size: 14px; margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>
                
                <button type="submit" style="width: 100%; background: #003580; color: white; padding: 16px; border: none; border-radius: 6px; font-weight: 600; font-size: 18px; cursor: pointer; transition: background 0.2s;">
                    <i class="fas fa-search me-2"></i>{{ __('extras.search_booking') }}
                </button>
            </form>
            
            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #E0E0E0; text-align: center;">
                <p style="color: #666; font-size: 14px; margin-bottom: 8px;">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ __('extras.no_code') }}
                </p>
                <a href="{{ route('web.contacto') }}" style="color: #003580; text-decoration: none; font-weight: 600;">
                    {{ __('extras.contact_us') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

