@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="fas fa-ticket-alt text-primary me-2"></i>
                Detalles del Cupón: <span class="text-primary">{{ $cupon->codigo }}</span>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.cupones.index') }}">Cupones</a></li>
                    <li class="breadcrumb-item active">Detalles</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.cupones.edit', $cupon) }}" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Editar
            </a>
            <a href="{{ route('admin.cupones.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Información del Cupón -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Cupón</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Código:</strong>
                            <p class="mb-0 fs-4 text-primary">{{ $cupon->codigo }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Nombre:</strong>
                            <p class="mb-0">{{ $cupon->nombre }}</p>
                        </div>
                    </div>

                    @if($cupon->descripcion)
                        <div class="mb-3">
                            <strong>Descripción:</strong>
                            <p class="mb-0 text-muted">{{ $cupon->descripcion }}</p>
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Tipo de Descuento:</strong>
                            <p class="mb-0">
                                @if($cupon->tipo_descuento === 'porcentaje')
                                    <span class="badge bg-info">Porcentaje</span>
                                @else
                                    <span class="badge bg-success">Fijo</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <strong>Valor:</strong>
                            <p class="mb-0 fs-5">
                                @if($cupon->tipo_descuento === 'porcentaje')
                                    {{ $cupon->valor_descuento }}%
                                @else
                                    {{ number_format($cupon->valor_descuento, 2) }} €
                                @endif
                            </p>
                        </div>
                        @if($cupon->descuento_maximo)
                            <div class="col-md-4">
                                <strong>Descuento Máximo:</strong>
                                <p class="mb-0">{{ number_format($cupon->descuento_maximo, 2) }} €</p>
                            </div>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <strong>Usos:</strong>
                            <p class="mb-0">{{ $cupon->usos_actuales }} / {{ $cupon->usos_maximos ?? '∞' }}</p>
                        </div>
                        <div class="col-md-4">
                            <strong>Usos por Cliente:</strong>
                            <p class="mb-0">{{ $cupon->usos_por_cliente }}</p>
                        </div>
                        @if($cupon->importe_minimo)
                            <div class="col-md-4">
                                <strong>Importe Mínimo:</strong>
                                <p class="mb-0">{{ number_format($cupon->importe_minimo, 2) }} €</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Historial de Usos -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Usos ({{ $cupon->usos->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($cupon->usos->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Reserva</th>
                                        <th>Cliente</th>
                                        <th>Importe Original</th>
                                        <th>Descuento</th>
                                        <th>Importe Final</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cupon->usos as $uso)
                                        <tr>
                                            <td>{{ $uso->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @if($uso->reserva)
                                                    <a href="{{ route('reservas.show', $uso->reserva) }}">{{ $uso->reserva->codigo_reserva }}</a>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($uso->cliente)
                                                    {{ $uso->cliente->nombre ?? $uso->cliente->alias }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($uso->importe_original, 2) }} €</td>
                                            <td class="text-success">-{{ number_format($uso->descuento_aplicado, 2) }} €</td>
                                            <td><strong>{{ number_format($uso->importe_final, 2) }} €</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-3 mb-0">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            Este cupón aún no ha sido utilizado
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Estado -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-toggle-on me-2"></i>Estado</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if($cupon->activo)
                            <span class="badge bg-success fs-6">ACTIVO</span>
                        @else
                            <span class="badge bg-danger fs-6">INACTIVO</span>
                        @endif
                    </div>
                    <div class="mb-2">
                        <strong>Vigente:</strong> {{ $cupon->es_vigente ? 'Sí' : 'No' }}
                    </div>
                    <div class="mb-2">
                        <strong>Usos Disponibles:</strong> {{ $cupon->tiene_usos_disponibles ? 'Sí' : 'No' }}
                    </div>
                </div>
            </div>

            <!-- Restricciones Temporales -->
            @if($cupon->fecha_inicio || $cupon->fecha_fin || $cupon->reserva_desde || $cupon->reserva_hasta)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Restricciones Temporales</h5>
                    </div>
                    <div class="card-body">
                        @if($cupon->fecha_inicio || $cupon->fecha_fin)
                            <div class="mb-3">
                                <strong>Validez del Cupón:</strong>
                                <p class="mb-0 small">
                                    {{ $cupon->fecha_inicio ? $cupon->fecha_inicio->format('d/m/Y') : '∞' }}
                                    -
                                    {{ $cupon->fecha_fin ? $cupon->fecha_fin->format('d/m/Y') : '∞' }}
                                </p>
                            </div>
                        @endif
                        @if($cupon->reserva_desde || $cupon->reserva_hasta)
                            <div class="mb-3">
                                <strong>Fechas de Reserva:</strong>
                                <p class="mb-0 small">
                                    {{ $cupon->reserva_desde ? $cupon->reserva_desde->format('d/m/Y') : '∞' }}
                                    -
                                    {{ $cupon->reserva_hasta ? $cupon->reserva_hasta->format('d/m/Y') : '∞' }}
                                </p>
                            </div>
                        @endif
                        @if($cupon->noches_minimas)
                            <div>
                                <strong>Noches Mínimas:</strong>
                                <p class="mb-0">{{ $cupon->noches_minimas }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Auditoría -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Auditoría</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Creado por:</strong>
                        <p class="mb-0">{{ $cupon->creador->name ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-2">
                        <strong>Fecha creación:</strong>
                        <p class="mb-0">{{ $cupon->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <strong>Última actualización:</strong>
                        <p class="mb-0">{{ $cupon->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
