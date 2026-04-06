<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Services\MIRService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarMIRPendientes extends Command
{
    protected $signature = 'mir:enviar-pendientes
                            {--dias=7 : Buscar reservas con entrada en los últimos N días}
                            {--dry-run : Simular sin enviar realmente}';

    protected $description = 'Envía automáticamente a MIR las reservas pendientes que tienen todos los datos completos';

    public function handle()
    {
        $dias = (int) $this->option('dias');
        $dryRun = $this->option('dry-run');
        $mirService = new MIRService();

        $this->info('Buscando reservas pendientes de envío a MIR...');

        // Buscar reservas no enviadas, con entrada en los últimos N días hasta hoy
        $reservas = Reserva::with(['cliente', 'apartamento.edificio'])
            ->where(function ($query) {
                $query->where('mir_enviado', false)
                      ->orWhereNull('mir_enviado');
            })
            ->whereBetween('fecha_entrada', [
                Carbon::now()->subDays($dias)->startOfDay(),
                Carbon::now()->endOfDay(),
            ])
            ->orderBy('fecha_entrada', 'asc')
            ->get();

        $this->info("Encontradas {$reservas->count()} reserva(s) sin enviar a MIR.");

        $enviadas = 0;
        $omitidas = 0;
        $errores = 0;

        foreach ($reservas as $reserva) {
            // Verificar si tiene datos completos
            if (!$mirService->reservaListaParaMIR($reserva)) {
                $omitidas++;
                if ($this->getOutput()->isVerbose()) {
                    $this->line("  Omitida #{$reserva->id} ({$reserva->codigo_reserva}) - datos incompletos");
                }
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY-RUN] Enviaría #{$reserva->id} ({$reserva->codigo_reserva}) - {$reserva->cliente->nombre} {$reserva->cliente->apellido1}");
                $enviadas++;
                continue;
            }

            $this->line("  Enviando #{$reserva->id} ({$reserva->codigo_reserva})...");

            $resultado = $mirService->enviarSiLista($reserva);

            if ($resultado && $resultado['success']) {
                $this->info("    ✓ Enviada OK - Lote: {$resultado['codigo_referencia']}");
                $enviadas++;
            } elseif ($resultado) {
                $this->error("    ✗ Error: {$resultado['mensaje']}");
                $errores++;
            }
        }

        $this->newLine();
        $this->info("Resultado: {$enviadas} enviadas, {$omitidas} omitidas (datos incompletos), {$errores} errores.");

        if ($enviadas > 0 || $errores > 0) {
            Log::info("MIR cron: {$enviadas} enviadas, {$omitidas} omitidas, {$errores} errores");
        }

        return 0;
    }
}
