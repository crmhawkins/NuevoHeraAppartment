<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaqApartamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'faq_apartamentos';

    protected $fillable = [
        'apartamento_id',
        'pregunta',
        'respuesta',
        'orden',
        'activo',
    ];

    protected $casts = [
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
     * Scope para FAQs activos
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
        return $query->orderBy('orden')->orderBy('pregunta');
    }
}
