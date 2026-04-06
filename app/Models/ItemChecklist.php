<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemChecklist extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'items_checklists';

    protected $fillable = [
        'nombre', 
        'checklist_id',
        'activo',
        'tiene_stock',
        'articulo_id',
        'cantidad_requerida',
        'tiene_averias',
        'observaciones_stock'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'tiene_stock' => 'boolean',
        'cantidad_requerida' => 'decimal:2',
        'tiene_averias' => 'boolean'
    ];

    public function controles()
    {
        return $this->hasMany(ControlLimpieza::class);
    }
    
    public function apartamentos()
    {
        return $this->belongsToMany(ApartamentoLimpieza::class, 'apartamento_item_checklist')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    // Scopes
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

        return $this->articulo->estado_stock;
    }

}
