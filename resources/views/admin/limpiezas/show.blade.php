@extends('layouts.appAdmin')

@section('title', 'Detalles de Limpieza #' . $limpieza->id)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-microscope"></i> Limpieza #{{ $limpieza->id }}
                    </h1>
                    <p class="text-muted mb-0">
                        @if($limpieza->apartamento)
                            🏠 {{ $limpieza->apartamento->nombre }}
                        @elseif($limpieza->zonaComun)
                            🏢 {{ $limpieza->zonaComun->nombre }} (Zona Común)
                        @else
                            Elemento no encontrado
                        @endif
                        - {{ $limpieza->estado->nombre ?? 'Estado N/A' }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.limpiezas.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <a href="{{ route('gestion.edit', $limpieza->id) }}" class="btn btn-info" target="_blank">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    @if($limpieza->analisis->count() > 0)
                        <a href="{{ route('limpiezas.analisis') }}?limpieza_id={{ $limpieza->id }}" class="btn btn-warning">
                            <i class="fas fa-microscope"></i> Ver Análisis
                        </a>
                    @endif
                </div>
            </div>

            <!-- Información General -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle"></i> Información General
                            </h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>#{{ $limpieza->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Elemento:</strong></td>
                                    <td>
                                        @if($limpieza->apartamento)
                                            <span class="badge bg-info">
                                                🏠 {{ $limpieza->apartamento->nombre }}
                                            </span>
                                        @elseif($limpieza->zonaComun)
                                            <span class="badge bg-purple zona-comun-badge">
                                                🏢 {{ $limpieza->zonaComun->nombre }}
                                                <br><small>Zona Común</small>
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                N/A 
                                                @if($limpieza->tipo_limpieza)
                                                    ({{ $limpieza->tipo_limpieza }})
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Empleada:</strong></td>
                                    <td>
                                        @if($limpieza->empleada)
                                            <span class="badge bg-success">
                                                {{ $limpieza->empleada->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">No asignada</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        @if($limpieza->estado)
                                            <span class="badge bg-{{ $limpieza->estado->id == 1 ? 'warning' : ($limpieza->estado->id == 2 ? 'info' : 'success') }}">
                                                {{ $limpieza->estado->nombre }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Inicio:</strong></td>
                                    <td>{{ $limpieza->fecha_comienzo ? $limpieza->fecha_comienzo->format('d/m/Y H:i') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Fin:</strong></td>
                                    <td>{{ $limpieza->fecha_fin ? $limpieza->fecha_fin->format('d/m/Y H:i') : 'N/A' }}</td>
                                </tr>
                                @if($limpieza->observacion)
                                    <tr>
                                        <td><strong>Observaciones:</strong></td>
                                        <td>{{ $limpieza->observacion }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-chart-bar"></i> Estadísticas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="h4 text-primary">{{ $estadisticas['porcentaje_completado'] ?? 0 }}%</div>
                                    <small class="text-muted">Completado</small>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-{{ ($estadisticas['porcentaje_completado'] ?? 0) == 100 ? 'success' : (($estadisticas['porcentaje_completado'] ?? 0) > 50 ? 'warning' : 'danger') }}" 
                                             style="width: {{ $estadisticas['porcentaje_completado'] ?? 0 }}%"></div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="h4 text-info">{{ $estadisticas['total_fotos'] ?? 0 }}</div>
                                    <small class="text-muted">Fotos</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="h4 text-success">{{ $estadisticas['fotos_aprobadas'] ?? 0 }}</div>
                                    <small class="text-muted">Aprobadas</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="h4 text-danger">{{ $estadisticas['fotos_rechazadas'] ?? 0 }}</div>
                                    <small class="text-muted">Rechazadas</small>
                                </div>
                                @if(($estadisticas['bajo_responsabilidad'] ?? 0) > 0)
                                    <div class="col-6 mb-3">
                                        <div class="h4 text-warning">{{ $estadisticas['bajo_responsabilidad'] ?? 0 }}</div>
                                        <small class="text-muted">Bajo Responsabilidad</small>
                                    </div>
                                @endif
                                @if(($estadisticas['puntuacion_media'] ?? 0) > 0)
                                    <div class="col-6 mb-3">
                                        <div class="h4 text-primary">{{ $estadisticas['puntuacion_media'] ?? 0 }}/10</div>
                                        <small class="text-muted">Puntuación Media</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gestión de Amenities -->
            @if($limpieza->apartamento)
                @if($estadisticasAmenities['total_amenities'] > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-pump-soap"></i> Amenities Gestionados
                            <span class="badge bg-success ms-2">{{ $estadisticasAmenities['total_amenities'] }} items</span>
                            @if($estadisticasAmenities['total_costo'] > 0)
                                <span class="badge bg-info ms-2">€{{ number_format($estadisticasAmenities['total_costo'], 2, ',', '.') }}</span>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body">
                        @foreach($estadisticasAmenities['categorias'] as $categoria => $datos)
                            <div class="mb-4">
                                <h6 class="text-success border-bottom pb-2 mb-3">
                                    <i class="fas fa-tags"></i> {{ ucfirst($categoria ?? 'Sin Categoría') }}
                                    <span class="badge bg-light text-dark ms-2">{{ $datos['cantidad'] }} items</span>
                                    @if($datos['costo'] > 0)
                                        <span class="badge bg-info ms-2">€{{ number_format($datos['costo'], 2, ',', '.') }}</span>
                                    @endif
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Amenity</th>
                                                <th class="text-center">Consumido</th>
                                                <th class="text-center">Dejado</th>
                                                <th class="text-center">Costo</th>
                                                <th>Observaciones</th>
                                                <th class="text-center">Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($datos['items'] as $item)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $item['nombre'] }}</strong>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-warning text-dark">
                                                            {{ $item['cantidad_consumida'] }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-success">
                                                            {{ $item['cantidad_actual'] }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($item['costo_total'] > 0)
                                                            <span class="text-info fw-bold">
                                                                €{{ number_format($item['costo_total'], 2, ',', '.') }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($item['observaciones'])
                                                            <small class="text-muted">{{ $item['observaciones'] }}</small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <small class="text-muted">
                                                            {{ $item['fecha_consumo'] ? \Carbon\Carbon::parse($item['fecha_consumo'])->format('d/m/Y') : 'N/A' }}
                                                        </small>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-pump-soap"></i> Amenities
                        </h6>
                    </div>
                    <div class="card-body text-center py-4">
                        <i class="fas fa-pump-soap fa-2x text-muted mb-3"></i>
                        <p class="text-muted mb-2">No hay amenities gestionados para esta limpieza</p>
                        <a href="{{ route('amenity.limpieza.show', $limpieza->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-2"></i>Gestionar Amenities
                        </a>
                    </div>
                </div>
                @endif
            @elseif($limpieza->zonaComun)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-purple">
                            <i class="fas fa-building"></i> Zona Común - Sin Amenities
                        </h6>
                    </div>
                    <div class="card-body text-center py-4">
                        <i class="fas fa-building fa-2x text-purple mb-3"></i>
                        <p class="text-muted mb-2">Las zonas comunes no requieren amenities</p>
                        <span class="badge bg-purple">Zona Común</span>
                    </div>
                </div>
            @endif

            <!-- Checklist Completado -->
            @if($limpieza->apartamento)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-check-square"></i> Checklist Completado
                            <span class="badge bg-primary ms-2">{{ $estadisticas['items_marcados'] ?? 0 }}/{{ $estadisticas['total_items'] ?? 0 }}</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($estadisticas['total_items'] > 0)
                            @foreach($itemsUnicos['items'] as $categoria => $items)
                                <div class="mb-4">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <!-- DEBUG: Categoria = {{ $categoria }} -->
                                        @if($categoria == 'COCINA')
                                            <i class="fas fa-utensils"></i> Cocina
                                        @elseif($categoria == 'DORMITORIO')
                                            <i class="fas fa-bed"></i> Dormitorio
                                        @elseif($categoria == 'SALÓN' || $categoria == 'SALON')
                                            <i class="fas fa-couch"></i> Salón
                                        @elseif($categoria == 'BAÑO')
                                            <i class="fas fa-bath"></i> Baño
                                        @elseif($categoria == 'AMENITIES')
                                            <i class="fas fa-gift"></i> Amenities
                                        @elseif($categoria == 'ESCALERA')
                                            <i class="fas fa-stairs"></i> Escalera
                                        @elseif($categoria == 'ASCENSOR')
                                            <i class="fas fa-elevator"></i> Ascensor
                                        @elseif($categoria == 'ARMARIO')
                                            <i class="fas fa-wardrobe"></i> Armario
                                        @elseif($categoria == 'CANAPE')
                                            <i class="fas fa-bed"></i> Canapé
                                        @elseif($categoria == 'CAJON DE CAMA')
                                            <i class="fas fa-drawer"></i> Cajón de Cama
                                        @elseif($categoria == 'PERCHERO')
                                            <i class="fas fa-hanger"></i> Perchero
                                        @elseif($categoria == 'COCINA COMUN')
                                            <i class="fas fa-utensils"></i> Cocina Común
                                        @else
                                            {{ $categoria ?? 'Sin Categoría' }}
                                        @endif
                                    </h6>
                                    <div class="row">
                                        @foreach($items as $item)
                                            <div class="col-md-4 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        @if($item->estado == 1)
                                                            <i class="fas fa-check-circle text-success fa-2x"></i>
                                                        @else
                                                            <i class="fas fa-times-circle text-danger fa-2x"></i>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <strong>{{ $item->nombre ?? $item->item->nombre ?? 'Item N/A' }}</strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $item->estado == 1 ? 'Completado' : 'Pendiente' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-clipboard-list fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No hay items de checklist para esta limpieza</p>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($limpieza->zonaComun)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-purple">
                            <i class="fas fa-building"></i> Zona Común - Sin Checklist Detallado
                        </h6>
                    </div>
                    <div class="card-body text-center py-4">
                        <i class="fas fa-building fa-2x text-purple mb-3"></i>
                        <p class="text-muted mb-2">Las zonas comunes no requieren checklist detallado</p>
                        @if($limpieza->observacion)
                            <div class="mt-3">
                                <strong>Observaciones:</strong>
                                <p class="text-muted mt-2">{{ $limpieza->observacion }}</p>
                            </div>
                        @endif
                        <span class="badge bg-purple">Zona Común</span>
                    </div>
                </div>
            @endif

            <!-- Análisis de Fotos -->
            @if($analisisFotos->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-microscope"></i> Análisis de Fotos con IA
                            <span class="badge bg-info ms-2">{{ $analisisFotos->count() }} análisis</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($analisisFotos as $analisis)
                                <div class="col-md-6 mb-4">
                                    <div class="card border-{{ $analisis->cumple_estandares ? 'success' : 'danger' }}">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-image"></i> {{ $analisis->categoria_nombre }}
                                            </h6>
                                            <div class="d-flex gap-2">
                                                @if($analisis->cumple_estandares)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Aprobada
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times"></i> Rechazada
                                                    </span>
                                                @endif
                                                @if($analisis->continuo_bajo_responsabilidad)
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Bajo Responsabilidad
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    @if($analisis->image_url)
                                                        <div class="position-relative">
                                                            <img src="{{ asset($analisis->image_url) }}" 
                                                                 alt="Foto analizada" 
                                                                 class="img-fluid rounded foto-analisis" 
                                                                 style="max-height: 150px; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;"
                                                                 onclick="ampliarFoto('{{ asset($analisis->image_url) }}', '{{ $analisis->image_url }}')">
                                                            <div class="position-absolute top-0 end-0 m-2">
                                                                <span class="badge bg-primary">
                                                                    <i class="fas fa-search-plus"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                             style="height: 150px;">
                                                            <i class="fas fa-image fa-2x text-muted"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="mb-2">
                                                        <strong>Calidad:</strong>
                                                        <span class="badge bg-{{ $analisis->calidad_general === 'excelente' ? 'success' : ($analisis->calidad_general === 'buena' ? 'info' : ($analisis->calidad_general === 'regular' ? 'warning' : 'danger')) }}">
                                                            {{ ucfirst($analisis->calidad_general) }}
                                                        </span>
                                                    </div>
                                                    <div class="mb-2">
                                                        <strong>Puntuación:</strong>
                                                        <span class="badge bg-primary">{{ $analisis->puntuacion }}/10</span>
                                                    </div>
                                                    @if($analisis->deficiencias && count($analisis->deficiencias) > 0)
                                                        <div class="mb-2">
                                                            <strong>Deficiencias:</strong>
                                                            <ul class="list-unstyled mb-0">
                                                                @foreach($analisis->deficiencias as $deficiencia)
                                                                    <li><i class="fas fa-exclamation-triangle text-warning me-1"></i>{{ $deficiencia }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                    @if($analisis->recomendaciones && count($analisis->recomendaciones) > 0)
                                                        <div class="mb-2">
                                                            <strong>Recomendaciones:</strong>
                                                            <ul class="list-unstyled mb-0">
                                                                @foreach($analisis->recomendaciones as $recomendacion)
                                                                    <li><i class="fas fa-lightbulb text-info me-1"></i>{{ $recomendacion }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                    @if($analisis->observaciones)
                                                        <div class="mb-2">
                                                            <strong>Observaciones:</strong>
                                                            <p class="mb-0 text-muted">{{ $analisis->observaciones }}</p>
                                                        </div>
                                                    @endif
                                                    <div class="text-muted">
                                                        <small>
                                                            <i class="fas fa-clock"></i> 
                                                            {{ $analisis->fecha_analisis->format('d/m/Y H:i') }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Todas las Fotos de la Limpieza -->
            @if($fotos->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-camera"></i> Todas las Fotos
                            <span class="badge bg-info ms-2">{{ $fotos->count() }} fotos</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($fotos as $foto)
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <img src="{{ asset($foto->photo_url) }}" 
                                             alt="Foto de limpieza" 
                                             class="card-img-top foto-limpieza" 
                                             style="height: 150px; object-fit: cover; cursor: pointer;"
                                             onclick="ampliarFoto('{{ asset($foto->photo_url) }}', '{{ $foto->photo_url }}')">
                                        <div class="card-body text-center">
                                            <small class="text-muted">
                                                @if($analisisFotos->where('image_url', $foto->photo_url)->first())
                                                    <i class="fas fa-microscope text-success"></i> Con análisis
                                                @else
                                                    <i class="fas fa-camera text-warning"></i> Sin análisis
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal para Ampliar Fotos -->
<div class="modal fade" id="modalAmpliarFoto" tabindex="-1" aria-labelledby="modalAmpliarFotoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAmpliarFotoLabel">
                    <i class="fas fa-search-plus"></i> Ampliar Foto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="fotoAmpliada" src="" alt="Foto ampliada" class="img-fluid" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a id="descargarFoto" href="" download class="btn btn-primary">
                    <i class="fas fa-download"></i> Descargar
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

.progress {
    background-color: #e9ecef;
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
}

.badge {
    font-size: 0.8em;
}

.table td {
    border: none;
    padding: 0.5rem 0;
}

.border-success {
    border-color: #28a745 !important;
}

    .border-danger {
        border-color: #dc3545 !important;
    }

    .foto-limpieza:hover {
        transform: scale(1.05);
        transition: transform 0.2s ease;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .foto-analisis:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
</style>

<script>
function ampliarFoto(url, nombre) {
    // Mostrar la foto en el modal
    document.getElementById('fotoAmpliada').src = url;
    
    // Configurar el enlace de descarga
    document.getElementById('descargarFoto').href = url;
    document.getElementById('descargarFoto').download = nombre.split('/').pop();
    
    // Mostrar el modal usando Bootstrap 5
    try {
        const modalElement = document.getElementById('modalAmpliarFoto');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            console.error('Modal no encontrado');
        }
    } catch (error) {
        console.error('Error al mostrar modal:', error);
        // Fallback: mostrar imagen en nueva ventana
        window.open(url, '_blank');
    }
}
</script>
@endsection

@section('styles')
<style>
/* Estilos para zonas comunes en vista de detalle */
.zona-comun-badge {
    background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%) !important;
    color: white !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3) !important;
    font-weight: 500 !important;
    padding: 8px 12px !important;
    border-radius: 20px !important;
    transition: all 0.3s ease !important;
}

.zona-comun-badge:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4) !important;
}

.text-purple {
    color: #8B5CF6 !important;
}

.bg-purple {
    background-color: #8B5CF6 !important;
}
</style>
@endsection
