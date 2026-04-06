@extends('layouts.public-booking')

@section('title', 'Compra Cancelada')

@section('content')
<div style="max-width: 800px; margin: 60px auto; padding: 0 16px; text-align: center;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); padding: 48px;">
        <div style="width: 80px; height: 80px; background: #ffc107; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: white;"></i>
        </div>
        
        <h1 style="font-size: 32px; font-weight: 700; color: #003580; margin-bottom: 16px;">
            Compra Cancelada
        </h1>
        
        <p style="font-size: 18px; color: #666; margin-bottom: 32px;">
            El proceso de pago ha sido cancelado. Tus servicios no han sido añadidos a la reserva.
        </p>
        
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('web.extras.buscar') }}" style="background: #003580; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                <i class="fas fa-redo me-2"></i>Intentar de Nuevo
            </a>
            <a href="{{ route('web.index') }}" style="background: white; color: #003580; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 2px solid #003580;">
                <i class="fas fa-home me-2"></i>Volver al Inicio
            </a>
        </div>
    </div>
</div>
@endsection

