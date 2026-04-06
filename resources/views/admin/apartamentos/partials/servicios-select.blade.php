<!-- Servicios del Apartamento -->
<div class="mb-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0 fw-semibold">
                <i class="fas fa-concierge-bell me-2"></i>
                Servicios del Apartamento
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Servicios:</strong> Selecciona los servicios que tiene este apartamento. 
                Puedes gestionar los servicios disponibles desde <a href="{{ route('admin.servicios.index') }}" target="_blank" class="alert-link">Servicios</a>.
            </div>

            @php
                $serviciosPopulares = $servicios->where('es_popular', true);
                $serviciosOtros = $servicios->where('es_popular', false);
            @endphp

            @if($servicios->isEmpty())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No hay servicios disponibles. 
                    <a href="{{ route('admin.servicios.create') }}" target="_blank" class="alert-link">Crea el primero</a>.
                </div>
            @else
                <!-- Servicios Populares -->
                @if($serviciosPopulares->isNotEmpty())
                    <div class="mb-4">
                        <h6 class="fw-semibold text-primary mb-3">
                            <i class="fas fa-star me-2"></i>Servicios Populares
                        </h6>
                        <div class="row">
                            @foreach($serviciosPopulares as $servicio)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="servicios[]" 
                                               value="{{ $servicio->id }}" 
                                               id="servicio_{{ $servicio->id }}"
                                               {{ in_array($servicio->id, old('servicios', $serviciosSeleccionados ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label d-flex align-items-center" for="servicio_{{ $servicio->id }}">
                                            @if($servicio->icono)
                                                <span class="me-2" style="font-size: 16px;">{!! $servicio->icono !!}</span>
                                            @endif
                                            <span>{{ $servicio->nombre }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <hr>
                @endif

                <!-- Otros Servicios -->
                @if($serviciosOtros->isNotEmpty())
                    <div class="mb-3">
                        <h6 class="fw-semibold text-dark mb-3">
                            <i class="fas fa-list me-2"></i>Otros Servicios
                        </h6>
                        <div class="row">
                            @foreach($serviciosOtros as $servicio)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="servicios[]" 
                                               value="{{ $servicio->id }}" 
                                               id="servicio_{{ $servicio->id }}"
                                               {{ in_array($servicio->id, old('servicios', $serviciosSeleccionados ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label d-flex align-items-center" for="servicio_{{ $servicio->id }}">
                                            @if($servicio->icono)
                                                <span class="me-2" style="font-size: 16px;">{!! $servicio->icono !!}</span>
                                            @endif
                                            <span>{{ $servicio->nombre }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Botón para gestionar servicios -->
                <div class="mt-3">
                    <a href="{{ route('admin.servicios.index') }}" 
                       target="_blank" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-cog me-2"></i>
                        Gestionar Servicios
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>




