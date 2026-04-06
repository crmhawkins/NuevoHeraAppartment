<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'photos';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'limpieza_id',
        'photo_categoria_id',
        'descripcion',
        'url',
        'cliente_id',
        'reserva_id',
        'requirement_id',
        'huespedes_id'
    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at', 
    ];


    public function categoria()
    {
        return $this->belongsTo(\App\Models\PhotoCategoria::class,'photo_categoria_id');
    }

    // Define la relación con ApartamentoLimpieza
    public function apartamentoLimpieza()
    {
        return $this->belongsTo(ApartamentoLimpieza::class, 'limpieza_id');
    }

    // Modelo Photo
    public function checklistRequirement()
    {
        return $this->belongsTo(ChecklistPhotoRequirement::class, 'requirement_id'); // Asumiendo que el modelo relacionado es ChecklistRequirement
    }


}
