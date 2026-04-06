@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        Nueva Configuración de Descuento
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('configuracion-descuentos.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Volver
                        </a>
                    </div>
                </div>
                <form action="{{ route('configuracion-descuentos.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre de la Configuración *</label>
                                    <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                           id="nombre" name="nombre" value="{{ old('nombre') }}" 
                                           placeholder="Ej: Descuento Temporada Baja" required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edificio_id">Edificio *</label>
                                    <select class="form-control @error('edificio_id') is-invalid @enderror" 
                                            id="edificio_id" name="edificio_id" required>
                                        <option value="">Seleccionar edificio</option>
                                        @foreach($edificios as $edificio)
                                            <option value="{{ $edificio->id }}" {{ old('edificio_id') == $edificio->id ? 'selected' : '' }}>
                                                {{ $edificio->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('edificio_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="porcentaje_descuento">Porcentaje de Descuento *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('porcentaje_descuento') is-invalid @enderror" 
                                               id="porcentaje_descuento" name="porcentaje_descuento" 
                                               value="{{ old('porcentaje_descuento', 20) }}" 
                                               min="0" max="100" step="0.01" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @error('porcentaje_descuento')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Descuento cuando la ocupación es menor al mínimo.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="porcentaje_incremento">Porcentaje de Incremento *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('porcentaje_incremento') is-invalid @enderror" 
                                               id="porcentaje_incremento" name="porcentaje_incremento" 
                                               value="{{ old('porcentaje_incremento', 15) }}" 
                                               min="0" max="100" step="0.01" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @error('porcentaje_incremento')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Incremento cuando la ocupación supera el máximo.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" name="descripcion" rows="3" 
                                      placeholder="Describe las condiciones y criterios para aplicar este descuento...">{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="activo" value="0">
                                <input type="checkbox" class="custom-control-input" id="activo" name="activo" value="1"
                                       {{ old('activo', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="activo">
                                    Configuración Activa
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Solo las configuraciones activas se utilizarán para aplicar descuentos automáticos.
                            </small>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cogs mr-2"></i>
                                    Condiciones Avanzadas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="dia_semana">Día de la Semana</label>
                                            <select class="form-control" id="dia_semana" name="condiciones[dia_semana]">
                                                <option value="monday" {{ old('condiciones.dia_semana', 'friday') == 'monday' ? 'selected' : '' }}>Lunes</option>
                                                <option value="tuesday" {{ old('condiciones.dia_semana', 'friday') == 'tuesday' ? 'selected' : '' }}>Martes</option>
                                                <option value="wednesday" {{ old('condiciones.dia_semana', 'friday') == 'wednesday' ? 'selected' : '' }}>Miércoles</option>
                                                <option value="thursday" {{ old('condiciones.dia_semana', 'friday') == 'thursday' ? 'selected' : '' }}>Jueves</option>
                                                <option value="friday" {{ old('condiciones.dia_semana', 'friday') == 'friday' ? 'selected' : '' }}>Viernes</option>
                                                <option value="saturday" {{ old('condiciones.dia_semana', 'friday') == 'saturday' ? 'selected' : '' }}>Sábado</option>
                                                <option value="sunday" {{ old('condiciones.dia_semana', 'friday') == 'sunday' ? 'selected' : '' }}>Domingo</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="temporada">Temporada</label>
                                            <select class="form-control" id="temporada" name="condiciones[temporada]">
                                                <option value="baja" {{ old('condiciones.temporada', 'baja') == 'baja' ? 'selected' : '' }}>Temporada Baja</option>
                                                <option value="alta" {{ old('condiciones.temporada', 'baja') == 'alta' ? 'selected' : '' }}>Temporada Alta</option>
                                                <option value="media" {{ old('condiciones.temporada', 'baja') == 'media' ? 'selected' : '' }}>Temporada Media</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ocupacion_minima">Ocupación Mínima (%)</label>
                                            <input type="number" class="form-control" id="ocupacion_minima" 
                                                   name="condiciones[ocupacion_minima]" 
                                                   value="{{ old('condiciones.ocupacion_minima', 60) }}" 
                                                   min="0" max="100">
                                            <small class="form-text text-muted">Si baja de este % → Descuento</small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ocupacion_maxima">Ocupación Máxima (%)</label>
                                            <input type="number" class="form-control" id="ocupacion_maxima" 
                                                   name="condiciones[ocupacion_maxima]" 
                                                   value="{{ old('condiciones.ocupacion_maxima', 80) }}" 
                                                   min="0" max="100">
                                            <small class="form-text text-muted">Si supera este % → Incremento</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('configuracion-descuentos.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times mr-1"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>
                                Guardar Configuración
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Preview del descuento
    $('#porcentaje_descuento').on('input', function() {
        const porcentaje = parseFloat($(this).val()) || 0;
        const precioEjemplo = 100;
        const precioConDescuento = precioEjemplo * (1 - porcentaje / 100);
        
        // Actualizar ejemplo en tiempo real
        $(this).closest('.form-group').find('.form-text').html(`
            Descuento cuando la ocupación es menor al mínimo.<br>
            <strong>Ejemplo:</strong> Precio de 100€ con ${porcentaje}% de descuento = ${precioConDescuento.toFixed(2)}€
        `);
    });

    // Preview del incremento
    $('#porcentaje_incremento').on('input', function() {
        const porcentaje = parseFloat($(this).val()) || 0;
        const precioEjemplo = 100;
        const precioConIncremento = precioEjemplo * (1 + porcentaje / 100);
        
        // Actualizar ejemplo en tiempo real
        $(this).closest('.form-group').find('.form-text').html(`
            Incremento cuando la ocupación supera el máximo.<br>
            <strong>Ejemplo:</strong> Precio de 100€ con ${porcentaje}% de incremento = ${precioConIncremento.toFixed(2)}€
        `);
    });

    // Trigger inicial
    $('#porcentaje_descuento').trigger('input');
    $('#porcentaje_incremento').trigger('input');
});
</script>
@endpush
