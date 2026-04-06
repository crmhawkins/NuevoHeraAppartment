@extends('layouts.public-booking')

@section('title', 'Reserva Cancelada')

@section('content')
<div style="max-width: 800px; margin: 60px auto; padding: 0 16px; text-align: center;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); padding: 48px;">
        <div style="width: 80px; height: 80px; background: #ffc107; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: white;"></i>
        </div>
        
        <h1 style="font-size: 32px; font-weight: 700; color: #003580; margin-bottom: 16px;">
            {{ __('booking_cancelled.title') }}
        </h1>
        
        <p style="font-size: 18px; color: #666; margin-bottom: 32px;">
            {{ __('booking_cancelled.message') }}
        </p>
        
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 16px; margin-bottom: 32px; text-align: left; border-radius: 4px;">
            <p style="margin: 0; color: #856404;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>{{ __('booking_cancelled.note') }}</strong> {{ __('booking_cancelled.note_text') }} <strong>605 379 329</strong> {{ __('common.or') }} <strong>info@apartamentosalgeciras.com</strong>
            </p>
        </div>
        
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('web.reservas.portal') }}" style="background: #003580; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                <i class="fas fa-search me-2"></i>{{ __('booking_cancelled.search_apartments') }}
            </a>
            <a href="{{ route('web.contacto') }}" style="background: white; color: #003580; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 2px solid #003580;">
                <i class="fas fa-envelope me-2"></i>{{ __('booking_cancelled.contact') }}
            </a>
        </div>
    </div>
</div>
@endsection

