<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservaServicio extends Model
{
    use HasFactory;

    protected $table = 'reserva_servicios';

    protected $fillable = [
        'reserva_id',
        'servicio_id',
        'pago_id',
        'precio',
        'moneda',
        'estado',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'notas',
        'fecha_pago',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    // Relaciones
    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }

    public function pago()
    {
        return $this->belongsTo(Pago::class);
    }

    // Scopes
    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }
}
