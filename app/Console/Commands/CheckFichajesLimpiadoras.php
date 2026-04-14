<?php

namespace App\Console\Commands;

use App\Models\TurnoTrabajo;
use App\Models\Fichaje;
use App\Models\User;
use App\Services\AlertaEquipoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckFichajesLimpiadoras extends Command
{
    protected $signature = 'fichajes:verificar-limpiadoras';
    protected $description = 'Verifica que las limpiadoras con turno hoy hayan fichado correctamente';

    public function handle()
    {
        $hoy = Carbon::today();
        $ahora = Carbon::now();

        // Get all shifts for today
        $turnos = TurnoTrabajo::where('fecha', $hoy->format('Y-m-d'))
            ->with('user')
            ->get();

        if ($turnos->isEmpty()) {
            $this->info('No hay turnos programados para hoy.');
            return 0;
        }

        $problemas = [];

        foreach ($turnos as $turno) {
            $user = $turno->user;
            if (!$user) continue;

            // Check if cleaner has a fichaje for today
            $fichaje = Fichaje::where('user_id', $user->id)
                ->whereDate('hora_entrada', $hoy)
                ->first();

            $horaInicio = Carbon::parse($turno->hora_inicio);
            $horaFin = Carbon::parse($turno->hora_fin);

            // Case 1: Shift should have started but no fichaje
            if (!$fichaje && $ahora->gt($horaInicio->addMinutes(30))) {
                $problemas[] = [
                    'tipo' => 'NO_FICHAJE',
                    'nombre' => $user->name,
                    'turno' => $turno->hora_inicio . ' - ' . $turno->hora_fin,
                    'mensaje' => "{$user->name} tiene turno ({$turno->hora_inicio}-{$turno->hora_fin}) pero NO ha fichado entrada.",
                ];
            }

            // Case 2: Fichaje started but not finished and shift should be over
            if ($fichaje && !$fichaje->hora_salida && $ahora->gt($horaFin->addMinutes(30))) {
                $problemas[] = [
                    'tipo' => 'SIN_SALIDA',
                    'nombre' => $user->name,
                    'turno' => $turno->hora_inicio . ' - ' . $turno->hora_fin,
                    'mensaje' => "{$user->name} fichó entrada pero NO ha fichado salida (turno terminaba a las {$turno->hora_fin}).",
                ];
            }
        }

        if (empty($problemas)) {
            $this->info('Todos los fichajes están correctos.');
            return 0;
        }

        // Send WhatsApp alert
        $mensaje = "Se han detectado " . count($problemas) . " problema(s) de fichaje:\n\n";
        foreach ($problemas as $p) {
            $mensaje .= "- " . $p['mensaje'] . "\n";
        }
        $mensaje .= "\nEsto puede constituir una falta grave según el convenio.";

        try {
            AlertaEquipoService::alertar('FICHAJE INCOMPLETO', $mensaje, 'fichaje_incompleto');
            $this->info('Alerta enviada: ' . count($problemas) . ' problemas detectados.');
            Log::info('[Fichajes] Alerta enviada', ['problemas' => count($problemas)]);
        } catch (\Exception $e) {
            $this->error('Error enviando alerta: ' . $e->getMessage());
            Log::error('[Fichajes] Error enviando alerta: ' . $e->getMessage());
        }

        return 0;
    }
}
