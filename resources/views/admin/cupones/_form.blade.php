<div class="row">
    <!-- Información Básica -->
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información Básica</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Código del Cupón <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" class="form-control @error('codigo') is-invalid @enderror" 
                               value="{{ old('codigo', $cupon->codigo ?? '') }}" 
                               placeholder="Ej: VERANO2024" 
                               style="text-transform: uppercase;" required>
                        <small class="text-muted">Código que los usuarios introducirán (se convertirá a mayúsculas)</small>
                        @error('codigo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre del Cupón <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" 
                               value="{{ old('nombre', $cupon->nombre ?? '') }}" 
                               placeholder="Ej: Descuento Verano 2024" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control @error('descripcion') is-invalid @enderror" 
                              rows="3" placeholder="Descripción interna del cupón (opcional)">{{ old('descripcion', $cupon->descripcion ?? '') }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Descuento <span class="text-danger">*</span></label>
                        <select name="tipo_descuento" class="form-select @error('tipo_descuento') is-invalid @enderror" required>
                            <option value="porcentaje" {{ old('tipo_descuento', $cupon->tipo_descuento ?? 'porcentaje') === 'porcentaje' ? 'selected' : '' }}>Porcentaje (%)</option>
                            <option value="fijo" {{ old('tipo_descuento', $cupon->tipo_descuento ?? '') === 'fijo' ? 'selected' : '' }}>Cantidad Fija (€)</option>
                        </select>
                        @error('tipo_descuento')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valor del Descuento <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="valor_descuento" 
                               class="form-control @error('valor_descuento') is-invalid @enderror" 
                               value="{{ old('valor_descuento', $cupon->valor_descuento ?? '') }}" required>
                        @error('valor_descuento')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Descuento Máximo (€)</label>
                        <input type="number" step="0.01" min="0" name="descuento_maximo" 
                               class="form-control @error('descuento_maximo') is-invalid @enderror" 
                               value="{{ old('descuento_maximo', $cupon->descuento_maximo ?? '') }}" 
                               placeholder="Opcional (solo para %)">
                        <small class="text-muted">Solo para porcentaje</small>
                        @error('descuento_maximo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Restricciones de Uso -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Restricciones de Uso</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Usos Máximos Totales</label>
                        <input type="number" min="1" name="usos_maximos" 
                               class="form-control @error('usos_maximos') is-invalid @enderror" 
                               value="{{ old('usos_maximos', $cupon->usos_maximos ?? '') }}" 
                               placeholder="Ilimitado">
                        <small class="text-muted">Dejar vacío para ilimitado</small>
                        @error('usos_maximos')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Usos por Cliente <span class="text-danger">*</span></label>
                        <input type="number" min="1" name="usos_por_cliente" 
                               class="form-control @error('usos_por_cliente') is-invalid @enderror" 
                               value="{{ old('usos_por_cliente', $cupon->usos_por_cliente ?? 1) }}" required>
                        @error('usos_por_cliente')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Importe Mínimo (€)</label>
                        <input type="number" step="0.01" min="0" name="importe_minimo" 
                               class="form-control @error('importe_minimo') is-invalid @enderror" 
                               value="{{ old('importe_minimo', $cupon->importe_minimo ?? '') }}" 
                               placeholder="Sin mínimo">
                        @error('importe_minimo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Válido Desde</label>
                        <input type="date" name="fecha_inicio" 
                               class="form-control @error('fecha_inicio') is-invalid @enderror" 
                               value="{{ old('fecha_inicio', $cupon->fecha_inicio?->format('Y-m-d') ?? '') }}">
                        <small class="text-muted">Fecha desde la que el cupón es válido</small>
                        @error('fecha_inicio')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Válido Hasta</label>
                        <input type="date" name="fecha_fin" 
                               class="form-control @error('fecha_fin') is-invalid @enderror" 
                               value="{{ old('fecha_fin', $cupon->fecha_fin?->format('Y-m-d') ?? '') }}">
                        <small class="text-muted">Fecha hasta la que el cupón es válido</small>
                        @error('fecha_fin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Reservas Desde</label>
                        <input type="date" name="reserva_desde" 
                               class="form-control @error('reserva_desde') is-invalid @enderror" 
                               value="{{ old('reserva_desde', $cupon->reserva_desde?->format('Y-m-d') ?? '') }}">
                        <small class="text-muted">Válido para reservas desde esta fecha</small>
                        @error('reserva_desde')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reservas Hasta</label>
                        <input type="date" name="reserva_hasta" 
                               class="form-control @error('reserva_hasta') is-invalid @enderror" 
                               value="{{ old('reserva_hasta', $cupon->reserva_hasta?->format('Y-m-d') ?? '') }}">
                        <small class="text-muted">Válido para reservas hasta esta fecha</small>
                        @error('reserva_hasta')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Noches Mínimas</label>
                    <input type="number" min="1" name="noches_minimas" 
                           class="form-control @error('noches_minimas') is-invalid @enderror" 
                           value="{{ old('noches_minimas', $cupon->noches_minimas ?? '') }}" 
                           placeholder="Sin mínimo">
                    <small class="text-muted">Número mínimo de noches para aplicar el cupón</small>
                    @error('noches_minimas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Restricciones de Apartamentos -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i>Restricciones de Apartamentos</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Edificios Permitidos</label>
                    <select name="edificios_ids[]" class="form-select @error('edificios_ids') is-invalid @enderror" multiple size="4">
                        <option value="">-- Todos los edificios --</option>
                        @foreach($edificios as $edificio)
                            <option value="{{ $edificio->id }}" 
                                {{ in_array($edificio->id, old('edificios_ids', $cupon->edificios_ids ?? [])) ? 'selected' : '' }}>
                                {{ $edificio->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Mantén Ctrl/Cmd para seleccionar varios. Dejar vacío = todos</small>
                    @error('edificios_ids')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Apartamentos Permitidos</label>
                    <select name="apartamentos_ids[]" class="form-select @error('apartamentos_ids') is-invalid @enderror" multiple size="6">
                        <option value="">-- Todos los apartamentos --</option>
                        @foreach($apartamentos as $apartamento)
                            <option value="{{ $apartamento->id }}" 
                                {{ in_array($apartamento->id, old('apartamentos_ids', $cupon->apartamentos_ids ?? [])) ? 'selected' : '' }}>
                                {{ $apartamento->titulo }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Mantén Ctrl/Cmd para seleccionar varios. Dejar vacío = todos</small>
                    @error('apartamentos_ids')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Estado</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="activo" class="form-check-input" id="activo" 
                           {{ old('activo', $cupon->activo ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="activo">
                        <strong>Cupón Activo</strong>
                        <br><small class="text-muted">Los cupones inactivos no pueden ser utilizados</small>
                    </label>
                </div>

                @if(isset($cupon) && $cupon->exists)
                    <hr>
                    <div class="mb-2">
                        <small class="text-muted">Creado por:</small>
                        <br><strong>{{ $cupon->creador->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Fecha creación:</small>
                        <br><strong>{{ $cupon->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Usos actuales:</small>
                        <br><strong>{{ $cupon->usos_actuales }}</strong>
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-primary">
            <div class="card-body">
                <h6 class="text-primary"><i class="fas fa-info-circle me-2"></i>Ayuda</h6>
                <ul class="small mb-0">
                    <li>El código se convertirá automáticamente a mayúsculas</li>
                    <li>Los campos marcados con <span class="text-danger">*</span> son obligatorios</li>
                    <li>Dejar campos vacíos = sin restricción</li>
                    <li>El descuento máximo solo aplica para descuentos porcentuales</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mt-4">
    <a href="{{ route('admin.cupones.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Volver
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>{{ isset($cupon) && $cupon->exists ? 'Actualizar' : 'Crear' }} Cupón
    </button>
</div>
