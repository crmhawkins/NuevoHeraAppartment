@extends('layouts.appAdmin')

@section('title')
    Gestión de Amenities - Limpieza
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-pump-soap me-2 text-primary"></i>
                        Gestión de Amenities - Limpieza
                    </h1>
                    <p class="text-muted mb-0">
                        Apartamento: <strong>{{ $apartamento->nombre }}</strong> | 
                        @if($reserva)
                            Reserva: <strong>{{ $reserva->codigo_reserva ?? 'N/A' }}</strong> | 
                            {{ $reserva->numero_personas ?? 1 }} personas | 
                            {{ Carbon\Carbon::parse($reserva->fecha_entrada)->diffInDays($reserva->fecha_salida) }} días
                        @else
                            Sin reserva activa
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('gestion.create', $limpieza->reserva_id ?? 'no-reserva') }}" class="btn btn-outline-info">
                        <i class="fas fa-eye me-2"></i>Volver a Limpieza
                    </a>
                    <a href="{{ route('apartamentos.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de la Limpieza -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-info"></i>
                        Información de la Limpieza
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Tipo de Limpieza</small>
                            <span class="badge bg-primary px-2 py-1">{{ ucfirst($limpieza->tipo_limpieza ?? 'N/A') }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Estado</small>
                            <span class="badge bg-{{ $limpieza->status_id == 1 ? 'warning' : 'success' }} px-2 py-1">
                                {{ $limpieza->status_id == 1 ? 'En Proceso' : 'Completada' }}
                            </span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Empleada</small>
                            <span class="fw-semibold">{{ $limpieza->empleada->nombre ?? 'No asignada' }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Fecha</small>
                            <span class="fw-semibold">{{ $limpieza->created_at ? $limpieza->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-chart-pie me-2 text-success"></i>
                        Resumen de Amenities
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="mb-1 fw-bold text-primary">{{ count($amenitiesConRecomendaciones) }}</h4>
                                <small class="text-muted">Categorías</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                @php
                                    $totalAmenities = collect($amenitiesConRecomendaciones)->flatten(1)->count();
                                @endphp
                                <h4 class="mb-1 fw-bold text-success">{{ $totalAmenities }}</h4>
                                <small class="text-muted">Amenities</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de Gestión de Amenities -->
    <form action="{{ route('amenity.limpieza.store', $limpieza->id) }}" method="POST" id="amenityForm">
        @csrf
        
        @foreach($amenitiesConRecomendaciones as $categoria => $amenities)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-semibold text-dark">
                            <i class="fas fa-tags me-2 text-{{ $categoria == 'higiene' ? 'success' : ($categoria == 'alimentacion' ? 'warning' : 'primary') }}"></i>
                            {{ ucfirst($categoria) }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%">Amenity</th>
                                        <th style="width: 15%">Tipo Consumo</th>
>
                                        <th style="width: 15%">Recomendado</th>
                                        <th style="width: 15%">Stock Disponible</th>
                                        <th style="width: 15%">Cantidad Dejada</th>
                                        <th style="width: 15%">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($amenities as $amenityData)
                                    @php
                                        $amenity = $amenityData['amenity'];
                                        $cantidadRecomendada = $amenityData['cantidad_recomendada'];
                                        $consumoExistente = $amenityData['consumo_existente'];
                                        $stockDisponible = $amenityData['stock_disponible'];
                                        
                                        // Determinar el color del stock
                                        $stockColor = 'success';
                                        if ($stockDisponible <= $amenity->stock_minimo) {
                                            $stockColor = 'danger';
                                        } elseif ($stockDisponible <= ($amenity->stock_minimo + 2)) {
                                            $stockColor = 'warning';
                                        }
                                        
                                        // Determinar el tipo de consumo
                                        $tipoConsumoText = '';
                                        switch($amenity->tipo_consumo) {
                                            case 'por_reserva':
                                                $tipoConsumoText = 'Por Reserva';
                                                break;
                                            case 'por_tiempo':
                                                $tipoConsumoText = 'Por Tiempo';
                                                break;
                                            case 'por_persona':
                                                $tipoConsumoText = 'Por Persona';
                                                break;
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <i class="fas fa-{{ $categoria == 'higiene' ? 'soap' : ($categoria == 'alimentacion' ? 'utensils' : 'star') }} text-{{ $categoria == 'higiene' ? 'success' : ($categoria == 'alimentacion' ? 'warning' : 'primary') }}"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-semibold">{{ $amenity->nombre }}</h6>
                                                    <small class="text-muted">{{ $amenity->descripcion }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info px-2 py-1">
                                                {{ $tipoConsumoText }}
                                            </span>
                                            @if($amenity->tipo_consumo == 'por_reserva')
                                                <br><small class="text-muted">
                                                    Min: {{ $amenity->consumo_minimo_reserva ?? 'N/A' }} | 
                                                    Max: {{ $amenity->consumo_maximo_reserva ?? 'N/A' }}
                                                </small>
                                            @elseif($amenity->tipo_consumo == 'por_tiempo')
                                                <br><small class="text-muted">
                                                    Duración: {{ $amenity->duracion_dias ?? 'N/A' }} días
                                                </small>
                                            @elseif($amenity->tipo_consumo == 'por_persona')
                                                <br><small class="text-muted">
                                                    {{ $amenity->consumo_por_persona ?? 'N/A' }} {{ $amenity->unidad_consumo ?? 'unidad' }}/persona/día
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <span class="badge bg-success-subtle text-success px-2 py-1 fw-bold">
                                                    {{ $cantidadRecomendada }}
                                                </span>
                                                <br>
                                                <small class="text-muted">{{ $amenity->unidad_medida }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <span class="badge bg-{{ $stockColor }}-subtle text-{{ $stockColor }} px-2 py-1">
                                                    {{ $stockDisponible }}
                                                </span>
                                                <br>
                                                <small class="text-muted">disponible</small>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   class="form-control form-control-sm @error('amenities.'.$loop->index.'.cantidad_dejada') is-invalid @enderror"
                                                   name="amenities[{{ $loop->index }}][cantidad_dejada]"
                                                   value="{{ $consumoExistente ? $consumoExistente->cantidad_actual : $cantidadRecomendada }}"
                                                   min="0" 
                                                   max="{{ $stockDisponible }}"
                                                   required>
                                            <input type="hidden" name="amenities[{{ $loop->index }}][amenity_id]" value="{{ $amenity->id }}">
                                            @error('amenities.'.$loop->index.'.cantidad_dejada')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <textarea class="form-control form-control-sm" 
                                                      name="amenities[{{ $loop->index }}][observaciones]" 
                                                      rows="2" 
                                                      placeholder="Observaciones...">{{ $consumoExistente ? $consumoExistente->observaciones : '' }}</textarea>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        <!-- Botones de Acción -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-save me-2"></i>Guardar Amenities
                        </button>
                        <button type="reset" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-undo me-2"></i>Restablecer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Cambios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres guardar los cambios en los amenities?</p>
                <p class="text-muted small">Esta acción actualizará el stock y registrará el consumo.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmSubmit">Confirmar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // SweetAlert para mensajes de sesión
    @if(session('swal_success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('swal_success') }}',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Aceptar'
        });
    @endif

    @if(session('swal_error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('swal_error') }}',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Aceptar'
        });
    @endif

    // Validación del formulario
    const form = document.getElementById('amenityForm');
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar que no haya cantidades negativas
        let hasErrors = false;
        const cantidadInputs = form.querySelectorAll('input[name*="[cantidad_dejada]"]');
        
        cantidadInputs.forEach(input => {
            if (parseInt(input.value) < 0) {
                input.classList.add('is-invalid');
                hasErrors = true;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (hasErrors) {
            Swal.fire({
                icon: 'error',
                title: 'Error de Validación',
                text: 'Por favor, corrige las cantidades negativas antes de continuar.',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        // Mostrar modal de confirmación
        confirmModal.show();
    });
    
    // Confirmar envío del formulario
    document.getElementById('confirmSubmit').addEventListener('click', function() {
        confirmModal.hide();
        form.submit();
    });
    
    // Validación en tiempo real de cantidades
    const cantidadInputs = form.querySelectorAll('input[name*="[cantidad_dejada]"]');
    cantidadInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);
            const max = parseInt(this.getAttribute('max'));
            
            if (value < 0) {
                this.classList.add('is-invalid');
                this.setCustomValidity('La cantidad no puede ser negativa');
            } else if (value > max) {
                this.classList.add('is-invalid');
                this.setCustomValidity(`La cantidad no puede exceder el stock disponible (${max})`);
            } else {
                this.classList.remove('is-invalid');
                this.setCustomValidity('');
            }
        });
    });
});
</script>
@endsection

<style>
.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    border-radius: 12px 12px 0 0 !important;
}

.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 50%;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e3e6f0;
}

.table td {
    border-bottom: 1px solid #f8f9fa;
    vertical-align: middle;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.form-control {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    transition: all 0.2s ease-in-out;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Responsive */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
}
</style>
