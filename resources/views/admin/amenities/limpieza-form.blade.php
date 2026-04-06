@if(count($amenitiesConRecomendaciones) > 0)
    <div class="amenities-form">
        <form action="{{ route('amenity.limpieza.store', $limpieza->id) }}" method="POST" id="amenityForm">
            @csrf
            
            @foreach($amenitiesConRecomendaciones as $categoria => $amenities)
            <div class="amenity-category mb-4">
                <h6 class="category-title mb-3">
                    <i class="fas fa-tags me-2 text-{{ $categoria == 'higiene' ? 'success' : ($categoria == 'alimentacion' ? 'warning' : 'primary') }}"></i>
                    {{ ucfirst($categoria) }}
                </h6>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Amenity</th>
                                <th>Tipo Consumo</th>
                                <th>Cantidad Recomendada</th>
                                <th>Stock Disponible</th>
                                <th>Cantidad Dejada</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($amenities as $item)
                            @php
                                $amenity = $item['amenity'];
                                $cantidadRecomendada = $item['cantidad_recomendada'];
                                $consumoExistente = $item['consumo_existente'];
                                $stockDisponible = $item['stock_disponible'];
                                
                                // Obtener cantidad actual (dejada anteriormente)
                                $cantidadActual = $consumoExistente ? $consumoExistente->cantidad_actual : $cantidadRecomendada;
                            @endphp
                            <tr>
                                <td>
                                    <div class="amenity-info">
                                        <strong>{{ $amenity->nombre }}</strong>
                                        <small class="text-muted d-block">{{ $amenity->descripcion }}</small>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $tipoConsumo = '';
                                        switch($amenity->tipo_consumo) {
                                            case 'por_reserva':
                                                $tipoConsumo = 'Por Reserva';
                                                break;
                                            case 'por_persona':
                                                $tipoConsumo = 'Por Persona';
                                                break;
                                            case 'por_tiempo':
                                                $tipoConsumo = 'Por Tiempo';
                                                break;
                                            default:
                                                $tipoConsumo = 'N/A';
                                        }
                                    @endphp
                                    <span class="badge bg-info">{{ $tipoConsumo }}</span>
                                    @if($amenity->tipo_consumo == 'por_persona')
                                        <small class="d-block text-muted">{{ $amenity->consumo_minimo }}-{{ $amenity->consumo_maximo }} por persona</small>
                                    @elseif($amenity->tipo_consumo == 'por_tiempo')
                                        <small class="d-block text-muted">Cada {{ $amenity->duracion_dias }} días</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-success">{{ $cantidadRecomendada }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $stockDisponible > 0 ? 'primary' : 'danger' }}">
                                        {{ $stockDisponible }}
                                    </span>
                                </td>
                                <td>
                                    <input type="number" 
                                           name="amenities[{{ $amenity->id }}][cantidad_dejada]" 
                                           value="{{ $cantidadActual }}"
                                           min="0" 
                                           max="{{ $stockDisponible }}"
                                           class="form-control form-control-sm cantidad-input"
                                           data-amenity-id="{{ $amenity->id }}"
                                           data-stock="{{ $stockDisponible }}"
                                           required>
                                    <input type="hidden" name="amenities[{{ $amenity->id }}][amenity_id]" value="{{ $amenity->id }}">
                                </td>
                                <td>
                                    <textarea name="amenities[{{ $amenity->id }}][observaciones]" 
                                              class="form-control form-control-sm" 
                                              rows="2" 
                                              placeholder="Observaciones...">{{ $consumoExistente ? $consumoExistente->observaciones : '' }}</textarea>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
            
            <div class="amenities-actions mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Guardar Amenities
                </button>
            </div>
        </form>
    </div>
@else
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No hay amenities configurados para este edificio.
    </div>
@endif

<style>
.amenity-category {
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    padding: 20px;
    background: #FFFFFF;
}

.category-title {
    color: #1D1D1F;
    font-weight: 600;
    border-bottom: 2px solid #F2F2F7;
    padding-bottom: 10px;
}

.amenity-info strong {
    color: #1D1D1F;
    font-size: 14px;
}

.amenity-info small {
    font-size: 12px;
}

.cantidad-input {
    width: 80px;
    text-align: center;
}

.amenities-actions {
    text-align: center;
    padding: 20px;
    background: #F8F9FA;
    border-radius: 12px;
}

.table th {
    font-weight: 600;
    color: #1D1D1F;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
    font-size: 14px;
}

.badge {
    font-size: 11px;
    padding: 6px 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación en tiempo real de cantidades
    const cantidadInputs = document.querySelectorAll('.cantidad-input');
    
    cantidadInputs.forEach(input => {
        input.addEventListener('input', function() {
            const stock = parseInt(this.dataset.stock);
            const valor = parseInt(this.value);
            
            if (valor > stock) {
                this.value = stock;
                this.classList.add('is-invalid');
            } else if (valor < 0) {
                this.value = 0;
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Manejo del formulario
    const form = document.getElementById('amenityForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar que todas las cantidades sean válidas
            let isValid = true;
            cantidadInputs.forEach(input => {
                const stock = parseInt(input.dataset.stock);
                const valor = parseInt(input.value);
                
                if (valor < 0 || valor > stock) {
                    isValid = false;
                    input.classList.add('is-invalid');
                }
            });
            
            if (!isValid) {
                alert('Por favor, corrige las cantidades inválidas antes de continuar.');
                return;
            }
            
            // Enviar formulario
            this.submit();
        });
    }
});
</script>
