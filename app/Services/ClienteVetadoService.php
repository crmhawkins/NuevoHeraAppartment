<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ClienteVetado;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-19] Servicio central del sistema de vetos ("derecho de admision").
 *
 * Logica de match:
 *  - Se considera VETADO si coincide num_identificacion (normalizado) O
 *    telefono (normalizado) con algun veto activo (levantado_at NULL).
 *  - La comprobacion es tolerante a formato: quitamos espacios, guiones,
 *    prefijos +34 / 0034 y pasamos DNI a mayusculas.
 *
 * Integracion:
 *  - AccessCodeService consulta isVetado() antes de programar PIN.
 *  - MIRService::enviarSiLista consulta antes de empaquetar SOAP.
 *  - Comandos de envio de claves consultan y, si hay veto, mandan el
 *    mensaje de derecho de admision en lugar de las claves.
 *  - CheckInPublicController llama detectarYMarcarReserva() al guardar
 *    los datos del huesped, que es cuando tenemos DNI real.
 */
class ClienteVetadoService
{
    /**
     * Veta a un cliente. Si ya existe un veto activo con mismo DNI/telefono,
     * lo devuelve sin duplicar.
     */
    public function vetar(Cliente $cliente, string $motivo, ?User $admin = null, ?string $notasInternas = null): ClienteVetado
    {
        $dni = $this->normalizarDni($cliente->num_identificacion ?? null);
        $tel = $this->normalizarTelefono($cliente->telefono ?? $cliente->telefono_movil ?? null);

        // Reutilizar veto activo si ya existe
        $existente = $this->buscarVetoActivo($dni, $tel);
        if ($existente) {
            Log::info('[Veto] Veto ya existente, no duplicado', [
                'veto_id' => $existente->id,
                'cliente_id' => $cliente->id,
            ]);
            return $existente;
        }

        $veto = ClienteVetado::create([
            'num_identificacion' => $dni,
            'telefono' => $tel,
            'cliente_id_original' => $cliente->id,
            'motivo' => $motivo,
            'vetado_por_user_id' => $admin?->id,
            'vetado_at' => now(),
            'notas_internas' => $notasInternas,
        ]);

        Log::warning('[Veto] Cliente vetado', [
            'veto_id' => $veto->id,
            'cliente_id' => $cliente->id,
            'dni' => $dni,
            'tel' => $tel,
            'admin_id' => $admin?->id,
            'motivo' => $motivo,
        ]);

        // Marca reservas futuras del mismo cliente (entrada >= hoy) como vetadas
        $this->marcarReservasFuturas($veto);

        return $veto;
    }

    /**
     * Levanta un veto (no lo borra, conserva el historico).
     */
    public function levantar(int $vetoId, ?User $admin = null, ?string $nota = null): void
    {
        $veto = ClienteVetado::findOrFail($vetoId);
        if ($veto->levantado_at) {
            return; // ya levantado
        }

        $veto->levantado_at = now();
        $veto->levantado_por_user_id = $admin?->id;
        if ($nota) {
            $veto->notas_internas = trim(($veto->notas_internas ? $veto->notas_internas . "\n" : '') . '[Levantado] ' . $nota);
        }
        $veto->save();

        // Quitar marca en reservas asociadas que aun no han entrado
        Reserva::where('veto_id', $veto->id)
            ->whereDate('fecha_entrada', '>=', now()->toDateString())
            ->update([
                'vetada' => false,
                'veto_detectado_at' => null,
                'veto_id' => null,
            ]);

        Log::info('[Veto] Veto levantado', [
            'veto_id' => $veto->id,
            'admin_id' => $admin?->id,
        ]);
    }

    /**
     * Devuelve el veto activo que aplica al cliente (o null).
     */
    public function isVetado(Cliente $cliente): ?ClienteVetado
    {
        $dni = $this->normalizarDni($cliente->num_identificacion ?? null);
        $tel = $this->normalizarTelefono($cliente->telefono ?? $cliente->telefono_movil ?? null);

        return $this->buscarVetoActivo($dni, $tel);
    }

    /**
     * Version "raw": comprueba por DNI o telefono directamente. Util
     * cuando aun no hay Cliente guardado (por ejemplo en el check-in
     * publico justo al recibir el formulario).
     */
    public function isVetadoRaw(?string $dni, ?string $telefono): ?ClienteVetado
    {
        return $this->buscarVetoActivo(
            $this->normalizarDni($dni),
            $this->normalizarTelefono($telefono)
        );
    }

    /**
     * Mira si el cliente de una reserva esta vetado y, si lo esta, marca
     * la reserva. Devuelve true si se marco (o ya estaba marcada).
     */
    public function detectarYMarcarReserva(Reserva $reserva): bool
    {
        if ($reserva->vetada) {
            return true;
        }

        $cliente = $reserva->cliente;
        if (!$cliente) {
            return false;
        }

        $veto = $this->isVetado($cliente);
        if (!$veto) {
            return false;
        }

        $reserva->vetada = true;
        $reserva->veto_detectado_at = now();
        $reserva->veto_id = $veto->id;
        $reserva->save();

        Log::warning('[Veto] Reserva marcada como vetada', [
            'reserva_id' => $reserva->id,
            'veto_id' => $veto->id,
            'cliente_id' => $cliente->id,
        ]);

        return true;
    }

    /**
     * Listado de vetos activos (para panel admin).
     */
    public function getVetosActivos(): Collection
    {
        return ClienteVetado::activos()
            ->with(['clienteOriginal', 'vetadoPor'])
            ->orderByDesc('vetado_at')
            ->get();
    }

    /**
     * Historico completo (activos + levantados).
     */
    public function getHistorico(): Collection
    {
        return ClienteVetado::with(['clienteOriginal', 'vetadoPor', 'levantadoPor'])
            ->orderByDesc('vetado_at')
            ->get();
    }

    // -----------------------------------------------------------------
    // Internos
    // -----------------------------------------------------------------

    private function buscarVetoActivo(?string $dni, ?string $tel): ?ClienteVetado
    {
        if (!$dni && !$tel) {
            return null;
        }

        $q = ClienteVetado::activos();
        $q->where(function ($w) use ($dni, $tel) {
            if ($dni) {
                $w->orWhere('num_identificacion', $dni);
            }
            if ($tel) {
                $w->orWhere('telefono', $tel);
            }
        });

        return $q->first();
    }

    private function marcarReservasFuturas(ClienteVetado $veto): void
    {
        try {
            $q = Reserva::query()
                ->whereDate('fecha_entrada', '>=', now()->toDateString())
                ->whereHas('cliente', function ($w) use ($veto) {
                    $w->where(function ($ww) use ($veto) {
                        if ($veto->num_identificacion) {
                            $ww->orWhere('num_identificacion', $veto->num_identificacion);
                        }
                        if ($veto->telefono) {
                            $ww->orWhere('telefono', $veto->telefono)
                               ->orWhere('telefono_movil', $veto->telefono);
                        }
                    });
                });

            // Traer primero para poder cancelar una a una (manteniendo auditoria)
            $reservasFuturas = (clone $q)->get();

            foreach ($reservasFuturas as $r) {
                $r->vetada = true;
                $r->veto_detectado_at = now();
                $r->veto_id = $veto->id;
                $r->save();

                // Cancelar para liberar disponibilidad
                $this->cancelarReservaVetada($r);
            }

            if ($reservasFuturas->count() > 0) {
                Log::info('[Veto] Reservas futuras marcadas y CANCELADAS al crear veto', [
                    'veto_id' => $veto->id,
                    'n_reservas' => $reservasFuturas->count(),
                    'ids' => $reservasFuturas->pluck('id')->all(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[Veto] Error marcando reservas futuras', [
                'veto_id' => $veto->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizarDni(?string $dni): ?string
    {
        if (!$dni) return null;
        $dni = strtoupper(trim($dni));
        $dni = preg_replace('/[\s\-\.]/', '', $dni);
        return $dni !== '' ? $dni : null;
    }

    private function normalizarTelefono(?string $tel): ?string
    {
        if (!$tel) return null;
        $tel = preg_replace('/[\s\-\.\(\)]/', '', trim($tel));
        // Quitar prefijo +34 o 0034 (Espana) para comparar robustamente
        $tel = preg_replace('/^\+?0*34/', '', $tel);
        return $tel !== '' ? $tel : null;
    }

    /**
     * Mensaje de derecho de admision (envio al final, cuando ya hay DNI).
     * Se manda en lugar de las claves si se confirma el veto en check-in.
     */
    public function mensajeDerechoAdmision(string $idiomaCodigo = 'es'): string
    {
        $lang = substr((string) $idiomaCodigo, 0, 2);
        return match ($lang) {
            'es' => "Su reserva ha sido bloqueada por nuestro servicio de derecho de admisión.\n\nSe procederá a la anulación de la misma y a la devolución del importe. Si considera que esto se debe a un error, por favor contacte con el 630625624 en horario laboral de lunes a viernes.",
            'fr' => "Votre réservation a été bloquée par notre service du droit d'admission.\n\nNous allons procéder à son annulation et au remboursement du montant. Si vous pensez qu'il s'agit d'une erreur, veuillez contacter le +34 630 625 624 en horaire de bureau, du lundi au vendredi.",
            'de' => "Ihre Buchung wurde von unserem Hausrecht-Service gesperrt.\n\nDie Buchung wird storniert und der Betrag erstattet. Falls Sie der Meinung sind, dass es sich um einen Fehler handelt, kontaktieren Sie bitte +34 630 625 624 werktags (Montag bis Freitag).",
            default => "Your reservation has been blocked by our right of admission service.\n\nIt will be cancelled and the amount refunded. If you believe this is a mistake, please contact +34 630 625 624 during office hours, Monday to Friday.",
        };
    }

    /**
     * Mensaje de bloqueo que se envia EN LUGAR del mensaje de bienvenida
     * cuando la reserva entra ya marcada como vetada (match por telefono al
     * llegar la reserva de Booking/Airbnb antes del check-in).
     *
     * Es el mismo texto que derechoAdmision — se unifica porque el cliente
     * no debe ver dos mensajes distintos; el mensaje ya incluye el telefono
     * de contacto y la info de anulacion.
     */
    public function mensajeBloqueoBienvenida(string $idiomaCodigo = 'es'): string
    {
        return $this->mensajeDerechoAdmision($idiomaCodigo);
    }

    /**
     * Cancela una reserva vetada: estado_id=4 (cancelada) + marca auditoria.
     * Se llama automaticamente al detectar el veto para liberar la disponibilidad.
     */
    public function cancelarReservaVetada(\App\Models\Reserva $reserva): void
    {
        if ((int) $reserva->estado_id === 4) {
            return; // ya cancelada
        }

        $reserva->estado_id = 4;
        // Nota de auditoria (si existe el campo 'observaciones', no rompemos si no)
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('reservas', 'observaciones')) {
                $nota = "[" . now()->format('Y-m-d H:i') . "] CANCELADA AUTOMATICAMENTE por veto #"
                    . ($reserva->veto_id ?? '?') . "\n";
                $reserva->observaciones = $nota . ($reserva->observaciones ?? '');
            }
        } catch (\Throwable $e) { /* ignora */ }
        $reserva->save();

        Log::warning('[Veto] Reserva cancelada automaticamente', [
            'reserva_id' => $reserva->id,
            'veto_id' => $reserva->veto_id,
        ]);

        // Alerta por WhatsApp al admin: "Incidencia - reserva cancelada por cliente vetado"
        $this->alertarAdminCancelacion($reserva);
    }

    /**
     * Manda WhatsApp al admin con los datos de la reserva cancelada por veto.
     * Se dispara una vez por cancelacion (si se ejecuta varias veces sobre la
     * misma reserva, la guard de "ya cancelada" en cancelarReservaVetada evita
     * duplicados en la misma iteracion).
     */
    private function alertarAdminCancelacion(\App\Models\Reserva $reserva): void
    {
        try {
            $reserva->loadMissing(['cliente', 'apartamento']);

            $cliente = $reserva->cliente;
            $apt = $reserva->apartamento;

            $mensaje = "🚫 INCIDENCIA — Reserva cancelada por cliente vetado\n\n"
                . "Reserva: #{$reserva->id}"
                . ($reserva->codigo_reserva ? " (cod: {$reserva->codigo_reserva})" : "")
                . "\n"
                . "Apartamento: " . ($apt->titulo ?? ('#' . ($reserva->apartamento_id ?? '-'))) . "\n"
                . "Cliente: " . ($cliente->nombre ?? $cliente->alias ?? '-') . " "
                . ($cliente->apellido1 ?? '') . "\n"
                . "DNI: " . ($cliente->num_identificacion ?? '-') . "\n"
                . "Telefono: " . ($cliente->telefono ?? $cliente->telefono_movil ?? '-') . "\n"
                . "Entrada: " . ($reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format('d/m/Y') : '-')
                . " | Salida: " . ($reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format('d/m/Y') : '-') . "\n"
                . "Origen: " . ($reserva->origen ?? '-') . "\n"
                . "Veto #" . ($reserva->veto_id ?? '?')
                . "\n\nLa disponibilidad ha sido liberada. Revisa si procede devolucion.";

            app(\App\Services\WhatsappNotificationService::class)->sendToConfiguredRecipients($mensaje);
        } catch (\Throwable $e) {
            Log::error('[Veto] No se pudo enviar alerta WhatsApp de cancelacion', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
