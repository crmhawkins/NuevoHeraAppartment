<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LugarCercano extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lugar_cercanos';

    protected $fillable = [
        'apartamento_id',
        'nombre',
        'categoria',
        'tipo',
        'distancia',
        'unidad_distancia',
        'orden',
        'activo',
    ];

    protected $casts = [
        'distancia' => 'decimal:2',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Relación con apartamento
     */
    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    /**
     * Scope para lugares activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Scope por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }
}
