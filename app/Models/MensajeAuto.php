<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MensajeAuto extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'mensajes_auto';

    /**
     * Atributos asignados en masa.
     *
     * @var array
     */
    protected $fillable = [
        'reserva_id',
        'cliente_id',
        'categoria_id',
        'fecha_envio',
        
    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at', 'fecha_envio'
    ];

    /**
     * Obtener el usuario
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function categoria()
    {
        return $this->belongsTo(\App\Models\MensajeAutoCategoria::class,'categoria_id');
    }
}
