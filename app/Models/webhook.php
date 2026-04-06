<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartamento_id',
        'webhook_category_id',
        'event',
        'url',
        'registered',
    ];

    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class);
    }
    public function category()
    {
        return $this->belongsTo(WebhookCategory::class, 'webhook_category_id');
    }
}
