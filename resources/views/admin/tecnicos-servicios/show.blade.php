@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 text-dark fw-bold">
                <i class="fas fa-user-cog me-2 text-primary"></i>
                Servicios de {{ $tecnico->nombre }}
            </h1>
            <p class="text-muted mb-0">Asigna servicios y establece precios para este técnico</p>
        </div>
        <a href="{{ route('admin.tecnicos-servicios.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <hr class="mb-4">

    @if(session('swal_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('swal_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('swal_error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('swal_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Información del técnico -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Información del Técnico
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Nombre:</strong> {{ $tecnico->nombre }}
                </div>
                <div class="col-md-3">
                    <strong>Teléfono:</strong> {{ $tecnico->telefono ?: '—' }}
                </div>
                <div class="col-md-3">
                    <strong>Email:</strong> {{ $tecnico->email ?: '—' }}
                </div>
                <div class="col-md-3">
                    <strong>Estado:</strong> 
                    @if($tecnico->activo)
                        <span class="badge bg-success">Activo</span>
                    @else
                        <span class="badge bg-secondary">Inactivo</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de servicios -->
    <form action="{{ route('admin.tecnicos-servicios.store', $tecnico->id) }}" method="POST" id="formServicios">
        @csrf
        
        @if($categorias->isEmpty())
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No hay servicios técnicos disponibles. <a href="{{ route('admin.servicios-tecnicos.index') }}">Crea servicios primero</a>
            </div>
        @else
            @foreach($categorias as $categoria)
                @php
                    $serviciosCategoria = $servicios->where('categoria_id', $categoria->id);
                @endphp
                
                @if($serviciosCategoria->isNotEmpty())
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                @if($categoria->icono)
                                    <span class="me-2">{!! $categoria->icono !!}</span>
                                @endif
                                {{ $categoria->nombre }}
                                <span class="badge bg-light text-dark ms-2">{{ $serviciosCategoria->count() }}</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" class="form-check-input categoria-checkbox" 
                                                       data-categoria="{{ $categoria->id }}">
                                            </th>
                                            <th>Servicio</th>
                                            <th>Unidad</th>
                                            <th>Precio Base</th>
                                            <th style="width: 200px;">Precio Técnico (€)</th>
                                            <th style="width: 250px;">Observaciones</th>
                                            <th style="width: 100px;">Activo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($serviciosCategoria as $servicio)
                                            <tr class="servicio-row" data-categoria="{{ $categoria->id }}">
                                                <td>
                                                    <input type="checkbox" class="form-check-input servicio-checkbox" 
                                                           data-servicio="{{ $servicio->id }}"
                                                           {{ $servicio->tiene_precio ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <strong>{{ $servicio->nombre }}</strong>
                                                    @if($servicio->descripcion)
                                                        <br><small class="text-muted">{{ Str::limit($servicio->descripcion, 50) }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $servicio->unidad_medida ?: '—' }}</span>
                                                </td>
                                                <td>
                                                    @if($servicio->precio_base)
                                                        <span class="text-muted">{{ number_format($servicio->precio_base, 2, ',', '.') }} €</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="hidden" name="servicios[{{ $servicio->id }}][servicio_id]" 
                                                           value="{{ $servicio->id }}">
                                                    <input type="number" 
                                                           step="0.01" 
                                                           min="0" 
                                                           max="999999.99"
                                                           class="form-control form-control-sm precio-input" 
                                                           name="servicios[{{ $servicio->id }}][precio]" 
                                                           value="{{ $servicio->precio_asignado ?? $servicio->precio_base ?? '' }}"
                                                           placeholder="0.00"
                                                           {{ $servicio->tiene_precio ? '' : 'disabled' }}
                                                           required>
                                                </td>
                                                <td>
                                                    <textarea class="form-control form-control-sm" 
                                                              name="servicios[{{ $servicio->id }}][observaciones]" 
                                                              rows="2"
                                                              placeholder="Observaciones..."
                                                              {{ $servicio->tiene_precio ? '' : 'disabled' }}>{{ $servicio->observaciones_asignadas ?? '' }}</textarea>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input activo-switch" 
                                                               type="checkbox" 
                                                               name="servicios[{{ $servicio->id }}][activo]" 
                                                               value="1"
                                                               {{ ($servicio->activo_asignado ?? true) ? 'checked' : '' }}
                                                               {{ $servicio->tiene_precio ? '' : 'disabled' }}>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            <!-- Servicios sin categoría -->
            @php
                $serviciosSinCategoria = $servicios->whereNull('categoria_id');
            @endphp
            @if($serviciosSinCategoria->isNotEmpty())
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-folder-open me-2"></i>
                            Sin categoría
                            <span class="badge bg-light text-dark ms-2">{{ $serviciosSinCategoria->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" class="form-check-input categoria-checkbox" 
                                                   data-categoria="sin-categoria">
                                        </th>
                                        <th>Servicio</th>
                                        <th>Unidad</th>
                                        <th>Precio Base</th>
                                        <th style="width: 200px;">Precio Técnico (€)</th>
                                        <th style="width: 250px;">Observaciones</th>
                                        <th style="width: 100px;">Activo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($serviciosSinCategoria as $servicio)
                                        <tr class="servicio-row" data-categoria="sin-categoria">
                                            <td>
                                                <input type="checkbox" class="form-check-input servicio-checkbox" 
                                                       data-servicio="{{ $servicio->id }}"
                                                       {{ $servicio->tiene_precio ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <strong>{{ $servicio->nombre }}</strong>
                                                @if($servicio->descripcion)
                                                    <br><small class="text-muted">{{ Str::limit($servicio->descripcion, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $servicio->unidad_medida ?: '—' }}</span>
                                            </td>
                                            <td>
                                                @if($servicio->precio_base)
                                                    <span class="text-muted">{{ number_format($servicio->precio_base, 2, ',', '.') }} €</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="hidden" name="servicios[{{ $servicio->id }}][servicio_id]" 
                                                       value="{{ $servicio->id }}">
                                                <input type="number" 
                                                       step="0.01" 
                                                       min="0" 
                                                       max="999999.99"
                                                       class="form-control form-control-sm precio-input" 
                                                       name="servicios[{{ $servicio->id }}][precio]" 
                                                       value="{{ $servicio->precio_asignado ?? $servicio->precio_base ?? '' }}"
                                                       placeholder="0.00"
                                                       {{ $servicio->tiene_precio ? '' : 'disabled' }}
                                                       required>
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm" 
                                                          name="servicios[{{ $servicio->id }}][observaciones]" 
                                                          rows="2"
                                                          placeholder="Observaciones..."
                                                          {{ $servicio->tiene_precio ? '' : 'disabled' }}>{{ $servicio->observaciones_asignadas ?? '' }}</textarea>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input activo-switch" 
                                                           type="checkbox" 
                                                           name="servicios[{{ $servicio->id }}][activo]" 
                                                           value="1"
                                                           {{ ($servicio->activo_asignado ?? true) ? 'checked' : '' }}
                                                           {{ $servicio->tiene_precio ? '' : 'disabled' }}>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-end mb-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>
                    Guardar Precios
                </button>
            </div>
        @endif
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Checkbox de categoría - seleccionar/deseleccionar todos los servicios de la categoría
    document.querySelectorAll('.categoria-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const categoriaId = this.getAttribute('data-categoria');
            const servicios = document.querySelectorAll(`.servicio-row[data-categoria="${categoriaId}"]`);
            const isChecked = this.checked;
            
            servicios.forEach(function(row) {
                const servicioCheckbox = row.querySelector('.servicio-checkbox');
                servicioCheckbox.checked = isChecked;
                toggleServicioInputs(servicioCheckbox);
            });
        });
    });

    // Checkbox de servicio individual
    document.querySelectorAll('.servicio-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            toggleServicioInputs(this);
        });
    });

    function toggleServicioInputs(checkbox) {
        const row = checkbox.closest('tr');
        const inputs = row.querySelectorAll('.precio-input, textarea, .activo-switch');
        const isChecked = checkbox.checked;
        
        inputs.forEach(function(input) {
            if (isChecked) {
                input.removeAttribute('disabled');
                if (input.classList.contains('precio-input') && !input.value && input.closest('td').previousElementSibling.querySelector('.text-muted')) {
                    // Copiar precio base si existe
                    const precioBaseText = input.closest('td').previousElementSibling.querySelector('.text-muted')?.textContent;
                    if (precioBaseText) {
                        const precioBase = parseFloat(precioBaseText.replace(/[^\d,.-]/g, '').replace(',', '.'));
                        if (!isNaN(precioBase)) {
                            input.value = precioBase.toFixed(2);
                        }
                    }
                }
            } else {
                input.setAttribute('disabled', 'disabled');
            }
        });
    }

    // Validación del formulario
    document.getElementById('formServicios').addEventListener('submit', function(e) {
        const checkedServicios = document.querySelectorAll('.servicio-checkbox:checked');
        if (checkedServicios.length === 0) {
            e.preventDefault();
            alert('Debes seleccionar al menos un servicio');
            return false;
        }

        let hasError = false;
        checkedServicios.forEach(function(checkbox) {
            const row = checkbox.closest('tr');
            const precioInput = row.querySelector('.precio-input');
            if (!precioInput.value || parseFloat(precioInput.value) <= 0) {
                hasError = true;
                precioInput.classList.add('is-invalid');
            } else {
                precioInput.classList.remove('is-invalid');
            }
        });

        if (hasError) {
            e.preventDefault();
            alert('Todos los servicios seleccionados deben tener un precio válido');
            return false;
        }
    });
});
</script>
@endsection

