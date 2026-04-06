@extends('layouts.appPersonal')

@section('title')
    Gestión de Incidencias
@endsection

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center text-white">Bienvenid@ {{Auth::user()->name}}</h5>
@endsection

@section('content')
<div class="apple-container">
    <!-- Header Principal -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fa-solid fa-exclamation-triangle"></i>
                        </div>
                        <div class="apartment-details">
                            <h1 class="apartment-title">Gestión de Incidencias</h1>
                            <p class="apartment-subtitle">Reporta y gestiona problemas encontrados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas Rápidas -->
        <div class="apple-stats-section">
            <div class="apple-stat-item">
                <div class="stat-content">
                    <div class="stat-number">{{ $incidencias->where('estado', 'pendiente')->count() }}</div>
                    <div class="stat-label">PENDIENTES</div>
                </div>
                <div class="stat-icon stat-pending">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            
            <div class="apple-stat-item">
                <div class="stat-content">
                    <div class="stat-number">{{ $incidencias->where('estado', 'en_proceso')->count() }}</div>
                    <div class="stat-label">EN PROCESO</div>
                </div>
                <div class="stat-icon stat-process">
                    <i class="fas fa-tools"></i>
                </div>
            </div>
            
            <div class="apple-stat-item">
                <div class="stat-content">
                    <div class="stat-number">{{ $incidencias->where('estado', 'resuelta')->count() }}</div>
                    <div class="stat-label">RESUELTAS</div>
                </div>
                <div class="stat-icon stat-resolved">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            
            <div class="apple-stat-item">
                <div class="stat-content">
                    <div class="stat-number">{{ $incidencias->where('prioridad', 'urgente')->count() }}</div>
                    <div class="stat-label">URGENTES</div>
                </div>
                <div class="stat-icon stat-urgent">
                    <i class="fas fa-exclamation"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón Crear Nueva Incidencia -->
    <div class="apple-action-section">
        <a href="{{ route('gestion.incidencias.create') }}" class="apple-btn apple-btn-primary apple-btn-full">
            <i class="fa-solid fa-plus"></i>
            <span>Reportar Nueva Incidencia</span>
        </a>
    </div>

    <!-- Lista de Incidencias -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fa-solid fa-list"></i>
                        </div>
                        <div class="apartment-details">
                            <h2 class="apartment-title">Mis Incidencias</h2>
                            <p class="apartment-subtitle">Total: {{ $incidencias->total() }} incidencias</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="apple-card-body">
            @if($incidencias->count() > 0)
                <div class="apple-list">
                    @foreach($incidencias as $incidencia)
                        <div class="apple-list-item apple-list-item-{{ $incidencia->estado === 'pendiente' ? 'warning' : ($incidencia->estado === 'en_proceso' ? 'info' : ($incidencia->estado === 'resuelta' ? 'success' : 'secondary')) }}">
                            <div class="apple-list-content">
                                <div class="apple-list-title">
                                    {{ $incidencia->titulo }}
                                    @if($incidencia->prioridad === 'urgente')
                                        <span class="badge bg-danger ms-2">
                                            <i class="fas fa-exclamation"></i> Urgente
                                        </span>
                                    @elseif($incidencia->prioridad === 'alta')
                                        <span class="badge bg-warning ms-2">
                                            <i class="fas fa-exclamation-triangle"></i> Alta
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="apple-list-subtitle">
                                    <div class="incident-details">
                                        <span class="incident-type">
                                            <i class="fas fa-{{ $incidencia->tipo === 'apartamento' ? 'building' : 'users' }}"></i>
                                            {{ $incidencia->tipo === 'apartamento' ? 'Apartamento' : 'Zona Común' }}
                                        </span>
                                        
                                        @if($incidencia->apartamento)
                                            <span class="incident-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                {{ $incidencia->apartamento->nombre }}
                                            </span>
                                        @elseif($incidencia->zonaComun)
                                            <span class="incident-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                {{ $incidencia->zonaComun->nombre }}
                                            </span>
                                        @endif
                                        
                                        <span class="incident-date">
                                            <i class="fas fa-calendar"></i>
                                            {{ \Carbon\Carbon::parse($incidencia->created_at)->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                    
                                    <div class="incident-description">
                                        {{ Str::limit($incidencia->descripcion, 100) }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="apple-list-action">
                                <div class="action-buttons">
                                    <a href="{{ route('gestion.incidencias.show', $incidencia) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($incidencia->estado === 'pendiente')
                                        <a href="{{ route('gestion.incidencias.edit', $incidencia) }}" 
                                           class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form action="{{ route('gestion.incidencias.destroy', $incidencia) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta incidencia?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Paginación -->
                @if($incidencias->hasPages())
                    <div class="apple-pagination">
                        {{ $incidencias->links() }}
                    </div>
                @endif
                
            @else
                <!-- Estado Vacío -->
                <div class="apple-empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="empty-title">No hay incidencias</div>
                    <div class="empty-subtitle">¡Perfecto! No tienes incidencias pendientes.</div>
                    <div class="empty-action">
                        <a href="{{ route('gestion.incidencias.create') }}" class="apple-btn apple-btn-primary">
                            <i class="fa-solid fa-plus"></i>
                            <span>Reportar Primera Incidencia</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
/* Variables CSS para consistencia con gestion-edit */
:root {
    --apple-blue: #007AFF;
    --apple-blue-dark: #0056CC;
    --border-radius-lg: 15px;
    --spacing-lg: 24px;
    --spacing-md: 16px;
    --spacing-sm: 12px;
}

/* Tarjeta Principal - Estilo idéntico a gestion-edit */
.apple-card {
    background: #FFFFFF;
    border-radius: var(--border-radius-lg);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: var(--spacing-lg);
}

/* Header de Tarjeta - Estilo idéntico a gestion-edit */
.apple-card-header {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%) !important;
    padding: var(--spacing-lg) var(--spacing-lg) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--spacing-lg);
}

.header-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--spacing-lg);
    flex: 1;
}

.header-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    flex: 1;
}

.apartment-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.apartment-icon i {
    font-size: 20px;
    color: #FFFFFF;
}

.apartment-details {
    flex: 1;
}

.apartment-title {
    font-size: 20px;
    font-weight: 700;
    color: #FFFFFF;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.apartment-subtitle {
    font-size: 14px;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    letter-spacing: -0.01em;
}

/* Cuerpo de Tarjeta - Estilo idéntico a gestion-edit */
.apple-card-body {
    padding: var(--spacing-lg);
    background: #FFFFFF;
}

/* Estadísticas en Grid 2x2 */
.apple-stats-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto;
    gap: 15px;
    margin: 20px 0;
    max-width: 100%;
}

.apple-stat-item {
    background: white;
    border-radius: 15px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
    min-height: 80px;
    width: 100%;
    box-sizing: border-box;
    gap: 20px;
}

.apple-stat-item:hover {
    transform: translateY(-2px);
}

.stat-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    flex: 1;
    padding-right: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5em;
    color: white;
    flex-shrink: 0;
}

.stat-pending { background: #FF9800; }
.stat-process { background: #2196F3; }
.stat-resolved { background: #4CAF50; }
.stat-urgent { background: #F44336; }

.stat-number {
    font-size: 2.2em;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
    line-height: 1;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Lista de incidencias */
.apple-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.apple-list-item {
    background: #FFFFFF;
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-lg);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-lg);
    transition: all 0.3s ease;
}

.apple-list-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.12);
}

.apple-list-content {
    flex: 1;
}

.apple-list-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0 0 8px 0;
    line-height: 1.3;
}

.apple-list-subtitle {
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.incident-details {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 10px;
}

.incident-type, .incident-location, .incident-date {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.85em;
    color: #666;
}

.incident-description {
    color: #333;
    font-style: italic;
    margin-top: 8px;
}

.apple-list-action {
    display: flex;
    align-items: center;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

/* Estados de incidencias */
.apple-list-item-warning {
    border-left: 4px solid #FF9800;
}

.apple-list-item-info {
    border-left: 4px solid #2196F3;
}

.apple-list-item-success {
    border-left: 4px solid #4CAF50;
}

.apple-list-item-secondary {
    border-left: 4px solid #9E9E9E;
}

/* Estado vacío */
.apple-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 4em;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-title {
    font-size: 1.5em;
    font-weight: bold;
    color: #333;
    margin-bottom: 10px;
}

.empty-subtitle {
    color: #666;
    margin-bottom: 30px;
}

.apple-pagination {
    margin-top: 30px;
    display: flex;
    justify-content: center;
}

/* Estilos responsive */
@media (max-width: 768px) {
    .apple-stats-section {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .apple-stat-item {
        padding: 12px;
        min-height: 65px;
        gap: 15px;
    }
    
    .stat-number {
        font-size: 1.6em;
    }
    
    .stat-icon {
        width: 45px;
        height: 45px;
        font-size: 1.3em;
    }
    
    .incident-details {
        flex-direction: column;
        gap: 10px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .apple-stat-item {
        padding: 14px;
        min-height: 65px;
        gap: 12px;
    }
    
    .stat-number {
        font-size: 1.6em;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.2em;
    }
    
    .stat-label {
        font-size: 0.8em;
    }
    
    .stat-content {
        padding-right: 8px;
    }
}
</style>

<!-- Overlay de Carga -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
        <h3 id="loadingMessage">Actualizando...</h3>
        <p class="loading-subtitle">Por favor, espera mientras se procesa tu solicitud</p>
        
        <!-- Barra de Progreso -->
        <div class="progress-container">
            <div class="progress">
                <div class="progress-bar" id="progressFill" role="progressbar" style="width: 0%"></div>
            </div>
            <div class="progress-text" id="progressText">0%</div>
        </div>
    </div>
</div>

<!-- Estilos del Overlay de Carga -->
<style>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    backdrop-filter: blur(5px);
}

.loading-content {
    background: white;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 400px;
    width: 90%;
}

.loading-spinner {
    margin-bottom: 20px;
}

.loading-spinner .spinner-border {
    width: 3rem;
    height: 3rem;
}

.loading-content h3 {
    color: #333;
    margin-bottom: 10px;
    font-weight: 600;
}

.loading-subtitle {
    color: #666;
    margin-bottom: 25px;
    font-size: 0.9em;
}

.progress-container {
    margin-top: 20px;
}

.progress {
    height: 8px;
    border-radius: 4px;
    background-color: #e9ecef;
    margin-bottom: 10px;
    overflow: hidden;
}

.progress-bar {
    background: linear-gradient(90deg, #007AFF, #4DA3FF);
    transition: width 0.3s ease;
    border-radius: 4px;
}

.progress-text {
    font-size: 0.85em;
    color: #666;
    font-weight: 500;
}
</style>

<!-- JavaScript del Overlay de Carga -->
<script>
// Funciones para el Overlay de Carga
function showLoadingOverlay(message = 'Actualizando...') {
    const overlay = document.getElementById('loadingOverlay');
    const messageElement = document.getElementById('loadingMessage');
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
    
    // Guardar el intervalo para poder limpiarlo
    overlay.dataset.progressInterval = progressInterval;
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    // Completar la barra de progreso
    progressFill.style.width = '100%';
    progressText.textContent = '100%';
    
    // Limpiar el intervalo de progreso
    if (overlay.dataset.progressInterval) {
        clearInterval(overlay.dataset.progressInterval);
    }
    
    // Ocultar después de un pequeño delay para mostrar el 100%
    setTimeout(() => {
        overlay.style.display = 'none';
    }, 500);
}

function updateLoadingProgress(percentage) {
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    if (progressFill && progressText) {
        progressFill.style.width = percentage + '%';
        progressText.textContent = Math.round(percentage) + '%';
    }
}

// Agregar overlay a todos los enlaces y formularios
document.addEventListener('DOMContentLoaded', function() {
    // Overlay para enlaces de navegación
    const links = document.querySelectorAll('a[href*="gestion/incidencias"]');
    links.forEach(link => {
        link.addEventListener('click', function() {
            showLoadingOverlay('Navegando...');
        });
    });
    
    // Overlay para formularios (si los hay)
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            showLoadingOverlay('Enviando formulario...');
        });
    });
    
    // Overlay para botones de acción
    const actionButtons = document.querySelectorAll('.apple-btn');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.textContent.includes('Crear') || this.textContent.includes('Nueva')) {
                showLoadingOverlay('Creando incidencia...');
            } else if (this.textContent.includes('Editar')) {
                showLoadingOverlay('Editando incidencia...');
            } else if (this.textContent.includes('Eliminar')) {
                showLoadingOverlay('Eliminando incidencia...');
            } else {
                showLoadingOverlay('Procesando...');
            }
        });
    });
});
</script>

@endsection
