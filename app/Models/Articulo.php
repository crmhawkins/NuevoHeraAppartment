<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Articulo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'tipo_descuento',
        'unidad_medida',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'precio_compra',
        'codigo_producto',
        'observaciones',
        'activo',
        'proveedor_id'
    ];

    protected $casts = [
        'stock_actual' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'precio_compra' => 'decimal:2',
        'activo' => 'boolean'
    ];

    // Relaciones
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class);
    }

    public function itemChecklists()
    {
        return $this->hasMany(ItemChecklist::class);
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
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }

    // Métodos
    public function getStockDisponibleAttribute()
    {
        return $this->stock_actual - $this->stock_minimo;
    }

    public function getEstadoStockAttribute()
    {
        if ($this->stock_actual <= $this->stock_minimo) {
            return 'bajo';
        } elseif ($this->stock_maximo && $this->stock_actual >= $this->stock_maximo) {
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

    // Métodos para gestionar el stock
    public function descontarStock($cantidad)
    {
        \Log::info("Descontando stock del artículo {$this->id}: stock_actual = {$this->stock_actual}, cantidad = {$cantidad}");
        $this->stock_actual = max(0, $this->stock_actual - $cantidad);
        \Log::info("Nuevo stock calculado: {$this->stock_actual}");
        $resultado = $this->save();
        \Log::info("Resultado del save(): " . ($resultado ? 'true' : 'false'));
        \Log::info("Stock final después de save(): {$this->stock_actual}");
        return $this->stock_actual;
    }

    public function reponerStock($cantidad)
    {
        $this->stock_actual = $this->stock_actual + $cantidad;
        $this->save();
        return $this->stock_actual;
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

    // Métodos para reposición
    public function esTipoReposicion()
    {
        return $this->tipo_descuento === 'reposicion';
    }

    public function esTipoConsumo()
    {
        return $this->tipo_descuento === 'consumo';
    }

    public function descontarStockPorReposicion($cantidad)
    {
        // Solo se descuenta del stock si es tipo 'consumo'
        if ($this->esTipoConsumo()) {
            return $this->descontarStock($cantidad);
        }
        
        // Para tipo 'reposicion', solo se registra el movimiento pero no se descuenta stock
        return $this->stock_actual;
    }

    public function getDescripcionTipoDescuentoAttribute()
    {
        switch ($this->tipo_descuento) {
            case 'reposicion':
                return 'Solo reposición física (toallas, sábanas, etc.)';
            case 'consumo':
                return 'Descuenta del stock general (cubiertos, vajilla, etc.)';
            default:
                return 'No definido';
        }
    }
}
