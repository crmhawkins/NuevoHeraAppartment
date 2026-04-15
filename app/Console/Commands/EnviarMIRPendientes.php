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
                            {--dias=7 : Ventana en dias alrededor de hoy (pasado y futuro)}
                            {--todas : Ignorar filtro de fecha, procesar TODAS las pendientes}
                            {--dry-run : Simular sin enviar realmente}';

    protected $description = 'Envía automáticamente a MIR las reservas pendientes que tienen todos los datos completos';

    public function handle()
    {
        $dias = (int) $this->option('dias');
        $dryRun = $this->option('dry-run');
        $todas = (bool) $this->option('todas');
        $mirService = new MIRService();

        $this->info('Buscando reservas pendientes de envío a MIR...');

        // Buscar reservas pendientes: no enviadas O enviadas sin lote real (falsos positivos).
        // Una reserva se considera realmente enviada SOLO si mir_codigo_referencia tiene valor.
        $query = Reserva::with(['cliente', 'apartamento.edificio'])
            ->where(function ($q) {
                $q->where('mir_enviado', false)
                  ->orWhereNull('mir_enviado')
                  ->orWhere(function ($qq) {
                      // Falsos positivos historicos: mir_enviado=true pero sin lote
                      $qq->where('mir_enviado', true)
                         ->where(function ($qqq) {
                             $qqq->whereNull('mir_codigo_referencia')
                                 ->orWhere('mir_codigo_referencia', '');
                         });
                  });
            });

        // Ventana de fechas: [hoy-dias, hoy+dias] (cubre reservas pasadas y futuras
        // que aun no se han enviado). Se puede desactivar con --todas.
        if (!$todas) {
            $query->whereBetween('fecha_entrada', [
                Carbon::now()->subDays($dias)->startOfDay(),
                Carbon::now()->addDays($dias)->endOfDay(),
            ]);
        }

        $reservas = $query->orderBy('fecha_entrada', 'asc')->get();

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
