<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingresos extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ingresos';

    protected $fillable = [
        'categoria_id',
        'bank_id',
        'title',
        'quantity',
        'date',
        'estado_id'
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
     * Obtener el banco
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function banco()
    {
        return $this->belongsTo(\App\Models\Bancos::class,'bank_id');
    }
    
    /**
     * Obtener la categoria
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function categoria()
    {
        return $this->belongsTo(\App\Models\CategoriaIngresos::class,'categoria_id');
    }
    
    /**
     * Obtener la categoria
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function estado()
    {
        return $this->belongsTo(\App\Models\EstadosIngresos::class,'estado_id');
    }
}
