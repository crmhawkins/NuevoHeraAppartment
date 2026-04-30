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
                <span class="badge bg-info" data-bs-toggle="tooltip"
                    title="% de tus apartamentos ocupados (con reserva activa) en esa fecha. Hoy {{ collect($situacion)->where('libre', false)->count() }} de {{ count($situacion) }} = {{ $ocupacion_pct }}%. El sistema baja precios automáticamente si está <30% y faltan <14 días.">
                    Ocupación nuestra: {{ $ocupacion_pct }}%
                </span>
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

    {{-- BLOQUE DE ESTRATEGIAS — siempre visible, se rellena al cargar --}}
    <div class="card mb-3" id="card-estrategias" style="display: none">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="fas fa-balance-scale me-1"></i>
                <strong>¿Qué precio aplico?</strong>
                Compara estrategias y elige la que más te convenza.
            </span>
            <div class="d-flex gap-2 align-items-center">
                <small class="text-muted" id="estrategias-stats"></small>
                <button id="btn-ver-fuentes" class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="collapse" data-bs-target="#fuentes-panel">
                    <i class="fas fa-list me-1"></i>Ver fuentes
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3" id="estrategias-tarjetas">
                {{-- 4 tarjetas se rellenan por JS --}}
            </div>

            {{-- Panel colapsable con TODOS los listings competencia --}}
            <div class="collapse mt-3" id="fuentes-panel">
                <div class="card border-secondary">
                    <div class="card-header bg-light">
                        <strong>Fuentes — listings reales scrapeados HOY</strong>
                        <small class="text-muted ms-2">(esto es la base de cálculo de las estrategias)</small>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto">
                        <table class="table table-sm table-striped mb-0" id="tabla-fuentes">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Plataforma</th>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th class="text-end">Precio/noche</th>
                                    <th>Rating</th>
                                    <th>Ver</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-fuentes"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="alert alert-light mt-3 mb-0 small" style="border-left: 4px solid #0d6efd">
                <strong>💡 Cómo funciona esto:</strong>
                <ol class="mb-0 mt-2">
                    <li><strong>1. Eliges una estrategia</strong> de las 4 tarjetas pulsando "Pre-rellenar tabla".</li>
                    <li><strong>2. La tabla de abajo</strong> se rellena con esos precios. Puedes desmarcar apartamentos individuales.</li>
                    <li><strong>3. Pulsas "Aplicar precios a Channex"</strong> abajo para empujarlos de verdad.</li>
                    <li>Hasta el paso 3 NO se modifica nada en producción. Todo es preview.</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- Tabla apartamentos --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Apartamentos · noche {{ $fecha->format('d/m/Y') }} → {{ $checkout->format('d/m/Y') }}</span>
            <div class="d-flex gap-2 align-items-center">
                <small class="text-muted me-2" id="ultima-recomendacion">
                    @if($recomendaciones->isNotEmpty())
                        Última calculada: {{ $recomendaciones->first()->calculado_at?->diffForHumans() }}
                    @endif
                </small>
                <label class="small text-muted mb-0 me-1">Ver:</label>
                <select id="filtro-vista" class="form-select form-select-sm" style="width: auto">
                    <option value="todos">Todos ({{ $libres_count + $ocupados_count }})</option>
                    <option value="libres" selected>Solo disponibles ({{ $libres_count }})</option>
                    <option value="ocupados">Solo ocupados ({{ $ocupados_count }})</option>
                </select>
            </div>
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

    // === ESTRATEGIAS — carga automatica al abrir la pagina ===
    const urlEstrategias = '{{ route("revenue.estrategias") }}';
    const cardEstrategias = document.getElementById('card-estrategias');
    const tarjetasContainer = document.getElementById('estrategias-tarjetas');
    const statsLabel = document.getElementById('estrategias-stats');

    async function cargarEstrategias() {
        const zona = document.getElementById('zona-select').value;
        const adultos = parseInt(document.getElementById('adultos-select').value);
        cardEstrategias.style.display = '';
        tarjetasContainer.innerHTML = `<div class="col-12 text-center py-4"><div class="spinner-border text-primary"></div><p class="text-muted mt-2 mb-0">Cargando estrategias con datos en cache...</p></div>`;

        try {
            const r = await fetch(urlEstrategias, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({fecha, zona, adultos})
            });
            const data = await r.json();
            if (!r.ok) {
                tarjetasContainer.innerHTML = `<div class="col-12"><div class="alert alert-warning mb-0"><strong>Sin datos todavía.</strong> Pulsa "Calcular precios competencia" arriba primero. ${data.error || ''}</div></div>`;
                return;
            }
            renderTarjetas(data);
        } catch (e) {
            tarjetasContainer.innerHTML = `<div class="col-12"><div class="alert alert-danger mb-0">Error: ${e.message}</div></div>`;
        }
    }

    function renderTarjetas(data) {
        const stats = data.estadisticas;
        const cacheLabel = data.cached
            ? `<span class="badge bg-secondary ms-1" title="Datos en caché. Pulsa 'Calcular precios competencia' para refrescar.">caché ${data.cache_age_minutes?.toFixed(0)}min</span>`
            : '<span class="badge bg-success ms-1">en vivo</span>';
        statsLabel.innerHTML = `Tu competencia real: <strong>${stats.premium_only.n} apartamentos</strong> · mediana <strong class="text-success">${stats.premium_only.mediana?.toFixed(0)}€</strong>${cacheLabel}`;

        // Pintar fuentes (listings)
        const tbodyFuentes = document.getElementById('tbody-fuentes');
        if (tbodyFuentes && data.listings) {
            tbodyFuentes.innerHTML = data.listings.map((l, i) => {
                const platBadge = l.plataforma === 'booking'
                    ? '<span class="badge bg-primary">Booking</span>'
                    : '<span class="badge bg-danger">Airbnb</span>';
                const tipo = (l.tipo || '').toLowerCase();
                const esEntero = tipo.includes('apartamento') || tipo.includes('casa') ||
                                 tipo.includes('estudio') || tipo.includes('vivienda') ||
                                 tipo.includes('entire');
                const enteroIcon = esEntero
                    ? '<i class="fas fa-star text-success" title="Apartamento entero (compite contigo)"></i>'
                    : '<i class="fas fa-bed text-muted" title="Hotel/Hostal/Habitación"></i>';
                const verLink = l.url
                    ? `<a href="${l.url}" target="_blank" class="btn btn-sm btn-outline-secondary py-0"><i class="fas fa-external-link-alt"></i></a>`
                    : '';
                return `<tr>
                    <td class="text-muted">${i+1}</td>
                    <td>${platBadge}</td>
                    <td>${enteroIcon} <small>${(l.tipo||'?').slice(0,25)}</small></td>
                    <td><small>${(l.titulo||'?').slice(0,55)}</small></td>
                    <td class="text-end fw-bold">${l.precio.toFixed(0)}€</td>
                    <td>${l.rating ? `<small>${l.rating.toFixed(1)}⭐</small>` : '-'}</td>
                    <td>${verLink}</td>
                </tr>`;
            }).join('');
        }

        const colors = {
            red:    {border:'border-danger',    badge:'bg-danger',    btn:'btn-outline-danger'},
            orange: {border:'border-warning',   badge:'bg-warning text-dark', btn:'btn-outline-warning'},
            green:  {border:'border-success',   badge:'bg-success',   btn:'btn-success'},
            blue:   {border:'border-primary',   badge:'bg-primary',   btn:'btn-primary'},
        };
        const etiquetas = {
            red:    'LO QUE TIENES AHORA',
            orange: 'OPCIÓN INTERMEDIA',
            green:  'RECOMENDADA',
            blue:   'RECOMENDADA + AJUSTE FINDES',
        };

        const totalA = data.estrategias[0].mes_70pct;
        let html = '';
        data.estrategias.forEach((e, idx) => {
            const c = colors[e.color] || colors.blue;
            const diff = e.mes_70pct - totalA;
            const diffStr = idx === 0 ? 'punto de partida' : (diff > 0 ? `+${diff.toLocaleString()}€/mes` : `${diff.toLocaleString()}€/mes`);
            const diffClass = idx === 0 ? 'text-muted' : (diff > 0 ? 'text-success fw-bold' : 'text-danger');
            const letra = e.nombre.charAt(0);

            // Lista precios apartamentos
            let preciosHtml = '<div class="small mt-2">';
            Object.values(e.precios).forEach(p => {
                if (p.libre) {
                    preciosHtml += `<div class="d-flex justify-content-between"><span>${p.apartamento}:</span> <strong>${p.precio}€</strong></div>`;
                } else {
                    preciosHtml += `<div class="d-flex justify-content-between text-muted"><span>${p.apartamento}:</span> <span>${p.precio}€ (ocupado)</span></div>`;
                }
            });
            preciosHtml += '</div>';

            html += `
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 ${c.border}" style="border-width: 2px">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge ${c.badge}">${etiquetas[e.color]}</span>
                            <span class="text-muted small">${letra}</span>
                        </div>
                        <h6 class="card-title">${e.nombre.replace(/^[A-D]\.\s*/, '')}</h6>
                        <div class="my-3 text-center">
                            <div class="display-6 fw-bold">${e.mes_70pct.toLocaleString()}€</div>
                            <small class="text-muted">por mes (al 70% ocupación)</small>
                            <div class="${diffClass} small mt-1">${diffStr}</div>
                        </div>
                        ${preciosHtml}
                        <p class="small text-muted mt-2 mb-3 flex-grow-1">${e.descripcion}</p>
                        <button class="btn btn-sm ${c.btn} aplicar-estrategia"
                                data-estrategia="${e.color}"
                                data-precios='${JSON.stringify(e.precios).replaceAll("'", "&apos;")}'
                                title="Rellena la tabla de abajo con estos precios. NO los aplica a Channex aún — para eso pulsa después el botón verde 'Aplicar precios a Channex'.">
                            <i class="fas fa-arrow-down me-1"></i>Pre-rellenar tabla con esta estrategia
                        </button>
                    </div>
                </div>
            </div>`;
        });
        tarjetasContainer.innerHTML = html;

        // Listener "Usar esta estrategia" — pre-rellena la tabla de abajo
        document.querySelectorAll('.aplicar-estrategia').forEach(btn => {
            btn.addEventListener('click', e => {
                const precios = JSON.parse(btn.dataset.precios.replaceAll("&apos;", "'"));
                Object.entries(precios).forEach(([aptId, p]) => {
                    if (!p.libre) return;
                    const cellPrecio = document.getElementById('precio-' + aptId);
                    const check = document.querySelector('.cambio-check[data-apt="' + aptId + '"]');
                    if (cellPrecio) {
                        cellPrecio.innerHTML = `<span class="text-success fw-bold">${p.precio}€</span>`;
                    }
                    if (check) {
                        check.dataset.precio = p.precio;
                        check.checked = true;
                    }
                });
                actualizarContador();
                showMsg(`<strong>Estrategia "${btn.closest('.card').querySelector('.card-title').textContent.trim()}"</strong> aplicada a la tabla. Marca/desmarca apartamentos abajo y pulsa "Aplicar precios a Channex" para empujarlos.`, 'success');
                document.getElementById('btn-aplicar').scrollIntoView({behavior: 'smooth', block: 'center'});
            });
        });
    }

    // Cargar al abrir la pagina (si hay datos previos en cache, sale rapido)
    cargarEstrategias();

    // Recargar estrategias cuando cambia zona o adultos
    document.getElementById('zona-select').addEventListener('change', cargarEstrategias);
    document.getElementById('adultos-select').addEventListener('change', cargarEstrategias);

    // === FILTRO Ver: todos / libres / ocupados ===
    const filtroVista = document.getElementById('filtro-vista');
    function aplicarFiltroVista() {
        const v = filtroVista.value;
        document.querySelectorAll('tr[data-libre]').forEach(tr => {
            const libre = tr.dataset.libre === '1';
            const mostrar = v === 'todos' || (v === 'libres' && libre) || (v === 'ocupados' && !libre);
            tr.style.display = mostrar ? '' : 'none';
        });
        // Actualizar contador de seleccionados (solo cuenta los visibles+marcados)
        actualizarContador();
    }
    filtroVista.addEventListener('change', aplicarFiltroVista);
    aplicarFiltroVista();  // aplicar el filtro default ("libres")
})();
</script>
@endsection
