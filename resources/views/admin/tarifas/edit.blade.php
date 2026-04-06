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
    
    .form-floating {
        position: relative;
        margin-bottom: 1.5rem;
    }
    
    .form-control, .form-select {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 15px;
        font-size: 1rem;
        transition: all 0.3s ease;
        height: auto;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        outline: none;
        transform: translateY(-2px);
    }
    
    .form-floating label {
        position: absolute;
        top: 15px;
        left: 15px;
        color: #6c757d;
        transition: all 0.3s ease;
        pointer-events: none;
        background: white;
        padding: 0 5px;
        font-size: 0.9rem;
    }
    
    .form-control:focus + label,
    .form-control:not(:placeholder-shown) + label,
    .form-select:focus + label,
    .form-select:not([value=""]) + label {
        top: -10px;
        left: 10px;
        font-size: 0.8rem;
        color: #667eea;
        font-weight: 600;
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
                    <i class="fas fa-edit me-2"></i>Editar Tarifa: {{ $tarifa->nombre }}
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
                            <h3><i class="fas fa-edit me-2"></i>Editar Información de la Tarifa</h3>
                        </div>
                        
                        <div class="form-body">
                            <form action="{{ route('tarifas.update', $tarifa) }}" method="POST" id="tarifaForm">
                                @csrf
                                @method('PUT')
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" 
                                                   class="form-control @error('nombre') is-invalid @enderror" 
                                                   id="nombre" 
                                                   name="nombre" 
                                                   placeholder="Nombre de la tarifa"
                                                   value="{{ old('nombre', $tarifa->nombre) }}" 
                                                   required>
                                            <label for="nombre">Nombre de la tarifa</label>
                                            @error('nombre')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" 
                                                   class="form-control @error('precio') is-invalid @enderror" 
                                                   id="precio" 
                                                   name="precio" 
                                                   placeholder="Precio por noche"
                                                   step="0.01" 
                                                   min="0" 
                                                   value="{{ old('precio', $tarifa->precio) }}" 
                                                   required>
                                            <label for="precio">Precio por noche (€)</label>
                                            @error('precio')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-floating">
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              placeholder="Descripción de la tarifa"
                                              rows="3">{{ old('descripcion', $tarifa->descripcion) }}</textarea>
                                    <label for="descripcion">Descripción (opcional)</label>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="date" 
                                                   class="form-control @error('fecha_inicio') is-invalid @enderror" 
                                                   id="fecha_inicio" 
                                                   name="fecha_inicio" 
                                                   value="{{ old('fecha_inicio', $tarifa->fecha_inicio->format('Y-m-d')) }}" 
                                                   required>
                                            <label for="fecha_inicio">Fecha de inicio</label>
                                            @error('fecha_inicio')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="date" 
                                                   class="form-control @error('fecha_fin') is-invalid @enderror" 
                                                   id="fecha_fin" 
                                                   name="fecha_fin" 
                                                   value="{{ old('fecha_fin', $tarifa->fecha_fin->format('Y-m-d')) }}" 
                                                   required>
                                            <label for="fecha_fin">Fecha de fin</label>
                                            @error('fecha_fin')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="checkbox-modern">
                                    <input type="checkbox" id="temporada_alta" name="temporada_alta" value="1" 
                                           {{ old('temporada_alta', $tarifa->temporada_alta) ? 'checked' : '' }}>
                                    <label for="temporada_alta">Aplicar a temporada alta</label>
                                </div>
                                
                                <div class="checkbox-modern">
                                    <input type="checkbox" id="temporada_baja" name="temporada_baja" value="1" 
                                           {{ old('temporada_baja', $tarifa->temporada_baja) ? 'checked' : '' }}>
                                    <label for="temporada_baja">Aplicar a temporada baja</label>
                                </div>
                                
                                <div class="apartamentos-section">
                                    <h5 class="mb-3">
                                        <i class="fas fa-building me-2"></i>Apartamentos que aplican esta tarifa
                                    </h5>
                                    <p class="text-muted mb-3">Selecciona los apartamentos donde se aplicará esta tarifa. Los apartamentos ya asignados aparecen marcados.</p>
                                    
                                    @foreach($apartamentos as $apartamento)
                                        <div class="apartamento-checkbox">
                                            <input type="checkbox" 
                                                   id="apartamento_{{ $apartamento->id }}" 
                                                   name="apartamentos[]" 
                                                   value="{{ $apartamento->id }}"
                                                   {{ in_array($apartamento->id, old('apartamentos', $tarifa->apartamentos->pluck('id')->toArray())) ? 'checked' : '' }}>
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
                                        <i class="fas fa-save me-2"></i>Actualizar Tarifa
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
