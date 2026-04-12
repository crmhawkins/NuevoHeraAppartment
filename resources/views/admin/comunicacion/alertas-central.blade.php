@extends('layouts.appAdmin')

@section('title', 'Centro de Alertas')

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
    .nav-tabs .nav-link { font-size: 12px; padding: 6px 14px; }
    .nav-tabs .nav-link.active { font-weight: 700; color: #0891b2; border-color: #0891b2 #0891b2 #fff; }
    .servicio-code { font-size: 10px; font-family: monospace; color: #6b7280; background: #f3f4f6; padding: 1px 4px; border-radius: 3px; }
    .historial-estado { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .historial-estado.leida { background: #d1d5db; }
    .historial-estado.no_leida { background: #0891b2; }
</style>

<div class="container-fluid py-2">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="fw-bold mb-0" style="font-size: 16px;">
            <i class="bi bi-bell me-1" style="color: #0891b2;"></i>Centro de Alertas
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

    {{-- Alert Types: Tabs --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3">
            <ul class="nav nav-tabs mb-2" id="alertTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="internas-tab" data-bs-toggle="tab" data-bs-target="#internas" type="button" role="tab">
                        <i class="bi bi-shield-lock me-1"></i>Internas ({{ $internas }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="externas-tab" data-bs-toggle="tab" data-bs-target="#externas" type="button" role="tab">
                        <i class="bi bi-people me-1"></i>Externas ({{ $externas }})
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="alertTabsContent">
                {{-- Tab Internas --}}
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
                {{-- Tab Externas --}}
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
    </div>

    {{-- Historial Reciente --}}
    <div class="card shadow-sm border-0">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0" style="font-size: 13px;">
                    <i class="bi bi-clock-history me-1" style="color: #0891b2;"></i>Historial Reciente
                </h6>
                <div class="d-flex gap-1">
                    <select id="historialFiltro" class="form-select form-select-sm" style="font-size: 11px; width: auto;">
                        <option value="todos">Todos</option>
                        <option value="alertas">Alertas CRM</option>
                        <option value="notificaciones">Notificaciones</option>
                    </select>
                    <button id="historialRefresh" class="btn df-btn-sm" style="background:#0891b2;color:#fff;" title="Actualizar">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>

            <table class="table table-hover mb-0 df-table" id="historialTable">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th>Fecha/Hora</th>
                        <th>Tipo</th>
                        <th>Titulo</th>
                        <th>Destinatario</th>
                        <th>Canal</th>
                    </tr>
                </thead>
                <tbody id="historialBody">
                    {{-- Combined and sorted alerts + notifications --}}
                    @php
                        $historial = collect();
                        foreach($recentAlerts as $a) {
                            $historial->push([
                                'fecha' => $a->created_at->format('d/m/Y H:i'),
                                'tipo' => $a->type ?? 'info',
                                'titulo' => $a->title,
                                'destinatario' => $a->user ? $a->user->name : 'Sistema',
                                'canal' => 'crm',
                                'estado' => $a->is_read ? 'leida' : 'no_leida',
                                'created_at' => $a->created_at,
                            ]);
                        }
                        foreach($recentNotifications as $n) {
                            $historial->push([
                                'fecha' => $n->created_at->format('d/m/Y H:i'),
                                'tipo' => $n->type ?? 'info',
                                'titulo' => $n->title,
                                'destinatario' => $n->user ? $n->user->name : 'Sistema',
                                'canal' => $n->type === 'whatsapp' ? 'whatsapp' : 'crm',
                                'estado' => $n->read_at ? 'leida' : 'no_leida',
                                'created_at' => $n->created_at,
                            ]);
                        }
                        $historial = $historial->sortByDesc('created_at')->take(50);
                    @endphp
                    @forelse($historial as $item)
                    <tr>
                        <td><span class="historial-estado {{ $item['estado'] }}" title="{{ $item['estado'] === 'leida' ? 'Leida' : 'No leida' }}"></span></td>
                        <td>{{ $item['fecha'] }}</td>
                        <td>
                            @php
                                $badgeColors = [
                                    'info' => 'bg-info',
                                    'warning' => 'bg-warning text-dark',
                                    'error' => 'bg-danger',
                                    'success' => 'bg-success',
                                    'reserva' => 'bg-primary',
                                    'incidencia' => 'bg-danger',
                                    'limpieza' => 'bg-success',
                                    'facturacion' => 'bg-warning text-dark',
                                    'inventario' => 'bg-secondary',
                                    'sistema' => 'bg-dark',
                                    'whatsapp' => 'bg-success',
                                    'channex' => 'bg-primary',
                                ];
                                $badgeClass = $badgeColors[$item['tipo']] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $badgeClass }} df-badge">{{ ucfirst($item['tipo']) }}</span>
                        </td>
                        <td>{{ Str::limit($item['titulo'], 60) }}</td>
                        <td>{{ $item['destinatario'] }}</td>
                        <td>
                            <span class="canal-icon canal-{{ $item['canal'] }}">
                                @if($item['canal'] === 'whatsapp')<i class="bi bi-whatsapp"></i>
                                @elseif($item['canal'] === 'email')<i class="bi bi-envelope"></i>
                                @else<i class="bi bi-display"></i>
                                @endif
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="font-size: 12px;">Sin alertas recientes</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Paginacion AJAX --}}
            <div id="historialPagination" class="d-flex justify-content-center mt-2" style="display:none !important;"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtro = document.getElementById('historialFiltro');
    const refreshBtn = document.getElementById('historialRefresh');
    const body = document.getElementById('historialBody');
    const pagination = document.getElementById('historialPagination');
    let currentPage = 1;

    function loadHistorial(page) {
        currentPage = page || 1;
        const tipo = filtro.value;
        const url = '{{ route("admin.alertas.historial") }}?tipo=' + tipo + '&page=' + currentPage;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                renderHistorial(data.data);
                renderPagination(data);
            })
            .catch(() => {
                body.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Error al cargar historial</td></tr>';
            });
    }

    function renderHistorial(items) {
        if (!items.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="font-size:12px;">Sin alertas recientes</td></tr>';
            return;
        }
        const badgeColors = {
            info: 'bg-info', warning: 'bg-warning text-dark', error: 'bg-danger', success: 'bg-success',
            reserva: 'bg-primary', incidencia: 'bg-danger', limpieza: 'bg-success',
            facturacion: 'bg-warning text-dark', inventario: 'bg-secondary', sistema: 'bg-dark',
            whatsapp: 'bg-success', channex: 'bg-primary'
        };
        const canalIcons = {
            whatsapp: '<i class="bi bi-whatsapp"></i>',
            email: '<i class="bi bi-envelope"></i>',
            crm: '<i class="bi bi-display"></i>'
        };

        body.innerHTML = items.map(function(item) {
            const bc = badgeColors[item.tipo] || 'bg-secondary';
            const ci = canalIcons[item.canal] || canalIcons.crm;
            const titulo = item.titulo ? (item.titulo.length > 60 ? item.titulo.substring(0, 60) + '...' : item.titulo) : '';
            return '<tr>' +
                '<td><span class="historial-estado ' + item.estado + '" title="' + (item.estado === 'leida' ? 'Leida' : 'No leida') + '"></span></td>' +
                '<td>' + item.fecha + '</td>' +
                '<td><span class="badge ' + bc + ' df-badge">' + (item.tipo.charAt(0).toUpperCase() + item.tipo.slice(1)) + '</span></td>' +
                '<td>' + titulo + '</td>' +
                '<td>' + item.destinatario + '</td>' +
                '<td><span class="canal-icon canal-' + item.canal + '">' + ci + '</span></td>' +
                '</tr>';
        }).join('');
    }

    function renderPagination(data) {
        if (data.last_page <= 1) {
            pagination.style.display = 'none';
            return;
        }
        pagination.style.display = 'flex';
        let html = '';
        for (let i = 1; i <= data.last_page; i++) {
            const active = i === data.page ? 'btn-sm btn-primary' : 'btn-sm btn-outline-secondary';
            html += '<button class="btn ' + active + ' mx-1" style="font-size:11px;" onclick="window.__loadHistorial(' + i + ')">' + i + '</button>';
        }
        pagination.innerHTML = html;
    }

    window.__loadHistorial = loadHistorial;

    filtro.addEventListener('change', function() { loadHistorial(1); });
    refreshBtn.addEventListener('click', function() { loadHistorial(currentPage); });
});
</script>
@endsection
