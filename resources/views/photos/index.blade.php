@extends('layouts.appPersonal')

<meta name="csrf-token" content="{{ csrf_token() }}">

@section('title')
    {{ __('Subidas de fotos de la categoría ') . $checklist->nombre }}
@endsection

@section('volver')
    <button class="back" type="button" onclick="history.back()"><i class="fa-solid fa-angle-left"></i></button>
@endsection

@section('content')

<!-- Debug: Verificar variables disponibles -->
@if(config('app.debug'))
    <div style="background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;">
        <strong>Debug - Variables disponibles:</strong><br>
        ID: {{ $id ?? 'NO DEFINIDO' }}<br>
        Cat: {{ $cat ?? 'NO DEFINIDO' }}<br>
        Limpieza: {{ isset($limpieza) ? 'DEFINIDA (ID: ' . $limpieza->id . ')' : 'NO DEFINIDA' }}<br>
        Categorias: {{ count($categorias ?? []) }}<br>
        Checklist: {{ $checklist->nombre ?? 'NO DEFINIDO' }}<br>
        <strong>Análisis Existentes:</strong><br>
        @if(isset($analisisExistentes) && count($analisisExistentes) > 0)
            @foreach($analisisExistentes as $catId => $analisis)
                Categoría {{ $catId }}: {{ $analisis->categoria_nombre }} (Cumple: {{ $analisis->cumple_estandares ? 'Sí' : 'No' }})<br>
            @endforeach
        @else
            NO HAY ANÁLISIS<br>
        @endif
    </div>
@endif

<div class="apple-container">
    <form action="{{ route('gestion.edit', $id) }}" method="GET" enctype="multipart/form-data" id="uploadForm">
        @csrf
        @foreach ($categorias as $categoria)
            <div class="apple-photo-section">
                <div class="apple-photo-header">
                    <div class="apple-photo-title">
                        <i class="fa-solid fa-camera"></i>
                        <span>{{ strtoupper($categoria->nombre) }}</span>
                    </div>
                    <div class="apple-photo-status" id="status-{{ $categoria->id }}">
                        <i class="fa-solid fa-circle"></i>
                    </div>
                </div>
                <div class="apple-photo-content">
                    <input type="file"
                        accept="image/*"
                        class="apple-file-input"
                        capture="camera"
                        name="image_{{ $categoria->id }}"
                        id="image_{{ $categoria->id }}">
                    <button type="button"
                            class="apple-camera-btn"
                            onclick="document.getElementById('image_{{ $categoria->id }}').click()">
                        <i class="fa-solid fa-camera"></i>
                        <span>CÁMARA</span>
                    </button>
                    <div class="apple-preview-container" id="preview-container-{{ $categoria->id }}">
                        <img id="preview_{{ $categoria->id }}"
                            class="apple-preview-image"
                            src="{{ isset($imagenes[$categoria->id]) ? asset($imagenes[$categoria->id]->photo_url) : '' }}">
                    </div>
                    
                    <!-- Contenedor de información de OpenAI -->
                    <div class="openai-info-container" id="openai-info-{{ $categoria->id }}" 
                         style="display: {{ isset($analisisExistentes[$categoria->id]) ? 'block' : 'none' }};">
                        <div class="openai-header">
                            <i class="fa-solid fa-robot"></i>
                            <span>Análisis de Calidad</span>
                            <div class="quality-status" id="quality-status-{{ $categoria->id }}">
                                @if(isset($analisisExistentes[$categoria->id]))
                                    @if($analisisExistentes[$categoria->id]->cumple_estandares)
                                        <i class="fa-solid fa-check-circle text-success"></i>
                                    @else
                                        <i class="fa-solid fa-exclamation-triangle text-warning"></i>
                                    @endif
                                @else
                                    <i class="fa-solid fa-circle"></i>
                                @endif
                            </div>
                        </div>
                        <div class="openai-content" id="openai-content-{{ $categoria->id }}">
                            @if(isset($analisisExistentes[$categoria->id]))
                                <!-- Mostrar análisis existente -->
                                <div class="analysis-content">
                                    <h6><i class="fa-solid fa-microscope"></i> Análisis de Calidad</h6>
                                    <div class="analysis-text">
                                        <div class="analysis-item">
                                            <i class="fa-solid fa-star"></i>
                                            <strong>Calidad General:</strong> 
                                            <span class="quality-value {{ $analisisExistentes[$categoria->id]->calidad_general }}">
                                                {{ ucfirst($analisisExistentes[$categoria->id]->calidad_general) }}
                                            </span>
                                        </div>
                                        <div class="analysis-item">
                                            <i class="fa-solid fa-chart-line"></i>
                                            <strong>Puntuación:</strong> 
                                            <span class="score-value">{{ $analisisExistentes[$categoria->id]->puntuacion }}/10</span>
                                        </div>
                                        <div class="analysis-item">
                                            <i class="fa-solid fa-check-double"></i>
                                            <strong>Cumple Estándares:</strong> 
                                            <span class="standard-value {{ $analisisExistentes[$categoria->id]->cumple_estandares ? 'success' : 'warning' }}">
                                                {{ $analisisExistentes[$categoria->id]->cumple_estandares ? 'Sí' : 'No' }}
                                            </span>
                                        </div>
                                        @if($analisisExistentes[$categoria->id]->deficiencias && count($analisisExistentes[$categoria->id]->deficiencias) > 0)
                                            <div class="analysis-item">
                                                <i class="fa-solid fa-exclamation-triangle"></i>
                                                <strong>Deficiencias:</strong>
                                                <ul class="deficiencies-list">
                                                    @foreach($analisisExistentes[$categoria->id]->deficiencias as $deficiencia)
                                                        <li>{{ $deficiencia }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if($analisisExistentes[$categoria->id]->observaciones)
                                            <div class="analysis-item">
                                                <i class="fa-solid fa-comment"></i>
                                                <strong>Observaciones:</strong> 
                                                <span class="observations-text">{{ $analisisExistentes[$categoria->id]->observaciones }}</span>
                                            </div>
                                        @endif
                                        @if($analisisExistentes[$categoria->id]->recomendaciones && count($analisisExistentes[$categoria->id]->recomendaciones) > 0)
                                            <div class="analysis-item">
                                                <i class="fa-solid fa-lightbulb"></i>
                                                <strong>Recomendaciones:</strong>
                                                <ul class="recommendations-list">
                                                    @foreach($analisisExistentes[$categoria->id]->recomendaciones as $recomendacion)
                                                        <li>{{ $recomendacion }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <!-- El contenido de OpenAI se insertará aquí -->
                            @endif
                        </div>
                        <div class="openai-actions" id="openai-actions-{{ $categoria->id }}" 
                             style="display: {{ (isset($analisisExistentes[$categoria->id]) && !$analisisExistentes[$categoria->id]->cumple_estandares) ? 'block' : 'none' }};">
                            <button type="button" class="apple-btn apple-btn-warning" id="btn-modificar-{{ $categoria->id }}" onclick="modificarFoto({{ $categoria->id }})">
                                <i class="fa-solid fa-edit"></i>
                                <span>Modificar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Sección de Resultados del Análisis -->
        <div id="resultadosAnalisis" class="resultados-analisis" style="display: none;">
            <div class="resultados-header">
                <h3><i class="fas fa-chart-line"></i> Resultados del Análisis de Calidad</h3>
                <div class="resultados-stats">
                    <div class="stat-item">
                        <i class="fa-solid fa-check-circle text-success"></i>
                        <span>Fotos Aprobadas: <strong id="approvedCount">0</strong></span>
                    </div>
                    <div class="stat-item">
                        <i class="fa-solid fa-exclamation-triangle text-warning"></i>
                        <span>Fotos con Observaciones: <strong id="warningCount">0</strong></span>
                    </div>
                </div>
            </div>
            
            <div class="resultados-content">
                <!-- El contenido se insertará dinámicamente -->
            </div>
            
            <div class="resultados-actions">
                <div id="accionAprobadas" class="accion-aprobadas" style="display: none;">
                    <div class="alert alert-success">
                        <i class="fa-solid fa-check-circle"></i>
                        <strong>¡Excelente!</strong> Todas las fotos han pasado el control de calidad. Puedes continuar con el proceso de limpieza.
                    </div>
                    <button type="button" class="apple-btn apple-btn-success btn-lg" onclick="continuarConLimpieza()">
                        <i class="fas fa-arrow-right"></i>
                        Continuar con Limpieza
                    </button>
                </div>
                
                <div id="accionResponsabilidad" class="accion-responsabilidad" style="display: none;">
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <strong>Atención:</strong> Algunas fotos no cumplen completamente con los estándares de calidad. 
                        Si decides continuar, asumes la responsabilidad de que el apartamento esté en condiciones óptimas.
                    </div>
                    <button type="button" class="apple-btn apple-btn-warning btn-lg" onclick="continuarConResponsabilidad()">
                        <i class="fas fa-arrow-right"></i>
                        Continuar bajo mi Responsabilidad
                    </button>
                    <button type="button" class="apple-btn apple-btn-info btn-lg mt-3" onclick="reAnalizarFotosRechazadas()">
                        <i class="fas fa-redo"></i>
                        Re-analizar Fotos Rechazadas
                    </button>
                </div>
            </div>
        </div>

        <div class="apple-continue-section">
            <div class="terminar-message" id="terminarMessage" style="display: none;">
                <div class="message-content">
                    <i class="fa-solid fa-info-circle"></i>
                    <span>Para activar el botón "Continuar", debes subir todas las fotos requeridas de cada sección.</span>
                </div>
            </div>
            <button id="btn_continuar" class="apple-continue-btn" type="button" onclick="continuarProceso()">
                <i class="fa-solid fa-arrow-right"></i>
                <span>CONTINUAR</span>
            </button>
            <button id="btn_volver" class="apple-btn apple-btn-secondary" type="button" onclick="volverALimpieza()" style="display: none;">
                <i class="fa-solid fa-arrow-left"></i>
                <span>VOLVER</span>
            </button>
        </div>
    </form>
</div>
@endsection

<!-- Overlay de Carga -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner">
            <div class="spinner"></div>
        </div>
        <div class="loading-text">
            <h3>Subiendo foto...</h3>
            <p>Por favor, espera mientras se procesa tu imagen</p>
            <div class="loading-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <span class="progress-text" id="progressText">0%</span>
            </div>
        </div>
    </div>
</div>




@section('styles')
<style>
/* Estilos para contenedores de OpenAI */
.openai-info-container {
    margin-top: 16px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    border: 2px solid #e9ecef;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.openai-info-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.openai-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #dee2e6;
}

.openai-header i {
    color: #007AFF;
    font-size: 20px;
}

.openai-header span {
    font-weight: 700;
    color: #2c3e50;
    font-size: 18px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex: 1;
}

.quality-status {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.8);
}

.quality-status.approved i {
    color: #28a745;
    font-size: 20px;
}

.quality-status.warning i {
    color: #ffc107;
    font-size: 20px;
}

.openai-content {
    margin-bottom: 16px;
}

.analysis-content h6 {
    color: #2c3e50;
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 16px;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.analysis-content h6 i {
    color: #007AFF;
    margin-right: 12px;
}

.analysis-text {
    font-size: 14px;
    line-height: 1.6;
    color: #495057;
}

.openai-actions {
    display: flex;
    justify-content: center;
    margin-top: 16px;
}

.openai-actions .apple-btn {
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.openai-actions .apple-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Estilos para sección de resultados */
.resultados-analisis {
    margin-top: 32px;
    padding: 24px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    border: 2px solid #e9ecef;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.resultados-analisis:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.resultados-header {
    text-align: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #dee2e6;
}

.resultados-header h3 {
    color: #2c3e50;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.resultados-header h3 i {
    color: #007AFF;
    margin-right: 12px;
}

.resultados-stats {
    display: flex;
    justify-content: center;
    gap: 32px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #495057;
}

.stat-item i {
    font-size: 20px;
}

.stat-item .text-success {
    color: #28a745;
}

.stat-item .text-warning {
    color: #ffc107;
}

.resultados-content {
    margin-bottom: 24px;
}

.resultados-actions {
    text-align: center;
}

.accion-aprobadas,
.accion-responsabilidad {
    margin-top: 20px;
}

.accion-aprobadas .alert,
.accion-responsabilidad .alert {
    margin-bottom: 20px;
    padding: 16px;
    border-radius: 12px;
    font-size: 16px;
    border: none;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.accion-aprobadas .apple-btn,
.accion-responsabilidad .apple-btn {
    padding: 16px 32px;
    font-size: 18px;
    font-weight: 600;
    border-radius: 12px;
    transition: all 0.3s ease;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.accion-aprobadas .apple-btn:hover,
.accion-responsabilidad .apple-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Responsive */
@media (max-width: 768px) {
    .resultados-analisis {
        margin-top: 24px;
        padding: 20px;
    }
    
    .resultados-header h3 {
        font-size: 20px;
    }
    
    .resultados-stats {
        flex-direction: column;
        gap: 16px;
        align-items: center;
    }
    
    .accion-aprobadas .apple-btn,
    .accion-responsabilidad .apple-btn {
        width: 100%;
        padding: 18px 24px;
    }
}

/* Estilos para el análisis de calidad */
.analysis-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 12px;
    border-left: 4px solid #007AFF;
    transition: all 0.3s ease;
}

.analysis-item:hover {
    background: rgba(255, 255, 255, 1);
    transform: translateX(4px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.analysis-item i {
    color: #007AFF;
    font-size: 18px;
    margin-top: 2px;
    min-width: 20px;
}

.analysis-item strong {
    color: #2c3e50;
    font-weight: 700;
    min-width: 120px;
}

.analysis-item span {
    color: #495057;
    font-weight: 500;
}

/* Estilos para valores específicos */
.quality-value.excelente { color: #28a745; font-weight: 700; }
.quality-value.buena { color: #17a2b8; font-weight: 700; }
.quality-value.regular { color: #ffc107; font-weight: 700; }
.quality-value.mala { color: #dc3545; font-weight: 700; }

.score-value {
    color: #6f42c1;
    font-weight: 700;
    font-size: 18px;
}

.standard-value.success {
    color: #28a745;
    font-weight: 700;
    background: rgba(40, 167, 69, 0.1);
    padding: 4px 8px;
    border-radius: 6px;
}

.standard-value.warning {
    color: #ffc107;
    font-weight: 700;
    background: rgba(255, 193, 7, 0.1);
    padding: 4px 8px;
    border-radius: 6px;
}

.observations-text {
    color: #6c757d;
    font-style: italic;
    line-height: 1.5;
}

.no-deficiencies,
.no-recommendations {
    color: #28a745;
    font-weight: 600;
    font-style: italic;
}

/* Estilos para listas */
.deficiencies-list,
.recommendations-list {
    margin: 8px 0 0 0;
    padding-left: 20px;
    list-style: none;
}

.deficiencies-list li,
.recommendations-list li {
    position: relative;
    margin-bottom: 6px;
    padding-left: 20px;
    color: #495057;
    line-height: 1.4;
}

.deficiencies-list li:before {
    content: '⚠️';
    position: absolute;
    left: 0;
    top: 0;
}

.recommendations-list li:before {
    content: '💡';
    position: absolute;
    left: 0;
    top: 0;
}

/* Estilo para errores */
.analysis-error {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 12px;
    color: #dc3545;
}

.analysis-error i {
    font-size: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .analysis-item {
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
    
    .analysis-item strong {
        min-width: auto;
    }
    
    .deficiencies-list,
    .recommendations-list {
        padding-left: 0;
    }
    
    .deficiencies-list li,
    .recommendations-list li {
        padding-left: 25px;
        text-align: left;
    }
}

/* Estilos para botón de cámara bloqueado */
.apple-camera-btn.blocked {
    background-color: #e0e0e0 !important;
    color: #888 !important;
    cursor: not-allowed !important;
    border: 1px solid #ccc !important;
    opacity: 0.7 !important;
}

.apple-camera-btn.blocked:hover {
    background-color: #e0e0e0 !important;
    color: #888 !important;
    transform: none !important;
}

.apple-camera-btn.blocked .fa-lock {
    margin-right: 8px;
}

/* Estilos para botón de re-análisis */
.apple-camera-btn.re-analyze {
    background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%) !important;
    color: #212529 !important;
    border: 2px solid #ffc107 !important;
    animation: pulse-warning 2s infinite;
}

.apple-camera-btn.re-analyze:hover {
    background: linear-gradient(135deg, #ffb300 0%, #ff6f00 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
}

@keyframes pulse-warning {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}

/* Estilos para botón de modificar re-análisis */
.re-analyze-btn {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
    color: white !important;
    border: none !important;
    padding: 8px 16px !important;
    border-radius: 20px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3) !important;
}

.re-analyze-btn:hover {
    background: linear-gradient(135deg, #138496 0%, #117a8b 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4) !important;
}

/* Estilos para indicador de re-análisis */
.re-analyze-indicator {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-5px); }
    60% { transform: translateY(-3px); }
}

/* Estilos para botón de re-análisis en la sección de resultados */
.apple-btn-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
}

.apple-btn-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
    background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
}

</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Verificar estado inicial de las fotos
    checkInitialPhotoStatus();
    
    // El botón continuar ahora usa onclick="continuarProceso()"
    
            @foreach ($categorias as $categoria)
        const input{{ $categoria->id }} = document.getElementById('image_{{ $categoria->id }}');
        input{{ $categoria->id }}.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (!file) return;

            // Mostrar overlay de carga
            showLoadingOverlay('Subiendo foto...');

            const reader = new FileReader();
            reader.onload = function (e) {
                const previewElement = document.getElementById('preview_{{ $categoria->id }}');
                const containerElement = document.getElementById('preview-container-{{ $categoria->id }}');
                
                previewElement.src = e.target.result;
                containerElement.classList.add('has-image');
                
                // Limpiar análisis anterior si existe
                limpiarAnalisisAnterior({{ $categoria->id }});
                
                // Actualizar estado
                updatePhotoStatus({{ $categoria->id }});
            };
            reader.readAsDataURL(file);

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('image', file);
            formData.append('item_id', '{{ $categoria->id }}');
            formData.append('checklist_id', '{{ $cat }}');
            
            // Usar route con placeholders y reemplazarlos
            const baseRoute = "{{ route('fotos.' . strtolower(strtr($checklist->nombre, [
                ' ' => '_', 'á' => 'a', 'é' => 'e', 'í' => 'i',
                'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e',
                'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'ñ' => 'n',
                'Ñ' => 'n'
            ])).'-store', ['id' => '__ID__', 'cat' => '__CAT__']) }}";

            const uploadUrl = baseRoute.replace('__ID__', '{{ $id }}').replace('__CAT__', '{{ $cat }}') + '';

            fetch(uploadUrl, {
                method: 'POST',
                body: formData,
            })
            .then(async response => {
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.includes("application/json")) {
                    const data = await response.json();
                    if (data.url) {
                        document.getElementById('preview_{{ $categoria->id }}').src = data.url;
                        // Verificar estado después de subida exitosa
                        checkContinueButton();
                    }
                } else {
                    const text = await response.text();
                    console.error('❌ Respuesta no JSON:', text);
                }
                // Ocultar overlay
                hideLoadingOverlay();
            })
            .catch(error => {
                console.error('Error al subir imagen:', error);
                // Ocultar overlay en caso de error
                hideLoadingOverlay();
            });
        });
    @endforeach
});

// Función para verificar estado inicial de las fotos
function checkInitialPhotoStatus() {
    const categorias = @json($categorias);
    
    categorias.forEach(function(categoria) {
        const previewElement = document.getElementById('preview_' + categoria.id);
        const containerElement = document.getElementById('preview-container-' + categoria.id);
        const statusElement = document.getElementById('status-' + categoria.id);
        
        // Verificar que no sea una imagen placeholder o vacía
        const isPlaceholder = previewElement.src && (
            previewElement.src.includes('camera-placeholder.png') || 
            previewElement.src.includes('placeholder.png') ||
            previewElement.src === window.location.href ||
            previewElement.src === '' ||
            previewElement.src.endsWith('camera-placeholder') ||
            previewElement.src.endsWith('placeholder')
        );
        
        if (previewElement && previewElement.src && !isPlaceholder) {
            containerElement.classList.add('has-image');
            updatePhotoStatus(categoria.id);
            
            // Marcar el checkbox de la categoría en la vista de checklist
            const categoryCheckbox = document.querySelector(`input[data-habitacion="${categoria.id}"]`);
            if (categoryCheckbox) {
                categoryCheckbox.checked = true;
                console.log(`Checkbox de categoría ${categoria.nombre} marcado automáticamente`);
            }
            
            // Debug: verificar que se marcó como completada
            console.log(`Categoría ${categoria.nombre} marcada como completada (imagen real)`);
        } else {
            // Asegurar que no esté marcada como completada si es placeholder
            if (statusElement) {
                statusElement.classList.remove('completed');
            }
            console.log(`Categoría ${categoria.nombre} no tiene imagen válida o es placeholder`);
        }
    });
    
    checkContinueButton();
}

// Función para actualizar el estado de las fotos
function updatePhotoStatus(categoriaId) {
    const statusElement = document.getElementById('status-' + categoriaId);
    if (statusElement) {
        statusElement.classList.add('completed');
    }
}

// Función para limpiar análisis anterior cuando se sube una nueva foto
function limpiarAnalisisAnterior(categoriaId) {
    // Ocultar información de OpenAI si existe
    const openaiContainer = document.getElementById('openai-info-' + categoriaId);
    if (openaiContainer) {
        openaiContainer.style.display = 'none';
    }
    
    // Limpiar contenido del análisis
    const openaiContent = document.getElementById('openai-content-' + categoriaId);
    if (openaiContent) {
        openaiContent.innerHTML = '';
    }
    
    // Resetear estado de calidad
    const qualityStatus = document.getElementById('quality-status-' + categoriaId);
    if (qualityStatus) {
        qualityStatus.innerHTML = '<i class="fa-solid fa-circle"></i>';
        qualityStatus.className = 'quality-status';
    }
    
    // Ocultar acciones si existen
    const openaiActions = document.getElementById('openai-actions-' + categoriaId);
    if (openaiActions) {
        openaiActions.style.display = 'none';
    }
    
    // Remover indicadores visuales de análisis anterior
    const previewContainer = document.getElementById('preview-container-' + categoriaId);
    if (previewContainer) {
        // Remover indicador de bloqueado
        const lockedIndicator = previewContainer.querySelector('.locked-indicator');
        if (lockedIndicator) {
            lockedIndicator.remove();
        }
        
        // Remover indicador de re-análisis
        const reAnalyzeIndicator = previewContainer.querySelector('.re-analyze-indicator');
        if (reAnalyzeIndicator) {
            reAnalyzeIndicator.remove();
        }
    }
    
    // Resetear botón de cámara a estado normal
    const cameraBtn = document.querySelector(`button[onclick="document.getElementById('image_${categoriaId}').click()"]`);
    if (cameraBtn) {
        cameraBtn.disabled = false;
        cameraBtn.style.opacity = '1';
        cameraBtn.style.pointerEvents = 'auto';
        cameraBtn.innerHTML = '<i class="fa-solid fa-camera"></i><span>CÁMARA</span>';
        cameraBtn.classList.remove('blocked', 're-analyze');
    }
    
    // Resetear botón de modificar si existe
    const modificarBtn = document.getElementById('btn-modificar-' + categoriaId);
    if (modificarBtn) {
        modificarBtn.style.display = 'block';
        modificarBtn.innerHTML = '<i class="fa-solid fa-edit"></i><span>Modificar</span>';
        modificarBtn.classList.remove('re-analyze-btn');
    }
    
    // Resetear input de archivo
    const fileInput = document.getElementById('image_' + categoriaId);
    if (fileInput) {
        fileInput.disabled = false;
        fileInput.style.opacity = '1';
        fileInput.style.pointerEvents = 'auto';
    }
    
    console.log(`🧹 Análisis anterior limpiado para categoría ${categoriaId}`);
}

// Función para verificar si se puede habilitar el botón continuar
function checkContinueButton() {
    const categorias = @json($categorias);
    const totalCategorias = categorias.length;
    let completedPhotos = 0;
    
    categorias.forEach(function(categoria) {
        const statusElement = document.getElementById('status-' + categoria.id);
        const previewElement = document.getElementById('preview_' + categoria.id);
        
        // Solo contar como completada si tiene clase 'completed' Y no es placeholder
        if (statusElement && statusElement.classList.contains('completed') && previewElement) {
            const isPlaceholder = previewElement.src && (
                previewElement.src.includes('camera-placeholder.png') || 
                previewElement.src.includes('placeholder.png') ||
                previewElement.src.endsWith('camera-placeholder') ||
                previewElement.src.endsWith('placeholder')
            );
            
            if (!isPlaceholder) {
                completedPhotos++;
            }
        }
    });
    
    const continueBtn = document.getElementById('btn_continuar');
    const terminarMessage = document.getElementById('terminarMessage');
    
    if (continueBtn) {
        if (completedPhotos >= totalCategorias && totalCategorias > 0) {
            continueBtn.disabled = false;
            if (terminarMessage) terminarMessage.style.display = 'none';
        } else {
            continueBtn.disabled = true;
            if (terminarMessage) terminarMessage.style.display = 'block';
        }
    }
}

// Funciones para el Overlay de Carga
function showLoadingOverlay(message = 'Subiendo foto...') {
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

// Función para continuar el proceso con overlay
function continuarProceso() {
    const continueBtn = document.getElementById('btn_continuar');
    
    // Verificar si el botón está habilitado
    if (continueBtn.disabled) {
        return;
    }
    
    // Mostrar overlay de carga
    showLoadingOverlay('Analizando fotos con IA...');
    
    // Analizar todas las fotos con OpenAI
    analizarFotosConOpenAI();
}

// Función para analizar fotos con OpenAI
async function analizarFotosConOpenAI() {
    try {
        const categorias = @json($categorias);
        let todasAprobadas = true;
        let fotosConObservaciones = 0;
        
        // Mantener overlay activo durante todo el análisis
        showLoadingOverlay('Iniciando análisis de fotos...');
        
        for (let i = 0; i < categorias.length; i++) {
            const categoria = categorias[i];
            const statusElement = document.getElementById('status-' + categoria.id);
            
            // Debug: verificar elementos
            console.log(`Analizando categoría ${categoria.nombre}:`, {
                statusElement: statusElement,
                hasCompletedClass: statusElement ? statusElement.classList.contains('completed') : false,
                statusClasses: statusElement ? statusElement.className : 'null',
                categoriaId: categoria.id,
                categoriaNombre: categoria.nombre
            });
            
            // Verificar si la foto existe
            console.log(`🔍 PASO 1: Verificando estado para ${categoria.nombre}`, {
                statusElement: statusElement,
                hasCompletedClass: statusElement ? statusElement.classList.contains('completed') : false
            });
            
            if (statusElement && statusElement.classList.contains('completed')) {
                console.log(`✅ PASO 2: Categoría ${categoria.nombre} marcada como completada`);
                
                // Actualizar progreso individual sin ocultar overlay
                updateLoadingProgress((i / categorias.length) * 100);
                    showLoadingOverlay(`Analizando ${categoria.nombre}... (${i + 1}/${categorias.length})`);
                
                // Obtener la URL de la imagen
                const previewElement = document.getElementById('preview_' + categoria.id);
                console.log(`🔍 PASO 3: Buscando preview para ${categoria.nombre}`, {
                    previewElement: previewElement,
                    previewId: 'preview_' + categoria.id,
                    hasSrc: previewElement ? previewElement.src : 'null'
                });
                
                // Validar que el elemento existe y tiene src
                if (!previewElement || !previewElement.src) {
                    console.error(`❌ PASO 4: No se encontró imagen para categoría ${categoria.nombre}`, {
                        previewElement: previewElement,
                        hasSrc: previewElement ? previewElement.src : 'null',
                        categoriaId: categoria.id
                    });
                    continue;
                }
                
                const imageUrl = previewElement.src;
                console.log(`✅ PASO 5: URL obtenida para ${categoria.nombre}:`, imageUrl);
                
                // Validar que la URL de la imagen sea válida y no sea placeholder
                const isPlaceholder = imageUrl.includes('camera-placeholder.png') || 
                                    imageUrl.includes('placeholder.png') ||
                                    imageUrl === '' || 
                                    imageUrl === window.location.href ||
                                    imageUrl.endsWith('camera-placeholder') ||
                                    imageUrl.endsWith('placeholder');
                
                console.log(`🔍 PASO 6: Validación placeholder para ${categoria.nombre}`, {
                    isPlaceholder: isPlaceholder,
                    hasCameraPlaceholder: imageUrl.includes('camera-placeholder.png'),
                    hasPlaceholder: imageUrl.includes('placeholder.png'),
                    endsWithCameraPlaceholder: imageUrl.endsWith('camera-placeholder'),
                    endsWithPlaceholder: imageUrl.endsWith('placeholder'),
                    isEmpty: imageUrl === '',
                    isCurrentUrl: imageUrl === window.location.href
                });
                
                if (isPlaceholder) {
                    console.warn(`⚠️ PASO 7: Imagen placeholder detectada para categoría ${categoria.nombre}:`, imageUrl);
                    continue;
                }
                
                console.log(`🎯 PASO 8: Imagen válida para análisis de ${categoria.nombre}:`, imageUrl);
                console.log(`📊 Detalles de la imagen:`, {
                    url: imageUrl,
                    length: imageUrl.length,
                    isDataUrl: imageUrl.startsWith('data:'),
                    isHttpUrl: imageUrl.startsWith('http'),
                    isRelativeUrl: imageUrl.startsWith('/') || imageUrl.startsWith('./')
                });
                
                console.log(`🚀 PASO 9: Llamando a OpenAI para ${categoria.nombre}...`);
                
                // Llamar a OpenAI
                const response = await fetch('/api/analyze-photo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        image_url: imageUrl,
                        categoria: categoria.nombre,
                        limpieza_id: {{ $id }},
                        categoria_id: categoria.id
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Mostrar resultado de OpenAI
                    mostrarResultadoOpenAI(categoria.id, result.analysis, result.passes_quality);
                    
                    if (!result.passes_quality) {
                        todasAprobadas = false;
                        fotosConObservaciones++;
                    }
                } else {
                    console.error('Error al analizar foto:', result.message);
                    todasAprobadas = false;
                }
                
                // Pequeño delay entre análisis
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
        }
        
        // Ocultar overlay
        hideLoadingOverlay();
        
        // Mostrar sección de resultados
        mostrarResultadosAnalisis(todasAprobadas, fotosConObservaciones);
        
    } catch (error) {
        console.error('Error en el análisis:', error);
        hideLoadingOverlay();
        alert('Error al analizar las fotos. Inténtalo de nuevo.');
    }
}

// Función para mostrar resultado de OpenAI
function mostrarResultadoOpenAI(categoriaId, analysis, passesQuality) {
    const container = document.getElementById('openai-info-' + categoriaId);
    const content = document.getElementById('openai-content-' + categoriaId);
    const status = document.getElementById('quality-status-' + categoriaId);
    const actions = document.getElementById('openai-actions-' + categoriaId);
    
    // Mostrar contenedor
    container.style.display = 'block';
    
    // Actualizar estado de calidad
    if (passesQuality) {
        status.innerHTML = '<i class="fa-solid fa-check-circle text-success"></i>';
        status.className = 'quality-status approved';
        // No mostrar acciones para fotos aprobadas
        actions.style.display = 'none';
    } else {
        status.innerHTML = '<i class="fa-solid fa-exclamation-triangle text-warning"></i>';
        status.className = 'quality-status warning';
        actions.style.display = 'block';
    }
    
    // Insertar análisis simplificado
    let analysisText = '';
    
    // Validar que el análisis sea válido
    if (analysis && typeof analysis === 'object' && analysis !== null) {
        // Mostrar calidad general
        if (analysis.calidad_general) {
            analysisText += `<div class="analysis-item">
                <i class="fa-solid fa-star"></i>
                <strong>Calidad General:</strong> 
                <span class="quality-value ${analysis.calidad_general}">${analysis.calidad_general}</span>
            </div>`;
        }
        
        // Mostrar puntuación
        if (analysis.puntuacion) {
            analysisText += `<div class="analysis-item">
                <i class="fa-solid fa-chart-line"></i>
                <strong>Puntuación:</strong> 
                <span class="score-value">${analysis.puntuacion}/10</span>
            </div>`;
        }
        
        // Mostrar estándares
        if (analysis.cumple_estandares !== undefined) {
            const cumpleText = analysis.cumple_estandares ? 'Sí' : 'No';
            const cumpleClass = analysis.cumple_estandares ? 'success' : 'warning';
            analysisText += `<div class="analysis-item">
                <i class="fa-solid fa-check-double"></i>
                <strong>Cumple Estándares:</strong> 
                <span class="standard-value ${cumpleClass}">${cumpleText}</span>
            </div>`;
        }
        
        // Mostrar deficiencias (siempre)
        if (analysis.deficiencias && Array.isArray(analysis.deficiencias)) {
            if (analysis.deficiencias.length > 0) {
                analysisText += `<div class="analysis-item">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <strong>Deficiencias:</strong>
                    <ul class="deficiencies-list">`;
                analysis.deficiencias.forEach(def => {
                    analysisText += `<li>${def}</li>`;
                });
                analysisText += `</ul></div>`;
            } else {
                analysisText += `<div class="analysis-item">
                    <i class="fa-solid fa-check-circle"></i>
                    <strong>Deficiencias:</strong>
                    <span class="no-deficiencies">Ninguna deficiencia detectada</span>
                </div>`;
            }
        }
        
        // Mostrar observaciones (siempre)
        if (analysis.observaciones) {
            analysisText += `<div class="analysis-item">
                <i class="fa-solid fa-comment"></i>
                <strong>Observaciones:</strong> 
                <span class="observations-text">${analysis.observaciones}</span>
            </div>`;
        }
        
        // Mostrar recomendaciones (siempre)
        if (analysis.recomendaciones && Array.isArray(analysis.recomendaciones)) {
            if (analysis.recomendaciones.length > 0) {
                analysisText += `<div class="analysis-item">
                    <i class="fa-solid fa-lightbulb"></i>
                    <strong>Recomendaciones:</strong>
                    <ul class="recommendations-list">`;
                analysis.recomendaciones.forEach(rec => {
                    analysisText += `<li>${rec}</li>`;
                });
                analysisText += `</ul></div>`;
            } else {
                analysisText += `<div class="analysis-item">
                    <i class="fa-solid fa-thumbs-up"></i>
                    <strong>Recomendaciones:</strong>
                    <span class="no-recommendations">No se requieren acciones adicionales</span>
                </div>`;
            }
        }
    }
    
    // Si no hay análisis válido, mostrar mensaje apropiado
    if (!analysisText) {
        analysisText = `<div class="analysis-error">
            <i class="fa-solid fa-exclamation-circle"></i>
            <span>Análisis no disponible o incompleto</span>
        </div>`;
    }
    
    content.innerHTML = `
        <div class="analysis-content">
            <h6><i class="fa-solid fa-microscope"></i> Análisis de Calidad</h6>
            <div class="analysis-text">${analysisText}</div>
        </div>
    `;
}

// Función para mostrar resultados del análisis
function mostrarResultadosAnalisis(todasAprobadas, warningCount) {
    try {
        // Actualizar estadísticas
        const totalCategorias = {{ count($categorias) }};
        document.getElementById('approvedCount').textContent = totalCategorias - warningCount;
        document.getElementById('warningCount').textContent = warningCount;
        
        // Mostrar sección de resultados
        const resultadosSection = document.getElementById('resultadosAnalisis');
        resultadosSection.style.display = 'block';
        
        // Mostrar acción correspondiente
        if (todasAprobadas) {
            document.getElementById('accionAprobadas').style.display = 'block';
            document.getElementById('accionResponsabilidad').style.display = 'none';
        } else {
            document.getElementById('accionAprobadas').style.display = 'none';
            document.getElementById('accionResponsabilidad').style.display = 'block';
        }
        
        // DESHABILITAR TODOS LOS CAMBIOS DE FOTOS DESPUÉS DEL ANÁLISIS
        deshabilitarCambiosFotos();
        
        // CAMBIAR BOTÓN CONTINUAR POR VOLVER
        cambiarBotonContinuarPorVolver();
        
        // Hacer scroll a la sección de resultados
        resultadosSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        console.log('✅ Resultados del análisis mostrados correctamente');
        
    } catch (error) {
        console.error('Error mostrando resultados:', error);
        // Fallback: alert simple
        if (todasAprobadas) {
            alert('Todas las fotos han pasado el control de calidad. Puedes continuar con el proceso de limpieza.');
        } else {
            alert('Algunas fotos no cumplen completamente con los estándares de calidad. Si decides continuar, asumes la responsabilidad.');
        }
    }
}

// Función para cambiar botón continuar por volver
function cambiarBotonContinuarPorVolver() {
    const btnContinuar = document.getElementById('btn_continuar');
    const btnVolver = document.getElementById('btn_volver');
    
    if (btnContinuar && btnVolver) {
        btnContinuar.style.display = 'none';
        btnVolver.style.display = 'block';
        
        console.log('🔄 Botón cambiado de CONTINUAR a VOLVER');
    }
}

// Función para deshabilitar cambios de fotos después del análisis
function deshabilitarCambiosFotos() {
    const categorias = @json($categorias);
    const analisisExistentes = @json($analisisExistentes ?? []);
    
    categorias.forEach(categoria => {
        const analisis = analisisExistentes[categoria.id];
        const cumpleEstándares = analisis ? analisis.cumple_estandares : false;
        
        // Verificar si la foto ha sido cambiada recientemente (no tiene análisis)
        const tieneAnalisis = analisisExistentes[categoria.id];
        const fotoCambiada = !tieneAnalisis;
        
        // Si la foto cumple estándares Y tiene análisis, bloquear completamente
        if (cumpleEstándares && tieneAnalisis) {
            // Deshabilitar input de archivo
            const fileInput = document.getElementById('image_' + categoria.id);
            if (fileInput) {
                fileInput.disabled = true;
                fileInput.style.opacity = '0.5';
                fileInput.style.pointerEvents = 'none';
            }
            
            // Deshabilitar botón de cámara
            const cameraBtn = document.querySelector(`button[onclick="document.getElementById('image_${categoria.id}').click()"]`);
            if (cameraBtn) {
                cameraBtn.disabled = true;
                cameraBtn.style.opacity = '0.5';
                cameraBtn.style.pointerEvents = 'none';
                cameraBtn.innerHTML = '<i class="fa-solid fa-lock"></i><span>BLOQUEADO</span>';
            }
            
            // Ocultar botón de modificar si existe
            const modificarBtn = document.getElementById('btn-modificar-' + categoria.id);
            if (modificarBtn) {
                modificarBtn.style.display = 'none';
            }
            
            // Añadir indicador visual de bloqueado
            const previewContainer = document.getElementById('preview-container-' + categoria.id);
            if (previewContainer) {
                previewContainer.style.position = 'relative';
                if (!previewContainer.querySelector('.locked-indicator')) {
                    const lockedIndicator = document.createElement('div');
                    lockedIndicator.className = 'locked-indicator';
                    lockedIndicator.innerHTML = '<i class="fa-solid fa-lock"></i><span>Análisis Completado</span>';
                    lockedIndicator.style.cssText = `
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background: rgba(0, 0, 0, 0.8);
                        color: white;
                        padding: 10px 15px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: bold;
                        z-index: 10;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    `;
                    previewContainer.appendChild(lockedIndicator);
                }
            }
        } else if (!cumpleEstándares && tieneAnalisis) {
            // Si la foto NO cumple estándares Y tiene análisis, permitir re-análisis
            const fileInput = document.getElementById('image_' + categoria.id);
            if (fileInput) {
                fileInput.disabled = false;
                fileInput.style.opacity = '1';
                fileInput.style.pointerEvents = 'auto';
            }
            
            // Habilitar botón de cámara para re-análisis
            const cameraBtn = document.querySelector(`button[onclick="document.getElementById('image_${categoria.id}').click()"]`);
            if (cameraBtn) {
                cameraBtn.disabled = false;
                cameraBtn.style.opacity = '1';
                cameraBtn.style.pointerEvents = 'auto';
                cameraBtn.innerHTML = '<i class="fa-solid fa-camera"></i><span>RE-ANALIZAR</span>';
                cameraBtn.classList.remove('blocked');
                cameraBtn.classList.add('re-analyze');
            }
            
            // Mostrar botón de modificar para re-análisis
            const modificarBtn = document.getElementById('btn-modificar-' + categoria.id);
            if (modificarBtn) {
                modificarBtn.style.display = 'block';
                modificarBtn.innerHTML = '<i class="fa-solid fa-redo"></i><span>Re-analizar</span>';
                modificarBtn.classList.add('re-analyze-btn');
            }
            
            // Añadir indicador visual de re-análisis disponible
            const previewContainer = document.getElementById('preview-container-' + categoria.id);
            if (previewContainer) {
                previewContainer.style.position = 'relative';
                if (!previewContainer.querySelector('.re-analyze-indicator')) {
                    const reAnalyzeIndicator = document.createElement('div');
                    reAnalyzeIndicator.className = 're-analyze-indicator';
                    reAnalyzeIndicator.innerHTML = '<i class="fa-solid fa-redo"></i><span>Re-análisis Disponible</span>';
                    reAnalyzeIndicator.style.cssText = `
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        background: rgba(255, 193, 7, 0.9);
                        color: #212529;
                        padding: 8px 12px;
                        border-radius: 15px;
                        font-size: 11px;
                        font-weight: bold;
                        z-index: 10;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                    `;
                    previewContainer.appendChild(reAnalyzeIndicator);
                }
            }
        } else if (fotoCambiada) {
            // Si la foto ha sido cambiada (no tiene análisis), permitir análisis normal
            const fileInput = document.getElementById('image_' + categoria.id);
            if (fileInput) {
                fileInput.disabled = false;
                fileInput.style.opacity = '1';
                fileInput.style.pointerEvents = 'auto';
            }
            
            // Resetear botón de cámara a estado normal
            const cameraBtn = document.querySelector(`button[onclick="document.getElementById('image_${categoria.id}').click()"]`);
            if (cameraBtn) {
                cameraBtn.disabled = false;
                cameraBtn.style.opacity = '1';
                cameraBtn.style.pointerEvents = 'auto';
                cameraBtn.innerHTML = '<i class="fa-solid fa-camera"></i><span>CÁMARA</span>';
                cameraBtn.classList.remove('blocked', 're-analyze');
            }
            
            // Mostrar botón de modificar normal
            const modificarBtn = document.getElementById('btn-modificar-' + categoria.id);
            if (modificarBtn) {
                modificarBtn.style.display = 'block';
                modificarBtn.innerHTML = '<i class="fa-solid fa-edit"></i><span>Modificar</span>';
                modificarBtn.classList.remove('re-analyze-btn');
            }
            
            // Remover indicadores visuales
            const previewContainer = document.getElementById('preview-container-' + categoria.id);
            if (previewContainer) {
                const lockedIndicator = previewContainer.querySelector('.locked-indicator');
                if (lockedIndicator) {
                    lockedIndicator.remove();
                }
                
                const reAnalyzeIndicator = previewContainer.querySelector('.re-analyze-indicator');
                if (reAnalyzeIndicator) {
                    reAnalyzeIndicator.remove();
                }
            }
        }
    });
    
    console.log('🔒 Cambios de fotos configurados según estándares de calidad y estado de análisis');
}

// Función para modificar foto (SOLO DISPONIBLE ANTES DEL ANÁLISIS)
function modificarFoto(categoriaId) {
    // Verificar si la foto cumple estándares
    const analisisExistentes = @json($analisisExistentes ?? []);
    const analisis = analisisExistentes[categoriaId];
    
    // Si la foto cumple estándares, no permitir modificación
    if (analisis && analisis.cumple_estandares) {
        alert('No se pueden modificar las fotos que ya cumplen los estándares de calidad.');
        return;
    }
    
    // Si la foto NO cumple estándares, permitir re-análisis
    if (analisis && !analisis.cumple_estandares) {
        // Ocultar información de OpenAI
        const openaiContainer = document.getElementById('openai-info-' + categoriaId);
        if (openaiContainer) {
            openaiContainer.style.display = 'none';
        }
        
        // Limpiar preview y estado
        const previewElement = document.getElementById('preview-' + categoriaId);
        const containerElement = document.getElementById('preview-container-' + categoriaId);
        const statusElement = document.getElementById('status-' + categoriaId);
        
        if (previewElement) {
            previewElement.src = '';
        }
        if (containerElement) {
            containerElement.classList.remove('has-image');
        }
        if (statusElement) {
            statusElement.classList.remove('completed');
        }
        
        // Habilitar input de archivo
        const fileInput = document.getElementById('image_' + categoriaId);
        if (fileInput) {
            fileInput.value = '';
            fileInput.disabled = false;
            fileInput.style.opacity = '1';
            fileInput.style.pointerEvents = 'auto';
        }
        
        // Resetear botón de cámara
        const cameraBtn = document.querySelector(`button[onclick="document.getElementById('image_${categoriaId}').click()"]`);
        if (cameraBtn) {
            cameraBtn.disabled = false;
            cameraBtn.style.opacity = '1';
            cameraBtn.style.pointerEvents = 'auto';
            cameraBtn.innerHTML = '<i class="fa-solid fa-camera"></i><span>CÁMARA</span>';
            cameraBtn.classList.remove('blocked', 're-analyze');
        }
        
        // Resetear botón de modificar
        const modificarBtn = document.getElementById('btn-modificar-' + categoriaId);
        if (modificarBtn) {
            modificarBtn.innerHTML = '<i class="fa-solid fa-edit"></i><span>Modificar</span>';
            modificarBtn.classList.remove('re-analyze-btn');
        }
        
        // Remover indicadores visuales
        const previewContainer = document.getElementById('preview-container-' + categoriaId);
        if (previewContainer) {
            const lockedIndicator = previewContainer.querySelector('.locked-indicator');
            if (lockedIndicator) {
                lockedIndicator.remove();
            }
            
            const reAnalyzeIndicator = previewContainer.querySelector('.re-analyze-indicator');
            if (reAnalyzeIndicator) {
                reAnalyzeIndicator.remove();
            }
        }
        
        console.log(`🔄 Foto de categoría ${categoriaId} preparada para re-análisis`);
        
        // Verificar estado del botón continuar
        checkContinueButton();
        return;
    }
    
    // Si no hay análisis, permitir modificación normal
    // Ocultar información de OpenAI
    const openaiContainer = document.getElementById('openai-info-' + categoriaId);
    if (openaiContainer) {
        openaiContainer.style.display = 'none';
    }
    
    // Limpiar preview y estado
    const previewElement = document.getElementById('preview-' + categoriaId);
    const containerElement = document.getElementById('preview-container-' + categoriaId);
    const statusElement = document.getElementById('status-' + categoriaId);
    
    if (previewElement) {
        previewElement.src = '';
    }
    if (containerElement) {
        containerElement.classList.remove('has-image');
    }
    if (statusElement) {
        statusElement.classList.remove('completed');
    }
    
    // Habilitar input de archivo
    const fileInput = document.getElementById('image_' + categoriaId);
    if (fileInput) {
        fileInput.value = '';
    }
    
    // Verificar estado del botón continuar
    checkContinueButton();
}

// Función para continuar con limpieza
function continuarConLimpieza() {
    @if(isset($limpieza) && $limpieza->tarea_asignada_id)
        // Nuevo sistema: volver al checklist de la tarea
        window.location.href = "{{ route('gestion.tareas.checklist', $limpieza->tarea_asignada_id) }}";
    @else
        // Sistema antiguo: volver a la edición de limpieza
        window.location.href = "{{ route('gestion.edit', $id) }}";
    @endif
}

// Función para volver a la gestión de limpieza
function volverALimpieza() {
    @if(isset($limpieza) && $limpieza->tarea_asignada_id)
        // Nuevo sistema: volver al checklist de la tarea
        window.location.href = "{{ route('gestion.tareas.checklist', $limpieza->tarea_asignada_id) }}";
    @else
        // Sistema antiguo: volver a la edición de limpieza
        window.location.href = "{{ route('gestion.edit', $id) }}";
    @endif
}

// Función para continuar con responsabilidad
function continuarConResponsabilidad() {
    // Marcar que continuó bajo responsabilidad
    const limpiezaId = {{ $limpieza->id }};
    
    // Mostrar loading
    showLoadingOverlay('Guardando responsabilidad...');
    
    // Llamar a la API para marcar responsabilidad
    fetch('/api/mark-responsibility', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            limpieza_id: limpiezaId,
            continuo_bajo_responsabilidad: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideLoadingOverlay();
            // Redirigir según el tipo de limpieza (tarea nueva o antigua)
            @if(isset($limpieza) && $limpieza->tarea_asignada_id)
                // Es una tarea nueva, redirigir a la vista de tareas
                window.location.href = "{{ route('gestion.tareas.checklist', $limpieza->tarea_asignada_id) }}";
            @else
                // Es una limpieza antigua, redirigir a la vista de gestión
                window.location.href = "{{ route('gestion.edit', $id) }}";
            @endif
        } else {
            hideLoadingOverlay();
            alert('Error al guardar la responsabilidad: ' + data.message);
        }
    })
    .catch(error => {
        hideLoadingOverlay();
        console.error('Error:', error);
        alert('Error al guardar la responsabilidad');
    });
}

// Función para re-analizar fotos rechazadas
async function reAnalizarFotosRechazadas() {
    try {
        const continuarBtn = document.getElementById('btn_continuar');
        const continuarMessage = document.getElementById('terminarMessage');
        const resultadosSection = document.getElementById('resultadosAnalisis');

        if (continuarBtn && continuarMessage && resultadosSection) {
            if (continuarBtn.disabled) {
                alert('No puedes re-analizar fotos si el botón "Continuar" está deshabilitado.');
                return;
            }

            if (resultadosSection.style.display === 'none') {
                alert('No hay resultados de análisis para re-analizar.');
                return;
            }

            if (!confirm('¿Estás seguro de que quieres re-analizar solo las fotos que no pasaron el control de calidad? Esto deshabilitará los botones de cámara y modificará las fotos que ya estaban marcadas como completadas.')) {
                return;
            }

            // Deshabilitar todos los cambios de fotos
            deshabilitarCambiosFotos();

            // Mostrar mensaje de re-análisis
            continuarMessage.style.display = 'block';
            continuarMessage.innerHTML = `
                <div class="message-content">
                    <i class="fa-solid fa-info-circle"></i>
                    <span>Para activar el botón "Continuar", debes subir todas las fotos requeridas de cada sección.</span>
                </div>
            `;

            // Re-analizar solo las fotos que no pasaron el control de calidad
            const categorias = @json($categorias);
            const analisisExistentes = @json($analisisExistentes ?? []);
            const fotosRechazadas = [];

            categorias.forEach(categoria => {
                if (analisisExistentes[categoria.id] && !analisisExistentes[categoria.id].cumple_estandares) {
                    fotosRechazadas.push(categoria.id);
                }
            });

            if (fotosRechazadas.length === 0) {
                alert('No hay fotos rechazadas para re-analizar.');
                return;
            }

            // Mostrar overlay de carga
            showLoadingOverlay('Re-analizando fotos...');

            // Analizar cada foto rechazada
            let fotosRechazadasAnalizadas = 0;
            for (let i = 0; i < fotosRechazadas.length; i++) {
                const categoriaId = fotosRechazadas[i];
                const statusElement = document.getElementById('status-' + categoriaId);
                const previewElement = document.getElementById('preview_' + categoriaId);

                if (statusElement && statusElement.classList.contains('completed') && previewElement) {
                    // Actualizar progreso individual sin ocultar overlay
                    updateLoadingProgress(((i + 1) / fotosRechazadas.length) * 100);
                    showLoadingOverlay(`Re-analizando foto ${fotosRechazadasAnalizadas + 1} de ${fotosRechazadas.length}...`);

                    const imageUrl = previewElement.src;
                    const isPlaceholder = imageUrl.includes('camera-placeholder.png') || 
                                        imageUrl.includes('placeholder.png') ||
                                        imageUrl === '' || 
                                        imageUrl === window.location.href ||
                                        imageUrl.endsWith('camera-placeholder') ||
                                        imageUrl.endsWith('placeholder');

                    if (isPlaceholder) {
                        console.warn(`⚠️ Imagen placeholder detectada para re-análisis de ${categoriaId}:`, imageUrl);
                        continue;
                    }

                    console.log(`🚀 Llamando a OpenAI para re-análisis de ${categoriaId}:`, imageUrl);

                    const response = await fetch('/api/analyze-photo', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            image_url: imageUrl,
                            categoria: categorias.find(c => c.id === categoriaId).nombre,
                            limpieza_id: {{ $id }},
                            categoria_id: categoriaId
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        mostrarResultadoOpenAI(categoriaId, result.analysis, result.passes_quality);
                        if (!result.passes_quality) {
                            fotosRechazadasAnalizadas++;
                        }
                    } else {
                        console.error('Error al re-analizar foto:', result.message);
                    }

                    await new Promise(resolve => setTimeout(resolve, 1000)); // Pequeño delay entre re-análisis
                }
            }

            hideLoadingOverlay();
            mostrarResultadosAnalisis(fotosRechazadasAnalizadas === fotosRechazadas.length, fotosRechazadas.length - fotosRechazadasAnalizadas);
            console.log(`✅ Re-análisis de ${fotosRechazadasAnalizadas} fotos rechazadas completado.`);

        }
    } catch (error) {
        console.error('Error en el re-análisis:', error);
        hideLoadingOverlay();
        alert('Error al re-analizar las fotos. Inténtalo de nuevo.');
    }
}

// BLOQUEAR AUTOMÁTICAMENTE LAS FOTOS YA ANALIZADAS AL CARGAR LA PÁGINA
document.addEventListener('DOMContentLoaded', function() {
    bloquearFotosYaAnalizadas();
});

// Función para bloquear fotos ya analizadas al cargar la página
function bloquearFotosYaAnalizadas() {
    const categorias = @json($categorias);
    const analisisExistentes = @json($analisisExistentes ?? []);
    
    // Verificar si hay análisis existentes
    if (Object.keys(analisisExistentes).length > 0) {
        console.log('🔍 Detectando análisis existentes al cargar la página...');
        
        categorias.forEach(categoria => {
            if (analisisExistentes[categoria.id]) {
                const analisis = analisisExistentes[categoria.id];
                const cumpleEstándares = analisis.cumple_estandares;
                
                console.log(`✅ Categoría ${categoria.nombre} ya analizada, cumple estándares: ${cumpleEstándares}`);
                
                if (cumpleEstándares) {
                    // Bloquear completamente fotos que cumplen estándares
                    const fileInput = document.getElementById('image_' + categoria.id);
                    if (fileInput) {
                        fileInput.disabled = true;
                        fileInput.style.opacity = '0.5';
                        fileInput.style.pointerEvents = 'none';
                    }
                    
                    const cameraBtn = document.querySelector(`button[onclick="document.getElementById('image_${categoria.id}').click()"]`);
                    if (cameraBtn) {
                        cameraBtn.disabled = true;
                        cameraBtn.style.opacity = '0.5';
                        cameraBtn.style.pointerEvents = 'none';
                        cameraBtn.innerHTML = '<i class="fa-solid fa-lock"></i><span>BLOQUEADO</span>';
                        cameraBtn.classList.add('blocked');
                    }
                    
                    // Añadir indicador visual de bloqueado
                    const previewContainer = document.getElementById('preview-container-' + categoria.id);
                    if (previewContainer) {
                        previewContainer.style.position = 'relative';
                        if (!previewContainer.querySelector('.locked-indicator')) {
                            const lockedIndicator = document.createElement('div');
                            lockedIndicator.className = 'locked-indicator';
                            lockedIndicator.innerHTML = '<i class="fa-solid fa-lock"></i><span>Análisis Completado</span>';
                            lockedIndicator.style.cssText = `
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                background: rgba(0, 0, 0, 0.8);
                                color: white;
                                padding: 10px 15px;
                                border-radius: 20px;
                                font-size: 12px;
                                font-weight: bold;
                                z-index: 10;
                                display: flex;
                                align-items: center;
                                gap: 8px;
                            `;
                            previewContainer.appendChild(lockedIndicator);
                        }
                    }
                    
                    // Marcar como completada
                    const statusElement = document.getElementById('status-' + categoria.id);
                    if (statusElement) {
                        statusElement.classList.add('completed');
                        statusElement.innerHTML = '<i class="fa-solid fa-check-circle text-success"></i>';
                    }
                } else {
                    // Para fotos que NO cumplen estándares, permitir re-análisis
                    const fileInput = document.getElementById('image_' + categoria.id);
                    if (fileInput) {
                        fileInput.disabled = false;
                        fileInput.style.opacity = '1';
                        fileInput.style.pointerEvents = 'auto';
                    }
                    
                    const cameraBtn = document.querySelector(`button[onclick="document.getElementById('image_${categoria.id}').click()"]`);
                    if (cameraBtn) {
                        cameraBtn.disabled = false;
                        cameraBtn.style.opacity = '1';
                        cameraBtn.style.pointerEvents = 'auto';
                        cameraBtn.innerHTML = '<i class="fa-solid fa-camera"></i><span>RE-ANALIZAR</span>';
                        cameraBtn.classList.remove('blocked');
                        cameraBtn.classList.add('re-analyze');
                    }
                    
                    // Mostrar botón de modificar para re-análisis
                    const modificarBtn = document.getElementById('btn-modificar-' + categoria.id);
                    if (modificarBtn) {
                        modificarBtn.style.display = 'block';
                        modificarBtn.innerHTML = '<i class="fa-solid fa-redo"></i><span>Re-analizar</span>';
                        modificarBtn.classList.add('re-analyze-btn');
                    }
                    
                    // Añadir indicador visual de re-análisis disponible
                    const previewContainer = document.getElementById('preview-container-' + categoria.id);
                    if (previewContainer) {
                        previewContainer.style.position = 'relative';
                        if (!previewContainer.querySelector('.re-analyze-indicator')) {
                            const reAnalyzeIndicator = document.createElement('div');
                            reAnalyzeIndicator.className = 're-analyze-indicator';
                            reAnalyzeIndicator.innerHTML = '<i class="fa-solid fa-redo"></i><span>Re-análisis Disponible</span>';
                            reAnalyzeIndicator.style.cssText = `
                                position: absolute;
                                top: 10px;
                                right: 10px;
                                background: rgba(255, 193, 7, 0.9);
                                color: #212529;
                                padding: 8px 12px;
                                border-radius: 15px;
                                font-size: 11px;
                                font-weight: bold;
                                z-index: 10;
                                display: flex;
                                align-items: center;
                                gap: 6px;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                            `;
                            previewContainer.appendChild(reAnalyzeIndicator);
                        }
                    }
                    
                    // Marcar como con observaciones
                    const statusElement = document.getElementById('status-' + categoria.id);
                    if (statusElement) {
                        statusElement.classList.add('warning');
                        statusElement.innerHTML = '<i class="fa-solid fa-exclamation-triangle text-warning"></i>';
                    }
                }
            }
        });
        
        // Mostrar sección de resultados si hay análisis
        mostrarResultadosExistentes();
        
        console.log('🔒 Fotos analizadas han sido configuradas según estándares de calidad');
    }
}

// Función para mostrar resultados existentes al cargar la página
function mostrarResultadosExistentes() {
    const analisisExistentes = @json($analisisExistentes ?? []);
    
    if (Object.keys(analisisExistentes).length > 0) {
        // Contar fotos aprobadas y con observaciones
        let fotosAprobadas = 0;
        let fotosConObservaciones = 0;
        
        Object.values(analisisExistentes).forEach(analisis => {
            if (analisis.cumple_estandares) {
                fotosAprobadas++;
            } else {
                fotosConObservaciones++;
            }
        });
        
        // Actualizar estadísticas
        document.getElementById('approvedCount').textContent = fotosAprobadas;
        document.getElementById('warningCount').textContent = fotosConObservaciones;
        
        // Mostrar sección de resultados
        const resultadosSection = document.getElementById('resultadosAnalisis');
        resultadosSection.style.display = 'block';
        
        // Mostrar acción correspondiente
        if (fotosConObservaciones === 0) {
            document.getElementById('accionAprobadas').style.display = 'block';
            document.getElementById('accionResponsabilidad').style.display = 'none';
        } else {
            document.getElementById('accionAprobadas').style.display = 'none';
            document.getElementById('accionResponsabilidad').style.display = 'block';
        }
        
        // Cambiar botón continuar por volver
        cambiarBotonContinuarPorVolver();
        
        console.log('📊 Resultados existentes mostrados automáticamente');
    }
}
</script>

<style>
/* Estilos para indicador de bloqueado */
.locked-indicator {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

/* Estilos para elementos deshabilitados */
.disabled-element {
    opacity: 0.5 !important;
    pointer-events: none !important;
}

/* Estilos para botón volver */
.apple-btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.apple-btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
    background: linear-gradient(135deg, #5a6268 0%, #343a40 100%);
}

/* Estilos para botón de cámara bloqueado */
.apple-camera-btn.blocked {
    background-color: #e0e0e0 !important;
    color: #888 !important;
    cursor: not-allowed !important;
    border: 1px solid #ccc !important;
    opacity: 0.7 !important;
}

.apple-camera-btn.blocked:hover {
    background-color: #e0e0e0 !important;
    color: #888 !important;
    transform: none !important;
}

.apple-camera-btn.blocked .fa-lock {
    margin-right: 8px;
}

/* Estilos para botón de re-análisis */
.apple-camera-btn.re-analyze {
    background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%) !important;
    color: #212529 !important;
    border: 2px solid #ffc107 !important;
    animation: pulse-warning 2s infinite;
}

.apple-camera-btn.re-analyze:hover {
    background: linear-gradient(135deg, #ffb300 0%, #ff6f00 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
}

@keyframes pulse-warning {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}

/* Estilos para botón de modificar re-análisis */
.re-analyze-btn {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
    color: white !important;
    border: none !important;
    padding: 8px 16px !important;
    border-radius: 20px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3) !important;
}

.re-analyze-btn:hover {
    background: linear-gradient(135deg, #138496 0%, #117a8b 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4) !important;
}

/* Estilos para indicador de re-análisis */
.re-analyze-indicator {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-5px); }
    60% { transform: translateY(-3px); }
}

/* Estilos para botón de re-análisis en la sección de resultados */
.apple-btn-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
}

.apple-btn-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
    background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
}

</style>
@endsection
