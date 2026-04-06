@extends('layouts.appAdmin')

@section('title', 'Detalle de Pago')

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-credit-card me-2"></i>Detalle de Pago #{{ $pago->id }}
        </h1>
        <div>
            <a href="{{ route('admin.pagos.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Información del Pago -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Información del Pago
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>ID:</strong></div>
                        <div class="col-md-9">{{ $pago->id }}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Estado:</strong></div>
                        <div class="col-md-9">
                            @php
                                $estadoColors = [
                                    'completado' => 'success',
                                    'pendiente' => 'warning',
                                    'procesando' => 'info',
                                    'fallido' => 'danger',
                                    'cancelado' => 'secondary',
                                    'reembolsado' => 'dark',
                                ];
                                $color = $estadoColors[$pago->estado] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}">{{ ucfirst($pago->estado) }}</span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Monto:</strong></div>
                        <div class="col-md-9">
                            <strong style="font-size: 20px; color: #003580;">
                                {{ number_format($pago->monto, 2, ',', '.') }} {{ $pago->moneda }}
                            </strong>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Método de Pago:</strong></div>
                        <div class="col-md-9">{{ ucfirst($pago->metodo_pago) }}</div>
                    </div>
                    
                    @if($pago->stripe_payment_intent_id)
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Stripe Payment Intent:</strong></div>
                            <div class="col-md-9">
                                <code>{{ $pago->stripe_payment_intent_id }}</code>
                            </div>
                        </div>
                    @endif
                    
                    @if($pago->stripe_checkout_session_id)
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Stripe Session:</strong></div>
                            <div class="col-md-9">
                                <code>{{ $pago->stripe_checkout_session_id }}</code>
                            </div>
                        </div>
                    @endif
                    
                    @if($pago->fecha_pago)
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Fecha de Pago:</strong></div>
                            <div class="col-md-9">{{ $pago->fecha_pago->format('d/m/Y H:i:s') }}</div>
                        </div>
                    @endif
                    
                    @if($pago->descripcion)
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Descripción:</strong></div>
                            <div class="col-md-9">{{ $pago->descripcion }}</div>
                        </div>
                    @endif
                    
                    @if($pago->notas)
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Notas:</strong></div>
                            <div class="col-md-9">{{ $pago->notas }}</div>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Información de la Reserva -->
            @if($pago->reserva)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar text-primary me-2"></i>
                            Información de la Reserva
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Código:</strong></div>
                            <div class="col-md-9">
                                <a href="{{ route('reservas.show', ['reserva' => $pago->reserva->id]) }}" class="text-primary">
                                    {{ $pago->reserva->codigo_reserva }}
                                </a>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Apartamento:</strong></div>
                            <div class="col-md-9">{{ $pago->reserva->apartamento->titulo ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Fechas:</strong></div>
                            <div class="col-md-9">
                                {{ \Carbon\Carbon::parse($pago->reserva->fecha_entrada)->format('d/m/Y') }} - 
                                {{ \Carbon\Carbon::parse($pago->reserva->fecha_salida)->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Intentos de Pago -->
            @if($pago->intentos->count() > 0)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history text-primary me-2"></i>
                            Historial de Intentos ({{ $pago->intentos->count() }})
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Monto</th>
                                        <th>Error</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pago->intentos as $intento)
                                        <tr>
                                            <td>{{ $intento->fecha_intento->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $intento->estado == 'exitoso' ? 'success' : ($intento->estado == 'fallido' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($intento->estado) }}
                                                </span>
                                            </td>
                                            <td>{{ number_format($intento->monto, 2, ',', '.') }} €</td>
                                            <td>
                                                @if($intento->mensaje_error)
                                                    <small class="text-danger">{{ $intento->mensaje_error }}</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
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
            @if($pago->cliente)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user text-primary me-2"></i>
                            Cliente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Nombre:</strong><br>
                            {{ $pago->cliente->nombre }} {{ $pago->cliente->apellido1 }}
                        </div>
                        
                        @if($pago->cliente->email)
                            <div class="mb-3">
                                <strong>Email:</strong><br>
                                <a href="mailto:{{ $pago->cliente->email }}">{{ $pago->cliente->email }}</a>
                            </div>
                        @endif
                        
                        @if($pago->cliente->telefono)
                            <div class="mb-3">
                                <strong>Teléfono:</strong><br>
                                <a href="tel:{{ $pago->cliente->telefono }}">{{ $pago->cliente->telefono }}</a>
                            </div>
                        @endif
                        
                        <a href="{{ route('clientes.show', $pago->cliente->id) }}" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-eye me-1"></i> Ver Cliente
                        </a>
                    </div>
                </div>
            @endif
            
            <!-- Metadata -->
            @if($pago->metadata)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-database text-primary me-2"></i>
                            Metadata
                        </h5>
                    </div>
                    <div class="card-body">
                        <pre style="background: #f8f9fa; padding: 12px; border-radius: 4px; font-size: 12px; overflow-x: auto;">{{ json_encode($pago->metadata, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

