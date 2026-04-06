@extends('layouts.appAdmin')

@section('title', 'Cierres de Apartamentos')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-door-closed text-primary me-2"></i>
                        Cierres de Apartamentos
                    </h1>
                    <p class="text-muted mb-0">Gestión de cierres de apartamentos</p>
                </div>
                <div>
                    <a href="{{ route('admin.cerrar-apartamento.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Crear Cierre
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-door-closed text-primary fa-2x mb-2"></i>
                    <h4 class="mb-1 fw-bold text-primary">{{ $cierresActivos ?? 0 }}</h4>
                    <small class="text-muted">Cierres Activos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-alt text-success fa-2x mb-2"></i>
                    <h4 class="mb-1 fw-bold text-success">{{ $cierresHoy ?? 0 }}</h4>
                    <small class="text-muted">Cierres Hoy</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-home text-info fa-2x mb-2"></i>
                    <h4 class="mb-1 fw-bold text-info">{{ $apartamentosCerrados ?? 0 }}</h4>
                    <small class="text-muted">Apartamentos Cerrados</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-history text-warning fa-2x mb-2"></i>
                    <h4 class="mb-1 fw-bold text-warning">{{ $totalCierres ?? 0 }}</h4>
                    <small class="text-muted">Total Cierres</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Cierres -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Cierres
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if(isset($cierres) && $cierres->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tablaCierres">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">ID</th>
                                        <th style="width: 20%;">Apartamento</th>
                                        <th style="width: 15%;">Fecha Inicio</th>
                                        <th style="width: 15%;">Fecha Fin</th>
                                        <th style="width: 10%;">Días</th>
                                        <th style="width: 10%;">Estado</th>
                                        <th style="width: 15%;">Creado</th>
                                        <th style="width: 10%;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cierres as $cierre)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">#{{ $cierre->id }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-home text-primary me-2"></i>
                                                    <div>
                                                        <div class="fw-semibold">{{ $cierre->apartamento->nombre ?? 'N/A' }}</div>
                                                        @if($cierre->apartamento && $cierre->apartamento->id_channex)
                                                            <small class="text-muted">Channex: {{ substr($cierre->apartamento->id_channex, 0, 8) }}...</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    {{ \Carbon\Carbon::parse($cierre->fecha_inicio)->format('d/m/Y') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    {{ \Carbon\Carbon::parse($cierre->fecha_fin)->format('d/m/Y') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ \Carbon\Carbon::parse($cierre->fecha_inicio)->diffInDays($cierre->fecha_fin) }} días
                                                </span>
                                            </td>
                                            <td>
                                                @if($cierre->activo)
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Activo
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-check me-1"></i>Finalizado
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($cierre->created_at)->format('d/m/Y H:i') }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('reservas.show', $cierre->reserva_id) }}" 
                                                       class="btn btn-outline-primary btn-sm" 
                                                       title="Ver Reserva">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($cierre->activo)
                                                        <button type="button" 
                                                                class="btn btn-outline-warning btn-sm" 
                                                                title="Finalizar Cierre"
                                                                onclick="finalizarCierre({{ $cierre->id }})">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-door-closed text-muted fa-3x mb-3"></i>
                            <h5 class="text-muted">No hay cierres registrados</h5>
                            <p class="text-muted">Crea tu primer cierre de apartamento</p>
                            <a href="{{ route('admin.cerrar-apartamento.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Crear Primer Cierre
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar DataTable si hay datos
        @if(isset($cierres) && $cierres->count() > 0)
            $('#tablaCierres').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "pageLength": 25,
                "order": [[ 0, "desc" ]],
                "columnDefs": [
                    { "orderable": false, "targets": 7 }
                ]
            });
        @endif
    });

    function finalizarCierre(cierreId) {
        Swal.fire({
            title: '¿Finalizar cierre?',
            text: '¿Estás seguro de que quieres finalizar este cierre de apartamento?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, finalizar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed) {
                // Aquí iría la lógica para finalizar el cierre
                Swal.fire({
                    title: '¡Funcionalidad en desarrollo!',
                    text: 'La funcionalidad para finalizar cierres está en desarrollo.',
                    icon: 'info',
                    confirmButtonText: 'Entendido'
                });
            }
        });
    }
</script>
@endsection
