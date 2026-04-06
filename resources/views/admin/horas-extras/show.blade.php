@extends('layouts.appAdmin')

@section('title', 'Detalles de Horas Extras')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock me-2"></i>Detalles de Horas Extras
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.horas-extras.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Información de la empleada -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Información de la Empleada</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Nombre:</strong><br>
                                            {{ $horasExtras->user->name }}
                                        </div>
                                        <div class="col-6">
                                            <strong>Email:</strong><br>
                                            {{ $horasExtras->user->email }}
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Fecha:</strong><br>
                                            {{ $horasExtras->fecha->format('d/m/Y') }}
                                        </div>
                                        <div class="col-6">
                                            <strong>Turno ID:</strong><br>
                                            #{{ $horasExtras->turno_id }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Información de horas -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Información de Horas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-3">
                                                <h5 class="mb-0 text-primary">{{ $horasExtras->horas_contratadas_formateadas }}</h5>
                                                <small class="text-muted">Horas Contratadas</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-3">
                                                <h5 class="mb-0 text-info">{{ $horasExtras->horas_trabajadas_formateadas }}</h5>
                                                <small class="text-muted">Horas Trabajadas</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-3">
                                                <h5 class="mb-0 text-warning">{{ $horasExtras->horas_extras_formateadas }}</h5>
                                                <small class="text-muted">Horas Extras</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="text-center">
                                        <strong>Estado:</strong><br>
                                        <span class="badge badge-{{ $horasExtras->estado === 'aprobada' ? 'success' : ($horasExtras->estado === 'rechazada' ? 'danger' : 'warning') }} fs-6">
                                            {{ ucfirst($horasExtras->estado) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Motivo y observaciones -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Motivo de las Horas Extras</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">{{ $horasExtras->motivo }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Observaciones del Administrador</h6>
                                </div>
                                <div class="card-body">
                                    @if($horasExtras->observaciones_admin)
                                        <p class="mb-0">{{ $horasExtras->observaciones_admin }}</p>
                                    @else
                                        <p class="text-muted mb-0">Sin observaciones</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de aprobación -->
                    @if($horasExtras->aprobador)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Información de {{ $horasExtras->estado === 'aprobada' ? 'Aprobación' : 'Rechazo' }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>{{ $horasExtras->estado === 'aprobada' ? 'Aprobado por:' : 'Rechazado por:' }}</strong><br>
                                                {{ $horasExtras->aprobador->name }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Fecha:</strong><br>
                                                {{ $horasExtras->fecha_aprobacion->format('d/m/Y H:i') }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Estado:</strong><br>
                                                <span class="badge badge-{{ $horasExtras->estado === 'aprobada' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($horasExtras->estado) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Acciones -->
                    @if($horasExtras->estado === 'pendiente')
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h6 class="mb-3">Acciones Disponibles</h6>
                                        <div class="btn-group">
                                            <button class="btn btn-success btn-lg" onclick="aprobarHoraExtra({{ $horasExtras->id }})">
                                                <i class="fas fa-check me-2"></i>Aprobar Horas Extras
                                            </button>
                                            <button class="btn btn-danger btn-lg" onclick="rechazarHoraExtra({{ $horasExtras->id }})">
                                                <i class="fas fa-times me-2"></i>Rechazar Horas Extras
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para aprobar -->
<div class="modal fade" id="aprobarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aprobar Horas Extras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="aprobarForm">
                <div class="modal-body">
                    <input type="hidden" id="aprobarId" name="id" value="{{ $horasExtras->id }}">
                    <div class="mb-3">
                        <label for="observaciones_aprobar" class="form-label">Observaciones (opcional)</label>
                        <textarea class="form-control" id="observaciones_aprobar" name="observaciones_admin" rows="3" 
                                  placeholder="Observaciones sobre la aprobación..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Se aprobarán <strong>{{ $horasExtras->horas_extras_formateadas }}</strong> de horas extras para <strong>{{ $horasExtras->user->name }}</strong>.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Aprobar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para rechazar -->
<div class="modal fade" id="rechazarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar Horas Extras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rechazarForm">
                <div class="modal-body">
                    <input type="hidden" id="rechazarId" name="id" value="{{ $horasExtras->id }}">
                    <div class="mb-3">
                        <label for="observaciones_rechazar" class="form-label">Motivo del rechazo *</label>
                        <textarea class="form-control" id="observaciones_rechazar" name="observaciones_admin" rows="3" 
                                  placeholder="Explica por qué se rechazan estas horas extras..." required></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Se rechazarán <strong>{{ $horasExtras->horas_extras_formateadas }}</strong> de horas extras para <strong>{{ $horasExtras->user->name }}</strong>.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rechazar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function aprobarHoraExtra(id) {
    $('#aprobarModal').modal('show');
}

function rechazarHoraExtra(id) {
    $('#rechazarModal').modal('show');
}

$('#aprobarForm').submit(function(e) {
    e.preventDefault();
    
    const id = $('#aprobarId').val();
    const formData = $(this).serialize();
    
    $.ajax({
        url: '/admin/horas-extras/' + id + '/aprobar',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#aprobarModal').modal('hide');
                Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    });
});

$('#rechazarForm').submit(function(e) {
    e.preventDefault();
    
    const id = $('#rechazarId').val();
    const formData = $(this).serialize();
    
    $.ajax({
        url: '/admin/horas-extras/' + id + '/rechazar',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#rechazarModal').modal('hide');
                Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    });
});
</script>
@endpush

