<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Translatable;

class NormaCasa extends Model
{
    use HasFactory, SoftDeletes, Translatable;

    protected $table = 'normas_casa';

    protected $fillable = [
        'icono',
        'titulo',
        'descripcion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Relación con apartamentos (many-to-many)
     */
    public function apartamentos()
    {
        return $this->belongsToMany(Apartamento::class, 'apartamento_norma_casa', 'norma_casa_id', 'apartamento_id')
                    ->withTimestamps();
    }

    /**
     * Scope para normas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por orden
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden')->orderBy('titulo');
    }
}
