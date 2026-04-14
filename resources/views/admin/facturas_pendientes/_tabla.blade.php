{{-- Parcial tabla de facturas. Recibe: $id, $active, $facturas, $mostrarCandidatos --}}
<div class="tab-pane fade {{ $active ? 'show active' : '' }}" id="{{ $id }}">
    @if($facturas->isEmpty())
        <div class="text-muted text-center py-4">No hay facturas en este estado.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width:80px">Preview</th>
                        <th>Archivo</th>
                        <th>Importe IA</th>
                        <th>Fecha IA</th>
                        <th>Proveedor IA</th>
                        <th>Estado</th>
                        <th>Error / Info</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($facturas as $fp)
                        <tr>
                            <td>
                                @if(str_contains($fp->mime_type ?? '', 'image'))
                                    <a href="{{ route('admin.facturasPendientes.imagen', $fp->id) }}" target="_blank">
                                        <img src="{{ route('admin.facturasPendientes.imagen', $fp->id) }}" alt="preview" style="max-width:70px; max-height:70px; object-fit:cover; border:1px solid #ddd;">
                                    </a>
                                @else
                                    <a href="{{ route('admin.facturasPendientes.imagen', $fp->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-file-pdf"></i> Ver
                                    </a>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-bold">{{ $fp->filename }}</div>
                                <div class="text-muted small">
                                    id={{ $fp->id }} · {{ $fp->uploaded_from ?? '?' }} ·
                                    {{ $fp->created_at?->diffForHumans() }}
                                </div>
                            </td>
                            <td>
                                @if($fp->importe_detectado)
                                    <strong>{{ number_format($fp->importe_detectado, 2) }} &euro;</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                                @if($fp->confianza_ia)
                                    <div class="small text-muted">{{ round($fp->confianza_ia * 100) }}%</div>
                                @endif
                            </td>
                            <td>{{ $fp->fecha_detectada?->format('d/m/Y') ?? '-' }}</td>
                            <td class="small">{{ $fp->proveedor_detectado ?? '-' }}</td>
                            <td>
                                @php
                                    $badges = [
                                        'pendiente'  => 'bg-secondary',
                                        'procesando' => 'bg-info',
                                        'espera'     => 'bg-warning text-dark',
                                        'error'      => 'bg-danger',
                                        'asociada'   => 'bg-success',
                                    ];
                                @endphp
                                <span class="badge {{ $badges[$fp->status] ?? 'bg-secondary' }}">{{ $fp->status }}</span>
                                @if($fp->intentos > 0)
                                    <div class="small text-muted">intentos: {{ $fp->intentos }}</div>
                                @endif
                            </td>
                            <td class="small" style="max-width:260px;">
                                @if($fp->error_message)
                                    <div class="text-danger">{{ \Illuminate\Support\Str::limit($fp->error_message, 150) }}</div>
                                @endif
                                @if($mostrarCandidatos && !empty($fp->candidatos_gasto_ids))
                                    <div class="mt-1">
                                        <strong>Candidatos:</strong>
                                        @foreach($fp->candidatos_gasto_ids as $gid)
                                            <span class="badge bg-light text-dark">#{{ $gid }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($fp->gasto_id)
                                    <div class="mt-1 text-success">
                                        <i class="fas fa-link"></i> Asociada al gasto #{{ $fp->gasto_id }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if(in_array($fp->status, ['espera', 'error']))
                                    <button class="btn btn-sm btn-outline-primary"
                                            data-asociar-url="{{ route('admin.facturasPendientes.asociar', $fp->id) }}">
                                        <i class="fas fa-link"></i> Asociar
                                    </button>
                                    <form method="POST" action="{{ route('admin.facturasPendientes.reintentar', $fp->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-info" type="submit" title="Volver a procesar con IA">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.facturasPendientes.descartar', $fp->id) }}" class="d-inline"
                                          onsubmit="return confirm('Descartar definitivamente?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
