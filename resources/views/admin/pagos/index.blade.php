@extends('layouts.appAdmin')

@section('title', 'Gestión de Pagos')

@section('content')
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-credit-card me-2"></i>Gestión de Pagos
        </h1>
        <div>
            <a href="{{ route('admin.pagos.intentos') }}" class="btn btn-outline-info">
                <i class="fas fa-list me-1"></i>Ver Intentos de Pago
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pagos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $estadisticas['total'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completados</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $estadisticas['completados'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendientes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $estadisticas['pendientes'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Monto Total</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($estadisticas['monto_total'], 2, ',', '.') }} €</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Pagos -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">
                <i class="fas fa-list text-primary me-2"></i>
                Lista de Pagos ({{ $pagos->total() }})
            </h5>
        </div>
        <div class="card-body p-0">
            @if($pagos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="border-0">ID</th>
                                <th scope="col" class="border-0">Reserva</th>
                                <th scope="col" class="border-0">Cliente</th>
                                <th scope="col" class="border-0">Monto</th>
                                <th scope="col" class="border-0">Estado</th>
                                <th scope="col" class="border-0">Método</th>
                                <th scope="col" class="border-0">Fecha</th>
                                <th scope="col" class="border-0">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pagos as $pago)
                                <tr>
                                    <td>{{ $pago->id }}</td>
                                    <td>
                                        @if($pago->reserva)
                                            <a href="{{ route('reservas.show', ['reserva' => $pago->reserva->id]) }}" class="text-primary">
                                                {{ $pago->reserva->codigo_reserva }}
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pago->cliente)
                                            {{ $pago->cliente->alias ?? $pago->cliente->nombre }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($pago->monto, 2, ',', '.') }} €</strong>
                                    </td>
                                    <td>
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
                                        <span class="badge bg-{{ $color }}">
                                            {{ ucfirst($pago->estado) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($pago->metodo_pago) }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $pago->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.pagos.show', $pago->id) }}" 
                                           class="btn btn-sm btn-primary" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer bg-white">
                    {{ $pagos->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay pagos registrados.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

