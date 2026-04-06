<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Amenity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'amenities';
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'es_para_ninos',
        'edad_minima',
        'edad_maxima',
        'tipo_nino',
        'cantidad_por_nino',
        'notas_ninos',
        'precio_compra',
        'unidad_medida',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'tipo_consumo',
        'consumo_por_reserva',
        'consumo_minimo_reserva',
        'consumo_maximo_reserva',
        'duracion_dias',
        'consumo_por_persona',
        'unidad_consumo',
        'activo',
        'proveedor',
        'codigo_producto'
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'stock_actual' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'consumo_por_reserva' => 'decimal:2',
        'consumo_minimo_reserva' => 'decimal:2',
        'consumo_maximo_reserva' => 'decimal:2',
        'consumo_por_persona' => 'decimal:2',
        'es_para_ninos' => 'boolean',
        'edad_minima' => 'integer',
        'edad_maxima' => 'integer',
        'cantidad_por_nino' => 'integer',
        'activo' => 'boolean'
    ];

    // Relaciones
    public function consumos()
    {
        return $this->hasMany(AmenityConsumo::class);
    }

    public function reposiciones()
    {
        return $this->hasMany(AmenityReposicion::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeStockBajo($query)
    {
        return $query->where('stock_actual', '<=', 'stock_minimo');
    }

    // Nuevos scopes para amenities de niños
    public function scopeParaNinos($query)
    {
        return $query->where('es_para_ninos', true);
    }

    public function scopePorEdad($query, $edad)
    {
        return $query->where('es_para_ninos', true)
                    ->where(function($q) use ($edad) {
                        $q->whereNull('edad_minima')
                          ->orWhere('edad_minima', '<=', $edad);
                    })
                    ->where(function($q) use ($edad) {
                        $q->whereNull('edad_maxima')
                          ->orWhere('edad_maxima', '>=', $edad);
                    });
    }

    public function scopePorTipoNino($query, $tipo)
    {
        return $query->where('es_para_ninos', true)
                    ->where('tipo_nino', $tipo);
    }

    // Métodos de cálculo
    public function calcularConsumoReserva($numeroPersonas = 1, $dias = 1)
    {
        switch ($this->tipo_consumo) {
            case 'por_reserva':
                return $this->consumo_por_reserva ?? 0;
            
            case 'por_tiempo':
                return $dias > 0 ? ceil($dias / ($this->duracion_dias ?? 1)) : 1;
            
            case 'por_persona':
                return ($this->consumo_por_persona ?? 0) * $numeroPersonas * $dias;
            
            default:
                return 0;
        }
    }

    public function getStockDisponibleAttribute()
    {
        return $this->stock_actual - $this->stock_minimo;
    }

    public function getEstadoStockAttribute()
    {
        if ($this->stock_actual <= $this->stock_minimo) {
            return 'bajo';
        } elseif ($this->stock_actual >= ($this->stock_maximo ?? 999999)) {
            return 'alto';
        } else {
            return 'normal';
        }
    }

    public function getColorEstadoAttribute()
    {
        switch ($this->estado_stock) {
            case 'bajo':
                return 'danger';
            case 'alto':
                return 'warning';
            default:
                return 'success';
        }
    }

    // Métodos para gestionar el stock (atómicos para evitar condiciones de carrera)
    public function descontarStock($cantidad)
    {
        $cantidad = (float) $cantidad;
        if ($cantidad <= 0) {
            return [
                'stock_anterior' => $this->stock_actual,
                'stock_actual' => $this->stock_actual,
                'cantidad_descontada' => 0,
            ];
        }

        // Actualización atómica: solo resta si hay stock suficiente (evita race conditions)
        $affected = DB::table($this->getTable())
            ->where('id', $this->id)
            ->whereRaw('stock_actual >= ?', [$cantidad])
            ->decrement('stock_actual', $cantidad);

        if ($affected === 0) {
            $this->refresh();
            \Log::warning("Stock insuficiente para amenity {$this->id}: disponible = {$this->stock_actual}, solicitado = {$cantidad}");
            throw new \Exception("Stock insuficiente. Disponible: {$this->stock_actual} {$this->unidad_medida}, Solicitado: {$cantidad} {$this->unidad_medida}");
        }

        $this->refresh();
        $stockAnterior = (float) $this->stock_actual + $cantidad;

        return [
            'stock_anterior' => $stockAnterior,
            'stock_actual' => (float) $this->stock_actual,
            'cantidad_descontada' => $cantidad,
        ];
    }

    public function reponerStock($cantidad)
    {
        $cantidad = (float) $cantidad;
        if ($cantidad <= 0) {
            return $this->stock_actual;
        }

        DB::table($this->getTable())
            ->where('id', $this->id)
            ->increment('stock_actual', $cantidad);

        $this->refresh();
        return (float) $this->stock_actual;
    }

    public function ajustarStock($cantidadAnterior, $cantidadNueva)
    {
        $diferencia = $cantidadNueva - $cantidadAnterior;
        if ($diferencia > 0) {
            // Se está añadiendo más cantidad, descontar del stock
            $this->descontarStock($diferencia);
        } elseif ($diferencia < 0) {
            // Se está reduciendo la cantidad, reponer al stock
            $this->reponerStock(abs($diferencia));
        }
        return $this->stock_actual;
    }

    public function verificarStockBajo()
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    // Métodos para amenities de niños
    public function calcularCantidadParaNinos($numeroNinos, $edadesNinos = [])
    {
        if (!$this->es_para_ninos) {
            return 0;
        }

        $cantidadTotal = 0;
        
        foreach ($edadesNinos as $edad) {
            // Verificar si la edad está en el rango del amenity
            if ($this->edad_minima && $edad < $this->edad_minima) {
                continue;
            }
            if ($this->edad_maxima && $edad > $this->edad_maxima) {
                continue;
            }
            
            // Verificar tipo de niño
            if ($this->tipo_nino) {
                $tipoAplicable = $this->determinarTipoNino($edad);
                if ($this->tipo_nino !== $tipoAplicable) {
                    continue;
                }
            }
            
            $cantidadTotal += $this->cantidad_por_nino;
        }
        
        return $cantidadTotal;
    }

    public function determinarTipoNino($edad)
    {
        if ($edad <= 2) {
            return 'bebe';
        } elseif ($edad <= 6) {
            return 'nino_pequeno';
        } elseif ($edad <= 12) {
            return 'nino_grande';
        } else {
            return 'adolescente';
        }
    }

    public function getDescripcionTipoNinoAttribute()
    {
        switch ($this->tipo_nino) {
            case 'bebe':
                return 'Bebé (0-2 años)';
            case 'nino_pequeno':
                return 'Niño pequeño (3-6 años)';
            case 'nino_grande':
                return 'Niño grande (7-12 años)';
            case 'adolescente':
                return 'Adolescente (13+ años)';
            default:
                return 'Todas las edades';
        }
    }

    public function getRangoEdadesAttribute()
    {
        if (!$this->es_para_ninos) {
            return 'No aplica';
        }
        
        if ($this->edad_minima && $this->edad_maxima) {
            return "{$this->edad_minima} - {$this->edad_maxima} años";
        } elseif ($this->edad_minima) {
            return "{$this->edad_minima}+ años";
        } elseif ($this->edad_maxima) {
            return "Hasta {$this->edad_maxima} años";
        } else {
            return 'Todas las edades';
        }
    }
}
