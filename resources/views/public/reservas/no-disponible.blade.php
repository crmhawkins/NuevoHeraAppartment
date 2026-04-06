@extends('layouts.public-booking')

@section('title', 'Reservas no disponibles temporalmente')

@section('breadcrumb')
<div class="booking-breadcrumb">
    <div class="booking-container-header">
        <div class="booking-breadcrumb-content">
            <a href="{{ route('web.index') }}">Inicio</a>
            <span class="booking-breadcrumb-separator">></span>
            <strong>Reservas no disponibles</strong>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="booking-detail-container" style="margin-top: 24px;">
    <div class="reservation-form-card" style="background: #ffffff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 32px; max-width: 800px; margin: 0 auto;">
        <div style="text-align: center;">
            <div style="font-size: 48px; margin-bottom: 16px; color: #003580;">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h1 style="font-size: 26px; font-weight: 700; color: #003580; margin-bottom: 12px;">
                En este momento no se pueden realizar reservas online
            </h1>
            <p style="font-size: 16px; color: #555; margin-bottom: 24px;">
                Estamos realizando ajustes en nuestro sistema de reservas. 
                Puedes seguir navegando por los apartamentos y consultando la información,
                pero por ahora las nuevas reservas solo se gestionan de forma directa.
            </p>
            <p style="font-size: 16px; color: #555; margin-bottom: 32px;">
                Para reservar, por favor contacta con nosotros por teléfono o WhatsApp
                y te ayudaremos a completar tu reserva.
            </p>

            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; margin-bottom: 32px;">
                <a href="tel:+34622440984" class="apple-btn btn btn-primary" style="min-width: 220px;">
                    <i class="fas fa-phone-alt me-2"></i>Llamar para reservar
                </a>
                <a href="https://wa.me/34622440984" target="_blank" rel="noopener" class="apple-btn btn btn-success" style="min-width: 220px;">
                    <i class="fab fa-whatsapp me-2"></i>Reservar por WhatsApp
                </a>
            </div>

            <a href="{{ route('web.reservas.portal') }}" class="apple-btn btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Volver a ver apartamentos
            </a>
        </div>
    </div>
</div>
@endsection

