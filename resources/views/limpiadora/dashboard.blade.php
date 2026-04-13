@extends('layouts.appPersonal')

@section('title', 'Dashboard - Limpiadora')

@section('content')
@php $ar = Auth::user()->idioma_preferido === 'ar'; @endphp
<div class="limpiadora-dashboard">
    <!-- Mensajes de Confirmación -->
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 15px; border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 15px; border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>{{ $ar ? 'صباح الخير' : '¡Buenos días' }}, {{ Auth::user()->name }}{{ $ar ? '' : '!' }}</h1>
            <p>{{ \Carbon\Carbon::now()->locale($ar ? 'ar' : 'es')->isoFormat('dddd, D/MM/YYYY') }}</p>
        </div>
        
        <!-- Estado de la Jornada -->
        <div class="jornada-status">
            @if($datos['fichajeActual'])
                <div class="jornada-active">
                    <div class="status-icon">
                        <i class="bi bi-play-circle-fill"></i>
                    </div>
                    <div class="status-info">
                        <span class="status-label">{{ $ar ? 'يوم العمل نشط' : 'Jornada activa' }}</span>
                        <span class="status-time">Iniciada: {{ \Carbon\Carbon::parse($datos['fichajeActual']->hora_entrada)->format('H:i') }}</span>
                    </div>
                </div>
            @else
                <div class="jornada-inactive">
                    <div class="status-icon">
                        <i class="bi bi-stop-circle-fill"></i>
                    </div>
                    <span>{{ $ar ? 'لم يبدأ يوم العمل' : 'Jornada no iniciada' }}</span>
                </div>
            @endif
            
            <!-- Botón de Iniciar/Finalizar Jornada -->
            @if(!$datos['fichajeActual'])
                <form action="{{ route('fichajes.iniciar') }}" method="POST" class="d-inline w-100">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg w-100 mb-4">
                        <i class="bi bi-play-fill"></i> {{ $ar ? 'بدء يوم العمل' : 'Iniciar Jornada' }}
                    </button>
                </form>
            @else
                <form action="{{ route('fichajes.finalizar') }}" method="POST" class="d-inline w-100" id="finalizarForm">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-lg w-100 mb-4" id="finalizarBtn">
                        <i class="bi bi-stop-fill"></i> {{ $ar ? 'إنهاء يوم العمل' : 'Finalizar Jornada' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Estadísticas del Día -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-brush"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $datos['limpiezasHoy'] }}</div>
                <div class="stat-label">{{ $ar ? 'تنظيفات اليوم' : 'Limpiezas Hoy' }}</div>
                <small class="stat-detail">{{ $datos['apartamentosPendientes'] }} {{ $ar ? 'معلقة' : 'pendientes' }}</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $datos['limpiezasCompletadasHoy'] }}</div>
                <div class="stat-label">{{ $ar ? 'مكتملة' : 'Completadas' }}</div>
                <small class="stat-detail">{{ $ar ? 'من' : 'de' }} {{ $datos['limpiezasAsignadas'] }} {{ $ar ? 'مخصصة' : 'asignadas' }}</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $datos['limpiezasPendientesHoy'] }}</div>
                <div class="stat-label">{{ $ar ? 'قيد التنفيذ' : 'En Proceso' }}</div>
                <small class="stat-detail">{{ $ar ? 'يعمل' : 'trabajando' }}</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-calendar-week"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">{{ $datos['porcentajeSemana'] }}%</div>
                <div class="stat-label">{{ $ar ? 'الأسبوع' : 'Semana' }}</div>
                <small class="stat-detail">{{ $datos['limpiezasCompletadasSemana'] }}/{{ $datos['limpiezasSemana'] }}</small>
            </div>
        </div>
    </div>

    <!-- Tareas Asignadas para Hoy -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="apple-card-title">
                <i class="bi bi-list-task"></i>
                <span>{{ $ar ? 'مهامي اليوم' : 'Mis Tareas de Hoy' }}</span>
            </div>
            @if($datos['turnoHoy'])
                <div class="apple-card-subtitle">
                    <span class="badge bg-primary">{{ $datos['turnoHoy']->estado }}</span>
                    <small class="text-muted">{{ $ar ? 'مرتبة حسب الأولوية' : 'Ordenadas por prioridad' }}</small>
                </div>
            @endif
        </div>
        <div class="apple-card-body">
            @if($datos['proximasLimpiezas']->count() > 0)
                <div class="tareas-list">
                    @foreach($datos['proximasLimpiezas'] as $tarea)
                        <a href="{{ route('gestion.tareas.checklist', $tarea['id']) }}" class="tarea-link" style="text-decoration: none; color: inherit; display: block;">
                        <div class="tarea-item {{ $tarea['estado'] === 'completada' ? 'completada' : ($tarea['estado'] === 'en_progreso' ? 'en-progreso' : 'pendiente') }}">
                            <div class="tarea-header">
                                <div class="tarea-info">
                                    <div class="tarea-tipo">
                                        <i class="bi bi-{{ $tarea['tipo_elemento'] === 'apartamento' ? 'house-fill' : ($tarea['tipo_elemento'] === 'zona_comun' ? 'building' : 'gear') }}"></i>
                                        <span class="tarea-nombre">{{ $tarea['tipo_tarea'] }}</span>
                                        @if($tarea['tipo_elemento'] !== 'general')
                                            <small class="elemento-nombre">{{ $tarea['nombre_elemento'] }}</small>
                                        @endif
                                    </div>
                                    <div class="tarea-prioridad">
                                        <span class="badge bg-{{ $tarea['prioridad'] >= 8 ? 'danger' : ($tarea['prioridad'] >= 6 ? 'warning' : 'info') }}">
                                            {{ $ar ? 'أولوية' : 'Prioridad' }} {{ $tarea['prioridad'] }}
                                        </span>
                                        <small class="orden-ejecucion">{{ $ar ? 'ترتيب' : 'Orden' }}: {{ $tarea['orden_ejecucion'] }}</small>
                                    </div>
                                </div>
                                <div class="tarea-details">
                                    <div class="tarea-tiempo">
                                        <i class="bi bi-clock"></i>
                                        <span>{{ $tarea['tiempo_estimado'] }} {{ $ar ? 'دقيقة' : 'min' }}</span>
                                    </div>
                                    <div class="tarea-estado">
                                        <span class="status-badge status-{{ $tarea['estado'] }}">
                                            @switch($tarea['estado'])
                                                @case('completada')
                                                    {{ $ar ? 'مكتملة' : 'Completada' }}
                                                    @break
                                                @case('en_progreso')
                                                    {{ $ar ? 'قيد التنفيذ' : 'En Progreso' }}
                                                    @break
                                                @default
                                                    {{ $ar ? 'معلق' : 'Pendiente' }}
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
                        </a>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>{{ $ar ? 'لا توجد مهام مخصصة لليوم' : 'No hay tareas asignadas para hoy' }}</p>
                    <small class="text-muted">{{ $ar ? 'تواصل مع مشرفك إذا كنت تعتقد أنه يجب أن تكون هناك مهام مخصصة' : 'Contacta con tu supervisor si crees que debería haber tareas asignadas' }}</small>
                </div>
            @endif
        </div>
    </div>

    <!-- Incidencias Pendientes -->
    @if($datos['incidenciasPendientes']->count() > 0)
        <div class="apple-card">
            <div class="apple-card-header">
                <div class="apple-card-title">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ $ar ? 'حوادثي المعلقة' : 'Mis Incidencias Pendientes' }}</span>
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
                                    {{ $ar ? 'عرض التفاصيل' : 'Ver Detalles' }}
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
                    <i class="bi bi-graph-up"></i>
                    <span>{{ $ar ? 'جودة التنظيف (الأسبوع الماضي)' : 'Calidad de Limpieza (Última Semana)' }}</span>
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
            <h3>{{ $ar ? 'جاري التحديث...' : 'Actualizando...' }}</h3>
            <p>{{ $ar ? 'يرجى الانتظار أثناء معالجة طلبك' : 'Por favor, espera mientras se procesa tu solicitud' }}</p>
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
function showLoadingOverlay(message) {
    message = message || 'Actualizando...';
    var overlay = document.getElementById('loadingOverlay');
    var messageElement = overlay.querySelector('h3');
    var progressFill = document.getElementById('progressFill');
    var progressText = document.getElementById('progressText');

    messageElement.textContent = message;
    progressFill.style.width = '0%';
    progressText.textContent = '0%';

    overlay.style.display = 'flex';

    var progress = 0;
    var progressInterval = setInterval(function() {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;

        progressFill.style.width = progress + '%';
        progressText.textContent = Math.round(progress) + '%';
    }, 200);

    overlay.dataset.progressInterval = progressInterval;
}

function hideLoadingOverlay() {
    var overlay = document.getElementById('loadingOverlay');
    var progressFill = document.getElementById('progressFill');
    var progressText = document.getElementById('progressText');

    progressFill.style.width = '100%';
    progressText.textContent = '100%';

    if (overlay.dataset.progressInterval) {
        clearInterval(overlay.dataset.progressInterval);
    }

    setTimeout(function() {
        overlay.style.display = 'none';
    }, 500);
}

// Mostrar overlay al hacer clic en botones de accion
document.addEventListener('DOMContentLoaded', function() {
    var actionButtons = document.querySelectorAll('.apple-btn');

    actionButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            if (this.type !== 'submit') {
                showLoadingOverlay('Procesando accion...');
            }
        });
    });

    // Formulario de finalizar jornada
    var finalizarForm = document.getElementById('finalizarForm');
    if (finalizarForm) {
        finalizarForm.addEventListener('submit', function() {
            var submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.innerHTML = '<i class="bi bi-arrow-repeat"></i> Finalizando...';
                submitButton.disabled = true;
            }
        });
    }
});
</script>
@endpush
