<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatePlanOption extends Model
{
    protected $table = 'rate_plan_options';

    protected $fillable = [
        'rate_plan_id',
        'rate',
        'occupancy',
        'is_primary',
        'inherit_rate',
    ];

    public function ratePlan()
    {
        return $this->belongsTo(RatePlan::class, 'rate_plan_id');
    }
}
