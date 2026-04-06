<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubGrupoContable extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sub_grupo_contable';

    /**
     * Atributos asignados en masa.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'grupo_id',
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


    /**
     * Obtener el Grupo
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function grupo()
    {
        return $this->belongsTo(GrupoContable::class,'grupo_id');
    }
    public function cuentas()
    {
        return $this->hasMany(CuentasContable::class, 'sub_grupo_id')->orderBy('numero', 'asc');
    }
    public function subCuentas()
    {
        return $this->hasMany(SubCuentaContable::class, 'cuenta_id');
    }
}
