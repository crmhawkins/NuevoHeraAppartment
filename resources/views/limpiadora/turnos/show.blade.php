@extends('layouts.appAdmin')

@section('title', 'Detalles del Turno')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt me-2"></i>Detalles del Turno
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('limpiadora.turnos.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Información del turno -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Información del Turno</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Fecha:</strong><br>
                                            {{ $turno->fecha->format('d/m/Y') }}
                                        </div>
                                        <div class="col-6">
                                            <strong>Horario:</strong><br>
                                            {{ $turno->hora_inicio->format('H:i') }} - {{ $turno->hora_fin->format('H:i') }}
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Estado:</strong><br>
                                            <span class="badge badge-{{ $turno->estado === 'completado' ? 'success' : ($turno->estado === 'en_progreso' ? 'warning' : 'primary') }}">
                                                {{ ucfirst(str_replace('_', ' ', $turno->estado)) }}
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <strong>Horas Trabajadas:</strong><br>
                                            {{ $turno->horas_trabajadas ? $turno->horas_trabajadas . 'h' : 'No registradas' }}
                                        </div>
                                    </div>
                                    @if($turno->observaciones)
                                        <hr>
                                        <div>
                                            <strong>Observaciones:</strong><br>
                                            <p class="text-muted mb-0">{{ $turno->observaciones }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Progreso del Turno</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Progreso General</span>
                                            <span>{{ $turno->progreso }}%</span>
                                        </div>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $turno->progreso }}%">
                                                {{ $turno->progreso }}%
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h5 class="mb-0 text-primary">{{ $turno->total_tareas }}</h5>
                                                <small class="text-muted">Total Tareas</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h5 class="mb-0 text-success">{{ $turno->tareas_completadas }}</h5>
                                                <small class="text-muted">Completadas</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <h5 class="mb-0 text-warning">{{ $turno->tareas_pendientes }}</h5>
                                                <small class="text-muted">Pendientes</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <strong>Tiempo Estimado:</strong><br>
                                            <span class="text-primary">{{ $turno->tiempo_estimado_total_formateado }}</span>
                                        </div>
                                        <div class="col-6">
                                            <strong>Tiempo Real:</strong><br>
                                            <span class="text-success">{{ $turno->tiempo_real_total_formateado }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de tareas -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>Tareas del Turno
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($turno->tareasAsignadas->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="5%">Estado</th>
                                                <th width="25%">Tarea</th>
                                                <th width="20%">Elemento</th>
                                                <th width="10%">Tiempo Est.</th>
                                                <th width="10%">Tiempo Real</th>
                                                <th width="10%">Prioridad</th>
                                                <th width="15%">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($turno->tareasAsignadas->sortBy('orden_ejecucion') as $tarea)
                                                <tr class="{{ $tarea->estado === 'completada' ? 'table-success' : ($tarea->estado === 'en_progreso' ? 'table-warning' : '') }}">
                                                    <td>{{ $tarea->orden_ejecucion }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $tarea->estado === 'completada' ? 'success' : ($tarea->estado === 'en_progreso' ? 'warning' : 'secondary') }}">
                                                            {{ ucfirst($tarea->estado) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $tarea->tipoTarea->nombre }}</strong>
                                                        @if($tarea->observaciones)
                                                            <br><small class="text-muted">{{ $tarea->observaciones }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $tarea->elemento_nombre }}</td>
                                                    <td>{{ $tarea->tiempo_estimado_formateado }}</td>
                                                    <td>{{ $tarea->tiempo_real_formateado }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $tarea->prioridad_calculada >= 8 ? 'danger' : ($tarea->prioridad_calculada >= 5 ? 'warning' : 'info') }}">
                                                            {{ $tarea->prioridad_calculada }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            @if($tarea->estado === 'pendiente')
                                                                <button class="btn btn-outline-success" onclick="iniciarTarea({{ $tarea->id }})" title="Iniciar Tarea">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            @elseif($tarea->estado === 'en_progreso')
                                                                <button class="btn btn-outline-primary" onclick="completarTarea({{ $tarea->id }})" title="Completar Tarea">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            @else
                                                                <span class="text-success">
                                                                    <i class="fas fa-check-circle"></i> Completada
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-tasks fa-2x text-muted mb-3"></i>
                                    <h6 class="text-muted">No hay tareas asignadas a este turno</h6>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Acciones del turno -->
                    @if($turno->estado !== 'completado')
                        <div class="card mt-4">
                            <div class="card-body text-center">
                                @if($turno->estado === 'programado')
                                    <button class="btn btn-success btn-lg" onclick="iniciarTurno({{ $turno->id }})">
                                        <i class="fas fa-play me-2"></i>Iniciar Turno
                                    </button>
                                @elseif($turno->estado === 'en_progreso')
                                    <button class="btn btn-warning btn-lg" onclick="finalizarTurno({{ $turno->id }})">
                                        <i class="fas fa-stop me-2"></i>Finalizar Turno
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para completar tarea -->
<div class="modal fade" id="completarTareaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Completar Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="completarTareaForm">
                <div class="modal-body">
                    <input type="hidden" id="tareaId" name="tarea_id">
                    
                    <div class="mb-3">
                        <label for="observaciones_tarea" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_tarea" name="observaciones" rows="3" 
                                  placeholder="Observaciones sobre la tarea completada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Completar Tarea</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para finalizar turno -->
<div class="modal fade" id="finalizarTurnoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Finalizar Turno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="finalizarTurnoForm">
                <div class="modal-body">
                    <input type="hidden" id="turnoId" name="turno_id" value="{{ $turno->id }}">
                    
                    <div class="mb-3">
                        <label for="horas_trabajadas" class="form-label">Horas Trabajadas</label>
                        <input type="number" class="form-control" id="horas_trabajadas" name="horas_trabajadas" 
                               step="0.5" min="0" max="24" placeholder="Ej: 7.5">
                        <div class="form-text">Deja vacío para calcular automáticamente</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones_turno" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_turno" name="observaciones" rows="3" 
                                  placeholder="Observaciones sobre el turno..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="motivo_horas_extras" class="form-label">Motivo de Horas Extras (si aplica)</label>
                        <textarea class="form-control" id="motivo_horas_extras" name="motivo_horas_extras" rows="2" 
                                  placeholder="Explica por qué trabajaste más horas de las contratadas..."></textarea>
                        <div class="form-text">Solo necesario si trabajaste más horas de las contratadas</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Finalizar Turno</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function iniciarTurno(turnoId) {
    if (confirm('¿Iniciar este turno?')) {
        $.ajax({
            url: '/limpiadora/turnos/' + turnoId + '/iniciar',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: response.message,
                        icon: 'success'
                    }).then(() => {
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
    }
}

function finalizarTurno(turnoId) {
    $('#turnoId').val(turnoId);
    $('#finalizarTurnoModal').modal('show');
}

function iniciarTarea(tareaId) {
    if (confirm('¿Iniciar esta tarea?')) {
        $.ajax({
            url: '/limpiadora/turnos/tareas/' + tareaId + '/iniciar',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: response.message,
                        icon: 'success'
                    }).then(() => {
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
    }
}

function completarTarea(tareaId) {
    $('#tareaId').val(tareaId);
    $('#completarTareaModal').modal('show');
}

$('#finalizarTurnoForm').submit(function(e) {
    e.preventDefault();
    
    const turnoId = $('#turnoId').val();
    const formData = $(this).serialize();
    
    $.ajax({
        url: '/limpiadora/turnos/' + turnoId + '/finalizar',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#finalizarTurnoModal').modal('hide');
                Swal.fire({
                    title: '¡Éxito!',
                    text: response.message,
                    icon: 'success'
                }).then(() => {
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

$('#completarTareaForm').submit(function(e) {
    e.preventDefault();
    
    const tareaId = $('#tareaId').val();
    const formData = $(this).serialize();
    
    $.ajax({
        url: '/limpiadora/turnos/tareas/' + tareaId + '/completar',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#completarTareaModal').modal('hide');
                Swal.fire({
                    title: '¡Éxito!',
                    text: response.message,
                    icon: 'success'
                }).then(() => {
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
