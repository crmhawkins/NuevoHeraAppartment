@extends('layouts.appAdmin')

@section('title', 'Generar Lugares Cercanos - OpenStreetMap')

@section('styles')
<style>
    .categoria-card {
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid #E9ECEF;
        overflow: hidden;
    }
    
    .categoria-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        border-color: #007AFF;
    }
    
    .categoria-card.selected {
        border-color: #007AFF;
        background: linear-gradient(135deg, #F0F7FF 0%, #E3F2FD 100%);
        box-shadow: 0 4px 16px rgba(0, 122, 255, 0.2);
    }
    
    .categoria-checkbox {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .categoria-checkbox:checked + .categoria-card-label .categoria-card {
        border-color: #007AFF;
        background: linear-gradient(135deg, #F0F7FF 0%, #E3F2FD 100%);
        box-shadow: 0 4px 16px rgba(0, 122, 255, 0.2);
    }
    
    .categoria-checkbox:checked + .categoria-card-label .check-icon {
        display: flex;
    }
    
    .categoria-card-label {
        cursor: pointer;
        display: block;
    }
    
    .check-icon {
        display: none;
        position: absolute;
        top: 12px;
        right: 12px;
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
        animation: checkPop 0.3s ease;
    }
    
    @keyframes checkPop {
        0% {
            transform: scale(0);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }
    
    .categoria-icon {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        margin-bottom: 16px;
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
    }
    
    .categoria-card.selected .categoria-icon {
        background: linear-gradient(135deg, #30D158 0%, #28A745 100%);
        box-shadow: 0 4px 12px rgba(48, 209, 88, 0.3);
    }
    
    .info-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: rgba(0, 122, 255, 0.1);
        border-radius: 8px;
        font-size: 13px;
        color: #007AFF;
        font-weight: 500;
    }
    
    .info-badge i {
        font-size: 12px;
    }
    
    .selection-counter {
        position: sticky;
        top: 20px;
        z-index: 100;
    }
    
    .counter-badge {
        font-size: 18px;
        font-weight: 700;
        padding: 8px 16px;
        border-radius: 12px;
        background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
        color: white;
        box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
    }
    
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    
    .status-active {
        background: #30D158;
        box-shadow: 0 0 8px rgba(48, 209, 88, 0.5);
    }
    
    .status-inactive {
        background: #FF9500;
        box-shadow: 0 0 8px rgba(255, 149, 0, 0.5);
    }
    
    .btn-generate {
        background: linear-gradient(135deg, #30D158 0%, #28A745 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 16px 32px;
        border-radius: 12px;
        font-size: 16px;
        box-shadow: 0 4px 16px rgba(48, 209, 88, 0.3);
        transition: all 0.3s ease;
    }
    
    .btn-generate:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(48, 209, 88, 0.4);
    }
    
    .btn-generate:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .location-badge {
        background: linear-gradient(135deg, #F0F7FF 0%, #E3F2FD 100%);
        border: 2px solid #007AFF;
        border-radius: 12px;
        padding: 16px;
    }
    
    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    @media (max-width: 768px) {
        .category-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    
    .loading-overlay.active {
        display: flex;
    }
    
    .loading-content {
        background: white;
        border-radius: 16px;
        padding: 40px;
        text-align: center;
        max-width: 400px;
    }
    
    .spinner {
        width: 48px;
        height: 48px;
        border: 4px solid #E9ECEF;
        border-top-color: #007AFF;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-map-marked-alt me-2" style="color: #007AFF;"></i>
                Generar Lugares Cercanos Automáticamente
            </h1>
            <p class="text-muted mb-0">
                <i class="fas fa-building me-1"></i>
                {{ $apartamento->titulo ?? $apartamento->nombre }}
                <span class="mx-2">•</span>
                <i class="fas fa-map me-1"></i>
                OpenStreetMap / Nominatim
            </p>
        </div>
        <a href="{{ route('admin.lugares-cercanos.index', $apartamento->id) }}" 
           class="btn btn-outline-secondary btn-lg">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    @if(!$tieneCoordenadas)
        <!-- Alerta sin coordenadas -->
        <div class="alert alert-danger border-0 shadow-sm" role="alert">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5 class="alert-heading fw-bold mb-2">Coordenadas requeridas</h5>
                    <p class="mb-2">El apartamento debe tener latitud y longitud configuradas para poder buscar lugares cercanos automáticamente.</p>
                    <a href="{{ route('apartamentos.admin.edit', $apartamento->id) }}" 
                       class="btn btn-danger">
                        <i class="fas fa-map-marker-alt me-2"></i>Configurar Coordenadas
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Información de ubicación -->
        <div class="location-badge mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-map-marker-alt me-2" style="color: #007AFF; font-size: 20px;"></i>
                        <strong class="text-dark">Ubicación del Apartamento</strong>
                    </div>
                    <div class="text-muted">
                        <i class="fas fa-globe me-1"></i>
                        <strong>Latitud:</strong> {{ number_format($apartamento->latitude, 6) }}
                        <span class="mx-2">•</span>
                        <strong>Longitud:</strong> {{ number_format($apartamento->longitude, 6) }}
                    </div>
                </div>
                <div class="text-end">
                    <a href="https://www.openstreetmap.org/?mlat={{ $apartamento->latitude }}&mlon={{ $apartamento->longitude }}&zoom=15" 
                       target="_blank" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1"></i>Ver en Mapa
                    </a>
                </div>
            </div>
        </div>

        <!-- Contador de selección -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="selection-counter">
                <span class="counter-badge" id="selectionCounter">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="selectedCount">0</span> / {{ $categorias->count() }} seleccionadas
                </span>
            </div>
            <div>
                <button type="button" 
                        class="btn btn-outline-primary btn-sm" 
                        onclick="toggleAll(true)">
                    <i class="fas fa-check-double me-2"></i>Seleccionar Todas
                </button>
                <button type="button" 
                        class="btn btn-outline-secondary btn-sm" 
                        onclick="toggleAll(false)">
                    <i class="fas fa-times me-2"></i>Deseleccionar Todas
                </button>
            </div>
        </div>

        <!-- Formulario -->
        <form action="{{ route('admin.lugares-cercanos.generar-automaticamente', $apartamento->id) }}" 
              method="POST" 
              id="formGenerar">
            @csrf
            
            <!-- Opción para borrar existentes -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="borrarExistentes" 
                               name="borrar_existentes" 
                               value="1">
                        <label class="form-check-label fw-semibold" for="borrarExistentes">
                            <i class="fas fa-trash-alt me-2 text-danger"></i>
                            Borrar lugares existentes antes de generar nuevos
                        </label>
                        <small class="d-block text-muted mt-1">
                            Si está marcado, se eliminarán todos los lugares cercanos actuales antes de buscar nuevos.
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-layer-group me-2" style="color: #007AFF;"></i>
                            Seleccionar Categorías de Búsqueda
                        </h5>
                        <span class="badge bg-primary" id="autoSearchBadge">
                            {{ $categorias->where('busqueda_automatica', true)->count() }} incluidas en búsqueda automática
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    @if($categorias->isEmpty())
                        <div class="alert alert-warning border-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay categorías configuradas. Debes crear categorías primero.
                        </div>
                    @else
                        <div class="category-grid">
                            @foreach($categorias as $categoria)
                                @php
                                    $iconos = [
                                        'restaurantes' => 'fa-utensils',
                                        'cafeterías' => 'fa-coffee',
                                        'bares' => 'fa-wine-glass-alt',
                                        'supermercados' => 'fa-shopping-cart',
                                        'farmacias' => 'fa-pills',
                                        'hospitales' => 'fa-hospital',
                                        'transporte' => 'fa-bus',
                                        'playas' => 'fa-umbrella-beach',
                                        'aeropuertos' => 'fa-plane',
                                        'museos' => 'fa-landmark',
                                        'iglesias' => 'fa-church',
                                        'parques' => 'fa-tree',
                                        'gimnasios' => 'fa-dumbbell',
                                    ];
                                    $icono = $iconos[strtolower($categoria->nombre)] ?? 'fa-map-marker-alt';
                                @endphp
                                
                                <label class="categoria-card-label">
                                    <input type="checkbox" 
                                           class="categoria-checkbox" 
                                           name="categorias[]" 
                                           value="{{ $categoria->id }}"
                                           id="cat_{{ $categoria->id }}"
                                           data-auto-search="{{ $categoria->busqueda_automatica ? 'true' : 'false' }}"
                                           onchange="updateCounter()">
                                    <div class="categoria-card card h-100">
                                        <div class="card-body position-relative">
                                            <div class="check-icon">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            
                                            <div class="text-center mb-3">
                                                <div class="categoria-icon mx-auto">
                                                    <i class="fas {{ $icono }}"></i>
                                                </div>
                                                <h6 class="mb-2 fw-bold text-dark">{{ $categoria->nombre }}</h6>
                                            </div>
                                            
                                            <div class="d-flex flex-column gap-2">
                                                <div class="info-badge justify-content-center">
                                                    <i class="fas fa-ruler"></i>
                                                    <span>{{ number_format($categoria->radio_metros / 1000, 1) }} km de radio</span>
                                                </div>
                                                
                                                <div class="info-badge justify-content-center">
                                                    <i class="fas fa-list-ol"></i>
                                                    <span>Hasta {{ $categoria->limite_resultados }} resultados</span>
                                                </div>
                                                
                                                @if($categoria->amenity_osm)
                                                    <div class="info-badge justify-content-center">
                                                        <i class="fas fa-tag"></i>
                                                        <span>Amenity: {{ $categoria->amenity_osm }}</span>
                                                    </div>
                                                @endif
                                                
                                                @if($categoria->shop_osm)
                                                    <div class="info-badge justify-content-center">
                                                        <i class="fas fa-store"></i>
                                                        <span>Shop: {{ $categoria->shop_osm }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <div class="mt-3 pt-3 border-top text-center">
                                                <span class="status-indicator {{ $categoria->busqueda_automatica ? 'status-active' : 'status-inactive' }}"></span>
                                                <small class="text-muted">
                                                    {{ $categoria->busqueda_automatica ? 'Incluida en búsqueda automática' : 'No incluida en búsqueda automática' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-semibold text-dark">
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                ¿Cómo funciona?
                            </h6>
                            <p class="text-muted small mb-0">
                                El sistema utiliza <strong>OpenStreetMap (Nominatim API)</strong> para buscar lugares cercanos. 
                                Los resultados se filtrarán automáticamente para evitar duplicados y se calculará la distancia desde el apartamento.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.lugares-cercanos.index', $apartamento->id) }}" 
                               class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" 
                                    class="btn btn-generate btn-lg" 
                                    id="btnGenerar" 
                                    {{ $categorias->isEmpty() ? 'disabled' : '' }}>
                                <i class="fas fa-search me-2"></i>Buscar y Generar Lugares
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <h5 class="fw-bold mb-2">Buscando lugares...</h5>
        <p class="text-muted mb-0">Esto puede tardar unos momentos</p>
        <small class="text-muted d-block mt-2" id="searchStatus">Conectando con OpenStreetMap...</small>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Actualizar contador
    function updateCounter() {
        const checked = document.querySelectorAll('.categoria-checkbox:checked');
        const total = document.querySelectorAll('.categoria-checkbox').length;
        const count = checked.length;
        
        document.getElementById('selectedCount').textContent = count;
        
        // Actualizar badge del contador
        const counterBadge = document.getElementById('selectionCounter');
        if (count > 0) {
            counterBadge.classList.add('counter-badge');
        }
    }
    
    // Seleccionar/deseleccionar todas
    function toggleAll(select) {
        const checkboxes = document.querySelectorAll('.categoria-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = select;
        });
        updateCounter();
        
        // Animar cards
        const cards = document.querySelectorAll('.categoria-card');
        cards.forEach(card => {
            if (select) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
    }
    
    // Actualizar contador al cargar
    document.addEventListener('DOMContentLoaded', function() {
        updateCounter();
        
        // Añadir efectos visuales a los checkboxes
        const checkboxes = document.querySelectorAll('.categoria-checkbox');
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const card = this.closest('.categoria-card-label').querySelector('.categoria-card');
                if (this.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        });
    });
    
    // Validar y enviar formulario
    document.getElementById('formGenerar')?.addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.categoria-checkbox:checked');
        
        if (checked.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Sin categorías seleccionadas',
                text: 'Por favor selecciona al menos una categoría para buscar lugares cercanos.',
                confirmButtonColor: '#007AFF'
            });
            return false;
        }
        
        // Mostrar loading overlay
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.add('active');
        
        // Actualizar estado de búsqueda
        const statusEl = document.getElementById('searchStatus');
        const categorias = Array.from(checked).map(cb => {
            const label = cb.closest('.categoria-card-label').querySelector('h6').textContent;
            return label.trim();
        });
        
        statusEl.textContent = `Buscando: ${categorias.slice(0, 3).join(', ')}${categorias.length > 3 ? '...' : ''}`;
        
        // Deshabilitar botón
        const btnGenerar = document.getElementById('btnGenerar');
        btnGenerar.disabled = true;
        btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Buscando...';
        
        // El formulario se enviará normalmente
    });
</script>
@endsection
