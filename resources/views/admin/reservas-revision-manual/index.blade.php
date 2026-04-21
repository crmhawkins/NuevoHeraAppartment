@extends('layouts.appAdmin')

@section('title', 'Reservas pendientes de revisión manual')

@section('content')
<div class="container-fluid">
    <div class="apple-card">
        <div class="apple-card-header">
            <h3 class="apple-card-title">
                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                Reservas bloqueadas por validación — revisión manual
            </h3>
            <div class="apple-card-actions">
                <span class="badge bg-warning-subtle text-warning">
                    {{ $reservas->count() }} pendientes
                </span>
            </div>
        </div>
        <div class="apple-card-body">

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($reservas->isEmpty())
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    No hay reservas bloqueadas. Todas las reservas con DNI subido se enviaron correctamente a MIR.
                </div>
            @else
                <p class="text-muted">
                    Estas reservas tienen el DNI subido pero el preflight ha detectado datos que MIR rechazaría.
                    Corrige los campos marcados y pulsa <strong>Revalidar</strong>. Si el dato es correcto y quieres
                    no enviar a MIR, pulsa <strong>Ignorar</strong>.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Reserva</th>
                                <th>Cliente</th>
                                <th>Apartamento</th>
                                <th>Entrada</th>
                                <th>Problemas detectados</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reservas as $r)
                                <tr>
                                    <td>
                                        <strong>#{{ $r->id }}</strong>
                                        <small class="d-block text-muted">{{ $r->codigo_reserva }}</small>
                                        <small class="d-block text-muted">{{ $r->origen }}</small>
                                    </td>
                                    <td>
                                        @if ($r->cliente)
                                            <strong>{{ $r->cliente->nombre }} {{ $r->cliente->apellido1 }}</strong>
                                            <small class="d-block text-muted">
                                                DNI: <code>{{ $r->cliente->num_identificacion ?: '—' }}</code><br>
                                                CP: <code>{{ $r->cliente->codigo_postal ?: '—' }}</code>
                                                · Nac: {{ $r->cliente->nacionalidad ?: '—' }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $r->apartamento->titulo ?? '-' }}
                                        <small class="d-block text-muted">{{ $r->apartamento->edificio->nombre ?? '' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m/Y') }}</small>
                                        <small class="d-block text-muted">
                                            {{ \Carbon\Carbon::parse($r->fecha_entrada)->diffForHumans() }}
                                        </small>
                                    </td>
                                    <td style="max-width: 400px;">
                                        @forelse ($r->_issues_parsed as $issue)
                                            @php
                                                $sev = $issue['severity'] ?? 'warning';
                                                $badgeClass = $sev === 'error' ? 'bg-danger' : 'bg-warning text-dark';
                                                $entidad = $issue['entidad'] ?? '';
                                                $entidadId = $issue['entidad_id'] ?? null;
                                                $campo = $issue['campo'] ?? '';
                                                $mensaje = $issue['mensaje'] ?? '';
                                                $sugerencia = $issue['sugerencia'] ?? null;
                                            @endphp
                                            <div class="mb-2 small">
                                                <span class="badge {{ $badgeClass }}">{{ strtoupper($sev) }}</span>
                                                <strong>{{ $entidad }}{{ $entidadId ? " #$entidadId" : '' }}</strong>
                                                <span class="text-muted">· campo:</span> <code>{{ $campo }}</code>
                                                <div>{{ $mensaje }}</div>
                                                @if ($sugerencia)
                                                    <div class="text-success small">
                                                        <i class="fas fa-lightbulb"></i> Sugerencia: <code>{{ $sugerencia }}</code>
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-muted small">Sin detalle (respuesta MIR sin parsear)</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            @if ($r->cliente)
                                                <a href="{{ route('clientes.edit', $r->cliente->id) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-user-edit me-1"></i>Editar cliente
                                                </a>
                                            @endif
                                            <a href="{{ route('reservas.show', $r->id) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-eye me-1"></i>Ver reserva
                                            </a>
                                            <form method="POST"
                                                  action="{{ route('admin.reservas-revision-manual.revalidar', $r->id) }}"
                                                  onsubmit="return confirm('¿Reenviar a MIR ahora?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success w-100">
                                                    <i class="fas fa-redo me-1"></i>Revalidar
                                                </button>
                                            </form>
                                            <form method="POST"
                                                  action="{{ route('admin.reservas-revision-manual.ignorar', $r->id) }}"
                                                  onsubmit="return confirm('¿Ignorar esta reserva? No se enviará a MIR.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                    <i class="fas fa-times me-1"></i>Ignorar
                                                </button>
                                            </form>
                                        </div>
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
