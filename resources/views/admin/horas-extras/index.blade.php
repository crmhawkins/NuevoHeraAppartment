@extends('layouts.appAdmin')

@section('title', 'Gestión de Horas Extras')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock me-2"></i>Gestión de Horas Extras
                    </h3>
                    <div class="card-tools">
                        <button class="btn btn-success btn-sm" onclick="aprobarSeleccionados()">
                            <i class="fas fa-check me-1"></i>Aprobar Seleccionados
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="rechazarSeleccionados()">
                            <i class="fas fa-times me-1"></i>Rechazar Seleccionados
                        </button>
                        <button class="btn btn-info btn-sm" onclick="exportarHorasExtras()">
                            <i class="fas fa-download me-1"></i>Exportar
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white text-center">
                                <div class="card-body py-2">
                                    <h5 class="mb-0">{{ $estadisticas['total'] }}</h5>
                                    <small>Total Registros</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white text-center">
                                <div class="card-body py-2">
                                    <h5 class="mb-0">{{ $estadisticas['pendientes'] }}</h5>
                                    <small>Pendientes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white text-center">
                                <div class="card-body py-2">
                                    <h5 class="mb-0">{{ $estadisticas['aprobadas'] }}</h5>
                                    <small>Aprobadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white text-center">
                                <div class="card-body py-2">
                                    <h5 class="mb-0">{{ $estadisticas['rechazadas'] }}</h5>
                                    <small>Rechazadas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white text-center">
                                <div class="card-body py-2">
                                    <h5 class="mb-0">{{ number_format($estadisticas['total_horas_extras'], 1) }}h</h5>
                                    <small>Total Horas Extras</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white text-center">
                                <div class="card-body py-2">
                                    <h5 class="mb-0">{{ number_format($estadisticas['total_horas_extras_pendientes'], 1) }}h</h5>
                                    <small>Pendientes</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" id="filtrosForm">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="estado" class="form-label">Estado</label>
                                        <select class="form-select" id="estado" name="estado">
                                            <option value="">Todos los estados</option>
                                            <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                                            <option value="aprobada" {{ request('estado') == 'aprobada' ? 'selected' : '' }}>Aprobadas</option>
                                            <option value="rechazada" {{ request('estado') == 'rechazada' ? 'selected' : '' }}>Rechazadas</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="user_id" class="form-label">Empleada</label>
                                        <select class="form-select" id="user_id" name="user_id">
                                            <option value="">Todas las empleadas</option>
                                            @foreach($empleadas as $empleada)
                                                <option value="{{ $empleada->id }}" {{ request('user_id') == $empleada->id ? 'selected' : '' }}>
                                                    {{ $empleada->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="{{ request('fecha_inicio') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="{{ request('fecha_fin') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-1"></i>Filtrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de horas extras -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="horasExtrasTable">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th width="10%">Fecha</th>
                                    <th width="15%">Empleada</th>
                                    <th width="10%">Horas Contratadas</th>
                                    <th width="10%">Horas Trabajadas</th>
                                    <th width="10%">Horas Extras</th>
                                    <th width="15%">Motivo</th>
                                    <th width="10%">Estado</th>
                                    <th width="15%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($horasExtras as $horaExtra)
                                    <tr>
                                        <td>
                                            @if($horaExtra->estado === 'pendiente')
                                                <input type="checkbox" class="select-item" value="{{ $horaExtra->id }}">
                                            @endif
                                        </td>
                                        <td>{{ $horaExtra->fecha->format('d/m/Y') }}</td>
                                        <td>
                                            <strong>{{ $horaExtra->user->name }}</strong>
                                            <br><small class="text-muted">Turno #{{ $horaExtra->turno_id }}</small>
                                        </td>
                                        <td>{{ $horaExtra->horas_contratadas_formateadas }}</td>
                                        <td>{{ $horaExtra->horas_trabajadas_formateadas }}</td>
                                        <td>
                                            <span class="badge badge-warning fs-6">
                                                {{ $horaExtra->horas_extras_formateadas }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $horaExtra->motivo }}">
                                                {{ $horaExtra->motivo }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $horaExtra->estado === 'aprobada' ? 'success' : ($horaExtra->estado === 'rechazada' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($horaExtra->estado) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.horas-extras.show', $horaExtra) }}" class="btn btn-outline-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($horaExtra->estado === 'pendiente')
                                                    <button class="btn btn-outline-success" onclick="aprobarHoraExtra({{ $horaExtra->id }})" title="Aprobar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="rechazarHoraExtra({{ $horaExtra->id }})" title="Rechazar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="d-flex justify-content-center">
                        {{ $horasExtras->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para aprobar horas extras -->
<div class="modal fade" id="aprobarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aprobar Horas Extras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="aprobarForm">
                <div class="modal-body">
                    <input type="hidden" id="aprobarId" name="id">
                    <div class="mb-3">
                        <label for="observaciones_aprobar" class="form-label">Observaciones (opcional)</label>
                        <textarea class="form-control" id="observaciones_aprobar" name="observaciones_admin" rows="3" 
                                  placeholder="Observaciones sobre la aprobación..."></textarea>
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

<!-- Modal para rechazar horas extras -->
<div class="modal fade" id="rechazarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar Horas Extras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rechazarForm">
                <div class="modal-body">
                    <input type="hidden" id="rechazarId" name="id">
                    <div class="mb-3">
                        <label for="observaciones_rechazar" class="form-label">Motivo del rechazo *</label>
                        <textarea class="form-control" id="observaciones_rechazar" name="observaciones_admin" rows="3" 
                                  placeholder="Explica por qué se rechazan estas horas extras..." required></textarea>
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

<!-- Modal para acciones múltiples -->
<div class="modal fade" id="accionesMultiplesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accionesMultiplesTitle">Acciones Múltiples</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="accionesMultiplesForm">
                <div class="modal-body">
                    <input type="hidden" id="accionTipo" name="accion">
                    <div class="mb-3">
                        <label for="observaciones_multiples" class="form-label" id="observacionesLabel">Observaciones</label>
                        <textarea class="form-control" id="observaciones_multiples" name="observaciones_admin" rows="3" 
                                  placeholder="Observaciones..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="accionInfo">Se procesarán los elementos seleccionados.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn" id="accionButton">Procesar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Seleccionar todos
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const selectItems = document.querySelectorAll('.select-item');
    
    selectItems.forEach(item => {
        item.checked = selectAll.checked;
    });
}

// Aprobar hora extra individual
function aprobarHoraExtra(id) {
    $('#aprobarId').val(id);
    $('#aprobarModal').modal('show');
}

// Rechazar hora extra individual
function rechazarHoraExtra(id) {
    $('#rechazarId').val(id);
    $('#rechazarModal').modal('show');
}

// Aprobar seleccionados
function aprobarSeleccionados() {
    const selected = getSelectedIds();
    if (selected.length === 0) {
        Swal.fire('Atención', 'Selecciona al menos un elemento', 'warning');
        return;
    }
    
    $('#accionTipo').val('aprobar');
    $('#accionesMultiplesTitle').text('Aprobar Horas Extras Seleccionadas');
    $('#observacionesLabel').text('Observaciones (opcional)');
    $('#observaciones_multiples').attr('required', false);
    $('#accionButton').removeClass('btn-danger').addClass('btn-success').text('Aprobar');
    $('#accionInfo').text(`Se aprobarán ${selected.length} registros de horas extras.`);
    $('#accionesMultiplesModal').modal('show');
}

// Rechazar seleccionados
function rechazarSeleccionados() {
    const selected = getSelectedIds();
    if (selected.length === 0) {
        Swal.fire('Atención', 'Selecciona al menos un elemento', 'warning');
        return;
    }
    
    $('#accionTipo').val('rechazar');
    $('#accionesMultiplesTitle').text('Rechazar Horas Extras Seleccionadas');
    $('#observacionesLabel').text('Motivo del rechazo *');
    $('#observaciones_multiples').attr('required', true);
    $('#accionButton').removeClass('btn-success').addClass('btn-danger').text('Rechazar');
    $('#accionInfo').text(`Se rechazarán ${selected.length} registros de horas extras.`);
    $('#accionesMultiplesModal').modal('show');
}

// Obtener IDs seleccionados
function getSelectedIds() {
    const selected = [];
    document.querySelectorAll('.select-item:checked').forEach(item => {
        selected.push(item.value);
    });
    return selected;
}

// Exportar
function exportarHorasExtras() {
    const form = document.getElementById('filtrosForm');
    form.action = '{{ route("admin.horas-extras.exportar") }}';
    form.submit();
}

// Formularios AJAX
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

$('#accionesMultiplesForm').submit(function(e) {
    e.preventDefault();
    
    const selectedIds = getSelectedIds();
    const accion = $('#accionTipo').val();
    const formData = $(this).serialize() + '&ids=' + JSON.stringify(selectedIds);
    
    const url = accion === 'aprobar' ? '/admin/horas-extras/aprobar-multiples' : '/admin/horas-extras/rechazar-multiples';
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#accionesMultiplesModal').modal('hide');
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

