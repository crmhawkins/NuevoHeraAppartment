<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrupoContable extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'grupo_contable';

    /**
     * Atributos asignados en masa.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'numero',
        'nombre',
        'descripcion'
        
    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at', 
    ];

    public function subGrupos()
    {
        return $this->hasMany(SubGrupoContable::class, 'grupo_id')->orderBy('numero', 'asc');
    }
}
