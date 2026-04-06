<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CierreApartamento extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cierres_apartamentos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'apartamento_id',
        'reserva_id',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
        'activo'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean'
    ];

    /**
     * Relación con el modelo Apartamento
     */
    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }

    /**
     * Relación con el modelo Reserva
     */
    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }
}
