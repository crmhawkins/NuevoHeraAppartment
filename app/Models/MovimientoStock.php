<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'articulo_id',
        'tipo',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'precio_unitario',
        'precio_total',
        'motivo',
        'observaciones',
        'user_id',
        'proveedor_id',
        'apartamento_limpieza_id',
        'numero_factura',
        'fecha_movimiento'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'stock_anterior' => 'decimal:2',
        'stock_nuevo' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_total' => 'decimal:2',
        'fecha_movimiento' => 'date'
    ];

    // Relaciones
    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function apartamentoLimpieza()
    {
        return $this->belongsTo(ApartamentoLimpieza::class);
    }

    // Scopes
    public function scopeEntradas($query)
    {
        return $query->where('tipo', 'entrada');
    }

    public function scopeSalidas($query)
    {
        return $query->where('tipo', 'salida');
    }

    public function scopeAjustes($query)
    {
        return $query->where('tipo', 'ajuste');
    }

    public function scopePorMotivo($query, $motivo)
    {
        return $query->where('motivo', $motivo);
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        $query->where('fecha_movimiento', '>=', $fechaInicio);
        if ($fechaFin) {
            $query->where('fecha_movimiento', '<=', $fechaFin);
        }
        return $query;
    }

    // Métodos estáticos para crear movimientos
    public static function crearEntrada($articuloId, $cantidad, $precioUnitario = null, $motivo = 'compra', $observaciones = null, $proveedorId = null, $numeroFactura = null)
    {
        $articulo = Articulo::findOrFail($articuloId);
        $stockAnterior = $articulo->stock_actual;
        $stockNuevo = $stockAnterior + $cantidad;

        // Actualizar stock del artículo
        $articulo->stock_actual = $stockNuevo;
        $articulo->save();

        // Crear movimiento
        return self::create([
            'articulo_id' => $articuloId,
            'tipo' => 'entrada',
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'precio_unitario' => $precioUnitario,
            'precio_total' => $precioUnitario ? $precioUnitario * $cantidad : null,
            'motivo' => $motivo,
            'observaciones' => $observaciones,
            'user_id' => auth()->id(),
            'proveedor_id' => $proveedorId,
            'numero_factura' => $numeroFactura,
            'fecha_movimiento' => now()
        ]);
    }

    public static function crearSalida($articuloId, $cantidad, $motivo = 'consumo', $observaciones = null, $apartamentoLimpiezaId = null)
    {
        $articulo = Articulo::findOrFail($articuloId);
        $stockAnterior = $articulo->stock_actual;
        $stockNuevo = max(0, $stockAnterior - $cantidad);

        // Actualizar stock del artículo
        $articulo->stock_actual = $stockNuevo;
        $articulo->save();

        // Crear movimiento
        return self::create([
            'articulo_id' => $articuloId,
            'tipo' => 'salida',
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'motivo' => $motivo,
            'observaciones' => $observaciones,
            'user_id' => auth()->id(),
            'apartamento_limpieza_id' => $apartamentoLimpiezaId,
            'fecha_movimiento' => now()
        ]);
    }

    public static function crearAjuste($articuloId, $stockNuevo, $motivo = 'ajuste', $observaciones = null)
    {
        $articulo = Articulo::findOrFail($articuloId);
        $stockAnterior = $articulo->stock_actual;
        $diferencia = $stockNuevo - $stockAnterior;

        // Actualizar stock del artículo
        $articulo->stock_actual = $stockNuevo;
        $articulo->save();

        // Crear movimiento
        return self::create([
            'articulo_id' => $articuloId,
            'tipo' => 'ajuste',
            'cantidad' => abs($diferencia),
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'motivo' => $motivo,
            'observaciones' => $observaciones,
            'user_id' => auth()->id(),
            'fecha_movimiento' => now()
        ]);
    }
}
