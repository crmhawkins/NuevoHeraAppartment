<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reparaciones extends Model
{
    use HasFactory;

    protected $table = 'reparacion';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'direccion',
        'localidad',
        'codigo_postal',
        'provincia',
        'nif_cif',
        'observaciones',
        'activo',
        'lunes',
        'martes',
        'miercoles',
        'jueves',
        'viernes',
        'sabado',
        'domingo',
        'hora_inicio',
        'hora_fin',
        'dias'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'lunes' => 'boolean',
        'martes' => 'boolean',
        'miercoles' => 'boolean',
        'jueves' => 'boolean',
        'viernes' => 'boolean',
        'sabado' => 'boolean',
        'domingo' => 'boolean',
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
     * Relación con servicios técnicos (many-to-many con precios)
     */
    public function servicios()
    {
        return $this->belongsToMany(ServicioTecnico::class, 'tecnico_servicio_precio', 'tecnico_id', 'servicio_id')
                    ->withPivot('precio', 'observaciones', 'activo')
                    ->withTimestamps();
    }

    /**
     * Scope para técnicos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
