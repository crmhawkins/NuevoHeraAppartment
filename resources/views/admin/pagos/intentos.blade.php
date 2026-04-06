@extends('layouts.appAdmin')

@section('title', 'Intentos de Pago')

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-history me-2"></i>Intentos de Pago
        </h1>
        <div>
            <a href="{{ route('admin.pagos.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a Pagos
            </a>
        </div>
    </div>

    <!-- Tabla de Intentos -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">
                <i class="fas fa-list text-primary me-2"></i>
                Lista de Intentos ({{ $intentos->total() }})
            </h5>
        </div>
        <div class="card-body p-0">
            @if($intentos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="border-0">ID</th>
                                <th scope="col" class="border-0">Pago</th>
                                <th scope="col" class="border-0">Reserva</th>
                                <th scope="col" class="border-0">Estado</th>
                                <th scope="col" class="border-0">Monto</th>
                                <th scope="col" class="border-0">Fecha</th>
                                <th scope="col" class="border-0">IP</th>
                                <th scope="col" class="border-0">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($intentos as $intento)
                                <tr>
                                    <td>{{ $intento->id }}</td>
                                    <td>
                                        @if($intento->pago)
                                            <a href="{{ route('admin.pagos.show', $intento->pago->id) }}" class="text-primary">
                                                #{{ $intento->pago->id }}
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($intento->reserva)
                                            <a href="{{ route('reservas.show', ['reserva' => $intento->reserva->id]) }}" class="text-primary">
                                                {{ $intento->reserva->codigo_reserva }}
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $intento->estado == 'exitoso' ? 'success' : ($intento->estado == 'fallido' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($intento->estado) }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($intento->monto, 2, ',', '.') }} €</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $intento->fecha_intento->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $intento->ip_address ?? '-' }}</small>
                                    </td>
                                    <td>
                                        @if($intento->mensaje_error)
                                            <small class="text-danger" title="{{ $intento->mensaje_error }}">
                                                {{ Str::limit($intento->mensaje_error, 30) }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer bg-white">
                    {{ $intentos->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay intentos de pago registrados.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

