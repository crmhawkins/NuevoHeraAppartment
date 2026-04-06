<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZonaComun extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'zona_comuns';
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion',
        'activo',
        'tipo',
        'orden'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer'
    ];

    // Relación con limpiezas
    public function limpiezas()
    {
        return $this->hasMany(ApartamentoLimpieza::class, 'zona_comun_id');
    }

    // Scope para zonas activas
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    // Scope para ordenar
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden', 'asc')->orderBy('nombre', 'asc');
    }
}
