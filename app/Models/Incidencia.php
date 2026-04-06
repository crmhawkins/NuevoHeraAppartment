<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Incidencia extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'titulo',
        'descripcion',
        'tipo',
        'apartamento_id',
        'zona_comun_id',
        'empleada_id',
        'apartamento_limpieza_id',
        'prioridad',
        'estado',
        'fotos',
        'solucion',
        'admin_resuelve_id',
        'fecha_resolucion',
        'observaciones_admin',
        'telefono_cliente',
        'origen',
        'hash_identificador',
        'apartamento_nombre',
        'reserva_id',
        'tecnico_notificado_at',
        'tecnicos_notificados',
        'metodo_notificacion',
        'tecnico_asignado_id'
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fotos' => 'array',
        'fecha_resolucion' => 'datetime',
        'consentimiento_finalizacion' => 'boolean',
    ];

    /**
     * Los atributos que pueden ser nulos.
     *
     * @var array
     */
    protected $nullable = [
        'apartamento_id',
        'zona_comun_id',
        'apartamento_limpieza_id',
        'fotos',
        'solucion',
        'admin_resuelve_id',
        'fecha_resolucion',
        'observaciones_admin'
    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at', 'fecha_resolucion', 'tecnico_notificado_at'
    ];

    /**
     * Relación con Apartamento
     */
    public function apartamento()
    {
        return $this->belongsTo(Apartamento::class, 'apartamento_id');
    }

    /**
     * Relación con Zona Común
     */
    public function zonaComun()
    {
        return $this->belongsTo(ZonaComun::class, 'zona_comun_id');
    }

    /**
     * Relación con Empleada (quien reporta)
     */
    public function empleada()
    {
        return $this->belongsTo(User::class, 'empleada_id');
    }

    /**
     * Relación con Limpieza
     */
    public function limpieza()
    {
        return $this->belongsTo(ApartamentoLimpieza::class, 'apartamento_limpieza_id');
    }

    /**
     * Relación con Admin que resuelve
     */
    public function adminResuelve()
    {
        return $this->belongsTo(User::class, 'admin_resuelve_id');
    }

    /**
     * Relación con Reserva (para incidencias desde WhatsApp)
     */
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Relación con Técnico asignado
     */
    public function tecnicoAsignado()
    {
        return $this->belongsTo(Reparaciones::class, 'tecnico_asignado_id');
    }

    /**
     * Scope para incidencias pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para incidencias urgentes
     */
    public function scopeUrgentes($query)
    {
        return $query->where('prioridad', 'urgente');
    }

    /**
     * Scope para incidencias de hoy
     */
    public function scopeHoy($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Obtener el elemento asociado (apartamento o zona común)
     */
    public function getElementoAttribute()
    {
        if ($this->apartamento) {
            return $this->apartamento;
        }
        return $this->zonaComun;
    }

    /**
     * Obtener el nombre del elemento
     */
    public function getNombreElementoAttribute()
    {
        if ($this->apartamento) {
            return $this->apartamento->nombre;
        }
        if ($this->zonaComun) {
            return $this->zonaComun->nombre;
        }
        return 'N/A';
    }

    /**
     * Obtener el tipo de elemento formateado
     */
    public function getTipoElementoAttribute()
    {
        if ($this->apartamento) {
            return 'Apartamento';
        }
        if ($this->zonaComun) {
            return 'Zona Común';
        }
        return 'N/A';
    }

    /**
     * Verificar si la incidencia es urgente
     */
    public function getEsUrgenteAttribute()
    {
        return $this->prioridad === 'urgente';
    }

    /**
     * Verificar si la incidencia está pendiente
     */
    public function getEstaPendienteAttribute()
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Verificar si fue notificada a técnicos
     */
    public function fueNotificadaATecnicos()
    {
        return $this->tecnico_notificado_at !== null;
    }

    /**
     * Obtener array de IDs de técnicos notificados
     */
    public function getTecnicosNotificadosArrayAttribute()
    {
        if (empty($this->tecnicos_notificados)) {
            return [];
        }
        
        $decoded = json_decode($this->tecnicos_notificados, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Verificar si es incidencia de reparación (desde limpieza)
     */
    public function esReparacion()
    {
        return $this->apartamento_limpieza_id !== null;
    }
}
