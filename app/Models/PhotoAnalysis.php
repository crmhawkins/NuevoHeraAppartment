<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ApartamentoLimpieza;
use App\Models\PhotoCategoria;
use App\Models\User;

class PhotoAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'limpieza_id',
        'categoria_id',
        'empleada_id',
        'image_url',
        'categoria_nombre',
        'calidad_general',
        'puntuacion',
        'cumple_estandares',
        'deficiencias',
        'observaciones',
        'recomendaciones',
        'continuo_bajo_responsabilidad',
        'fecha_analisis',
        'raw_openai_response'
    ];

    protected $casts = [
        'deficiencias' => 'array',
        'recomendaciones' => 'array',
        'cumple_estandares' => 'boolean',
        'continuo_bajo_responsabilidad' => 'boolean',
        'fecha_analisis' => 'datetime',
        'raw_openai_response' => 'array'
    ];

    // Relaciones
    public function limpieza()
    {
        return $this->belongsTo(ApartamentoLimpieza::class, 'limpieza_id');
    }

    public function categoria()
    {
        return $this->belongsTo(PhotoCategoria::class, 'categoria_id');
    }

    public function empleada()
    {
        return $this->belongsTo(User::class, 'empleada_id');
    }

    // Scopes útiles
    public function scopePorLimpieza($query, $limpiezaId)
    {
        return $query->where('limpieza_id', $limpiezaId);
    }

    public function scopePorEmpleada($query, $empleadaId)
    {
        return $query->where('empleada_id', $empleadaId);
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_analisis', $fecha);
    }
}
