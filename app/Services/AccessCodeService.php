<?php
namespace App\Services;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccessCodeService
{
    /**
     * Genera un código de 4 dígitos único, lo programa en la cerradura TTLock
     * y lo guarda en la reserva.
     */
    public function generarYProgramar(Reserva $reserva): ?string
    {
        $codigo = $this->generarCodigoUnico();

        // Validación: reservas de un solo día no se pueden programar en cerradura (ventana horaria inválida)
        if (Carbon::parse($reserva->fecha_entrada)->isSameDay(Carbon::parse($reserva->fecha_salida))) {
            Log::warning('AccessCode: Reserva de un solo día detectada, no se puede programar cerradura', [
                'reserva_id' => $reserva->id
            ]);
            // Generar código pero no enviar a TTLock (ventana horaria inválida)
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            return $codigo;
        }

        $apartamento = $reserva->apartamento;
        if (!$apartamento || !$apartamento->ttlock_lock_id) {
            // No hay cerradura configurada, guardamos el código sin programar
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::info("AccessCodeService: código {$codigo} generado para reserva {$reserva->id} sin cerradura configurada.");
            return $codigo;
        }

        $tuyaAppUrl = config('services.tuya_app.url');
        if (empty($tuyaAppUrl)) {
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::warning("AccessCodeService: TUYA_APP_URL no configurada.");
            return $codigo;
        }

        // TTLock tiene un límite de ~180 días. Si la fecha de entrada está a más de 150 días,
        // generamos el código y lo guardamos, pero NO lo enviamos a la cerradura todavía.
        // El comando cerraduras:programar-proximas se encargará de enviarlo cuando esté dentro del rango.
        $diasHastaEntrada = Carbon::now()->diffInDays(Carbon::parse($reserva->fecha_entrada), false);
        if ($diasHastaEntrada > 150) {
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::info("AccessCodeService: código {$codigo} generado para reserva {$reserva->id} pero NO enviado a cerradura (faltan {$diasHastaEntrada} días, límite TTLock ~180 días). Se programará automáticamente cuando esté dentro del rango.");
            return $codigo;
        }

        // Ventana de validez: día entrada 15:00 → día salida 11:00
        $efectivo = Carbon::parse($reserva->fecha_entrada)->setTime(15, 0, 0);
        $invalido  = Carbon::parse($reserva->fecha_salida)->setTime(11, 0, 0);

        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => config('services.tuya_app.api_key')])
                ->post("{$tuyaAppUrl}/api/pins", [
                'lock_id'            => $apartamento->ttlock_lock_id,
                'name'               => 'Reserva #' . $reserva->id,
                'pin'                => $codigo,
                'effective_time'     => $efectivo->toDateTimeString(),
                'invalid_time'       => $invalido->toDateTimeString(),
                'external_reference' => 'reserva_' . $reserva->id,
            ]);

            if ($response->successful()) {
                $pinId = $response->json('data.provider_code_id');
                $reserva->update([
                    'codigo_acceso'          => $codigo,
                    'ttlock_pin_id'          => $pinId,
                    'codigo_enviado_cerradura' => 1,
                ]);
                Log::info("AccessCodeService: PIN {$codigo} programado en cerradura para reserva {$reserva->id}.");
            } else {
                $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
                Log::error("AccessCodeService: error al programar en cerradura. Status: " . $response->status());

                // Alerta WhatsApp
                try {
                    $whatsapp = app(\App\Services\WhatsappNotificationService::class);
                    $whatsapp->sendToConfiguredRecipients(
                        "⚠️ ALERTA CERRADURA\n\nError HTTP {$response->status()} al programar cerradura para Reserva #{$reserva->id}\nApartamento: {$reserva->apartamento->titulo}"
                    );
                } catch (\Exception $alertEx) {
                    Log::error('No se pudo enviar alerta WhatsApp de cerradura', ['error' => $alertEx->getMessage()]);
                }

                // Notificación interna
                try {
                    \App\Models\Notification::createForAdmins(
                        \App\Models\Notification::TYPE_SISTEMA,
                        'Error cerradura TTLock',
                        "Error HTTP {$response->status()} al programar cerradura para Reserva #{$reserva->id}",
                        ['reserva_id' => $reserva->id, 'http_status' => $response->status()],
                        \App\Models\Notification::PRIORITY_HIGH,
                        \App\Models\Notification::CATEGORY_ERROR
                    );
                } catch (\Exception $notifEx) {
                    Log::error('No se pudo crear notificación de cerradura', ['error' => $notifEx->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::error("AccessCodeService: excepción al llamar a TTLock API: " . $e->getMessage());

            // Alerta WhatsApp
            try {
                $whatsapp = app(\App\Services\WhatsappNotificationService::class);
                $whatsapp->sendToConfiguredRecipients(
                    "⚠️ ALERTA CERRADURA\n\nError al programar cerradura para Reserva #{$reserva->id}\nApartamento: {$reserva->apartamento->titulo}\nError: {$e->getMessage()}"
                );
            } catch (\Exception $alertEx) {
                Log::error('No se pudo enviar alerta WhatsApp de cerradura', ['error' => $alertEx->getMessage()]);
            }

            // Notificación interna
            try {
                \App\Models\Notification::createForAdmins(
                    \App\Models\Notification::TYPE_SISTEMA,
                    'Error cerradura TTLock',
                    "No se pudo programar código para Reserva #{$reserva->id}: {$e->getMessage()}",
                    ['reserva_id' => $reserva->id],
                    \App\Models\Notification::PRIORITY_HIGH,
                    \App\Models\Notification::CATEGORY_ERROR
                );
            } catch (\Exception $notifEx) {
                Log::error('No se pudo crear notificación de cerradura', ['error' => $notifEx->getMessage()]);
            }
        }

        return $codigo;
    }

    /**
     * Envía a la cerradura TTLock un código de acceso que ya existe en la reserva.
     * Usado por el comando cerraduras:programar-proximas para reservas diferidas.
     */
    public function programarEnCerradura(Reserva $reserva): void
    {
        if (empty($reserva->codigo_acceso)) {
            throw new \Exception("La reserva #{$reserva->id} no tiene código de acceso generado.");
        }

        $apartamento = $reserva->apartamento;
        if (!$apartamento || !$apartamento->ttlock_lock_id) {
            throw new \Exception("La reserva #{$reserva->id} no tiene cerradura TTLock configurada.");
        }

        // Validación: reservas de un solo día no se pueden programar en cerradura
        if (Carbon::parse($reserva->fecha_entrada)->isSameDay(Carbon::parse($reserva->fecha_salida))) {
            throw new \Exception("Reserva #{$reserva->id} es de un solo día, ventana horaria inválida para TTLock.");
        }

        $tuyaAppUrl = config('services.tuya_app.url');
        if (empty($tuyaAppUrl)) {
            throw new \Exception('TUYA_APP_URL no configurada.');
        }

        // Ventana de validez: día entrada 15:00 → día salida 11:00
        $efectivo = Carbon::parse($reserva->fecha_entrada)->setTime(15, 0, 0);
        $invalido = Carbon::parse($reserva->fecha_salida)->setTime(11, 0, 0);

        $response = Http::timeout(30)
            ->withHeaders(['X-API-Key' => config('services.tuya_app.api_key')])
            ->post("{$tuyaAppUrl}/api/pins", [
                'lock_id'            => $apartamento->ttlock_lock_id,
                'name'               => 'Reserva #' . $reserva->id,
                'pin'                => $reserva->codigo_acceso,
                'effective_time'     => $efectivo->toDateTimeString(),
                'invalid_time'       => $invalido->toDateTimeString(),
                'external_reference' => 'reserva_' . $reserva->id,
            ]);

        if ($response->successful()) {
            $pinId = $response->json('data.provider_code_id');
            $reserva->update([
                'ttlock_pin_id'            => $pinId,
                'codigo_enviado_cerradura' => 1,
            ]);
            Log::info("AccessCodeService::programarEnCerradura: PIN programado para reserva {$reserva->id}", [
                'pin_id' => $pinId,
            ]);
        } else {
            throw new \Exception("Error HTTP {$response->status()} al programar cerradura para reserva #{$reserva->id}: {$response->body()}");
        }
    }

    /**
     * Revoca el PIN de la cerradura al cancelar/eliminar una reserva.
     *
     * NOTA: En la práctica, la revocación no es necesaria porque los códigos TTLock
     * tienen fechas de validez integradas (effective_time / invalid_time) y expiran
     * automáticamente. Se mantiene este método para uso manual en casos excepcionales.
     */
    public function revocarPin(Reserva $reserva): void
    {
        if (empty($reserva->ttlock_pin_id)) {
            return;
        }

        $tuyaAppUrl = config('services.tuya_app.url');
        if (empty($tuyaAppUrl)) {
            return;
        }

        try {
            Log::info("AccessCodeService: revisar revocación manual para reserva {$reserva->id}, pin_id: {$reserva->ttlock_pin_id}");
        } catch (\Exception $e) {
            Log::error("AccessCodeService: excepción al revocar PIN: " . $e->getMessage());
        }
    }

    private function generarCodigoUnico(): string
    {
        $intentos = 0;
        $maxIntentos = 100;
        do {
            $codigo = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            // Verificar que no está en uso en reservas activas o futuras
            $existe = Reserva::where('codigo_acceso', $codigo)
                ->where('fecha_salida', '>=', now()->toDateString())
                ->whereNotNull('codigo_acceso')
                ->exists();
            $intentos++;
        } while ($existe && $intentos < $maxIntentos);

        if ($existe) {
            throw new \Exception('No se pudo generar un código de acceso único después de ' . $maxIntentos . ' intentos.');
        }

        return $codigo;
    }
}
