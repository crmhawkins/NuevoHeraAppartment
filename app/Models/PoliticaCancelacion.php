<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Translatable;

class PoliticaCancelacion extends Model
{
    use HasFactory, Translatable;

    protected $table = 'politica_cancelaciones';

    protected $fillable = [
        'titulo',
        'contenido',
        'fecha_actualizacion',
        'activo',
    ];

    protected $casts = [
        'fecha_actualizacion' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Obtener la política activa
     */
    public static function activa()
    {
        return static::where('activo', true)->first();
    }
}
