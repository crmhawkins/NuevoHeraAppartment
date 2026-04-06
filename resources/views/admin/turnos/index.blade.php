@extends('layouts.appAdmin')

@section('title', 'Gestión de Turnos de Trabajo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-check me-2"></i>Gestión de Turnos de Trabajo
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGenerarTurnos">
                            <i class="fas fa-magic me-1"></i>Generar Turnos
                        </button>
                        <a href="{{ route('gestion.turnos.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>Crear Turno Manual
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="fecha" class="form-label">Fecha:</label>
                            <input type="date" class="form-control" id="fecha" value="{{ $fecha }}" onchange="cambiarFecha()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Acciones:</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary" onclick="cambiarFecha('{{ today()->subDay()->format('Y-m-d') }}')">
                                    <i class="fas fa-chevron-left"></i> Ayer
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="cambiarFecha('{{ today()->format('Y-m-d') }}')">
                                    Hoy
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="cambiarFecha('{{ today()->addDay()->format('Y-m-d') }}')">
                                    Mañana <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estadísticas:</label>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary">{{ $estadisticas['total_turnos'] }} Turnos</span>
                                <span class="badge bg-success">{{ $estadisticas['tareas_completadas'] }} Completadas</span>
                                <span class="badge bg-warning">{{ $estadisticas['tareas_pendientes'] }} Pendientes</span>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $estadisticas['total_turnos'] }}</h4>
                                    <p class="mb-0">Turnos Programados</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $estadisticas['total_tareas'] }}</h4>
                                    <p class="mb-0">Total Tareas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $estadisticas['tareas_completadas'] }}</h4>
                                    <p class="mb-0">Tareas Completadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4>{{ number_format($estadisticas['tiempo_estimado_total'] / 60, 1) }}h</h4>
                                    <p class="mb-0">Tiempo Estimado</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($turnos->count() > 0)
                        <!-- Lista de turnos -->
                        <div class="row">
                            @foreach($turnos as $turno)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-user me-1"></i>{{ $turno->user->name }}
                                            </h5>
                                            <span class="badge bg-{{ $turno->estado === 'completado' ? 'success' : ($turno->estado === 'en_progreso' ? 'warning' : 'primary') }}">
                                                {{ ucfirst($turno->estado) }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <small class="text-muted">Horario:</small><br>
                                                    <strong>{{ $turno->hora_inicio->format('H:i') }} - {{ $turno->hora_fin->format('H:i') }}</strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Progreso:</small><br>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" role="progressbar" style="width: {{ $turno->progreso }}%">
                                                            {{ $turno->progreso }}%
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">Tareas:</small><br>
                                                <span class="badge bg-success">{{ $turno->tareas_completadas }}</span> completadas
                                                <span class="badge bg-warning">{{ $turno->tareas_pendientes }}</span> pendientes
                                                <span class="badge bg-info">{{ $turno->total_tareas }}</span> total
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">Tiempo:</small><br>
                                                <strong>{{ $turno->tiempo_estimado_total_formateado }}</strong> 
                                                <span class="badge bg-success">dentro de jornada</span>
                                                @if($turno->sobrepasa_jornada)
                                                    <br><small class="text-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> 
                                                        {{ $turno->tiempo_real_asignado_formateado }} total asignado
                                                    </small>
                                                @endif
                                                @if($turno->tiempo_real_total)
                                                    <br><strong>{{ $turno->tiempo_real_total_formateado }}</strong> real
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <div class="btn-group w-100" role="group">
                                                <a href="{{ route('gestion.turnos.show', $turno) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <a href="{{ route('gestion.turnos.edit', $turno) }}" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                @if($turno->estado === 'programado')
                                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="iniciarTurno({{ $turno->id }})">
                                                        <i class="fas fa-play"></i> Iniciar
                                                    </button>
                                                @elseif($turno->estado === 'en_progreso')
                                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="finalizarTurno({{ $turno->id }})">
                                                        <i class="fas fa-stop"></i> Finalizar
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarTurno({{ $turno->id }})">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay turnos programados para esta fecha</h4>
                            <p class="text-muted">Genera turnos automáticamente o crea uno manualmente</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGenerarTurnos">
                                <i class="fas fa-magic me-1"></i>Generar Turnos
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para generar turnos -->
<div class="modal fade" id="modalGenerarTurnos" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-magic me-2"></i>Generar Turnos Automáticamente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formGenerarTurnos" method="POST" action="{{ route('gestion.turnos.generar') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="fecha_generar" class="form-label">Fecha para generar turnos:</label>
                        <input type="date" class="form-control" id="fecha_generar" name="fecha" value="{{ $fecha }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="forzar" name="forzar" value="1">
                            <label class="form-check-label" for="forzar">
                                Forzar regeneración (eliminar turnos existentes)
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="usar_ia" name="usar_ia" value="1">
                            <label class="form-check-label" for="usar_ia">
                                <i class="fas fa-robot me-1"></i>Usar IA para optimización (OpenAI)
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            La IA analizará todas las tareas y empleadas para generar la asignación más eficiente
                        </small>
                    </div>
                    
                    <div class="mb-3" id="opciones_ia" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="ia_real" name="tipo_ia" value="real" checked>
                            <label class="form-check-label" for="ia_real">
                                <i class="fas fa-brain me-1"></i>IA Real (OpenAI) - Requiere API Key
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="ia_simulada" name="tipo_ia" value="simulada">
                            <label class="form-check-label" for="ia_simulada">
                                <i class="fas fa-flask me-1"></i>IA Simulada - Para pruebas
                            </label>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>¿Qué hace este comando?</strong>
                        <ul class="mb-0 mt-2">
                            <li>Detecta empleadas disponibles según sus horarios</li>
                            <li>Genera tareas pendientes basadas en limpiezas programadas</li>
                            <li>Calcula prioridades dinámicas según días sin limpiar</li>
                            <li>Distribuye tareas equitativamente entre empleadas</li>
                            <li><strong>Con IA:</strong> Optimiza asignación usando algoritmos avanzados</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning" id="alerta_ia" style="display: none;">
                        <i class="fas fa-robot me-2"></i>
                        <strong>Modo IA Activado</strong>
                        <ul class="mb-0 mt-2">
                            <li>Analiza todas las tareas y empleadas disponibles</li>
                            <li>Prioriza apartamentos y zonas comunes (máxima prioridad)</li>
                            <li>Asigna mantenimiento por edificio</li>
                            <li>Optimiza distribución respetando jornadas laborales</li>
                            <li>Genera asignación más eficiente que el sistema tradicional</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-magic me-1"></i>Generar Turnos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para mostrar resultado del comando -->
<div class="modal fade" id="modalResultado" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-terminal me-2"></i>Terminal de Comando
                </h5>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-2">
                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                        Ejecutado
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="terminal-header bg-dark text-light p-2 d-flex align-items-center">
                    <div class="d-flex me-3">
                        <div class="terminal-button bg-danger me-1"></div>
                        <div class="terminal-button bg-warning me-1"></div>
                        <div class="terminal-button bg-success"></div>
                    </div>
                    <span class="text-muted">turnos:generar</span>
                </div>
                <div id="resultadoComando" class="terminal-output"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cerrar
                </button>
                <button type="button" class="btn btn-outline-primary" onclick="copiarResultado()">
                    <i class="fas fa-copy me-1"></i>Copiar Resultado
                </button>
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Actualizar Página
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .card {
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .btn {
        transition: all 0.3s ease;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .badge {
        border-radius: 20px;
        padding: 6px 12px;
        font-weight: 500;
    }
    
    .progress {
        border-radius: 10px;
        height: 8px;
    }
    
    .progress-bar {
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        color: white;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        border-radius: 15px 15px 0 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
    }
    
    .modal-header .btn-close {
        filter: invert(1);
    }
    
    .terminal-output {
        background: #1a1a1a;
        color: #00ff00;
        font-family: 'Courier New', monospace;
        border-radius: 10px;
        padding: 20px;
        max-height: 400px;
        overflow-y: auto;
        border: 2px solid #333;
    }
    
    .terminal-output::-webkit-scrollbar {
        width: 8px;
    }
    
    .terminal-output::-webkit-scrollbar-track {
        background: #333;
        border-radius: 4px;
    }
    
    .terminal-output::-webkit-scrollbar-thumb {
        background: #00ff00;
        border-radius: 4px;
    }
    
    .terminal-button {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    
    .terminal-header {
        border-bottom: 1px solid #333;
    }
    
    .btn-pulse {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .success-glow {
        box-shadow: 0 0 20px rgba(40, 167, 69, 0.5);
    }
    
    .error-glow {
        box-shadow: 0 0 20px rgba(220, 53, 69, 0.5);
    }
</style>
@endsection

@section('scripts')
<script>
// Configuración global de SweetAlert2
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

function mostrarNotificacion(tipo, titulo, mensaje) {
    Toast.fire({
        icon: tipo,
        title: titulo,
        text: mensaje
    });
}

function cambiarFecha(fecha = null) {
    if (fecha) {
        document.getElementById('fecha').value = fecha;
    }
    const fechaSeleccionada = document.getElementById('fecha').value;
    
    // Mostrar loading
    mostrarNotificacion('info', 'Cambiando fecha...', 'Cargando turnos para la nueva fecha');
    
    window.location.href = `{{ route('gestion.turnos.index') }}?fecha=${fechaSeleccionada}`;
}

function iniciarTurno(turnoId) {
    Swal.fire({
        title: '🚀 Iniciar Turno',
        html: `
            <div class="text-center">
                <i class="fas fa-play-circle fa-3x text-primary mb-3"></i>
                <p>¿Estás seguro de que quieres iniciar este turno?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Al iniciar el turno, se registrará la hora de inicio y cambiará el estado a "En Progreso"
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-play me-2"></i>Sí, Iniciar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        customClass: {
            popup: 'animate__animated animate__zoomIn',
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Iniciando turno...',
                html: '<div class="loading-spinner mx-auto"></div><p class="mt-3">Por favor espera...</p>',
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'animate__animated animate__fadeIn'
                }
            });
            
            fetch(`/gestion/turnos/${turnoId}/iniciar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Continuar',
                        customClass: {
                            popup: 'animate__animated animate__bounceIn'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        customClass: {
                            popup: 'animate__animated animate__shakeX'
                        }
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Error al iniciar el turno',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        }
    });
}

function finalizarTurno(turnoId) {
    Swal.fire({
        title: '🏁 Finalizar Turno',
        html: `
            <div class="text-center">
                <i class="fas fa-flag-checkered fa-3x text-warning mb-3"></i>
                <p>¿Cuántas horas trabajó realmente?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-clock me-2"></i>
                    Ingresa las horas reales trabajadas para un registro preciso
                </div>
            </div>
        `,
        input: 'number',
        inputAttributes: {
            min: 0,
            max: 24,
            step: 0.5,
            placeholder: 'Ej: 7.5'
        },
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-2"></i>Finalizar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        customClass: {
            popup: 'animate__animated animate__zoomIn',
            confirmButton: 'btn btn-warning',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const horasTrabajadas = result.value;
            
            if (!horasTrabajadas || horasTrabajadas < 0) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor ingresa un número válido de horas',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            
            // Mostrar loading
            Swal.fire({
                title: 'Finalizando turno...',
                html: '<div class="loading-spinner mx-auto"></div><p class="mt-3">Procesando información...</p>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            fetch(`/gestion/turnos/${turnoId}/finalizar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    horas_trabajadas: horasTrabajadas
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Turno Finalizado!',
                        html: `
                            <div class="text-center">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <p>${data.message}</p>
                                <div class="alert alert-success">
                                    <strong>Horas trabajadas:</strong> ${horasTrabajadas}h
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'Continuar',
                        customClass: {
                            popup: 'animate__animated animate__bounceIn'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Error al finalizar el turno',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        }
    });
}

// Manejar el formulario de generar turnos
document.getElementById('formGenerarTurnos').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const fecha = formData.get('fecha');
    const forzar = formData.get('forzar');
    const usarIA = formData.get('usar_ia');
    const tipoIA = formData.get('tipo_ia');
    
    // Determinar título según tipo de generación
    const titulo = usarIA ? '🤖 Generando Turnos con IA' : '🎯 Generando Turnos';
    const descripcion = usarIA ? 
        (tipoIA === 'real' ? 'Conectando con OpenAI...' : 'Ejecutando algoritmo de IA...') :
        'Analizando empleadas disponibles...';
    
    // Mostrar loading espectacular
    Swal.fire({
        title: titulo,
        html: `
            <div class="text-center">
                <div class="loading-spinner mx-auto mb-3" style="width: 40px; height: 40px; border-width: 4px;"></div>
                <p>${descripcion}</p>
                <p>Calculando prioridades de tareas...</p>
                <p>Distribuyendo trabajo equitativamente...</p>
                ${usarIA ? '<p><i class="fas fa-robot me-1"></i>Optimizando con IA...</p>' : ''}
                <div class="progress mt-3" style="height: 6px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: {
            popup: 'animate__animated animate__fadeIn'
        }
    });
    
    // Ejecutar comando
    fetch('{{ route("gestion.turnos.generar") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            // Mostrar resultado en modal espectacular
            document.getElementById('resultadoComando').innerHTML = `
                <div class="text-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Comando ejecutado exitosamente</strong>
                </div>
                <pre>${data.output || 'Turnos generados exitosamente'}</pre>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('modalResultado'));
            modal.show();
            
            // Cerrar modal de generar turnos
            const modalGenerar = bootstrap.Modal.getInstance(document.getElementById('modalGenerarTurnos'));
            modalGenerar.hide();
            
            // Mostrar notificación de éxito
            mostrarNotificacion('success', '¡Turnos Generados!', 'Los turnos se han creado exitosamente');
            
        } else {
            Swal.fire({
                title: 'Error al Generar Turnos',
                html: `
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <p>${data.message || 'Error al generar los turnos'}</p>
                        ${data.output ? `<div class="alert alert-danger mt-3"><pre class="mb-0">${data.output}</pre></div>` : ''}
                    </div>
                `,
                confirmButtonText: 'Entendido',
                customClass: {
                    popup: 'animate__animated animate__shakeX'
                }
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            title: 'Error de Conexión',
            text: 'Error al ejecutar el comando',
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
    });
});

function copiarResultado() {
    const resultado = document.getElementById('resultadoComando').textContent;
    navigator.clipboard.writeText(resultado).then(() => {
        mostrarNotificacion('success', 'Copiado', 'Resultado copiado al portapapeles');
    }).catch(() => {
        mostrarNotificacion('error', 'Error', 'No se pudo copiar el resultado');
    });
}

// Manejar opciones de IA
document.getElementById('usar_ia').addEventListener('change', function() {
    const opcionesIA = document.getElementById('opciones_ia');
    const alertaIA = document.getElementById('alerta_ia');
    
    if (this.checked) {
        opcionesIA.style.display = 'block';
        alertaIA.style.display = 'block';
    } else {
        opcionesIA.style.display = 'none';
        alertaIA.style.display = 'none';
    }
});

// Añadir animaciones a las tarjetas
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
    
    // Añadir efectos de hover a los botones
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Añadir efectos a las tarjetas de estadísticas
    const statCards = document.querySelectorAll('.card.bg-primary, .card.bg-info, .card.bg-success, .card.bg-warning');
    statCards.forEach(card => {
        card.classList.add('stat-card');
    });
});

// Función para eliminar turnos
function eliminarTurno(turnoId) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });
            
            // Realizar petición AJAX
            fetch(`/gestion/turnos/${turnoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Eliminado!',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // Recargar la página para actualizar la vista
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Hubo un problema al eliminar el turno',
                    icon: 'error'
                });
            });
        }
    });
}
</script>
@endsection
