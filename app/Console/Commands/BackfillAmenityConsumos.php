<?php

namespace App\Console\Commands;

use App\Models\Amenity;
use App\Models\AmenityConsumo;
use App\Models\ApartamentoLimpieza;
use App\Models\User;
use App\Services\AmenityConsumptionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillAmenityConsumos extends Command
{
    protected $signature = 'amenity:backfill-consumos
                            {--from=2025-11-10 : Fecha desde (YYYY-MM-DD)}
                            {--to= : Fecha hasta (YYYY-MM-DD). Por defecto hoy}
                            {--dry-run : Solo simular, no aplicar cambios}
                            {--user-id= : ID de usuario a registrar en los consumos}
                            {--force : Procesar aunque la limpieza ya tenga consumos}
                            {--limpieza-id= : Limitar a una limpieza específica}';

    protected $description = 'Recrea los consumos de amenities faltantes para limpiezas finalizadas en un rango de fechas.';

    public function handle(): int
    {
        $from = Carbon::parse($this->option('from'))->startOfDay();
        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))->endOfDay()
            : Carbon::now();

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $limpiezaId = $this->option('limpieza-id');

        $this->info('🔄 RECONSTRUCCIÓN DE CONSUMOS DE AMENITIES');
        $this->line("   Rango: {$from->toDateString()} → {$to->toDateString()}");
        if ($dryRun) {
            $this->warn('   Modo DRY-RUN: no se aplicarán cambios en la base de datos');
        }
        if ($force) {
            $this->warn('   Opción --force activa: se recalcularán consumos aunque existan');
        }
        $this->newLine();

        $userId = $this->resolveUserId();
        if (!$userId) {
            $this->error('No se pudo determinar un usuario para registrar los consumos. Usa --user-id=');
            return self::FAILURE;
        }

        $query = ApartamentoLimpieza::with(['reserva', 'apartamento'])
            ->whereNotNull('fecha_fin')
            ->whereBetween('fecha_fin', [$from, $to])
            ->orderBy('fecha_fin');

        if ($limpiezaId) {
            $query->where('id', $limpiezaId);
        }

        $limpiezas = $query->get();

        if ($limpiezas->isEmpty()) {
            $this->info('No se encontraron limpiezas en el rango indicado.');
            return self::SUCCESS;
        }

        $amenities = Amenity::where('activo', true)
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get();

        $this->line('Limpiezas a procesar: ' . $limpiezas->count());
        $this->line('Amenities activos: ' . $amenities->count());
        $this->newLine();

        $totalConsumosCreados = 0;
        $totalCantidadesPorAmenity = [];

        foreach ($limpiezas as $limpieza) {
            $fechaLimpieza = Carbon::parse($limpieza->fecha_fin);

            if (!$force && AmenityConsumo::where('limpieza_id', $limpieza->id)->exists()) {
                $this->line("⏭️  Limpieza {$limpieza->id} ya tiene consumos. Saltando.");
                continue;
            }

            $this->line("Procesando limpieza ID {$limpieza->id} ({$fechaLimpieza->toDateString()})");

            if ($dryRun) {
                $this->simulateConsumos($amenities, $limpieza, $totalCantidadesPorAmenity);
                continue;
            }

            DB::beginTransaction();
            try {
                foreach ($amenities as $amenity) {
                    $cantidad = AmenityConsumptionService::calculateRecommendedQuantity(
                        $amenity,
                        $limpieza->reserva,
                        $limpieza->apartamento
                    );

                    if ($cantidad <= 0) {
                        continue;
                    }

                    $amenityLocked = Amenity::where('id', $amenity->id)->lockForUpdate()->first();
                    if (!$amenityLocked) {
                        Log::warning('Amenity no encontrado durante backfill', ['amenity_id' => $amenity->id]);
                        continue;
                    }

                    try {
                        $resultado = $amenityLocked->descontarStock($cantidad);
                    } catch (\Throwable $e) {
                        Log::error('No se pudo descontar stock en backfill', [
                            'amenity_id' => $amenity->id,
                            'limpieza_id' => $limpieza->id,
                            'error' => $e->getMessage()
                        ]);
                        continue;
                    }

                    AmenityConsumo::create([
                        'amenity_id' => $amenity->id,
                        'reserva_id' => $limpieza->reserva_id,
                        'apartamento_id' => $limpieza->apartamento_id,
                        'limpieza_id' => $limpieza->id,
                        'user_id' => $userId,
                        'tipo_consumo' => 'limpieza',
                        'cantidad_consumida' => $cantidad,
                        'cantidad_anterior' => $resultado['stock_anterior'] ?? null,
                        'cantidad_actual' => $resultado['stock_actual'] ?? null,
                        'costo_unitario' => $amenity->precio_compra,
                        'costo_total' => $cantidad * $amenity->precio_compra,
                        'observaciones' => 'Backfill automático de consumos',
                        'fecha_consumo' => $fechaLimpieza->toDateString()
                    ]);

                    $totalConsumosCreados++;
                    $totalCantidadesPorAmenity[$amenity->id] = ($totalCantidadesPorAmenity[$amenity->id] ?? 0) + $cantidad;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error procesando limpieza en backfill', [
                    'limpieza_id' => $limpieza->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->error("   ❌ Error en limpieza {$limpieza->id}: {$e->getMessage()}");
                continue;
            }
        }

        $this->newLine();
        $this->info('📊 RESUMEN');
        $this->line('   Consumos creados: ' . $totalConsumosCreados);
        $this->line('   Amenities afectados: ' . count($totalCantidadesPorAmenity));

        if (!empty($totalCantidadesPorAmenity)) {
            $this->newLine();
            $this->line('Detalles por amenity:');
            Amenity::whereIn('id', array_keys($totalCantidadesPorAmenity))
                ->orderBy('categoria')
                ->orderBy('nombre')
                ->get()
                ->each(function (Amenity $amenity) use ($totalCantidadesPorAmenity) {
                    $cantidad = number_format($totalCantidadesPorAmenity[$amenity->id], 2);
                    $this->line("   • [{$amenity->id}] {$amenity->nombre}: {$cantidad} {$amenity->unidad_medida}");
                });
        }

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN: No se aplicaron cambios');
        } else {
            $this->info('✅ Backfill finalizado');
        }

        return self::SUCCESS;
    }

    private function simulateConsumos($amenities, $limpieza, &$totales)
    {
        foreach ($amenities as $amenity) {
            $cantidad = AmenityConsumptionService::calculateRecommendedQuantity(
                $amenity,
                $limpieza->reserva,
                $limpieza->apartamento
            );

            if ($cantidad <= 0) {
                continue;
            }

            $this->line("   • [DRY-RUN] Amenity {$amenity->nombre} → {$cantidad} {$amenity->unidad_medida}");
            $totales[$amenity->id] = ($totales[$amenity->id] ?? 0) + $cantidad;
        }
    }

    private function resolveUserId(): ?int
    {
        $userId = $this->option('user-id');
        if ($userId) {
            return (int) $userId;
        }

        $adminUser = User::where('role', 'ADMIN')->first();
        if ($adminUser) {
            return $adminUser->id;
        }

        $firstUser = User::first();
        return $firstUser?->id;
    }
}

