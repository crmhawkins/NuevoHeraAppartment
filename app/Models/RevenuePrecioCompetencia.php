<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Precio histórico de un competidor para una noche concreta.
 * Cada scrape genera un nuevo registro (mantenemos histórico).
 */
class RevenuePrecioCompetencia extends Model
{
    use HasFactory;

    protected $table = 'revenue_precios_competencia';

    public $timestamps = false; // solo scrapeado_at

    protected $fillable = [
        'competidor_id',
        'fecha',
        'precio',
        'moneda',
        'disponible',
        'min_noches',
        'rating',
        'scrapeado_at',
        'raw_data',
    ];

    protected $casts = [
        'fecha' => 'date',
        'precio' => 'decimal:2',
        'rating' => 'decimal:2',
        'disponible' => 'boolean',
        'scrapeado_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function competidor(): BelongsTo
    {
        return $this->belongsTo(RevenueCompetidor::class, 'competidor_id');
    }
}
