<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactoWeb extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contactos_web';

    protected $fillable = [
        'nombre',
        'email',
        'asunto',
        'mensaje',
        'leido',
        'leido_at',
        'leido_por',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'leido' => 'boolean',
        'leido_at' => 'datetime',
    ];

    public function leidoPor()
    {
        return $this->belongsTo(User::class, 'leido_por');
    }

    public function marcarComoLeido($userId = null)
    {
        $this->update([
            'leido' => true,
            'leido_at' => now(),
            'leido_por' => $userId ?? auth()->id(),
        ]);
    }
}
