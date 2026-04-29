@extends('layouts.appAdmin')

@section('title', 'Calcular Revenue · ' . $fecha->format('d/m/Y'))

@section('content')
<div class="container-fluid py-3">

    {{-- Cabecera --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-0">
                <i class="fas fa-chart-line text-warning me-1"></i>
                Calcular Revenue
                <small class="text-muted ms-2">{{ $fecha->isoFormat('dddd D MMMM YYYY') }}</small>
            </h1>
            <small class="text-muted">
                Compara precios con la competencia y aplica el recomendado a tus apartamentos libres.
            </small>
        </div>
        <div>
            <a href="{{ route('reservas.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver a reservas
            </a>
        </div>
    </div>

    {{-- Selector de fecha --}}
    <form method="GET" class="card card-body mb-3 p-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-0 small">Fecha noche</label>
                <input type="date" name="fecha" value="{{ $fecha->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary">Cargar</button>
            </div>
            <div class="col-md-7 text-end small">
                @if($es_finde)
                    <span class="badge bg-warning text-dark">Fin de semana (+10%)</span>
                @endif
                @if($es_festivo)
                    <span class="badge bg-danger">Festivo (+15%)</span>
                @endif
                <span class="badge bg-info">Ocupación nuestra: {{ $ocupacion_pct }}%</span>
            </div>
        </div>
    </form>

    {{-- KPIs --}}
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body p-2 text-center">
                    <div class="display-6 text-success">{{ $libres_count }}</div>
                    <small class="text-muted">Apartamentos LIBRES</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body p-2 text-center">
                    <div class="display-6 text-secondary">{{ $ocupados_count }}</div>
                    <small class="text-muted">Apartamentos OCUPADOS</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body p-2 text-center">
                    <div class="display-6 text-info" id="kpi-mediana">—</div>
                    <small class="text-muted">Mediana competencia</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body p-2 text-center">
                    <div class="display-6 text-warning" id="kpi-listings">—</div>
                    <small class="text-muted">Listings competencia</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Estado del scraper Python --}}
    <div class="card mb-3 border-{{ $scraper_health['alive'] ? 'success' : 'danger' }}">
        <div class="card-body p-2 d-flex justify-content-between align-items-center">
            <div>
                @if($scraper_health['alive'])
                    <i class="fas fa-check-circle text-success me-1"></i>
                    <strong>Scraper Python:</strong> activo en
                    <code>{{ env('REVENUE_SCRAPER_URL', 'http://127.0.0.1:8765') }}</code>
                    @if(isset($scraper_health['data']['zonas']))
                        <small class="text-muted ms-2">
                            zonas: {{ implode(', ', $scraper_health['data']['zonas']) }}
                        </small>
                    @endif
                @else
                    <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                    <strong>Scraper Python: NO RESPONDE</strong>
                    <small class="text-muted ms-2">{{ $scraper_health['error'] ?? '' }}</small>
                @endif
            </div>
            <div>
                <select id="zona-select" class="form-select form-select-sm d-inline-block" style="width: auto">
                    <option value="algeciras_centro">Algeciras centro</option>
                    <option value="algeciras_costa">Algeciras costa/playa</option>
                    <option value="bahia_completa">Bahía completa</option>
                </select>
                <select id="adultos-select" class="form-select form-select-sm d-inline-block" style="width: auto">
                    <option value="2" selected>2 adultos</option>
                    <option value="1">1 adulto</option>
                    <option value="3">3 adultos</option>
                    <option value="4">4 adultos</option>
                </select>
                <button id="btn-scrape" class="btn btn-warning btn-sm" {{ $scraper_health['alive'] ? '' : 'disabled' }}>
                    <i class="fas fa-sync-alt me-1"></i>Calcular precios competencia
                </button>
            </div>
        </div>
    </div>

    {{-- Errores y mensajes --}}
    <div id="msg-area"></div>

    {{-- Tabla apartamentos --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Apartamentos · noche {{ $fecha->format('d/m/Y') }} → {{ $checkout->format('d/m/Y') }}</span>
            <small class="text-muted" id="ultima-recomendacion">
                @if($recomendaciones->isNotEmpty())
                    Última recomendación calculada: {{ $recomendaciones->first()->calculado_at?->diffForHumans() }}
                @else
                    Pulsa "Calcular precios competencia" para empezar
                @endif
            </small>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px"></th>
                        <th>Apartamento</th>
                        <th>Estado</th>
                        <th class="text-end">Precio recomendado</th>
                        <th>Razonamiento</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody-apartamentos">
                    @foreach($situacion as $row)
                        @php
                            $apt = $row['apartamento'];
                            $rec = $recomendaciones->get($apt->id);
                        @endphp
                        <tr data-apt-id="{{ $apt->id }}" data-libre="{{ $row['libre'] ? '1' : '0' }}"
                            class="{{ $row['libre'] ? '' : 'table-secondary' }}">
                            <td>
                                @if($row['libre'])
                                    <input type="checkbox" class="form-check-input apt-check"
                                           value="{{ $apt->id }}" checked>
                                @else
                                    <i class="fas fa-lock text-muted" title="Ocupado"></i>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $apt->nombre }}</strong>
                                <br>
                                <small class="text-muted">
                                    Min: {{ $apt->revenue_min_precio ? number_format($apt->revenue_min_precio, 0).'€' : '—' }} ·
                                    Max: {{ $apt->revenue_max_precio ? number_format($apt->revenue_max_precio, 0).'€' : '—' }} ·
                                    {{ ucfirst($apt->revenue_factor_segmento ?? 'match') }}
                                </small>
                            </td>
                            <td>
                                @if($row['libre'])
                                    <span class="badge bg-success">LIBRE</span>
                                @else
                                    <span class="badge bg-secondary">OCUPADO</span>
                                    @if($row['reserva']?->cliente)
                                        <br><small>{{ $row['reserva']->cliente->nombre ?? '?' }} · sale {{ \Carbon\Carbon::parse($row['reserva']->fecha_salida)->format('d/m') }}</small>
                                    @endif
                                @endif
                            </td>
                            <td class="text-end fw-bold precio-cell" id="precio-{{ $apt->id }}">
                                @if($rec && $rec->precio_recomendado)
                                    {{ number_format($rec->precio_recomendado, 0) }}€
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="razonamiento-cell small text-muted" id="razon-{{ $apt->id }}">
                                {{ $rec?->razonamiento ?? '' }}
                            </td>
                            <td>
                                @if($apt->revenue_rate_plan_id)
                                    <span class="badge bg-success" title="Channex configurado">
                                        <i class="fas fa-check"></i>
                                    </span>
                                @else
                                    <a href="{{ route('revenue.configurar', $apt->id) }}"
                                       class="badge bg-warning text-dark" title="Falta rate_plan_id Channex">
                                        ⚠ Configurar
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Footer fijo con acciones --}}
    <div class="card mt-3" style="position: sticky; bottom: 0; z-index: 10">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <span id="contador">{{ $libres_count }}</span> apartamentos libres seleccionados
            </div>
            <div class="d-flex gap-2">
                <button id="btn-todos-libres" class="btn btn-outline-secondary btn-sm">
                    Marcar todos los libres
                </button>
                <button id="btn-ninguno" class="btn btn-outline-secondary btn-sm">
                    Desmarcar todos
                </button>
                <button id="btn-aplicar" class="btn btn-success" disabled>
                    <i class="fas fa-rocket me-1"></i>
                    Aplicar precios a Channex
                </button>
            </div>
        </div>
    </div>

    {{-- Listings competencia (top) --}}
    <div class="card mt-3" id="listings-card" style="display: none">
        <div class="card-header">Listings competencia (top 20 más baratos)</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Plataforma</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th class="text-end">Precio</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody id="tbody-listings"></tbody>
            </table>
        </div>
    </div>

</div>

<script>
(function () {
    const fecha = '{{ $fecha->toDateString() }}';
    const csrf = '{{ csrf_token() }}';
    const urlScrape = '{{ route("revenue.scrape") }}';
    const urlAplicar = '{{ route("revenue.aplicarLibresHoy") }}';

    const btnScrape = document.getElementById('btn-scrape');
    const btnAplicar = document.getElementById('btn-aplicar');
    const btnTodos = document.getElementById('btn-todos-libres');
    const btnNinguno = document.getElementById('btn-ninguno');
    const msgArea = document.getElementById('msg-area');
    const tbody = document.getElementById('tbody-apartamentos');
    const contador = document.getElementById('contador');

    function showMsg(html, tipo = 'info') {
        msgArea.innerHTML = `<div class="alert alert-${tipo} alert-dismissible">${html}<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
    }

    function actualizarContador() {
        const sel = document.querySelectorAll('.apt-check:checked');
        contador.textContent = sel.length;
        // Solo permitimos aplicar si hay precios calculados
        const conPrecio = Array.from(sel).filter(c => {
            const cell = document.getElementById('precio-' + c.value);
            return cell && cell.textContent.trim().match(/^\d/);
        });
        btnAplicar.disabled = conPrecio.length === 0;
    }
    document.querySelectorAll('.apt-check').forEach(c => c.addEventListener('change', actualizarContador));
    btnTodos.addEventListener('click', () => {
        document.querySelectorAll('.apt-check').forEach(c => c.checked = true);
        actualizarContador();
    });
    btnNinguno.addEventListener('click', () => {
        document.querySelectorAll('.apt-check').forEach(c => c.checked = false);
        actualizarContador();
    });

    // === SCRAPE ===
    btnScrape.addEventListener('click', async () => {
        const zona = document.getElementById('zona-select').value;
        const adultos = parseInt(document.getElementById('adultos-select').value);
        btnScrape.disabled = true;
        btnScrape.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Scrapeando Booking + Airbnb (~30-60s)...';
        showMsg('Lanzando scrapers Python (Airbnb + Booking)...');

        try {
            const r = await fetch(urlScrape, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ fecha, zona, adultos, use_cache: true }),
            });
            const data = await r.json();
            if (!r.ok) {
                showMsg(`<strong>Error:</strong> ${data.error || 'desconocido'}<br><small>${data.hint || ''}</small>`, 'danger');
                return;
            }

            // Actualizar KPIs
            document.getElementById('kpi-mediana').textContent = (data.mercado.mediana ?? '—') + (data.mercado.mediana ? '€' : '');
            document.getElementById('kpi-listings').textContent = data.mercado.n ?? '—';

            // Actualizar tabla apartamentos
            data.apartamentos.forEach(a => {
                const cellPrecio = document.getElementById('precio-' + a.apartamento_id);
                const cellRazon = document.getElementById('razon-' + a.apartamento_id);
                if (cellPrecio) {
                    cellPrecio.innerHTML = a.precio_recomendado
                        ? `<span class="text-success">${a.precio_recomendado}€</span>`
                        : '<span class="text-muted">—</span>';
                }
                if (cellRazon) cellRazon.textContent = a.razonamiento || '';
            });

            // Listings top
            const tbodyL = document.getElementById('tbody-listings');
            tbodyL.innerHTML = '';
            (data.listings_top || []).forEach(l => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><span class="badge bg-${l.plataforma === 'booking' ? 'primary' : 'danger'}">${l.plataforma}</span></td>
                    <td>${l.titulo || ''}</td>
                    <td><small>${l.tipo || ''}</small></td>
                    <td class="text-end fw-bold">${l.precio?.toFixed(0) || '—'}€</td>
                    <td>${l.rating ? l.rating.toFixed(1) : '—'}</td>
                `;
                tbodyL.appendChild(tr);
            });
            document.getElementById('listings-card').style.display = '';

            const cacheMsg = data.cached
                ? `<i class="fas fa-clock"></i> Datos en caché (${data.cache_age_minutes?.toFixed(0)} min)`
                : '<i class="fas fa-bolt"></i> Datos en vivo';
            showMsg(`<strong>Scrape OK</strong> · ${data.mercado.n} listings · mediana ${data.mercado.mediana}€ · media ${data.mercado.media}€ · ${cacheMsg}`, 'success');

            actualizarContador();
        } catch (e) {
            showMsg(`<strong>Error de red:</strong> ${e.message}`, 'danger');
        } finally {
            btnScrape.disabled = false;
            btnScrape.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Calcular precios competencia';
        }
    });

    // === APLICAR ===
    btnAplicar.addEventListener('click', async () => {
        const ids = Array.from(document.querySelectorAll('.apt-check:checked')).map(c => parseInt(c.value));
        if (!ids.length) return;

        if (!confirm(`¿Aplicar precio recomendado a ${ids.length} apartamento(s) en Channex?\n\nEsto MODIFICA los precios reales en Booking, Airbnb y tu web.`)) return;

        btnAplicar.disabled = true;
        btnAplicar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Aplicando...';

        try {
            const r = await fetch(urlAplicar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ fecha, apartamento_ids: ids }),
            });
            const data = await r.json();
            if (!r.ok) {
                showMsg(`<strong>Error:</strong> ${data.error || 'desconocido'}`, 'danger');
                return;
            }
            let html = `<strong>Aplicados:</strong> ${data.ok}/${data.total}`;
            if (data.errores && data.errores.length) {
                html += '<ul class="mb-0 mt-1">' + data.errores.map(e => `<li><small>${e}</small></li>`).join('') + '</ul>';
            }
            showMsg(html, data.ok > 0 ? 'success' : 'warning');
        } catch (e) {
            showMsg(`<strong>Error de red:</strong> ${e.message}`, 'danger');
        } finally {
            btnAplicar.innerHTML = '<i class="fas fa-rocket me-1"></i>Aplicar precios a Channex';
            actualizarContador();
        }
    });

    actualizarContador();
})();
</script>
@endsection
