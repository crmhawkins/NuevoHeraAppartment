<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MensajeAutoCategoria extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'mensajes_auto_categorias';

    /**
     * Atributos asignados en masa.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        
    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at', 
    ];
}
