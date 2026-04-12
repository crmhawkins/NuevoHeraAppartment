@extends('layouts.appPersonal')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header con información básica -->
            <div class="apple-card">
                <div class="apple-card-header">
                    <div class="apple-card-title">
                        <i class="bi bi-check-circle me-3"></i>
                        <span>Limpieza Completada - Vista de Información</span>
                    </div>
                    <div class="apple-card-actions">
                        <a href="{{ route($back_route ?? 'gestion.index') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-2"></i>
                            {{ $back_label ?? 'Volver a Gestión' }}
                        </a>
                    </div>
                </div>
                <div class="apple-card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-section">
                                <h5 class="info-title">
                                    <i class="bi bi-building me-3"></i>
                                    {{ $apartamentoLimpieza->apartamento ? $apartamentoLimpieza->apartamento->nombre : ($apartamentoLimpieza->zonaComun ? $apartamentoLimpieza->zonaComun->nombre : 'Elemento no encontrado') }}
                                </h5>
                                <div class="info-item">
                                    <span class="info-label">ID Limpieza:</span>
                                    <span class="info-value">{{ $apartamentoLimpieza->id }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Estado:</span>
                                    <span class="status-badge">{{ $apartamentoLimpieza->estado ? $apartamentoLimpieza->estado->nombre : 'Completado' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-section">
                                <div class="info-item">
                                    <span class="info-label">Fecha Comienzo:</span>
                                    <span class="info-value">{{ $apartamentoLimpieza->fecha_comienzo ? \Carbon\Carbon::parse($apartamentoLimpieza->fecha_comienzo)->format('d/m/Y H:i') : 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Fecha Finalización:</span>
                                    <span class="info-value">{{ $apartamentoLimpieza->fecha_fin ? \Carbon\Carbon::parse($apartamentoLimpieza->fecha_fin)->format('d/m/Y H:i') : 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Limpieza realizada por:</span>
                                    <span class="info-value">{{ $apartamentoLimpieza->empleada ? $apartamentoLimpieza->empleada->name : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de la reserva si existe -->
            @if($apartamentoLimpieza->reserva)
            <div class="apple-card">
                <div class="apple-card-header">
                    <div class="apple-card-title">
                        <i class="bi bi-calendar-check me-3"></i>
                        <span>Información de la Reserva</span>
                    </div>
                </div>
                <div class="apple-card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-section">
                                <div class="info-item">
                                    <span class="info-label">Fecha Entrada:</span>
                                    <span class="info-value">{{ $apartamentoLimpieza->reserva->fecha_entrada }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Fecha Salida:</span>
                                    <span class="info-value">{{ $apartamentoLimpieza->reserva->fecha_salida }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Número de Personas:</span>
                                    <span class="info-value">{{ $apartamentoLimpieza->reserva->numero_personas }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            @if($apartamentoLimpieza->reserva->numero_ninos > 0)
                                <div class="info-section">
                                    <div class="info-item">
                                        <span class="info-label">Niños:</span>
                                        <span class="info-value">{{ $apartamentoLimpieza->reserva->numero_ninos }}</span>
                                    </div>
                                    @if($apartamentoLimpieza->reserva->edades_ninos)
                                        <div class="info-item">
                                            <span class="info-label">Edades:</span>
                                            <span class="info-value">
                                                @foreach($apartamentoLimpieza->reserva->edades_ninos as $edad)
                                                    @if($edad <= 2)
                                                        bebé ({{$edad}} años)
                                                    @elseif($edad <= 12)
                                                        niño ({{$edad}} años)
                                                    @else
                                                        adolescente ({{$edad}} años)
                                                    @endif
                                                    @if(!$loop->last), @endif
                                                @endforeach
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif


            <!-- Checklist completado -->
            @if($checklists->count() > 0)
            <div class="apple-card">
                <div class="apple-card-header">
                    <div class="apple-card-title">
                        <i class="bi bi-list-check me-3"></i>
                        <span>Checklist de Limpieza</span>
                    </div>
                </div>
                <div class="apple-card-body">
                    @foreach($checklists as $checklist)
                        <div class="checklist-section mb-4">
                            <h6 class="checklist-title">{{ $checklist->nombre }}</h6>
                            @if($checklist->items)
                                <div class="row g-3">
                                    @foreach($checklist->items as $item)
                                        @php
                                            $itemCompletado = $itemsExistentes->where('item_id', $item->id)->first();
                                        @endphp
                                        <div class="col-md-6">
                                            <div class="checklist-item">
                                                <div class="item-icon">
                                                    <i class="bi bi-check-circle"></i>
                                                </div>
                                                <div class="item-content">
                                                    <span class="item-text">{{ $item->nombre }}</span>
                                                    @if($itemCompletado)
                                                        <span class="completion-badge">Completado</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Fotos de la limpieza organizadas por sección -->
            @if($todasLasFotos && $todasLasFotos->count() > 0)
            <div class="apple-card">
                <div class="apple-card-header">
                    <div class="apple-card-title">
                        <i class="bi bi-camera me-3"></i>
                        <span>Fotos de la Limpieza ({{ $todasLasFotos->count() }})</span>
                    </div>
                </div>
                <div class="apple-card-body">
                    <!-- Agrupar fotos por categoría -->
                    @php
                        $fotosPorCategoria = $todasLasFotos->groupBy(function($foto) {
                            return $foto->photo_cat ?? $foto->photo_categoria_id ?? 'sin_categoria';
                        });
                    @endphp
                    
                    @foreach($fotosPorCategoria as $categoria => $fotos)
                        <div class="photo-category mb-5">
                            <h6 class="category-title">
                                <i class="bi bi-folder me-2"></i>
                                {{ ucfirst($categoria ?: 'Sin categoría') }} ({{ $fotos->count() }} fotos)
                            </h6>
                            <div class="row g-4">
                                @foreach($fotos as $foto)
                                    <div class="col-md-4 col-sm-6">
                                        <div class="photo-card" onclick="abrirModalFoto('{{ asset($foto->url) }}', '{{ $foto->descripcion ?? 'Sin descripción' }}')">
                                            <div class="photo-image">
                                                <img src="{{ asset($foto->url) }}"
                                                     alt="Foto de limpieza - {{ $categoria }}"
                                                     class="img-fluid"
                                                     loading="lazy">
                                                <div class="photo-overlay">
                                                    <i class="bi bi-zoom-in"></i>
                                                </div>
                                            </div>
                                            <div class="photo-info">
                                                <p class="photo-description">{{ $foto->descripcion ?? 'Sin descripción' }}</p>
                                                <small class="photo-date">{{ \Carbon\Carbon::parse($foto->created_at)->format('d/m/Y H:i') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @else
            <!-- Mensaje si no hay fotos -->
            <div class="apple-card">
                <div class="apple-card-header">
                    <div class="apple-card-title">
                        <i class="bi bi-camera me-3"></i>
                        <span>Fotos de la Limpieza</span>
                    </div>
                </div>
                <div class="apple-card-body">
                    <div class="no-photos">
                        <p class="text-muted text-center">
                            <i class="bi bi-info-circle me-2"></i>
                            No hay fotos disponibles para esta limpieza
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Notas adicionales -->
            @if($apartamentoLimpieza->notas)
            <div class="apple-card">
                <div class="apple-card-header">
                    <div class="apple-card-title">
                        <i class="bi bi-sticky me-3"></i>
                        <span>Notas de la Limpieza</span>
                    </div>
                </div>
                <div class="apple-card-body">
                    <div class="notes-content">
                        <p class="notes-text">{{ $apartamentoLimpieza->notas }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal para ver fotos en grande -->
<div class="modal fade" id="modalFoto" tabindex="-1" aria-labelledby="modalFotoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFotoLabel">Foto de Limpieza</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="fotoModal" src="" class="img-fluid" alt="Foto en grande" loading="lazy">
                <p id="descripcionModal" class="mt-3 text-muted"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos modernos para las tarjetas */
.apple-card {
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: 24px;
    transition: all 0.3s ease;
}

.apple-card:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.apple-card-header {
    background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
    padding: 24px 32px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    /* QUITADO EL HOVER DEL HEADER */
}

.apple-card-title {
    display: flex;
    align-items: center;
    color: white;
    font-size: 20px;
    font-weight: 600;
    margin: 0;
}

.apple-card-title i {
    font-size: 24px;
    margin-right: 16px;
    color: white !important; /* FORZAR COLOR BLANCO */
}

.apple-card-actions {
    margin-left: auto;
}

.apple-card-body {
    padding: 32px;
    background: #FFFFFF;
}

/* Estilos para secciones de información */
.info-section {
    padding: 20px 0;
}

.info-title {
    color: #1D1D1F;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #F2F2F7;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #6E6E73;
    font-size: 14px;
}

.info-value {
    font-weight: 600;
    color: #1D1D1F;
    font-size: 14px;
}

.status-badge {
    background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}


/* Estilos para mensaje de no fotos */
.no-photos {
    padding: 40px 20px;
    text-align: center;
}

.no-photos p {
    color: #8E8E93;
    font-size: 16px;
    margin: 0;
}

.no-photos i {
    color: #007AFF;
    font-size: 18px;
}

/* Estilos para checklist */
.checklist-section {
    padding: 20px 0;
}

.checklist-title {
    color: #007AFF;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #F2F2F7;
}

.checklist-item {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    background: #F8F9FA;
    border-radius: 12px;
    border: 1px solid #E9ECEF;
    transition: all 0.3s ease;
}

.checklist-item:hover {
    background: #E9ECEF;
    transform: translateX(4px);
}

.item-icon {
    margin-right: 16px;
}

.item-icon i {
    color: #34C759;
    font-size: 18px;
}

.item-content {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.item-text {
    color: #1D1D1F;
    font-size: 14px;
    font-weight: 500;
}

.completion-badge {
    background: #34C759;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

/* Estilos para categorías de fotos */
.photo-category {
    padding: 20px 0;
}

.category-title {
    color: #FF9500;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 2px solid #F2F2F7;
    display: flex;
    align-items: center;
}

/* Estilos para las tarjetas de fotos */
.photo-card {
    border: 1px solid #E9ECEF;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    cursor: pointer;
    background: white;
}

.photo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    border-color: #007AFF;
}

.photo-image {
    position: relative;
    overflow: hidden;
}

.photo-image img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.photo-card:hover .photo-image img {
    transform: scale(1.08);
}

.photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 122, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.photo-overlay i {
    color: white;
    font-size: 2.5rem;
}

.photo-card:hover .photo-overlay {
    opacity: 1;
}

.photo-info {
    padding: 20px;
    background: white;
}

.photo-description {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #1D1D1F;
    font-weight: 500;
    line-height: 1.4;
}

.photo-date {
    color: #8E8E93;
    font-size: 12px;
    font-weight: 500;
}

/* Estilos para notas */
.notes-content {
    padding: 20px 0;
}

.notes-text {
    color: #1D1D1F;
    font-size: 16px;
    line-height: 1.6;
    margin: 0;
    padding: 24px;
    background: #F8F9FA;
    border-radius: 12px;
    border-left: 4px solid #007AFF;
}

/* Responsive */
@media (max-width: 768px) {
    .apple-card-body {
        padding: 24px 20px;
    }
    
    .apple-card-header {
        padding: 20px 24px;
    }
    
    .photo-image img {
        height: 180px;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .checklist-item {
        padding: 12px 16px;
    }
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.apple-card {
    animation: fadeInUp 0.4s ease-out;
}

.apple-card:nth-child(2) { animation-delay: 0.1s; }
.apple-card:nth-child(3) { animation-delay: 0.2s; }
.apple-card:nth-child(4) { animation-delay: 0.3s; }
.apple-card:nth-child(5) { animation-delay: 0.4s; }
</style>

<script>
function abrirModalFoto(ruta, descripcion) {
    document.getElementById('fotoModal').src = ruta;
    document.getElementById('descripcionModal').textContent = descripcion;
    new bootstrap.Modal(document.getElementById('modalFoto')).show();
}
</script>
@endsection
