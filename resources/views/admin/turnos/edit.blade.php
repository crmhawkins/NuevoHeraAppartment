@extends('layouts.appAdmin')

@section('title', 'Editar Turno')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit me-2"></i>Editar Turno
                    </h3>
                </div>
                
                <form action="{{ route('gestion.turnos.update', $turno) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Empleada</label>
                                    <input type="text" class="form-control" value="{{ $turno->user->name }}" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Fecha</label>
                                    <input type="text" class="form-control" value="{{ $turno->fecha->format('d/m/Y') }}" readonly>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="hora_inicio" class="form-label">Hora Inicio <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control @error('hora_inicio') is-invalid @enderror" 
                                                   id="hora_inicio" name="hora_inicio" value="{{ old('hora_inicio', $turno->hora_inicio->format('H:i')) }}" required>
                                            @error('hora_inicio')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="hora_fin" class="form-label">Hora Fin <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control @error('hora_fin') is-invalid @enderror" 
                                                   id="hora_fin" name="hora_fin" value="{{ old('hora_fin', $turno->hora_fin->format('H:i')) }}" required>
                                            @error('hora_fin')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
                                        <option value="programado" {{ old('estado', $turno->estado) == 'programado' ? 'selected' : '' }}>Programado</option>
                                        <option value="en_progreso" {{ old('estado', $turno->estado) == 'en_progreso' ? 'selected' : '' }}>En Progreso</option>
                                        <option value="completado" {{ old('estado', $turno->estado) == 'completado' ? 'selected' : '' }}>Completado</option>
                                        <option value="ausente" {{ old('estado', $turno->estado) == 'ausente' ? 'selected' : '' }}>Ausente</option>
                                    </select>
                                    @error('estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                  id="observaciones" name="observaciones" rows="3">{{ old('observaciones', $turno->observaciones) }}</textarea>
                        @error('observaciones')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Información de generación inteligente -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Turno generado inteligentemente:</strong> Este turno fue creado usando la lógica de generación automática que considera vacaciones, prioridades y horarios.
                    </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Estadísticas del Turno</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="card bg-primary text-white text-center mb-2">
                                            <div class="card-body py-2">
                                                <h6 class="mb-0">{{ $turno->total_tareas }}</h6>
                                                <small>Total Tareas</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card bg-success text-white text-center mb-2">
                                            <div class="card-body py-2">
                                                <h6 class="mb-0">{{ $turno->tareas_completadas }}</h6>
                                                <small>Completadas</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="card bg-warning text-white text-center mb-2">
                                            <div class="card-body py-2">
                                                <h6 class="mb-0">{{ $turno->tareas_pendientes }}</h6>
                                                <small>Pendientes</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card bg-info text-white text-center mb-2">
                                            <div class="card-body py-2">
                                                <h6 class="mb-0">{{ $turno->tiempo_estimado_total_formateado }}</h6>
                                                <small>Tiempo Est.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <h6>Progreso</h6>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $turno->progreso }}%">
                                            {{ $turno->progreso }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('gestion.turnos.show', $turno) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Actualizar Turno
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- NUEVA SECCIÓN: Gestión de Tareas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>Gestión de Tareas del Turno
                    </h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" onclick="addNewTask()">
                            <i class="fas fa-plus me-1"></i>Añadir Tarea
                        </button>
                        <button class="btn btn-info btn-sm" onclick="reordenarTareas()">
                            <i class="fas fa-sort me-1"></i>Reordenar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($turno->tareasAsignadas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped" id="tareasTable">
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
                                        <tr class="{{ $tarea->estado === 'completada' ? 'table-success' : ($tarea->estado === 'en_progreso' ? 'table-warning' : '') }}" data-task-id="{{ $tarea->id }}">
                                            <td>{{ $tarea->orden_ejecucion }}</td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input task-checkbox" 
                                                           type="checkbox" 
                                                           data-task-id="{{ $tarea->id }}"
                                                           {{ $tarea->estado === 'completada' ? 'checked' : '' }}>
                                                </div>
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
                                                    <button class="btn btn-outline-primary" onclick="editTask({{ $tarea->id }})" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-info" onclick="viewTaskDetails({{ $tarea->id }})" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="deleteTask({{ $tarea->id }})" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay tareas asignadas a este turno</h5>
                            <button class="btn btn-primary" onclick="addNewTask()">
                                <i class="fas fa-plus me-1"></i>Añadir Primera Tarea
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para añadir/editar tarea -->
    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Añadir Tarea al Turno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="taskForm">
                    <div class="modal-body">
                        <input type="hidden" id="taskId" name="task_id">
                        <input type="hidden" name="turno_id" value="{{ $turno->id }}">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo_tarea_id" class="form-label">Tipo de Tarea *</label>
                                    <select class="form-select" id="tipo_tarea_id" name="tipo_tarea_id" required>
                                        <option value="">Seleccionar tipo de tarea</option>
                                        @foreach($tiposTareas as $tipo)
                                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }} ({{ $tipo->tiempo_estimado_minutos }}min)</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="orden_ejecucion" class="form-label">Orden de Ejecución *</label>
                                    <input type="number" class="form-control" id="orden_ejecucion" name="orden_ejecucion" 
                                           min="1" value="{{ $turno->tareasAsignadas->count() + 1 }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="apartamento_id" class="form-label">Apartamento</label>
                                    <select class="form-select" id="apartamento_id" name="apartamento_id">
                                        <option value="">Seleccionar apartamento</option>
                                        @foreach(\App\Models\Apartamento::all() as $apartamento)
                                            <option value="{{ $apartamento->id }}">{{ $apartamento->titulo }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="zona_comun_id" class="form-label">Zona Común</label>
                                    <select class="form-select" id="zona_comun_id" name="zona_comun_id">
                                        <option value="">Seleccionar zona común</option>
                                        @foreach(\App\Models\ZonaComun::where('activo', true)->get() as $zona)
                                            <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="prioridad_calculada" class="form-label">Prioridad (1-10)</label>
                            <input type="number" class="form-control" id="prioridad_calculada" name="prioridad_calculada" 
                                   min="1" max="10" value="5">
                        </div>
                        
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Tarea</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de tarea -->
<div class="modal fade" id="tareaDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de la Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tareaDetailsContent">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Gestión de tareas
function addNewTask() {
    // Limpiar el formulario
    document.getElementById('taskForm').reset();
    document.getElementById('taskId').value = '';
    
    // Cambiar el título del modal
    document.querySelector('#addTaskModal .modal-title').textContent = 'Añadir Tarea al Turno';
    
    // Establecer el orden por defecto
    document.getElementById('orden_ejecucion').value = {{ $turno->tareasAsignadas->count() + 1 }};
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('addTaskModal'));
    modal.show();
}

function editTask(taskId) {
    // Obtener datos de la tarea
    fetch(`/admin/turnos/tareas/${taskId}/edit`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Llenar el modal de edición con los datos
                document.getElementById('taskId').value = data.tarea.id;
                document.getElementById('tipo_tarea_id').value = data.tarea.tipo_tarea_id;
                document.getElementById('apartamento_id').value = data.tarea.apartamento_id || '';
                document.getElementById('zona_comun_id').value = data.tarea.zona_comun_id || '';
                document.getElementById('orden_ejecucion').value = data.tarea.orden_ejecucion;
                document.getElementById('prioridad_calculada').value = data.tarea.prioridad_calculada;
                document.getElementById('observaciones').value = data.tarea.observaciones || '';
                
                // Cambiar el título del modal
                document.querySelector('#addTaskModal .modal-title').textContent = 'Editar Tarea';
                
                // Mostrar el modal
                const modal = new bootstrap.Modal(document.getElementById('addTaskModal'));
                modal.show();
            } else {
                alert('Error al cargar los datos de la tarea');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos de la tarea');
        });
}

function viewTaskDetails(taskId) {
    // Obtener detalles de la tarea
    fetch(`/admin/turnos/tareas/${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar modal con detalles
                const modal = new bootstrap.Modal(document.getElementById('tareaDetailsModal'));
                
                // Llenar el contenido del modal
                document.getElementById('tareaDetailsContent').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6><strong>Tipo de Tarea:</strong></h6>
                            <p>${data.tarea.tipo_tarea_nombre || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><strong>Elemento:</strong></h6>
                            <p>${data.tarea.elemento_nombre || 'N/A'}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6><strong>Tiempo Estimado:</strong></h6>
                            <p>${data.tarea.tiempo_estimado_formateado || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6><strong>Tiempo Real:</strong></h6>
                            <p>${data.tarea.tiempo_real_formateado || 'No completada'}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6><strong>Prioridad:</strong></h6>
                            <p><span class="badge badge-${data.tarea.prioridad_calculada >= 8 ? 'danger' : (data.tarea.prioridad_calculada >= 5 ? 'warning' : 'info')}">${data.tarea.prioridad_calculada}</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6><strong>Estado:</strong></h6>
                            <p>${data.tarea.completada ? 'Completada' : 'Pendiente'}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6><strong>Observaciones:</strong></h6>
                            <p>${data.tarea.observaciones || 'Sin observaciones'}</p>
                        </div>
                    </div>
                `;
                
                modal.show();
            } else {
                alert('Error al cargar los detalles de la tarea');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles de la tarea');
        });
}

function deleteTask(taskId) {
    Swal.fire({
        title: '¿Eliminar Tarea?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/turnos/tareas/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Eliminada', 'La tarea ha sido eliminada', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Error al eliminar la tarea', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al eliminar la tarea', 'error');
            });
        }
    });
}

function reordenarTareas() {
    // Crear modal de reordenamiento
    const modalHtml = `
        <div class="modal fade" id="reordenarModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reordenar Tareas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Arrastra las tareas para reordenarlas:</p>
                        <div id="tareasList" class="list-group">
                            <!-- Las tareas se cargarán aquí -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarReordenamiento()">Guardar Orden</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Agregar modal al DOM si no existe
    if (!document.getElementById('reordenarModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    // Cargar tareas en el modal
    cargarTareasParaReordenar();
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('reordenarModal'));
    modal.show();
}

function cargarTareasParaReordenar() {
    const tareasList = document.getElementById('tareasList');
    const tareas = [];
    
    // Obtener tareas de la tabla
    document.querySelectorAll('#tareasTable tbody tr').forEach((row, index) => {
        const taskId = row.dataset.taskId;
        const tarea = row.cells[2].textContent.trim();
        const elemento = row.cells[3].textContent.trim();
        const prioridad = row.cells[6].textContent.trim();
        
        tareas.push({
            id: taskId,
            tarea: tarea,
            elemento: elemento,
            prioridad: prioridad,
            orden: index + 1
        });
    });
    
    // Crear elementos de lista ordenable
    tareasList.innerHTML = tareas.map(tarea => `
        <div class="list-group-item d-flex justify-content-between align-items-center" data-task-id="${tarea.id}">
            <div class="d-flex align-items-center">
                <i class="fas fa-grip-vertical text-muted me-3" style="cursor: move;"></i>
                <div>
                    <h6 class="mb-1">${tarea.tarea}</h6>
                    <small class="text-muted">${tarea.elemento} - Prioridad: ${tarea.prioridad}</small>
                </div>
            </div>
            <span class="badge bg-primary">${tarea.orden}</span>
        </div>
    `).join('');
    
    // Hacer la lista ordenable
    makeSortable();
}

function makeSortable() {
    const tareasList = document.getElementById('tareasList');
    let draggedElement = null;
    
    tareasList.addEventListener('dragstart', function(e) {
        draggedElement = e.target.closest('.list-group-item');
        e.target.style.opacity = '0.5';
    });
    
    tareasList.addEventListener('dragend', function(e) {
        e.target.style.opacity = '1';
        draggedElement = null;
    });
    
    tareasList.addEventListener('dragover', function(e) {
        e.preventDefault();
        const afterElement = getDragAfterElement(tareasList, e.clientY);
        if (afterElement == null) {
            tareasList.appendChild(draggedElement);
        } else {
            tareasList.insertBefore(draggedElement, afterElement);
        }
    });
    
    // Hacer elementos arrastrables
    tareasList.querySelectorAll('.list-group-item').forEach(item => {
        item.draggable = true;
    });
    
    // Actualizar números de orden
    actualizarNumerosOrden();
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.list-group-item:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function actualizarNumerosOrden() {
    const tareas = document.querySelectorAll('#tareasList .list-group-item');
    tareas.forEach((tarea, index) => {
        const badge = tarea.querySelector('.badge');
        badge.textContent = index + 1;
    });
}

function guardarReordenamiento() {
    const tareas = [];
    document.querySelectorAll('#tareasList .list-group-item').forEach((item, index) => {
        tareas.push({
            id: item.dataset.taskId,
            orden: index + 1
        });
    });
    
    fetch(`/admin/turnos/{{ $turno->id }}/reordenar-tareas`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ tareas: tareas })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', 'El orden de las tareas ha sido actualizado', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Error al reordenar las tareas', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error al reordenar las tareas', 'error');
    });
}

// Formulario de tareas
document.getElementById('taskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    const taskId = document.getElementById('taskId').value;
    
    // Determinar si es edición o creación
    const isEdit = taskId && taskId !== '';
    const url = isEdit ? `/admin/turnos/tareas/${taskId}` : '/admin/turnos/tareas';
    const method = isEdit ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error al guardar la tarea: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar la tarea');
    });
});

// Checkbox de tareas
document.querySelectorAll('.task-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const taskId = this.dataset.taskId;
        const estado = this.checked ? 'completada' : 'pendiente';
        
        fetch(`/admin/turnos/tareas/${taskId}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ estado: estado })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al actualizar el estado de la tarea');
            }
        });
    });
});
</script>
@endsection
