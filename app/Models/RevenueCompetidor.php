<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Competidor de un apartamento nuestro en Booking o Airbnb.
 * El admin define manualmente la lista de URLs comparables.
 */
class RevenueCompetidor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'revenue_competidores';

    protected $fillable = [
        'apartamento_id',
        'plataforma',     // 'booking' | 'airbnb'
        'url',
        'titulo',
        'activo',
        'notas',
        'ultimo_scrape_at',
        'ultimo_error_at',
        'ultimo_error_msg',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_scrape_at' => 'datetime',
        'ultimo_error_at' => 'datetime',
    ];

    public function apartamento(): BelongsTo
    {
        return $this->belongsTo(Apartamento::class);
    }

    public function precios(): HasMany
    {
        return $this->hasMany(RevenuePrecioCompetencia::class, 'competidor_id');
    }

    /**
     * Último precio scrapeado por fecha (un registro por fecha).
     */
    public function preciosUltimos(): HasMany
    {
        return $this->hasMany(RevenuePrecioCompetencia::class, 'competidor_id')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('revenue_precios_competencia')
                    ->whereColumn('competidor_id', 'revenue_competidores.id')
                    ->groupBy('competidor_id', 'fecha');
            });
    }
}
