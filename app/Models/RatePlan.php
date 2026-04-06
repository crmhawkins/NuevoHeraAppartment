<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatePlan extends Model
{
    use HasFactory;

    protected $table = 'rate_plans';

    protected $fillable = [
        'title',
        'property_id',
        'room_type_id',
        'tax_set_id',
        'parent_rate_plan_id',
        'children_fee',
        'infant_fee',
        'max_stay',
        'min_stay_arrival',
        'min_stay_through',
        'closed_to_arrival',
        'closed_to_departure',
        'stop_sell',
        'options',
        'currency',
        'sell_mode',
        'rate_mode',
        'id_channex', // ID devuelto por Channex
    ];

    protected $casts = [
        'max_stay' => 'array',
        'min_stay_arrival' => 'array',
        'min_stay_through' => 'array',
        'closed_to_arrival' => 'array',
        'closed_to_departure' => 'array',
        'stop_sell' => 'array',
        'options' => 'array',
    ];

    public function property()
    {
        return $this->belongsTo(Apartamento::class, 'property_id');
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

}

