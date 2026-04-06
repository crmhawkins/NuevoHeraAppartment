<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreguntaFrecuente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'preguntas_frecuentes';

    protected $fillable = [
        'pregunta',
        'respuesta',
        'categoria',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Scope para preguntas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden')->orderBy('pregunta');
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Obtener todas las categorías únicas
     */
    public static function categorias()
    {
        return static::activas()
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->pluck('categoria')
            ->sort()
            ->values();
    }
}
