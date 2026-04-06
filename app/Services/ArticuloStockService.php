<?php

namespace App\Services;

use App\Models\Articulo;
use App\Models\MovimientoStock;
use Illuminate\Support\Facades\DB;

class ArticuloStockService
{
    /**
     * Registrar salida atómica y detallada de un artículo, devolviendo el MovimientoStock
     */
    public function salidaDetallada(
        int $articuloId,
        float $cantidad = 1.0,
        string $motivo = 'consumo',
        ?string $observaciones = null,
        ?int $apartamentoLimpiezaId = null
    ): MovimientoStock {
        return DB::transaction(function () use ($articuloId, $cantidad, $motivo, $observaciones, $apartamentoLimpiezaId) {
            // Bloquear fila del artículo para evitar condiciones de carrera
            /** @var Articulo $articulo */
            $articulo = Articulo::where('id', $articuloId)->lockForUpdate()->firstOrFail();

            if ($cantidad <= 0) {
                throw new \InvalidArgumentException('La cantidad debe ser mayor que cero');
            }

            if ($articulo->stock_actual < $cantidad) {
                throw new \RuntimeException("Stock insuficiente. Disponible: {$articulo->stock_actual}");
            }

            $stockAnterior = $articulo->stock_actual;
            $stockNuevo = $stockAnterior - $cantidad;

            // Actualizar stock del artículo
            $articulo->stock_actual = $stockNuevo;
            $articulo->save();

            // Crear movimiento
            return MovimientoStock::create([
                'articulo_id' => $articulo->id,
                'tipo' => 'salida',
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'motivo' => $motivo,
                'observaciones' => $observaciones,
                'user_id' => auth()->id(),
                'apartamento_limpieza_id' => $apartamentoLimpiezaId,
                'fecha_movimiento' => now(),
            ]);
        });
    }

    /**
     * Descontar 1 unidad por roto y, si queda stock, descontar otra unidad por reposición del mismo artículo.
     * Devuelve array con los movimientos generados.
     */
    public function salidaAutoReposicionMismoArticulo(
        int $articuloId,
        int $apartamentoLimpiezaId,
        string $motivoRoto = 'roto',
        ?string $observaciones = null
    ): array {
        return DB::transaction(function () use ($articuloId, $apartamentoLimpiezaId, $motivoRoto, $observaciones) {
            // Descontar 1 por roto
            $movRoto = $this->salidaDetallada(
                articuloId: $articuloId,
                cantidad: 1.0,
                motivo: $motivoRoto . '_roto',
                observaciones: trim(($observaciones ?? '') . ' [Artículo roto descontado]') ?: null,
                apartamentoLimpiezaId: $apartamentoLimpiezaId
            );

            // Verificar si queda stock para reponer automáticamente
            $articulo = Articulo::where('id', $articuloId)->lockForUpdate()->firstOrFail();
            $movRepos = null;
            if ($articulo->stock_actual > 0) {
                $movRepos = $this->salidaDetallada(
                    articuloId: $articuloId,
                    cantidad: 1.0,
                    motivo: 'reposicion',
                    observaciones: 'Reposición automática de artículo roto/deteriorado',
                    apartamentoLimpiezaId: $apartamentoLimpiezaId
                );
            }

            return [
                'roto' => $movRoto,
                'reposicion' => $movRepos,
            ];
        });
    }
}




