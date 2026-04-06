<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatGpt extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'whatsapp_mensaje_chatgpt';

    /**
     * Atributos asignados en masa.
     *
     * @var array
     */
    protected $fillable = [
        'id_mensaje',
        'id_three',
        'remitente',
        'mensaje',
        'respuesta',
        'status',
        'status_mensaje',
        'type',
        'date',
        'estado_id',
        'reserva_id'

    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function whatsappMensaje()
    {
        return $this->belongsTo(WhatsappMensaje::class, 'id_mensaje', 'mensaje_id');
    }

}
