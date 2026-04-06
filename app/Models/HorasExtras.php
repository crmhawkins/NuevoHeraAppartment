<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HorasExtras extends Model
{
    use HasFactory;

    protected $table = 'horas_extras';

    protected $fillable = [
        'user_id',
        'turno_id',
        'fecha',
        'horas_contratadas',
        'horas_trabajadas',
        'horas_extras',
        'motivo',
        'estado',
        'observaciones_admin',
        'aprobado_por',
        'fecha_aprobacion'
    ];

    protected $casts = [
        'fecha' => 'date',
        'horas_contratadas' => 'decimal:2',
        'horas_trabajadas' => 'decimal:2',
        'horas_extras' => 'decimal:2',
        'fecha_aprobacion' => 'datetime'
    ];

    // Estados de las horas extras
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADA = 'aprobada';
    const ESTADO_RECHAZADA = 'rechazada';

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function turno()
    {
        return $this->belongsTo(TurnoTrabajo::class, 'turno_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', self::ESTADO_APROBADA);
    }

    public function scopeRechazadas($query)
    {
        return $query->where('estado', self::ESTADO_RECHAZADA);
    }

    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    // Métodos
    public function getEsPendienteAttribute()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function getEsAprobadaAttribute()
    {
        return $this->estado === self::ESTADO_APROBADA;
    }

    public function getEsRechazadaAttribute()
    {
        return $this->estado === self::ESTADO_RECHAZADA;
    }

    public function getHorasExtrasFormateadasAttribute()
    {
        $horas = floor($this->horas_extras);
        $minutos = round(($this->horas_extras - $horas) * 60);
        
        if ($horas > 0 && $minutos > 0) {
            return "{$horas}h {$minutos}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$minutos}m";
        }
    }

    public function getHorasTrabajadasFormateadasAttribute()
    {
        $horas = floor($this->horas_trabajadas);
        $minutos = round(($this->horas_trabajadas - $horas) * 60);
        
        if ($horas > 0 && $minutos > 0) {
            return "{$horas}h {$minutos}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$minutos}m";
        }
    }

    public function getHorasContratadasFormateadasAttribute()
    {
        $horas = floor($this->horas_contratadas);
        $minutos = round(($this->horas_contratadas - $horas) * 60);
        
        if ($horas > 0 && $minutos > 0) {
            return "{$horas}h {$minutos}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$minutos}m";
        }
    }

    // Método para aprobar horas extras
    public function aprobar($aprobadoPor, $observaciones = null)
    {
        $this->update([
            'estado' => self::ESTADO_APROBADA,
            'aprobado_por' => $aprobadoPor,
            'fecha_aprobacion' => now(),
            'observaciones_admin' => $observaciones
        ]);
    }

    // Método para rechazar horas extras
    public function rechazar($aprobadoPor, $observaciones = null)
    {
        $this->update([
            'estado' => self::ESTADO_RECHAZADA,
            'aprobado_por' => $aprobadoPor,
            'fecha_aprobacion' => now(),
            'observaciones_admin' => $observaciones
        ]);
    }

    // Método estático para crear horas extras automáticamente
    public static function crearDesdeTurno(TurnoTrabajo $turno, $motivo = null)
    {
        // Obtener horas contratadas de la empleada
        $empleadaHorario = EmpleadaHorario::where('user_id', $turno->user_id)->first();
        $horasContratadas = $empleadaHorario ? $empleadaHorario->horas_contratadas_dia : 8.0;
        
        $horasTrabajadas = $turno->horas_trabajadas ?? 0;
        
        // Solo crear si hay horas extras
        if ($horasTrabajadas > $horasContratadas) {
            $horasExtras = $horasTrabajadas - $horasContratadas;
            
            return self::create([
                'user_id' => $turno->user_id,
                'turno_id' => $turno->id,
                'fecha' => $turno->fecha,
                'horas_contratadas' => $horasContratadas,
                'horas_trabajadas' => $horasTrabajadas,
                'horas_extras' => $horasExtras,
                'motivo' => $motivo ?? 'Trabajo adicional requerido',
                'estado' => self::ESTADO_PENDIENTE
            ]);
        }
        
        return null;
    }
}