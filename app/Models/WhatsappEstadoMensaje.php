<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappEstadoMensaje extends Model
{
    protected $fillable = [
        'whatsapp_mensaje_id',
        'estado',
        'recipient_id',
        'fecha_estado'
    ];

    protected $casts = [
        'fecha_estado' => 'datetime',
    ];

    public function mensaje()
    {
        return $this->belongsTo(WhatsappMensaje::class, 'whatsapp_mensaje_id');
    }
}
