@extends('layouts.appPersonal')

@section('title', 'Dashboard - Limpiadora')

@section('content')
<div class="limpiadora-dashboard">
    <!-- Mensajes de Confirmación -->
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 15px; border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <i class="fas fa-check-circle"></i>
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 15px; border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <i class="fas fa-exclamation-triangle"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>¡Buenos días, {{ Auth::user()->name }}!</h1>
            <p>{{ $datos['diaSemana'] }}, {{ $datos['hoy'] }}</p>
        </div>
        
        <!-- Estado de la Jornada -->
        <div class="jornada-status">
            @if($datos['fichajeActual'])
                <div class="jornada-active">
                    <div class="status-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="status-info">
                        <span class="status-label">Jornada activa</span>
                        <span class="status-time">Iniciada: {{ \Carbon\Carbon::parse($datos['fichajeActual']->hora_entrada)->format('H:i') }}</span>
                    </div>
                </div>
            @else
                <div class="jornada-inactive">
                    <div class="status-icon">
                        <i class="fas fa-stop-circle"></i>
                    </div>
                    <span>Jornada no iniciada</span>
                </div>
            @endif
            
            <!-- Botón de Iniciar/Finalizar Jornada -->
            @if(!$datos['fichajeActual'])
                <form action="{{ route('fichajes.iniciar') }}" method="POST" class="d-inline w-100">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg w-100 mb-4">
                        <i class="fas fa-play"></i> Iniciar Jornada
                    </button>
                </form>
            @else
                <form action="{{ route('fichajes.finalizar') }}" method="POST" class="d-inline w-100" id="finalizarForm">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-lg w-100 mb-4" id="finalizarBtn">
                        <i class="fas fa-stop"></i> Finalizar Jornada
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Estadísticas del Día -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-broom"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $datos['limpiezasHoy'] }}</div>
                <div class="stat-label">Limpiezas Hoy</div>
                <small class="stat-detail">{{ $datos['apartamentosPendientes'] }} pendientes</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $datos['limpiezasCompletadasHoy'] }}</div>
                <div class="stat-label">Completadas</div>
                <small class="stat-detail">de {{ $datos['limpiezasAsignadas'] }} asignadas</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $datos['limpiezasPendientesHoy'] }}</div>
                <div class="stat-label">En Proceso</div>
                <small class="stat-detail">trabajando</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $datos['porcentajeSemana'] }}%</div>
                <div class="stat-label">Semana</div>
                <small class="stat-detail">{{ $datos['limpiezasCompletadasSemana'] }}/{{ $datos['limpiezasSemana'] }}</small>
            </div>
        </div>
    </div>

    <!-- Tareas Asignadas para Hoy -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="apple-card-title">
                <i class="fas fa-tasks"></i>
                <span>Mis Tareas de Hoy</span>
            </div>
            @if($datos['turnoHoy'])
                <div class="apple-card-subtitle">
                    <span class="badge bg-primary">{{ $datos['turnoHoy']->estado }}</span>
                    <small class="text-muted">Ordenadas por prioridad</small>
                </div>
            @endif
        </div>
        <div class="apple-card-body">
            @if($datos['proximasLimpiezas']->count() > 0)
                <div class="tareas-list">
                    @foreach($datos['proximasLimpiezas'] as $tarea)
                        <div class="tarea-item {{ $tarea['estado'] === 'completada' ? 'completada' : ($tarea['estado'] === 'en_progreso' ? 'en-progreso' : 'pendiente') }}">
                            <div class="tarea-header">
                                <div class="tarea-info">
                                    <div class="tarea-tipo">
                                        <i class="fas fa-{{ $tarea['tipo_elemento'] === 'apartamento' ? 'home' : ($tarea['tipo_elemento'] === 'zona_comun' ? 'building' : 'cog') }}"></i>
                                        <span class="tarea-nombre">{{ $tarea['tipo_tarea'] }}</span>
                                        @if($tarea['tipo_elemento'] !== 'general')
                                            <small class="elemento-nombre">{{ $tarea['nombre_elemento'] }}</small>
                                        @endif
                                    </div>
                                    <div class="tarea-prioridad">
                                        <span class="badge bg-{{ $tarea['prioridad'] >= 8 ? 'danger' : ($tarea['prioridad'] >= 6 ? 'warning' : 'info') }}">
                                            Prioridad {{ $tarea['prioridad'] }}
                                        </span>
                                        <small class="orden-ejecucion">Orden: {{ $tarea['orden_ejecucion'] }}</small>
                                    </div>
                                </div>
                                <div class="tarea-details">
                                    <div class="tarea-tiempo">
                                        <i class="fas fa-clock"></i>
                                        <span>{{ $tarea['tiempo_estimado'] }} min</span>
                                    </div>
                                    <div class="tarea-estado">
                                        <span class="status-badge status-{{ $tarea['estado'] }}">
                                            @switch($tarea['estado'])
                                                @case('completada')
                                                    Completada
                                                    @break
                                                @case('en_progreso')
                                                    En Progreso
                                                    @break
                                                @default
                                                    Pendiente
                                            @endswitch
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @if($tarea['observaciones'])
                                <div class="tarea-observaciones">
                                    <small class="text-muted">{{ $tarea['observaciones'] }}</small>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>No hay tareas asignadas para hoy</p>
                    <small class="text-muted">Contacta con tu supervisor si crees que debería haber tareas asignadas</small>
                </div>
            @endif
        </div>
    </div>

    <!-- Incidencias Pendientes -->
    @if($datos['incidenciasPendientes']->count() > 0)
        <div class="apple-card">
            <div class="apple-card-header">
                <div class="apple-card-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Mis Incidencias Pendientes</span>
                </div>
            </div>
            <div class="apple-card-body">
                <div class="incidencias-list">
                    @foreach($datos['incidenciasPendientes'] as $incidencia)
                        <div class="incidencia-item">
                            <div class="incidencia-header">
                                <h4 class="incidencia-titulo">{{ $incidencia->titulo }}</h4>
                                <span class="incidencia-fecha">
                                    {{ \Carbon\Carbon::parse($incidencia->created_at)->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <p class="incidencia-descripcion">{{ Str::limit($incidencia->descripcion, 100) }}</p>
                            <div class="incidencia-footer">
                                <span class="incidencia-prioridad prioridad-{{ $incidencia->prioridad }}">
                                    {{ ucfirst($incidencia->prioridad) }}
                                </span>
                                <a href="{{ route('gestion.incidencias.show', $incidencia->id) }}" class="apple-btn apple-btn-info">
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Estadísticas de Calidad -->
    @if(!empty($datos['analisisRecientes']))
        <div class="apple-card">
            <div class="apple-card-header">
                <div class="apple-card-title">
                    <i class="fas fa-chart-line"></i>
                    <span>Calidad de Limpieza (Última Semana)</span>
                </div>
            </div>
            <div class="apple-card-body">
                <div class="calidad-stats">
                    @foreach($datos['analisisRecientes'] as $calidad => $total)
                        <div class="calidad-item calidad-{{ $calidad }}">
                            <div class="calidad-label">{{ ucfirst($calidad) }}</div>
                            <div class="calidad-number">{{ $total }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Overlay de Carga -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner">
            <div class="spinner"></div>
        </div>
        <div class="loading-text">
            <h3>Actualizando...</h3>
            <p>Por favor, espera mientras se procesa tu solicitud</p>
            <div class="loading-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <span class="progress-text" id="progressText">0%</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/limpiadora-dashboard.css') }}">
@endpush

@push('scripts')
<script>
// Funciones para el Overlay de Carga
function showLoadingOverlay(message = 'Actualizando...') {
    const overlay = document.getElementById('loadingOverlay');
    const messageElement = overlay.querySelector('h3');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    messageElement.textContent = message;
    progressFill.style.width = '0%';
    progressText.textContent = '0%';
    
    overlay.style.display = 'flex';
    
    // Simular progreso
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        
        progressFill.style.width = progress + '%';
        progressText.textContent = Math.round(progress) + '%';
    }, 200);
    
    overlay.dataset.progressInterval = progressInterval;
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    progressFill.style.width = '100%';
    progressText.textContent = '100%';
    
    if (overlay.dataset.progressInterval) {
        clearInterval(overlay.dataset.progressInterval);
    }
    
    setTimeout(() => {
        overlay.style.display = 'none';
    }, 500);
}

// Actualizar estadísticas cada 5 minutos
setInterval(() => {
    fetch('/limpiadora/estadisticas')
        .then(response => response.json())
        .then(data => {
            // Actualizar estadísticas en tiempo real si es necesario
            console.log('Estadísticas actualizadas:', data);
        })
        .catch(error => {
            console.error('Error actualizando estadísticas:', error);
        });
}, 300000); // 5 minutos

// Mostrar overlay al hacer clic en botones de acción
document.addEventListener('DOMContentLoaded', function() {
    const actionButtons = document.querySelectorAll('.apple-btn');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.type !== 'submit') {
                showLoadingOverlay('Procesando acción...');
            }
        });
    });
});

// Debug del formulario de finalizar jornada
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DEBUG FORMULARIO FINALIZAR JORNADA ===');
    
    const finalizarForm = document.getElementById('finalizarForm');
    if (finalizarForm) {
        console.log('✅ Formulario de finalizar jornada encontrado');
        console.log('Formulario:', finalizarForm);
        console.log('Action:', finalizarForm.action);
        console.log('Method:', finalizarForm.method);
        
        // Verificar token CSRF
        const csrfToken = finalizarForm.querySelector('input[name="_token"]');
        if (csrfToken) {
            console.log('✅ Token CSRF encontrado:', csrfToken.value);
        } else {
            console.log('❌ Token CSRF NO encontrado');
        }
        
        // Agregar event listener para submit
        finalizarForm.addEventListener('submit', function(e) {
            console.log('🚀 FORMULARIO ENVIADO - Evento submit disparado');
            console.log('Action:', this.action);
            console.log('Method:', this.method);
            
            // Mostrar mensaje de "Finalizando jornada..."
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizando...';
                submitButton.disabled = true;
            }
            
            // Verificar token CSRF
            const csrfToken = this.querySelector('input[name="_token"]');
            if (csrfToken) {
                console.log('✅ Token CSRF en submit:', csrfToken.value);
            } else {
                console.log('❌ Token CSRF NO encontrado en submit');
            }
            
            // Verificar que el botón esté habilitado
            console.log('Botón submit:', submitButton);
            console.log('Botón disabled:', submitButton.disabled);
            
            console.log('📤 Enviando formulario a:', this.action);
        });
        
        // Verificar si hay algún JavaScript que esté interfiriendo
        console.log('🔍 Verificando si hay JavaScript interfiriendo...');
        
        // Verificar si jQuery está interfiriendo
        if (typeof $ !== 'undefined') {
            console.log('jQuery detectado, verificando event handlers...');
            $(finalizarForm).off('submit').on('submit', function(e) {
                console.log('🚀 jQuery submit handler disparado');
                console.log('Evento:', e);
                return true; // Permitir envío
            });
        }
        
    } else {
        console.log('❌ Formulario de finalizar jornada NO encontrado');
    }
    
    // Verificar si hay algún error en la consola
    window.addEventListener('error', function(e) {
        console.log('❌ Error en la página:', e.error);
        console.log('Error message:', e.message);
        console.log('Error filename:', e.filename);
        console.log('Error lineno:', e.lineno);
    });
});

// Debug adicional para ver si hay algún problema con el botón
function debugBotonFinalizar() {
    console.log('=== DEBUG BOTÓN FINALIZAR ===');
    const boton = document.querySelector('button[type="submit"]');
    if (boton) {
        console.log('Botón encontrado:', boton);
        console.log('Texto del botón:', boton.textContent);
        console.log('Clases del botón:', boton.className);
        console.log('Botón visible:', boton.offsetParent !== null);
        console.log('Botón habilitado:', !boton.disabled);
        
        // Simular click
        console.log('Simulando click en el botón...');
        boton.click();
    } else {
        console.log('❌ Botón no encontrado');
    }
}

// Función para probar el formulario manualmente
function testFormulario() {
    console.log('=== TEST MANUAL FORMULARIO ===');
    const form = document.getElementById('finalizarForm');
    if (form) {
        console.log('Enviando formulario manualmente...');
        form.submit();
    } else {
        console.log('❌ Formulario no encontrado para test manual');
    }
}

// Exponer funciones para debugging en consola
window.debugBotonFinalizar = debugBotonFinalizar;
window.testFormulario = testFormulario;
</script>
@endpush
