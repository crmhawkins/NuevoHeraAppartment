@extends('layouts.public-booking')

@section('title', 'Extras Comprados Exitosamente')

@section('content')
<div style="max-width: 800px; margin: 60px auto; padding: 0 16px; text-align: center;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); padding: 48px;">
        <div style="width: 80px; height: 80px; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <i class="fas fa-check" style="font-size: 40px; color: white;"></i>
        </div>
        
        <h1 style="font-size: 32px; font-weight: 700; color: #003580; margin-bottom: 16px;">
            ¡Extras Comprados Exitosamente!
        </h1>
        
        <p style="font-size: 18px; color: #666; margin-bottom: 32px;">
            Tus servicios adicionales han sido procesados correctamente. Te hemos enviado un email de confirmación.
        </p>
        
        <div style="background: #f8f9fa; border-radius: 8px; padding: 24px; margin-bottom: 32px; text-align: left;">
            <h3 style="font-size: 18px; font-weight: 600; color: #003580; margin-bottom: 16px;">
                <i class="fas fa-info-circle me-2"></i>Detalles
            </h3>
            
            <div style="line-height: 2;">
                <div><strong>Reserva:</strong> {{ $reserva->codigo_reserva }}</div>
                <div><strong>Total Pagado:</strong> {{ number_format($pago->monto, 2, ',', '.') }} €</div>
            </div>
        </div>
        
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('web.index') }}" style="background: #003580; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                <i class="fas fa-home me-2"></i>Volver al Inicio
            </a>
            <a href="{{ route('web.extras.buscar') }}" style="background: white; color: #003580; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 2px solid #003580;">
                <i class="fas fa-gift me-2"></i>Comprar Más Extras
            </a>
        </div>
    </div>
</div>
@endsection

