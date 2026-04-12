<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reserva;
use App\Services\AlertaEquipoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DetectOrphanedReservations extends Command
{
    protected $signature = 'reservas:detectar-huerfanas';
    protected $description = 'Detecta reservas activas cuya fecha de salida ya paso o que deberian estar canceladas';

    public function handle()
    {
        // 1. Find reservations where fecha_salida < today but estado_id is still active (1=confirmada, 2=pendiente)
        $pasadas = Reserva::whereIn('estado_id', [1, 2])
            ->where('fecha_salida', '<', Carbon::today())
            ->get();

        if ($pasadas->count() > 0) {
            $this->info("Encontradas {$pasadas->count()} reservas activas con fecha pasada");

            foreach ($pasadas as $reserva) {
                // Mark as completed (estado_id = 3 = completada) not cancelled
                $reserva->update(['estado_id' => 3]);
                Log::info("[Huerfanas] Reserva #{$reserva->id} ({$reserva->codigo_reserva}) marcada como completada - salida fue {$reserva->fecha_salida}");
            }
        }

        // 2. Find duplicate active reservations for same apartment on same dates
        $duplicadas = DB::select("
            SELECT r1.id as id1, r2.id as id2, r1.apartamento_id,
                   r1.codigo_reserva as cod1, r2.codigo_reserva as cod2,
                   r1.fecha_entrada as entrada1, r1.fecha_salida as salida1,
                   r2.fecha_entrada as entrada2, r2.fecha_salida as salida2
            FROM reservas r1
            JOIN reservas r2 ON r1.apartamento_id = r2.apartamento_id
                AND r1.id < r2.id
                AND r1.fecha_entrada < r2.fecha_salida
                AND r1.fecha_salida > r2.fecha_entrada
            WHERE r1.estado_id NOT IN (4) AND r2.estado_id NOT IN (4)
                AND r1.deleted_at IS NULL AND r2.deleted_at IS NULL
                AND r1.fecha_salida >= CURDATE()
        ");

        if (count($duplicadas) > 0) {
            $this->warn("ALERTA: " . count($duplicadas) . " solapamientos detectados!");

            $mensaje = "Se detectaron " . count($duplicadas) . " solapamientos:\n\n";
            foreach ($duplicadas as $d) {
                $mensaje .= "Apt #{$d->apartamento_id}: {$d->cod1} ({$d->entrada1}-{$d->salida1}) vs {$d->cod2} ({$d->entrada2}-{$d->salida2})\n";
            }

            try {
                AlertaEquipoService::alertar('SOLAPAMIENTOS DETECTADOS', $mensaje, 'solapamiento');
            } catch (\Exception $e) {
                Log::error('Error enviando alerta solapamientos: ' . $e->getMessage());
            }
        } else {
            $this->info('No se detectaron solapamientos activos.');
        }

        return 0;
    }
}
