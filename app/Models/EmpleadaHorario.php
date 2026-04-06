<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpleadaHorario extends Model
{
    use HasFactory;

    protected $table = 'empleada_horarios';

    protected $fillable = [
        'user_id',
        'horas_contratadas_dia',
        'dias_libres_semana',
        'hora_inicio_atencion',
        'hora_fin_atencion',
        'lunes',
        'martes',
        'miercoles',
        'jueves',
        'viernes',
        'sabado',
        'domingo',
        'activo',
        'observaciones'
    ];

    protected $casts = [
        'horas_contratadas_dia' => 'integer',
        'dias_libres_semana' => 'integer',
        'hora_inicio_atencion' => 'datetime:H:i',
        'hora_fin_atencion' => 'datetime:H:i',
        'lunes' => 'boolean',
        'martes' => 'boolean',
        'miercoles' => 'boolean',
        'jueves' => 'boolean',
        'viernes' => 'boolean',
        'sabado' => 'boolean',
        'domingo' => 'boolean',
        'activo' => 'boolean'
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function turnos()
    {
        return $this->hasMany(TurnoTrabajo::class, 'user_id', 'user_id');
    }

    public function diasLibres()
    {
        return $this->hasMany(EmpleadaDiasLibres::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDisponiblesHoy($query)
    {
        $diaSemana = now()->dayOfWeek;
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
        
        return $query->where('activo', true)
                    ->where($columnaDia, true);
    }

    // Métodos
    public function estaDisponibleHoy()
    {
        $diaSemana = now()->dayOfWeek;
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
        
        return $this->activo && $this->$columnaDia;
    }

    public function getDiasTrabajoAttribute()
    {
        $dias = [];
        if ($this->lunes) $dias[] = 'Lunes';
        if ($this->martes) $dias[] = 'Martes';
        if ($this->miercoles) $dias[] = 'Miércoles';
        if ($this->jueves) $dias[] = 'Jueves';
        if ($this->viernes) $dias[] = 'Viernes';
        if ($this->sabado) $dias[] = 'Sábado';
        if ($this->domingo) $dias[] = 'Domingo';
        
        return implode(', ', $dias);
    }

    public function getNumeroDiasTrabajoAttribute()
    {
        return ($this->lunes ? 1 : 0) + 
               ($this->martes ? 1 : 0) + 
               ($this->miercoles ? 1 : 0) + 
               ($this->jueves ? 1 : 0) + 
               ($this->viernes ? 1 : 0) + 
               ($this->sabado ? 1 : 0) + 
               ($this->domingo ? 1 : 0);
    }

    public function getHorarioAtencionAttribute()
    {
        return $this->hora_inicio_atencion->format('H:i') . ' - ' . $this->hora_fin_atencion->format('H:i');
    }

    // Mutator para calcular automáticamente días libres por semana
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Calcular días libres por semana (7 - días de trabajo)
            $diasTrabajo = ($model->lunes ? 1 : 0) + 
                          ($model->martes ? 1 : 0) + 
                          ($model->miercoles ? 1 : 0) + 
                          ($model->jueves ? 1 : 0) + 
                          ($model->viernes ? 1 : 0) + 
                          ($model->sabado ? 1 : 0) + 
                          ($model->domingo ? 1 : 0);
            
            $model->dias_libres_semana = 7 - $diasTrabajo;
        });
    }
}