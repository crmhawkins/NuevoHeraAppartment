@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-history text-primary me-2"></i>
            Historial de Sincronizaciones Bankinter
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.bankinter.index') }}">Bankinter</a></li>
                <li class="breadcrumb-item active" aria-current="page">Historial</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('admin.bankinter.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Volver
    </a>
</div>

<!-- Filtros -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-filter text-primary me-2"></i>
            Filtros
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.bankinter.historial') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="cuenta" class="form-label">Cuenta</label>
                <select name="cuenta" id="cuenta" class="form-select">
                    <option value="">Todas las cuentas</option>
                    @foreach($cuentasDisponibles as $alias)
                        <option value="{{ $alias }}" {{ request('cuenta') === $alias ? 'selected' : '' }}>
                            {{ strtoupper($alias) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Estado</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Exitosa</option>
                    <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                    <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>En curso</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Filtrar
                </button>
                <a href="{{ route('admin.bankinter.historial') }}" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-undo me-1"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Historial -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-list text-primary me-2"></i>
            Registros de Importacion
        </h5>
    </div>
    <div class="card-body p-0">
        @if($historial->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0"><i class="fas fa-hashtag text-primary me-1"></i>ID</th>
                            <th class="border-0"><i class="fas fa-calendar text-primary me-1"></i>Fecha</th>
                            <th class="border-0"><i class="fas fa-wallet text-primary me-1"></i>Cuenta</th>
                            <th class="border-0"><i class="fas fa-signal text-primary me-1"></i>Estado</th>
                            <th class="border-0"><i class="fas fa-list-ol text-primary me-1"></i>Total</th>
                            <th class="border-0"><i class="fas fa-check text-primary me-1"></i>Proc.</th>
                            <th class="border-0"><i class="fas fa-copy text-primary me-1"></i>Dup.</th>
                            <th class="border-0"><i class="fas fa-exclamation-triangle text-primary me-1"></i>Err.</th>
                            <th class="border-0"><i class="fas fa-arrow-down text-success me-1"></i>Ing.</th>
                            <th class="border-0"><i class="fas fa-arrow-up text-danger me-1"></i>Gas.</th>
                            <th class="border-0"><i class="fas fa-file text-primary me-1"></i>Archivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historial as $log)
                            <tr>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary fw-bold">#{{ $log->id }}</span>
                                </td>
                                <td>
                                    <small>{{ $log->fecha_sync->format('d/m/Y H:i:s') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase">{{ $log->cuenta_alias }}</span>
                                </td>
                                <td>
                                    @if($log->status === 'success')
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Exitosa</span>
                                    @elseif($log->status === 'running')
                                        <span class="badge bg-warning"><i class="fas fa-spinner fa-spin me-1"></i>En curso</span>
                                    @else
                                        <span class="badge bg-danger" data-bs-toggle="tooltip" title="{{ $log->error_message }}">
                                            <i class="fas fa-times me-1"></i>Error
                                        </span>
                                    @endif
                                </td>
                                <td><span class="fw-semibold">{{ $log->total_filas }}</span></td>
                                <td><span class="text-success fw-semibold">{{ $log->procesados }}</span></td>
                                <td><span class="text-muted">{{ $log->duplicados }}</span></td>
                                <td>
                                    @if($log->errores > 0)
                                        <span class="text-danger fw-semibold">{{ $log->errores }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td><span class="text-success">{{ $log->ingresos_creados }}</span></td>
                                <td><span class="text-danger">{{ $log->gastos_creados }}</span></td>
                                <td>
                                    @if($log->archivo)
                                        <small class="text-muted" data-bs-toggle="tooltip" title="{{ $log->archivo }}">
                                            {{ basename($log->archivo) }}
                                        </small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginacion -->
            <div class="card-footer d-flex justify-content-center">
                {{ $historial->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Sin registros</h5>
                <p class="text-muted">No se encontraron registros con los filtros aplicados.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) {
            return new bootstrap.Tooltip(el);
        });
    });
</script>
@endsection
