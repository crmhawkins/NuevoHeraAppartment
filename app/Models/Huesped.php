<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Huesped extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'huespedes';

      /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reserva_id',
        'cliente_comprador_id',
        'nombre',
        'primer_apellido',
        'segundo_apellido',
        'fecha_nacimiento',
        'lugar_nacimiento', // Lugar de nacimiento (opcional pero recomendado)
        'pais',
        'tipo_documento',
        'tipo_documento_str',
        'numero_identificacion',
        'numero_soporte_documento', // Nuevo campo
        'fecha_expedicion',
        'fecha_caducidad', // Fecha de caducidad del documento (obligatorio para verificar validez)
        'sexo',
        'sexo_str',
        'email',
        'telefono_movil', // Nuevo campo
        'direccion',
        'localidad',
        'codigo_postal',
        'provincia',
        'relacion_parentesco', // Nuevo campo
        'numero_referencia_contrato', // Nuevo campo
        'fecha_firma_contrato', // Nuevo campo
        'fecha_hora_entrada', // Nuevo campo
        'fecha_hora_salida', // Nuevo campo
        'numero_habitaciones', // Nuevo campo
        'conexion_internet', // Nuevo campo
        'tipo_pago', // Nuevo campo
        'identificacion_medio_pago', // Nuevo campo
        'titular_medio_pago', // Nuevo campo
        'fecha_caducidad_tarjeta', // Nuevo campo
        'fecha_pago', // Nuevo campo
        'nacionalidadStr',
        'nacionalidadCode',
        'nacionalidad',
        // Campos para plataforma del estado
        'pais_iso3',
        'codigo_municipio_ine',
        'nombre_municipio',
        'telefono2',
    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at',
        'fecha_nacimiento',
        'fecha_expedicion',
        'fecha_caducidad',
        'fecha_firma_contrato',
        'fecha_hora_entrada',
        'fecha_hora_salida',
        'fecha_caducidad_tarjeta',
        'fecha_pago',
    ];

    /**
     * Relación con Reserva
     */
    public function reserva()
    {
        return $this->belongsTo(\App\Models\Reserva::class, 'reserva_id');
    }

    /**
     * Relación con Photos
     */
    public function photos()
    {
        return $this->hasMany(\App\Models\Photo::class, 'huespedes_id');
    }
    
    /**
     * Relación con Cliente Comprador
     */
    public function clienteComprador()
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_comprador_id');
    }
}
