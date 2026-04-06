<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LimpiezaFondo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'limpieza_fondo';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'apartamento_id',
        'fecha',
       
         
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
     * Obtener el usuario
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function apartamentoName()
    {
        return $this->belongsTo(\App\Models\Apartamento::class,'apartamento_id');
    }
}
