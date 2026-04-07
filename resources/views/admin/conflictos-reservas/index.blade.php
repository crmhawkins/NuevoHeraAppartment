@extends('layouts.appAdmin')

@section('title', 'Conflictos de Reservas')

@section('content')
<div class="container-fluid">
    {{-- Tarjetas de resumen --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #dc3545 !important;">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(220,53,69,0.1);">
                            <i class="fas fa-exclamation-triangle text-danger fa-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Conflictos Activos</h6>
                        <h3 class="mb-0 fw-bold text-danger">{{ $totalActivos }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #28a745 !important;">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(40,167,69,0.1);">
                            <i class="fas fa-check-circle text-success fa-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Resueltos Hoy</h6>
                        <h3 class="mb-0 fw-bold text-success">{{ $resueltosHoy }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #007bff !important;">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(0,123,255,0.1);">
                            <i class="fas fa-calendar-alt text-primary fa-lg"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Este Mes</h6>
                        <h3 class="mb-0 fw-bold text-primary">{{ $totalEsteMes }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Conflictos Activos --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                Conflictos Activos
            </h5>
            <span class="badge bg-danger">{{ $totalActivos }}</span>
        </div>
        <div class="card-body p-0">
            @if($activos->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <h5 class="text-muted">No hay conflictos activos</h5>
                    <p class="text-muted">Todas las reservas son correctas.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Deteccion</th>
                                <th>Apartamento</th>
                                <th>Reservas Involucradas</th>
                                <th>Estado</th>
                                <th>Ultima Notificacion</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activos as $conflicto)
                                <tr style="border-left: 4px solid #dc3545;">
                                    <td>
                                        <span class="fw-semibold">{{ $conflicto->created_at->format('d/m/Y') }}</span>
                                        <br>
                                        <small class="text-muted">{{ $conflicto->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <i class="fas fa-home me-1 text-muted"></i>
                                        {{ $apartamentos[$conflicto->apartamento_id] ?? 'Apartamento #' . $conflicto->apartamento_id }}
                                    </td>
                                    <td>
                                        @if(is_array($conflicto->reserva_ids))
                                            @foreach($conflicto->reserva_ids as $rid)
                                                @php $r = $reservas[$rid] ?? null; @endphp
                                                <div class="mb-1">
                                                    <a href="{{ route('reservas.show', $rid) }}" class="badge bg-primary text-decoration-none" title="Ver reserva">
                                                        #{{ $rid }}
                                                    </a>
                                                    @if($r)
                                                        <small class="text-muted">
                                                            {{ \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m') }} - {{ \Carbon\Carbon::parse($r->fecha_salida)->format('d/m') }}
                                                            @if($r->cliente)
                                                                | {{ $r->cliente->nombre }}
                                                            @endif
                                                        </small>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-circle me-1"></i>Activo
                                        </span>
                                    </td>
                                    <td>
                                        @if($conflicto->last_sent_at)
                                            <small>{{ $conflicto->last_sent_at->format('d/m/Y H:i') }}</small>
                                        @else
                                            <small class="text-muted">Sin enviar</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.conflictos-reservas.detalle', $conflicto->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.conflictos-reservas.resolver', $conflicto->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Marcar este conflicto como resuelto?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Marcar resuelto">
                                                <i class="fas fa-check me-1"></i>Resolver
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Conflictos Resueltos --}}
    <div class="card shadow-sm">
        <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-check-circle text-success me-2"></i>
                Conflictos Resueltos
            </h5>
            <span class="badge bg-success">{{ $resueltos->count() }}</span>
        </div>
        <div class="card-body p-0">
            @if($resueltos->isEmpty())
                <div class="text-center py-4">
                    <p class="text-muted mb-0">No hay conflictos resueltos.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Deteccion</th>
                                <th>Apartamento</th>
                                <th>Reservas Involucradas</th>
                                <th>Estado</th>
                                <th>Resuelto el</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resueltos as $conflicto)
                                <tr>
                                    <td>
                                        <span>{{ $conflicto->created_at->format('d/m/Y') }}</span>
                                        <br>
                                        <small class="text-muted">{{ $conflicto->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <i class="fas fa-home me-1 text-muted"></i>
                                        {{ $apartamentos[$conflicto->apartamento_id] ?? 'Apartamento #' . $conflicto->apartamento_id }}
                                    </td>
                                    <td>
                                        @if(is_array($conflicto->reserva_ids))
                                            @foreach($conflicto->reserva_ids as $rid)
                                                @php $r = $reservas[$rid] ?? null; @endphp
                                                <div class="mb-1">
                                                    <a href="{{ route('reservas.show', $rid) }}" class="badge bg-secondary text-decoration-none">
                                                        #{{ $rid }}
                                                    </a>
                                                    @if($r)
                                                        <small class="text-muted">
                                                            {{ \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m') }} - {{ \Carbon\Carbon::parse($r->fecha_salida)->format('d/m') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Resuelto
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $conflicto->resolved_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.conflictos-reservas.detalle', $conflicto->id) }}" class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
