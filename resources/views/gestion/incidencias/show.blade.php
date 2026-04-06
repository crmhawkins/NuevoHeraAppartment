@extends('layouts.appPersonal')

@section('title')
    Detalles de Incidencia
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
                            <h1 class="apartment-title">{{ $incidencia->titulo }}</h1>
                            <p class="apartment-subtitle">Detalles de la Incidencia #{{ $incidencia->id }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estado y Prioridad -->
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

    <!-- Información Principal -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="apartment-details">
                            <h2 class="apartment-title">Información General</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="apple-card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-tag"></i>
                        Tipo de Elemento
                    </div>
                    <div class="info-value">
                        <span class="badge bg-{{ $incidencia->tipo === 'apartamento' ? 'primary' : 'info' }}">
                            <i class="fas fa-{{ $incidencia->tipo === 'apartamento' ? 'building' : 'users' }}"></i>
                            {{ $incidencia->tipo === 'apartamento' ? 'Apartamento' : 'Zona Común' }}
                        </span>
                    </div>
                </div>
                
                @if($incidencia->apartamento)
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-building"></i>
                        Apartamento
                    </div>
                    <div class="info-value">{{ $incidencia->apartamento->nombre }}</div>
                </div>
                @endif
                
                @if($incidencia->zonaComun)
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-users"></i>
                        Zona Común
                    </div>
                    <div class="info-value">
                        {{ $incidencia->zonaComun->nombre }}
                        <small class="text-muted">({{ ucfirst(str_replace('_', ' ', $incidencia->zonaComun->tipo)) }})</small>
                    </div>
                </div>
                @endif
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-calendar"></i>
                        Fecha de Reporte
                    </div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($incidencia->created_at)->format('d/m/Y H:i') }}</div>
                </div>
                
                @if($incidencia->limpieza)
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-broom"></i>
                        Limpieza Asociada
                    </div>
                    <div class="info-value">
                        @if($incidencia->limpieza->apartamento)
                            {{ $incidencia->limpieza->apartamento->nombre }}
                        @elseif($incidencia->limpieza->zonaComun)
                            {{ $incidencia->limpieza->zonaComun->nombre }}
                        @endif
                        <small class="text-muted">(Limpieza #{{ $incidencia->limpieza->id }})</small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Descripción -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fas fa-align-left"></i>
                        </div>
                        <div class="apartment-details">
                            <h2 class="apartment-title">Descripción del Problema</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="apple-card-body">
            <div class="description-content">
                {{ $incidencia->descripcion }}
            </div>
        </div>
    </div>

    <!-- Fotos -->
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="apartment-details">
                            <h2 class="apartment-title">Fotos del Problema</h2>
                            <p class="apartment-subtitle">
                                @if($incidencia->fotos && count($incidencia->fotos) > 0)
                                    {{ count($incidencia->fotos) }} foto(s)
                                @else
                                    No hay fotos subidas
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="apple-card-body">
            @if($incidencia->fotos && count($incidencia->fotos) > 0)
            <div class="photos-grid">
                @foreach($incidencia->fotos as $foto)
                <div class="photo-item">
                    <img src="{{ Storage::url($foto) }}" 
                         alt="Foto de la incidencia" 
                         class="incident-photo"
                         onclick="openPhotoModal('{{ Storage::url($foto) }}')">
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-4">
                <i class="fas fa-images fa-3x text-muted mb-3"></i>
                <p class="text-muted">No hay fotos subidas para esta incidencia</p>
            </div>
            @endif

            <!-- Formulario para añadir fotos adicionales -->
            <div class="add-photos-section mt-4 pt-4 border-top">
                <h5 class="mb-3">
                    <i class="fas fa-plus-circle text-primary"></i>
                    Añadir Fotos Adicionales
                </h5>
                <form action="{{ route('gestion.incidencias.add-photos', $incidencia) }}" 
                      method="POST" 
                      enctype="multipart/form-data" 
                      id="addPhotosForm">
                    @csrf
                    <div class="form-group">
                        <label for="fotos_adicionales" class="form-label">Seleccionar Fotos</label>
                        <input type="file" 
                               class="form-control @error('fotos.*') is-invalid @enderror" 
                               id="fotos_adicionales" 
                               name="fotos[]" 
                               multiple 
                               accept="image/*">
                        <small class="form-text text-muted">
                            Puedes subir múltiples fotos. Formatos: JPG, PNG. Máximo 2MB por foto.
                        </small>
                        @error('fotos.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div id="fotosPreview" style="display: none;" class="mt-3">
                        <label class="form-label">Vista Previa:</label>
                        <div id="fotosGrid" class="photos-grid"></div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="apple-btn apple-btn-primary">
                            <i class="fas fa-upload"></i>
                            <span>Subir Fotos</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Solución (si está resuelta) -->
    @if($incidencia->estado === 'resuelta' && $incidencia->solucion)
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="apartment-details">
                            <h2 class="apartment-title">Solución Aplicada</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="apple-card-body">
            <div class="solution-content">
                {{ $incidencia->solucion }}
            </div>
            
            @if($incidencia->fecha_resolucion)
            <div class="solution-meta">
                <small class="text-muted">
                    <i class="fas fa-calendar-check"></i>
                    Resuelta el {{ \Carbon\Carbon::parse($incidencia->fecha_resolucion)->format('d/m/Y H:i') }}
                </small>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Observaciones del Admin (si las hay) -->
    @if($incidencia->observaciones_admin)
    <div class="apple-card">
        <div class="apple-card-header">
            <div class="header-content">
                <div class="header-main">
                    <div class="header-info">
                        <div class="apartment-icon">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="apartment-details">
                            <h2 class="apartment-title">Observaciones del Administrador</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="apple-card-body">
            <div class="admin-notes">
                {{ $incidencia->observaciones_admin }}
            </div>
        </div>
    </div>
    @endif

    <!-- Botones de Acción -->
    <div class="apple-action-section">
        <a href="{{ route('gestion.incidencias.index') }}" class="apple-btn apple-btn-secondary">
            <i class="fas fa-arrow-left"></i>
            <span>Volver a Incidencias</span>
        </a>
        
        @if($incidencia->estado === 'pendiente')
        <a href="{{ route('gestion.incidencias.edit', $incidencia) }}" class="apple-btn apple-btn-warning">
            <i class="fas fa-edit"></i>
            <span>Editar Incidencia</span>
        </a>
        
        <form action="{{ route('gestion.incidencias.destroy', $incidencia) }}" 
              method="POST" 
              class="d-inline"
              onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta incidencia?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="apple-btn apple-btn-danger">
                <i class="fas fa-trash"></i>
                <span>Eliminar</span>
            </button>
        </form>
        @endif
    </div>
</div>

<!-- Modal para ver fotos -->
<div id="photoModal" class="photo-modal" onclick="closePhotoModal()">
    <div class="modal-content">
        <span class="close-modal" onclick="closePhotoModal()">&times;</span>
        <img id="modalImage" src="" alt="Foto ampliada">
    </div>
</div>

<style>
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

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.info-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #007AFF;
}

.info-label {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-value {
    color: #333;
    font-weight: 600;
    font-size: 1.1em;
}

.description-content, .solution-content, .admin-notes {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    line-height: 1.6;
    color: #333;
}

.solution-meta {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e1e5e9;
}

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.photo-item {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.2s ease;
}

.photo-item:hover {
    transform: scale(1.05);
}

.incident-photo {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

/* Modal de fotos */
.photo-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
}

.modal-content {
    position: relative;
    margin: auto;
    padding: 20px;
    width: 90%;
    max-width: 800px;
    text-align: center;
    top: 50%;
    transform: translateY(-50%);
}

.close-modal {
    position: absolute;
    top: 10px;
    right: 25px;
    color: #f1f1f1;
    font-size: 35px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: #bbb;
}

#modalImage {
    max-width: 100%;
    max-height: 80vh;
    border-radius: 10px;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .photos-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .incident-status-section {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<script>
function openPhotoModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('photoModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePhotoModal() {
    document.getElementById('photoModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
    }
});

// Preview de fotos adicionales
document.addEventListener('DOMContentLoaded', function() {
    const fotoInput = document.getElementById('fotos_adicionales');
    const fotoPreview = document.getElementById('fotosPreview');
    const fotoGrid = document.getElementById('fotosGrid');
    
    if (fotoInput) {
        fotoInput.addEventListener('change', function() {
            fotoGrid.innerHTML = '';
            
            if (this.files.length > 0) {
                fotoPreview.style.display = 'block';
                
                Array.from(this.files).forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const fotoItem = document.createElement('div');
                            fotoItem.className = 'photo-item';
                            fotoItem.innerHTML = `
                                <img src="${e.target.result}" alt="Preview" class="incident-photo">
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
    }
});
</script>
@endsection
