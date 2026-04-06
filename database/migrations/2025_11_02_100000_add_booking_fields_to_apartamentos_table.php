<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            // Check-in/Check-out
            $table->time('check_in_time')->nullable()->comment('Hora de entrada (ej: 15:00)');
            $table->time('check_out_time')->nullable()->comment('Hora de salida (ej: 11:00)');
            $table->text('check_in_instructions')->nullable()->comment('Instrucciones de entrada');
            $table->text('check_out_instructions')->nullable()->comment('Instrucciones de salida');
            
            // Amenities básicos (servicios/comodidades)
            $table->boolean('wifi')->default(false)->comment('WiFi disponible');
            $table->boolean('wifi_free')->default(false)->comment('WiFi gratuito');
            $table->boolean('parking')->default(false)->comment('Parking disponible');
            $table->boolean('parking_free')->default(false)->comment('Parking gratuito');
            $table->integer('parking_spaces')->nullable()->comment('Número de plazas de parking');
            $table->decimal('parking_price_per_day', 10, 2)->nullable()->comment('Precio parking por día');
            $table->boolean('air_conditioning')->default(false)->comment('Aire acondicionado');
            $table->boolean('heating')->default(false)->comment('Calefacción');
            $table->boolean('tv')->default(false)->comment('TV disponible');
            $table->boolean('cable_tv')->default(false)->comment('TV por cable');
            $table->boolean('kitchen')->default(false)->comment('Cocina disponible');
            $table->boolean('kitchen_fully_equipped')->default(false)->comment('Cocina totalmente equipada');
            $table->boolean('dishwasher')->default(false)->comment('Lavavajillas');
            $table->boolean('washing_machine')->default(false)->comment('Lavadora');
            $table->boolean('dryer')->default(false)->comment('Secadora');
            $table->boolean('microwave')->default(false)->comment('Microondas');
            $table->boolean('refrigerator')->default(false)->comment('Nevera');
            $table->boolean('oven')->default(false)->comment('Horno');
            $table->boolean('coffee_machine')->default(false)->comment('Cafetera');
            $table->boolean('balcony')->default(false)->comment('Balcón');
            $table->boolean('terrace')->default(false)->comment('Terraza');
            $table->boolean('garden')->default(false)->comment('Jardín');
            $table->boolean('swimming_pool')->default(false)->comment('Piscina');
            $table->boolean('elevator')->default(false)->comment('Ascensor');
            $table->boolean('pets_allowed')->default(false)->comment('Mascotas permitidas');
            $table->boolean('smoking_allowed')->default(false)->comment('Fumar permitido');
            $table->boolean('accessible')->default(false)->comment('Accesible para discapacitados');
            $table->boolean('safe')->default(false)->comment('Caja fuerte');
            $table->boolean('hair_dryer')->default(false)->comment('Secador de pelo');
            $table->boolean('iron')->default(false)->comment('Plancha');
            $table->boolean('linen')->default(true)->comment('Ropa de cama incluida');
            $table->boolean('towels')->default(true)->comment('Toallas incluidas');
            
            // Internet y tecnología
            $table->string('wifi_speed')->nullable()->comment('Velocidad WiFi (ej: 50 Mbps)');
            $table->enum('wifi_coverage', ['full', 'partial', 'none'])->default('full')->comment('Cobertura WiFi');
            $table->boolean('workspace')->default(false)->comment('Zona de trabajo/escritorio');
            
            // Reglas de la casa
            $table->text('house_rules')->nullable()->comment('Reglas de la casa');
            $table->integer('min_age_child')->nullable()->comment('Edad mínima para niños');
            $table->time('quiet_hours_start')->nullable()->comment('Hora inicio horas tranquilas');
            $table->time('quiet_hours_end')->nullable()->comment('Hora fin horas tranquilas');
            
            // Política de cancelación
            $table->enum('cancellation_policy', ['flexible', 'moderate', 'strict', 'super_strict'])->nullable()->comment('Política de cancelación');
            $table->text('cancellation_details')->nullable()->comment('Detalles de cancelación');
            $table->integer('cancellation_deadline')->nullable()->comment('Días antes para cancelación gratis');
            
            // Reseñas y calificaciones (iniciales, luego se calcularán de reviews)
            $table->decimal('rating_score', 3, 2)->nullable()->comment('Puntuación promedio (0-10)');
            $table->integer('reviews_count')->default(0)->comment('Número de reseñas');
            $table->decimal('cleanliness_rating', 3, 2)->nullable()->comment('Puntuación limpieza');
            $table->decimal('location_rating', 3, 2)->nullable()->comment('Puntuación ubicación');
            $table->decimal('value_rating', 3, 2)->nullable()->comment('Puntuación relación calidad-precio');
            $table->decimal('service_rating', 3, 2)->nullable()->comment('Puntuación servicio');
            
            // Información adicional
            $table->json('payment_options')->nullable()->comment('Opciones de pago');
            $table->json('languages_spoken')->nullable()->comment('Idiomas hablados');
            $table->decimal('nearest_beach_distance', 8, 2)->nullable()->comment('Distancia a playa (km)');
            $table->string('nearest_beach_name')->nullable()->comment('Nombre playa más cercana');
            $table->decimal('nearest_airport_distance', 8, 2)->nullable()->comment('Distancia a aeropuerto (km)');
            $table->string('nearest_airport_name')->nullable()->comment('Nombre aeropuerto más cercano');
            $table->boolean('public_transport_nearby')->default(false)->comment('Transporte público cerca');
            $table->decimal('metro_station_distance', 8, 2)->nullable()->comment('Distancia a metro (km)');
            $table->decimal('bus_stop_distance', 8, 2)->nullable()->comment('Distancia a parada bus (km)');
            
            // Detalles de cama
            $table->json('bed_types')->nullable()->comment('Tipos de cama [{"type":"double","count":1},{"type":"single","count":2}]');
            $table->boolean('sofa_bed')->default(false)->comment('Sofá cama disponible');
            $table->boolean('extra_bed_available')->default(false)->comment('Cama extra disponible');
            $table->decimal('extra_bed_price', 10, 2)->nullable()->comment('Precio cama extra');
            
            // Seguridad
            $table->decimal('security_deposit', 10, 2)->nullable()->comment('Depósito de seguridad');
            $table->enum('security_deposit_type', ['cash', 'credit_card', 'none'])->default('none')->comment('Tipo depósito');
            $table->boolean('fire_extinguisher')->default(false)->comment('Extintor disponible');
            $table->boolean('smoke_detector')->default(false)->comment('Detector de humo');
            $table->boolean('first_aid_kit')->default(false)->comment('Botiquín disponible');
            
            // Servicios adicionales
            $table->decimal('cleaning_fee', 10, 2)->nullable()->comment('Tarifa de limpieza');
            $table->decimal('tourist_tax', 10, 2)->nullable()->comment('Impuesto turístico');
            $table->boolean('tourist_tax_included')->default(false)->comment('Impuesto incluido en precio');
            $table->decimal('city_tax', 10, 2)->nullable()->comment('Impuesto local');
            $table->boolean('city_tax_included')->default(false)->comment('Impuesto local incluido');
            
            // Otras características
            $table->integer('floor_number')->nullable()->comment('Número de planta');
            $table->integer('building_year')->nullable()->comment('Año construcción');
            $table->integer('last_renovation_year')->nullable()->comment('Año última renovación');
            $table->string('view_type')->nullable()->comment('Tipo de vista: sea_view, city_view, garden_view, etc.');
            $table->decimal('balcony_size', 8, 2)->nullable()->comment('Tamaño balcón (m²)');
            $table->decimal('terrace_size', 8, 2)->nullable()->comment('Tamaño terraza (m²)');
            $table->boolean('parking_reservation_required')->default(false)->comment('Reserva parking requerida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_time', 'check_out_time', 'check_in_instructions', 'check_out_instructions',
                'wifi', 'wifi_free', 'parking', 'parking_free', 'parking_spaces', 'parking_price_per_day',
                'air_conditioning', 'heating', 'tv', 'cable_tv', 'kitchen', 'kitchen_fully_equipped',
                'dishwasher', 'washing_machine', 'dryer', 'microwave', 'refrigerator', 'oven',
                'coffee_machine', 'balcony', 'terrace', 'garden', 'swimming_pool', 'elevator',
                'pets_allowed', 'smoking_allowed', 'accessible', 'safe', 'hair_dryer', 'iron',
                'linen', 'towels', 'wifi_speed', 'wifi_coverage', 'workspace',
                'house_rules', 'min_age_child', 'quiet_hours_start', 'quiet_hours_end',
                'cancellation_policy', 'cancellation_details', 'cancellation_deadline',
                'rating_score', 'reviews_count', 'cleanliness_rating', 'location_rating',
                'value_rating', 'service_rating', 'payment_options', 'languages_spoken',
                'nearest_beach_distance', 'nearest_beach_name', 'nearest_airport_distance',
                'nearest_airport_name', 'public_transport_nearby', 'metro_station_distance',
                'bus_stop_distance', 'bed_types', 'sofa_bed', 'extra_bed_available',
                'extra_bed_price', 'security_deposit', 'security_deposit_type',
                'fire_extinguisher', 'smoke_detector', 'first_aid_kit',
                'cleaning_fee', 'tourist_tax', 'tourist_tax_included', 'city_tax',
                'city_tax_included', 'floor_number', 'building_year', 'last_renovation_year',
                'view_type', 'balcony_size', 'terrace_size', 'parking_reservation_required'
            ]);
        });
    }
};





