<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmenityReposicion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'amenity_reposicions';
    
    protected $fillable = [
        'amenity_id',
        'user_id',
        'cantidad_reponida',
        'stock_anterior',
        'stock_nuevo',
        'precio_unitario',
        'precio_total',
        'proveedor',
        'numero_factura',
        'observaciones',
        'fecha_reposicion'
    ];

    protected $casts = [
        'cantidad_reponida' => 'decimal:2',
        'stock_anterior' => 'decimal:2',
        'stock_nuevo' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_total' => 'decimal:2',
        'fecha_reposicion' => 'date'
    ];

    // Relaciones
    public function amenity()
    {
        return $this->belongsTo(Amenity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
