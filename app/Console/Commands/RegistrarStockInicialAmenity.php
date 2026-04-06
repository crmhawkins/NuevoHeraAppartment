<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Amenity;
use App\Models\AmenityReposicion;
use App\Models\AmenityConsumo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegistrarStockInicialAmenity extends Command
{
    protected $signature = 'amenity:registrar-stock-inicial 
                            {amenity_id : ID del amenity}
                            {cantidad : Cantidad de stock inicial a registrar}
                            {--fecha= : Fecha de la reposición inicial (YYYY-MM-DD). Por defecto: día anterior a la primera reposición}
                            {--user-id= : ID del usuario. Por defecto: primer ADMIN}
                            {--dry-run : Solo mostrar lo que se haría}';

    protected $description = 'Registra un stock inicial como reposición histórica para un amenity';

    public function handle(): int
    {
        $amenityId = (int) $this->argument('amenity_id');
        $cantidad = (float) $this->argument('cantidad');
        $dryRun = (bool) $this->option('dry-run');
        $fecha = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : null;

        $amenity = Amenity::find($amenityId);
        if (!$amenity) {
            $this->error("Amenity {$amenityId} no encontrado");
            return self::FAILURE;
        }

        // Obtener primera reposición para determinar fecha
        $primeraReposicion = AmenityReposicion::where('amenity_id', $amenityId)
            ->orderBy('fecha_reposicion', 'asc')
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$fecha) {
            if ($primeraReposicion) {
                $fecha = Carbon::parse($primeraReposicion->fecha_reposicion)->subDay();
            } else {
                $fecha = Carbon::now()->subYear(); // Si no hay reposiciones, usar hace un año
            }
        } else {
            $fecha = Carbon::parse($fecha);
        }

        // Obtener usuario
        $userId = $this->option('user-id');
        if (!$userId) {
            $adminUser = User::where('role', 'ADMIN')->first();
            if ($adminUser) {
                $userId = $adminUser->id;
            } else {
                $firstUser = User::first();
                $userId = $firstUser?->id;
            }
        }

        if (!$userId) {
            $this->error('No se pudo determinar un usuario. Usa --user-id=');
            return self::FAILURE;
        }

        // Calcular stock actual
        $totalReposiciones = (float) AmenityReposicion::where('amenity_id', $amenityId)
            ->sum('cantidad_reponida');
        $totalConsumos = (float) AmenityConsumo::where('amenity_id', $amenityId)
            ->sum('cantidad_consumida');
        $stockCalculado = $totalReposiciones - $totalConsumos;

        $this->info("📦 Amenity: [{$amenity->id}] {$amenity->nombre}");
        $this->line("   Stock actual (BD): {$amenity->stock_actual} {$amenity->unidad_medida}");
        $this->line("   Total reposiciones: {$totalReposiciones} {$amenity->unidad_medida}");
        $this->line("   Total consumos: {$totalConsumos} {$amenity->unidad_medida}");
        $this->line("   Stock calculado: {$stockCalculado} {$amenity->unidad_medida}");
        $this->newLine();

        $this->info("📝 OPERACIÓN:");
        $this->line("   Cantidad a registrar: {$cantidad} {$amenity->unidad_medida}");
        $this->line("   Fecha: {$fecha->format('d/m/Y')}");
        $this->line("   Usuario ID: {$userId}");
        $this->newLine();

        $stockDespues = $stockCalculado + $cantidad;
        $this->line("   Stock calculado después: {$stockDespues} {$amenity->unidad_medida}");
        $this->newLine();

        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: No se realizarán cambios");
            return self::SUCCESS;
        }

        if (!$this->confirm('¿Deseas continuar con el registro del stock inicial?')) {
            $this->info('Operación cancelada');
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            // Crear reposición histórica
            $reposicion = AmenityReposicion::create([
                'amenity_id' => $amenityId,
                'user_id' => $userId,
                'cantidad_reponida' => $cantidad,
                'stock_anterior' => 0,
                'stock_nuevo' => $cantidad,
                'precio_unitario' => $amenity->precio_compra ?? 0,
                'precio_total' => ($amenity->precio_compra ?? 0) * $cantidad,
                'proveedor' => 'Stock inicial histórico',
                'observaciones' => 'Stock inicial registrado mediante comando para corregir inconsistencias',
                'fecha_reposicion' => $fecha->format('Y-m-d')
            ]);

            // Actualizar stock actual del amenity
            $nuevoStock = max(0, $stockDespues);
            $amenity->update(['stock_actual' => $nuevoStock]);

            DB::commit();

            $this->info("✅ Stock inicial registrado correctamente");
            $this->line("   Reposición ID: {$reposicion->id}");
            $this->line("   Nuevo stock actual: {$nuevoStock} {$amenity->unidad_medida}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

