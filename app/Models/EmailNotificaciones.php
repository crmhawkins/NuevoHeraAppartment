<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailNotificaciones extends Model
{
    use HasFactory;
    use SoftDeletes;

    
    protected $table = 'email_notificaciones';

    protected $fillable = [
        'email',
        'nombre',
        'telefono'
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
