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
                                <th>Fotos DNI</th>
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
                                    <td style="min-width: 180px;">
                                        @php
                                            $fotosCli = $r->_fotos_dni['cliente'] ?? [];
                                            $fotosHue = $r->_fotos_dni['huespedes'] ?? [];
                                            $sinFotos = empty($fotosCli) && empty($fotosHue);
                                        @endphp
                                        @if ($sinFotos)
                                            <span class="text-muted small">
                                                <i class="fas fa-image-slash"></i> Sin fotos
                                            </span>
                                        @else
                                            @if (!empty($fotosCli))
                                                <div class="mb-2">
                                                    <small class="text-muted d-block mb-1">Cliente:</small>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        @foreach ($fotosCli as $foto)
                                                            <a href="#"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#modalFoto"
                                                               data-src="{{ route('admin.reservas-revision-manual.foto', $foto->id) }}"
                                                               data-titulo="Cliente — {{ $foto->photo_categoria_id == 13 ? 'Frontal' : 'Trasera' }}"
                                                               title="{{ $foto->photo_categoria_id == 13 ? 'DNI Frontal' : 'DNI Trasera' }}">
                                                                <img src="{{ route('admin.reservas-revision-manual.foto', $foto->id) }}"
                                                                     alt="DNI"
                                                                     style="width:56px;height:56px;object-fit:cover;border-radius:4px;border:1px solid #ccc;">
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            @foreach ($fotosHue as $huespedId => $fotos)
                                                <div class="mb-1">
                                                    <small class="text-muted d-block mb-1">Huésped #{{ $huespedId }}:</small>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        @foreach ($fotos as $foto)
                                                            <a href="#"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#modalFoto"
                                                               data-src="{{ route('admin.reservas-revision-manual.foto', $foto->id) }}"
                                                               data-titulo="Huésped #{{ $huespedId }} — {{ $foto->photo_categoria_id == 13 ? 'Frontal' : 'Trasera' }}">
                                                                <img src="{{ route('admin.reservas-revision-manual.foto', $foto->id) }}"
                                                                     alt="DNI"
                                                                     style="width:56px;height:56px;object-fit:cover;border-radius:4px;border:1px solid #ccc;">
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
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

{{-- Modal para ver la foto del DNI a tamaño completo --}}
<div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFotoTitulo">Foto DNI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalFotoImg" src="" alt="" style="max-width:100%;max-height:75vh;">
            </div>
            <div class="modal-footer">
                <a id="modalFotoAbrir" href="#" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i>Abrir en pestaña nueva
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalFoto');
    modal.addEventListener('show.bs.modal', function (e) {
        const trigger = e.relatedTarget;
        const src = trigger.getAttribute('data-src');
        const titulo = trigger.getAttribute('data-titulo') || 'Foto DNI';
        document.getElementById('modalFotoImg').src = src;
        document.getElementById('modalFotoTitulo').textContent = titulo;
        document.getElementById('modalFotoAbrir').href = src;
    });
});
</script>
@endsection
