<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensajeChat extends Model
{
    use HasFactory;

    protected $table = 'mensajes';

    protected $fillable = [
        'channex_message_id',
        'booking_id',
        'thread_id',
        'property_id',
        'sender',
        'message',
        'attachments',
        'have_attachment',
        'received_at',
        'openai_thread_id'
    ];

    protected $casts = [
        'attachments' => 'array',
        'received_at' => 'datetime',
    ];
}
