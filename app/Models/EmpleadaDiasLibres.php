<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmpleadaDiasLibres extends Model
{
    use HasFactory;

    protected $table = 'empleada_dias_libres';

    protected $fillable = [
        'empleada_horario_id',
        'semana_inicio',
        'dias_libres',
        'observaciones'
    ];

    protected $casts = [
        'semana_inicio' => 'date',
        'dias_libres' => 'array'
    ];

    // Relaciones
    public function empleadaHorario()
    {
        return $this->belongsTo(EmpleadaHorario::class);
    }

    // Métodos
    public function getDiasLibresNombresAttribute()
    {
        $nombresDias = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado'
        ];

        return collect($this->dias_libres)->map(function($dia) use ($nombresDias) {
            return $nombresDias[$dia] ?? 'Día ' . $dia;
        })->implode(', ');
    }

    public function getSemanaFinAttribute()
    {
        return $this->semana_inicio->copy()->addDays(6);
    }

    public function getRangoSemanaAttribute()
    {
        return $this->semana_inicio->format('d/m/Y') . ' - ' . $this->semana_fin->format('d/m/Y');
    }

    // Scopes
    public function scopeParaSemana($query, $fecha)
    {
        $lunesSemana = Carbon::parse($fecha)->startOfWeek();
        return $query->where('semana_inicio', $lunesSemana);
    }

    public function scopeParaEmpleada($query, $empleadaHorarioId)
    {
        return $query->where('empleada_horario_id', $empleadaHorarioId);
    }

    // Métodos estáticos
    public static function obtenerDiasLibresParaEmpleada($empleadaHorarioId, $fecha)
    {
        $lunesSemana = Carbon::parse($fecha)->startOfWeek();
        
        $diasLibres = self::paraEmpleada($empleadaHorarioId)
            ->paraSemana($fecha)
            ->first();

        return $diasLibres ? $diasLibres->dias_libres : [];
    }

    public static function estaDisponibleEnFecha($empleadaHorarioId, $fecha)
    {
        $diasLibres = self::obtenerDiasLibresParaEmpleada($empleadaHorarioId, $fecha);
        
        // Si no hay días libres específicos configurados para esta semana,
        // usar la configuración general de días de trabajo
        if (empty($diasLibres)) {
            $empleada = EmpleadaHorario::find($empleadaHorarioId);
            if (!$empleada) {
                return false;
            }
            
            // Verificar si trabaja ese día según su configuración general
            $diaSemana = $fecha->dayOfWeek;
            $diasColumnas = [
                1 => 'lunes',
                2 => 'martes', 
                3 => 'miercoles',
                4 => 'jueves',
                5 => 'viernes',
                6 => 'sabado',
                0 => 'domingo'
            ];
            
            $columnaDia = $diasColumnas[$diaSemana] ?? 'lunes';
            return $empleada->$columnaDia;
        }
        
        // Si hay días libres específicos configurados, usar esa configuración
        return !in_array($fecha->dayOfWeek, $diasLibres);
    }
}