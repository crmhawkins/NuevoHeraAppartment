<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Translatable;

class Servicio extends Model
{
    use HasFactory, SoftDeletes, Translatable;

    /** Slug del servicio Alquiler de coche: reserva con rango de fechas, siempre no disponible */
    public const SLUG_ALQUILER_COCHE = 'alquiler-de-coche';

    protected $table = 'servicios';

    protected $fillable = [
        'icono',
        'nombre',
        'slug',
        'descripcion',
        'precio',
        'imagen',
        'orden',
        'categoria',
        'es_popular',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'es_popular' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($servicio) {
            if (empty($servicio->slug)) {
                $servicio->slug = Str::slug($servicio->nombre);
            }
        });

        static::updating(function ($servicio) {
            if ($servicio->isDirty('nombre') && empty($servicio->slug)) {
                $servicio->slug = Str::slug($servicio->nombre);
            }
        });
    }

    /**
     * Relación con apartamentos (many-to-many)
     */
    public function apartamentos()
    {
        return $this->belongsToMany(Apartamento::class, 'apartamento_servicio', 'servicio_id', 'apartamento_id')
                    ->withTimestamps();
    }

    /**
     * Relación con reservas (a través de reserva_servicios)
     */
    public function reservas()
    {
        return $this->hasMany(ReservaServicio::class, 'servicio_id');
    }

    /**
     * Scope para servicios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para servicios populares
     */
    public function scopePopulares($query)
    {
        return $query->where('es_popular', true);
    }

    /**
     * Scope para ordenar por orden
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Obtener todas las categorías disponibles
     */
    public static function categoriasDisponibles()
    {
        return static::select('categoria')
            ->whereNotNull('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria')
            ->filter();
    }

    /**
     * Indica si este servicio es "Alquiler de coche" (reserva con rango de fechas, siempre no disponible).
     */
    public function esAlquilerCoche(): bool
    {
        return $this->slug === self::SLUG_ALQUILER_COCHE;
    }
}
