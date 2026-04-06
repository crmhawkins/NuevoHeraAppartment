@extends('layouts.appAdmin')

@section('title', 'Consumos - ' . $amenity->nombre)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h3 mb-1 text-dark fw-bold">
                        <i class="fas fa-minus-circle me-2 text-warning"></i>
                        Consumos de {{ $amenity->nombre }}
                    </h2>
                    <p class="text-muted mb-0">Historial completo de consumos del amenity</p>
                </div>
                <div>
                    <a href="{{ route('admin.amenities.show', $amenity->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver al amenity
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Amenity -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Proveedor:</strong> {{ $amenity->proveedor }}
                        </div>
                        <div class="col-md-3">
                            <strong>Stock Actual:</strong> 
                            <span class="badge bg-info">{{ $amenity->stock_actual }} {{ $amenity->unidad_medida }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Precio Compra:</strong> {{ number_format($amenity->precio_compra, 2) }}€
                        </div>
                        <div class="col-md-3">
                            <strong>Total Consumos:</strong> {{ $consumos->total() }} registros
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Consumos -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if($consumos->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Cantidad</th>
                                        <th>Tipo</th>
                                        <th>Usuario</th>
                                        <th>Apartamento</th>
                                        <th>Limpieza</th>
                                        <th>Tarea</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consumos as $consumo)
                                        <tr>
                                            <td>
                                                <span class="fw-medium">{{ $consumo->fecha_consumo->format('d/m/Y') }}</span>
                                                <br>
                                                <small class="text-muted">{{ $consumo->fecha_consumo->format('H:i') }}</small>
                                            </td>
                                            <td>
                                                <span class="text-danger fw-bold">{{ $consumo->cantidad_consumida }}</span>
                                                {{ $amenity->unidad_medida }}
                                            </td>
                                            <td>
                                                <span class="badge bg-warning-subtle text-warning px-2 py-1">
                                                    {{ ucfirst($consumo->tipo_consumo) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium">{{ $consumo->user->name ?? 'N/A' }}</div>
                                                        <small class="text-muted">{{ $consumo->user->email ?? '' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($consumo->apartamento)
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-home text-info me-2"></i>
                                                        <div>
                                                            <div class="fw-medium">{{ $consumo->apartamento->nombre }}</div>
                                                            <small class="text-muted">{{ $consumo->apartamento->edificio->nombre ?? '' }}</small>
                                                        </div>
                                                    </div>
                                                @elseif($consumo->limpieza && $consumo->limpieza->apartamento)
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-home text-info me-2"></i>
                                                        <div>
                                                            <div class="fw-medium">{{ $consumo->limpieza->apartamento->nombre }}</div>
                                                            <small class="text-muted">{{ $consumo->limpieza->apartamento->edificio->nombre ?? '' }}</small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($consumo->limpieza)
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-broom text-success me-2"></i>
                                                        <div>
                                                            <div class="fw-medium">Limpieza #{{ $consumo->limpieza->id }}</div>
                                                            <small class="text-muted">
                                                                @if($consumo->limpieza->fecha_entrada)
                                                                    {{ $consumo->limpieza->fecha_entrada->format('d/m/Y') }}
                                                                @else
                                                                    Sin fecha entrada
                                                                @endif
                                                                @if($consumo->limpieza->fecha_entrada && $consumo->limpieza->fecha_salida)
                                                                    - 
                                                                @endif
                                                                @if($consumo->limpieza->fecha_salida)
                                                                    {{ $consumo->limpieza->fecha_salida->format('d/m/Y') }}
                                                                @else
                                                                    Sin fecha salida
                                                                @endif
                                                            </small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($consumo->limpieza && $consumo->limpieza->tareaAsignada)
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-tasks text-primary me-2"></i>
                                                        <div>
                                                            <div class="fw-medium">Tarea #{{ $consumo->limpieza->tareaAsignada->id }}</div>
                                                            <small class="text-muted">{{ $consumo->limpieza->tareaAsignada->tipo_tarea ?? 'Limpieza' }}</small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($consumo->observaciones)
                                                    <span class="text-muted">{{ Str::limit($consumo->observaciones, 50) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $consumos->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-minus-circle fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay consumos registrados</h5>
                            <p class="text-muted">Este amenity no tiene consumos registrados aún.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
}
</style>
@endsection
