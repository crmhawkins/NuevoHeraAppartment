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
        // [2026-04-19] Sistema de veto: si la reserva esta marcada (o el
        // cliente cae bajo un veto activo), no generamos PIN. La reserva
        // seguira sin codigo_acceso y el flujo de envio de claves mandara
        // el mensaje de derecho de admision en lugar de las claves.
        try {
            $vetoSvc = app(\App\Services\ClienteVetadoService::class);
            $vetoSvc->detectarYMarcarReserva($reserva);
            if ($reserva->vetada) {
                Log::warning('[AccessCodeService] Reserva vetada, abortando generacion PIN', [
                    'reserva_id' => $reserva->id,
                    'veto_id' => $reserva->veto_id,
                ]);
                return null;
            }
        } catch (\Throwable $e) {
            Log::error('[AccessCodeService] Error comprobando veto', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
            ]);
            // Si falla la comprobacion, seguimos (no bloqueamos por error tecnico)
        }

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

        // [2026-04-19] Deteccion cerradura exterior (portal) vs interior (puerta):
        //   - Si el mismo lock_id aparece en varios apartamentos -> es PORTAL
        //     (compartida) -> patron 000xxxx (3 ceros + 4 digitos) para que el
        //     huesped la teclee facilmente varias veces al dia.
        //   - Si aparece solo en este apartamento -> es INTERIOR -> 7 digitos
        //     aleatorios (solo se usa 1 vez/dia, copia-pega desde movil).
        $esPortal = false;
        if ($lockId) {
            $count = \App\Models\Apartamento::where(function ($q) use ($lockId) {
                $q->where('tuyalaravel_lock_id', $lockId)->orWhere('ttlock_lock_id', $lockId);
            })->count();
            $esPortal = $count > 1;
        }

        // [2026-04-19 FALLBACK] Si el edificio de este apartamento esta en modo
        // fallback para el proveedor correspondiente (tuya o ttlock), saltamos
        // toda la logica de programacion y devolvemos directamente el codigo
        // de emergencia. El cliente recibira ese codigo en lugar del unico.
        $edificio = $apartamento->edificioName ?? $apartamento->edificioRel ?? null;
        if (!$edificio && !empty($apartamento->edificio_id)) {
            $edificio = \App\Models\Edificio::find($apartamento->edificio_id);
        }
        $fallbackSvc = app(\App\Services\CerraduraFallbackService::class);
        $proveedor = strtolower($tipoCerradura); // 'tuya' o 'ttlock'
        if ($edificio && $fallbackSvc->estaEnFallback($edificio, $proveedor)) {
            $codigoEmerg = $edificio->codigo_emergencia_portal;
            if ($codigoEmerg) {
                $reserva->update([
                    'codigo_acceso' => $codigoEmerg,
                    'codigo_enviado_cerradura' => 1, // asumido porque es codigo estatico ya programado
                ]);
                Log::info("[Fallback] Reserva {$reserva->id}: asignado codigo emergencia {$codigoEmerg} (modo fallback activo en edificio {$edificio->id} proveedor {$proveedor})");
                return $codigoEmerg;
            }
            Log::warning("[Fallback] Edificio {$edificio->id} en modo fallback pero sin codigo_emergencia_portal configurado");
        }

        if (!$lockId) {
            $codigo = $this->generarCodigoUnico();
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::warning("AccessCodeService: apartamento {$apartamento->id} tipo '{$tipoCerradura}' sin lock_id.");
            $this->notificarError($reserva, "Apartamento {$apartamento->titulo} tiene cerradura {$tipoCerradura} pero no tiene lock_id configurado.");
            return $codigo;
        }

        $codigo = $esPortal
            ? $this->generarCodigoPortal()      // 000 + 4 digitos variables
            : $this->generarCodigoUnico();       // 7 digitos aleatorios

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

        // [2026-04-20] Ventana de programacion segun proveedor:
        //  - TTLock: hasta 150 dias (limite de su cloud ~180d)
        //  - Tuya:   hasta 7 dias (la cerradura del portal es compartida y el
        //            slot de temp_passwords es limitado ~10; si programamos
        //            con meses de antelacion saturamos la cerradura y las
        //            reservas inmediatas no pueden crearse).
        // Si falta mas, guardamos el PIN en BD pero no lo enviamos aun. El
        // cron 'cerraduras:programar-proximas' lo enviara cuando toque.
        $ventanaDias = ($tipoCerradura === 'tuya') ? 7 : 150;
        $diasHastaEntrada = Carbon::now()->diffInDays(Carbon::parse($reserva->fecha_entrada), false);
        if ($diasHastaEntrada > $ventanaDias) {
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::info("AccessCodeService: código para reserva {$reserva->id} diferido ({$diasHastaEntrada} días, ventana {$ventanaDias}d para {$tipoCerradura}).");
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

                // [FALLBACK] Exito -> resetear contador de fallos del edificio+proveedor
                $this->notificarResultadoAlFallback($reserva, true, null);

                return $pinReal;
            } else {
                $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
                Log::error("AccessCodeService: error HTTP {$response->status()} al programar cerradura.", [
                    'reserva_id' => $reserva->id,
                    'response' => $response->body(),
                ]);
                $this->notificarError($reserva, "Error HTTP {$response->status()} al programar cerradura.\nApartamento: {$reserva->apartamento->titulo}");
                $this->notificarResultadoAlFallback($reserva, false, "HTTP {$response->status()}: " . mb_substr($response->body(), 0, 200));
            }
        } catch (\Exception $e) {
            $reserva->update(['codigo_acceso' => $codigo, 'codigo_enviado_cerradura' => 0]);
            Log::error("AccessCodeService: excepción al llamar a Tuyalaravel: " . $e->getMessage());
            $this->notificarError($reserva, "Error al conectar con Tuyalaravel: {$e->getMessage()}\nApartamento: {$reserva->apartamento->titulo}");
            $this->notificarResultadoAlFallback($reserva, false, 'Exception: ' . $e->getMessage());
        }

        return $codigo;
    }

    /**
     * [2026-04-19] Notifica al CerraduraFallbackService el resultado del
     * intento de programacion para que contabilice y, si procede, active
     * el modo fallback del edificio+proveedor.
     */
    private function notificarResultadoAlFallback(Reserva $reserva, bool $exito, ?string $motivo): void
    {
        try {
            $apt = $reserva->apartamento;
            if (!$apt) return;
            $edificio = $apt->edificioName ?? $apt->edificioRel ?? null;
            if (!$edificio && !empty($apt->edificio_id)) {
                $edificio = \App\Models\Edificio::find($apt->edificio_id);
            }
            if (!$edificio) return;

            $tipo = strtolower($apt->tipo_cerradura ?? '');
            if (!in_array($tipo, ['tuya', 'ttlock'], true)) return;

            $svc = app(\App\Services\CerraduraFallbackService::class);
            if ($exito) {
                $svc->registrarExito($edificio, $tipo);
            } else {
                $svc->registrarFallo($edificio, $tipo, $motivo ?? '');
            }
        } catch (\Throwable $e) {
            // No debe interrumpir el flujo principal
            Log::warning('[Fallback] error registrando resultado: ' . $e->getMessage());
        }
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

    /**
     * [2026-04-19] Genera PIN con patron portal '000xxxx':
     *   - Prefijo fijo '000' (3 ceros) para que el huesped identifique que
     *     es el codigo del portal exterior y lo teclee rapido.
     *   - Sufijo de 4 digitos variables (10.000 combinaciones: 0000-9999).
     *   - Total 7 digitos (cumple requisito Tuya Smart Lock X7).
     *
     * Solo se usa para cerraduras COMPARTIDAS (portal de un edificio). Para
     * cerraduras de puerta individual usamos generarCodigoUnico() con 7
     * digitos totalmente aleatorios, que se copia-pega desde el WhatsApp.
     *
     * Unicidad: se comprueba contra reservas con fecha_salida >= hoy. Con
     * 10.000 combinaciones aguanta sin problemas unas pocas decenas de
     * reservas concurrentes en el mismo edificio.
     */
    private function generarCodigoPortal(): string
    {
        $intentos = 0;
        $maxIntentos = 100;
        do {
            $sufijo = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $codigo = '000' . $sufijo;
            $existe = Reserva::where('codigo_acceso', $codigo)
                ->where('fecha_salida', '>=', now()->toDateString())
                ->whereNotNull('codigo_acceso')
                ->exists();
            $intentos++;
        } while ($existe && $intentos < $maxIntentos);

        if ($existe) {
            throw new \Exception('No se pudo generar un código de portal único después de ' . $maxIntentos . ' intentos. Espacio 000xxxx saturado, considera migrar a 7 digitos aleatorios.');
        }

        return $codigo;
    }
}
