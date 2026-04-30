@extends('layouts.appAdmin')

@section('title', 'Revenue Management')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-0">📈 Revenue Management</h2>
            <small class="text-muted">Tus precios vs competencia · Próximos {{ $diasVista }} días</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('revenue.hoy') }}" class="btn btn-sm btn-outline-warning">
                <i class="fas fa-bolt me-1"></i>Vista hoy
            </a>
            <a href="{{ route('revenue.historial') }}" class="btn btn-sm btn-outline-secondary">
                Histórico cambios
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="card card-body mb-3 p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Apartamentos</label>
                <select name="apartamentos[]" class="form-select form-select-sm" multiple size="3">
                    @foreach($apartamentos as $apt)
                        <option value="{{ $apt->id }}" selected>{{ $apt->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Días vista</label>
                <select name="dias" class="form-select form-select-sm">
                    @foreach([7,14,30,45,60] as $d)
                        <option value="{{ $d }}" @selected($diasVista===$d)>{{ $d }} días</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-secondary btn-sm w-100">Filtrar</button>
            </div>
            <div class="col-md-5 text-end">
                <label class="form-label small mb-1">Calcular precios competencia para:</label>
                <div class="d-flex gap-2 justify-content-end">
                    <select id="scrape-dias" class="form-select form-select-sm" style="width: auto">
                        <option value="7" selected>Próximos 7 días</option>
                        <option value="14">Próximos 14 días</option>
                        <option value="30">Próximos 30 días</option>
                        <option value="60">Próximos 60 días</option>
                        <option value="90">Próximos 90 días (máx)</option>
                    </select>
                    <select id="scrape-zona" class="form-select form-select-sm" style="width: auto">
                        <option value="algeciras_centro">Algeciras centro</option>
                        <option value="algeciras_costa">Algeciras costa</option>
                        <option value="bahia_completa">Bahía completa</option>
                    </select>
                    <select id="scrape-adultos" class="form-select form-select-sm" style="width: auto">
                        <option value="2" selected>2 ad.</option>
                        <option value="1">1 ad.</option>
                        <option value="3">3 ad.</option>
                        <option value="4">4 ad.</option>
                    </select>
                    <button type="button" id="btn-scrape-multi" class="btn btn-warning btn-sm">
                        <i class="fas fa-sync-alt me-1"></i>Calcular
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Modal de progreso --}}
    <div class="modal fade" id="modal-progreso" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-cog fa-spin me-2"></i>
                        Calculando Revenue Management
                    </h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="display-1 fw-bold text-warning mb-2" id="prog-eta">5:00</div>
                    <small class="text-muted">tiempo estimado restante</small>

                    <div class="progress my-4" style="height: 24px">
                        <div id="prog-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning"
                             role="progressbar" style="width: 0%">
                            <span id="prog-pct">0%</span>
                        </div>
                    </div>

                    <h5 class="text-primary mb-1" id="prog-fase">Iniciando...</h5>
                    <p class="text-muted small mb-0" id="prog-detalle">Preparando proceso en background</p>

                    <div class="row text-center mt-4 g-2">
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <div class="h5 mb-0" id="prog-step">0/0</div>
                                    <small class="text-muted">días procesados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <div class="h5 mb-0" id="prog-aps">0</div>
                                    <small class="text-muted">precios calculados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <div class="h5 mb-0" id="prog-elapsed">0s</div>
                                    <small class="text-muted">transcurrido</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info small mt-4 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Puedes cerrar esta ventana y seguir trabajando.</strong>
                        El proceso continúa en background. Vuelve a esta página en unos minutos
                        y los precios estarán actualizados.
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <small class="text-muted" id="prog-job-id"></small>
                    <div>
                        <button type="button" id="btn-cerrar-modal" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cerrar (sigue en background)
                        </button>
                        <button type="button" id="btn-ver-resultado" class="btn btn-success" style="display: none">
                            Ver resultados
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Matriz --}}
    @if($apartamentos->isEmpty())
        <div class="alert alert-warning">No hay apartamentos para mostrar.</div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 70vh">
                    <table class="table table-sm table-hover mb-0 align-middle" id="matriz-revenue">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="min-width: 180px">Apartamento</th>
                                @foreach($fechas as $f)
                                    <th class="text-center" style="min-width: 90px">
                                        <small class="d-block text-muted">{{ $f->isoFormat('ddd') }}</small>
                                        <strong>{{ $f->format('d/m') }}</strong>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($apartamentos as $apt)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $apt->nombre }}</div>
                                        <small class="text-muted">
                                            <a href="{{ route('revenue.configurar', $apt->id) }}">⚙ configurar</a>
                                        </small>
                                    </td>
                                    @foreach($fechas as $f)
                                        @php
                                            $key = $apt->id . '_' . $f->toDateString();
                                            $rec = $recomendaciones[$key] ?? null;
                                            $estado = $rec ? $rec->estado : 'sin_datos';
                                        @endphp
                                        <td class="text-center
                                            @if($estado === 'subir') table-success
                                            @elseif($estado === 'bajar') table-danger
                                            @elseif($estado === 'aplicado') table-info
                                            @endif"
                                            data-apt="{{ $apt->id }}"
                                            data-fecha="{{ $f->toDateString() }}"
                                            data-precio-rec="{{ $rec?->precio_recomendado }}"
                                            style="cursor: pointer"
                                            @if($rec)
                                                title="Actual: {{ number_format($rec->precio_actual ?? 0, 0) }}€ → Recomendado: {{ number_format($rec->precio_recomendado ?? 0, 0) }}€&#10;Competencia: {{ number_format($rec->competencia_min ?? 0, 0) }}-{{ number_format($rec->competencia_max ?? 0, 0) }}€ (mediana {{ number_format($rec->competencia_media ?? 0, 0) }}€)&#10;{{ $rec->razonamiento }}"
                                            @endif
                                            >
                                            @if($rec && $rec->precio_recomendado)
                                                <input type="checkbox" class="form-check-input cambio-check"
                                                    data-apt="{{ $apt->id }}"
                                                    data-fecha="{{ $f->toDateString() }}"
                                                    data-precio="{{ $rec->precio_recomendado }}">
                                                <div class="small">
                                                    <span class="text-muted">{{ number_format($rec->precio_actual ?? 0, 0) }}€</span>
                                                    <span>→</span>
                                                    <strong>{{ number_format($rec->precio_recomendado, 0) }}€</strong>
                                                </div>
                                                @if($estado === 'aplicado')
                                                    <div class="small text-info">✓ aplicado</div>
                                                @endif
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Footer fijo con acción --}}
        <div class="card mt-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span id="contador-seleccion">0</span> celdas seleccionadas
                </div>
                <div>
                    <button id="btn-simular" class="btn btn-outline-secondary" disabled>
                        Simular (dry-run)
                    </button>
                    <button id="btn-aplicar" class="btn btn-success" disabled>
                        Aplicar precios seleccionados
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
(function() {
    const checks = document.querySelectorAll('.cambio-check');
    const contador = document.getElementById('contador-seleccion');
    const btnAplicar = document.getElementById('btn-aplicar');
    const btnSimular = document.getElementById('btn-simular');

    function actualizarContador() {
        const sel = document.querySelectorAll('.cambio-check:checked');
        contador.textContent = sel.length;
        btnAplicar.disabled = sel.length === 0;
        btnSimular.disabled = sel.length === 0;
    }
    checks.forEach(c => c.addEventListener('change', actualizarContador));

    function recolectarCambios() {
        return Array.from(document.querySelectorAll('.cambio-check:checked')).map(c => ({
            apartamento_id: parseInt(c.dataset.apt),
            fecha: c.dataset.fecha,
            precio: parseFloat(c.dataset.precio),
        }));
    }

    async function lanzar(dryRun) {
        const cambios = recolectarCambios();
        if (cambios.length === 0) return;
        if (!dryRun && !confirm(`¿Aplicar ${cambios.length} cambios de precio en Channex? Esta acción es REAL.`)) return;
        const r = await fetch('{{ route("revenue.aplicar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({cambios, dry_run: dryRun}),
        });
        const data = await r.json();
        let msg = `OK: ${data.ok}/${data.total}`;
        if (data.errores && data.errores.length) {
            msg += '\n\nErrores:\n' + data.errores.slice(0, 5).join('\n');
        }
        if (dryRun) msg = 'SIMULACIÓN — ' + msg;
        alert(msg);
        if (!dryRun && data.ok > 0) location.reload();
    }
    btnAplicar.addEventListener('click', () => lanzar(false));
    btnSimular.addEventListener('click', () => lanzar(true));

    // === SCRAPE MULTI-DIA EN BACKGROUND ===
    const btnScrapeMulti = document.getElementById('btn-scrape-multi');
    const urlScrapeMulti = '{{ route("revenue.scrapeMulti") }}';
    const urlProgressBase = '{{ url("/admin/revenue/scrape-progress") }}';

    let pollTimer = null;
    let activeJobId = localStorage.getItem('revenue_active_job') || null;

    function fmtTime(seconds) {
        if (!seconds || seconds < 0) return '—';
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return `${m}:${String(s).padStart(2, '0')}`;
    }

    btnScrapeMulti.addEventListener('click', async () => {
        const dias = parseInt(document.getElementById('scrape-dias').value);
        const zona = document.getElementById('scrape-zona').value;
        const adultos = parseInt(document.getElementById('scrape-adultos').value);
        const fechaDesde = new Date().toISOString().slice(0, 10);

        const eta = dias * 5;
        if (!confirm(`Vas a calcular precios para los próximos ${dias} días (zona: ${zona}, ${adultos} adultos).\n\nTiempo estimado: ${Math.ceil(eta/60)} minutos.\n\nPuedes cerrar la ventana y volver después.\n\n¿Continuar?`)) return;

        btnScrapeMulti.disabled = true;
        try {
            const r = await fetch(urlScrapeMulti, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({fecha_desde: fechaDesde, dias, zona, adultos}),
            });
            const data = await r.json();
            if (!r.ok) {
                alert('Error lanzando job: ' + (data.error || 'desconocido'));
                btnScrapeMulti.disabled = false;
                return;
            }
            activeJobId = data.job_id;
            localStorage.setItem('revenue_active_job', activeJobId);
            abrirModalProgreso(activeJobId, dias);
        } catch (e) {
            alert('Error de red: ' + e.message);
            btnScrapeMulti.disabled = false;
        }
    });

    function abrirModalProgreso(jobId, diasTotal) {
        document.getElementById('prog-job-id').textContent = `job ${jobId}`;
        const modal = new bootstrap.Modal(document.getElementById('modal-progreso'));
        modal.show();
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(() => actualizarProgreso(jobId, diasTotal), 2000);
        actualizarProgreso(jobId, diasTotal);
    }

    async function actualizarProgreso(jobId, diasTotal) {
        try {
            const r = await fetch(`${urlProgressBase}/${jobId}`);
            if (!r.ok) {
                document.getElementById('prog-detalle').textContent = 'Job expirado o no encontrado';
                clearInterval(pollTimer);
                return;
            }
            const s = await r.json();
            const total = s.total || diasTotal;
            const step = s.step || 0;
            const pct = total > 0 ? Math.round((step / total) * 100) : 0;
            const restante = Math.max((s.segundos_estimados || 0) - (s.segundos_transcurridos || 0), 0);

            document.getElementById('prog-bar').style.width = pct + '%';
            document.getElementById('prog-pct').textContent = pct + '%';
            document.getElementById('prog-eta').textContent = fmtTime(restante);
            document.getElementById('prog-step').textContent = `${step}/${total}`;
            document.getElementById('prog-aps').textContent = s.apartamentos_actualizados || 0;
            document.getElementById('prog-elapsed').textContent = (s.segundos_transcurridos || 0) + 's';

            const fasesTexto = {
                'arrancando':  '🚀 Arrancando proceso...',
                'iniciando':   '🚀 Preparando...',
                'scrapeando':  '🌐 Buscando precios competencia',
                'analizando':  '🧠 Analizando competencia',
                'guardando':   '💾 Guardando recomendaciones en BD',
                'completado':  '✅ ¡Listo!',
            };
            document.getElementById('prog-fase').textContent = fasesTexto[s.fase] || s.fase;
            document.getElementById('prog-detalle').textContent = s.fase_texto || '';

            if (s.completed) {
                clearInterval(pollTimer);
                localStorage.removeItem('revenue_active_job');
                document.getElementById('prog-bar').classList.remove('progress-bar-animated', 'bg-warning');
                document.getElementById('prog-bar').classList.add('bg-success');
                document.getElementById('prog-eta').textContent = '✓';
                document.getElementById('prog-eta').classList.remove('text-warning');
                document.getElementById('prog-eta').classList.add('text-success');
                document.querySelector('.modal-header').classList.remove('bg-warning', 'text-dark');
                document.querySelector('.modal-header').classList.add('bg-success', 'text-white');
                document.getElementById('btn-ver-resultado').style.display = '';
                btnScrapeMulti.disabled = false;
                if (s.errores && s.errores.length) {
                    document.getElementById('prog-detalle').innerHTML +=
                        `<br><small class="text-danger">⚠ ${s.errores.length} errores: ${s.errores[0]}</small>`;
                }
            }
        } catch (e) {
            console.error('poll fail', e);
        }
    }

    document.getElementById('btn-ver-resultado').addEventListener('click', () => {
        location.reload();
    });

    // Si hay un job activo guardado en localStorage, retomar polling
    if (activeJobId) {
        // Comprobar si sigue vivo
        fetch(`${urlProgressBase}/${activeJobId}`).then(r => r.json()).then(s => {
            if (s && !s.completed) {
                btnScrapeMulti.disabled = true;
                abrirModalProgreso(activeJobId, s.total || 7);
            } else {
                localStorage.removeItem('revenue_active_job');
            }
        }).catch(() => {
            localStorage.removeItem('revenue_active_job');
        });
    }
})();
</script>
@endsection
