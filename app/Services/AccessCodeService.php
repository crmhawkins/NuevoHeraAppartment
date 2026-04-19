<?php
namespace App\Services;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccessCodeService
{
    /**
     * Genera un código de acceso según el tipo de cerradura del apartamento:
     * - manual: usa el código fijo almacenado en apartamento.claves
     * - ttlock/tuya: genera PIN de 4 dígitos y lo envía a Tuyalaravel
     */
    public function generarYProgramar(Reserva $reserva): ?string
    {
        $apartamento = $reserva->apartamento;
        if (!$apartamento) {
            Log::warning("AccessCodeService: reserva {$reserva->id} sin apartamento asociado.");
            return null;
        }

        $tipoCerradura = $apartamento->tipo_cerradura ?? 'manual';

        // CERRADURA MANUAL: usar código fijo del apartamento
        if ($tipoCerradura === 'manual') {
            return $this->generarCodigoManual($reserva, $apartamento);
        }

        // CERRADURA DIGITAL (TTLock o Tuya): generar PIN y enviar a Tuyalaravel
        return $this->generarCodigoDigital($reserva, $apartamento, $tipoCerradura);
    }

    /**
     * Cerradura manual: el código es el almacenado en apartamento.claves
     */
    private function generarCodigoManual(Reserva $reserva, $apartamento): ?string
    {
        $codigo = $apartamento->claves;

        if (empty($codigo)) {
            Log::warning("AccessCodeService: apartamento {$apartamento->id} sin clave manual configurada.");
            $this->notificarError($reserva, "Apartamento {$apartamento->titulo} no tiene clave manual configurada.");
            return null;
        }

        $reserva->update([
            'codigo_acceso' => $codigo,
            'codigo_enviado_cerradura' => 1, // Manual = siempre "programada"
        ]);

        Log::info("AccessCodeService: código manual asignado a reserva {$reserva->id}.");
        return $codigo;
    }

    /**
     * Cerradura digital: genera PIN de 4 dígitos y lo envía a Tuyalaravel
     */
    private function generarCodigoDigital(Reserva $reserva, $apartamento, string $tipoCerradura): ?string
    {
        $lockId = $apartamento->tuyalaravel_lock_id ?? $apartamento->ttlock_lock_id;

        if (!$lockId) {
            // No hay cerradura configurada, generar código y guardar sin enviar
            $codigo = $this->generarCodigoUnico();
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::warning("AccessCodeService: apartamento {$apartamento->id} tipo '{$tipoCerradura}' sin lock_id.");
            $this->notificarError($reserva, "Apartamento {$apartamento->titulo} tiene cerradura {$tipoCerradura} pero no tiene lock_id configurado.");
            return $codigo;
        }

        $codigo = $this->generarCodigoUnico();

        // Validación: reservas de un solo día
        if (Carbon::parse($reserva->fecha_entrada)->isSameDay(Carbon::parse($reserva->fecha_salida))) {
            Log::warning('AccessCode: Reserva de un solo día, no se puede programar cerradura', ['reserva_id' => $reserva->id]);
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            return $codigo;
        }

        $tuyaAppUrl = config('services.tuya_app.url');
        if (empty($tuyaAppUrl)) {
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::warning("AccessCodeService: TUYA_APP_URL no configurada.");
            $this->notificarError($reserva, "TUYA_APP_URL no configurada. Código generado pero no enviado a cerradura.");
            return $codigo;
        }

        // TTLock tiene límite ~180 días. Si falta mucho, guardar sin enviar (el cron lo enviará después).
        $diasHastaEntrada = Carbon::now()->diffInDays(Carbon::parse($reserva->fecha_entrada), false);
        if ($diasHastaEntrada > 150) {
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::info("AccessCodeService: código para reserva {$reserva->id} diferido ({$diasHastaEntrada} días).");
            return $codigo;
        }

        // Enviar a Tuyalaravel
        return $this->enviarATuyalaravel($reserva, $lockId, $codigo);
    }

    /**
     * Envía el PIN a Tuyalaravel y guarda el resultado.
     * Si el gateway confirma, guarda codigo_enviado_cerradura = 1.
     * Si falla, guarda el código pero con codigo_enviado_cerradura = 0.
     * Tuyalaravel puede devolver un PIN diferente (modo offline TTLock) — usamos el que devuelve.
     */
    private function enviarATuyalaravel(Reserva $reserva, $lockId, string $codigo): ?string
    {
        $tuyaAppUrl = config('services.tuya_app.url');
        $efectivo = Carbon::parse($reserva->fecha_entrada)->setTime(15, 0, 0);
        $invalido = Carbon::parse($reserva->fecha_salida)->setTime(11, 0, 0);

        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => config('services.tuya_app.api_key')])
                ->post("{$tuyaAppUrl}/api/pins", [
                    'lock_id'            => $lockId,
                    'name'               => 'Reserva #' . $reserva->id,
                    'pin'                => $codigo,
                    'effective_time'     => $efectivo->toDateTimeString(),
                    'invalid_time'       => $invalido->toDateTimeString(),
                    'external_reference' => 'reserva_' . $reserva->id,
                ]);

            if ($response->successful()) {
                $pinId = $response->json('data.provider_code_id');
                // Usar el PIN que devuelve Tuyalaravel (puede ser diferente en modo offline)
                $pinReal = $response->json('data.pin') ?? $codigo;

                $reserva->update([
                    'codigo_acceso'            => $pinReal,
                    'ttlock_pin_id'            => $pinId,
                    'codigo_enviado_cerradura' => 1,
                ]);

                Log::info("AccessCodeService: PIN programado en cerradura para reserva {$reserva->id}.", [
                    'provider_code_id' => $pinId,
                ]);

                return $pinReal;
            } else {
                $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
                Log::error("AccessCodeService: error HTTP {$response->status()} al programar cerradura.", [
                    'reserva_id' => $reserva->id,
                    'response' => $response->body(),
                ]);
                $this->notificarError($reserva, "Error HTTP {$response->status()} al programar cerradura.\nApartamento: {$reserva->apartamento->titulo}");
            }
        } catch (\Exception $e) {
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::error("AccessCodeService: excepción al llamar a Tuyalaravel: " . $e->getMessage());
            $this->notificarError($reserva, "Error al conectar con Tuyalaravel: {$e->getMessage()}\nApartamento: {$reserva->apartamento->titulo}");
        }

        return $codigo;
    }

    /**
     * Reintenta enviar a la cerradura un código que ya existe.
     * Devuelve true si se confirmó, false si sigue pendiente.
     * Si el gateway sigue offline, pide un PIN offline a TTLock.
     */
    public function reintentarOFallback(Reserva $reserva): bool
    {
        if (empty($reserva->codigo_acceso)) {
            return false;
        }

        // Si ya está confirmado, no hay nada que hacer
        if ($reserva->codigo_enviado_cerradura) {
            return true;
        }

        $apartamento = $reserva->apartamento;
        $tipoCerradura = $apartamento->tipo_cerradura ?? 'manual';

        // Si es manual, marcar como enviado
        if ($tipoCerradura === 'manual') {
            $reserva->update(['codigo_enviado_cerradura' => 1]);
            return true;
        }

        $lockId = $apartamento->tuyalaravel_lock_id ?? $apartamento->ttlock_lock_id;
        if (!$lockId) return false;

        $tuyaAppUrl = config('services.tuya_app.url');
        if (empty($tuyaAppUrl)) return false;

        // Intentar enviar el código existente
        try {
            $efectivo = Carbon::parse($reserva->fecha_entrada)->setTime(15, 0, 0);
            $invalido = Carbon::parse($reserva->fecha_salida)->setTime(11, 0, 0);

            $response = Http::timeout(15)
                ->withHeaders(['X-API-Key' => config('services.tuya_app.api_key')])
                ->post("{$tuyaAppUrl}/api/pins", [
                    'lock_id'            => $lockId,
                    'name'               => 'Reserva #' . $reserva->id,
                    'pin'                => $reserva->codigo_acceso,
                    'effective_time'     => $efectivo->toDateTimeString(),
                    'invalid_time'       => $invalido->toDateTimeString(),
                    'external_reference' => 'reserva_' . $reserva->id,
                ]);

            if ($response->successful()) {
                $pinId = $response->json('data.provider_code_id');
                $pinReal = $response->json('data.pin') ?? $reserva->codigo_acceso;

                $reserva->update([
                    'codigo_acceso'            => $pinReal,
                    'ttlock_pin_id'            => $pinId,
                    'codigo_enviado_cerradura' => 1,
                ]);

                Log::info("AccessCodeService::reintento: PIN confirmado para reserva {$reserva->id}.", [
                    'provider_code_id' => $pinId,
                ]);

                return true;
            }

            // Gateway offline: el PIN solicitado no se pudo programar.
            // Tuyalaravel puede haber devuelto un PIN offline en data.pin
            $pinOffline = $response->json('data.pin');
            if ($pinOffline) {
                $reserva->update([
                    'codigo_acceso'            => $pinOffline,
                    'ttlock_pin_id'            => $response->json('data.provider_code_id'),
                    'codigo_enviado_cerradura' => 1,
                ]);

                Log::info("AccessCodeService::reintento: PIN offline asignado para reserva {$reserva->id}.");

                $this->notificarError($reserva,
                    "Gateway offline para reserva #{$reserva->id}.\n" .
                    "Se asignó PIN offline automático.\n" .
                    "Apartamento: {$reserva->apartamento->titulo}"
                );

                return true;
            }

            Log::warning("AccessCodeService::reintento: gateway sigue offline para reserva {$reserva->id}.");

        } catch (\Exception $e) {
            Log::error("AccessCodeService::reintento: excepción para reserva {$reserva->id}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Envía a la cerradura un código ya existente.
     * Usado por el comando cerraduras:programar-proximas.
     */
    public function programarEnCerradura(Reserva $reserva): void
    {
        if (empty($reserva->codigo_acceso)) {
            throw new \Exception("La reserva #{$reserva->id} no tiene código de acceso generado.");
        }

        $apartamento = $reserva->apartamento;
        $tipoCerradura = $apartamento->tipo_cerradura ?? 'manual';

        // Manual: no necesita envío
        if ($tipoCerradura === 'manual') {
            $reserva->update(['codigo_enviado_cerradura' => 1]);
            return;
        }

        $lockId = $apartamento->tuyalaravel_lock_id ?? $apartamento->ttlock_lock_id;
        if (!$lockId) {
            throw new \Exception("La reserva #{$reserva->id} no tiene lock_id configurado.");
        }

        if (Carbon::parse($reserva->fecha_entrada)->isSameDay(Carbon::parse($reserva->fecha_salida))) {
            throw new \Exception("Reserva #{$reserva->id} es de un solo día, ventana horaria inválida.");
        }

        $tuyaAppUrl = config('services.tuya_app.url');
        if (empty($tuyaAppUrl)) {
            throw new \Exception('TUYA_APP_URL no configurada.');
        }

        $efectivo = Carbon::parse($reserva->fecha_entrada)->setTime(15, 0, 0);
        $invalido = Carbon::parse($reserva->fecha_salida)->setTime(11, 0, 0);

        $response = Http::timeout(30)
            ->withHeaders(['X-API-Key' => config('services.tuya_app.api_key')])
            ->post("{$tuyaAppUrl}/api/pins", [
                'lock_id'            => $lockId,
                'name'               => 'Reserva #' . $reserva->id,
                'pin'                => $reserva->codigo_acceso,
                'effective_time'     => $efectivo->toDateTimeString(),
                'invalid_time'       => $invalido->toDateTimeString(),
                'external_reference' => 'reserva_' . $reserva->id,
            ]);

        if ($response->successful()) {
            $pinId = $response->json('data.provider_code_id');
            $pinReal = $response->json('data.pin') ?? $reserva->codigo_acceso;

            $reserva->update([
                'codigo_acceso'            => $pinReal,
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
     * Revoca el PIN de la cerradura.
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
            $response = Http::timeout(15)
                ->withHeaders(['X-API-Key' => config('services.tuya_app.api_key')])
                ->delete("{$tuyaAppUrl}/api/pins/{$reserva->ttlock_pin_id}");

            if ($response->successful()) {
                Log::info("AccessCodeService: PIN revocado para reserva {$reserva->id}");
            } else {
                Log::warning("AccessCodeService: error al revocar PIN para reserva {$reserva->id}: HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            Log::error("AccessCodeService: excepción al revocar PIN: " . $e->getMessage());
        }
    }

    /**
     * Notifica un error por WhatsApp y como notificación interna.
     */
    private function notificarError(Reserva $reserva, string $mensaje): void
    {
        // WhatsApp
        try {
            $whatsapp = app(\App\Services\WhatsappNotificationService::class);
            $whatsapp->sendToConfiguredRecipients("⚠️ ALERTA CERRADURA\n\nReserva #{$reserva->id}\n{$mensaje}");
        } catch (\Exception $e) {
            Log::error('No se pudo enviar alerta WhatsApp de cerradura', ['error' => $e->getMessage()]);
        }

        // Notificación interna
        try {
            \App\Models\Notification::createForAdmins(
                \App\Models\Notification::TYPE_SISTEMA,
                'Error cerradura',
                "Reserva #{$reserva->id}: {$mensaje}",
                ['reserva_id' => $reserva->id],
                \App\Models\Notification::PRIORITY_HIGH,
                \App\Models\Notification::CATEGORY_ERROR
            );
        } catch (\Exception $e) {
            Log::error('No se pudo crear notificación de cerradura', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Genera un código único entre reservas activas.
     *
     * [FIX 2026-04-19] Las cerraduras Tuya Smart Lock X7 requieren PIN de
     * EXACTAMENTE 7 digitos (rechazan cualquier otra longitud con HTTP 422
     * "PIN must be numeric and between 7 and 7 digits"). TTLock acepta
     * ventanas de longitud mas amplias pero 7 es un punto comun valido para
     * ambos, asi que lo usamos de forma homogenea.
     */
    private function generarCodigoUnico(int $digitos = 7): string
    {
        $intentos = 0;
        $maxIntentos = 100;
        $min = (int) str_repeat('1', 1) . str_repeat('0', $digitos - 1); // 1000000 para 7 digitos
        $max = (int) str_repeat('9', $digitos);                           // 9999999 para 7 digitos
        do {
            $codigo = str_pad((string) random_int($min, $max), $digitos, '0', STR_PAD_LEFT);
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
