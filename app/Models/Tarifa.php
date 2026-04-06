<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'fecha_inicio',
        'fecha_fin',
        'temporada_alta',
        'temporada_baja',
        'activo'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'temporada_alta' => 'boolean',
        'temporada_baja' => 'boolean',
        'activo' => 'boolean'
    ];

    /**
     * Relación con apartamentos
     */
    public function apartamentos()
    {
        return $this->belongsToMany(Apartamento::class, 'apartamento_tarifa')
                    ->withPivot('activo')
                    ->withTimestamps();
    }

    /**
     * Scope para tarifas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para tarifas de temporada alta
     */
    public function scopeTemporadaAlta($query)
    {
        return $query->where('temporada_alta', true);
    }

    /**
     * Scope para tarifas de temporada baja
     */
    public function scopeTemporadaBaja($query)
    {
        return $query->where('temporada_baja', true);
    }

    /**
     * Scope para tarifas vigentes en una fecha
     */
    public function scopeVigentesEn($query, $fecha)
    {
        return $query->where('fecha_inicio', '<=', $fecha)
                    ->where('fecha_fin', '>=', $fecha);
    }

    /**
     * Obtener el precio formateado
     */
    public function getPrecioFormateadoAttribute()
    {
        return number_format($this->precio, 2, ',', '.') . ' €';
    }

    /**
     * Obtener el rango de fechas formateado
     */
    public function getRangoFechasAttribute()
    {
        return $this->fecha_inicio->format('d/m/Y') . ' - ' . $this->fecha_fin->format('d/m/Y');
    }

    /**
     * Obtener el tipo de temporada
     */
    public function getTipoTemporadaAttribute()
    {
        if ($this->temporada_alta) {
            return 'Alta';
        } elseif ($this->temporada_baja) {
            return 'Baja';
        }
        return 'General';
    }
}
