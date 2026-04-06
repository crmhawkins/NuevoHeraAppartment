<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gastos extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gastos';

    protected $fillable = [
        'categoria_id',
        'bank_id',
        'is_apartamento',
        'title',
        'quantity',
        'date',
        'factura_foto',
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
        return $this->belongsTo(\App\Models\CategoriaGastos::class,'categoria_id');
    }
    
    /**
     * Obtener la categoria
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function estado()
    {
        return $this->belongsTo(\App\Models\EstadosGastos::class,'estado_id');
    }
    
}
