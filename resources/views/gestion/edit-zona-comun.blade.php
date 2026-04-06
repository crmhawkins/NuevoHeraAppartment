@extends('layouts.appPersonal')

@section('title', 'Editar Limpieza - Zona Común')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/gestion-buttons.css') }}">
<style>
/* ESTILOS DE PRUEBA - FORZAR APLICACIÓN */
    .apple-container {
        max-width: 1200px !important;
        margin: 0 auto !important;
        padding: 20px !important;
    }

    .zona-comun-header {
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%) !important;
        color: white !important;
        padding: 28px 24px !important;
        border-radius: 20px !important;
        margin-bottom: 24px !important;
        box-shadow: 0 12px 40px rgba(0, 122, 255, 0.25) !important;
        position: relative !important;
        overflow: hidden !important;
        min-height: 120px !important;
    }

    .zona-comun-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        pointer-events: none;
    }

    .zona-comun-header .header-content {
        display: flex !important;
        align-items: center !important;
        gap: 20px !important;
        position: relative !important;
        z-index: 1 !important;
        height: 100% !important;
    }

    .zona-comun-header h1 {
        margin: 0 !important;
        font-size: 24px !important;
        font-weight: 600 !important;
        color: white !important;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3) !important;
        line-height: 1.2 !important;
    }

    .zona-comun-header .header-text {
        display: flex !important;
        flex-direction: column !important;
        gap: 4px !important;
    }

    .zona-comun-header .header-icon {
        width: 60px !important;
        height: 60px !important;
        background: rgba(255, 255, 255, 0.25) !important;
        border-radius: 16px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 26px !important;
        backdrop-filter: blur(10px) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1) !important;
        flex-shrink: 0 !important;
    }

    .zona-comun-header .header-subtitle {
        margin-top: 8px !important;
        opacity: 0.95 !important;
        font-size: 15px !important;
        font-weight: 400 !important;
        position: relative !important;
        z-index: 1 !important;
        line-height: 1.4 !important;
        color: white !important;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3) !important;
    }

    .apple-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        margin-bottom: 20px;
        overflow: hidden;
        border: 1px solid #E5E7EB;
    }

    .apple-card-header {
        background: #F8F9FA;
        padding: 16px 20px;
    }

    /* Estilos del Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }

    .loading-content {
        background: white;
        border-radius: 20px;
        padding: 40px;
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
        color: #007AFF;
    }

    #loadingMessage {
        color: #1D1D1F;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .loading-subtitle {
        color: #6C6C70;
        font-size: 1rem;
        margin-bottom: 25px;
    }

    .progress-container {
        margin-top: 20px;
    }

    .progress {
        height: 8px;
        border-radius: 4px;
        background-color: #E5E7EB;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .progress-text {
        font-size: 0.9rem;
        color: #6C6C70;
        font-weight: 500;
    }
        border-bottom: 1px solid #E5E7EB;
    }

    .apple-card-title {
        font-size: 16px;
        font-weight: 600;
        color: #1F2937;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .apple-card-title i {
        color: #007AFF;
        font-size: 20px;
    }

    .apple-card-body {
        padding: 20px;
    }

    .checklist-section {
        margin-bottom: 32px;
    }

    .checklist-title {
        font-size: 18px;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checklist-title i {
        color: #007AFF;
    }

    .checklist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 16px;
    }

    .checklist-item {
        background: #F8F9FA;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 14px;
        transition: all 0.2s ease;
    }

    .checklist-item:hover {
        border-color: #007AFF;
        box-shadow: 0 2px 8px rgba(0, 122, 255, 0.1);
    }

    .checklist-item.completed {
        border-color: #10B981;
        background: #F0FDF4;
    }

    .checklist-item-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .checklist-checkbox {
        width: 20px;
        height: 20px;
        accent-color: #007AFF;
        cursor: pointer;
    }

    .checklist-item-title {
        font-weight: 600;
        color: #1F2937;
        flex: 1;
    }

    .checklist-item-description {
        color: #6B7280;
        font-size: 14px;
        line-height: 1.5;
    }

    .observations-section {
        margin-top: 32px;
    }

    .observations-textarea {
        width: 100%;
        min-height: 120px;
        padding: 16px;
        border: 2px solid #E5E7EB;
        border-radius: 12px;
        font-size: 16px;
        font-family: inherit;
        resize: vertical;
        transition: border-color 0.3s ease;
    }

    .observations-textarea:focus {
        outline: none;
        border-color: #007AFF;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
    }

    .apple-actions {
        display: flex;
        gap: 16px;
        justify-content: flex-end;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #E5E7EB;
    }

    .apple-btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .apple-btn-primary {
        background: #007AFF;
        color: white;
        box-shadow: 0 2px 8px rgba(0, 122, 255, 0.2);
    }

    .apple-btn-primary:hover {
        background: #0056CC;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
    }

    .apple-btn-secondary {
        background: #F3F4F6;
        color: #374151;
        border: 1px solid #E5E7EB;
    }

    .apple-btn-secondary:hover {
        background: #E5E7EB;
        border-color: #D1D5DB;
    }

    .apple-btn-danger {
        background: #EF4444;
        color: white;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    }

    .apple-btn-danger:hover {
        background: #DC2626;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
    }

    .status-badge.en-proceso {
        background: #FEF3C7;
        color: #92400E;
    }

    .status-badge.completada {
        background: #D1FAE5;
        color: #065F46;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .info-card {
        background: #F8F9FA;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }

    .info-card-label {
        font-size: 12px;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    .info-card-value {
        font-size: 18px;
        font-weight: 600;
        color: #1F2937;
    }

    @media (max-width: 768px) {
        .apple-container {
            padding: 16px;
        }

        .apple-header {
            padding: 24px 20px;
            border-radius: 16px;
        }

        .apple-header h1 {
            font-size: 22px;
            gap: 14px;
        }

        .apple-header .header-icon {
            width: 44px;
            height: 44px;
            font-size: 20px;
        }

        .apple-header .header-subtitle {
            font-size: 14px;
            margin-top: 10px;
        }

        .checklist-grid {
            grid-template-columns: 1fr;
        }

        .apple-actions {
            flex-direction: column;
        }

        .apple-btn {
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<div class="apple-container">
    <!-- Header -->
    <div class="zona-comun-header">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="header-text">
                <h1>Editar Limpieza - Zona Común</h1>
                <div class="header-subtitle">
                    {{ $zonaComun->nombre }} • {{ $zonaComun->tipo }}
                    @if($zonaComun->ubicacion)
                        • {{ $zonaComun->ubicacion }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Información de la Limpieza -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="apple-card-title">
                <i class="fas fa-info-circle"></i>
                Información de la Limpieza
            </div>
        </div>
        <div class="apple-card-body">
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-card-label">Estado</div>
                    <div class="info-card-value">
                        <span class="status-badge @if($apartamentoLimpieza->status_id == 2) en-proceso @elseif($apartamentoLimpieza->status_id == 3) completada @endif">
                            @if($apartamentoLimpieza->status_id == 2)
                                <i class="fas fa-clock"></i>
                                En Proceso
                            @elseif($apartamentoLimpieza->status_id == 3)
                                <i class="fas fa-check-circle"></i>
                                Completada
                            @else
                                <i class="fas fa-question-circle"></i>
                                Pendiente
                            @endif
                        </span>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-card-label">Fecha Inicio</div>
                    <div class="info-card-value">
                        {{ \Carbon\Carbon::parse($apartamentoLimpieza->fecha_comienzo)->format('d/m/Y H:i') }}
                    </div>
                </div>
                @if($apartamentoLimpieza->fecha_fin)
                <div class="info-card">
                    <div class="info-card-label">Fecha Fin</div>
                    <div class="info-card-value">
                        {{ \Carbon\Carbon::parse($apartamentoLimpieza->fecha_fin)->format('d/m/Y H:i') }}
                    </div>
                </div>
                @endif
                <div class="info-card">
                    <div class="info-card-label">Empleada</div>
                    <div class="info-card-value">
                        {{ $apartamentoLimpieza->empleada->name ?? 'No asignada' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

            <!-- Formulario de Limpieza -->
        <form action="{{ route('gestion.updateZonaComun', $apartamentoLimpieza->id) }}" method="POST">
            @csrf
            
            <div class="apple-card">
                <div class="apple-card-header">
                    <div class="apple-card-title">
                        <i class="fas fa-clipboard-check"></i>
                        Estado de la Limpieza
                    </div>
                </div>
                <div class="apple-card-body">
                    <div style="text-align: center; padding: 40px; color: #6B7280;">
                        <i class="fas fa-building" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5; color: #007AFF;"></i>
                        <h4 style="color: #1F2937; margin-bottom: 12px;">Limpieza de Zona Común</h4>
                        <p style="margin-bottom: 0;">Las zonas comunes no requieren checklist detallado.</p>
                        <p style="font-size: 14px; opacity: 0.8;">Marca la limpieza como completada cuando hayas terminado.</p>
                    </div>
                </div>
            </div>

        <!-- Observaciones -->
        <div class="apple-card">
            <div class="apple-card-header">
                <div class="apple-card-title">
                    <i class="fas fa-comment"></i>
                    Observaciones de la Limpieza
                </div>
            </div>
            <div class="apple-card-body">
                <div style="margin-bottom: 16px;">
                    <label for="observacion" class="form-label" style="font-weight: 600; color: #1F2937; margin-bottom: 8px;">
                        Describe el trabajo realizado:
                    </label>
                    <textarea name="observacion" 
                              id="observacion"
                              class="observations-textarea" 
                              placeholder="Describe qué tareas de limpieza has completado, si hay algún problema o incidencia, estado general de la zona...">{{ $apartamentoLimpieza->observacion }}</textarea>
                </div>
                <div style="background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 8px; padding: 12px; font-size: 14px; color: #0369A1;">
                    <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                    <strong>Tip:</strong> Sé específico sobre el trabajo realizado para que el equipo pueda hacer seguimiento.
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="apple-actions">
            <div class="action-info" style="flex: 1; text-align: center; margin-bottom: 16px;">
                <div style="background: #FEF3C7; border: 1px solid #F59E0B; border-radius: 8px; padding: 12px; display: inline-block;">
                    <i class="fas fa-lightbulb" style="color: #D97706; margin-right: 8px;"></i>
                    <span style="color: #92400E; font-weight: 500;">
                        @if($apartamentoLimpieza->status_id == 2)
                            Limpieza en progreso - Guarda tu trabajo regularmente
                        @elseif($apartamentoLimpieza->status_id == 3)
                            ✅ Limpieza completada exitosamente
                        @else
                            Lista para comenzar la limpieza
                        @endif
                    </span>
                </div>
            </div>
            
            <div class="action-buttons" style="display: flex; gap: 16px; justify-content: center; width: 100%;">
                <a href="{{ route('gestion.index') }}" class="apple-btn apple-btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                
                @if($apartamentoLimpieza->status_id != 3)
                    <button type="submit" class="apple-btn apple-btn-primary" id="saveBtn">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    
                    <button type="button" 
                            class="apple-btn apple-btn-danger"
                            id="finalizarBtn"
                            onclick="confirmarFinalizar()">
                        <i class="fas fa-check-double"></i>
                        Finalizar Limpieza
                    </button>
                @else
                    <div class="apple-btn apple-btn-secondary" style="cursor: default;">
                        <i class="fas fa-check-circle"></i>
                        Limpieza Completada
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>

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
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM cargado, inicializando script de zona común...');
    
    // Funciones del Loading Overlay
    window.showLoadingOverlay = function(message = 'Actualizando...') {
        console.log('🎯 showLoadingOverlay llamado con mensaje:', message);
        const overlay = document.getElementById('loadingOverlay');
        const messageEl = document.getElementById('loadingMessage');
        
        console.log('🔍 Elementos encontrados:', { overlay, messageEl });
        
        if (overlay && messageEl) {
            console.log('✅ Mostrando loading overlay...');
            messageEl.textContent = message;
            overlay.style.display = 'flex';
            
            // Simular progreso
            let progress = 0;
            const progressBar = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                
                if (progressBar) progressBar.style.width = progress + '%';
                if (progressText) progressText.textContent = Math.round(progress) + '%';
            }, 200);
            
            // Guardar el intervalo para limpiarlo
            overlay.dataset.progressInterval = progressInterval;
        } else {
            console.log('❌ No se pudieron encontrar los elementos del loading overlay');
        }
    };

    window.hideLoadingOverlay = function() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            // Limpiar el intervalo de progreso
            if (overlay.dataset.progressInterval) {
                clearInterval(parseInt(overlay.dataset.progressInterval));
            }
            
            // Completar la barra de progreso
            const progressBar = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            if (progressBar) progressBar.style.width = '100%';
            if (progressText) progressText.textContent = '100%';
            
            // Ocultar después de un breve delay
            setTimeout(() => {
                overlay.style.display = 'none';
                // Resetear progreso
                if (progressBar) progressBar.style.width = '0%';
                if (progressText) progressText.textContent = '0%';
            }, 500);
        }
    };

    // Función para confirmar finalización
    window.confirmarFinalizar = function() {
        if (confirm('¿Estás seguro de que quieres finalizar esta limpieza?\n\nUna vez finalizada, no podrás hacer más cambios.')) {
            // Mostrar loading overlay
            showLoadingOverlay('Finalizando limpieza...');
            
            // Crear y enviar formulario de finalización
            const finalizarForm = document.createElement('form');
            finalizarForm.method = 'POST';
            finalizarForm.action = '{{ route("gestion.finalizarZonaComun", $apartamentoLimpieza->id) }}';
            
            // Agregar token CSRF
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            finalizarForm.appendChild(csrfToken);
            
            // Agregar al DOM y enviar
            document.body.appendChild(finalizarForm);
            finalizarForm.submit();
        }
    }
    
    // Función para mostrar feedback de guardado
    const form = document.querySelector('form');
    const saveBtn = document.getElementById('saveBtn');
    
    if (form && saveBtn) {
        console.log('✅ Formulario y botón encontrados, añadiendo event listener...');
        form.addEventListener('submit', function(e) {
            console.log('🚀 Formulario enviándose, mostrando loading overlay...');
            // Mostrar loading overlay
            showLoadingOverlay('Guardando cambios...');
            
            // Cambiar texto del botón durante el envío
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            saveBtn.disabled = true;
            
            // El formulario se enviará automáticamente
        });
    } else {
        console.log('❌ No se encontró el formulario o el botón de guardar');
        console.log('Formulario:', form);
        console.log('Botón guardar:', saveBtn);
    }
    
    // Auto-guardar observaciones cada 30 segundos si hay cambios
    let observacionTextarea = document.getElementById('observacion');
    let lastValue = observacionTextarea ? observacionTextarea.value : '';
    let autoSaveTimer;
    
    if (observacionTextarea) {
        observacionTextarea.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                if (this.value !== lastValue) {
                    // Mostrar indicador de auto-guardado
                    const autoSaveIndicator = document.createElement('div');
                    autoSaveIndicator.innerHTML = '<i class="fas fa-save"></i> Auto-guardando...';
                    autoSaveIndicator.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10B981; color: white; padding: 12px 20px; border-radius: 8px; z-index: 9999; font-size: 14px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);';
                    
                    document.body.appendChild(autoSaveIndicator);
                    
                    // Remover después de 2 segundos
                    setTimeout(() => {
                        if (autoSaveIndicator.parentNode) {
                            autoSaveIndicator.parentNode.removeChild(autoSaveIndicator);
                        }
                    }, 2000);
                    
                    lastValue = this.value;
                }
            }, 30000); // 30 segundos
        });
    }
});
</script>
@endsection
