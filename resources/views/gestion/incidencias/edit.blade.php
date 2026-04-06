@extends('layouts.appPersonal')

@section('title')
    Editar Incidencia
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
                            <i class="fa-solid fa-edit"></i>
                        </div>
                        <div class="apartment-details">
                            <h1 class="apartment-title">Editar Incidencia</h1>
                            <p class="apartment-subtitle">{{ $incidencia->titulo }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estado Actual -->
        <div class="incident-status-section">
            <div class="status-badge status-{{ $incidencia->estado }}">
                <i class="fas fa-{{ $incidencia->estado === 'pendiente' ? 'clock' : ($incidencia->estado === 'en_proceso' ? 'tools' : ($incidencia->estado === 'resuelta' ? 'check-circle' : 'times-circle')) }}"></i>
                <span>{{ ucfirst(str_replace('_', ' ', $incidencia->estado)) }}</span>
            </div>
            
            <div class="priority-badge priority-{{ $incidencia->prioridad }}">
                <i class="fas fa-{{ $incidencia->prioridad === 'urgente' ? 'exclamation' : ($incidencia->prioridad === 'alta' ? 'exclamation-triangle' : ($incidencia->prioridad === 'media' ? 'minus-circle' : 'check-circle')) }}"></i>
                <span>{{ ucfirst($incidencia->prioridad) }}</span>
            </div>
        </div>
    </div>

    <!-- Formulario de Edición -->
    <div class="apple-card">
        <div class="apple-card-body">
            <form action="{{ route('gestion.incidencias.update', $incidencia) }}" method="POST" enctype="multipart/form-data" id="editIncidentForm">
                @csrf
                @method('PUT')
                
                <!-- Información Básica -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Información Básica
                    </h3>
                    
                    <div class="form-group">
                        <label for="titulo" class="form-label">Título de la Incidencia *</label>
                        <input type="text" 
                               class="form-control @error('titulo') is-invalid @enderror" 
                               id="titulo" 
                               name="titulo" 
                               value="{{ old('titulo', $incidencia->titulo) }}" 
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
                                  required>{{ old('descripcion', $incidencia->descripcion) }}</textarea>
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
                            <option value="baja" {{ old('prioridad', $incidencia->prioridad) == 'baja' ? 'selected' : '' }}>Baja</option>
                            <option value="media" {{ old('prioridad', $incidencia->prioridad) == 'media' ? 'selected' : '' }}>Media</option>
                            <option value="alta" {{ old('prioridad', $incidencia->prioridad) == 'alta' ? 'selected' : '' }}>Alta</option>
                            <option value="urgente" {{ old('prioridad', $incidencia->prioridad) == 'urgente' ? 'selected' : '' }}>Urgente</option>
                        </select>
                        @error('prioridad')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Tipo de Elemento -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-tag"></i>
                        Tipo de Elemento
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">Selecciona el tipo *</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" 
                                       id="tipo_apartamento" 
                                       name="tipo" 
                                       value="apartamento" 
                                       {{ old('tipo', $incidencia->tipo) == 'apartamento' ? 'checked' : '' }}
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
                                       {{ old('tipo', $incidencia->tipo) == 'zona_comun' ? 'checked' : '' }}
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
                <div class="form-section" id="elementoSection" style="display: {{ $incidencia->tipo ? 'block' : 'none' }};">
                    <h3 class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Elemento Específico
                    </h3>
                    
                    <!-- Apartamentos -->
                    <div id="apartamentosSection" style="display: {{ $incidencia->tipo === 'apartamento' ? 'block' : 'none' }};">
                        <div class="form-group">
                            <label for="apartamento_id" class="form-label">Selecciona el Apartamento</label>
                            <select class="form-select" id="apartamento_id" name="apartamento_id">
                                <option value="">Selecciona un apartamento</option>
                                @foreach($apartamentos as $apartamento)
                                    <option value="{{ $apartamento->id }}" {{ old('apartamento_id', $incidencia->apartamento_id) == $apartamento->id ? 'selected' : '' }}>
                                        {{ $apartamento->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- Zonas Comunes -->
                    <div id="zonasComunesSection" style="display: {{ $incidencia->tipo === 'zona_comun' ? 'block' : 'none' }};">
                        <div class="form-group">
                            <label for="zona_comun_id" class="form-label">Selecciona la Zona Común</label>
                            <select class="form-select" id="zona_comun_id" name="zona_comun_id">
                                <option value="">Selecciona una zona común</option>
                                @foreach($zonasComunes as $zonaComun)
                                    <option value="{{ $zonaComun->id }}" {{ old('zona_comun_id', $incidencia->zona_comun_id) == $zonaComun->id ? 'selected' : '' }}>
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
                    <h3 class="section-title">
                        <i class="fas fa-broom"></i>
                        Limpieza Asociada (Opcional)
                    </h3>
                    
                    <div class="form-group">
                        <label for="apartamento_limpieza_id" class="form-label">¿Está relacionada con una limpieza en proceso?</label>
                        <select class="form-select" id="apartamento_limpieza_id" name="apartamento_limpieza_id">
                            <option value="">No está relacionada</option>
                            @foreach($limpiezasEnProceso as $limpieza)
                                <option value="{{ $limpieza->id }}" {{ old('apartamento_limpieza_id', $incidencia->apartamento_limpieza_id) == $limpieza->id ? 'selected' : '' }}>
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

                <!-- Fotos Existentes -->
                @if($incidencia->fotos && count($incidencia->fotos) > 0)
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-camera"></i>
                        Fotos Existentes
                    </h3>
                    
                    <div class="existing-photos">
                        <p class="text-muted mb-3">Fotos actuales de la incidencia:</p>
                        <div class="photos-grid">
                            @foreach($incidencia->fotos as $index => $foto)
                            <div class="photo-item existing-photo">
                                <img src="{{ Storage::url($foto) }}" alt="Foto existente">
                                <div class="photo-overlay">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeExistingPhoto({{ $index }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="existing_photos[]" value="{{ $foto }}">
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Nuevas Fotos -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-plus-circle"></i>
                        Añadir Nuevas Fotos (Opcional)
                    </h3>
                    
                    <div class="form-group">
                        <label for="fotos" class="form-label">Subir Fotos Adicionales</label>
                        <input type="file" 
                               class="form-control @error('fotos.*') is-invalid @enderror" 
                               id="fotos" 
                               name="fotos[]" 
                               multiple 
                               accept="image/*">
                        <small class="form-text text-muted">
                            Puedes subir hasta 5 fotos adicionales. Formatos: JPG, PNG. Máximo 2MB por foto.
                        </small>
                        @error('fotos.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Preview de nuevas fotos -->
                    <div id="fotoPreview" class="foto-preview" style="display: none;">
                        <h5>Vista Previa de Nuevas Fotos:</h5>
                        <div id="fotoGrid" class="foto-grid"></div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="form-actions">
                    <a href="{{ route('gestion.incidencias.show', $incidencia) }}" class="apple-btn apple-btn-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                    
                    <button type="submit" class="apple-btn apple-btn-primary">
                        <i class="fas fa-save"></i>
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 15px;
    border-left: 4px solid #007AFF;
}

.section-title {
    color: #333;
    font-size: 1.2em;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: #007AFF;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    display: block;
}

.form-control, .form-select {
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 1em;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #007AFF;
    box-shadow: 0 0 0 0.2rem rgba(0, 122, 255, 0.25);
    outline: none;
}

.radio-group {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.radio-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 200px;
}

.radio-item:hover {
    border-color: #007AFF;
    background: #f0f8ff;
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
}

.radio-item i {
    color: #007AFF;
}

.existing-photos {
    margin-bottom: 20px;
}

.existing-photo {
    position: relative;
}

.photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.existing-photo:hover .photo-overlay {
    opacity: 1;
}

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
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.2s ease;
}

.foto-item:hover {
    transform: scale(1.05);
}

.foto-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.foto-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255,0,0,0.8);
    color: white;
    border: none;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.incident-status-section {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin: 20px 0;
    flex-wrap: wrap;
}

.status-badge, .priority-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 25px;
    color: white;
    font-weight: 600;
    font-size: 0.9em;
}

.status-pendiente { background: #FF9800; }
.status-en_proceso { background: #2196F3; }
.status-resuelta { background: #4CAF50; }
.status-cerrada { background: #9E9E9E; }

.priority-baja { background: #4CAF50; }
.priority-media { background: #FF9800; }
.priority-alta { background: #F44336; }
.priority-urgente { background: #9C27B0; }

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
    
    .incident-status-section {
        flex-direction: column;
        align-items: center;
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
    
    // Preview de nuevas fotos
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
    
    // Función para remover nueva foto
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
    
    // Función para remover foto existente
    window.removeExistingPhoto = function(index) {
        if (confirm('¿Estás seguro de que quieres eliminar esta foto?')) {
            const photoItem = document.querySelector(`.existing-photo:nth-child(${index + 1})`);
            if (photoItem) {
                photoItem.remove();
            }
        }
    };
    
    // Validación del formulario
    document.getElementById('editIncidentForm').addEventListener('submit', function(e) {
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
        showLoadingOverlay('Actualizando incidencia...');
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
