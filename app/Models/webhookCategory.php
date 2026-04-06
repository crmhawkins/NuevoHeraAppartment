<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }
}
