<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemChecklistTareaGeneral extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'item_checklist_tarea_generals';
    
    protected $fillable = [
        'checklist_id',
        'nombre',
        'descripcion',
        'categoria',
        'activo',
        'orden',
        'tiene_stock',
        'articulo_id',
        'cantidad_requerida',
        'tiene_averias',
        'observaciones_stock'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
        'tiene_stock' => 'boolean',
        'cantidad_requerida' => 'decimal:2',
        'tiene_averias' => 'boolean'
    ];

    // Relación con el checklist padre
    public function checklist()
    {
        return $this->belongsTo(ChecklistTareaGeneral::class, 'checklist_id');
    }

    // Relación con el artículo
    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    // Scope para items activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para ordenar
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden', 'asc')->orderBy('nombre', 'asc');
    }

    // Scopes para stock y averías
    public function scopeConStock($query)
    {
        return $query->where('tiene_stock', true);
    }

    public function scopeConAverias($query)
    {
        return $query->where('tiene_averias', true);
    }

    // Métodos
    public function necesitaReposicion()
    {
        if (!$this->tiene_stock || !$this->articulo) {
            return false;
        }

        return $this->articulo->verificarStockBajo();
    }

    public function getEstadoStockAttribute()
    {
        if (!$this->tiene_stock || !$this->articulo) {
            return 'sin_stock';
        }

        if ($this->necesitaReposicion()) {
            return 'bajo_stock';
        }

        return 'normal';
    }
}
