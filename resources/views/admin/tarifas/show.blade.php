@extends('layouts.appAdmin')

@section('content')
<style>
    .tarifa-detail-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }
    
    .tarifa-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px 20px 0 0;
        padding: 30px;
        text-align: center;
    }
    
    .tarifa-header h2 {
        margin: 0;
        font-weight: 600;
        font-size: 2rem;
    }
    
    .tarifa-body {
        padding: 30px;
    }
    
    .info-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        margin: 20px 0;
        border-left: 4px solid #667eea;
    }
    
    .info-section h5 {
        color: #495057;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    .info-item {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .info-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .info-label {
        font-size: 0.9rem;
        color: #6c757d;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .info-value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #495057;
    }
    
    .precio-display {
        font-size: 3rem;
        font-weight: 700;
        color: #28a745;
        text-align: center;
        margin: 20px 0;
    }
    
    .temporada-badge {
        padding: 8px 20px;
        border-radius: 25px;
        font-size: 0.9rem;
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
    
    .status-badge {
        padding: 8px 20px;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-active {
        background: #28a745;
        color: white;
    }
    
    .status-inactive {
        background: #6c757d;
        color: white;
    }
    
    .btn-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: white;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
    }
    
    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
    }
    
    .btn-secondary-modern {
        background: #6c757d;
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: white;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
    }
    
    .btn-secondary-modern:hover {
        background: #5a6268;
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }
    
    .btn-danger-modern {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: white;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
    }
    
    .btn-danger-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
        color: white;
        text-decoration: none;
    }
    
    .apartamentos-list {
        max-height: 300px;
        overflow-y: auto;
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-top: 15px;
    }
    
    .apartamento-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin: 10px 0;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .apartamento-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }
    
    .apartamento-name {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    
    .apartamento-building {
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .fecha-range {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 15px;
        text-align: center;
        margin: 20px 0;
    }
    
    .fecha-range h4 {
        margin: 0;
        font-weight: 600;
    }
    
    .fecha-range p {
        margin: 10px 0 0 0;
        opacity: 0.9;
    }
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-tag me-2"></i>Detalles de la Tarifa
                </h2>
                <div>
                    <a href="{{ route('tarifas.edit', $tarifa) }}" class="btn-modern">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('tarifas.index') }}" class="btn-secondary-modern">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
            
            <div class="tarifa-detail-card">
                <div class="tarifa-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">{{ $tarifa->nombre }}</h2>
                            <p class="mb-0 opacity-75">{{ $tarifa->descripcion ?: 'Sin descripción' }}</p>
                        </div>
                        <span class="status-badge {{ $tarifa->activo ? 'status-active' : 'status-inactive' }}">
                            {{ $tarifa->activo ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>
                </div>
                
                <div class="tarifa-body">
                    <div class="text-center">
                        <div class="precio-display">{{ $tarifa->precio_formateado }}</div>
                        <p class="text-muted">Precio por noche</p>
                    </div>
                    
                    <div class="fecha-range">
                        <h4><i class="fas fa-calendar me-2"></i>Período de Validez</h4>
                        <p>{{ $tarifa->rango_fechas }}</p>
                    </div>
                    
                    <div class="info-section">
                        <h5><i class="fas fa-info-circle me-2"></i>Información General</h5>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Tipo de Temporada</div>
                                <div class="info-value">
                                    @if($tarifa->temporada_alta && $tarifa->temporada_baja)
                                        <span class="temporada-badge temporada-alta">Alta</span>
                                        <span class="temporada-badge temporada-baja">Baja</span>
                                    @elseif($tarifa->temporada_alta)
                                        <span class="temporada-badge temporada-alta">Alta</span>
                                    @elseif($tarifa->temporada_baja)
                                        <span class="temporada-badge temporada-baja">Baja</span>
                                    @else
                                        <span class="temporada-badge temporada-general">General</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Estado</div>
                                <div class="info-value">
                                    <span class="status-badge {{ $tarifa->activo ? 'status-active' : 'status-inactive' }}">
                                        {{ $tarifa->activo ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Fecha de Creación</div>
                                <div class="info-value">{{ $tarifa->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Última Actualización</div>
                                <div class="info-value">{{ $tarifa->updated_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h5><i class="fas fa-building me-2"></i>Apartamentos Asignados ({{ $tarifa->apartamentos->count() }})</h5>
                        
                        @if($tarifa->apartamentos->count() > 0)
                            <div class="apartamentos-list">
                                @foreach($tarifa->apartamentos as $apartamento)
                                    <div class="apartamento-item">
                                        <div class="apartamento-name">{{ $apartamento->nombre }}</div>
                                        @if($apartamento->edificio)
                                            <div class="apartamento-building">{{ $apartamento->edificioName ? $apartamento->edificioName->nombre : 'N/A' }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-building fa-2x mb-3"></i>
                                <p>No hay apartamentos asignados a esta tarifa</p>
                                <p class="small">Puedes asignar apartamentos editando la tarifa</p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="text-center mt-4">
                        <div class="btn-group" role="group">
                            <a href="{{ route('tarifas.edit', $tarifa) }}" class="btn-modern">
                                <i class="fas fa-edit me-2"></i>Editar Tarifa
                            </a>
                            
                            <form action="{{ route('tarifas.toggle-status', $tarifa) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-secondary-modern" 
                                        onclick="return confirm('¿Estás seguro de cambiar el estado de esta tarifa?')">
                                    <i class="fas fa-toggle-{{ $tarifa->activo ? 'off' : 'on' }} me-2"></i>
                                    {{ $tarifa->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                            
                            <form action="{{ route('tarifas.destroy', $tarifa) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger-modern" 
                                        onclick="return confirm('¿Estás seguro de eliminar esta tarifa? Esta acción no se puede deshacer.')">
                                    <i class="fas fa-trash me-2"></i>Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Confirm delete with SweetAlert
    function confirmDelete(form) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }
</script>
@endsection
