@extends('layouts.appAdmin')

@section('title', 'Monitor DNI')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-id-card text-primary me-2"></i>
                        Monitor DNI
                    </h1>
                    <p class="text-muted mb-0">Control de identificacion de huespedes para cumplimiento legal</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check text-primary fa-2x mb-2"></i>
                    <h4 class="mb-1 fw-bold text-primary">{{ $totalReservas }}</h4>
                    <small class="text-muted">Check-ins en el rango</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <h4 class="mb-1 fw-bold text-success">{{ $dniCompleto }}</h4>
                    <small class="text-muted">DNI Completo</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                    <h4 class="mb-1 fw-bold text-warning">{{ $dniPendiente }}</h4>
                    <small class="text-muted">DNI Parcial</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle text-danger fa-2x mb-2"></i>
                    <h4 class="mb-1 fw-bold text-danger">{{ $sinDatos }}</h4>
                    <small class="text-muted">Sin datos</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.monitorizacion-dni.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="fecha_desde" class="form-label"><i class="fas fa-calendar me-1"></i>Desde</label>
                            <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="{{ $fechaDesde }}">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_hasta" class="form-label"><i class="fas fa-calendar me-1"></i>Hasta</label>
                            <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="{{ $fechaHasta }}">
                        </div>
                        <div class="col-md-3">
                            <label for="estado_dni" class="form-label"><i class="fas fa-filter me-1"></i>Estado DNI</label>
                            <select class="form-select" id="estado_dni" name="estado_dni">
                                <option value="todos" {{ $estadoFiltro == 'todos' ? 'selected' : '' }}>Todos</option>
                                <option value="completo" {{ $estadoFiltro == 'completo' ? 'selected' : '' }}>Completo</option>
                                <option value="enviado_mir" {{ $estadoFiltro == 'enviado_mir' ? 'selected' : '' }}>Enviado MIR</option>
                                <option value="parcial" {{ $estadoFiltro == 'parcial' ? 'selected' : '' }}>Parcial</option>
                                <option value="sin_datos" {{ $estadoFiltro == 'sin_datos' ? 'selected' : '' }}>Sin datos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de reservas -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Reservas ({{ $reservasConEstado->count() }})
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if($reservasConEstado->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha Check-in</th>
                                        <th>Apartamento</th>
                                        <th>Huesped principal</th>
                                        <th class="text-center">Estado DNI</th>
                                        <th class="text-center">Personas</th>
                                        <th class="text-center">DNI completos</th>
                                        <th class="text-center">MIR</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservasConEstado as $reserva)
                                        @php
                                            $diasParaCheckin = \Carbon\Carbon::parse($reserva->fecha_entrada)->diffInDays(\Carbon\Carbon::today(), false);
                                            $esHoy = \Carbon\Carbon::parse($reserva->fecha_entrada)->isToday();
                                            $esManana = \Carbon\Carbon::parse($reserva->fecha_entrada)->isTomorrow();
                                        @endphp
                                        <tr class="{{ $esHoy ? 'table-warning' : '' }}">
                                            <td>
                                                <strong>{{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}</strong>
                                                @if($esHoy)
                                                    <span class="badge bg-warning text-dark ms-1">HOY</span>
                                                @elseif($esManana)
                                                    <span class="badge bg-info ms-1">MANANA</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($reserva->apartamento)
                                                    <i class="fas fa-home text-muted me-1"></i>
                                                    {{ $reserva->apartamento->nombre ?? 'Apt #' . $reserva->apartamento_id }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($reserva->cliente)
                                                    {{ $reserva->cliente->nombre }} {{ $reserva->cliente->apellido1 }}
                                                    @if($reserva->cliente->email)
                                                        <br><small class="text-muted">{{ $reserva->cliente->email }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Sin cliente</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @switch($reserva->estado_dni_calculado)
                                                    @case('enviado_mir')
                                                        <span class="badge bg-primary">
                                                            <i class="fas fa-paper-plane me-1"></i>Enviado MIR
                                                        </span>
                                                        @break
                                                    @case('completo')
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Completo
                                                        </span>
                                                        @break
                                                    @case('parcial')
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-exclamation me-1"></i>Parcial
                                                        </span>
                                                        @break
                                                    @case('sin_datos')
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>Sin datos
                                                        </span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">{{ $reserva->total_esperado }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold {{ $reserva->dni_completos >= $reserva->total_esperado ? 'text-success' : ($reserva->dni_completos > 0 ? 'text-warning' : 'text-danger') }}">
                                                    {{ $reserva->dni_completos }}/{{ $reserva->total_esperado }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($reserva->mir_enviado)
                                                    <span class="badge bg-primary" title="Enviado el {{ $reserva->mir_fecha_envio ? \Carbon\Carbon::parse($reserva->mir_fecha_envio)->format('d/m/Y H:i') : '-' }}">
                                                        <i class="fas fa-check"></i>
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-clock"></i>
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.monitorizacion-dni.detalle', $reserva->id) }}" class="btn btn-outline-primary" title="Ver detalle DNI">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('reservas.show', $reserva->id) }}" class="btn btn-outline-secondary" title="Ver reserva">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                    @if($reserva->cliente && $reserva->cliente->email && $reserva->estado_dni_calculado !== 'completo' && $reserva->estado_dni_calculado !== 'enviado_mir')
                                                        @if($reserva->token)
                                                            <a href="mailto:{{ $reserva->cliente->email }}?subject=Registro%20de%20huespedes%20-%20Identificacion%20requerida&body=Estimado/a%20{{ $reserva->cliente->nombre }},%0A%0APor%20favor,%20complete%20el%20registro%20de%20identificacion%20de%20todos%20los%20huespedes%20antes%20de%20su%20llegada.%0A%0AEnlace:%20{{ url('/dni/' . $reserva->token) }}%0A%0AGracias." class="btn btn-outline-warning" title="Enviar recordatorio por email">
                                                                <i class="fas fa-envelope"></i>
                                                            </a>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h5 class="text-muted">No hay reservas que coincidan con los filtros seleccionados</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
