<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentoPago extends Model
{
    use HasFactory;

    protected $table = 'intentos_pago';

    protected $fillable = [
        'pago_id',
        'reserva_id',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'estado',
        'monto',
        'moneda',
        'mensaje_error',
        'respuesta_stripe',
        'ip_address',
        'user_agent',
        'fecha_intento',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'respuesta_stripe' => 'array',
        'fecha_intento' => 'datetime',
    ];

    // Relaciones
    public function pago()
    {
        return $this->belongsTo(Pago::class);
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    // Scopes
    public function scopeExitosos($query)
    {
        return $query->where('estado', 'exitoso');
    }

    public function scopeFallidos($query)
    {
        return $query->where('estado', 'fallido');
    }
}
