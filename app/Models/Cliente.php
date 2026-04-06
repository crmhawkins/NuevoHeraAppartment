<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Cliente extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

      /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'is_null',
        'alias',
        'nombre',
        'apellido1',
        'apellido2',
        'nacionalidad',
        'tipo_documento',
        'tipo_documento_str',
        'num_identificacion',
        'numero_soporte_documento', // Nuevo campo
        'fecha_expedicion_doc',
        'fecha_nacimiento',
        'sexo',
        'sexo_str',
        'telefono',
        'telefono_movil', // Nuevo campo
        'email',
        'identificador',
        'idioma',
        'idiomas',
        'idioma_establecido',
        'inactivo',
        'email_secundario',
        'password',
        'remember_token',
        'password_set_at',
        'stripe_customer_id',
        'stripe_payment_methods',
        'nacionalidadStr',
        'nacionalidadCode',
        'direccion',
        'localidad',
        'codigo_postal',
        'provincia',
        'estado',
        'data_dni', // Indica si el DNI ha sido entregado/verificado
        'lugar_nacimiento', // Lugar de nacimiento
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
        // Campos de facturación
        'facturacion_nombre_razon_social',
        'facturacion_nif_cif',
        'facturacion_direccion',
        'facturacion_localidad',
        'facturacion_codigo_postal',
        'facturacion_provincia',
        'facturacion_pais',
        'facturacion_email',
        'facturacion_telefono',
        'es_empresa',
        'tipo_cliente',
        'requiere_factura',
        'condiciones_pago',
        'observaciones_facturacion',
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
        'fecha_nacimiento', 'fecha_expedicion_doc',
        'fecha_firma_contrato', 'fecha_hora_entrada', 'fecha_hora_salida',
        'fecha_caducidad_tarjeta', 'fecha_pago',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts para campos específicos
     */
    protected $casts = [
        'is_null' => 'boolean',
        'es_empresa' => 'boolean',
        'requiere_factura' => 'boolean',
        'password_set_at' => 'datetime',
        'stripe_payment_methods' => 'array',
        'fecha_nacimiento' => 'date',
        'fecha_expedicion_doc' => 'date',
    ];

    /**
     * Obtener el email principal del cliente (no de booking)
     * Prioridad: email (si no es de booking) > email_secundario (si no es de booking)
     */
    public function getEmailPrincipalAttribute()
    {
        // Verificar si el email principal no es de booking y no es null
        if ($this->email && !$this->esEmailBooking($this->email)) {
            return $this->email;
        }
        
        // Si el email secundario no es de booking y no es null
        if ($this->email_secundario && !$this->esEmailBooking($this->email_secundario)) {
            return $this->email_secundario;
        }
        
        // Fallback al email principal aunque sea de booking
        return $this->email;
    }

    /**
     * Verificar si un email es de booking
     */
    public function esEmailBooking($email)
    {
        if (!$email) {
            return false;
        }
        
        $dominiosBooking = ['booking.com', 'expedia.com', 'airbnb.com', 'tripadvisor.com'];
        $dominio = substr(strrchr($email, "@"), 1);
        
        return in_array(strtolower($dominio), $dominiosBooking);
    }

    /**
     * Verificar si el cliente tiene password establecido
     */
    public function tienePassword()
    {
        return !empty($this->password);
    }

    /**
     * Buscar cliente por email o teléfono
     */
    public static function buscarPorCredenciales($identificador)
    {
        // Primero intentar por email principal o secundario
        $cliente = static::where(function($query) use ($identificador) {
            $query->where('email', $identificador)
                  ->orWhere('email_secundario', $identificador);
        })->first();
        
        // Si no se encuentra, intentar por teléfono
        if (!$cliente) {
            $cliente = static::where(function($query) use ($identificador) {
                $query->where('telefono', $identificador)
                      ->orWhere('telefono_movil', $identificador)
                      ->orWhere('telefono2', $identificador);
            })->first();
        }
        
        return $cliente;
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Obtiene el nombre completo para facturación
     * Para particulares: usa nombre + apellidos
     * Para empresas/autónomos: usa facturacion_nombre_razon_social si existe, sino nombre + apellidos
     */
    public function getNombreFacturacionAttribute()
    {
        // Para particulares, siempre usar nombre + apellidos
        if ($this->tipo_cliente === 'particular') {
            return trim($this->nombre . ' ' . $this->apellido1 . ' ' . $this->apellido2);
        }
        
        // Para empresas y autónomos, usar razón social si existe
        if ($this->facturacion_nombre_razon_social) {
            return $this->facturacion_nombre_razon_social;
        }
        
        // Fallback a nombre + apellidos
        return trim($this->nombre . ' ' . $this->apellido1 . ' ' . $this->apellido2);
    }

    /**
     * Obtiene el NIF/CIF para facturación
     * Para particulares: usa el documento de identidad
     * Para empresas/autónomos: usa facturacion_nif_cif si existe, sino el documento general
     */
    public function getNifFacturacionAttribute()
    {
        // Para particulares, siempre usar el documento de identidad
        if ($this->tipo_cliente === 'particular') {
            return $this->num_identificacion;
        }
        
        // Para empresas y autónomos, usar facturacion_nif_cif si existe
        if ($this->facturacion_nif_cif) {
            return $this->facturacion_nif_cif;
        }
        
        // Fallback al documento general
        return $this->num_identificacion;
    }

    /**
     * Obtiene la dirección completa para facturación
     * Si tiene datos específicos de facturación, los usa, sino usa los datos generales
     */
    public function getDireccionFacturacionAttribute()
    {
        if ($this->facturacion_direccion) {
            $direccion = $this->facturacion_direccion;
            if ($this->facturacion_localidad) {
                $direccion .= ', ' . $this->facturacion_localidad;
            }
            if ($this->facturacion_codigo_postal) {
                $direccion .= ', ' . $this->facturacion_codigo_postal;
            }
            if ($this->facturacion_provincia) {
                $direccion .= ', ' . $this->facturacion_provincia;
            }
            if ($this->facturacion_pais) {
                $direccion .= ', ' . $this->facturacion_pais;
            }
            return $direccion;
        }
        
        // Fallback a datos generales
        $direccion = $this->direccion ?? '';
        if ($this->localidad) {
            $direccion .= ($direccion ? ', ' : '') . $this->localidad;
        }
        if ($this->codigo_postal) {
            $direccion .= ($direccion ? ', ' : '') . $this->codigo_postal;
        }
        if ($this->provincia) {
            $direccion .= ($direccion ? ', ' : '') . $this->provincia;
        }
        if ($this->nacionalidad) {
            $direccion .= ($direccion ? ', ' : '') . $this->nacionalidad;
        }
        
        return $direccion;
    }

    /**
     * Obtiene el email para facturación
     * Si tiene email específico de facturación, lo usa, sino usa el email general
     */
    public function getEmailFacturacionAttribute()
    {
        return $this->facturacion_email ?: $this->email;
    }

    /**
     * Obtiene el teléfono para facturación
     * Si tiene teléfono específico de facturación, lo usa, sino usa el teléfono general
     */
    public function getTelefonoFacturacionAttribute()
    {
        return $this->facturacion_telefono ?: $this->telefono;
    }

    /**
     * Verifica si el cliente tiene todos los datos necesarios para facturación
     * Para particulares: solo necesita tipo_cliente y documento de identidad
     * Para empresas/autónomos: necesita tipo_cliente, NIF/CIF y datos específicos
     */
    public function tieneDatosFacturacionCompletos()
    {
        // Verificar que tenga tipo_cliente
        if (!$this->tipo_cliente) {
            return false;
        }

        // Para particulares: solo necesita documento de identidad
        if ($this->tipo_cliente === 'particular') {
            return !empty($this->num_identificacion);
        }

        // Para empresas y autónomos: necesita NIF/CIF específico y al menos nombre/razón social
        if ($this->tipo_cliente === 'empresa' || $this->tipo_cliente === 'autonomo') {
            return !empty($this->facturacion_nif_cif) && !empty($this->facturacion_nombre_razon_social);
        }

        return false;
    }

    /**
     * Obtiene los datos de facturación en formato array para usar en facturas
     */
    public function getDatosFacturacion()
    {
        return [
            'nombre_razon_social' => $this->nombre_facturacion,
            'nif_cif' => $this->nif_facturacion,
            'direccion' => $this->direccion_facturacion,
            'email' => $this->email_facturacion,
            'telefono' => $this->telefono_facturacion,
            'es_empresa' => $this->es_empresa,
            'tipo_cliente' => $this->tipo_cliente,
            'condiciones_pago' => $this->condiciones_pago,
            'observaciones' => $this->observaciones_facturacion,
        ];
    }

    /**
     * Obtiene los datos de facturación formateados para el PDF
     * Incluye información adicional como tipo de documento y etiquetas
     */
    public function getDatosFacturacionParaPDF()
    {
        $datos = $this->getDatosFacturacion();
        
        // Agregar información adicional para el PDF
        $datos['tipo_documento_label'] = $this->getTipoDocumentoLabel();
        $datos['direccion_completa'] = $this->direccion_facturacion;
        $datos['es_particular'] = $this->tipo_cliente === 'particular';
        $datos['es_empresa'] = $this->tipo_cliente === 'empresa';
        $datos['es_autonomo'] = $this->tipo_cliente === 'autonomo';
        
        return $datos;
    }

    /**
     * Obtiene la etiqueta del tipo de documento para el PDF
     */
    private function getTipoDocumentoLabel()
    {
        if ($this->tipo_cliente === 'particular') {
            return $this->tipo_documento != 'P' ? 'DNI:' : 'PASAPORTE:';
        } elseif ($this->tipo_cliente === 'empresa') {
            return 'CIF:';
        } elseif ($this->tipo_cliente === 'autonomo') {
            return 'NIF:';
        }
        
        return 'DOCUMENTO:';
    }

}
