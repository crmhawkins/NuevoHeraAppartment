<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApartamentoPhoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'apartamento_id',
        'url',
        'path', // Ruta en storage
        'position',
        'author',
        'kind',
        'description',
        'is_primary', // Foto principal
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Relación con el apartamento.
     */
    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    /**
     * Scope para obtener foto principal
     */
    public function scopePrincipal($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope para ordenar por posición
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
