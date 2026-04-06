<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reserva;
use App\Services\AccessCodeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Comando programado que envía códigos PIN a cerraduras TTLock para reservas futuras.
 *
 * TTLock tiene un límite de ~180 días para programar códigos. Para reservas con fecha de
 * entrada superior a 150 días, el servicio AccessCodeService genera el PIN pero NO lo envía
 * a la cerradura. Este comando se ejecuta diariamente y envía los PINs pendientes cuando
 * la reserva ya está dentro del rango de 150 días.
 */
class ProgramarCerradurasProximas extends Command
{
    protected $signature = 'cerraduras:programar-proximas';

    protected $description = 'Programa en cerraduras TTLock los códigos de reservas próximas (dentro de 150 días)';

    public function handle(): int
    {
        $limite = Carbon::now()->addDays(150);

        // Buscar reservas que:
        // - Tienen código de acceso generado
        // - NO se ha enviado a la cerradura todavía
        // - La fecha de entrada está dentro de los próximos 150 días
        // - El apartamento tiene cerradura TTLock configurada
        // - La reserva no está cancelada (estado_id != 4)
        $reservas = Reserva::with(['apartamento'])
            ->whereNotNull('codigo_acceso')
            ->where('codigo_enviado_cerradura', false)
            ->where('estado_id', '!=', 4) // no canceladas
            ->where('fecha_entrada', '<=', $limite->toDateString())
            ->where('fecha_entrada', '>=', Carbon::now()->toDateString())
            ->whereHas('apartamento', function ($q) {
                $q->whereNotNull('ttlock_lock_id')->where('ttlock_lock_id', '!=', '');
            })
            ->get();

        $this->info("Encontradas {$reservas->count()} reservas pendientes de programar cerradura.");

        if ($reservas->isEmpty()) {
            Log::info('cerraduras:programar-proximas - No hay reservas pendientes.');
            return 0;
        }

        $accessCodeService = app(AccessCodeService::class);
        $programadas = 0;
        $errores = 0;

        foreach ($reservas as $reserva) {
            try {
                // Enviar el código existente a TTLock, sin regenerar
                $accessCodeService->programarEnCerradura($reserva);
                $programadas++;
                $this->info("  Reserva #{$reserva->id}: código programado OK");
            } catch (\Exception $e) {
                $errores++;
                Log::error('cerraduras:programar-proximas error', [
                    'reserva_id' => $reserva->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Reserva #{$reserva->id}: ERROR - {$e->getMessage()}");
            }
        }

        $this->info("Resultado: {$programadas} programadas, {$errores} errores.");
        Log::info("cerraduras:programar-proximas - Completado: {$programadas} programadas, {$errores} errores.");

        return $errores > 0 ? 1 : 0;
    }
}
