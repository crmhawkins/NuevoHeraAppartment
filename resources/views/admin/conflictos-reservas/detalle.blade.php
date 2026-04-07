@extends('layouts.appAdmin')

@section('title', 'Detalle del Conflicto #' . $conflicto->id)

@section('content')
<div class="container-fluid">
    {{-- Cabecera --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.conflictos-reservas.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
            <span class="fs-5 fw-bold">Conflicto #{{ $conflicto->id }}</span>
        </div>
        <div>
            @if(is_null($conflicto->resolved_at))
                <form action="{{ route('admin.conflictos-reservas.resolver', $conflicto->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Marcar este conflicto como resuelto?')">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Marcar como Resuelto
                    </button>
                </form>
            @else
                <span class="badge bg-success fs-6 px-3 py-2">
                    <i class="fas fa-check-circle me-1"></i>Resuelto el {{ $conflicto->resolved_at->format('d/m/Y H:i') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Informacion del conflicto --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header {{ is_null($conflicto->resolved_at) ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10' }}">
            <h5 class="mb-0">
                @if(is_null($conflicto->resolved_at))
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Conflicto Activo
                @else
                    <i class="fas fa-check-circle text-success me-2"></i>Conflicto Resuelto
                @endif
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong class="text-muted d-block mb-1">Apartamento</strong>
                    <span class="fs-5">
                        <i class="fas fa-home me-1"></i>
                        {{ $apartamento->nombre ?? 'Apartamento #' . $conflicto->apartamento_id }}
                    </span>
                </div>
                <div class="col-md-3">
                    <strong class="text-muted d-block mb-1">Detectado</strong>
                    <span>{{ $conflicto->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="col-md-3">
                    <strong class="text-muted d-block mb-1">Ultima Notificacion</strong>
                    <span>{{ $conflicto->last_sent_at ? $conflicto->last_sent_at->format('d/m/Y H:i') : 'Sin enviar' }}</span>
                </div>
                <div class="col-md-3">
                    <strong class="text-muted d-block mb-1">Conflict Key</strong>
                    <code class="small">{{ $conflicto->conflict_key }}</code>
                </div>
            </div>
        </div>
    </div>

    {{-- Reservas lado a lado --}}
    <h5 class="mb-3">
        <i class="fas fa-exchange-alt me-2"></i>Reservas en Conflicto
    </h5>
    <div class="row">
        @foreach($reservas as $reserva)
            <div class="col-md-{{ 12 / max($reservas->count(), 1) }}">
                <div class="card shadow-sm h-100" style="border-top: 3px solid {{ is_null($conflicto->resolved_at) ? '#dc3545' : '#6c757d' }};">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <a href="{{ route('reservas.show', $reserva->id) }}" class="text-decoration-none">
                                Reserva #{{ $reserva->id }}
                            </a>
                        </h6>
                        <a href="{{ route('reservas.show', $reserva->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt me-1"></i>Ver
                        </a>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted fw-semibold" style="width: 40%;">Cliente</td>
                                <td>
                                    @if($reserva->cliente)
                                        {{ $reserva->cliente->nombre }} {{ $reserva->cliente->apellidos ?? '' }}
                                    @else
                                        <span class="text-muted">Sin cliente</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold">Entrada</td>
                                <td>
                                    <i class="fas fa-sign-in-alt text-success me-1"></i>
                                    {{ \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold">Salida</td>
                                <td>
                                    <i class="fas fa-sign-out-alt text-danger me-1"></i>
                                    {{ \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold">Noches</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($reserva->fecha_entrada)->diffInDays(\Carbon\Carbon::parse($reserva->fecha_salida)) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold">Apartamento</td>
                                <td>
                                    {{ $reserva->apartamento->nombre ?? 'N/A' }}
                                </td>
                            </tr>
                            @if($reserva->estado)
                                <tr>
                                    <td class="text-muted fw-semibold">Estado</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $reserva->estado }}</span>
                                    </td>
                                </tr>
                            @endif
                            @if($reserva->plataforma)
                                <tr>
                                    <td class="text-muted fw-semibold">Plataforma</td>
                                    <td>{{ $reserva->plataforma }}</td>
                                </tr>
                            @endif
                            @if($reserva->precio_total)
                                <tr>
                                    <td class="text-muted fw-semibold">Precio Total</td>
                                    <td class="fw-bold">{{ number_format($reserva->precio_total, 2, ',', '.') }} &euro;</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($reservas->count() >= 2)
        {{-- Visualizacion del solapamiento --}}
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-gantt me-2"></i>Solapamiento de Fechas</h6>
            </div>
            <div class="card-body">
                @php
                    $fechaMin = $reservas->min('fecha_entrada');
                    $fechaMax = $reservas->max('fecha_salida');
                    $totalDias = max(\Carbon\Carbon::parse($fechaMin)->diffInDays(\Carbon\Carbon::parse($fechaMax)), 1);
                    $colors = ['#007bff', '#dc3545', '#ffc107', '#28a745', '#6f42c1'];
                @endphp
                <div class="position-relative" style="min-height: {{ $reservas->count() * 50 + 30 }}px;">
                    {{-- Eje de fechas --}}
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>{{ \Carbon\Carbon::parse($fechaMin)->format('d/m/Y') }}</span>
                        <span>{{ \Carbon\Carbon::parse($fechaMax)->format('d/m/Y') }}</span>
                    </div>
                    @foreach($reservas as $idx => $r)
                        @php
                            $inicio = \Carbon\Carbon::parse($fechaMin)->diffInDays(\Carbon\Carbon::parse($r->fecha_entrada));
                            $duracion = \Carbon\Carbon::parse($r->fecha_entrada)->diffInDays(\Carbon\Carbon::parse($r->fecha_salida));
                            $left = ($inicio / $totalDias) * 100;
                            $width = max(($duracion / $totalDias) * 100, 3);
                            $color = $colors[$idx % count($colors)];
                        @endphp
                        <div class="mb-2 d-flex align-items-center">
                            <div style="width: 80px; flex-shrink: 0;" class="text-end pe-2 small fw-semibold">#{{ $r->id }}</div>
                            <div class="flex-grow-1 position-relative" style="height: 30px; background: #f0f0f0; border-radius: 4px;">
                                <div style="position: absolute; left: {{ $left }}%; width: {{ $width }}%; height: 100%; background: {{ $color }}; border-radius: 4px; opacity: 0.8;" title="Reserva #{{ $r->id }}: {{ \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m') }} - {{ \Carbon\Carbon::parse($r->fecha_salida)->format('d/m') }}">
                                    <span class="text-white small px-1" style="line-height: 30px; white-space: nowrap;">
                                        {{ \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m') }} - {{ \Carbon\Carbon::parse($r->fecha_salida)->format('d/m') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
