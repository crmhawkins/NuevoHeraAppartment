@extends('layouts.appAdmin')

@section('title', 'Panel de Turnos - Drag & Drop')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .turnos-panel { font-size: 12px; }
    .turnos-panel .top-bar {
        background: #0891b2;
        color: #fff;
        padding: 8px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 6px 6px 0 0;
    }
    .turnos-panel .top-bar input[type="date"] {
        font-size: 12px;
        padding: 3px 8px;
        border: 1px solid rgba(255,255,255,.4);
        border-radius: 4px;
        background: rgba(255,255,255,.15);
        color: #fff;
    }
    .turnos-panel .top-bar input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }
    .turnos-panel .top-bar .btn-sm {
        font-size: 11px;
        padding: 3px 10px;
    }
    .turnos-panel .top-bar a { color: rgba(255,255,255,.85); text-decoration: none; font-size: 11px; }
    .turnos-panel .top-bar a:hover { color: #fff; }

    .panel-body {
        display: flex;
        gap: 0;
        overflow-x: auto;
        min-height: 500px;
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 6px 6px;
        background: #f8f9fa;
    }

    /* Sidebar: task types */
    .task-sidebar {
        min-width: 200px;
        max-width: 200px;
        background: #fff;
        border-right: 2px solid #dee2e6;
        padding: 8px;
        overflow-y: auto;
    }
    .task-sidebar h6 {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #0891b2;
        margin-bottom: 8px;
        padding-bottom: 4px;
        border-bottom: 2px solid #0891b2;
    }
    .task-type-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-left: 3px solid #6b7280;
        border-radius: 4px;
        padding: 6px 8px;
        margin-bottom: 4px;
        cursor: grab;
        font-size: 11px;
        transition: box-shadow .15s, transform .15s;
        user-select: none;
    }
    .task-type-card:active { cursor: grabbing; }
    .task-type-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,.12); transform: translateY(-1px); }
    .task-type-card.dragging { opacity: .5; }
    .task-type-card .tt-name { font-weight: 600; }
    .task-type-card .tt-meta { color: #6b7280; font-size: 10px; }
    .task-type-card[data-prioridad="1"] { border-left-color: #dc2626; }
    .task-type-card[data-prioridad="2"] { border-left-color: #f59e0b; }
    .task-type-card[data-prioridad="3"] { border-left-color: #6b7280; }

    /* Cleaner columns */
    .columns-area {
        display: flex;
        flex: 1;
        gap: 0;
        overflow-x: auto;
    }
    .cleaner-column {
        min-width: 240px;
        flex: 1;
        border-right: 1px solid #dee2e6;
        display: flex;
        flex-direction: column;
    }
    .cleaner-column:last-child { border-right: none; }
    .cleaner-header {
        background: #fff;
        padding: 8px 10px;
        border-bottom: 2px solid #0891b2;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .cleaner-header .cleaner-name {
        font-weight: 700;
        font-size: 12px;
        color: #1e293b;
    }
    .cleaner-header .cleaner-hours {
        font-size: 11px;
        color: #6b7280;
    }
    .cleaner-header .hours-bar {
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        margin-top: 4px;
        overflow: hidden;
    }
    .cleaner-header .hours-bar-fill {
        height: 100%;
        background: #0891b2;
        border-radius: 2px;
        transition: width .3s;
    }
    .cleaner-header .hours-bar-fill.over { background: #dc2626; }

    .task-list {
        flex: 1;
        padding: 6px;
        min-height: 100px;
        transition: background .2s;
    }
    .task-list.drag-over { background: rgba(8,145,178,.08); }

    .task-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 6px 8px;
        margin-bottom: 4px;
        cursor: grab;
        font-size: 11px;
        transition: box-shadow .15s, transform .15s;
        user-select: none;
        position: relative;
    }
    .task-card:active { cursor: grabbing; }
    .task-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,.12); }
    .task-card.dragging { opacity: .4; transform: scale(.95); }
    .task-card.no-drag { cursor: default; opacity: .7; }

    .task-card .tc-priority {
        display: inline-block;
        width: 4px;
        height: 100%;
        position: absolute;
        left: 0;
        top: 0;
        border-radius: 4px 0 0 4px;
    }
    .task-card .tc-priority.p-high { background: #dc2626; }
    .task-card .tc-priority.p-medium { background: #f59e0b; }
    .task-card .tc-priority.p-low { background: #6b7280; }

    .task-card .tc-body { padding-left: 6px; }
    .task-card .tc-name { font-weight: 600; color: #1e293b; }
    .task-card .tc-location { color: #6b7280; font-size: 10px; }
    .task-card .tc-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 3px;
    }
    .task-card .tc-duration { color: #0891b2; font-size: 10px; font-weight: 600; }
    .task-card .tc-badge {
        display: inline-block;
        padding: 1px 5px;
        border-radius: 3px;
        font-size: 9px;
        font-weight: 700;
        color: #fff;
    }
    .tc-badge.bg-p1 { background: #dc2626; }
    .tc-badge.bg-p2 { background: #f59e0b; }
    .tc-badge.bg-p3 { background: #6b7280; }
    .task-card .tc-remove {
        position: absolute;
        top: 2px;
        right: 4px;
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        font-size: 13px;
        line-height: 1;
        padding: 2px;
    }
    .task-card .tc-remove:hover { color: #dc2626; }

    .task-card .tc-estado {
        display: inline-block;
        padding: 1px 5px;
        border-radius: 3px;
        font-size: 9px;
        font-weight: 600;
    }
    .tc-estado.estado-pendiente { background: #fef3c7; color: #92400e; }
    .tc-estado.estado-en_progreso { background: #dbeafe; color: #1d4ed8; }
    .tc-estado.estado-completada { background: #d1fae5; color: #065f46; }

    /* Empty state */
    .task-list-empty {
        text-align: center;
        padding: 20px 10px;
        color: #9ca3af;
        font-size: 11px;
    }

    /* Modal */
    .modal-panel {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,.4);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .modal-panel.active { display: flex; }
    .modal-panel .modal-box {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        min-width: 340px;
        max-width: 420px;
        box-shadow: 0 8px 30px rgba(0,0,0,.2);
    }
    .modal-panel .modal-box h5 { font-size: 14px; font-weight: 700; margin-bottom: 12px; }
    .modal-panel .modal-box select {
        width: 100%;
        padding: 6px 10px;
        font-size: 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 12px;
    }
    .modal-panel .modal-box .btn-row {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    /* No shifts message */
    .no-shifts {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 1;
        padding: 40px;
        color: #6b7280;
        font-size: 13px;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="turnos-panel">
    {{-- Top Bar --}}
    <div class="top-bar">
        <a href="{{ route('gestion.turnos.index') }}">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <span style="font-weight:700;font-size:13px;">
            <i class="bi bi-grid-3x3-gap"></i> Panel de Turnos
        </span>
        <input type="date" id="fechaSelector" value="{{ $fecha }}">
        <button class="btn btn-sm btn-outline-light" id="btnHoy" title="Ir a hoy">Hoy</button>
        <button class="btn btn-sm btn-warning" id="btnRegenerar" title="Regenerar turnos para esta fecha">
            <i class="bi bi-arrow-clockwise"></i> Regenerar
        </button>
        <span style="flex:1;"></span>
        <span id="statusMsg" style="font-size:10px;opacity:.7;"></span>
    </div>

    {{-- Panel Body --}}
    <div class="panel-body">
        {{-- Sidebar: Available task types --}}
        <div class="task-sidebar">
            <h6><i class="bi bi-list-task"></i> Tareas disponibles</h6>
            @foreach($tiposTarea as $tipo)
                @php
                    $pNivel = $tipo->prioridad_base >= 8 ? 1 : ($tipo->prioridad_base >= 4 ? 2 : 3);
                @endphp
                <div class="task-type-card"
                     draggable="true"
                     data-tipo-tarea-id="{{ $tipo->id }}"
                     data-nombre="{{ $tipo->nombre }}"
                     data-requiere-apartamento="{{ $tipo->requiere_apartamento ? '1' : '0' }}"
                     data-requiere-zona-comun="{{ $tipo->requiere_zona_comun ? '1' : '0' }}"
                     data-tiempo="{{ $tipo->tiempo_estimado_minutos }}"
                     data-prioridad="{{ $pNivel }}"
                     data-prioridad-base="{{ $tipo->prioridad_base }}">
                    <div class="tt-name">{{ $tipo->nombre }}</div>
                    <div class="tt-meta">
                        <i class="bi bi-clock"></i> {{ $tipo->tiempo_estimado_formateado }}
                        &middot;
                        P{{ $pNivel }}
                        @if($tipo->requiere_apartamento)
                            &middot; <i class="bi bi-house"></i>
                        @elseif($tipo->requiere_zona_comun)
                            &middot; <i class="bi bi-building"></i>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Cleaner columns --}}
        <div class="columns-area">
            @if($turnos->isEmpty())
                <div class="no-shifts">
                    <div>
                        <i class="bi bi-calendar-x" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                        No hay turnos para esta fecha.<br>
                        Pulsa <strong>Regenerar</strong> para crear turnos.
                    </div>
                </div>
            @else
                @foreach($turnos as $turno)
                    @php
                        $tiempoTotal = $turno->tareasAsignadas->sum(fn($t) => $t->tipoTarea->tiempo_estimado_minutos ?? 0);
                        $jornadaMin = $turno->jornada_contratada;
                        $hUsadas = floor($tiempoTotal / 60);
                        $mUsados = $tiempoTotal % 60;
                        $hJornada = floor($jornadaMin / 60);
                        $pct = $jornadaMin > 0 ? min(100, round(($tiempoTotal / $jornadaMin) * 100)) : 0;
                        $over = $tiempoTotal > $jornadaMin;
                    @endphp
                    <div class="cleaner-column" data-turno-id="{{ $turno->id }}">
                        <div class="cleaner-header">
                            <div class="cleaner-name">
                                <i class="bi bi-person-fill"></i>
                                {{ $turno->user->name ?? 'Sin asignar' }}
                            </div>
                            <div class="cleaner-hours">
                                <span class="hours-text" data-turno-id="{{ $turno->id }}">
                                    {{ $hUsadas }}h {{ $mUsados }}min / {{ $hJornada }}h
                                </span>
                                @if($over)
                                    <span style="color:#dc2626;font-weight:700;"> (excede)</span>
                                @endif
                            </div>
                            <div class="hours-bar">
                                <div class="hours-bar-fill {{ $over ? 'over' : '' }}"
                                     style="width:{{ $pct }}%"
                                     data-turno-id="{{ $turno->id }}"></div>
                            </div>
                        </div>
                        <div class="task-list" data-turno-id="{{ $turno->id }}">
                            @forelse($turno->tareasAsignadas->sortBy('orden_ejecucion') as $tarea)
                                @php
                                    $tp = $tarea->prioridad_calculada;
                                    $pClass = $tp >= 8 ? 'p-high' : ($tp >= 4 ? 'p-medium' : 'p-low');
                                    $pLabel = $tp >= 8 ? 'P1' : ($tp >= 4 ? 'P2' : 'P3');
                                    $pBg = $tp >= 8 ? 'bg-p1' : ($tp >= 4 ? 'bg-p2' : 'bg-p3');
                                    $isDraggable = $tarea->estado === 'pendiente';
                                @endphp
                                <div class="task-card {{ $isDraggable ? '' : 'no-drag' }}"
                                     draggable="{{ $isDraggable ? 'true' : 'false' }}"
                                     data-tarea-id="{{ $tarea->id }}"
                                     data-tiempo="{{ $tarea->tipoTarea->tiempo_estimado_minutos ?? 0 }}">
                                    <span class="tc-priority {{ $pClass }}"></span>
                                    @if($isDraggable)
                                        <button class="tc-remove" data-tarea-id="{{ $tarea->id }}" title="Quitar tarea">&times;</button>
                                    @endif
                                    <div class="tc-body">
                                        <div class="tc-name">{{ $tarea->tipoTarea->nombre ?? '?' }}</div>
                                        <div class="tc-location">
                                            @if($tarea->apartamento)
                                                <i class="bi bi-house"></i> {{ $tarea->apartamento->titulo }}
                                            @elseif($tarea->zonaComun)
                                                <i class="bi bi-building"></i> {{ $tarea->zonaComun->nombre }}
                                            @else
                                                <i class="bi bi-dash"></i> General
                                            @endif
                                        </div>
                                        <div class="tc-footer">
                                            <span class="tc-duration">
                                                <i class="bi bi-clock"></i>
                                                {{ $tarea->tipoTarea->tiempo_estimado_formateado ?? '--' }}
                                            </span>
                                            <span class="tc-badge {{ $pBg }}">{{ $pLabel }}</span>
                                            <span class="tc-estado estado-{{ $tarea->estado }}">
                                                {{ ucfirst(str_replace('_', ' ', $tarea->estado)) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="task-list-empty">
                                    <i class="bi bi-inbox" style="font-size:20px;display:block;margin-bottom:4px;"></i>
                                    Arrastra tareas aqui
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

{{-- Modal: Select apartment/zone --}}
<div class="modal-panel" id="modalSelect">
    <div class="modal-box">
        <h5 id="modalTitle">Seleccionar apartamento</h5>
        <select id="modalSelectField">
            <option value="">-- Seleccionar --</option>
        </select>
        <div class="btn-row">
            <button class="btn btn-sm btn-secondary" id="modalCancel">Cancelar</button>
            <button class="btn btn-sm btn-primary" id="modalConfirm" style="background:#0891b2;border-color:#0891b2;">Agregar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var currentFecha = document.getElementById('fechaSelector').value;

    // Data for modal selections
    var apartamentos = @json($apartamentos->map(fn($a) => ['id' => $a->id, 'titulo' => $a->titulo]));
    var zonasComunes = @json($zonasComunes->map(fn($z) => ['id' => $z->id, 'nombre' => $z->nombre]));

    // ===== Date navigation =====
    document.getElementById('fechaSelector').addEventListener('change', function() {
        window.location.href = '{{ route("admin.turnos-panel.index") }}?fecha=' + this.value;
    });
    document.getElementById('btnHoy').addEventListener('click', function() {
        var hoy = new Date().toISOString().split('T')[0];
        window.location.href = '{{ route("admin.turnos-panel.index") }}?fecha=' + hoy;
    });

    // ===== Regenerate =====
    document.getElementById('btnRegenerar').addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generando...';
        setStatus('Regenerando turnos...');

        fetch('{{ route("admin.turnos-panel.regenerar") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ fecha: currentFecha })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                setStatus('Turnos regenerados');
                window.location.reload();
            } else {
                setStatus('Error al regenerar');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Regenerar';
            }
        })
        .catch(function() {
            setStatus('Error de red');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Regenerar';
        });
    });

    // ===== Drag & Drop: Task type cards (sidebar) =====
    var taskTypeCards = document.querySelectorAll('.task-type-card');
    taskTypeCards.forEach(function(card) {
        card.addEventListener('dragstart', function(e) {
            card.classList.add('dragging');
            e.dataTransfer.setData('drag_type', 'new_task');
            e.dataTransfer.setData('tipo_tarea_id', card.dataset.tipoTareaId);
            e.dataTransfer.setData('requiere_apartamento', card.dataset.requiereApartamento);
            e.dataTransfer.setData('requiere_zona_comun', card.dataset.requiereZonaComun);
            e.dataTransfer.effectAllowed = 'copy';
        });
        card.addEventListener('dragend', function() {
            card.classList.remove('dragging');
        });
    });

    // ===== Drag & Drop: Existing task cards =====
    function initTaskCardDrag(card) {
        if (card.classList.contains('no-drag')) return;
        card.addEventListener('dragstart', function(e) {
            card.classList.add('dragging');
            e.dataTransfer.setData('drag_type', 'move_task');
            e.dataTransfer.setData('tarea_id', card.dataset.tareaId);
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', function() {
            card.classList.remove('dragging');
        });
    }
    document.querySelectorAll('.task-card').forEach(initTaskCardDrag);

    // ===== Drop zones: task-list =====
    var taskLists = document.querySelectorAll('.task-list');
    taskLists.forEach(function(list) {
        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            list.classList.add('drag-over');
            e.dataTransfer.dropEffect = 'move';
        });
        list.addEventListener('dragleave', function(e) {
            if (!list.contains(e.relatedTarget)) {
                list.classList.remove('drag-over');
            }
        });
        list.addEventListener('drop', function(e) {
            e.preventDefault();
            list.classList.remove('drag-over');
            var turnoId = list.dataset.turnoId;
            var dragType = e.dataTransfer.getData('drag_type');

            if (dragType === 'new_task') {
                handleNewTaskDrop(turnoId, e.dataTransfer);
            } else if (dragType === 'move_task') {
                handleMoveTask(e.dataTransfer.getData('tarea_id'), turnoId);
            }
        });
    });

    // ===== Handle new task drop =====
    function handleNewTaskDrop(turnoId, dt) {
        var tipoTareaId = dt.getData('tipo_tarea_id');
        var reqApt = dt.getData('requiere_apartamento') === '1';
        var reqZona = dt.getData('requiere_zona_comun') === '1';

        if (reqApt) {
            showModal('Seleccionar apartamento', apartamentos, 'titulo', function(selectedId) {
                agregarTarea(turnoId, tipoTareaId, selectedId, null);
            });
        } else if (reqZona) {
            showModal('Seleccionar zona comun', zonasComunes, 'nombre', function(selectedId) {
                agregarTarea(turnoId, tipoTareaId, null, selectedId);
            });
        } else {
            agregarTarea(turnoId, tipoTareaId, null, null);
        }
    }

    // ===== Modal logic =====
    var modal = document.getElementById('modalSelect');
    var modalSelect = document.getElementById('modalSelectField');
    var modalCallback = null;

    function showModal(title, items, labelField, callback) {
        document.getElementById('modalTitle').textContent = title;
        modalSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
        items.forEach(function(item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item[labelField];
            modalSelect.appendChild(opt);
        });
        modalCallback = callback;
        modal.classList.add('active');
    }

    document.getElementById('modalCancel').addEventListener('click', function() {
        modal.classList.remove('active');
        modalCallback = null;
    });
    document.getElementById('modalConfirm').addEventListener('click', function() {
        var val = modalSelect.value;
        if (!val) { modalSelect.style.borderColor = '#dc2626'; return; }
        modalSelect.style.borderColor = '#dee2e6';
        modal.classList.remove('active');
        if (modalCallback) modalCallback(val);
        modalCallback = null;
    });
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
            modalCallback = null;
        }
    });

    // ===== AJAX: Add task =====
    function agregarTarea(turnoId, tipoTareaId, aptId, zonaId) {
        setStatus('Agregando tarea...');
        var body = {
            turno_id: turnoId,
            tipo_tarea_id: tipoTareaId,
            apartamento_id: aptId,
            zona_comun_id: zonaId
        };
        fetch('{{ route("admin.turnos-panel.agregarTarea") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                setStatus('Tarea agregada');
                appendTaskCard(turnoId, data.tarea);
                updateColumnHours(turnoId);
            } else {
                setStatus('Error: ' + (data.message || 'desconocido'));
            }
        })
        .catch(function() { setStatus('Error de red'); });
    }

    // ===== AJAX: Move task =====
    function handleMoveTask(tareaId, turnoDestinoId) {
        setStatus('Moviendo tarea...');
        fetch('{{ route("admin.turnos-panel.moverTarea") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ tarea_id: tareaId, turno_destino_id: turnoDestinoId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                setStatus('Tarea movida');
                // Move DOM element
                var card = document.querySelector('.task-card[data-tarea-id="' + tareaId + '"]');
                var destList = document.querySelector('.task-list[data-turno-id="' + turnoDestinoId + '"]');
                if (card && destList) {
                    // Remove empty state if present
                    var empty = destList.querySelector('.task-list-empty');
                    if (empty) empty.remove();
                    // Get source turno for updating hours
                    var srcList = card.closest('.task-list');
                    var srcTurnoId = srcList ? srcList.dataset.turnoId : null;
                    destList.appendChild(card);
                    updateColumnHours(turnoDestinoId);
                    if (srcTurnoId && srcTurnoId !== turnoDestinoId) {
                        updateColumnHours(srcTurnoId);
                        // Add empty state back if needed
                        if (srcList.querySelectorAll('.task-card').length === 0) {
                            srcList.innerHTML = '<div class="task-list-empty"><i class="bi bi-inbox" style="font-size:20px;display:block;margin-bottom:4px;"></i>Arrastra tareas aqui</div>';
                        }
                    }
                }
            } else {
                setStatus('Error: ' + (data.message || 'no se pudo mover'));
            }
        })
        .catch(function() { setStatus('Error de red'); });
    }

    // ===== AJAX: Remove task =====
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.tc-remove');
        if (!btn) return;
        var tareaId = btn.dataset.tareaId;
        if (!confirm('Quitar esta tarea del turno?')) return;

        setStatus('Quitando tarea...');
        fetch('{{ route("admin.turnos-panel.quitarTarea", ["id" => "__ID__"]) }}'.replace('__ID__', tareaId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                setStatus('Tarea eliminada');
                var card = document.querySelector('.task-card[data-tarea-id="' + tareaId + '"]');
                if (card) {
                    var list = card.closest('.task-list');
                    var turnoId = list ? list.dataset.turnoId : null;
                    card.remove();
                    if (turnoId) {
                        updateColumnHours(turnoId);
                        if (list.querySelectorAll('.task-card').length === 0) {
                            list.innerHTML = '<div class="task-list-empty"><i class="bi bi-inbox" style="font-size:20px;display:block;margin-bottom:4px;"></i>Arrastra tareas aqui</div>';
                        }
                    }
                }
            } else {
                setStatus('Error: ' + (data.message || 'no se pudo quitar'));
            }
        })
        .catch(function() { setStatus('Error de red'); });
    });

    // ===== Append task card to DOM =====
    function appendTaskCard(turnoId, tarea) {
        var list = document.querySelector('.task-list[data-turno-id="' + turnoId + '"]');
        if (!list) return;
        // Remove empty state
        var empty = list.querySelector('.task-list-empty');
        if (empty) empty.remove();

        var tp = tarea.prioridad_calculada || 0;
        var pClass = tp >= 8 ? 'p-high' : (tp >= 4 ? 'p-medium' : 'p-low');
        var pLabel = tp >= 8 ? 'P1' : (tp >= 4 ? 'P2' : 'P3');
        var pBg = tp >= 8 ? 'bg-p1' : (tp >= 4 ? 'bg-p2' : 'bg-p3');
        var tiempoMin = (tarea.tipo_tarea && tarea.tipo_tarea.tiempo_estimado_minutos) || 0;
        var tiempoStr = formatMinutos(tiempoMin);
        var nombre = (tarea.tipo_tarea && tarea.tipo_tarea.nombre) || '?';
        var location = '';
        if (tarea.apartamento) {
            location = '<i class="bi bi-house"></i> ' + escapeHtml(tarea.apartamento.titulo);
        } else if (tarea.zona_comun) {
            location = '<i class="bi bi-building"></i> ' + escapeHtml(tarea.zona_comun.nombre);
        } else {
            location = '<i class="bi bi-dash"></i> General';
        }

        var html = '<div class="task-card" draggable="true" data-tarea-id="' + tarea.id + '" data-tiempo="' + tiempoMin + '">' +
            '<span class="tc-priority ' + pClass + '"></span>' +
            '<button class="tc-remove" data-tarea-id="' + tarea.id + '" title="Quitar tarea">&times;</button>' +
            '<div class="tc-body">' +
            '<div class="tc-name">' + escapeHtml(nombre) + '</div>' +
            '<div class="tc-location">' + location + '</div>' +
            '<div class="tc-footer">' +
            '<span class="tc-duration"><i class="bi bi-clock"></i> ' + tiempoStr + '</span>' +
            '<span class="tc-badge ' + pBg + '">' + pLabel + '</span>' +
            '<span class="tc-estado estado-pendiente">Pendiente</span>' +
            '</div></div></div>';

        var temp = document.createElement('div');
        temp.innerHTML = html;
        var card = temp.firstChild;
        list.appendChild(card);
        initTaskCardDrag(card);
    }

    // ===== Update column hours dynamically =====
    function updateColumnHours(turnoId) {
        var list = document.querySelector('.task-list[data-turno-id="' + turnoId + '"]');
        if (!list) return;
        var cards = list.querySelectorAll('.task-card');
        var totalMin = 0;
        cards.forEach(function(c) {
            totalMin += parseInt(c.dataset.tiempo || 0, 10);
        });

        var col = list.closest('.cleaner-column');
        if (!col) return;

        var hoursText = col.querySelector('.hours-text[data-turno-id="' + turnoId + '"]');
        var barFill = col.querySelector('.hours-bar-fill[data-turno-id="' + turnoId + '"]');

        if (hoursText) {
            var h = Math.floor(totalMin / 60);
            var m = totalMin % 60;
            // Parse jornada from existing text
            var match = hoursText.textContent.match(/\/\s*(\d+)h/);
            var jornadaH = match ? parseInt(match[1], 10) : 8;
            var jornadaMin = jornadaH * 60;
            hoursText.textContent = h + 'h ' + m + 'min / ' + jornadaH + 'h';

            // Update over indicator
            var parent = hoursText.parentElement;
            var overSpan = parent.querySelector('span[style*="dc2626"]');
            if (totalMin > jornadaMin) {
                if (!overSpan) {
                    overSpan = document.createElement('span');
                    overSpan.style.cssText = 'color:#dc2626;font-weight:700;';
                    overSpan.textContent = ' (excede)';
                    parent.appendChild(overSpan);
                }
            } else if (overSpan) {
                overSpan.remove();
            }
        }
        if (barFill) {
            var match2 = (hoursText ? hoursText.textContent : '').match(/\/\s*(\d+)h/);
            var jMin = match2 ? parseInt(match2[1], 10) * 60 : 480;
            var pct = jMin > 0 ? Math.min(100, Math.round((totalMin / jMin) * 100)) : 0;
            barFill.style.width = pct + '%';
            if (totalMin > jMin) {
                barFill.classList.add('over');
            } else {
                barFill.classList.remove('over');
            }
        }
    }

    // ===== Utilities =====
    function setStatus(msg) {
        var el = document.getElementById('statusMsg');
        if (el) {
            el.textContent = msg;
            clearTimeout(el._timer);
            el._timer = setTimeout(function() { el.textContent = ''; }, 3000);
        }
    }

    function formatMinutos(min) {
        var h = Math.floor(min / 60);
        var m = min % 60;
        if (h > 0 && m > 0) return h + 'h ' + m + 'm';
        if (h > 0) return h + 'h';
        return m + 'm';
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
@endpush
