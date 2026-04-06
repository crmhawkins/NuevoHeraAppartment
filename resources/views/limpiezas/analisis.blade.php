@extends('layouts.appPersonal')

@section('title', 'Análisis de Limpiezas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header Apple-style -->
            <div class="apple-header">
                <div class="apple-header-content">
                    <h1><i class="fa-solid fa-microscope"></i> Análisis de Limpiezas</h1>
                    <p>Revisa la calidad de las limpiezas con análisis de IA</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="apple-card mb-4">
                <div class="apple-card-header">
                    <h5><i class="fa-solid fa-filter"></i> Filtros</h5>
                </div>
                <div class="apple-card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="fecha_desde" class="form-label">Fecha Desde</label>
                            <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                            <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="empleada" class="form-label">Empleada</label>
                            <select class="form-control" id="empleada" name="empleada">
                                <option value="">Todas</option>
                                @foreach($empleadas as $empleada)
                                    <option value="{{ $empleada->id }}" {{ $filtros['empleada'] == $empleada->id ? 'selected' : '' }}>
                                        {{ $empleada->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="calidad" class="form-label">Calidad</label>
                            <select class="form-control" id="calidad" name="calidad">
                                <option value="">Todas</option>
                                <option value="excelente" {{ $filtros['calidad'] == 'excelente' ? 'selected' : '' }}>Excelente</option>
                                <option value="buena" {{ $filtros['calidad'] == 'buena' ? 'selected' : '' }}>Buena</option>
                                <option value="regular" {{ $filtros['calidad'] == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="mala" {{ $filtros['calidad'] == 'mala' ? 'selected' : '' }}>Mala</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="button" class="apple-btn apple-btn-primary" onclick="aplicarFiltros()">
                                <i class="fa-solid fa-search"></i> Aplicar Filtros
                            </button>
                            <button type="button" class="apple-btn apple-btn-secondary" onclick="limpiarFiltros()">
                                <i class="fa-solid fa-times"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Análisis -->
            <div class="apple-card">
                <div class="apple-card-header">
                    <h5><i class="fa-solid fa-list"></i> Análisis de Limpiezas</h5>
                    <div class="apple-card-actions">
                        <span class="badge bg-primary">{{ $analisis->total() }} análisis</span>
                    </div>
                </div>
                <div class="apple-card-body">
                    @if($analisis->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Apartamento</th>
                                        <th>Empleada</th>
                                        <th>Categoría</th>
                                        <th>Calidad</th>
                                        <th>Puntuación</th>
                                        <th>Responsabilidad</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analisis as $analisisItem)
                                        <tr>
                                            <td>{{ $analisisItem->fecha_analisis->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <strong>{{ $analisisItem->limpieza->apartamento->nombre ?? 'N/A' }}</strong>
                                            </td>
                                            <td>{{ $analisisItem->empleada->name ?? 'N/A' }}</td>
                                            <td>{{ $analisisItem->categoria_nombre }}</td>
                                            <td>
                                                <span class="badge bg-{{ $analisisItem->calidad_general === 'excelente' ? 'success' : ($analisisItem->calidad_general === 'buena' ? 'info' : ($analisisItem->calidad_general === 'regular' ? 'warning' : 'danger')) }}">
                                                    {{ ucfirst($analisisItem->calidad_general) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary me-2">{{ $analisisItem->puntuacion }}/10</span>
                                                    @if($analisisItem->cumple_estandares)
                                                        <i class="fa-solid fa-check-circle text-success"></i>
                                                    @else
                                                        <i class="fa-solid fa-times-circle text-danger"></i>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($analisisItem->continuo_bajo_responsabilidad)
                                                    <span class="badge bg-warning">
                                                        <i class="fa-solid fa-exclamation-triangle"></i> Bajo Responsabilidad
                                                    </span>
                                                @else
                                                    <span class="badge bg-success">
                                                        <i class="fa-solid fa-check"></i> Aprobada
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="verDetalleAnalisis({{ $analisisItem->id }})">
                                                    <i class="fa-solid fa-eye"></i> Ver
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="verFoto({{ $analisisItem->id }})">
                                                    <i class="fa-solid fa-image"></i> Foto
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $analisis->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay análisis disponibles</h5>
                            <p class="text-muted">Aplica otros filtros o espera a que se realicen nuevos análisis</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalle del Análisis -->
<div class="modal fade" id="detalleAnalisisModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Análisis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleAnalisisContent">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Foto -->
<div class="modal fade" id="verFotoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Foto Analizada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="fotoAnalizada" src="" alt="Foto analizada" class="img-fluid" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos Apple-style */
.apple-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    border: 2px solid #e9ecef;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.apple-header-content h1 {
    color: #2c3e50;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.apple-header-content p {
    color: #6c757d;
    font-size: 18px;
    margin: 0;
}

.apple-card {
    background: #ffffff;
    border-radius: 16px;
    border: 2px solid #e9ecef;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.apple-card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 20px 24px;
    border-bottom: 2px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.apple-card-header h5 {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
}

.apple-card-body {
    padding: 24px;
}

.apple-btn {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.apple-btn-primary {
    background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
    color: white;
}

.apple-btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

.apple-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.table th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #2c3e50;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

.badge {
    font-size: 12px;
    padding: 6px 12px;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .apple-header {
        padding: 24px;
        margin-bottom: 20px;
    }
    
    .apple-header-content h1 {
        font-size: 24px;
    }
    
    .apple-card-body {
        padding: 20px;
    }
    
    .table-responsive {
        font-size: 14px;
    }
}
</style>

<script>
function aplicarFiltros() {
    const fechaDesde = document.getElementById('fecha_desde').value;
    const fechaHasta = document.getElementById('fecha_hasta').value;
    const empleada = document.getElementById('empleada').value;
    const calidad = document.getElementById('calidad').value;
    
    // Construir URL con filtros
    let url = '{{ route("limpiezas.analisis") }}?';
    const params = new URLSearchParams();
    
    if (fechaDesde) params.append('fecha_desde', fechaDesde);
    if (fechaHasta) params.append('fecha_hasta', fechaHasta);
    if (empleada) params.append('empleada', empleada);
    if (calidad) params.append('calidad', calidad);
    
    url += params.toString();
    window.location.href = url;
}

function limpiarFiltros() {
    document.getElementById('fecha_desde').value = '';
    document.getElementById('fecha_hasta').value = '';
    document.getElementById('empleada').value = '';
    document.getElementById('calidad').value = '';
    
    window.location.href = '{{ route("limpiezas.analisis") }}';
}

function verDetalleAnalisis(analisisId) {
    // Mostrar loading
    const modal = document.getElementById('detalleAnalisisModal');
    const content = document.getElementById('detalleAnalisisContent');
    
    content.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando análisis...</p></div>';
    
    // Llamar a la API para obtener detalles
    fetch(`/api/photo-analysis/${analisisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const analisis = data.analisis;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fa-solid fa-chart-line"></i> Calidad General</h6>
                            <p class="badge bg-${analisis.calidad_general === 'excelente' ? 'success' : (analisis.calidad_general === 'buena' ? 'info' : (analisis.calidad_general === 'regular' ? 'warning' : 'danger'))} fs-6">
                                ${analisis.calidad_general.charAt(0).toUpperCase() + analisis.calidad_general.slice(1)}
                            </p>
                            
                            <h6 class="mt-3"><i class="fa-solid fa-star"></i> Puntuación</h6>
                            <p class="badge bg-primary fs-6">${analisis.puntuacion}/10</p>
                            
                            <h6 class="mt-3"><i class="fa-solid fa-check-double"></i> Cumple Estándares</h6>
                            <p class="badge bg-${analisis.cumple_estandares ? 'success' : 'danger'} fs-6">
                                ${analisis.cumple_estandares ? 'Sí' : 'No'}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fa-solid fa-exclamation-triangle"></i> Deficiencias</h6>
                            <ul class="list-unstyled">
                                ${analisis.deficiencias.map(def => `<li><i class="fa-solid fa-circle text-danger me-2"></i>${def}</li>`).join('')}
                            </ul>
                            
                            <h6 class="mt-3"><i class="fa-solid fa-lightbulb"></i> Recomendaciones</h6>
                            <ul class="list-unstyled">
                                ${analisis.recomendaciones.map(rec => `<li><i class="fa-solid fa-circle text-info me-2"></i>${rec}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="fa-solid fa-comment"></i> Observaciones</h6>
                            <p class="text-muted">${analisis.observaciones}</p>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Error al cargar el análisis</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="alert alert-danger">Error al cargar el análisis</div>';
        });
    
    // Mostrar modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

function verFoto(analisisId) {
    // Obtener la URL de la foto
    fetch(`/api/photo-analysis/${analisisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('fotoAnalizada').src = data.analisis.image_url;
                const modal = new bootstrap.Modal(document.getElementById('verFotoModal'));
                modal.show();
            } else {
                alert('Error al cargar la foto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar la foto');
        });
}
</script>
@endsection
