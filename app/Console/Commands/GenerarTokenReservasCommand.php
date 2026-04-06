<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerarTokenReservasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservas:generar-token-dni
                            {--dry-run : Solo listar reservas sin token, sin guardar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera token DNI en reservas activas (no canceladas ni eliminadas) que no tienen token';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Reservas no canceladas (estado_id != 4), no eliminadas (soft deletes), sin token
        $reservasSinToken = Reserva::activas()
            ->where(function ($query) {
                $query->whereNull('token')->orWhere('token', '');
            })
            ->orderBy('id')
            ->get();

        $total = $reservasSinToken->count();

        if ($total === 0) {
            $this->info('No hay reservas activas sin token.');
            return 0;
        }

        $this->info("Reservas activas sin token: {$total}");

        foreach ($reservasSinToken as $reserva) {
            $token = bin2hex(random_bytes(16));

            if ($dryRun) {
                $this->line("  [dry-run] Reserva #{$reserva->id} ({$reserva->codigo_reserva}) - cliente_id: {$reserva->cliente_id}");
                continue;
            }

            $reserva->token = $token;
            $reserva->save();

            Log::info('Token DNI generado para reserva sin token', [
                'reserva_id' => $reserva->id,
                'codigo_reserva' => $reserva->codigo_reserva,
                'cliente_id' => $reserva->cliente_id,
            ]);

            $this->info("  Token generado para reserva #{$reserva->id} ({$reserva->codigo_reserva}).");
        }

        if (!$dryRun && $total > 0) {
            $this->info("Se generó token en {$total} reserva(s).");
        }

        return 0;
    }
}
