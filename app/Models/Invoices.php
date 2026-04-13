<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoices extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'invoices';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            // Don't override if reference already set
            if (!empty($invoice->reference)) return;

            // Don't assign to rectificativas (those get R prefix)
            if ($invoice->es_rectificativa) return;

            // Get the date for this invoice
            $fecha = $invoice->fecha ? \Carbon\Carbon::parse($invoice->fecha) : now();
            $year = $fecha->format('Y');
            $month = $fecha->format('m');

            // Find the highest reference number for this year (exclude R-prefixed rectificativas)
            $lastRef = static::where('reference', 'like', "{$year}/%")
                ->where('reference', 'not like', 'R%')
                ->orderByDesc('reference')
                ->value('reference');

            $lastNum = 0;
            if ($lastRef && preg_match('/\d{4}\/\d{2}\/(\d+)/', $lastRef, $matches)) {
                $lastNum = (int) $matches[1];
            }

            $invoice->reference = sprintf('%s/%s/%06d', $year, $month, $lastNum + 1);
        });
    }

    protected $fillable = [
        'budget_id',
        'cliente_id',
        'reserva_id',
        'invoice_status_id',
        'concepto',
        'description',
        'fecha',
        'fecha_cobro',
        'base',
        'iva',
        'descuento',
        'total',
        'reference',
        'reference_autoincrement_id',
        // Campos de factura rectificativa
        'es_rectificativa',
        'factura_original_id',
        'motivo_rectificacion',
        'observaciones_rectificacion'
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
     * Casts para campos específicos
     */
    protected $casts = [
        'es_rectificativa' => 'boolean',
        'base' => 'decimal:2',
        'iva' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
    ];
    
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
     // Relación con el modelo Client (Cliente)
     public function client()
     {
         return $this->belongsTo(Cliente::class, 'cliente_id'); // cliente_id es la clave foránea en la tabla invoices
     }
     // Relación con el modelo Client (Cliente)
     public function estado()
     {
         return $this->belongsTo(InvoicesStatus::class, 'invoice_status_id'); // cliente_id es la clave foránea en la tabla invoices
     }
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Relación con la factura original (para facturas rectificativas)
     */
    public function facturaOriginal()
    {
        return $this->belongsTo(Invoices::class, 'factura_original_id');
    }

    /**
     * Relación con las facturas rectificativas (para facturas originales)
     */
    public function facturasRectificativas()
    {
        return $this->hasMany(Invoices::class, 'factura_original_id');
    }

    /**
     * Scope para obtener solo facturas rectificativas
     */
    public function scopeRectificativas($query)
    {
        return $query->where('es_rectificativa', true);
    }

    /**
     * Scope para obtener solo facturas originales (no rectificativas)
     */
    public function scopeOriginales($query)
    {
        return $query->where('es_rectificativa', false);
    }

    /**
     * Verifica si la factura es rectificativa
     */
    public function esRectificativa()
    {
        return $this->es_rectificativa;
    }

    /**
     * Verifica si la factura tiene rectificativas
     */
    public function tieneRectificativas()
    {
        return $this->facturasRectificativas()->count() > 0;
    }

    /**
     * Obtiene el total neto (original + rectificativas)
     */
    public function getTotalNetoAttribute()
    {
        if ($this->es_rectificativa) {
            return 0; // Las rectificativas no cuentan para el total neto
        }

        $totalOriginal = $this->total;
        $totalRectificativas = $this->facturasRectificativas()->sum('total');
        
        return $totalOriginal + $totalRectificativas;
    }
    
}
