<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = ['contenido'];
    protected $casts = ['contenido' => 'array'];
}

