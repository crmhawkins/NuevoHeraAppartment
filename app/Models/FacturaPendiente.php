<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaPendiente extends Model
{
    protected $table = 'facturas_pendientes';

    protected $fillable = [
        'filename',
        'storage_path',
        'size_bytes',
        'mime_type',
        'status',
        'importe_detectado',
        'fecha_detectada',
        'proveedor_detectado',
        'numero_factura_detectado',
        'concepto_detectado',
        'confianza_ia',
        'ia_raw_response',
        'gasto_id',
        'candidatos_gasto_ids',
        'error_message',
        'intentos',
        'last_attempt_at',
        'resolved_at',
        'uploaded_from',
        'uploaded_by_ip',
    ];

    protected $casts = [
        'importe_detectado' => 'decimal:2',
        'fecha_detectada' => 'date',
        'confianza_ia' => 'decimal:2',
        'ia_raw_response' => 'array',
        'candidatos_gasto_ids' => 'array',
        'last_attempt_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // ----- Scopes -----

    public function scopePendientes($q)
    {
        return $q->whereIn('status', ['pendiente', 'espera']);
    }

    public function scopeEnEspera($q)
    {
        return $q->where('status', 'espera');
    }

    public function scopeConError($q)
    {
        return $q->where('status', 'error');
    }

    public function scopeAsociadas($q)
    {
        return $q->where('status', 'asociada');
    }

    // ----- Relaciones -----

    public function gasto()
    {
        return $this->belongsTo(\App\Models\Gastos::class, 'gasto_id');
    }
}
