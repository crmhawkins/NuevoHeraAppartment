@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-list me-2 text-info"></i>
                        Facturas Rectificativas
                    </h1>
                    <p class="text-muted mb-0">Rectificativas asociadas a la factura original</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.facturas.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de la Factura Original -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice me-2"></i>
                        Factura Original
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <strong>Referencia:</strong><br>
                            <span class="badge bg-primary">{{ $facturaOriginal->reference }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Cliente:</strong><br>
                            {{ $facturaOriginal->cliente->nombre ?? 'N/A' }} {{ $facturaOriginal->cliente->apellido1 ?? '' }}
                        </div>
                        <div class="col-md-3">
                            <strong>Concepto:</strong><br>
                            {{ $facturaOriginal->concepto }}
                        </div>
                        <div class="col-md-2">
                            <strong>Total Original:</strong><br>
                            <span class="h5 text-success">{{ number_format($facturaOriginal->total, 2) }} €</span>
                        </div>
                        <div class="col-md-2">
                            <strong>Total Neto:</strong><br>
                            <span class="h5 text-info">{{ number_format($facturaOriginal->total_neto, 2) }} €</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Facturas Rectificativas -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-undo me-2"></i>
                        Facturas Rectificativas ({{ $facturaOriginal->facturasRectificativas->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    @if($facturaOriginal->facturasRectificativas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Referencia</th>
                                        <th>Fecha</th>
                                        <th>Motivo</th>
                                        <th>Base</th>
                                        <th>IVA</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($facturaOriginal->facturasRectificativas as $rectificativa)
                                        <tr>
                                            <td>
                                                <span class="badge bg-warning text-dark">{{ $rectificativa->reference }}</span>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($rectificativa->fecha)->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $rectificativa->motivo_rectificacion }}</span>
                                            </td>
                                            <td class="text-danger">{{ number_format($rectificativa->base, 2) }} €</td>
                                            <td class="text-danger">{{ number_format($rectificativa->iva, 2) }} €</td>
                                            <td class="text-danger"><strong>{{ number_format($rectificativa->total, 2) }} €</strong></td>
                                            <td>
                                                <span class="badge bg-{{ $rectificativa->estado->color ?? 'secondary' }}">
                                                    {{ $rectificativa->estado->name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.facturas.generatePdf', $rectificativa->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Descargar PDF">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    @if($rectificativa->observaciones_rectificacion)
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-info" 
                                                                data-bs-toggle="tooltip" 
                                                                title="{{ $rectificativa->observaciones_rectificacion }}">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Resumen -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-calculator me-2"></i>
                                        Resumen de Rectificaciones
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Total Original:</strong><br>
                                            <span class="h5 text-success">{{ number_format($facturaOriginal->total, 2) }} €</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Rectificativas:</strong><br>
                                            <span class="h5 text-danger">{{ number_format($facturaOriginal->facturasRectificativas->sum('total'), 2) }} €</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Neto:</strong><br>
                                            <span class="h5 text-info">{{ number_format($facturaOriginal->total_neto, 2) }} €</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Estado:</strong><br>
                                            @if($facturaOriginal->total_neto == 0)
                                                <span class="badge bg-success">Anulada</span>
                                            @else
                                                <span class="badge bg-warning">Parcialmente Rectificada</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-undo fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No hay rectificativas</h5>
                            <p class="text-muted mb-3">
                                Esta factura no tiene facturas rectificativas asociadas.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // SweetAlert para mensajes de sesión
    @if(session('swal_success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('swal_success') }}',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Aceptar'
        });
    @endif

    @if(session('swal_error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('swal_error') }}',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Aceptar'
        });
    @endif
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    border-radius: 12px 12px 0 0 !important;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.alert {
    border: none;
    border-radius: 8px;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}
</style>
@endsection
