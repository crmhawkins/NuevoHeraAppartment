<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ARIUpdate extends Model
{
    use HasFactory;

    protected $table = 'ari_updates';

    protected $fillable = [
        'property_id',
        'rate_plan_id',
        'room_type_id',
        'start_date',
        'end_date',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];
}
