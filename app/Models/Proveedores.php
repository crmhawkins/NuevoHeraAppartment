<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedores extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'proveedores';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'dni',
        'cif',
        'email',
        'pais',
        'ciudad',
        'provincia',
        'direccion',
        'zipcode',
        'work_activity',
        'fax',
        'phone',
        'web',
        'facebook',
        'twitter',
        'linkedin',
        'instagram',
        'pinterest',
        'note'
        
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
