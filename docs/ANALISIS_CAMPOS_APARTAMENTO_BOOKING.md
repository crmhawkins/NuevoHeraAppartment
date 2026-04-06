# Análisis de Campos: Apartamento vs Booking.com

## 📊 Campos Actuales en el Modelo Apartamento

### Información Básica
- ✅ `nombre` - Nombre interno
- ✅ `titulo` - Título público
- ✅ `description` - Descripción completa
- ✅ `important_information` - Información importante
- ✅ `property_type` - Tipo de propiedad

### Características Físicas
- ✅ `bedrooms` - Número de habitaciones
- ✅ `bathrooms` - Número de baños
- ✅ `max_guests` - Capacidad máxima
- ✅ `size` - Tamaño en m²

### Ubicación
- ✅ `address` - Dirección
- ✅ `city` - Ciudad
- ✅ `state` - Provincia/Estado
- ✅ `country` - País
- ✅ `zip_code` - Código postal
- ✅ `latitude` - Latitud
- ✅ `longitude` - Longitud
- ✅ `edificio_id` - Relación con edificio

### Contacto
- ✅ `email` - Email de contacto
- ✅ `phone` - Teléfono
- ✅ `website` - Sitio web

### Relaciones
- ✅ `photos` - Fotos del apartamento
- ✅ `roomTypes` - Tipos de habitación
- ✅ `ratePlans` - Planes de tarifa

---

## ❌ Campos que FALTAN para una experiencia tipo Booking.com

### 1. Check-in/Check-out
- `check_in_time` - Hora de entrada (ej: "15:00")
- `check_out_time` - Hora de salida (ej: "11:00")
- `check_in_instructions` - Instrucciones de entrada (text)
- `check_out_instructions` - Instrucciones de salida (text)

### 2. Amenities/Facilities (Servicios/Comodidades)
Estos deberían ser una tabla separada `apartamento_amenities` con relación many-to-many, o campos booleanos:
- `wifi` - WiFi disponible (boolean)
- `wifi_free` - WiFi gratuito (boolean)
- `parking` - Parking disponible (boolean)
- `parking_free` - Parking gratuito (boolean)
- `parking_on_site` - Parking en el sitio (boolean)
- `air_conditioning` - Aire acondicionado (boolean)
- `heating` - Calefacción (boolean)
- `tv` - TV disponible (boolean)
- `cable_tv` - TV por cable (boolean)
- `kitchen` - Cocina disponible (boolean)
- `kitchen_fully_equipped` - Cocina totalmente equipada (boolean)
- `dishwasher` - Lavavajillas (boolean)
- `washing_machine` - Lavadora (boolean)
- `dryer` - Secadora (boolean)
- `microwave` - Microondas (boolean)
- `refrigerator` - Nevera (boolean)
- `oven` - Horno (boolean)
- `coffee_machine` - Cafetera (boolean)
- `balcony` - Balcón (boolean)
- `terrace` - Terraza (boolean)
- `garden` - Jardín (boolean)
- `swimming_pool` - Piscina (boolean)
- `elevator` - Ascensor (boolean)
- `pets_allowed` - Mascotas permitidas (boolean)
- `smoking_allowed` - Fumar permitido (boolean)
- `accessible` - Accesible para discapacitados (boolean)
- `safe` - Caja fuerte (boolean)
- `hair_dryer` - Secador de pelo (boolean)
- `iron` - Plancha (boolean)
- `linen` - Ropa de cama incluida (boolean)
- `towels` - Toallas incluidas (boolean)

### 3. Reglas de la Casa
- `house_rules` - Reglas de la casa (text)
- `min_age_child` - Edad mínima para niños (integer, nullable)
- `quiet_hours_start` - Hora inicio horas tranquilas (time, nullable)
- `quiet_hours_end` - Hora fin horas tranquilas (time, nullable)

### 4. Política de Cancelación
- `cancellation_policy` - Política de cancelación (enum: flexible, moderate, strict, super_strict)
- `cancellation_details` - Detalles de cancelación (text)

### 5. Reseñas y Calificaciones
- `rating_score` - Puntuación promedio (decimal 2,2) - calculado de reviews
- `reviews_count` - Número de reseñas (integer) - calculado
- `cleanliness_rating` - Puntuación limpieza (decimal 2,2)
- `location_rating` - Puntuación ubicación (decimal 2,2)
- `value_rating` - Puntuación relación calidad-precio (decimal 2,2)
- `service_rating` - Puntuación servicio (decimal 2,2)

### 6. Información Adicional
- `cancellation_deadline` - Días antes para cancelación gratis (integer, nullable)
- `payment_options` - Opciones de pago (JSON)
- `languages_spoken` - Idiomas hablados (JSON array)
- `nearest_beach_distance` - Distancia a playa (decimal, nullable)
- `nearest_beach_name` - Nombre playa más cercana (string, nullable)
- `nearest_airport_distance` - Distancia a aeropuerto (decimal, nullable)
- `nearest_airport_name` - Nombre aeropuerto (string, nullable)
- `public_transport_nearby` - Transporte público cerca (boolean)
- `metro_station_distance` - Distancia a metro (decimal, nullable)
- `bus_stop_distance` - Distancia a parada bus (decimal, nullable)

### 7. Detalles de Cama
- `bed_types` - Tipos de cama (JSON) - [{"type": "double", "count": 1}, {"type": "single", "count": 2}]
- `sofa_bed` - Sofá cama disponible (boolean)
- `extra_bed_available` - Cama extra disponible (boolean)
- `extra_bed_price` - Precio cama extra (decimal 10,2, nullable)

### 8. Internet y Tecnología
- `wifi_speed` - Velocidad WiFi (string, nullable) - "50 Mbps"
- `wifi_coverage` - Cobertura WiFi (enum: full, partial, none)
- `workspace` - Zona de trabajo/escritorio (boolean)

### 9. Seguridad
- `security_deposit` - Depósito de seguridad (decimal 10,2, nullable)
- `security_deposit_type` - Tipo depósito (enum: cash, credit_card, none)
- `fire_extinguisher` - Extintor disponible (boolean)
- `smoke_detector` - Detector de humo (boolean)
- `first_aid_kit` - Botiquín disponible (boolean)

### 10. Servicios Adicionales
- `cleaning_fee` - Tarifa de limpieza (decimal 10,2, nullable)
- `tourist_tax` - Impuesto turístico (decimal 10,2, nullable)
- `tourist_tax_included` - Impuesto incluido en precio (boolean)
- `city_tax` - Impuesto local (decimal 10,2, nullable)
- `city_tax_included` - Impuesto local incluido (boolean)

### 11. Estacionamiento
- `parking_spaces` - Número de plazas (integer, nullable)
- `parking_price_per_day` - Precio parking por día (decimal 10,2, nullable)
- `parking_reservation_required` - Reserva parking requerida (boolean)

### 12. Otras Características
- `floor_number` - Número de planta (integer, nullable)
- `elevator_available` - Ascensor disponible (boolean)
- `building_year` - Año construcción (integer, nullable)
- `last_renovation_year` - Año última renovación (integer, nullable)
- `view_type` - Tipo de vista (string, nullable) - "sea_view", "city_view", "garden_view"
- `balcony_size` - Tamaño balcón (decimal 8,2, nullable)
- `terrace_size` - Tamaño terraza (decimal 8,2, nullable)

---

## 🎯 Recomendación de Implementación

### Opción 1: Campos Booleanos (Simple, rápido)
Añadir campos booleanos para los amenities más importantes. Rápido pero menos flexible.

### Opción 2: Tabla Separada de Amenities (Recomendado)
Crear tabla `apartamento_amenities` con relación many-to-many. Más flexible y escalable.

### Opción 3: Híbrido
Campos booleanos para lo básico (wifi, parking, ac) + tabla para amenities específicos.

---

## 📝 Prioridad de Implementación

### Alta Prioridad (Esenciales para Booking.com)
1. ✅ Check-in/out times
2. ✅ Amenities básicos (wifi, parking, air_conditioning, kitchen)
3. ✅ House rules
4. ✅ Cancellation policy
5. ✅ Rating score (aunque sea mock inicialmente)

### Media Prioridad (Mejoran la experiencia)
6. Detalles de cama
7. Información de seguridad
8. Detalles de ubicación cercana
9. Servicios adicionales (limpieza, impuestos)

### Baja Prioridad (Nice to have)
10. Año construcción/renovación
11. Detalles específicos de terraza/balcón
12. Información detallada de transporte

---

## 🔄 Campos a Mostrar en Vista de Detalles

Con los campos actuales, podemos mostrar:
- ✅ Título y ubicación
- ✅ Descripción
- ✅ Características básicas (habitaciones, baños, capacidad, tamaño)
- ✅ Fotos
- ✅ Ubicación (dirección, mapa si hay coordenadas)
- ✅ Información importante

**Necesitamos añadir:**
- ⚠️ Amenities/Servicios (lista de servicios disponibles)
- ⚠️ Check-in/out times
- ⚠️ Reglas de la casa
- ⚠️ Política de cancelación
- ⚠️ Rating/reseñas
- ⚠️ Información de transporte cercano
- ⚠️ Qué hay cerca (atracciones)





