<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformeAi extends Model
{
    use HasFactory;

    protected $table = 'informes_ai';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'contenido_md',
        'resumen'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Buscar informe existente para un período
     */
    public static function buscarPorPeriodo($fechaInicio, $fechaFin)
    {
        return self::where('fecha_inicio', $fechaInicio)
                  ->where('fecha_fin', $fechaFin)
                  ->first();
    }
}