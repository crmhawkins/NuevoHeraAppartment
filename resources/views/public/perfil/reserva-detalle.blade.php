@extends('layouts.public-booking')

@section('title', 'Detalles de Reserva - Apartamentos Algeciras')

@section('content')
<div class="booking-detail-container" style="margin-top: 40px; margin-bottom: 40px;">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0" style="color: #003580; font-weight: 700; font-size: 32px;">{{ __('booking_detail.title') }}</h1>
                <a href="{{ route('web.perfil') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>{{ __('booking_detail.back_to_profile') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Información Principal -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-calendar-check me-2"></i>
                        {{ __('booking_detail.reservation') }} #{{ $reserva->codigo_reserva }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('booking_detail.apartment') }}</strong>
                            <p class="mb-0">{{ $reserva->apartamento->nombre ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('booking_detail.status') }}</strong>
                            <p class="mb-0">
                                <span class="badge bg-{{ $reserva->estado->color ?? 'secondary' }}">
                                    {{ $reserva->estado->nombre ?? 'N/A' }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('booking_detail.checkin_date') }}</strong>
                            <p class="mb-0">
                                {{ $reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') : 'N/A' }}
                                @if($reserva->fecha_hora_entrada)
                                    {{ __('common.at') }} {{ \Carbon\Carbon::parse($reserva->fecha_hora_entrada)->format('H:i') }}
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('booking_detail.checkout_date') }}</strong>
                            <p class="mb-0">
                                {{ $reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') : 'N/A' }}
                                @if($reserva->fecha_hora_salida)
                                    {{ __('common.at') }} {{ \Carbon\Carbon::parse($reserva->fecha_hora_salida)->format('H:i') }}
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('booking_detail.number_people') }}</strong>
                            <p class="mb-0">
                                {{ $reserva->numero_personas ?? 0 }} {{ __('reservation.adults') }}
                                @if($reserva->numero_ninos > 0)
                                    , {{ $reserva->numero_ninos }} {{ __('reservation.children') }}
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('booking_detail.total_price') }}</strong>
                            <p class="mb-0" style="font-size: 20px; font-weight: 600; color: #003580;">
                                {{ number_format($reserva->precio ?? 0, 2) }} €
                            </p>
                        </div>
                        @if($reserva->origen)
                        <div class="col-md-6 mb-3">
                            <strong>{{ __('booking_detail.origin') }}</strong>
                            <p class="mb-0">{{ $reserva->origen }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Servicios Extras -->
            @if($reserva->serviciosExtras && $reserva->serviciosExtras->count() > 0)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-concierge-bell me-2"></i>
                        {{ __('booking_detail.extra_services') }}
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('booking_detail.service') }}</th>
                                    <th>{{ __('booking_detail.quantity') }}</th>
                                    <th>{{ __('booking_detail.unit_price') }}</th>
                                    <th>{{ __('booking_detail.total') }}</th>
                                    <th>{{ __('booking_detail.status_label') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reserva->serviciosExtras as $servicioExtra)
                                    <tr>
                                        <td>{{ $servicioExtra->servicio->nombre ?? 'N/A' }}</td>
                                        <td>{{ $servicioExtra->cantidad }}</td>
                                        <td>{{ number_format($servicioExtra->precio_unitario ?? 0, 2) }} €</td>
                                        <td>{{ number_format($servicioExtra->precio_total ?? 0, 2) }} €</td>
                                        <td>
                                            <span class="badge bg-{{ $servicioExtra->estado === 'completado' ? 'success' : ($servicioExtra->estado === 'cancelado' ? 'danger' : 'warning') }}">
                                                @if($servicioExtra->estado === 'completado')
                                                    {{ __('booking_detail.completed') }}
                                                @elseif($servicioExtra->estado === 'cancelado')
                                                    {{ __('booking_detail.cancelled') }}
                                                @else
                                                    {{ __('booking_detail.pending') }}
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Pagos -->
            @if($reserva->pagos && $reserva->pagos->count() > 0)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        {{ __('booking_detail.payments') }}
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('booking_detail.date') }}</th>
                                    <th>{{ __('booking_detail.amount') }}</th>
                                    <th>{{ __('booking_detail.status_label') }}</th>
                                    <th>{{ __('booking_detail.description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reserva->pagos as $pago)
                                    <tr>
                                        <td>{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ number_format($pago->monto ?? 0, 2) }} {{ $pago->moneda ?? 'EUR' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $pago->estado === 'completado' ? 'success' : ($pago->estado === 'pendiente' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($pago->estado) }}
                                            </span>
                                        </td>
                                        <td>{{ $pago->descripcion ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Información del Cliente -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Información del Cliente
                    </h4>
                </div>
                <div class="card-body">
                    <p><strong>Nombre:</strong><br>{{ $reserva->cliente->nombre ?? 'N/A' }} {{ $reserva->cliente->apellido1 ?? '' }}</p>
                    @if($reserva->cliente->email)
                        <p><strong>Email:</strong><br>{{ $reserva->cliente->email }}</p>
                    @endif
                    @if($reserva->cliente->telefono)
                        <p><strong>Teléfono:</strong><br>{{ $reserva->cliente->telefono }}</p>
                    @endif
                </div>
            </div>

            <!-- Acciones -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="mb-3">Acciones</h5>
                    <a href="{{ route('web.perfil') }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Perfil
                    </a>
                    @if($reserva->fecha_salida >= now() && in_array($reserva->estado_id, [1, 2, 3]))
                        <a href="{{ route('web.extras.buscar') }}?codigo={{ $reserva->codigo_reserva }}" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Agregar Servicios
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


