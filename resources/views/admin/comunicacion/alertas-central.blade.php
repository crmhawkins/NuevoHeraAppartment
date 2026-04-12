@extends('layouts.appAdmin')

@section('title', 'Centro de Comunicaciones')

@section('scriptHead')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endsection

@section('content')
<style>
    .df-card { padding: 12px 16px; margin-bottom: 8px; }
    .df-card h6 { font-size: 11px; color: #6b7280; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
    .df-card .val { font-size: 20px; font-weight: 700; margin: 0; }
    .df-table { font-size: 12px; }
    .df-table th, .df-table td { padding: 6px 8px !important; vertical-align: middle; }
    .df-table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
    .df-badge { font-size: 10px; padding: 2px 6px; }
    .df-btn-sm { font-size: 11px; padding: 2px 8px; }
    .canal-icon { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 4px; font-size: 12px; margin-right: 2px; }
    .canal-whatsapp { background: #dcfce7; color: #16a34a; }
    .canal-email { background: #dbeafe; color: #2563eb; }
    .canal-crm { background: #e0f2fe; color: #0891b2; }
    .servicio-code { font-size: 10px; font-family: monospace; color: #6b7280; background: #f3f4f6; padding: 1px 4px; border-radius: 3px; }
    .historial-estado { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .historial-estado.leida { background: #d1d5db; }
    .historial-estado.no_leida { background: #0891b2; }
    .cc-tabs .nav-link { font-size: 12px; padding: 8px 16px; color: #6b7280; border: none; border-bottom: 2px solid transparent; }
    .cc-tabs .nav-link.active { font-weight: 700; color: #0891b2; border-bottom-color: #0891b2; background: transparent; }
    .cc-tabs .nav-link:hover:not(.active) { color: #374151; border-bottom-color: #d1d5db; }
    .clickable-row { cursor: pointer; }
    .clickable-row:hover { background: #f0fdfa !important; }
    .filter-bar { background: #f9fafb; border-radius: 6px; padding: 8px 12px; margin-bottom: 10px; }
    .filter-bar .form-control, .filter-bar .form-select { font-size: 11px; padding: 4px 8px; }
    .template-component { background: #f3f4f6; border-radius: 6px; padding: 8px 12px; margin-bottom: 6px; font-size: 12px; }
    .template-component .comp-type { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 2px; }
    .loading-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #e5e7eb; border-top: 2px solid #0891b2; border-radius: 50%; animation: spin 0.6s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .tab-pane { min-height: 200px; }
    .pagination-cc { display: flex; justify-content: center; gap: 4px; margin-top: 8px; }
    .pagination-cc button { font-size: 11px; padding: 2px 8px; }
</style>

<div class="container-fluid py-2">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="fw-bold mb-0" style="font-size: 16px;">
            <i class="bi bi-broadcast me-1" style="color: #0891b2;"></i>Centro de Comunicaciones
        </h5>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-md-2">
            <div class="card shadow-sm border-0 df-card">
                <h6>Total Hoy</h6>
                <p class="val" style="color: #0891b2;">{{ $totalHoy }}</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 df-card">
                <h6>Alertas CRM</h6>
                <p class="val" style="color: #f59e0b;">{{ $alertasHoy }}</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 df-card">
                <h6>Notificaciones</h6>
                <p class="val" style="color: #8b5cf6;">{{ $notificacionesHoy }}</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 df-card">
                <h6>Tipos Internos</h6>
                <p class="val" style="color: #0891b2;">{{ $internas }}</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 df-card">
                <h6>Tipos Externos</h6>
                <p class="val" style="color: #10b981;">{{ $externas }}</p>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm border-0 df-card">
                <h6>Canales</h6>
                <p class="val" style="font-size: 13px;">
                    @foreach($canalesCount as $canal => $count)
                        <span class="canal-icon canal-{{ $canal }}">
                            @if($canal === 'whatsapp')<i class="bi bi-whatsapp"></i>
                            @elseif($canal === 'email')<i class="bi bi-envelope"></i>
                            @else<i class="bi bi-display"></i>
                            @endif
                        </span>{{ $count }}
                        @if(!$loop->last) &nbsp; @endif
                    @endforeach
                </p>
            </div>
        </div>
    </div>

    {{-- Main Tabs --}}
    <div class="card shadow-sm border-0">
        <div class="card-body py-2 px-3">
            <ul class="nav cc-tabs mb-2" id="ccTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAlertas" type="button" role="tab">
                        <i class="bi bi-shield-exclamation me-1"></i>Alertas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHistorial" type="button" role="tab" id="btnTabHistorial">
                        <i class="bi bi-clock-history me-1"></i>Historial
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPlantillas" type="button" role="tab" id="btnTabPlantillas">
                        <i class="bi bi-whatsapp me-1"></i>Plantillas WhatsApp
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabOTA" type="button" role="tab" id="btnTabOTA">
                        <i class="bi bi-chat-dots me-1"></i>Mensajes OTA
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEmails" type="button" role="tab" id="btnTabEmails">
                        <i class="bi bi-envelope me-1"></i>Emails
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="ccTabsContent">

                {{-- TAB 1: Alertas (catalog) --}}
                <div class="tab-pane fade show active" id="tabAlertas" role="tabpanel">
                    <ul class="nav nav-tabs mb-2" role="tablist" style="font-size:12px;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#internas" type="button" role="tab" style="font-size:12px;padding:6px 14px;">
                                <i class="bi bi-shield-lock me-1"></i>Internas ({{ $internas }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#externas" type="button" role="tab" style="font-size:12px;padding:6px 14px;">
                                <i class="bi bi-people me-1"></i>Externas ({{ $externas }})
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="internas" role="tabpanel">
                            <table class="table table-hover mb-0 df-table">
                                <thead>
                                    <tr>
                                        <th>Alerta</th>
                                        <th>Descripcion</th>
                                        <th>Trigger</th>
                                        <th>Destinatarios</th>
                                        <th>Canales</th>
                                        <th>Servicio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(collect($alertTypes)->where('grupo', 'Internas') as $alert)
                                    <tr>
                                        <td class="fw-semibold">{{ $alert['nombre'] }}</td>
                                        <td>{{ $alert['descripcion'] }}</td>
                                        <td><span class="badge bg-light text-dark df-badge">{{ $alert['trigger'] }}</span></td>
                                        <td>{{ $alert['destinatarios'] }}</td>
                                        <td>
                                            @foreach($alert['canales'] as $canal)
                                                <span class="canal-icon canal-{{ $canal }}" title="{{ ucfirst($canal) }}">
                                                    @if($canal === 'whatsapp')<i class="bi bi-whatsapp"></i>
                                                    @elseif($canal === 'email')<i class="bi bi-envelope"></i>
                                                    @else<i class="bi bi-display"></i>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </td>
                                        <td><span class="servicio-code">{{ $alert['servicio'] }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="externas" role="tabpanel">
                            <table class="table table-hover mb-0 df-table">
                                <thead>
                                    <tr>
                                        <th>Alerta</th>
                                        <th>Descripcion</th>
                                        <th>Trigger</th>
                                        <th>Destinatarios</th>
                                        <th>Canales</th>
                                        <th>Servicio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(collect($alertTypes)->where('grupo', 'Externas') as $alert)
                                    <tr>
                                        <td class="fw-semibold">{{ $alert['nombre'] }}</td>
                                        <td>{{ $alert['descripcion'] }}</td>
                                        <td><span class="badge bg-light text-dark df-badge">{{ $alert['trigger'] }}</span></td>
                                        <td>{{ $alert['destinatarios'] }}</td>
                                        <td>
                                            @foreach($alert['canales'] as $canal)
                                                <span class="canal-icon canal-{{ $canal }}" title="{{ ucfirst($canal) }}">
                                                    @if($canal === 'whatsapp')<i class="bi bi-whatsapp"></i>
                                                    @elseif($canal === 'email')<i class="bi bi-envelope"></i>
                                                    @else<i class="bi bi-display"></i>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </td>
                                        <td><span class="servicio-code">{{ $alert['servicio'] }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- TAB 2: Historial --}}
                <div class="tab-pane fade" id="tabHistorial" role="tabpanel">
                    <div class="filter-bar d-flex flex-wrap align-items-center gap-2">
                        <label class="mb-0" style="font-size:11px;font-weight:600;">Filtros:</label>
                        <input type="date" id="histDesde" class="form-control" style="width:130px;" placeholder="Desde">
                        <input type="date" id="histHasta" class="form-control" style="width:130px;" placeholder="Hasta">
                        <select id="histTipo" class="form-select" style="width:140px;">
                            <option value="todos">Todos</option>
                            <option value="alertas">Alertas CRM</option>
                            <option value="notificaciones">Notificaciones</option>
                        </select>
                        <select id="histLeidas" class="form-select" style="width:130px;">
                            <option value="todas">Todas</option>
                            <option value="leidas">Leidas</option>
                            <option value="no_leidas">No leidas</option>
                        </select>
                        <button id="histBuscar" class="btn df-btn-sm" style="background:#0891b2;color:#fff;">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <button id="histRefresh" class="btn df-btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <table class="table table-hover mb-0 df-table" id="historialTable">
                        <thead>
                            <tr>
                                <th style="width:30px;"></th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Titulo</th>
                                <th>Destinatario</th>
                                <th>Canal</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="historialBody">
                            <tr><td colspan="7" class="text-center text-muted" style="font-size:12px;">Cargando...</td></tr>
                        </tbody>
                    </table>
                    <div id="historialPagination" class="pagination-cc"></div>
                </div>

                {{-- TAB 3: Plantillas WhatsApp --}}
                <div class="tab-pane fade" id="tabPlantillas" role="tabpanel">
                    <table class="table table-hover mb-0 df-table" id="plantillasTable">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Idioma</th>
                                <th>Estado</th>
                                <th>Categoria</th>
                            </tr>
                        </thead>
                        <tbody id="plantillasBody">
                            <tr><td colspan="4" class="text-center text-muted" style="font-size:12px;">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- TAB 4: Mensajes OTA --}}
                <div class="tab-pane fade" id="tabOTA" role="tabpanel">
                    <table class="table table-hover mb-0 df-table" id="otaTable">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Remitente</th>
                                <th>Mensaje</th>
                                <th>Respuesta IA</th>
                            </tr>
                        </thead>
                        <tbody id="otaBody">
                            <tr><td colspan="4" class="text-center text-muted" style="font-size:12px;">Cargando...</td></tr>
                        </tbody>
                    </table>
                    <div id="otaPagination" class="pagination-cc"></div>
                </div>

                {{-- TAB 5: Emails --}}
                <div class="tab-pane fade" id="tabEmails" role="tabpanel">
                    <table class="table table-hover mb-0 df-table" id="emailsTable">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Remitente</th>
                                <th>Asunto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="emailsBody">
                            <tr><td colspan="4" class="text-center text-muted" style="font-size:12px;">Cargando...</td></tr>
                        </tbody>
                    </table>
                    <div id="emailsPagination" class="pagination-cc"></div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Reusable Detail Modal --}}
<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#0891b2;color:#fff;">
                <h6 class="modal-title mb-0" id="detalleModalTitle" style="font-size:14px;"></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleModalBody" style="font-size:13px;"></div>
            <div class="modal-footer py-1">
                <a href="#" id="detalleModalLink" class="btn btn-sm" style="background:#0891b2;color:#fff;display:none;" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> Ir al contexto
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var detalleModal = new bootstrap.Modal(document.getElementById('detalleModal'));
    var badgeColors = {
        info:'bg-info', warning:'bg-warning text-dark', error:'bg-danger', success:'bg-success',
        reserva:'bg-primary', incidencia:'bg-danger', limpieza:'bg-success',
        facturacion:'bg-warning text-dark', inventario:'bg-secondary', sistema:'bg-dark',
        whatsapp:'bg-success', channex:'bg-primary'
    };
    var canalIcons = {
        whatsapp: '<i class="bi bi-whatsapp"></i>',
        email: '<i class="bi bi-envelope"></i>',
        crm: '<i class="bi bi-display"></i>'
    };
    var statusBadgeColors = {
        APPROVED: 'bg-success', PENDING: 'bg-warning text-dark', REJECTED: 'bg-danger'
    };

    function esc(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '...' : str;
    }

    function showModal(title, bodyHtml, linkUrl) {
        document.getElementById('detalleModalTitle').textContent = title;
        document.getElementById('detalleModalBody').innerHTML = bodyHtml;
        var linkEl = document.getElementById('detalleModalLink');
        if (linkUrl) {
            linkEl.href = linkUrl;
            linkEl.style.display = '';
        } else {
            linkEl.style.display = 'none';
        }
        detalleModal.show();
    }

    function renderPaginationGeneric(container, data, loadFn) {
        if (!data.last_page || data.last_page <= 1) { container.innerHTML = ''; return; }
        var html = '';
        for (var i = 1; i <= data.last_page; i++) {
            var cls = i === data.current_page ? 'btn-sm btn-primary' : 'btn-sm btn-outline-secondary';
            html += '<button class="btn ' + cls + '" data-page="' + i + '" style="font-size:11px;">' + i + '</button>';
        }
        container.innerHTML = html;
        container.querySelectorAll('button').forEach(function(btn) {
            btn.addEventListener('click', function() { loadFn(parseInt(this.dataset.page)); });
        });
    }

    // ========== TAB 2: HISTORIAL ==========
    var histPage = 1;
    var histLoaded = false;

    function loadHistorial(page) {
        histPage = page || 1;
        var params = new URLSearchParams({
            tipo: document.getElementById('histTipo').value,
            leidas: document.getElementById('histLeidas').value,
            page: histPage
        });
        var desde = document.getElementById('histDesde').value;
        var hasta = document.getElementById('histHasta').value;
        if (desde) params.set('desde', desde);
        if (hasta) params.set('hasta', hasta);

        var body = document.getElementById('historialBody');
        body.innerHTML = '<tr><td colspan="7" class="text-center"><span class="loading-spinner"></span></td></tr>';

        fetch('{{ route("alertas.historial") }}?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.data || !data.data.length) {
                    body.innerHTML = '<tr><td colspan="7" class="text-center text-muted" style="font-size:12px;">Sin resultados</td></tr>';
                    document.getElementById('historialPagination').innerHTML = '';
                    return;
                }
                body.innerHTML = data.data.map(function(item) {
                    var bc = badgeColors[item.tipo] || 'bg-secondary';
                    var ci = canalIcons[item.canal] || canalIcons.crm;
                    var titulo = item.titulo ? truncate(item.titulo, 60) : '';
                    var estadoBadge = item.estado === 'leida'
                        ? '<span class="badge bg-light text-muted df-badge">Leida</span>'
                        : '<span class="badge bg-info df-badge">No leida</span>';
                    return '<tr class="clickable-row" data-id="' + item.id + '" data-origen="' + esc(item.origen) + '" data-url="' + esc(item.action_url || '') + '">' +
                        '<td><span class="historial-estado ' + item.estado + '"></span></td>' +
                        '<td>' + esc(item.fecha) + '</td>' +
                        '<td><span class="badge ' + bc + ' df-badge">' + esc(item.tipo.charAt(0).toUpperCase() + item.tipo.slice(1)) + '</span></td>' +
                        '<td class="fw-semibold" style="color:#0891b2;">' + esc(titulo) + '</td>' +
                        '<td>' + esc(item.destinatario) + '</td>' +
                        '<td><span class="canal-icon canal-' + item.canal + '">' + ci + '</span></td>' +
                        '<td>' + estadoBadge + '</td>' +
                        '</tr>';
                }).join('');

                // Click handler for rows
                body.querySelectorAll('.clickable-row').forEach(function(row) {
                    row.addEventListener('click', function() {
                        var id = this.dataset.id;
                        var origen = this.dataset.origen;
                        var actionUrl = this.dataset.url;
                        var tipo = origen === 'alert' ? 'alert' : 'notification';
                        loadDetalle(tipo, id, actionUrl);
                    });
                });

                // Pagination
                var pag = document.getElementById('historialPagination');
                if (data.last_page > 1) {
                    var html = '';
                    for (var i = 1; i <= data.last_page; i++) {
                        var cls = i === data.page ? 'btn-sm btn-primary' : 'btn-sm btn-outline-secondary';
                        html += '<button class="btn ' + cls + '" data-page="' + i + '" style="font-size:11px;">' + i + '</button>';
                    }
                    pag.innerHTML = html;
                    pag.querySelectorAll('button').forEach(function(btn) {
                        btn.addEventListener('click', function() { loadHistorial(parseInt(this.dataset.page)); });
                    });
                } else {
                    pag.innerHTML = '';
                }
            })
            .catch(function() {
                body.innerHTML = '<tr><td colspan="7" class="text-center text-danger" style="font-size:12px;">Error al cargar historial</td></tr>';
            });
    }

    function loadDetalle(tipo, id, actionUrl) {
        fetch('{{ route("admin.alertas.detalle") }}?tipo=' + tipo + '&id=' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.item) {
                    showModal('Error', '<p class="text-muted">No se encontro el elemento.</p>', null);
                    return;
                }
                var item = data.item;
                var html = '<table class="table table-sm mb-0" style="font-size:12px;">';
                if (tipo === 'alert') {
                    html += '<tr><td class="fw-bold" style="width:120px;">Titulo</td><td>' + esc(item.title) + '</td></tr>';
                    html += '<tr><td class="fw-bold">Contenido</td><td>' + esc(item.content) + '</td></tr>';
                    html += '<tr><td class="fw-bold">Tipo</td><td><span class="badge ' + (badgeColors[item.type] || 'bg-secondary') + ' df-badge">' + esc(item.type) + '</span></td></tr>';
                    html += '<tr><td class="fw-bold">Escenario</td><td>' + esc(item.scenario) + '</td></tr>';
                    html += '<tr><td class="fw-bold">Fecha</td><td>' + esc(item.created_at) + '</td></tr>';
                    html += '<tr><td class="fw-bold">Estado</td><td>' + (item.is_read ? '<span class="badge bg-light text-muted df-badge">Leida</span>' : '<span class="badge bg-info df-badge">No leida</span>') + '</td></tr>';
                    if (item.metadata) {
                        try {
                            var meta = typeof item.metadata === 'string' ? JSON.parse(item.metadata) : item.metadata;
                            html += '<tr><td class="fw-bold">Metadatos</td><td><pre style="font-size:10px;max-height:150px;overflow:auto;margin:0;background:#f9fafb;padding:6px;border-radius:4px;">' + esc(JSON.stringify(meta, null, 2)) + '</pre></td></tr>';
                        } catch(e) {}
                    }
                } else {
                    html += '<tr><td class="fw-bold" style="width:120px;">Titulo</td><td>' + esc(item.title) + '</td></tr>';
                    html += '<tr><td class="fw-bold">Mensaje</td><td>' + esc(item.message) + '</td></tr>';
                    html += '<tr><td class="fw-bold">Tipo</td><td><span class="badge ' + (badgeColors[item.type] || 'bg-secondary') + ' df-badge">' + esc(item.type) + '</span></td></tr>';
                    html += '<tr><td class="fw-bold">Prioridad</td><td>' + esc(item.priority) + '</td></tr>';
                    html += '<tr><td class="fw-bold">Categoria</td><td>' + esc(item.category) + '</td></tr>';
                    html += '<tr><td class="fw-bold">Fecha</td><td>' + esc(item.created_at) + '</td></tr>';
                    html += '<tr><td class="fw-bold">Estado</td><td>' + (item.read_at ? '<span class="badge bg-light text-muted df-badge">Leida</span>' : '<span class="badge bg-info df-badge">No leida</span>') + '</td></tr>';
                    if (item.data) {
                        try {
                            var ndata = typeof item.data === 'string' ? JSON.parse(item.data) : item.data;
                            html += '<tr><td class="fw-bold">Datos</td><td><pre style="font-size:10px;max-height:150px;overflow:auto;margin:0;background:#f9fafb;padding:6px;border-radius:4px;">' + esc(JSON.stringify(ndata, null, 2)) + '</pre></td></tr>';
                        } catch(e) {}
                    }
                }
                html += '</table>';

                var linkUrl = actionUrl || item.action_url || null;
                showModal(item.title || 'Detalle', html, linkUrl);
            })
            .catch(function() {
                showModal('Error', '<p class="text-danger">Error al cargar detalle.</p>', null);
            });
    }

    document.getElementById('histBuscar').addEventListener('click', function() { loadHistorial(1); });
    document.getElementById('histRefresh').addEventListener('click', function() { loadHistorial(histPage); });

    document.getElementById('btnTabHistorial').addEventListener('shown.bs.tab', function() {
        if (!histLoaded) { histLoaded = true; loadHistorial(1); }
    });

    // ========== TAB 3: PLANTILLAS WHATSAPP ==========
    var plantillasLoaded = false;

    function loadPlantillas() {
        var body = document.getElementById('plantillasBody');
        body.innerHTML = '<tr><td colspan="4" class="text-center"><span class="loading-spinner"></span></td></tr>';

        fetch('{{ route("admin.alertas.plantillas") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.templates || !data.templates.length) {
                    body.innerHTML = '<tr><td colspan="4" class="text-center text-muted" style="font-size:12px;">No hay plantillas' + (data.error ? ' (' + esc(data.error) + ')' : '') + '</td></tr>';
                    return;
                }
                body.innerHTML = data.templates.map(function(t) {
                    var sc = statusBadgeColors[t.status] || 'bg-secondary';
                    return '<tr class="clickable-row" data-tpl=\'' + esc(JSON.stringify(t)) + '\'>' +
                        '<td class="fw-semibold">' + esc(t.name) + '</td>' +
                        '<td>' + esc(t.language) + '</td>' +
                        '<td><span class="badge ' + sc + ' df-badge">' + esc(t.status) + '</span></td>' +
                        '<td>' + esc(t.category) + '</td>' +
                        '</tr>';
                }).join('');

                body.querySelectorAll('.clickable-row').forEach(function(row) {
                    row.addEventListener('click', function() {
                        try {
                            var tpl = JSON.parse(this.dataset.tpl);
                            showTemplatModal(tpl);
                        } catch(e) {}
                    });
                });
            })
            .catch(function() {
                body.innerHTML = '<tr><td colspan="4" class="text-center text-danger" style="font-size:12px;">Error al cargar plantillas</td></tr>';
            });
    }

    function showTemplatModal(tpl) {
        var html = '<div class="mb-2"><strong>Nombre:</strong> ' + esc(tpl.name) + '</div>';
        html += '<div class="mb-2"><strong>Idioma:</strong> ' + esc(tpl.language) + ' &nbsp; <strong>Estado:</strong> <span class="badge ' + (statusBadgeColors[tpl.status] || 'bg-secondary') + ' df-badge">' + esc(tpl.status) + '</span> &nbsp; <strong>Categoria:</strong> ' + esc(tpl.category) + '</div>';

        if (tpl.components) {
            try {
                var comps = typeof tpl.components === 'string' ? JSON.parse(tpl.components) : tpl.components;
                if (Array.isArray(comps)) {
                    comps.forEach(function(comp) {
                        html += '<div class="template-component">';
                        html += '<div class="comp-type">' + esc(comp.type || 'Componente') + '</div>';
                        if (comp.text) {
                            html += '<div>' + esc(comp.text) + '</div>';
                        }
                        if (comp.format) {
                            html += '<div style="font-size:10px;color:#9ca3af;">Formato: ' + esc(comp.format) + '</div>';
                        }
                        if (comp.buttons && Array.isArray(comp.buttons)) {
                            comp.buttons.forEach(function(btn) {
                                html += '<div style="margin-top:4px;"><span class="badge bg-light text-dark df-badge"><i class="bi bi-cursor"></i> ' + esc(btn.text || btn.url || '') + '</span></div>';
                            });
                        }
                        if (comp.example && comp.example.body_text) {
                            html += '<div style="font-size:10px;color:#9ca3af;margin-top:4px;">Ejemplo: ' + esc(JSON.stringify(comp.example.body_text)) + '</div>';
                        }
                        html += '</div>';
                    });
                } else {
                    html += '<pre style="font-size:10px;max-height:200px;overflow:auto;background:#f9fafb;padding:8px;border-radius:4px;">' + esc(JSON.stringify(comps, null, 2)) + '</pre>';
                }
            } catch(e) {
                html += '<pre style="font-size:10px;max-height:200px;overflow:auto;background:#f9fafb;padding:8px;border-radius:4px;">' + esc(String(tpl.components)) + '</pre>';
            }
        }
        showModal('Plantilla: ' + tpl.name, html, null);
    }

    document.getElementById('btnTabPlantillas').addEventListener('shown.bs.tab', function() {
        if (!plantillasLoaded) { plantillasLoaded = true; loadPlantillas(); }
    });

    // ========== TAB 4: MENSAJES OTA ==========
    var otaLoaded = false;

    function loadOTA(page) {
        var body = document.getElementById('otaBody');
        body.innerHTML = '<tr><td colspan="4" class="text-center"><span class="loading-spinner"></span></td></tr>';
        var url = '{{ route("admin.alertas.mensajesOTA") }}' + (page ? '?page=' + page : '');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var items = data.mensajes && data.mensajes.data ? data.mensajes.data : [];
                if (!data.success || !items.length) {
                    body.innerHTML = '<tr><td colspan="4" class="text-center text-muted" style="font-size:12px;">No hay mensajes OTA' + (data.error ? ' (' + esc(data.error) + ')' : '') + '</td></tr>';
                    document.getElementById('otaPagination').innerHTML = '';
                    return;
                }
                body.innerHTML = items.map(function(m) {
                    var fecha = m.created_at ? m.created_at.substring(0, 16).replace('T', ' ') : (m.date || '');
                    return '<tr class="clickable-row" data-msg=\'' + esc(JSON.stringify(m)) + '\'>' +
                        '<td>' + esc(fecha) + '</td>' +
                        '<td>' + esc(m.remitente || '-') + '</td>' +
                        '<td>' + esc(truncate(m.mensaje, 80)) + '</td>' +
                        '<td>' + esc(truncate(m.respuesta, 80)) + '</td>' +
                        '</tr>';
                }).join('');

                body.querySelectorAll('.clickable-row').forEach(function(row) {
                    row.addEventListener('click', function() {
                        try {
                            var msg = JSON.parse(this.dataset.msg);
                            var html = '<div class="mb-2"><strong>Remitente:</strong> ' + esc(msg.remitente || '-') + '</div>';
                            html += '<div class="mb-2"><strong>Fecha:</strong> ' + esc(msg.created_at || msg.date || '') + '</div>';
                            html += '<div class="mb-2"><strong>Tipo:</strong> ' + esc(msg.type || '-') + '</div>';
                            html += '<hr style="margin:8px 0;">';
                            html += '<div class="mb-2"><strong>Mensaje:</strong></div>';
                            html += '<div style="background:#f3f4f6;padding:8px;border-radius:6px;font-size:12px;white-space:pre-wrap;max-height:200px;overflow:auto;">' + esc(msg.mensaje || 'Sin mensaje') + '</div>';
                            html += '<div class="mt-2 mb-2"><strong>Respuesta IA:</strong></div>';
                            html += '<div style="background:#ecfdf5;padding:8px;border-radius:6px;font-size:12px;white-space:pre-wrap;max-height:200px;overflow:auto;">' + esc(msg.respuesta || 'Sin respuesta') + '</div>';
                            showModal('Mensaje OTA', html, null);
                        } catch(e) {}
                    });
                });

                renderPaginationGeneric(document.getElementById('otaPagination'), data.mensajes, loadOTA);
            })
            .catch(function() {
                body.innerHTML = '<tr><td colspan="4" class="text-center text-danger" style="font-size:12px;">Error al cargar mensajes OTA</td></tr>';
            });
    }

    document.getElementById('btnTabOTA').addEventListener('shown.bs.tab', function() {
        if (!otaLoaded) { otaLoaded = true; loadOTA(1); }
    });

    // ========== TAB 5: EMAILS ==========
    var emailsLoaded = false;

    function loadEmails(page) {
        var body = document.getElementById('emailsBody');
        body.innerHTML = '<tr><td colspan="4" class="text-center"><span class="loading-spinner"></span></td></tr>';
        var url = '{{ route("admin.alertas.emails") }}' + (page ? '?page=' + page : '');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var items = data.emails && data.emails.data ? data.emails.data : [];
                if (!data.success || !items.length) {
                    body.innerHTML = '<tr><td colspan="4" class="text-center text-muted" style="font-size:12px;">No hay emails' + (data.error ? ' (' + esc(data.error) + ')' : '') + '</td></tr>';
                    document.getElementById('emailsPagination').innerHTML = '';
                    return;
                }
                body.innerHTML = items.map(function(e) {
                    var fecha = e.created_at ? e.created_at.substring(0, 16).replace('T', ' ') : '';
                    var estado = e.view ? '<span class="badge bg-light text-muted df-badge">Leido</span>' : '<span class="badge bg-info df-badge">No leido</span>';
                    return '<tr class="clickable-row" data-email=\'' + esc(JSON.stringify(e)) + '\'>' +
                        '<td>' + esc(fecha) + '</td>' +
                        '<td>' + esc(e.sender || '-') + '</td>' +
                        '<td>' + esc(truncate(e.subject, 60)) + '</td>' +
                        '<td>' + estado + '</td>' +
                        '</tr>';
                }).join('');

                body.querySelectorAll('.clickable-row').forEach(function(row) {
                    row.addEventListener('click', function() {
                        try {
                            var email = JSON.parse(this.dataset.email);
                            var html = '<div class="mb-2"><strong>De:</strong> ' + esc(email.sender || '-') + '</div>';
                            html += '<div class="mb-2"><strong>Asunto:</strong> ' + esc(email.subject || '-') + '</div>';
                            html += '<div class="mb-2"><strong>Fecha:</strong> ' + esc(email.created_at || '') + '</div>';
                            html += '<hr style="margin:8px 0;">';
                            html += '<div style="background:#f9fafb;padding:10px;border-radius:6px;font-size:12px;max-height:400px;overflow:auto;">' + (email.body || '<em class="text-muted">Sin contenido</em>') + '</div>';
                            showModal('Email: ' + (email.subject || 'Sin asunto'), html, null);
                        } catch(e) {}
                    });
                });

                renderPaginationGeneric(document.getElementById('emailsPagination'), data.emails, loadEmails);
            })
            .catch(function() {
                body.innerHTML = '<tr><td colspan="4" class="text-center text-danger" style="font-size:12px;">Error al cargar emails</td></tr>';
            });
    }

    document.getElementById('btnTabEmails').addEventListener('shown.bs.tab', function() {
        if (!emailsLoaded) { emailsLoaded = true; loadEmails(1); }
    });
});
</script>
@endsection
