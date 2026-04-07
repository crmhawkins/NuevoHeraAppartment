@extends('layouts.appAdmin')

@section('title', 'Detalle DNI - Reserva #' . $reserva->id)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-id-card text-primary me-2"></i>
                        Detalle DNI - Reserva #{{ $reserva->id }}
                    </h1>
                    <p class="text-muted mb-0">
                        {{ $reserva->apartamento->nombre ?? 'Apartamento' }} |
                        {{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.monitorizacion-dni.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                    <a href="{{ route('reservas.show', $reserva->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1"></i>Ver Reserva
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Info de la reserva -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted"><i class="fas fa-user me-1"></i>Cliente principal</h6>
                    @if($reserva->cliente)
                        <p class="mb-1"><strong>{{ $reserva->cliente->nombre }} {{ $reserva->cliente->apellido1 }} {{ $reserva->cliente->apellido2 }}</strong></p>
                        <p class="mb-1"><small class="text-muted">{{ $reserva->cliente->email }}</small></p>
                        <p class="mb-1">
                            <strong>Documento:</strong>
                            @if($reserva->cliente->num_identificacion)
                                <span class="text-success"><i class="fas fa-check me-1"></i>{{ $reserva->cliente->tipo_documento_str ?? $reserva->cliente->tipo_documento }} - {{ $reserva->cliente->num_identificacion }}</span>
                            @else
                                <span class="text-danger"><i class="fas fa-times me-1"></i>No proporcionado</span>
                            @endif
                        </p>
                        @if($reserva->cliente->nacionalidad)
                            <p class="mb-0"><strong>Nacionalidad:</strong> {{ $reserva->cliente->nacionalidad }}</p>
                        @endif
                    @else
                        <p class="text-muted">Sin cliente asignado</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted"><i class="fas fa-info-circle me-1"></i>Datos de la reserva</h6>
                    <p class="mb-1"><strong>Codigo:</strong> {{ $reserva->codigo_reserva ?? '-' }}</p>
                    <p class="mb-1"><strong>Origen:</strong> {{ $reserva->origen ?? '-' }}</p>
                    <p class="mb-1"><strong>Personas esperadas:</strong> {{ $totalEsperado }}</p>
                    <p class="mb-0"><strong>Huespedes registrados:</strong> {{ $huespedes->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted"><i class="fas fa-paper-plane me-1"></i>Estado MIR</h6>
                    @if($reserva->mir_enviado)
                        <p class="mb-1">
                            <span class="badge bg-primary"><i class="fas fa-check me-1"></i>Enviado</span>
                        </p>
                        @if($reserva->mir_fecha_envio)
                            <p class="mb-1"><strong>Fecha envio:</strong> {{ \Carbon\Carbon::parse($reserva->mir_fecha_envio)->format('d/m/Y H:i') }}</p>
                        @endif
                        @if($reserva->mir_estado)
                            <p class="mb-0"><strong>Estado:</strong> {{ $reserva->mir_estado }}</p>
                        @endif
                    @else
                        <p class="mb-0">
                            <span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>Pendiente de envio</span>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de huéspedes -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Huespedes registrados ({{ $huespedes->count() }} de {{ $totalEsperado }} esperados)
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if($huespedes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre completo</th>
                                        <th>Tipo documento</th>
                                        <th>Numero documento</th>
                                        <th>Nacionalidad</th>
                                        <th>Fecha nacimiento</th>
                                        <th>Sexo</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($huespedes as $index => $huesped)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $huesped->nombre }} {{ $huesped->primer_apellido }} {{ $huesped->segundo_apellido }}</strong>
                                                @if($huesped->email)
                                                    <br><small class="text-muted">{{ $huesped->email }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $huesped->tipo_documento_str ?? $huesped->tipo_documento ?? '-' }}</td>
                                            <td>
                                                @if($huesped->numero_identificacion)
                                                    <code>{{ $huesped->numero_identificacion }}</code>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $huesped->nacionalidad ?? $huesped->nacionalidadStr ?? '-' }}</td>
                                            <td>{{ $huesped->fecha_nacimiento ? \Carbon\Carbon::parse($huesped->fecha_nacimiento)->format('d/m/Y') : '-' }}</td>
                                            <td>{{ $huesped->sexo_str ?? $huesped->sexo ?? '-' }}</td>
                                            <td class="text-center">
                                                @if(!empty($huesped->numero_identificacion))
                                                    <span class="badge bg-success"><i class="fas fa-check"></i> OK</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Falta DNI</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-slash text-muted fa-3x mb-3"></i>
                            <h5 class="text-muted">No hay huespedes registrados para esta reserva</h5>
                            <p class="text-muted">El formulario de DNI aun no ha sido completado por el cliente.</p>
                            @if($reserva->token)
                                <p class="mb-0">
                                    <strong>Enlace del formulario:</strong>
                                    <a href="{{ url('/dni/' . $reserva->token) }}" target="_blank" class="text-primary">
                                        {{ url('/dni/' . $reserva->token) }}
                                    </a>
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Enlace de formulario DNI -->
    @if($reserva->token && ($huespedes->count() < $totalEsperado))
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 border-start border-warning border-4">
                    <div class="card-body">
                        <h6><i class="fas fa-link text-warning me-2"></i>Enlace de registro de huespedes</h6>
                        <p class="mb-2">Comparta este enlace con el huesped para que complete el registro de identificacion:</p>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ url('/dni/' . $reserva->token) }}" id="dniLink" readonly>
                            <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText(document.getElementById('dniLink').value); this.innerHTML='<i class=\'fas fa-check\'></i> Copiado'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy\'></i> Copiar', 2000);">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
