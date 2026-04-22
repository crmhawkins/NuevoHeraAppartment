@extends('layouts.appAdmin')

@section('title', 'Reservas pendientes de revisión manual')

@section('content')
<style>
/* [2026-04-22] Botones de rotar sobre la miniatura — aparecen al hacer hover */
.thumb-rotable .thumb-rotar-controls {
    position: absolute;
    left: 0; right: 0; bottom: 0;
    display: flex;
    justify-content: center;
    gap: 2px;
    padding: 2px;
    background: rgba(0,0,0,0.55);
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
    opacity: 0;
    transition: opacity 0.15s ease;
    pointer-events: none;
}
.thumb-rotable:hover .thumb-rotar-controls {
    opacity: 1;
    pointer-events: auto;
}
.thumb-rotable .thumb-rotar-btn {
    width: 18px; height: 18px;
    line-height: 1;
    color: #fff !important;
    background: transparent !important;
    border: none !important;
}
.thumb-rotable .thumb-rotar-btn:hover {
    background: rgba(255,255,255,0.18) !important;
}
</style>
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
                <div class="alert alert-success" style="white-space: pre-line;">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="alert alert-warning" style="white-space: pre-line;">{{ session('warning') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger" style="white-space: pre-line;">{{ session('error') }}</div>
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
                                                            @php $lado = in_array($foto->photo_categoria_id, [13, 15]) ? 'Frontal' : 'Trasera'; @endphp
                                                            @if (!empty($foto->_archivo_existe))
                                                                @include('admin.reservas-revision-manual.partials.thumb-rotable', ['foto' => $foto, 'titulo' => "Cliente — {$lado}", 'alt' => "Doc {$lado}"])
                                                            @else
                                                                <div class="text-center d-flex flex-column align-items-center justify-content-center"
                                                                     style="width:56px;height:56px;border-radius:4px;border:1px dashed #dc3545;background:#fff5f5;"
                                                                     title="La foto {{ $lado }} ya no está disponible (perdida en un deploy anterior del servidor)">
                                                                    <i class="fas fa-image text-danger" style="font-size:14px;"></i>
                                                                    <small class="text-danger" style="font-size:8px;line-height:1;">perdida</small>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            @foreach ($fotosHue as $huespedId => $fotos)
                                                <div class="mb-1">
                                                    <small class="text-muted d-block mb-1">Huésped #{{ $huespedId }}:</small>
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        @foreach ($fotos as $foto)
                                                            @php $lado = in_array($foto->photo_categoria_id, [13, 15]) ? 'Frontal' : 'Trasera'; @endphp
                                                            @if (!empty($foto->_archivo_existe))
                                                                @include('admin.reservas-revision-manual.partials.thumb-rotable', ['foto' => $foto, 'titulo' => "Huésped #{$huespedId} — {$lado}", 'alt' => "Doc {$lado}"])
                                                            @else
                                                                <div class="text-center d-flex flex-column align-items-center justify-content-center"
                                                                     style="width:56px;height:56px;border-radius:4px;border:1px dashed #dc3545;background:#fff5f5;"
                                                                     title="La foto {{ $lado }} ya no está disponible (perdida en un deploy anterior del servidor)">
                                                                    <i class="fas fa-image text-danger" style="font-size:14px;"></i>
                                                                    <small class="text-danger" style="font-size:8px;line-height:1;">perdida</small>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td style="max-width: 400px;">
                                        @php
                                            // [2026-04-21] Detectar huespedes que son la misma persona que el
                                            // cliente (comparten DNI). Para avisar visualmente en cada issue.
                                            $huespedesMismaPersona = [];
                                            if ($r->cliente && $r->cliente->num_identificacion) {
                                                $dniCli = (string) $r->cliente->num_identificacion;
                                                $hs = \App\Models\Huesped::where('reserva_id', $r->id)
                                                    ->where('numero_identificacion', $dniCli)
                                                    ->pluck('id')->all();
                                                $huespedesMismaPersona = $hs;
                                            }

                                            // Traer el valor actual del campo para mostrarlo en el modal
                                            $valoresActuales = [];
                                            if ($r->cliente) {
                                                $valoresActuales['cliente'] = [
                                                    'codigo_postal' => $r->cliente->codigo_postal,
                                                    'num_identificacion' => $r->cliente->num_identificacion,
                                                    'provincia' => $r->cliente->provincia,
                                                    'direccion' => $r->cliente->direccion,
                                                    'nombre_municipio' => $r->cliente->nombre_municipio ?? null,
                                                    'municipio' => $r->cliente->municipio ?? null,
                                                    'nacionalidad' => $r->cliente->nacionalidad,
                                                    'apellido1' => $r->cliente->apellido1,
                                                    'apellido2' => $r->cliente->apellido2,
                                                    'nombre' => $r->cliente->nombre,
                                                    'tipo_documento' => $r->cliente->tipo_documento,
                                                    'numero_soporte_documento' => $r->cliente->numero_soporte_documento ?? null,
                                                ];
                                            }
                                        @endphp
                                        @forelse ($r->_issues_parsed as $issue)
                                            @php
                                                $sev = $issue['severity'] ?? 'warning';
                                                $badgeClass = $sev === 'error' ? 'bg-danger' : 'bg-warning text-dark';
                                                $entidad = $issue['entidad'] ?? '';
                                                $entidadId = $issue['entidad_id'] ?? null;
                                                $campo = $issue['campo'] ?? '';
                                                $mensaje = $issue['mensaje'] ?? '';
                                                $sugerencia = $issue['sugerencia'] ?? null;
                                                // Valor actual del campo
                                                $valorActual = '';
                                                if ($entidad === 'cliente') {
                                                    $valorActual = $valoresActuales['cliente'][$campo] ?? '';
                                                } elseif ($entidad === 'huesped' && $entidadId) {
                                                    $huespedObj = \App\Models\Huesped::find($entidadId);
                                                    if ($huespedObj) {
                                                        $valorActual = $huespedObj->{$campo} ?? '';
                                                    }
                                                }
                                            @endphp
                                            @php
                                                // ¿Este issue es de una persona que aparece doble (cliente+huesped)?
                                                $esMismaPersona = ($entidad === 'cliente' && !empty($huespedesMismaPersona))
                                                    || ($entidad === 'huesped' && in_array((int) $entidadId, $huespedesMismaPersona, true));
                                            @endphp
                                            <div class="mb-2 small border-bottom pb-2">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div class="flex-grow-1">
                                                        <span class="badge {{ $badgeClass }}">{{ strtoupper($sev) }}</span>
                                                        <strong>{{ $entidad }}{{ $entidadId ? " #$entidadId" : '' }}</strong>
                                                        <span class="text-muted">· campo:</span> <code>{{ $campo }}</code>
                                                        @if ($esMismaPersona)
                                                            <span class="badge bg-info-subtle text-info ms-1"
                                                                  title="Cliente y huésped son la misma persona (mismo DNI). Al arreglar uno se actualizarán ambos.">
                                                                <i class="fas fa-link"></i> mismo DNI
                                                            </span>
                                                        @endif
                                                        <div>{{ $mensaje }}</div>
                                                        <div class="text-muted mt-1">
                                                            Valor actual: <code>{{ $valorActual !== '' ? $valorActual : '(vacío)' }}</code>
                                                        </div>
                                                        @if ($sugerencia)
                                                            <div class="text-success mt-1">
                                                                <i class="fas fa-lightbulb"></i> Sugerencia: <code>{{ $sugerencia }}</code>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    @if (str_starts_with((string) $campo, '_'))
                                                        {{-- Pseudo-campo tecnico (_ia_excepcion, _ia_no_disponible):
                                                             no hay nada que editar. Solo reintentar. --}}
                                                        <span class="badge bg-secondary-subtle text-secondary small"
                                                              title="Error tecnico del validador IA. Usa el boton 'Revalidar' de la columna Acciones para reintentar.">
                                                            <i class="fas fa-robot me-1"></i>Error IA
                                                        </span>
                                                    @else
                                                        <button type="button"
                                                                class="btn btn-sm btn-primary btn-fix"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#modalFix"
                                                                data-reserva="{{ $r->id }}"
                                                                data-entidad="{{ $entidad }}"
                                                                data-entidad-id="{{ $entidadId }}"
                                                                data-campo="{{ $campo }}"
                                                                data-valor="{{ $valorActual }}"
                                                                data-sugerencia="{{ $sugerencia }}"
                                                                data-mensaje="{{ $mensaje }}"
                                                                title="Corregir este campo">
                                                            <i class="fas fa-wrench"></i> Arreglar
                                                        </button>
                                                    @endif
                                                </div>
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
                                                  action="{{ route('admin.reservas-revision-manual.reanalizar-dni', $r->id) }}"
                                                  class="form-reanalizar">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-info w-100 text-white"
                                                        title="Vuelve a pasar la foto del DNI por la IA y rellena los campos que quedaron vacíos la primera vez (p.ej. número de soporte)">
                                                    <i class="fas fa-robot me-1"></i>Re-analizar DNI con IA
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

{{-- Modal para arreglar un campo concreto --}}
<div class="modal fade" id="modalFix" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.reservas-revision-manual.fix') }}" class="modal-content">
            @csrf
            <input type="hidden" name="reserva_id" id="fix_reserva">
            <input type="hidden" name="entidad" id="fix_entidad">
            <input type="hidden" name="entidad_id" id="fix_entidad_id">
            <input type="hidden" name="campo" id="fix_campo">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-wrench me-2"></i>Arreglar campo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small mb-3" id="fix_mensaje"></div>

                <div class="mb-2 small text-muted">
                    Editando: <strong id="fix_entidad_display"></strong> · campo: <code id="fix_campo_display"></code>
                </div>

                <label class="form-label">Nuevo valor</label>
                <input type="text" name="valor" id="fix_valor" class="form-control" maxlength="300" autofocus>
                <small class="text-muted d-block mt-1" id="fix_sugerencia_hint"></small>

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="autorevalidar" id="fix_autorevalidar" value="1" checked>
                    <label class="form-check-label" for="fix_autorevalidar">
                        Revalidar e intentar enviar a MIR inmediatamente tras guardar
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalFix = document.getElementById('modalFix');
    modalFix.addEventListener('show.bs.modal', function (e) {
        const t = e.relatedTarget;
        document.getElementById('fix_reserva').value = t.getAttribute('data-reserva');
        document.getElementById('fix_entidad').value = t.getAttribute('data-entidad');
        document.getElementById('fix_entidad_id').value = t.getAttribute('data-entidad-id');
        document.getElementById('fix_campo').value = t.getAttribute('data-campo');
        document.getElementById('fix_valor').value = t.getAttribute('data-valor') || '';
        document.getElementById('fix_entidad_display').textContent =
            t.getAttribute('data-entidad') + ' #' + t.getAttribute('data-entidad-id');
        document.getElementById('fix_campo_display').textContent = t.getAttribute('data-campo');
        document.getElementById('fix_mensaje').textContent = t.getAttribute('data-mensaje') || '';
        const sug = t.getAttribute('data-sugerencia');
        const hint = document.getElementById('fix_sugerencia_hint');
        if (sug && sug !== 'null' && sug.trim() !== '') {
            hint.innerHTML = '💡 Sugerencia de la IA: <code>' + sug + '</code>';
        } else {
            hint.textContent = '';
        }
    });
});
</script>

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

{{-- [2026-04-22] Modal de progreso para el re-analisis con IA. La peticion
     HTTP tarda ~5-15 segundos (Qwen3-VL mirando la foto del DNI), asi que
     ensenamos frases divertidas para que el admin no piense que se ha
     colgado. Se abre al enviar el form de re-analizar y se cierra solo
     cuando la nueva pagina se carga (redirect del POST). --}}
<div class="modal fade" id="modalReanalizando" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="mb-4" style="font-size: 3rem;">
                    <i class="fas fa-robot" id="reanaliza-icon" style="color: #17a2b8;"></i>
                </div>
                <h5 class="mb-3">Analizando el DNI con la IA…</h5>
                <p class="text-muted mb-4" id="reanaliza-frase" style="min-height: 3em;">
                    Preparando los folios…
                </p>
                <div class="progress" style="height: 6px;">
                    <div id="reanaliza-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                         role="progressbar" style="width: 0%"></div>
                </div>
                <small class="text-muted d-block mt-3">
                    <i class="fas fa-info-circle me-1"></i>Esto suele tardar entre 5 y 15 segundos.
                </small>
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

    // ----- Modal de progreso "re-analizando con IA" -----
    const FRASES = [
        'Preparando los folios…',
        'Sacando la lupa…',
        'Ajustando las gafas…',
        'Leyendo el DNI con detenimiento…',
        'Buscando el número de soporte…',
        'Cotejando con el archivo…',
        'Pasándolo a limpio…',
        'Comparando datos con el huésped…',
        'Revisando los bordes del carné…',
        'Descifrando el sello holográfico…',
        'Preguntándole al Reino de España…',
        'Desempolvando el bolígrafo de tinta azul…',
        'Poniendo el pie derecho al escribir…',
        'Mirando dos veces por si acaso…',
        'Consultando con el ministerio…',
        'Revisando por enésima vez…',
        'Abriendo el monóculo…',
        'Pidiendo permiso al notario…',
        'Llamando a la fábrica de DNIs…',
    ];
    const ICONOS = ['fa-robot', 'fa-search', 'fa-glasses', 'fa-id-card', 'fa-magnifying-glass', 'fa-pen-nib', 'fa-file-signature'];

    const formsReanalizar = document.querySelectorAll('form.form-reanalizar');
    const modalReanaliza = document.getElementById('modalReanalizando');
    const fraseEl = document.getElementById('reanaliza-frase');
    const iconEl  = document.getElementById('reanaliza-icon');
    const barEl   = document.getElementById('reanaliza-bar');

    formsReanalizar.forEach(f => {
        f.addEventListener('submit', function (e) {
            if (!confirm('¿Pedir a la IA que re-analice la foto del DNI para extraer los campos que falten?\n\nSolo rellenará campos vacíos (no pisa datos ya guardados).')) {
                e.preventDefault();
                return false;
            }
            // Mostrar modal de progreso
            const bsModal = new bootstrap.Modal(modalReanaliza);
            bsModal.show();

            // Rotar frases cada 1.8s
            let idx = 0;
            let frasesUsadas = [...FRASES].sort(() => Math.random() - 0.5);
            fraseEl.textContent = frasesUsadas[0];
            let iconIdx = 0;
            setInterval(() => {
                idx = (idx + 1) % frasesUsadas.length;
                fraseEl.style.opacity = 0;
                setTimeout(() => {
                    fraseEl.textContent = frasesUsadas[idx];
                    fraseEl.style.opacity = 1;
                }, 180);
                iconIdx = (iconIdx + 1) % ICONOS.length;
                iconEl.className = 'fas ' + ICONOS[iconIdx];
            }, 1800);

            // Simular barra de progreso que sube asintóticamente hacia 90%
            // (nunca llega al 100% hasta que el servidor responde — así si
            // tarda mucho no parece que esté "terminado y colgado")
            let pct = 0;
            setInterval(() => {
                pct = pct + (90 - pct) * 0.04;
                barEl.style.width = pct.toFixed(1) + '%';
            }, 200);

            // No preventDefault — dejamos que el POST se envie y navegue.
            // Al cargar la nueva pagina el modal desaparece naturalmente.
        });
    });
    // Estilo para transicion suave de frases
    if (fraseEl) fraseEl.style.transition = 'opacity 0.2s ease';
});
</script>
@endsection
