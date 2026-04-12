@extends('layouts.appAdmin')

@section('title', 'Dashboard Financiero')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="fas fa-chart-line me-2" style="color: #0891b2;"></i>Dashboard Financiero</h3>
        <div>
            <a href="{{ route('admin.diarioCaja.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-book me-1"></i>Diario de Caja
            </a>
            <a href="{{ route('admin.facturas.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-file-invoice me-1"></i>Facturas
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="todos" {{ $estado === 'todos' ? 'selected' : '' }}>Todos</option>
                        <option value="pendiente" {{ $estado === 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                        <option value="cobrada" {{ $estado === 'cobrada' ? 'selected' : '' }}>Cobradas</option>
                        <option value="cancelada" {{ $estado === 'cancelada' ? 'selected' : '' }}>Canceladas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn w-100" style="background: #0891b2; color: white;">
                        <i class="fas fa-filter me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-left: 4px solid #0891b2 !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Facturado</p>
                            <h4 class="fw-bold mb-0">{{ number_format($totalFacturado, 2, ',', '.') }} &euro;</h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(8,145,178,0.1);">
                            <i class="fas fa-file-invoice" style="color: #0891b2; font-size: 20px;"></i>
                        </div>
                    </div>
                    <small class="text-muted">{{ $numFacturas }} facturas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-left: 4px solid #10b981 !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Cobrado</p>
                            <h4 class="fw-bold mb-0 text-success">{{ number_format($totalCobrado, 2, ',', '.') }} &euro;</h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(16,185,129,0.1);">
                            <i class="fas fa-check-circle text-success" style="font-size: 20px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-left: 4px solid #f59e0b !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Pendiente</p>
                            <h4 class="fw-bold mb-0 text-warning">{{ number_format($totalPendiente, 2, ',', '.') }} &euro;</h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(245,158,11,0.1);">
                            <i class="fas fa-clock text-warning" style="font-size: 20px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-left: 4px solid #ef4444 !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Cancelado</p>
                            <h4 class="fw-bold mb-0 text-danger">{{ number_format($totalCancelado, 2, ',', '.') }} &euro;</h4>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(239,68,68,0.1);">
                            <i class="fas fa-times-circle text-danger" style="font-size: 20px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        {{-- Ingresos por mes --}}
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-chart-bar me-2" style="color: #0891b2;"></i>Ingresos Bancarios (6 meses)</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartIngresosMes" height="200"></canvas>
                </div>
            </div>
        </div>
        {{-- Ingresos por canal --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-chart-pie me-2" style="color: #0891b2;"></i>Por Canal (mes actual)</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartCanales" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Facturas pendientes antiguas (alerta) --}}
    @if($facturasAntiguas->count() > 0)
    <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #f59e0b !important;">
        <div class="card-header bg-warning-subtle border-0 py-3">
            <h5 class="mb-0 fw-semibold text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Facturas Pendientes (+7 dias)</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Referencia</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Dias</th><th>Accion</th></tr></thead>
                <tbody>
                @foreach($facturasAntiguas as $f)
                    <tr>
                        <td class="fw-semibold">{{ $f->reference }}</td>
                        <td>{{ optional($f->cliente)->nombre ?? 'N/A' }} {{ optional($f->cliente)->apellido1 ?? '' }}</td>
                        <td>{{ $f->fecha ? \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') : '-' }}</td>
                        <td class="fw-bold">{{ number_format($f->total, 2, ',', '.') }} &euro;</td>
                        <td><span class="badge bg-warning text-dark">{{ $f->fecha ? now()->diffInDays(\Carbon\Carbon::parse($f->fecha)) : '?' }}d</span></td>
                        <td>
                            <button class="btn btn-success btn-sm" onclick="cambiarEstado({{ $f->id }}, 'cobrada')">
                                <i class="fas fa-check me-1"></i>Cobrada
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Tabla de facturas --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-semibold"><i class="fas fa-list me-2" style="color: #0891b2;"></i>Facturas del Periodo</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                @php
                    $toggleDir = $direction === 'asc' ? 'desc' : 'asc';
                    $qParams = request()->except(['order_by', 'direction', 'page']);
                @endphp
                <thead style="background: #f8fafc;">
                    <tr>
                        <th>
                            <a href="{{ route('admin.tesoreria.dashboard', array_merge($qParams, ['order_by' => 'reference', 'direction' => $orderBy === 'reference' ? $toggleDir : 'desc'])) }}" class="text-dark text-decoration-none">
                                Referencia {!! $orderBy === 'reference' ? ($direction === 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '' !!}
                            </a>
                        </th>
                        <th>Cliente</th>
                        <th>Reserva</th>
                        <th>
                            <a href="{{ route('admin.tesoreria.dashboard', array_merge($qParams, ['order_by' => 'fecha', 'direction' => $orderBy === 'fecha' ? $toggleDir : 'desc'])) }}" class="text-dark text-decoration-none">
                                Fecha {!! $orderBy === 'fecha' ? ($direction === 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '' !!}
                            </a>
                        </th>
                        <th>Base</th>
                        <th>IVA</th>
                        <th>
                            <a href="{{ route('admin.tesoreria.dashboard', array_merge($qParams, ['order_by' => 'total', 'direction' => $orderBy === 'total' ? $toggleDir : 'desc'])) }}" class="text-dark text-decoration-none">
                                Total {!! $orderBy === 'total' ? ($direction === 'asc' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '' !!}
                            </a>
                        </th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($facturas as $f)
                    <tr id="factura-row-{{ $f->id }}">
                        <td class="fw-semibold">{{ $f->reference }}</td>
                        <td>{{ optional($f->cliente)->nombre ?? 'N/A' }} {{ optional($f->cliente)->apellido1 ?? '' }}</td>
                        <td>
                            @if($f->reserva)
                                <small class="text-muted">{{ $f->reserva->codigo_reserva }}</small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td>{{ $f->fecha ? \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') : '-' }}</td>
                        <td>{{ number_format($f->base ?? 0, 2, ',', '.') }} &euro;</td>
                        <td>{{ number_format($f->iva ?? 0, 2, ',', '.') }} &euro;</td>
                        <td class="fw-bold">{{ number_format($f->total, 2, ',', '.') }} &euro;</td>
                        <td>
                            <select class="form-select form-select-sm estado-select" data-factura-id="{{ $f->id }}" style="width: 130px; font-size: 12px;">
                                <option value="pendiente" {{ in_array($f->invoice_status_id, [1,2]) ? 'selected' : '' }}>Pendiente</option>
                                <option value="cobrada" {{ in_array($f->invoice_status_id, [3,4,6]) ? 'selected' : '' }}>Cobrada</option>
                                <option value="cancelada" {{ in_array($f->invoice_status_id, [5,7]) ? 'selected' : '' }}>Cancelada</option>
                            </select>
                        </td>
                        <td>
                            <a href="{{ route('admin.facturas.generatePdf', $f->id) }}" class="btn btn-outline-secondary btn-sm" title="PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No hay facturas en este periodo</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($facturas->hasPages())
        <div class="card-footer bg-white">
            {{ $facturas->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Grafico ingresos por mes
    var ctxMes = document.getElementById('chartIngresosMes').getContext('2d');
    new Chart(ctxMes, {
        type: 'bar',
        data: {
            labels: {!! json_encode($ingresosPorMes->pluck('mes')) !!},
            datasets: [{
                label: 'Ingresos bancarios',
                data: {!! json_encode($ingresosPorMes->pluck('total')) !!},
                backgroundColor: 'rgba(8, 145, 178, 0.6)',
                borderColor: '#0891b2',
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return v.toLocaleString('es-ES') + ' \u20AC'; } } } }
        }
    });

    // Grafico por canal
    var ctxCanal = document.getElementById('chartCanales').getContext('2d');
    new Chart(ctxCanal, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($ingresosPorCanal->pluck('canal')) !!},
            datasets: [{
                data: {!! json_encode($ingresosPorCanal->pluck('total')) !!},
                backgroundColor: ['#0891b2', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
        }
    });

    // Cambiar estado factura
    document.querySelectorAll('.estado-select').forEach(function(select) {
        select.addEventListener('change', function() {
            cambiarEstado(this.dataset.facturaId, this.value);
        });
    });

    function cambiarEstado(facturaId, estado) {
        fetch('/admin/tesoreria/factura/' + facturaId + '/estado', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ estado: estado })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                }
            }
        })
        .catch(function(e) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: e.message });
            }
        });
    }
</script>
@endsection
