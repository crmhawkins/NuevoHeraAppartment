@extends('layouts.appPersonal')

@section('title')
    {{ __('Checklist de Tarea - ') . $tarea->tipoTarea->nombre}}
@endsection

@section('bienvenido')
    <h5 class="navbar-brand mb-0 w-auto text-center text-white">Bienvenid@ {{Auth::user()->name}}</h5>
@endsection

@section('content')
<div class="apple-container">
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            @if($tarea->apartamento_id)
                                <i class="fa-solid fa-building"></i>
                            @elseif($tarea->zona_comun_id)
                                <i class="fa-solid fa-users"></i>
                            @else
                                <i class="fa-solid fa-tasks"></i>
                            @endif
                        </div>
                        <div class="apartment-details">
                            <h1 class="apartment-title">
                                @if($tarea->apartamento_id)
                                    {{ $tarea->apartamento->titulo }}
                                @elseif($tarea->zona_comun_id)
                                    {{ $tarea->zonaComun->nombre }}
                                @else
                                    {{ $tarea->tipoTarea->nombre }}
                                @endif
                            </h1>
                            <p class="apartment-subtitle">{{ $tarea->tipoTarea->nombre }}</p>
                        </div>
                    </div>
                    
                    <div class="header-actions">
                        <a href="{{ route('gestion.index') }}" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="progress-badge mt-3 w-75 mx-auto mb-0" id="checklistProgress">
            <div class="progress-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="progress-text">
                <span class="progress-count"><span id="checklistCount">0</span>/<span id="checklistTotal">0</span></span>
                <span class="progress-label">Items</span>
            </div>
        </div>
        
        <!-- Información de la Siguiente Reserva -->
        @if($siguienteReserva)
        <div class="siguiente-reserva-banner mt-3 w-75 mx-auto" style="
            background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
            border: 2px solid #2196F3;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        ">
            <!-- Header de la Reserva -->
            <div class="d-flex align-items-center justify-content-center mb-3">
                <i class="fas fa-calendar-alt text-primary me-2" style="font-size: 1.5em;"></i>
                <div>
                    <strong class="text-primary" style="font-size: 1.1em;">Próxima Reserva</strong>
                    <span class="text-dark ms-2" style="font-size: 1.1em;">{{ \Carbon\Carbon::parse($siguienteReserva->fecha_entrada)->format('d/m/Y') }}</span>
                </div>
            </div>
            
            <!-- Información del Cliente -->
            @if($siguienteReserva->cliente)
            <div class="cliente-info mb-3">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-user text-primary me-2"></i>
                    <strong class="text-primary">{{ $siguienteReserva->cliente->nombre ?? 'N/A' }}</strong>
                    @if($siguienteReserva->cliente->telefono)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siguienteReserva->cliente->telefono) }}" 
                           target="_blank" 
                           class="btn btn-primary btn-sm ms-2">
                            <i class="fab fa-whatsapp"></i>
                            WhatsApp
                        </a>
                    @endif
                </div>
                @if($siguienteReserva->cliente->email)
                <div class="cliente-email mt-1">
                    <i class="fas fa-envelope text-info me-1"></i>
                    <span class="text-dark">{{ $siguienteReserva->cliente->email }}</span>
                </div>
                @endif
            </div>
            @endif
            
            <!-- Detalles de la Reserva -->
            <div class="reserva-details">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="detail-item">
                            <i class="fas fa-users text-primary mb-1"></i>
                            <div class="detail-value">{{ $siguienteReserva->numero_personas ?? 0 }}</div>
                            <div class="detail-label">Personas</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="detail-item">
                            <i class="fas fa-child text-primary mb-1"></i>
                            <div class="detail-value">{{ $siguienteReserva->numero_ninos ?? 0 }}</div>
                            <div class="detail-label">Niños</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="detail-item">
                            <i class="fas fa-calendar-check text-primary mb-1"></i>
                            <div class="detail-value">{{ \Carbon\Carbon::parse($siguienteReserva->fecha_salida)->format('d/m') }}</div>
                            <div class="detail-label">Salida</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Banner de Información -->
        <div class="info-banner mt-3 w-75 mx-auto" style="
            background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
            border: 2px solid #FF9800;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        ">
            <div class="d-flex align-items-center justify-content-center">
                <i class="fas fa-info-circle text-primary me-2" style="font-size: 1.2em;"></i>
                <div>
                    <strong class="text-primary">Información Importante:</strong>
                    <span class="text-dark ms-2">Puedes finalizar la tarea sin completar todos los checklists, pero asegúrate de revisar la calidad general.</span>
                </div>
            </div>
        </div>
        
        <div class="apple-card-body">
            <form action="{{ route('gestion.updateTarea', $tarea) }}" method="POST" id="formPrincipalLimpieza" data-limpieza-id="{{ $tarea->apartamentoLimpieza ? $tarea->apartamentoLimpieza->id : 0 }}">
                @csrf
                <input type="hidden" name="id" value="{{ $tarea->id }}">

                @foreach ($checklists as $checklist)
                @php
                // Normaliza el nombre para usar como identificador
                $nombreHabitacion = strtolower(str_replace(' ', '_', $checklist->nombre));
                // Quitar tildes manualmente
                $nombreHabitacion = strtr($nombreHabitacion, [
                    'á' => 'a', 'é' => 'e', 'í' => 'i',
                    'ó' => 'o', 'ú' => 'u',
                    'Á' => 'a', 'É' => 'e', 'Í' => 'i',
                    'Ó' => 'o', 'Ú' => 'u',
                    'ñ' => 'n', 'Ñ' => 'n',
                ]);
            @endphp

                    <div class="checklist-section">
                        <div class="section-header">
                            <div class="section-title-container">
                                @php
                                    $iconClass = 'fa-solid fa-home';
                                    switch(strtolower($checklist->nombre)) {
                                        case 'salon':
                                        case 'sala':
                                            $iconClass = 'fa-solid fa-couch';
                                            break;
                                        case 'dormitorio':
                                        case 'habitacion':
                                            $iconClass = 'fa-solid fa-bed';
                                            break;
                                        case 'cocina':
                                            $iconClass = 'fa-solid fa-utensils';
                                            break;
                                        case 'baño':
                                        case 'bano':
                                        case 'aseo':
                                            $iconClass = 'fa-solid fa-bath';
                                            break;
                                        case 'comedor':
                                            $iconClass = 'fa-solid fa-utensils';
                                            break;
                                        case 'terraza':
                                        case 'balcon':
                                            $iconClass = 'fa-solid fa-umbrella-beach';
                                            break;
                                        case 'escalera':
                                            $iconClass = 'fa-solid fa-stairs';
                                            break;
                                        case 'ascensor':
                                            $iconClass = 'fa-solid fa-elevator';
                                            break;
                                        case 'amenities':
                                            $iconClass = 'fa-solid fa-gift';
                                            break;
                                        case 'armario':
                                            $iconClass = 'fa-solid fa-door-closed';
                                            break;
                                        case 'canape':
                                            $iconClass = 'fa-solid fa-couch';
                                            break;
                                        case 'perchero':
                                            $iconClass = 'fa-solid fa-hanger';
                                            break;
                                        default:
                                            $iconClass = 'fa-solid fa-check-square';
                                    }
                                @endphp
                                <i class="{{ $iconClass }} section-icon"></i>
                                <h3 class="section-title">{{ strtoupper($checklist->nombre) }}</h3>
                            </div>
                            <div class="section-controls">
                                <div class="apple-switch-container">
                                    @php
                                    $isChecklistChecked = isset($checklistsExistentes[$checklist->id]) && $checklistsExistentes[$checklist->id] == 1;
                                    @endphp
                                    <input
                                    {{ isset($checklistsExistentes[$checklist->id]) && $checklistsExistentes[$checklist->id] == 1 ? 'checked' : '' }}
                                    class="apple-switch category-switch"
                                    value="1"
                                    name="checklist[{{ $checklist->id }}]"
                                    type="checkbox"
                                    data-habitacion="{{ $checklist->id }}"
                                    id="checklist_{{ $checklist->id }}">
                                    <label for="checklist_{{ $checklist->id }}" class="apple-switch-label">
                                        <span class="apple-switch-slider"></span>
                                    </label>
                                </div>
                                
                                {{-- Botones de cámara no aplicables en el sistema de tareas asignadas --}}
                            </div>
                        </div>
                        
                        <div class="items-container" id="items_{{ $checklist->id }}">
                            @if($checklist->items && $checklist->items->count() > 0)
                                @foreach($checklist->items as $item)
                                    <div class="item-row">
                                        <div class="item-info">
                                            <span class="item-name">{{ $item->nombre }}</span>
                                            @if($item->articulo)
                                                <span class="item-article">({{ $item->articulo->nombre }})</span>
                                            @endif
                                        </div>
                                        <div class="item-controls">
                                            <input type="checkbox" 
                                                   class="item-checkbox" 
                                                   data-item-id="{{ $item->id }}"
                                                   data-checklist-id="{{ $checklist->id }}"
                                                   name="items[{{ $item->id }}]"
                                                   value="1"
                                                   {{ in_array($item->id, $elementosCompletados) ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted">No hay items en este checklist</p>
                            @endif
                        </div>
                    </div>
                @endforeach

                <!-- Sección de Amenities - Solo información -->
                @if($tarea->apartamento_id && isset($amenitiesConRecomendaciones) && count($amenitiesConRecomendaciones) > 0)
                <div class="amenities-section">
                    <div class="amenities-header">
                        <div class="amenities-title">
                            <i class="fas fa-gift amenities-icon"></i>
                            <h3>Estado de Amenities</h3>
                            <small class="text-muted">Información del stock actual</small>
                        </div>
                    </div>
                    
                    <div class="amenities-content">
                        @foreach($amenitiesConRecomendaciones as $categoria => $amenitiesCategoria)
                            <div class="amenities-category mb-3">
                                <h5 class="category-title text-primary">{{ $categoria }}</h5>
                                <div class="amenities-list">
                                    @foreach($amenitiesCategoria as $amenityData)
                                        @php
                                            $amenity = $amenityData['amenity'];
                                            $cantidadRecomendada = $amenityData['cantidad_recomendada'];
                                            $consumoExistente = $amenityData['consumo_existente'];
                                            $stockDisponible = $amenityData['stock_disponible'];
                                            $esAutomaticoNinos = $amenityData['es_automatico_ninos'] ?? false;
                                            $motivoNinos = $amenityData['motivo_ninos'] ?? '';
                                        @endphp
                                        
                                        <div class="amenity-item-simple d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div class="amenity-info">
                                                <span class="amenity-name fw-bold">{{ $amenity->nombre }}</span>
                                                @if($esAutomaticoNinos)
                                                    <span class="badge bg-info ms-2">{{ $motivoNinos }}</span>
                                                @endif
                                            </div>
                                            <div class="amenity-details text-end">
                                                <small class="text-muted">
                                                    Recomendado: <strong>{{ $cantidadRecomendada }}</strong> | 
                                                    Stock: <span class="{{ $stockDisponible < $cantidadRecomendada ? 'text-danger' : 'text-success' }}">{{ $stockDisponible }}</span>
                                                </small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Botones de Acción -->
                <div class="checklist-actions mt-4">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-primary btn-lg w-100" onclick="guardarProgreso()">
                                <i class="fas fa-save me-2"></i>
                                Guardar Progreso
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-success btn-lg w-100" onclick="finalizarTarea()">
                                <i class="fas fa-check me-2"></i>
                                Finalizar Tarea
                            </button>
                        </div>
                    </div>
                    
                    <!-- Checkbox de consentimiento para finalizar sin completar todos los checklists -->
                    <div class="consentimiento-section mt-3" style="
                        background: linear-gradient(135deg, #FFF8E1 0%, #FFECB3 100%);
                        border: 2px solid #FFC107;
                        border-radius: 12px;
                        padding: 20px;
                        text-align: center;
                        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
                    ">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="consentimientoFinalizar" style="transform: scale(1.2);">
                            <label class="form-check-label" for="consentimientoFinalizar">
                                <strong>Consentimiento para Finalizar sin Checklists Completos</strong>
                            </label>
                        </div>
                        <div class="form-group mb-3" id="motivoConsentimientoGroup" style="display: none;">
                            <label for="motivoConsentimiento" class="form-label">
                                <strong>Motivo del consentimiento:</strong>
                            </label>
                            <textarea class="form-control" 
                                    id="motivoConsentimiento" 
                                    name="motivo_consentimiento" 
                                    rows="3" 
                                    placeholder="Explica brevemente por qué no se completaron todos los checklists..."></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Campos ocultos para el formulario -->
                <input type="hidden" name="consentimiento_finalizacion" id="consentimientoFinalizarHidden" value="false">
                <input type="hidden" name="motivo_consentimiento" id="motivoConsentimientoHidden" value="">
                <input type="hidden" name="fecha_consentimiento" id="fechaConsentimientoHidden" value="">
            </form>
        </div>
    </div>
</div>

<!-- Overlay de Carga -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <div class="loading-text">Procesando...</div>
    </div>
</div>

<!-- Modal de Error -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="errorModalBody">
                <!-- Contenido del error -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para el checklist */
.checklist-section {
    margin-bottom: 2rem;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.section-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e0e0e0;
}

.section-title-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-icon {
    font-size: 1.5rem;
    color: #007bff;
}

.section-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
}

.section-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.apple-switch-container {
    position: relative;
}

.apple-switch {
    display: none;
}

.apple-switch-label {
    display: block;
    width: 50px;
    height: 30px;
    background: #ccc;
    border-radius: 15px;
    position: relative;
    cursor: pointer;
    transition: background 0.3s;
}

.apple-switch:checked + .apple-switch-label {
    background: #007bff;
}

.apple-switch-slider {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 26px;
    height: 26px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s;
}

.apple-switch:checked + .apple-switch-label .apple-switch-slider {
    transform: translateX(20px);
}

.camera-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.3s;
}

.camera-button:hover {
    background: #218838;
    color: white;
    transform: scale(1.1);
}

.items-container {
    padding: 1rem 1.5rem;
}

.item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.item-row:last-child {
    border-bottom: none;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 500;
    color: #333;
}

.item-article {
    color: #666;
    font-size: 0.9rem;
    margin-left: 0.5rem;
}

.item-controls {
    margin-left: 1rem;
}

.item-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

/* Estilos para amenities */
.amenities-section {
    margin-top: 2rem;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.amenities-header {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e0e0e0;
}

.amenities-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.amenities-icon {
    font-size: 1.5rem;
    color: #2196f3;
}

/* Estilos del toggle eliminados - amenities ahora son solo informativos */

.amenities-content {
    padding: 1.5rem;
}

.amenities-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.summary-item h4 {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
    margin: 0;
}

.summary-item p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.amenities-category {
    margin-bottom: 2rem;
}

.category-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #007bff;
}

.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.amenity-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    background: #fff;
    transition: all 0.3s;
}

.amenity-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.amenity-item.automatico-ninos {
    border-color: #ffc107;
    background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
}

.amenity-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.amenity-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.amenity-stock {
    text-align: right;
}

.stock-label {
    font-size: 0.8rem;
    color: #666;
}

.stock-value {
    font-weight: bold;
    font-size: 1.1rem;
}

/* Estilos de amenities simplificados */
.amenity-item-simple {
    transition: background-color 0.2s;
}

.amenity-item-simple:hover {
    background-color: #f8f9fa;
}

/* Estilos para el progreso */
.progress-badge {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-radius: 25px;
    padding: 1rem 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.progress-icon {
    font-size: 1.5rem;
}

.progress-text {
    text-align: center;
}

.progress-count {
    font-size: 1.5rem;
    font-weight: bold;
}

.progress-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Estilos para el overlay de carga */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    font-size: 1.1rem;
    color: #333;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .amenities-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .section-controls {
        width: 100%;
        justify-content: space-between;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar contadores
    actualizarContadores();
    
    // Inicializar switches de categorías
    inicializarSwitchesCategorias();
    
    // Inicializar amenities
    inicializarAmenities();
    
    // Inicializar consentimiento
    inicializarConsentimiento();
    
    // Inicializar checkboxes de items
    inicializarCheckboxesItems();
});

function actualizarContadores() {
    const totalItems = document.querySelectorAll('.item-checkbox').length;
    const itemsCompletados = document.querySelectorAll('.item-checkbox:checked').length;
    
    document.getElementById('checklistTotal').textContent = totalItems;
    document.getElementById('checklistCount').textContent = itemsCompletados;
    
    // Actualizar barra de progreso visual
    const progressBadge = document.getElementById('checklistProgress');
    if (totalItems > 0) {
        const porcentaje = (itemsCompletados / totalItems) * 100;
        if (porcentaje === 100) {
            progressBadge.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
        } else if (porcentaje >= 50) {
            progressBadge.style.background = 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)';
        }
    }
}

function inicializarSwitchesCategorias() {
    const switches = document.querySelectorAll('.category-switch');
    switches.forEach(switchElement => {
        switchElement.addEventListener('change', function() {
            const habitacionId = this.dataset.habitacion;
            const itemsContainer = document.getElementById(`items_${habitacionId}`);
            const cameraButton = document.getElementById(`camara${habitacionId}`);
            
            if (this.checked) {
                itemsContainer.style.display = 'block';
                if (cameraButton) {
                    cameraButton.style.display = 'inline-flex';
                }
            } else {
                itemsContainer.style.display = 'none';
                if (cameraButton) {
                    cameraButton.style.display = 'none';
                }
                // Desmarcar todos los items de esta categoría
                const items = itemsContainer.querySelectorAll('.item-checkbox');
                items.forEach(item => {
                    item.checked = false;
                });
            }
            
            actualizarContadores();
            // Guardar automáticamente el cambio de categoría
            guardarCheckboxCategoria(this);
        });
        
        // Mostrar items si la categoría ya está marcada
        if (switchElement.checked) {
            const habitacionId = switchElement.dataset.habitacion;
            const itemsContainer = document.getElementById(`items_${habitacionId}`);
            const cameraButton = document.getElementById(`camara${habitacionId}`);
            
            if (itemsContainer) {
                itemsContainer.style.display = 'block';
            }
            if (cameraButton) {
                cameraButton.style.display = 'inline-flex';
            }
        }
    });
}

function inicializarAmenities() {
    const amenitiesContent = document.getElementById('amenitiesContent');
    
    if (amenitiesContent) {
        // Mostrar siempre el contenido de amenities (solo informativo)
        amenitiesContent.style.display = 'block';
        console.log('✅ Amenities mostrados como información');
    } else {
        console.log('X Contenido de amenities no encontrado');
    }
}

// Función eliminada - amenities ahora son solo informativos

function inicializarConsentimiento() {
    const consentimientoCheckbox = document.getElementById('consentimientoFinalizar');
    const motivoGroup = document.getElementById('motivoConsentimientoGroup');
    const motivoTextarea = document.getElementById('motivoConsentimiento');
    const motivoHidden = document.getElementById('motivoConsentimientoHidden');
    const consentimientoHidden = document.getElementById('consentimientoFinalizarHidden');
    
    if (consentimientoCheckbox) {
        consentimientoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                motivoGroup.style.display = 'block';
                motivoTextarea.required = true;
            } else {
                motivoGroup.style.display = 'none';
                motivoTextarea.required = false;
                motivoTextarea.value = '';
                motivoHidden.value = '';
                consentimientoHidden.value = 'false';
            }
        });
    }
    
    if (motivoTextarea) {
        motivoTextarea.addEventListener('input', function() {
            motivoHidden.value = this.value;
        });
    }
}

function inicializarCheckboxesItems() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    console.log('🔧 Inicializando checkboxes de items:', checkboxes.length, 'encontrados');
    
    checkboxes.forEach((checkbox, index) => {
        console.log(`🔧 Checkbox ${index + 1}:`, {
            id: checkbox.dataset.itemId,
            checklistId: checkbox.dataset.checklistId,
            checked: checkbox.checked
        });
        
        checkbox.addEventListener('change', function() {
            console.log('🔄 Checkbox cambiado:', {
                id: this.dataset.itemId,
                checked: this.checked
            });
            actualizarContadores();
            // Guardar automáticamente el cambio
            guardarCheckboxIndividual(this);
        });
    });
}

function guardarCheckboxIndividual(checkbox) {
    const itemId = checkbox.dataset.itemId;
    const checklistId = checkbox.dataset.checklistId;
    const isChecked = checkbox.checked;
    
    console.log('🔧 Guardando checkbox individual:', {
        itemId: itemId,
        checklistId: checklistId,
        isChecked: isChecked,
        limpiezaId: {{ $apartamentoLimpieza->id ?? 'null' }}
    });
    
    // Mostrar indicador de guardado
    const originalText = checkbox.parentElement.innerHTML;
    checkbox.parentElement.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
    
    fetch('{{ route("gestion.updateCheckbox") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            type: 'item',
            id: itemId,
            checked: isChecked ? 1 : 0,
            limpieza_id: {{ $apartamentoLimpieza->id ?? 'null' }},
            tarea_id: {{ $tarea->id ?? 'null' }}
        })
    })
    .then(response => {
        console.log('📡 Respuesta del servidor:', response.status, response.statusText);
        return response.json();
    })
    .then(data => {
        console.log('📦 Datos recibidos:', data);
        if (data.success) {
            // Restaurar el checkbox
            checkbox.parentElement.innerHTML = originalText;
            // Mostrar notificación sutil
            mostrarNotificacionSutil('Estado actualizado', 'success');
            console.log('✅ Checkbox guardado correctamente');
        } else {
            // Revertir el checkbox si hay error
            checkbox.checked = !isChecked;
            checkbox.parentElement.innerHTML = originalText;
            mostrarAlerta('Error al actualizar el estado', 'error');
            console.error('❌ Error del servidor:', data.message);
        }
    })
    .catch(error => {
        console.error('❌ Error de red:', error);
        // Revertir el checkbox si hay error
        checkbox.checked = !isChecked;
        checkbox.parentElement.innerHTML = originalText;
        mostrarAlerta('Error al actualizar el estado', 'error');
    });
}

function guardarCheckboxCategoria(checkbox) {
    const checklistId = checkbox.dataset.habitacion;
    const isChecked = checkbox.checked;
    
    console.log('🔧 Guardando checkbox de categoría:', {
        checklistId: checklistId,
        isChecked: isChecked,
        limpiezaId: {{ $apartamentoLimpieza->id ?? 'null' }}
    });
    
    // Mostrar indicador de guardado
    const originalText = checkbox.parentElement.innerHTML;
    checkbox.parentElement.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
    
    fetch('{{ route("gestion.updateCheckbox") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            type: 'checklist',
            id: checklistId,
            checked: isChecked ? 1 : 0,
            limpieza_id: {{ $apartamentoLimpieza->id ?? 'null' }},
            tarea_id: {{ $tarea->id ?? 'null' }}
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Restaurar el checkbox
            checkbox.parentElement.innerHTML = originalText;
            // Mostrar notificación sutil
            mostrarNotificacionSutil('Categoría actualizada', 'success');
        } else {
            // Revertir el checkbox si hay error
            checkbox.checked = !isChecked;
            checkbox.parentElement.innerHTML = originalText;
            mostrarAlerta('Error al actualizar la categoría', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revertir el checkbox si hay error
        checkbox.checked = !isChecked;
        checkbox.parentElement.innerHTML = originalText;
        mostrarAlerta('Error al actualizar la categoría', 'error');
    });
}

function mostrarNotificacionSutil(mensaje, tipo = 'info') {
    // Crear notificación sutil que no interfiera con el trabajo
    const notification = document.createElement('div');
    notification.className = `alert alert-${tipo === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 200px;';
    notification.innerHTML = `
        <small>${mensaje}</small>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 2 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 2000);
}

function guardarProgreso() {
    mostrarOverlay();
    
    // Preparar datos del formulario
    const formData = new FormData(document.getElementById('formPrincipalLimpieza'));
    formData.append('accion', 'guardar');
    
    fetch('{{ route("gestion.updateTarea", $tarea) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        ocultarOverlay();
        if (data.success) {
            mostrarAlerta('Progreso guardado correctamente', 'success');
        } else {
            mostrarAlerta(data.message || 'Error al guardar el progreso', 'error');
        }
    })
    .catch(error => {
        ocultarOverlay();
        console.error('Error:', error);
        mostrarAlerta('Error al guardar el progreso', 'error');
    });
}

function finalizarTarea() {
    const totalItems = document.querySelectorAll('.item-checkbox').length;
    const itemsCompletados = document.querySelectorAll('.item-checkbox:checked').length;
    const porcentajeCompletado = totalItems > 0 ? (itemsCompletados / totalItems) * 100 : 100;
    
    // Si faltan checks, mostrar popup SweetAlert
    if (porcentajeCompletado < 100) {
        Swal.fire({
            title: '¡No has revisado todos los tics del apartamento!',
            text: 'Faltan ' + (totalItems - itemsCompletados) + ' elementos por revisar',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0891b2',
            cancelButtonColor: '#dc2626',
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Arreglar',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                _mostrarFotosYFinalizar();
            }
        });
        return;
    }
    _mostrarFotosYFinalizar();
}

function mostrarOverlay() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function ocultarOverlay() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function mostrarAlerta(mensaje, tipo = 'info') {
    // Usar SweetAlert si está disponible, sino usar alert nativo
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: tipo === 'success' ? 'Éxito' : tipo === 'error' ? 'Error' : tipo === 'warning' ? 'Advertencia' : 'Información',
            text: mensaje,
            icon: tipo,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: tipo === 'success' ? '#28a745' : tipo === 'error' ? '#dc3545' : '#007bff',
            timer: tipo === 'success' ? 3000 : null,
            timerProgressBar: tipo === 'success' ? true : false,
            toast: tipo === 'success' ? true : false,
            position: tipo === 'success' ? 'top-end' : 'center'
        });
    } else {
        alert(mensaje);
    }
}
</script>
@endsection
