<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AmenityConsumo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'amenity_consumos';
    
    protected $fillable = [
        'amenity_id',
        'reserva_id',
        'apartamento_id',
        'limpieza_id',
        'user_id',
        'tipo_consumo',
        'cantidad_consumida',
        'cantidad_anterior',
        'cantidad_actual',
        'costo_unitario',
        'costo_total',
        'observaciones',
        'fecha_consumo'
    ];

    protected $casts = [
        'cantidad_consumida' => 'decimal:2',
        'cantidad_anterior' => 'decimal:2',
        'cantidad_actual' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'costo_total' => 'decimal:2',
        'fecha_consumo' => 'date',
        'tipo_consumo' => 'string'
    ];

    // Relaciones
    public function amenity()
    {
        return $this->belongsTo(Amenity::class);
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    public function limpieza()
    {
        return $this->belongsTo(ApartamentoLimpieza::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
