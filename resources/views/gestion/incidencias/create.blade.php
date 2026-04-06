@extends('layouts.appPersonal')

@section('title')
    Reportar Nueva Incidencia
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
                            <h1 class="apartment-title">Reportar Nueva Incidencia</h1>
                            <p class="apartment-subtitle">Describe el problema encontrado</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de Incidencia -->
    <div class="apple-card">
        <div class="apple-card-body">
            <form action="{{ route('gestion.incidencias.store') }}" method="POST" enctype="multipart/form-data" id="incidentForm">
                @csrf
                
                <!-- Información Básica -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-title-container">
                            <i class="fas fa-info-circle text-primary"></i>
                            <h3>Información Básica</h3>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="titulo" class="form-label">Título de la Incidencia *</label>
                        <input type="text" 
                               class="form-control @error('titulo') is-invalid @enderror" 
                               id="titulo" 
                               name="titulo" 
                               value="{{ old('titulo') }}" 
                               placeholder="Ej: Fuga de agua en baño principal"
                               required>
                        @error('titulo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción Detallada *</label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="4" 
                                  placeholder="Describe detalladamente el problema encontrado..."
                                  required>{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="prioridad" class="form-label">Prioridad *</label>
                        <select class="form-select @error('prioridad') is-invalid @enderror" 
                                id="prioridad" 
                                name="prioridad" 
                                required>
                            <option value="">Selecciona la prioridad</option>
                            <option value="baja" {{ old('prioridad') == 'baja' ? 'selected' : '' }}>Baja</option>
                            <option value="media" {{ old('prioridad') == 'media' ? 'selected' : '' }}>Media</option>
                            <option value="alta" {{ old('prioridad') == 'alta' ? 'selected' : '' }}>Alta</option>
                            <option value="urgente" {{ old('prioridad') == 'urgente' ? 'selected' : '' }}>Urgente</option>
                        </select>
                        @error('prioridad')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Tipo de Elemento -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-title-container">
                            <i class="fas fa-tag text-primary"></i>
                            <h3>Tipo de Elemento</h3>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Selecciona el tipo *</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" 
                                       id="tipo_apartamento" 
                                       name="tipo" 
                                       value="apartamento" 
                                       {{ old('tipo') == 'apartamento' ? 'checked' : '' }}
                                       required>
                                <label for="tipo_apartamento">
                                    <i class="fas fa-building"></i>
                                    Apartamento
                                </label>
                            </div>
                            
                            <div class="radio-item">
                                <input type="radio" 
                                       id="tipo_zona_comun" 
                                       name="tipo" 
                                       value="zona_comun" 
                                       {{ old('tipo') == 'zona_comun' ? 'checked' : '' }}
                                       required>
                                <label for="tipo_zona_comun">
                                    <i class="fas fa-users"></i>
                                    Zona Común
                                </label>
                            </div>
                        </div>
                        @error('tipo')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Selección de Elemento Específico -->
                <div class="form-section" id="elementoSection" style="display: none;">
                    <div class="section-header">
                        <div class="section-title-container">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                            <h3>Elemento Específico</h3>
                        </div>
                    </div>
                    
                    <!-- Apartamentos -->
                    <div id="apartamentosSection" style="display: none;">
                        <div class="form-group">
                            <label for="apartamento_id" class="form-label">Selecciona el Apartamento</label>
                            <select class="form-select" id="apartamento_id" name="apartamento_id">
                                <option value="">Selecciona un apartamento</option>
                                @foreach($apartamentos as $apartamento)
                                    <option value="{{ $apartamento->id }}" {{ old('apartamento_id') == $apartamento->id ? 'selected' : '' }}>
                                        {{ $apartamento->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- Zonas Comunes -->
                    <div id="zonasComunesSection" style="display: none;">
                        <div class="form-group">
                            <label for="zona_comun_id" class="form-label">Selecciona la Zona Común</label>
                            <select class="form-select" id="zona_comun_id" name="zona_comun_id">
                                <option value="">Selecciona una zona común</option>
                                @foreach($zonasComunes as $zonaComun)
                                    <option value="{{ $zonaComun->id }}" {{ old('zona_comun_id') == $zonaComun->id ? 'selected' : '' }}>
                                        {{ $zonaComun->nombre }} ({{ ucfirst(str_replace('_', ' ', $zonaComun->tipo)) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Limpieza Asociada (Opcional) -->
                @if($limpiezasEnProceso->count() > 0)
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-title-container">
                            <i class="fas fa-broom text-primary"></i>
                            <h3>Limpieza Asociada (Opcional)</h3>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="apartamento_limpieza_id" class="form-label">¿Está relacionada con una limpieza en proceso?</label>
                        <select class="form-select" id="apartamento_limpieza_id" name="apartamento_limpieza_id">
                            <option value="">No está relacionada</option>
                            @foreach($limpiezasEnProceso as $limpieza)
                                <option value="{{ $limpieza->id }}" {{ old('apartamento_limpieza_id') == $limpieza->id ? 'selected' : '' }}>
                                    @if($limpieza->apartamento)
                                        {{ $limpieza->apartamento->nombre }} - Limpieza #{{ $limpieza->id }}
                                    @elseif($limpieza->zonaComun)
                                        {{ $limpieza->zonaComun->nombre }} - Limpieza #{{ $limpieza->id }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Esto ayudará a los administradores a entender mejor el contexto</small>
                    </div>
                </div>
                @endif

                <!-- Fotos -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-title-container">
                            <i class="fas fa-camera text-primary"></i>
                            <h3>Fotos (Opcional)</h3>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="fotos" class="form-label">Subir Fotos</label>
                        <input type="file" 
                               class="form-control" 
                               id="fotos" 
                               name="fotos[]" 
                               multiple 
                               accept="image/*">
                        <small class="form-text text-muted">Puedes subir múltiples fotos. Formatos: JPG, PNG. Máximo 2MB por foto.</small>
                    </div>
                    
                    <div id="fotoPreview" style="display: none;">
                        <label class="form-label">Vista Previa:</label>
                        <div id="fotoGrid" class="foto-grid"></div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="form-actions">
                    <a href="{{ route('gestion.incidencias.index') }}" class="apple-btn apple-btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        <span>Cancelar</span>
                    </a>
                    <button type="submit" class="apple-btn apple-btn-primary">
                        <i class="fas fa-save"></i>
                        <span>Crear Incidencia</span>
                    </button>
                </div>
            </form>
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

/* Secciones del formulario */
.form-section {
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-header {
    margin-bottom: var(--spacing-md);
}

.section-title-container {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.section-title-container i {
    font-size: 1.2em;
    color: var(--apple-blue);
}

.section-title-container h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

/* Grupos de formulario */
.form-group {
    margin-bottom: var(--spacing-md);
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-control, .form-select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #FFFFFF;
}

.form-control:focus, .form-select:focus {
    outline: none;
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.form-control::placeholder {
    color: #999;
}

/* Radio buttons personalizados */
.radio-group {
    display: flex;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.radio-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border: 2px solid #e1e5e9;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 200px;
    background: #FFFFFF;
}

.radio-item:hover {
    border-color: var(--apple-blue);
    background: #f0f8ff;
    transform: translateY(-2px);
}

.radio-item input[type="radio"] {
    margin: 0;
}

.radio-item label {
    margin: 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    color: #333;
}

.radio-item i {
    color: var(--apple-blue);
    font-size: 1.1em;
}

/* Preview de fotos */
.foto-preview {
    margin-top: 20px;
}

.foto-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.foto-item {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.foto-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.foto-remove {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(255,0,0,0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.foto-remove:hover {
    background: rgba(255,0,0,1);
    transform: scale(1.1);
}

/* Botones de acción */
.form-actions {
    display: flex;
    gap: var(--spacing-md);
    justify-content: flex-end;
    margin-top: var(--spacing-lg);
    padding-top: var(--spacing-md);
    border-top: 1px solid rgba(0, 0, 0, 0.08);
}

.apple-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.apple-btn-primary {
    background: var(--apple-blue);
    color: white;
}

.apple-btn-primary:hover {
    background: var(--apple-blue-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 122, 255, 0.3);
}

.apple-btn-secondary {
    background: #f8f9fa;
    color: #6c757d;
    border: 1px solid #dee2e6;
}

.apple-btn-secondary:hover {
    background: #e9ecef;
    color: #495057;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .radio-group {
        flex-direction: column;
    }
    
    .radio-item {
        min-width: auto;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .apple-btn {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoRadios = document.querySelectorAll('input[name="tipo"]');
    const elementoSection = document.getElementById('elementoSection');
    const apartamentosSection = document.getElementById('apartamentosSection');
    const zonasComunesSection = document.getElementById('zonasComunesSection');
    const apartamentoSelect = document.getElementById('apartamento_id');
    const zonaComunSelect = document.getElementById('zona_comun_id');
    
    // Manejar cambio de tipo
    tipoRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'apartamento') {
                elementoSection.style.display = 'block';
                apartamentosSection.style.display = 'block';
                zonasComunesSection.style.display = 'none';
                zonaComunSelect.value = '';
            } else if (this.value === 'zona_comun') {
                elementoSection.style.display = 'block';
                apartamentosSection.style.display = 'none';
                zonasComunesSection.style.display = 'block';
                apartamentoSelect.value = '';
            } else {
                elementoSection.style.display = 'none';
            }
        });
    });
    
    // Preview de fotos
    const fotoInput = document.getElementById('fotos');
    const fotoPreview = document.getElementById('fotoPreview');
    const fotoGrid = document.getElementById('fotoGrid');
    
    fotoInput.addEventListener('change', function() {
        fotoGrid.innerHTML = '';
        
        if (this.files.length > 0) {
            fotoPreview.style.display = 'block';
            
            Array.from(this.files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const fotoItem = document.createElement('div');
                        fotoItem.className = 'foto-item';
                        fotoItem.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="foto-remove" onclick="removeFoto(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        fotoGrid.appendChild(fotoItem);
                    };
                    reader.readAsDataURL(file);
                }
            });
        } else {
            fotoPreview.style.display = 'none';
        }
    });
    
    // Función para remover foto
    window.removeFoto = function(index) {
        const dt = new DataTransfer();
        const input = document.getElementById('fotos');
        const { files } = input;
        
        for (let i = 0; i < files.length; i++) {
            if (index !== i) {
                dt.items.add(files[i]);
            }
        }
        
        input.files = dt.files;
        fotoInput.dispatchEvent(new Event('change'));
    };
    
    // Validación del formulario
    document.getElementById('incidentForm').addEventListener('submit', function(e) {
        const tipo = document.querySelector('input[name="tipo"]:checked');
        const apartamentoId = document.getElementById('apartamento_id').value;
        const zonaComunId = document.getElementById('zona_comun_id').value;
        
        if (tipo && tipo.value === 'apartamento' && !apartamentoId) {
            e.preventDefault();
            alert('Por favor selecciona un apartamento');
            return false;
        }
        
        if (tipo && tipo.value === 'zona_comun' && !zonaComunId) {
            e.preventDefault();
            alert('Por favor selecciona una zona común');
            return false;
        }
        
        // Mostrar overlay de carga al enviar el formulario
        showLoadingOverlay('Creando incidencia...');
    });
});

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
</script>

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

@endsection
