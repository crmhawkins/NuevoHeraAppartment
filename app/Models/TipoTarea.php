<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTarea extends Model
{
    use HasFactory;

    protected $table = 'tipos_tareas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'prioridad_base',
        'tiempo_estimado_minutos',
        'dias_max_sin_limpiar',
        'incremento_prioridad_por_dia',
        'prioridad_maxima',
        'requiere_apartamento',
        'requiere_zona_comun',
        'activo',
        'instrucciones'
    ];

    protected $casts = [
        'prioridad_base' => 'integer',
        'tiempo_estimado_minutos' => 'integer',
        'dias_max_sin_limpiar' => 'integer',
        'incremento_prioridad_por_dia' => 'integer',
        'prioridad_maxima' => 'integer',
        'requiere_apartamento' => 'boolean',
        'requiere_zona_comun' => 'boolean',
        'activo' => 'boolean'
    ];

    // Relaciones
    public function tareasAsignadas()
    {
        return $this->hasMany(TareaAsignada::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeLimpiezaApartamentos($query)
    {
        return $query->where('categoria', 'limpieza_apartamento');
    }

    public function scopeLimpiezaZonasComunes($query)
    {
        return $query->where('categoria', 'limpieza_zona_comun');
    }

    public function scopeLimpiezaOficinas($query)
    {
        return $query->where('categoria', 'limpieza_oficina');
    }

    // Métodos
    public function calcularPrioridad($diasSinLimpiar = 0)
    {
        $prioridad = $this->prioridad_base;
        
        if ($this->dias_max_sin_limpiar && $diasSinLimpiar > 0) {
            $incremento = $diasSinLimpiar * $this->incremento_prioridad_por_dia;
            $prioridad = min($this->prioridad_maxima, $prioridad + $incremento);
        }
        
        return $prioridad;
    }

    public function getCategoriaDescripcionAttribute()
    {
        $categorias = [
            'limpieza_apartamento' => 'Limpieza de Apartamento',
            'limpieza_zona_comun' => 'Limpieza de Zona Común',
            'limpieza_oficina' => 'Limpieza de Oficina',
            'preparacion_amenities' => 'Preparación de Amenities',
            'planchado' => 'Planchado',
            'mantenimiento' => 'Mantenimiento',
            'otro' => 'Otro'
        ];
        
        return $categorias[$this->categoria] ?? 'Desconocido';
    }

    public function getTiempoEstimadoFormateadoAttribute()
    {
        $horas = floor($this->tiempo_estimado_minutos / 60);
        $minutos = $this->tiempo_estimado_minutos % 60;
        
        if ($horas > 0 && $minutos > 0) {
            return "{$horas}h {$minutos}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$minutos}m";
        }
    }

    public function necesitaActualizacionPrioridad()
    {
        return $this->dias_max_sin_limpiar !== null && $this->incremento_prioridad_por_dia > 0;
    }
}