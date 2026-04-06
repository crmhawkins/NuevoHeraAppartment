<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\HasLocalTimezone;

class TurnoTrabajo extends Model
{
    use HasFactory, HasLocalTimezone;

    protected $table = 'turnos_trabajo';

    protected $fillable = [
        'fecha',
        'user_id',
        'hora_inicio',
        'hora_fin',
        'horas_trabajadas',
        'estado',
        'observaciones',
        'fecha_creacion',
        'fecha_inicio_real',
        'fecha_fin_real'
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
        'horas_trabajadas' => 'integer',
        'fecha_creacion' => 'datetime',
        'fecha_inicio_real' => 'datetime',
        'fecha_fin_real' => 'datetime'
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tareasAsignadas()
    {
        return $this->hasMany(TareaAsignada::class, 'turno_id');
    }

    public function empleadaHorario()
    {
        return $this->hasOne(EmpleadaHorario::class, 'user_id', 'user_id');
    }

    // Scopes
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopePorEmpleada($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActivos($query)
    {
        return $query->whereIn('estado', ['programado', 'en_progreso']);
    }

    public function scopeCompletados($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha', today());
    }

    // Métodos
    public function iniciarTurno()
    {
        $this->update([
            'estado' => 'en_progreso',
            'fecha_inicio_real' => now()
        ]);
    }

    public function finalizarTurno($horasTrabajadas = null)
    {
        $this->update([
            'estado' => 'completado',
            'fecha_fin_real' => now(),
            'horas_trabajadas' => $horasTrabajadas ?? $this->calcularHorasTrabajadas()
        ]);
    }

    public function calcularHorasTrabajadas()
    {
        if ($this->fecha_inicio_real && $this->fecha_fin_real) {
            $inicio = Carbon::parse($this->fecha_inicio_real);
            $fin = Carbon::parse($this->fecha_fin_real);
            return $fin->diffInHours($inicio);
        }
        
        return 0;
    }

    public function getTareasCompletadasAttribute()
    {
        return $this->tareasAsignadas()->where('estado', 'completada')->count();
    }

    public function getTareasPendientesAttribute()
    {
        return $this->tareasAsignadas()->whereIn('estado', ['pendiente', 'en_progreso'])->count();
    }

    public function getTotalTareasAttribute()
    {
        return $this->tareasAsignadas()->count();
    }

    public function getProgresoAttribute()
    {
        if ($this->total_tareas == 0) return 0;
        return round(($this->tareas_completadas / $this->total_tareas) * 100, 2);
    }

    public function getTiempoEstimadoTotalAttribute()
    {
        // Obtener jornada contratada de la empleada
        $empleadaHorario = EmpleadaHorario::where('user_id', $this->user_id)->first();
        $jornadaContratadaMinutos = $empleadaHorario ? $empleadaHorario->horas_contratadas_dia * 60 : 480; // Default 8h
        
        // Calcular tiempo total de tareas asignadas
        $tiempoTotalTareas = $this->tareasAsignadas()
            ->join('tipos_tareas', 'tareas_asignadas.tipo_tarea_id', '=', 'tipos_tareas.id')
            ->sum('tipos_tareas.tiempo_estimado_minutos');
        
        // Retornar el menor entre tiempo total y jornada contratada
        return min($tiempoTotalTareas, $jornadaContratadaMinutos);
    }
    
    public function getTiempoRealAsignadoAttribute()
    {
        // Tiempo real de todas las tareas asignadas (puede sobrepasar jornada)
        return $this->tareasAsignadas()
            ->join('tipos_tareas', 'tareas_asignadas.tipo_tarea_id', '=', 'tipos_tareas.id')
            ->sum('tipos_tareas.tiempo_estimado_minutos');
    }
    
    public function getJornadaContratadaAttribute()
    {
        $empleadaHorario = EmpleadaHorario::where('user_id', $this->user_id)->first();
        return $empleadaHorario ? $empleadaHorario->horas_contratadas_dia * 60 : 480; // Default 8h
    }
    
    public function getSobrepasaJornadaAttribute()
    {
        return $this->tiempo_real_asignado > $this->jornada_contratada;
    }

    public function getTiempoRealTotalAttribute()
    {
        return $this->tareasAsignadas()->sum('tiempo_real_minutos');
    }

    public function getTiempoEstimadoTotalFormateadoAttribute()
    {
        $minutos = $this->tiempo_estimado_total;
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        
        if ($horas > 0 && $mins > 0) {
            return "{$horas}h {$mins}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$mins}m";
        }
    }
    
    public function getTiempoRealAsignadoFormateadoAttribute()
    {
        $minutos = $this->tiempo_real_asignado;
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        
        if ($horas > 0 && $mins > 0) {
            return "{$horas}h {$mins}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$mins}m";
        }
    }

    public function getTiempoRealTotalFormateadoAttribute()
    {
        $minutos = $this->tiempo_real_total;
        if (!$minutos) return 'No completado';
        
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        
        if ($horas > 0 && $mins > 0) {
            return "{$horas}h {$mins}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$mins}m";
        }
    }

    public function estaEnProgreso()
    {
        return $this->estado === 'en_progreso';
    }

    public function estaCompletado()
    {
        return $this->estado === 'completado';
    }

    public function estaProgramado()
    {
        return $this->estado === 'programado';
    }
}