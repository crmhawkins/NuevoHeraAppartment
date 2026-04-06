<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pagos';

    protected $fillable = [
        'reserva_id',
        'cliente_id',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'metodo_pago',
        'estado',
        'monto',
        'moneda',
        'descripcion',
        'metadata',
        'fecha_pago',
        'fecha_vencimiento',
        'notas',
        'referencia_externa',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'metadata' => 'array',
        'fecha_pago' => 'datetime',
        'fecha_vencimiento' => 'datetime',
    ];

    // Relaciones
    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function intentos()
    {
        return $this->hasMany(IntentoPago::class);
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeCompletados($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopeFallidos($query)
    {
        return $query->where('estado', 'fallido');
    }
}
