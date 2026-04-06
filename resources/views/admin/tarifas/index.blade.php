@extends('layouts.appAdmin')

@section('content')
<style>
    .tarifa-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }
    
    .tarifa-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    
    .tarifa-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 20px;
    }
    
    .tarifa-body {
        padding: 25px;
    }
    
    .btn-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 10px 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: white;
        text-decoration: none;
        display: inline-block;
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
        border-radius: 20px;
        padding: 8px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: white;
        text-decoration: none;
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
        border-radius: 20px;
        padding: 8px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: white;
        text-decoration: none;
    }
    
    .btn-danger-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
        color: white;
        text-decoration: none;
    }
    
    .status-badge {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
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
    
    .temporada-badge {
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.7rem;
        font-weight: 600;
        margin: 2px;
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
    
    .precio-display {
        font-size: 1.5rem;
        font-weight: 700;
        color: #28a745;
    }
    
    .fecha-display {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .apartamentos-list {
        max-height: 100px;
        overflow-y: auto;
        background: #f8f9fa;
        border-radius: 10px;
        padding: 10px;
        margin-top: 10px;
    }
    
    .apartamento-item {
        background: white;
        padding: 5px 10px;
        border-radius: 5px;
        margin: 2px 0;
        font-size: 0.8rem;
        border-left: 3px solid #667eea;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-tags me-2"></i>Gestión de Tarifas
                </h2>
                <a href="{{ route('tarifas.create') }}" class="btn-modern">
                    <i class="fas fa-plus me-2"></i>Nueva Tarifa
                </a>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
    </div>
    
    <div class="row">
        @forelse($tarifas as $tarifa)
            <div class="col-lg-6 col-xl-4">
                <div class="tarifa-card">
                    <div class="tarifa-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">{{ $tarifa->nombre }}</h5>
                                <p class="mb-0 opacity-75">{{ Str::limit($tarifa->descripcion, 50) }}</p>
                            </div>
                            <span class="status-badge {{ $tarifa->activo ? 'status-active' : 'status-inactive' }}">
                                {{ $tarifa->activo ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="tarifa-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="precio-display">{{ $tarifa->precio_formateado }}</div>
                                <small class="text-muted">Precio por noche</small>
                            </div>
                            <div class="col-6 text-end">
                                <div class="fecha-display">
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ $tarifa->rango_fechas }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Temporada:</strong>
                            @if($tarifa->temporada_alta)
                                <span class="temporada-badge temporada-alta">Alta</span>
                            @endif
                            @if($tarifa->temporada_baja)
                                <span class="temporada-badge temporada-baja">Baja</span>
                            @endif
                            @if(!$tarifa->temporada_alta && !$tarifa->temporada_baja)
                                <span class="temporada-badge bg-secondary text-white">General</span>
                            @endif
                        </div>
                        
                        @if($tarifa->apartamentos->count() > 0)
                            <div class="mb-3">
                                <strong>Apartamentos asignados ({{ $tarifa->apartamentos->count() }}):</strong>
                                <div class="apartamentos-list">
                                    @foreach($tarifa->apartamentos->take(3) as $apartamento)
                                        <div class="apartamento-item">
                                            {{ $apartamento->nombre }}
                                        </div>
                                    @endforeach
                                    @if($tarifa->apartamentos->count() > 3)
                                        <div class="apartamento-item text-center">
                                            <em>+{{ $tarifa->apartamentos->count() - 3 }} más</em>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="mb-3">
                                <span class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    No hay apartamentos asignados
                                </span>
                            </div>
                        @endif
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="btn-group" role="group">
                                <a href="{{ route('tarifas.show', $tarifa) }}" class="btn-secondary-modern">
                                    <i class="fas fa-eye me-1"></i>Ver
                                </a>
                                <a href="{{ route('tarifas.edit', $tarifa) }}" class="btn-modern">
                                    <i class="fas fa-edit me-1"></i>Editar
                                </a>
                            </div>
                            
                            <div class="btn-group" role="group">
                                <form action="{{ route('tarifas.toggle-status', $tarifa) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn-secondary-modern" 
                                            onclick="return confirm('¿Estás seguro de cambiar el estado de esta tarifa?')">
                                        <i class="fas fa-toggle-{{ $tarifa->activo ? 'off' : 'on' }} me-1"></i>
                                        {{ $tarifa->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                                
                                <form action="{{ route('tarifas.destroy', $tarifa) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger-modern" 
                                            onclick="return confirm('¿Estás seguro de eliminar esta tarifa? Esta acción no se puede deshacer.')">
                                        <i class="fas fa-trash me-1"></i>Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay tarifas creadas</h4>
                    <p class="text-muted">Comienza creando tu primera tarifa para gestionar los precios de tus apartamentos.</p>
                    <a href="{{ route('tarifas.create') }}" class="btn-modern">
                        <i class="fas fa-plus me-2"></i>Crear Primera Tarifa
                    </a>
                </div>
            </div>
        @endforelse
    </div>
    
    @if($tarifas->hasPages())
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-center">
                    {{ $tarifas->links() }}
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
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
