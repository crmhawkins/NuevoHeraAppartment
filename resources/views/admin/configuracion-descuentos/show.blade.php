@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-eye mr-2"></i>
                        Detalles de Configuración de Descuento
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('configuracion-descuentos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Nombre:</strong></label>
                                        <p class="form-control-static">{{ $configuracionDescuento->nombre }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Porcentaje de Descuento:</strong></label>
                                        <p class="form-control-static">
                                            <span class="badge badge-info badge-lg">
                                                {{ $configuracionDescuento->porcentaje_formateado }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Descripción:</strong></label>
                                <p class="form-control-static">
                                    {{ $configuracionDescuento->descripcion ?: 'Sin descripción' }}
                                </p>
                            </div>

                            <div class="form-group">
                                <label><strong>Estado:</strong></label>
                                <p class="form-control-static">
                                    @if($configuracionDescuento->activo)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-secondary">Inactivo</span>
                                    @endif
                                </p>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-cogs mr-2"></i>
                                        Condiciones de Aplicación
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label><strong>Día de la Semana:</strong></label>
                                                <p class="form-control-static">
                                                    @php
                                                        $dias = [
                                                            'monday' => 'Lunes',
                                                            'tuesday' => 'Martes',
                                                            'wednesday' => 'Miércoles',
                                                            'thursday' => 'Jueves',
                                                            'friday' => 'Viernes',
                                                            'saturday' => 'Sábado',
                                                            'sunday' => 'Domingo'
                                                        ];
                                                        $dia = $configuracionDescuento->condiciones['dia_semana'] ?? 'friday';
                                                    @endphp
                                                    {{ $dias[$dia] }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label><strong>Temporada:</strong></label>
                                                <p class="form-control-static">
                                                    @php
                                                        $temporadas = [
                                                            'baja' => 'Temporada Baja',
                                                            'alta' => 'Temporada Alta',
                                                            'media' => 'Temporada Media'
                                                        ];
                                                        $temporada = $configuracionDescuento->condiciones['temporada'] ?? 'baja';
                                                    @endphp
                                                    {{ $temporadas[$temporada] }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label><strong>Días Mínimos Libres:</strong></label>
                                                <p class="form-control-static">
                                                    {{ $configuracionDescuento->condiciones['dias_minimos_libres'] ?? 1 }} día(s)
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Información General
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li><strong>ID:</strong> {{ $configuracionDescuento->id }}</li>
                                        <li><strong>Creado:</strong> {{ $configuracionDescuento->created_at->format('d/m/Y H:i:s') }}</li>
                                        <li><strong>Actualizado:</strong> {{ $configuracionDescuento->updated_at->format('d/m/Y H:i:s') }}</li>
                                        <li><strong>Historial:</strong> {{ $configuracionDescuento->historialDescuentos->count() }} descuentos aplicados</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-calculator mr-2"></i>
                                        Ejemplo de Cálculo
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center">
                                        <h6>Precio Base: 100€</h6>
                                        <h6>Descuento: {{ $configuracionDescuento->porcentaje_formateado }}</h6>
                                        <hr>
                                        <h5 class="text-success">
                                            Precio Final: {{ $configuracionDescuento->calcularPrecioConDescuento(100) }}€
                                        </h5>
                                        <small class="text-muted">
                                            Ahorro: {{ $configuracionDescuento->calcularAhorroPorDia(100) }}€ por día
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de descuentos aplicados -->
                    @if($configuracionDescuento->historialDescuentos->count() > 0)
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history mr-2"></i>
                                    Historial de Descuentos Aplicados
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Fecha Aplicación</th>
                                                <th>Apartamento</th>
                                                <th>Tarifa</th>
                                                <th>Días</th>
                                                <th>Ahorro Total</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($configuracionDescuento->historialDescuentos->take(10) as $historial)
                                                <tr>
                                                    <td>{{ $historial->fecha_aplicacion->format('d/m/Y') }}</td>
                                                    <td>{{ $historial->apartamento->nombre }}</td>
                                                    <td>{{ $historial->tarifa->nombre }}</td>
                                                    <td>{{ $historial->dias_aplicados }}</td>
                                                    <td>{{ $historial->ahorro_total }}€</td>
                                                    <td>
                                                        <span class="badge badge-{{ $historial->estado == 'aplicado' ? 'success' : ($historial->estado == 'error' ? 'danger' : 'warning') }}">
                                                            {{ $historial->estado_formateado }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($configuracionDescuento->historialDescuentos->count() > 10)
                                    <div class="text-center mt-3">
                                        <small class="text-muted">
                                            Mostrando los últimos 10 registros de {{ $configuracionDescuento->historialDescuentos->count() }} totales
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('configuracion-descuentos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Volver al Listado
                        </a>
                        <div>
                            <a href="{{ route('configuracion-descuentos.edit', $configuracionDescuento) }}" class="btn btn-warning">
                                <i class="fas fa-edit mr-1"></i>
                                Editar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
