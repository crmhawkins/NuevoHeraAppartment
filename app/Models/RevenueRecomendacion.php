<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recomendación de precio por apartamento × fecha.
 * Una fila por apartamento+fecha. Se reescribe cada cálculo nocturno
 * (UPSERT), pero se conserva precio_aplicado/aplicado_at.
 */
class RevenueRecomendacion extends Model
{
    use HasFactory;

    protected $table = 'revenue_recomendaciones';

    public $timestamps = false; // solo calculado_at

    protected $fillable = [
        'apartamento_id',
        'fecha',
        'precio_actual',
        'precio_recomendado',
        'precio_aplicado',
        'aplicado_at',
        'aplicado_por_user_id',
        'competencia_media',
        'competencia_min',
        'competencia_max',
        'competidores_count',
        'ocupacion_nuestra_pct',
        'es_finde',
        'es_festivo',
        'razonamiento',
        'calculado_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'precio_actual' => 'decimal:2',
        'precio_recomendado' => 'decimal:2',
        'precio_aplicado' => 'decimal:2',
        'competencia_media' => 'decimal:2',
        'competencia_min' => 'decimal:2',
        'competencia_max' => 'decimal:2',
        'ocupacion_nuestra_pct' => 'decimal:2',
        'es_finde' => 'boolean',
        'es_festivo' => 'boolean',
        'aplicado_at' => 'datetime',
        'calculado_at' => 'datetime',
    ];

    public function apartamento(): BelongsTo
    {
        return $this->belongsTo(Apartamento::class);
    }

    public function aplicadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\User::class, 'aplicado_por_user_id');
    }

    /**
     * Diferencia porcentual entre precio actual y recomendado.
     * Positivo = recomendamos subir.
     */
    public function getCambioPctAttribute(): ?float
    {
        if (!$this->precio_actual || $this->precio_actual == 0) {
            return null;
        }
        return round((($this->precio_recomendado - $this->precio_actual) / $this->precio_actual) * 100, 2);
    }

    /**
     * Estado para la UI.
     *  'subir'   → recomienda subir > 2%
     *  'bajar'   → recomienda bajar > 2%
     *  'igual'   → cambio menor del 2%
     *  'aplicado'→ ya se aplicó hoy
     */
    public function getEstadoAttribute(): string
    {
        if ($this->precio_aplicado && $this->aplicado_at && $this->aplicado_at->isToday()) {
            return 'aplicado';
        }
        $cambio = $this->cambio_pct;
        if ($cambio === null) return 'igual';
        if ($cambio > 2) return 'subir';
        if ($cambio < -2) return 'bajar';
        return 'igual';
    }
}
