<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiarioCaja extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'diario_caja';

    /**
     * Atributos asignados en masa.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'gasto_id',
        'ingreso_id',
        'cuenta_id',
        'formas_pago_id',
        'asiento_contable',
        'tipo',
        'date',
        'concepto',
        'debe',
        'haber',
        
    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at', 
    ];

    
    // Definición de relaciones
    public function cuentaContable()
    {
        return $this->belongsTo(CuentasContable::class, 'cuenta_id', 'numero');
    }

    public function subCuentaContable()
    {
        return $this->belongsTo(SubCuentaContable::class, 'cuenta_id', 'numero');
    }

    public function subCuentaHijo()
    {
        return $this->belongsTo(SubCuentaHijo::class, 'cuenta_id', 'numero');
    }

    /**
     * Intenta encontrar la cuenta asociada en varias tablas y devolver el modelo correspondiente.
     */
    public function cuenta()
    {
        // Intentar encontrar en CuentasContable
        $cuentaContable = $this->cuentaContable;
        if ($cuentaContable && $cuentaContable->numero == $this->cuenta_id) {
            return $cuentaContable;
        }

        // Intentar encontrar en SubCuentaContable
        $subCuentaContable = $this->subCuentaContable;
        if ($subCuentaContable && $subCuentaContable->numero == $this->cuenta_id) {
            return $subCuentaContable;
        }

        // Intentar encontrar en SubCuentaHijo
        $subCuentaHijo = $this->subCuentaHijo;
        if ($subCuentaHijo && $subCuentaHijo->numero == $this->cuenta_id) {
            return $subCuentaHijo;
        }

        // Devolver nulo si no se encuentra ninguna coincidencia
        return;
    }

    public function determineCuenta()
    {
        // Intentar encontrar en CuentasContable
        if ($this->cuentaContable) {
            return $this->cuentaContable;
        }

        // Intentar encontrar en SubCuentaContable
        if ($this->subCuentaContable) {
            return $this->subCuentaContable;
        }

        // Intentar encontrar en SubCuentaHijo
        if ($this->subCuentaHijo) {
            return $this->subCuentaHijo;
        }

        // Devolver nulo si no se encuentra ninguna coincidencia
        return null;
    }
    public function estado(){
        return $this->belongsTo(EstadosDiario::class, 'estado_id');
    }
    
    public function gasto(){
        return $this->belongsTo(\App\Models\Gastos::class, 'gasto_id');
    }
    
    public function ingreso(){
        return $this->belongsTo(\App\Models\Ingresos::class, 'ingreso_id');
    }
}
