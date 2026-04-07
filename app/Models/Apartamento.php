<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Translatable;

class Apartamento extends Model
{
    use HasFactory, SoftDeletes, Translatable;
      /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'id_booking',
        'id_airbnb',
        'id_web',
        'titulo',
        'claves',
        // 'edificio',
        'edificio_id',
        'id_channex',
        'currency',
        'country',
        'state',
        'city',
        'address',
        'zip_code',
        'latitude',
        'longitude',
        'timezone',
        'property_type',
        'description',
        'bedrooms',
        'bathrooms',
        'max_guests',
        'size',
        'important_information',
        'email',
        'phone',
        'website',
        // Campos para plataforma del estado
        'codigo_establecimiento',
        'pais_iso3',
        'codigo_municipio_ine',
        'nombre_municipio',
        'tipo_establecimiento',
        // Campos Booking.com
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
        'view_type', 'balcony_size', 'terrace_size', 'parking_reservation_required',
        'ttlock_lock_id',
        'tipo_cerradura',
        'tuyalaravel_lock_id',
    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_options' => 'array',
        'languages_spoken' => 'array',
        'bed_types' => 'array',
        'wifi' => 'boolean',
        'wifi_free' => 'boolean',
        'parking' => 'boolean',
        'parking_free' => 'boolean',
        'air_conditioning' => 'boolean',
        'heating' => 'boolean',
        'tv' => 'boolean',
        'cable_tv' => 'boolean',
        'kitchen' => 'boolean',
        'kitchen_fully_equipped' => 'boolean',
        'dishwasher' => 'boolean',
        'washing_machine' => 'boolean',
        'dryer' => 'boolean',
        'microwave' => 'boolean',
        'refrigerator' => 'boolean',
        'oven' => 'boolean',
        'coffee_machine' => 'boolean',
        'balcony' => 'boolean',
        'terrace' => 'boolean',
        'garden' => 'boolean',
        'swimming_pool' => 'boolean',
        'elevator' => 'boolean',
        'pets_allowed' => 'boolean',
        'smoking_allowed' => 'boolean',
        'accessible' => 'boolean',
        'safe' => 'boolean',
        'hair_dryer' => 'boolean',
        'iron' => 'boolean',
        'linen' => 'boolean',
        'towels' => 'boolean',
        'workspace' => 'boolean',
        'public_transport_nearby' => 'boolean',
        'sofa_bed' => 'boolean',
        'extra_bed_available' => 'boolean',
        'parking_reservation_required' => 'boolean',
        'fire_extinguisher' => 'boolean',
        'smoke_detector' => 'boolean',
        'first_aid_kit' => 'boolean',
        'tourist_tax_included' => 'boolean',
        'city_tax_included' => 'boolean',
    ];

    /**
     * Relación con el edificio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
     /**
     * Relación con las fotos del apartamento.
     */
    public function photos()
    {
        return $this->hasMany(ApartamentoPhoto::class);
    }
    public function roomTypes()
    {
        return $this->hasMany(RoomType::class, 'property_id', 'id');
    }
    
    public function ratePlans()
    {
        return $this->hasMany(RatePlan::class, 'property_id', 'id');
    }
    
    public function edificioName()
    {
        return $this->belongsTo(\App\Models\Edificio::class, 'edificio_id');
    }

    public function edificio()
    {
        return $this->belongsTo(\App\Models\Edificio::class, 'edificio_id');
    }
    
    
    /**
     * Relación con el edificio (alias alternativo)
     */
    public function edificioRel()
    {
        return $this->belongsTo(\App\Models\Edificio::class, 'edificio_id');
    }

    /**
     * Relación con tarifas
     */
    public function tarifas()
    {
        return $this->belongsToMany(Tarifa::class, 'apartamento_tarifa')
                    ->withPivot('activo')
                    ->withTimestamps();
    }

    /**
     * Relación con reservas
     */
    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'apartamento_id');
    }

    /**
     * Obtener tarifas activas
     */
    public function tarifasActivas()
    {
        return $this->tarifas()->wherePivot('activo', true);
    }

    /**
     * Obtener tarifa vigente para una fecha específica
     */
    public function tarifaVigente($fecha)
    {
        return $this->tarifas()
                    ->wherePivot('activo', true)
                    ->where('activo', true)
                    ->where('fecha_inicio', '<=', $fecha)
                    ->where('fecha_fin', '>=', $fecha)
                    ->first();
    }

    /**
     * Relación con normas de la casa
     */
    public function normasCasa()
    {
        return $this->belongsToMany(NormaCasa::class, 'apartamento_norma_casa', 'apartamento_id', 'norma_casa_id')
                    ->where('normas_casa.activo', true)
                    ->orderBy('normas_casa.orden')
                    ->orderBy('normas_casa.titulo')
                    ->withTimestamps();
    }

    /**
     * Relación con servicios
     */
    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'apartamento_servicio', 'apartamento_id', 'servicio_id')
                    ->where('servicios.activo', true)
                    ->orderBy('servicios.categoria')
                    ->orderBy('servicios.es_popular', 'desc')
                    ->orderBy('servicios.orden')
                    ->orderBy('servicios.nombre')
                    ->withTimestamps();
    }

    /**
     * Obtener servicios populares del apartamento
     */
    public function serviciosPopulares()
    {
        return $this->servicios()->where('servicios.es_popular', true);
    }

    /**
     * Obtener servicios agrupados por categoría
     */
    public function serviciosPorCategoria()
    {
        return $this->servicios()->get()->groupBy('categoria');
    }

    /**
     * Relación con lugares cercanos
     */
    public function lugaresCercanos()
    {
        return $this->hasMany(LugarCercano::class)
                    ->where('lugar_cercanos.activo', true)
                    ->orderBy('lugar_cercanos.orden')
                    ->orderBy('lugar_cercanos.nombre');
    }

    /**
     * Relación con FAQs
     */
    public function faqs()
    {
        return $this->hasMany(FaqApartamento::class)
                    ->where('faq_apartamentos.activo', true)
                    ->orderBy('faq_apartamentos.orden')
                    ->orderBy('faq_apartamentos.pregunta');
    }
}
