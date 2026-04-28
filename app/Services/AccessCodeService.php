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

        // [2026-04-26] Cerradura manual: el codigo es la clave del apartamento.
        // Lo guardamos en codigo_apartamento (canonico nuevo) y en codigo_acceso
        // (legacy, por compat con lectores antiguos).
        $reserva->update([
            'codigo_acceso'            => $codigo,
            'codigo_apartamento'       => $codigo,
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
                // [2026-04-26 BUG-FIX HISTORICO] El codigo de emergencia ES
                // del PORTAL del edificio, NO del apartamento. Antes lo
                // metiamos en `codigo_acceso` (campo unico) y la vista lo
                // mostraba como "clave del apartamento" -> 28 huespedes
                // recibieron el codigo del portal etiquetado como clave de
                // su apartamento, no podian entrar a su piso, costo dia y
                // medio limpiarlo.
                //
                // Ahora separamos: el codigo de emergencia va SOLO a
                // `codigo_portal`. El `codigo_apartamento` se queda con la
                // clave fija del piso (si la hay). El campo `codigo_acceso`
                // legacy NO se contamina con el codigo del portal — solo se
                // pobla si NO hay clave de apartamento (para que apps
                // antiguas sigan funcionando minimamente).
                $claveApt = $apartamento->claves ?? null;
                $update = [
                    'codigo_portal'            => $codigoEmerg,
                    'codigo_apartamento'       => $claveApt ?: null,
                    'codigo_enviado_cerradura' => 1,
                ];
                // codigo_acceso legacy: ya no le metemos el codigo del portal.
                // Si hay clave de apartamento, la usamos. Si no, dejamos lo
                // que ya hubiera (no contaminamos con datos del portal).
                if ($claveApt) {
                    $update['codigo_acceso'] = $claveApt;
                }
                $reserva->update($update);
                Log::info("[Fallback] Reserva {$reserva->id}: codigo_portal={$codigoEmerg}, codigo_apartamento=" . ($claveApt ?: '(sin clave fija)') . " (fallback edificio {$edificio->id} proveedor {$proveedor})");
                return $codigoEmerg;
            }
            Log::warning("[Fallback] Edificio {$edificio->id} en modo fallback pero sin codigo_emergencia_portal configurado");
        }

        if (!$lockId) {
            // [2026-04-28 FIX #11] Si el apartamento tiene tipo_cerradura digital
            // (tuya/ttlock) pero no tiene lock_id configurado, NO generamos un
            // PIN unico que nunca se programara. En su lugar:
            //   1. Si el apartamento tiene una clave manual fija configurada
            //      (`apartamento->claves`), la usamos como fallback. El huesped
            //      la recibira por el flujo normal.
            //   2. Si tampoco tiene clave manual, alertamos al admin pero
            //      registramos codigo_acceso en blanco (no inventamos un PIN
            //      que llevaria al huesped a chocarse con una puerta cerrada).
            $claveManual = trim((string) ($apartamento->claves ?? ''));
            if ($claveManual !== '') {
                $reserva->update([
                    'codigo_acceso'            => $claveManual,
                    'codigo_apartamento'       => $claveManual,
                    'codigo_enviado_cerradura' => 1, // clave manual = siempre programada
                ]);
                Log::warning("AccessCodeService: apartamento {$apartamento->id} tipo '{$tipoCerradura}' sin lock_id, usando clave manual como fallback", [
                    'reserva_id' => $reserva->id, 'clave' => $claveManual,
                ]);
                $this->notificarError($reserva, "Apartamento {$apartamento->titulo} tiene cerradura {$tipoCerradura} pero sin lock_id configurado. Se ha usado la clave manual fija como fallback. CORREGIR EL APARTAMENTO en el panel admin.");
                return $claveManual;
            }

            // Sin lock_id Y sin clave manual: situacion grave, alertar pero NO
            // dar al huesped un PIN inventado.
            $reserva->update(['codigo_enviado_cerradura' => 0]);
            Log::error("AccessCodeService: apartamento {$apartamento->id} tipo '{$tipoCerradura}' SIN lock_id NI clave manual — huesped sin acceso", [
                'reserva_id' => $reserva->id,
            ]);
            $this->notificarError($reserva, "🚨 URGENTE: Apartamento {$apartamento->titulo} sin lock_id ni clave manual. Huesped #{$reserva->id} se quedara SIN ACCESO. Revisar configuracion YA.");
            return null;
        }

        $codigo = $esPortal
            ? $this->generarCodigoPortal()      // 000 + 4 digitos variables
            : $this->generarCodigoUnico();       // 7 digitos aleatorios

        // Validación: reservas de un solo día
        if (Carbon::parse($reserva->fecha_entrada)->isSameDay(Carbon::parse($reserva->fecha_salida))) {
            Log::warning('AccessCode: Reserva de un solo día, no se puede programar cerradura', ['reserva_id' => $reserva->id]);
            $this->guardarPinGenerado($reserva, $apartamento, $codigo, $esPortal, false);
            return $codigo;
        }

        $tuyaAppUrl = config('services.tuya_app.url');
        if (empty($tuyaAppUrl)) {
            $this->guardarPinGenerado($reserva, $apartamento, $codigo, $esPortal, false);
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
            $this->guardarPinGenerado($reserva, $apartamento, $codigo, $esPortal, false);
            Log::info("AccessCodeService: código para reserva {$reserva->id} diferido ({$diasHastaEntrada} días, ventana {$ventanaDias}d para {$tipoCerradura}).");
            return $codigo;
        }

        // [2026-04-28] Pre-flight de slots: si el lock esta saturado,
        // intenta purgar PINs de reservas finalizadas. NO bloquea el
        // flujo si algo falla — devolver true por defecto significa
        // "intenta programar igualmente" (es lo que ya hacia siempre
        // antes de este cambio).
        try {
            $slotMgr = app(\App\Services\CerraduraSlotManager::class);
            $hayHueco = $slotMgr->asegurarSlotLibre((int) $lockId);
            if (!$hayHueco) {
                Log::warning("[AccessCode] Lock {$lockId} saturado tras purga, programando igualmente con riesgo de fallo silencioso", [
                    'reserva_id' => $reserva->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[AccessCode] SlotManager fallo, sigo flujo normal: ' . $e->getMessage());
        }

        // Enviar a Tuyalaravel
        return $this->enviarATuyalaravel($reserva, $lockId, $codigo, $esPortal);
    }

    /**
     * [2026-04-26] Helper canonico para guardar un PIN generado en la reserva.
     *
     * Reglas:
     *  - Si el PIN es del PORTAL (cerradura compartida entre apartamentos),
     *    va al campo `codigo_portal`. La clave del apartamento (si hay)
     *    sigue intacta en `codigo_apartamento`.
     *  - Si el PIN es de la PUERTA del apartamento (cerradura individual),
     *    va a `codigo_apartamento`. Se mantiene la clave del portal en
     *    `codigo_portal` (si el edificio tiene `clave` configurada).
     *  - El campo legacy `codigo_acceso` se rellena con el PIN dinamico
     *    para no romper lectores antiguos. Pero ya NUNCA se contamina con
     *    el codigo de emergencia (eso vive solo en `codigo_portal`).
     */
    private function guardarPinGenerado(Reserva $reserva, $apartamento, string $codigo, bool $esPortal, bool $confirmado): void
    {
        $update = [
            'codigo_acceso'            => $codigo,  // legacy: lo seguimos rellenando
            'codigo_enviado_cerradura' => $confirmado ? 1 : 0,
        ];

        if ($esPortal) {
            $update['codigo_portal'] = $codigo;
            // codigo_apartamento se queda con apartamento->claves si existe
            if (!empty($apartamento->claves) && empty($reserva->codigo_apartamento)) {
                $update['codigo_apartamento'] = $apartamento->claves;
            }
        } else {
            $update['codigo_apartamento'] = $codigo;
            // codigo_portal se queda con la clave estatica del edificio si la hay
            $edif = $apartamento->edificioName ?? $apartamento->edificioRel ?? null;
            if (!$edif && !empty($apartamento->edificio_id)) {
                $edif = \App\Models\Edificio::find($apartamento->edificio_id);
            }
            if ($edif && !empty($edif->clave) && empty($reserva->codigo_portal)) {
                $update['codigo_portal'] = $edif->clave;
            }
        }

        $reserva->update($update);
    }

    /**
     * Envía el PIN a Tuyalaravel y guarda el resultado.
     * Si el gateway confirma, guarda codigo_enviado_cerradura = 1.
     * Si falla, guarda el código pero con codigo_enviado_cerradura = 0.
     * Tuyalaravel puede devolver un PIN diferente (modo offline TTLock) — usamos el que devuelve.
     */
    private function enviarATuyalaravel(Reserva $reserva, $lockId, string $codigo, bool $esPortal = false): ?string
    {
        $tuyaAppUrl = config('services.tuya_app.url');
        $efectivo = Carbon::parse($reserva->fecha_entrada)->setTime(15, 0, 0);
        $invalido = Carbon::parse($reserva->fecha_salida)->setTime(11, 0, 0);
        $apartamento = $reserva->apartamento;

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

                $this->guardarPinGenerado($reserva, $apartamento, $pinReal, $esPortal, true);
                $reserva->update(['ttlock_pin_id' => $pinId]);

                Log::info("AccessCodeService: PIN programado en cerradura para reserva {$reserva->id}.", [
                    'provider_code_id' => $pinId,
                ]);

                // [2026-04-28] Programar borrado automatico al vencer (libera
                // slot en la cerradura). Si la queue falla, el cron de
                // purga semanal lo recogera. try/catch para no romper jamas
                // el flujo principal de creacion del PIN.
                try {
                    $cuandoBorrar = $invalido->copy()->addMinutes(30); // 30 min de margen
                    \App\Jobs\BorrarPinAlVencer::dispatch($reserva->id)->delay($cuandoBorrar);
                    Log::info("[AccessCode] Job de borrado programado para reserva {$reserva->id} a las {$cuandoBorrar}");
                } catch (\Throwable $e) {
                    Log::warning("[AccessCode] No se pudo programar BorrarPinAlVencer: " . $e->getMessage());
                }

                // [FALLBACK] Exito -> resetear contador de fallos del edificio+proveedor
                $this->notificarResultadoAlFallback($reserva, true, null);

                return $pinReal;
            } else {
                $this->guardarPinGenerado($reserva, $apartamento, $codigo, $esPortal, false);
                Log::error("AccessCodeService: error HTTP {$response->status()} al programar cerradura.", [
                    'reserva_id' => $reserva->id,
                    'response' => $response->body(),
                ]);
                $this->notificarError($reserva, "Error HTTP {$response->status()} al programar cerradura.\nApartamento: {$reserva->apartamento->titulo}");
                $this->notificarResultadoAlFallback($reserva, false, "HTTP {$response->status()}: " . mb_substr($response->body(), 0, 200));
            }
        } catch (\Exception $e) {
            $this->guardarPinGenerado($reserva, $apartamento, $codigo, $esPortal, false);
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
                $pinViejo = $reserva->codigo_acceso;

                $reserva->update([
                    'codigo_acceso'            => $pinReal,
                    'ttlock_pin_id'            => $pinId,
                    'codigo_enviado_cerradura' => 1,
                ]);

                Log::info("AccessCodeService::reintento: PIN confirmado para reserva {$reserva->id}.", [
                    'provider_code_id' => $pinId,
                ]);

                // [2026-04-28 FIX auditoria #13] Si Tuyalaravel devolvio un
                // PIN distinto al que pediamos (modo offline TTLock genera
                // su propio PIN), el huesped tiene en su WhatsApp el viejo.
                // Notificamos del cambio con el template aprobado.
                if (trim((string) $pinViejo) !== trim((string) $pinReal) && !empty($pinViejo)) {
                    try {
                        app(\App\Services\CerraduraFallbackService::class)
                            ->enviarCodigoEmergenciaACliente($reserva);
                        Log::info("[reintento] Huesped notificado del cambio de PIN", [
                            'reserva_id' => $reserva->id,
                            'pin_viejo' => $pinViejo, 'pin_nuevo' => $pinReal,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('[reintento] No se pudo notificar al huesped del cambio: ' . $e->getMessage());
                    }
                }

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
