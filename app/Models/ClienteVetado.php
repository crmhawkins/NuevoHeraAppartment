<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [2026-04-19] Registro de veto de cliente ("derecho de admision").
 *
 * Match por num_identificacion OR telefono. Un veto se considera ACTIVO
 * mientras levantado_at sea null. Al levantar, conservamos el registro
 * para historial.
 */
class ClienteVetado extends Model
{
    protected $table = 'clientes_vetados';

    protected $fillable = [
        'num_identificacion',
        'telefono',
        'cliente_id_original',
        'motivo',
        'vetado_por_user_id',
        'vetado_at',
        'levantado_at',
        'levantado_por_user_id',
        'notas_internas',
    ];

    protected $casts = [
        'vetado_at' => 'datetime',
        'levantado_at' => 'datetime',
    ];

    public function clienteOriginal(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id_original');
    }

    public function vetadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vetado_por_user_id');
    }

    public function levantadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'levantado_por_user_id');
    }

    public function scopeActivos($q)
    {
        return $q->whereNull('levantado_at');
    }

    public function estaActivo(): bool
    {
        return $this->levantado_at === null;
    }
}
