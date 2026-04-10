@extends('layouts.appAdmin')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-university text-primary me-2"></i>
            Configuracion Bankinter
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Bankinter</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('admin.bankinter.historial') }}" class="btn btn-outline-primary">
        <i class="fas fa-history me-2"></i>Ver Historial Completo
    </a>
</div>

<!-- Session Alerts -->
@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Cuentas Configuradas -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">
            <i class="fas fa-wallet text-primary me-2"></i>
            Cuentas Bancarias Configuradas
        </h5>
    </div>
    <div class="card-body">
        @if(count($cuentas) > 0)
            <div class="row">
                @foreach($cuentas as $cuenta)
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                        <i class="fas fa-university text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-uppercase">{{ $cuenta['alias'] }}</h6>
                                        <small class="text-muted">IBAN: {{ $cuenta['iban'] }}</small>
                                    </div>
                                </div>

                                <!-- Estado de configuracion -->
                                <div class="mb-2">
                                    @if($cuenta['configurada'])
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="fas fa-check-circle me-1"></i>Credenciales configuradas
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="fas fa-times-circle me-1"></i>Sin credenciales
                                        </span>
                                    @endif
                                    <span class="badge bg-info-subtle text-info ms-1">
                                        Bank ID: {{ $cuenta['bank_id'] }}
                                    </span>
                                </div>

                                <!-- Ultima sincronizacion -->
                                <div class="mb-3">
                                    @if($cuenta['ultimo_sync'])
                                        <small class="text-muted d-block">
                                            <i class="fas fa-clock me-1"></i>
                                            Ultima sync: {{ $cuenta['ultimo_sync']->fecha_sync->format('d/m/Y H:i') }}
                                        </small>
                                        @if($cuenta['ultimo_sync']->status === 'success')
                                            <span class="badge bg-success mt-1">
                                                <i class="fas fa-check me-1"></i>Exitosa
                                            </span>
                                            <small class="text-muted ms-1">
                                                {{ $cuenta['ultimo_sync']->procesados }} proc. /
                                                {{ $cuenta['ultimo_sync']->duplicados }} dup. /
                                                {{ $cuenta['ultimo_sync']->errores }} err.
                                            </small>
                                        @elseif($cuenta['ultimo_sync']->status === 'running')
                                            <span class="badge bg-warning mt-1">
                                                <i class="fas fa-spinner fa-spin me-1"></i>En curso
                                            </span>
                                        @else
                                            <span class="badge bg-danger mt-1">
                                                <i class="fas fa-times me-1"></i>Error
                                            </span>
                                        @endif
                                    @else
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>Nunca sincronizada
                                        </small>
                                    @endif
                                </div>

                                <!-- Info: sincronizacion automatica via scraper externo -->
                                <small class="text-muted d-block text-center mt-1">
                                    <i class="fas fa-robot me-1"></i>Sincronizacion automatica via PC externo (08:00 y 12:00)
                                </small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-university fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay cuentas Bankinter configuradas</h5>
                <p class="text-muted">Configura las credenciales en el archivo <code>.env</code> o en <code>config/services.php</code></p>
            </div>
        @endif
    </div>
</div>

<!-- Historial Reciente -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-history text-primary me-2"></i>
            Historial de Importaciones Recientes
        </h5>
        <a href="{{ route('admin.bankinter.historial') }}" class="btn btn-sm btn-outline-secondary">
            Ver todo
        </a>
    </div>
    <div class="card-body p-0">
        @if($historial->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0"><i class="fas fa-calendar text-primary me-1"></i>Fecha</th>
                            <th class="border-0"><i class="fas fa-wallet text-primary me-1"></i>Cuenta</th>
                            <th class="border-0"><i class="fas fa-signal text-primary me-1"></i>Estado</th>
                            <th class="border-0"><i class="fas fa-list-ol text-primary me-1"></i>Total</th>
                            <th class="border-0"><i class="fas fa-check text-primary me-1"></i>Procesados</th>
                            <th class="border-0"><i class="fas fa-copy text-primary me-1"></i>Duplicados</th>
                            <th class="border-0"><i class="fas fa-exclamation-triangle text-primary me-1"></i>Errores</th>
                            <th class="border-0"><i class="fas fa-arrow-down text-primary me-1"></i>Ingresos</th>
                            <th class="border-0"><i class="fas fa-arrow-up text-primary me-1"></i>Gastos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historial as $log)
                            <tr>
                                <td>
                                    <small>{{ $log->fecha_sync->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase">{{ $log->cuenta_alias }}</span>
                                </td>
                                <td>
                                    @if($log->status === 'success')
                                        <span class="badge bg-success">Exitosa</span>
                                    @elseif($log->status === 'running')
                                        <span class="badge bg-warning"><i class="fas fa-spinner fa-spin me-1"></i>En curso</span>
                                    @else
                                        <span class="badge bg-danger" data-bs-toggle="tooltip" title="{{ $log->error_message }}">Error</span>
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Sin historial de importaciones</h5>
                <p class="text-muted">Ejecuta una sincronizacion para ver los resultados aqui.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) {
            return new bootstrap.Tooltip(el);
        });

        // Sincronizacion manual deshabilitada: ahora corre automaticamente
        // desde un PC Windows externo a las 08:00 y 12:00 cada dia.
    });
</script>
@endsection
