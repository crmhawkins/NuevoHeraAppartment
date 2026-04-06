<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Whatsapp extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'whatsapp';

    /**
     * Atributos asignados en masa.
     *
     * @var array
     */
    protected $fillable = [
        'mensaje'
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
