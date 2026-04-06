<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReposicionArticulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartamento_limpieza_id',
        'item_checklist_id',
        'articulo_id',
        'user_id',
        'cantidad_reponer',
        'cantidad_anterior',
        'cantidad_nueva',
        'tipo_descuento',
        'stock_descontado',
        'stock_anterior',
        'stock_nuevo',
        'observaciones'
    ];

    protected $casts = [
        'cantidad_reponer' => 'decimal:2',
        'cantidad_anterior' => 'decimal:2',
        'cantidad_nueva' => 'decimal:2',
        'stock_descontado' => 'boolean',
        'stock_anterior' => 'decimal:2',
        'stock_nuevo' => 'decimal:2'
    ];

    // Relaciones
    public function apartamentoLimpieza()
    {
        return $this->belongsTo(ApartamentoLimpieza::class);
    }

    public function itemChecklist()
    {
        return $this->belongsTo(ItemChecklist::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Métodos
    public function getTipoDescuentoDescripcionAttribute()
    {
        switch ($this->tipo_descuento) {
            case 'reposicion':
                return 'Solo reposición física';
            case 'consumo':
                return 'Descuenta del stock general';
            default:
                return 'No definido';
        }
    }

    public function getStockDescontadoDescripcionAttribute()
    {
        return $this->stock_descontado ? 'Sí' : 'No';
    }
}