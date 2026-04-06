<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMensaje extends Model
{
    protected $fillable = [
        'mensaje_id',
        'tipo',
        'contenido',
        'remitente',
        'estado',
        'recipient_id',
        'fecha_mensaje',
        'metadata',
        'conversacion_id',
        'origen_conversacion',
        'expiracion_conversacion',
        'billable',
        'categoria_precio',
        'modelo_precio',
        'errores'
    ];

    protected $casts = [
        'metadata' => 'array',
        'errores' => 'array',
        'expiracion_conversacion' => 'datetime',
        'fecha_mensaje' => 'datetime',
        'billable' => 'boolean',
    ];

    public function chatGpt()
    {
        return $this->hasOne(ChatGpt::class);
    }

    public function estados()
    {
        return $this->hasMany(WhatsappEstadoMensaje::class);
    }

    public function respuestaA()
{
    return $this->belongsTo(WhatsappMensaje::class, 'reply_to_id');
}

public function respuestas()
{
    return $this->hasMany(WhatsappMensaje::class, 'reply_to_id');
}

}
