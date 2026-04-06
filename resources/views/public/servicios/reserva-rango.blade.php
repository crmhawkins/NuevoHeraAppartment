@extends('layouts.public-booking')

@section('title', __('services.check_availability') . ' - ' . $servicioModel->getTranslated('nombre'))

@section('content')
<div class="booking-detail-container" style="margin-top: 40px;">
    <div style="max-width: 720px; margin: 0 auto; padding: 0 16px;">
        {{-- Resumen del servicio --}}
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); overflow: hidden; margin-bottom: 24px;">
            @if($servicioModel->imagen)
                <div style="width: 100%; height: 220px; overflow: hidden; background: #f5f5f5;">
                    <img src="{{ asset($servicioModel->imagen) }}" alt="{{ $servicioModel->getTranslated('nombre') }}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            @else
                <div style="width: 100%; height: 220px; background: linear-gradient(135deg, #003580 0%, #0056b3 100%); display: flex; align-items: center; justify-content: center;">
                    @if($servicioModel->icono)
                        <i class="{{ $servicioModel->icono }}" style="font-size: 72px; color: white; opacity: 0.9;"></i>
                    @else
                        <i class="fas fa-car" style="font-size: 72px; color: white; opacity: 0.9;"></i>
                    @endif
                </div>
            @endif
            <div style="padding: 24px;">
                <h1 style="font-size: 26px; font-weight: 700; color: #003580; margin-bottom: 12px;">
                    {{ $servicioModel->getTranslated('nombre') }}
                </h1>
                <p style="color: #666; font-size: 15px; line-height: 1.6; margin-bottom: 16px;">
                    {{ $servicioModel->getTranslated('descripcion') }}
                </p>
                <p style="font-size: 22px; font-weight: 700; color: #003580; margin-bottom: 0;">
                    {{ number_format($servicioModel->precio, 2, ',', '.') }} €
                    <span style="font-size: 14px; font-weight: 400; color: #666;">{{ $servicioModel->esAlquilerCoche() ? __('services.per_day') : __('services.per_period') }}</span>
                </p>
            </div>
        </div>

        {{-- Formulario rango de fechas --}}
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); padding: 28px;">
            <h2 style="font-size: 20px; font-weight: 700; color: #003580; margin-bottom: 20px;">
                <i class="fas fa-calendar-alt me-2"></i>{{ __('services.select_dates') }}
            </h2>

            @if($errors->any() || session('error'))
                <div style="background: #f8d7da; color: #721c24; padding: 14px; border-radius: 6px; margin-bottom: 20px;">
                    <strong><i class="fas fa-exclamation-circle me-2"></i>{{ __('common.error') }}:</strong>
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

            <form action="{{ route('web.servicios.comprobar-disponibilidad', ['servicio' => $servicioModel->slug]) }}" method="POST" id="form-reserva-rango">
                @csrf
                <div style="margin-bottom: 20px;">
                    <label for="fecha_rango" style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">
                        {{ __('services.date_range') }} <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="text"
                           id="fecha_rango"
                           value="{{ old('fecha_entrada') && old('fecha_salida') ? old('fecha_entrada') . ' - ' . old('fecha_salida') : '' }}"
                           placeholder="{{ __('portal.dates_placeholder') }}"
                           required
                           readonly
                           style="width: 100%; padding: 14px 16px; border: 2px solid #E0E0E0; border-radius: 6px; font-size: 16px; background: white; cursor: pointer;">
                    <input type="hidden" id="fecha_entrada" name="fecha_entrada" value="{{ old('fecha_entrada') }}">
                    <input type="hidden" id="fecha_salida" name="fecha_salida" value="{{ old('fecha_salida') }}">
                    @error('fecha_entrada')
                        <span style="color: #dc3545; font-size: 14px; margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                    @error('fecha_salida')
                        <span style="color: #dc3545; font-size: 14px; margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" style="width: 100%; background: #003580; color: white; padding: 16px; border: none; border-radius: 6px; font-weight: 600; font-size: 18px; cursor: pointer; transition: background 0.2s;">
                    <i class="fas fa-search me-2"></i>{{ __('services.check_availability') }}
                </button>
            </form>

            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E0E0E0;">
                <a href="{{ route('web.servicios') }}" style="color: #003580; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-arrow-left me-2"></i>{{ __('services.back_to_services') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    const fechaRangoInput = document.getElementById('fecha_rango');
    const fechaEntradaHidden = document.getElementById('fecha_entrada');
    const fechaSalidaHidden = document.getElementById('fecha_salida');
    const form = document.getElementById('form-reserva-rango');

    if (typeof flatpickr !== 'undefined' && fechaRangoInput) {
        const fp = flatpickr('#fecha_rango', {
            locale: 'es',
            dateFormat: 'Y-m-d',
            minDate: 'today',
            mode: 'range',
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const inicio = formatDate(selectedDates[0]);
                    const fin = formatDate(selectedDates[1]);
                    fechaRangoInput.value = inicio + ' - ' + fin;
                    if (fechaEntradaHidden) fechaEntradaHidden.value = inicio;
                    if (fechaSalidaHidden) fechaSalidaHidden.value = fin;
                } else if (selectedDates.length === 1) {
                    const d = formatDate(selectedDates[0]);
                    fechaRangoInput.value = d;
                    if (fechaEntradaHidden) fechaEntradaHidden.value = d;
                    if (fechaSalidaHidden) fechaSalidaHidden.value = '';
                }
            }
        });

        if (form) {
            form.addEventListener('submit', function(e) {
                var selected = fp.selectedDates;
                if (selected.length === 2) {
                    if (fechaEntradaHidden) fechaEntradaHidden.value = formatDate(selected[0]);
                    if (fechaSalidaHidden) fechaSalidaHidden.value = formatDate(selected[1]);
                }
            });
        }
    }
});
</script>
@endsection
