@extends('layouts.public-booking')

@section('title', 'Seleccionar Extras - ' . $reserva->codigo_reserva)

@section('content')
<div class="booking-detail-container" style="margin-top: 40px;">
    <!-- Información de la Reserva -->
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); padding: 24px; margin-bottom: 32px;">
        <h2 style="font-size: 24px; font-weight: 700; color: #003580; margin-bottom: 16px;">
            <i class="fas fa-calendar-check me-2"></i>{{ __('extras_select.your_booking') }}
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; color: #666;">
            <div>
                <strong style="color: #333;">{{ __('extras_select.code') }}</strong> {{ $reserva->codigo_reserva }}
            </div>
            <div>
                <strong style="color: #333;">{{ __('extras_select.apartment') }}</strong> {{ $reserva->apartamento->titulo ?? 'N/A' }}
            </div>
            <div>
                <strong style="color: #333;">{{ __('extras_select.checkin') }}</strong> {{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}
            </div>
            <div>
                <strong style="color: #333;">{{ __('extras_select.checkout') }}</strong> {{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') }}
            </div>
        </div>
    </div>
    
    <!-- Servicios Disponibles -->
    <h2 style="font-size: 28px; font-weight: 700; color: #003580; margin-bottom: 24px; text-align: center;">
        {{ __('extras_select.available_services') }}
    </h2>
    
    <form action="{{ route('web.extras.comprar') }}" method="POST" id="serviciosForm">
        @csrf
        <input type="hidden" name="reserva_id" value="{{ $reserva->id }}">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-bottom: 32px;">
            @foreach($servicios as $servicio)
                @php
                    $yaComprado = in_array($servicio->id, $serviciosComprados);
                @endphp
                <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); overflow: hidden; transition: transform 0.2s; {{ $yaComprado ? 'opacity: 0.6;' : '' }}">
                    @if($servicio->imagen)
                        <div style="width: 100%; height: 200px; overflow: hidden; background: #f5f5f5;">
                            <img src="{{ asset($servicio->imagen) }}" alt="{{ $servicio->nombre }}" style="width: 100%; height: 100%; object-fit: cover;">
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
                            {{ $servicio->nombre }}
                        </h3>
                        <p style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 16px;">
                            {{ $servicio->descripcion }}
                        </p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <span style="font-size: 24px; font-weight: 700; color: #003580;">
                                {{ number_format($servicio->precio, 2, ',', '.') }} €
                            </span>
                            @if($yaComprado)
                                <span style="background: #28a745; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <i class="fas fa-check me-1"></i>{{ __('extras_select.already_purchased') }}
                                </span>
                            @endif
                        </div>
                        
                        @if(!$yaComprado)
                            <label style="display: flex; align-items: center; cursor: pointer; padding: 12px; background: #f8f9fa; border-radius: 6px; transition: background 0.2s;">
                                <input 
                                    type="checkbox" 
                                    name="servicios[]" 
                                    value="{{ $servicio->id }}"
                                    style="width: 20px; height: 20px; margin-right: 12px; cursor: pointer;"
                                    onchange="updateTotal()"
                                >
                                <span style="font-weight: 600; color: #333;">{{ __('extras_select.select') }}</span>
                            </label>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Resumen y Botón de Pago -->
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); padding: 24px; position: sticky; bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <strong style="font-size: 18px; color: #333;">{{ __('extras_select.total_selected') }}</strong>
                </div>
                <div style="font-size: 28px; font-weight: 700; color: #003580;" id="totalSeleccionado">
                    0,00 €
                </div>
            </div>
            <button 
                type="submit" 
                id="btnComprar"
                disabled
                style="width: 100%; background: #ccc; color: white; padding: 16px; border: none; border-radius: 6px; font-weight: 600; font-size: 18px; cursor: not-allowed; transition: background 0.2s;">
                <i class="fas fa-credit-card me-2"></i>{{ __('extras_select.buy_now') }}
            </button>
        </div>
    </form>
</div>

<script>
    const precios = @json($servicios->pluck('precio', 'id'));
    const processingText = @json(__('common.processing'));
    
    function updateTotal() {
        const checkboxes = document.querySelectorAll('input[name="servicios[]"]:checked');
        let total = 0;
        
        checkboxes.forEach(checkbox => {
            const servicioId = parseInt(checkbox.value);
            total += parseFloat(precios[servicioId] || 0);
        });
        
        document.getElementById('totalSeleccionado').textContent = total.toFixed(2).replace('.', ',') + ' €';
        
        const btnComprar = document.getElementById('btnComprar');
        if (checkboxes.length > 0) {
            btnComprar.disabled = false;
            btnComprar.style.background = '#003580';
            btnComprar.style.cursor = 'pointer';
        } else {
            btnComprar.disabled = true;
            btnComprar.style.background = '#ccc';
            btnComprar.style.cursor = 'not-allowed';
        }
    }
    
    document.getElementById('serviciosForm').addEventListener('submit', function(e) {
        const checkboxes = document.querySelectorAll('input[name="servicios[]"]:checked');
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('{{ __('extras_select.select_at_least_one') }}');
            return false;
        }
        
        const btn = document.getElementById('btnComprar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + processingText;
    });
</script>
@endsection

