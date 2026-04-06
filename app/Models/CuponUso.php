<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuponUso extends Model
{
    use HasFactory;

    protected $table = 'cupon_usos';

    protected $fillable = [
        'cupon_id',
        'reserva_id',
        'cliente_id',
        'importe_original',
        'descuento_aplicado',
        'importe_final',
        'ip_address',
    ];

    protected $casts = [
        'importe_original' => 'decimal:2',
        'descuento_aplicado' => 'decimal:2',
        'importe_final' => 'decimal:2',
    ];

    /**
     * Relaciones
     */
    public function cupon()
    {
        return $this->belongsTo(Cupon::class);
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
