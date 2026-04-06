<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaHold extends Model
{
    protected $table = 'reserva_holds';

    protected $fillable = [
        'apartamento_id',
        'room_type_id',
        'reserva_id',
        'fecha_entrada',
        'fecha_salida',
        'hold_token',
        'estado',
        'expires_at',
    ];

    protected $casts = [
        'fecha_entrada' => 'date',
        'fecha_salida' => 'date',
        'expires_at' => 'datetime',
    ];

    public function apartamento(): BelongsTo
    {
        return $this->belongsTo(Apartamento::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }
}

