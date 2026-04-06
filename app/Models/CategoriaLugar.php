<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CategoriaLugar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categoria_lugares';

    protected $fillable = [
        'nombre',
        'slug',
        'tipo_categoria',
        'terminos_busqueda',
        'amenity_osm',
        'shop_osm',
        'tourism_osm',
        'leisure_osm',
        'radio_metros',
        'limite_resultados',
        'orden',
        'activo',
        'busqueda_automatica',
    ];

    protected $casts = [
        'radio_metros' => 'integer',
        'limite_resultados' => 'integer',
        'orden' => 'integer',
        'activo' => 'boolean',
        'busqueda_automatica' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($categoria) {
            if (empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nombre);
            }
        });
    }

    /**
     * Scope para categorías activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para búsqueda automática
     */
    public function scopeParaBusquedaAutomatica($query)
    {
        return $query->where('activo', true)
            ->where('busqueda_automatica', true);
    }

    /**
     * Scope ordenadas
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Obtener parámetros de búsqueda para OpenStreetMap
     */
    public function obtenerParametrosBusqueda(): array
    {
        $params = [
            'radius' => $this->radio_metros,
            'limit' => $this->limite_resultados,
        ];

        if ($this->amenity_osm) {
            $params['amenity'] = $this->amenity_osm;
        }

        if ($this->shop_osm) {
            $params['shop'] = $this->shop_osm;
        }

        if ($this->tourism_osm) {
            $params['tourism'] = $this->tourism_osm;
        }

        if ($this->leisure_osm) {
            $params['leisure'] = $this->leisure_osm;
        }

        if ($this->terminos_busqueda) {
            $terminos = array_map('trim', explode(',', $this->terminos_busqueda));
            $params['q'] = implode(' ', $terminos);
        }

        return $params;
    }
}
