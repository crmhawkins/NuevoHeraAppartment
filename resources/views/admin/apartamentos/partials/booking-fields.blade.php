{{-- REGLAS Y POLÍTICAS - PRIMERO PARA MAYOR VISIBILIDAD --}}
<div class="row mb-4 mt-4">
    <div class="col-12">
        <div class="alert alert-info border-left-primary border-left-4 mb-3" role="alert">
            <h5 class="text-primary mb-2 fw-semibold">
                <i class="fas fa-gavel me-2"></i>Reglas y Políticas
            </h5>
            <p class="mb-0 text-muted">Define las reglas de la casa y la política de cancelación para este apartamento</p>
        </div>
    </div>
    
    <div class="col-12 mb-3">
        <label for="house_rules" class="form-label fw-semibold">
            <i class="fas fa-file-contract me-1 text-primary"></i>Reglas de la casa
        </label>
        <textarea class="form-control" id="house_rules" name="house_rules" rows="6" 
                  placeholder="Ej: No se permiten fiestas, horario de silencio de 22:00 a 08:00, máximo 4 personas...">{{ old('house_rules', $apartamento->house_rules) }}</textarea>
        <small class="form-text text-muted">Estas reglas se mostrarán a los huéspedes en la página pública</small>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="cancellation_policy" class="form-label fw-semibold">
            <i class="fas fa-ban me-1 text-primary"></i>Política de cancelación
        </label>
        <select class="form-select" id="cancellation_policy" name="cancellation_policy">
            <option value="">Seleccionar política...</option>
            <option value="flexible" {{ old('cancellation_policy', $apartamento->cancellation_policy) == 'flexible' ? 'selected' : '' }}>Flexible - Cancelación gratuita hasta 1 día antes</option>
            <option value="moderate" {{ old('cancellation_policy', $apartamento->cancellation_policy) == 'moderate' ? 'selected' : '' }}>Moderada - Cancelación gratuita hasta 5 días antes</option>
            <option value="strict" {{ old('cancellation_policy', $apartamento->cancellation_policy) == 'strict' ? 'selected' : '' }}>Estricta - Cancelación gratuita hasta 14 días antes</option>
            <option value="super_strict" {{ old('cancellation_policy', $apartamento->cancellation_policy) == 'super_strict' ? 'selected' : '' }}>Muy estricta - Sin cancelación gratuita</option>
        </select>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="cancellation_deadline" class="form-label fw-semibold">
            <i class="fas fa-calendar-times me-1 text-primary"></i>Días antes para cancelación gratis
        </label>
        <input type="number" class="form-control" id="cancellation_deadline" name="cancellation_deadline" min="0" 
               placeholder="Ej: 7 días" value="{{ old('cancellation_deadline', $apartamento->cancellation_deadline) }}">
        <small class="form-text text-muted">Número de días antes de la entrada para cancelar gratis</small>
    </div>
    
    <div class="col-12 mb-3">
        <label for="cancellation_details" class="form-label fw-semibold">
            <i class="fas fa-info-circle me-1 text-primary"></i>Detalles de cancelación
        </label>
        <textarea class="form-control" id="cancellation_details" name="cancellation_details" rows="3" 
                  placeholder="Detalles específicos sobre la política de cancelación...">{{ old('cancellation_details', $apartamento->cancellation_details) }}</textarea>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="min_age_child" class="form-label fw-semibold">
            <i class="fas fa-baby me-1 text-primary"></i>Edad mínima para niños
        </label>
        <input type="number" class="form-control" id="min_age_child" name="min_age_child" min="0" 
               placeholder="Ej: 2 años" value="{{ old('min_age_child', $apartamento->min_age_child) }}">
    </div>
    
    <div class="col-md-3 mb-3">
        <label for="quiet_hours_start" class="form-label fw-semibold">
            <i class="fas fa-moon me-1 text-primary"></i>Hora inicio silencio
        </label>
        <input type="time" class="form-control" id="quiet_hours_start" name="quiet_hours_start" 
               value="{{ old('quiet_hours_start', $apartamento->quiet_hours_start ? \Carbon\Carbon::parse($apartamento->quiet_hours_start)->format('H:i') : '') }}">
    </div>
    
    <div class="col-md-3 mb-3">
        <label for="quiet_hours_end" class="form-label fw-semibold">
            <i class="fas fa-sun me-1 text-primary"></i>Hora fin silencio
        </label>
        <input type="time" class="form-control" id="quiet_hours_end" name="quiet_hours_end" 
               value="{{ old('quiet_hours_end', $apartamento->quiet_hours_end ? \Carbon\Carbon::parse($apartamento->quiet_hours_end)->format('H:i') : '') }}">
    </div>
</div>

<hr class="my-4" style="border-top: 2px solid #dee2e6;">

{{-- Campos adicionales tipo Booking.com --}}
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-primary mb-3 fw-semibold">
            <i class="fas fa-calendar-check me-2"></i>Check-in / Check-out
        </h6>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="check_in_time" class="form-label fw-semibold">Hora de entrada</label>
        <input type="time" class="form-control" id="check_in_time" name="check_in_time" 
               value="{{ old('check_in_time', $apartamento->check_in_time ? \Carbon\Carbon::parse($apartamento->check_in_time)->format('H:i') : '') }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="check_out_time" class="form-label fw-semibold">Hora de salida</label>
        <input type="time" class="form-control" id="check_out_time" name="check_out_time" 
               value="{{ old('check_out_time', $apartamento->check_out_time ? \Carbon\Carbon::parse($apartamento->check_out_time)->format('H:i') : '') }}">
    </div>
    
    <div class="col-12 mb-3">
        <label for="check_in_instructions" class="form-label fw-semibold">Instrucciones de entrada</label>
        <textarea class="form-control" id="check_in_instructions" name="check_in_instructions" rows="3">{{ old('check_in_instructions', $apartamento->check_in_instructions) }}</textarea>
    </div>
    
    <div class="col-12 mb-3">
        <label for="check_out_instructions" class="form-label fw-semibold">Instrucciones de salida</label>
        <textarea class="form-control" id="check_out_instructions" name="check_out_instructions" rows="3">{{ old('check_out_instructions', $apartamento->check_out_instructions) }}</textarea>
    </div>
</div>

{{-- Amenities / Servicios --}}
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-primary mb-3 fw-semibold">
            <i class="fas fa-star me-2"></i>Amenities / Servicios
        </h6>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="row">
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="wifi" name="wifi" {{ old('wifi', $apartamento->wifi) ? 'checked' : '' }}>
                    <label class="form-check-label" for="wifi">WiFi disponible</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="wifi_free" name="wifi_free" {{ old('wifi_free', $apartamento->wifi_free) ? 'checked' : '' }}>
                    <label class="form-check-label" for="wifi_free">WiFi gratuito</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="parking" name="parking" {{ old('parking', $apartamento->parking) ? 'checked' : '' }}>
                    <label class="form-check-label" for="parking">Parking disponible</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="parking_free" name="parking_free" {{ old('parking_free', $apartamento->parking_free) ? 'checked' : '' }}>
                    <label class="form-check-label" for="parking_free">Parking gratuito</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="air_conditioning" name="air_conditioning" {{ old('air_conditioning', $apartamento->air_conditioning) ? 'checked' : '' }}>
                    <label class="form-check-label" for="air_conditioning">Aire acondicionado</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="heating" name="heating" {{ old('heating', $apartamento->heating) ? 'checked' : '' }}>
                    <label class="form-check-label" for="heating">Calefacción</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="tv" name="tv" {{ old('tv', $apartamento->tv) ? 'checked' : '' }}>
                    <label class="form-check-label" for="tv">TV</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="cable_tv" name="cable_tv" {{ old('cable_tv', $apartamento->cable_tv) ? 'checked' : '' }}>
                    <label class="form-check-label" for="cable_tv">TV por cable</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="kitchen" name="kitchen" {{ old('kitchen', $apartamento->kitchen) ? 'checked' : '' }}>
                    <label class="form-check-label" for="kitchen">Cocina</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="kitchen_fully_equipped" name="kitchen_fully_equipped" {{ old('kitchen_fully_equipped', $apartamento->kitchen_fully_equipped) ? 'checked' : '' }}>
                    <label class="form-check-label" for="kitchen_fully_equipped">Cocina totalmente equipada</label>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="row">
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="dishwasher" name="dishwasher" {{ old('dishwasher', $apartamento->dishwasher) ? 'checked' : '' }}>
                    <label class="form-check-label" for="dishwasher">Lavavajillas</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="washing_machine" name="washing_machine" {{ old('washing_machine', $apartamento->washing_machine) ? 'checked' : '' }}>
                    <label class="form-check-label" for="washing_machine">Lavadora</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="dryer" name="dryer" {{ old('dryer', $apartamento->dryer) ? 'checked' : '' }}>
                    <label class="form-check-label" for="dryer">Secadora</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="balcony" name="balcony" {{ old('balcony', $apartamento->balcony) ? 'checked' : '' }}>
                    <label class="form-check-label" for="balcony">Balcón</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="terrace" name="terrace" {{ old('terrace', $apartamento->terrace) ? 'checked' : '' }}>
                    <label class="form-check-label" for="terrace">Terraza</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="swimming_pool" name="swimming_pool" {{ old('swimming_pool', $apartamento->swimming_pool) ? 'checked' : '' }}>
                    <label class="form-check-label" for="swimming_pool">Piscina</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="elevator" name="elevator" {{ old('elevator', $apartamento->elevator) ? 'checked' : '' }}>
                    <label class="form-check-label" for="elevator">Ascensor</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="pets_allowed" name="pets_allowed" {{ old('pets_allowed', $apartamento->pets_allowed) ? 'checked' : '' }}>
                    <label class="form-check-label" for="pets_allowed">Mascotas permitidas</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="accessible" name="accessible" {{ old('accessible', $apartamento->accessible) ? 'checked' : '' }}>
                    <label class="form-check-label" for="accessible">Accesible para discapacitados</label>
                </div>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="safe" name="safe" {{ old('safe', $apartamento->safe) ? 'checked' : '' }}>
                    <label class="form-check-label" for="safe">Caja fuerte</label>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="wifi_speed" class="form-label fw-semibold">Velocidad WiFi</label>
        <input type="text" class="form-control" id="wifi_speed" name="wifi_speed" 
               placeholder="Ej: 50 Mbps" value="{{ old('wifi_speed', $apartamento->wifi_speed) }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="wifi_coverage" class="form-label fw-semibold">Cobertura WiFi</label>
        <select class="form-select" id="wifi_coverage" name="wifi_coverage">
            <option value="full" {{ old('wifi_coverage', $apartamento->wifi_coverage) == 'full' ? 'selected' : '' }}>Completa</option>
            <option value="partial" {{ old('wifi_coverage', $apartamento->wifi_coverage) == 'partial' ? 'selected' : '' }}>Parcial</option>
            <option value="none" {{ old('wifi_coverage', $apartamento->wifi_coverage) == 'none' ? 'selected' : '' }}>Ninguna</option>
        </select>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="parking_spaces" class="form-label fw-semibold">Número de plazas de parking</label>
        <input type="number" class="form-control" id="parking_spaces" name="parking_spaces" min="0" 
               value="{{ old('parking_spaces', $apartamento->parking_spaces) }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="parking_price_per_day" class="form-label fw-semibold">Precio parking por día (€)</label>
        <input type="number" step="0.01" class="form-control" id="parking_price_per_day" name="parking_price_per_day" min="0" 
               value="{{ old('parking_price_per_day', $apartamento->parking_price_per_day) }}">
    </div>
</div>

{{-- Esta sección se movió al inicio del partial para mayor visibilidad --}}

{{-- Información adicional --}}
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-primary mb-3 fw-semibold">
            <i class="fas fa-info-circle me-2"></i>Información Adicional
        </h6>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="nearest_beach_name" class="form-label fw-semibold">Nombre playa más cercana</label>
        <input type="text" class="form-control" id="nearest_beach_name" name="nearest_beach_name" 
               value="{{ old('nearest_beach_name', $apartamento->nearest_beach_name) }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="nearest_beach_distance" class="form-label fw-semibold">Distancia a playa (km)</label>
        <input type="number" step="0.01" class="form-control" id="nearest_beach_distance" name="nearest_beach_distance" min="0" 
               value="{{ old('nearest_beach_distance', $apartamento->nearest_beach_distance) }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="nearest_airport_name" class="form-label fw-semibold">Nombre aeropuerto más cercano</label>
        <input type="text" class="form-control" id="nearest_airport_name" name="nearest_airport_name" 
               value="{{ old('nearest_airport_name', $apartamento->nearest_airport_name) }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="nearest_airport_distance" class="form-label fw-semibold">Distancia a aeropuerto (km)</label>
        <input type="number" step="0.01" class="form-control" id="nearest_airport_distance" name="nearest_airport_distance" min="0" 
               value="{{ old('nearest_airport_distance', $apartamento->nearest_airport_distance) }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="floor_number" class="form-label fw-semibold">Número de planta</label>
        <input type="number" class="form-control" id="floor_number" name="floor_number" 
               value="{{ old('floor_number', $apartamento->floor_number) }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="view_type" class="form-label fw-semibold">Tipo de vista</label>
        <select class="form-select" id="view_type" name="view_type">
            <option value="">Seleccionar...</option>
            <option value="sea_view" {{ old('view_type', $apartamento->view_type) == 'sea_view' ? 'selected' : '' }}>Vista al mar</option>
            <option value="city_view" {{ old('view_type', $apartamento->view_type) == 'city_view' ? 'selected' : '' }}>Vista a la ciudad</option>
            <option value="garden_view" {{ old('view_type', $apartamento->view_type) == 'garden_view' ? 'selected' : '' }}>Vista al jardín</option>
            <option value="mountain_view" {{ old('view_type', $apartamento->view_type) == 'mountain_view' ? 'selected' : '' }}>Vista a la montaña</option>
        </select>
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="building_year" class="form-label fw-semibold">Año construcción</label>
        <input type="number" class="form-control" id="building_year" name="building_year" min="1800" max="{{ date('Y') }}" 
               value="{{ old('building_year', $apartamento->building_year) }}">
    </div>
    
    <div class="col-md-6 mb-3">
        <label for="last_renovation_year" class="form-label fw-semibold">Año última renovación</label>
        <input type="number" class="form-control" id="last_renovation_year" name="last_renovation_year" min="1800" max="{{ date('Y') }}" 
               value="{{ old('last_renovation_year', $apartamento->last_renovation_year) }}">
    </div>
</div>

