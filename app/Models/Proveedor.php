<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'contacto',
        'telefono',
        'email',
        'direccion',
        'cif_nif',
        'observaciones',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    // Relaciones
    public function articulos()
    {
        return $this->hasMany(Articulo::class);
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Métodos
    public function getTotalArticulosAttribute()
    {
        return $this->articulos()->count();
    }

    public function getTotalStockAttribute()
    {
        return $this->articulos()->sum('stock_actual');
    }
}
