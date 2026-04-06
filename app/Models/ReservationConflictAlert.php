<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationConflictAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartamento_id',
        'conflict_key',
        'reserva_ids',
        'last_sent_at',
        'resolved_at',
    ];

    protected $casts = [
        'reserva_ids' => 'array',
        'last_sent_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function isDueForSend(Carbon $now): bool
    {
        if (!$this->last_sent_at) {
            return true;
        }

        return $this->last_sent_at->lte($now->copy()->subHours(6));
    }
}


