<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $table = 'room_types';

    protected $fillable = [
        'title',
        'property_id',
        'count_of_rooms',
        'occ_adults',
        'occ_children',
        'occ_infants',
        'default_occupancy',
        'facilities',
        'room_kind',
        'capacity',
        'description',
        'photos',
        'id_channex',
    ];

    protected $casts = [
        'facilities' => 'array',
        'photos' => 'array',
    ];

    public function property()
    {
        return $this->belongsTo(Apartamento::class, 'property_id');
    }
}
