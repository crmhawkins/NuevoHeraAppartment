@extends('layouts.appAdmin')

@section('content')
<style>
    .form-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }
    
    .form-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px 20px 0 0;
        padding: 20px;
        text-align: center;
    }
    
    .form-header h3 {
        margin: 0;
        font-weight: 600;
        font-size: 1.5rem;
    }
    
    .form-body {
        padding: 30px;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
    }
    
    .form-control, .form-select {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 15px;
        font-size: 1rem;
        transition: all 0.3s ease;
        width: 100%;
        height: auto;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        outline: none;
        transform: translateY(-2px);
    }
    
    .form-control::placeholder {
        color: #adb5bd;
        opacity: 1;
    }
    
    .form-control:focus::placeholder {
        opacity: 0.7;
    }
    
    .btn-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 15px 40px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        color: white;
        width: 100%;
        max-width: 300px;
    }
    
    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        color: white;
    }
    
    .btn-secondary-modern {
        background: #6c757d;
        border: none;
        border-radius: 25px;
        padding: 15px 40px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        color: white;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }
    
    .btn-secondary-modern:hover {
        background: #5a6268;
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }
    
    .checkbox-modern {
        display: flex;
        align-items: center;
        margin: 15px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 15px;
        transition: all 0.3s ease;
    }
    
    .checkbox-modern:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }
    
    .checkbox-modern input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin-right: 15px;
        accent-color: #667eea;
    }
    
    .checkbox-modern label {
        margin: 0;
        font-weight: 600;
        color: #495057;
        cursor: pointer;
    }
    
    .apartamentos-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .apartamento-checkbox {
        display: flex;
        align-items: center;
        margin: 10px 0;
        padding: 10px;
        background: white;
        border-radius: 10px;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .apartamento-checkbox:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .apartamento-checkbox input[type="checkbox"] {
        margin-right: 15px;
        accent-color: #667eea;
    }
    
    .apartamento-checkbox label {
        margin: 0;
        font-weight: 600;
        color: #495057;
        cursor: pointer;
        flex: 1;
    }
    
    .preview-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin: 20px 0;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .preview-card:hover {
        border-color: #667eea;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
    }
    
    .preview-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 15px;
        margin: -20px -20px 20px -20px;
        text-align: center;
    }
    
    .preview-price {
        font-size: 2rem;
        font-weight: 700;
        color: #28a745;
        text-align: center;
        margin: 20px 0;
    }
    
    .preview-dates {
        text-align: center;
        color: #6c757d;
        margin-bottom: 20px;
    }
    
    .preview-temporada {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .temporada-badge {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin: 5px;
        display: inline-block;
    }
    
    .temporada-alta {
        background: #ffc107;
        color: #212529;
    }
    
    .temporada-baja {
        background: #17a2b8;
        color: white;
    }
    
    .temporada-general {
        background: #6c757d;
        color: white;
    }
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-plus me-2"></i>Crear Nueva Tarifa
                </h2>
                <a href="{{ route('tarifas.index') }}" class="btn-secondary-modern">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>
            
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error:</strong> Por favor, corrige los siguientes errores:
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="form-card">
                        <div class="form-header">
                            <h3><i class="fas fa-tag me-2"></i>Información de la Tarifa</h3>
                        </div>
                        
                        <div class="form-body">
                            <form action="{{ route('tarifas.store') }}" method="POST" id="tarifaForm">
                                @csrf
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre" class="form-label">Nombre de la tarifa</label>
                                            <input type="text" 
                                                   class="form-control @error('nombre') is-invalid @enderror" 
                                                   id="nombre" 
                                                   name="nombre" 
                                                   placeholder="Nombre de la tarifa"
                                                   value="{{ old('nombre') }}" 
                                                   required>
                                            @error('nombre')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio" class="form-label">Precio por noche (€)</label>
                                            <input type="number" 
                                                   class="form-control @error('precio') is-invalid @enderror" 
                                                   id="precio" 
                                                   name="precio" 
                                                   placeholder="Precio por noche"
                                                   step="0.01" 
                                                   min="0" 
                                                   value="{{ old('precio') }}" 
                                                   required>
                                            @error('precio')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="descripcion" class="form-label">Descripción (opcional)</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              placeholder="Descripción de la tarifa"
                                              rows="3">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_inicio" class="form-label">Fecha de inicio</label>
                                            <input type="date" 
                                                   class="form-control @error('fecha_inicio') is-invalid @enderror" 
                                                   id="fecha_inicio" 
                                                   name="fecha_inicio" 
                                                   value="{{ old('fecha_inicio') }}" 
                                                   required>
                                            @error('fecha_inicio')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_fin" class="form-label">Fecha de fin</label>
                                            <input type="date" 
                                                   class="form-control @error('fecha_fin') is-invalid @enderror" 
                                                   id="fecha_fin" 
                                                   name="fecha_fin" 
                                                   value="{{ old('fecha_fin') }}" 
                                                   required>
                                            @error('fecha_fin')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="checkbox-modern">
                                    <input type="checkbox" id="temporada_alta" name="temporada_alta" value="1" {{ old('temporada_alta') ? 'checked' : '' }}>
                                    <label for="temporada_alta">Aplicar a temporada alta</label>
                                </div>
                                
                                <div class="checkbox-modern">
                                    <input type="checkbox" id="temporada_baja" name="temporada_baja" value="1" {{ old('temporada_baja') ? 'checked' : '' }}>
                                    <label for="temporada_baja">Aplicar a temporada baja</label>
                                </div>
                                
                                <div class="apartamentos-section">
                                    <h5 class="mb-3">
                                        <i class="fas fa-building me-2"></i>Apartamentos que aplican esta tarifa
                                    </h5>
                                    <p class="text-muted mb-3">Selecciona los apartamentos donde se aplicará esta tarifa. Si no seleccionas ninguno, la tarifa estará disponible para asignar manualmente.</p>
                                    
                                    @foreach($apartamentos as $apartamento)
                                        <div class="apartamento-checkbox">
                                            <input type="checkbox" 
                                                   id="apartamento_{{ $apartamento->id }}" 
                                                   name="apartamentos[]" 
                                                   value="{{ $apartamento->id }}"
                                                   {{ in_array($apartamento->id, old('apartamentos', [])) ? 'checked' : '' }}>
                                            <label for="apartamento_{{ $apartamento->id }}">
                                                {{ $apartamento->nombre }}
                                                @if($apartamento->edificioName)
                                                    <small class="text-muted d-block">{{ $apartamento->edificioName->nombre }}</small>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn-modern">
                                        <i class="fas fa-save me-2"></i>Crear Tarifa
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="preview-card">
                        <div class="preview-header">
                            <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Vista Previa</h5>
                        </div>
                        
                        <div id="previewContent">
                            <div class="text-center text-muted">
                                <i class="fas fa-tag fa-3x mb-3"></i>
                                <p>Completa el formulario para ver la vista previa</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para actualizar la vista previa
    function updatePreview() {
        const nombre = document.getElementById('nombre').value || 'Nombre de la tarifa';
        const precio = document.getElementById('precio').value || '0';
        const descripcion = document.getElementById('descripcion').value || 'Sin descripción';
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        const temporadaAlta = document.getElementById('temporada_alta').checked;
        const temporadaBaja = document.getElementById('temporada_baja').checked;
        
        let temporadaText = '';
        if (temporadaAlta && temporadaBaja) {
            temporadaText = '<span class="temporada-badge temporada-alta">Alta</span><span class="temporada-badge temporada-baja">Baja</span>';
        } else if (temporadaAlta) {
            temporadaText = '<span class="temporada-badge temporada-alta">Alta</span>';
        } else if (temporadaBaja) {
            temporadaText = '<span class="temporada-badge temporada-baja">Baja</span>';
        } else {
            temporadaText = '<span class="temporada-badge temporada-general">General</span>';
        }
        
        const previewContent = `
            <h6 class="text-center mb-3">${nombre}</h6>
            <div class="preview-price">${parseFloat(precio).toFixed(2)} €</div>
            <div class="preview-dates">
                <i class="fas fa-calendar me-1"></i>
                ${fechaInicio ? new Date(fechaInicio).toLocaleDateString('es-ES') : 'Fecha inicio'} - 
                ${fechaFin ? new Date(fechaFin).toLocaleDateString('es-ES') : 'Fecha fin'}
            </div>
            <div class="preview-temporada">
                ${temporadaText}
            </div>
            <p class="text-muted text-center small">${descripcion}</p>
        `;
        
        document.getElementById('previewContent').innerHTML = previewContent;
    }
    
    // Event listeners para actualizar la vista previa
    document.getElementById('nombre').addEventListener('input', updatePreview);
    document.getElementById('precio').addEventListener('input', updatePreview);
    document.getElementById('descripcion').addEventListener('input', updatePreview);
    document.getElementById('fecha_inicio').addEventListener('change', updatePreview);
    document.getElementById('fecha_fin').addEventListener('change', updatePreview);
    document.getElementById('temporada_alta').addEventListener('change', updatePreview);
    document.getElementById('temporada_baja').addEventListener('change', updatePreview);
    
    // Validación de fechas
    document.getElementById('fecha_fin').addEventListener('change', function() {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = this.value;
        
        if (fechaInicio && fechaFin && fechaFin < fechaInicio) {
            this.setCustomValidity('La fecha de fin debe ser posterior a la fecha de inicio');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Inicializar vista previa
    document.addEventListener('DOMContentLoaded', function() {
        updatePreview();
    });
</script>
@endsection
