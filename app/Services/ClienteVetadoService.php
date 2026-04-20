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

            $n = $q->update([
                'vetada' => true,
                'veto_detectado_at' => now(),
                'veto_id' => $veto->id,
            ]);

            if ($n > 0) {
                Log::info('[Veto] Reservas futuras marcadas al crear veto', [
                    'veto_id' => $veto->id,
                    'n_reservas' => $n,
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
     * Mensaje de derecho de admision para enviar por WhatsApp/Channex
     * al cliente vetado en lugar de las claves.
     */
    public function mensajeDerechoAdmision(string $idiomaCodigo = 'es'): string
    {
        $lang = substr((string) $idiomaCodigo, 0, 2);
        return match ($lang) {
            'es' => "Estimado/a cliente,\n\nLamentamos comunicarle que, en ejercicio de nuestro derecho de admisión, no podemos confirmar su estancia. Por favor, póngase en contacto con nosotros para gestionar la devolución correspondiente.\n\nGracias por su comprensión.",
            'fr' => "Cher client,\n\nNous regrettons de vous informer que, dans l'exercice de notre droit d'admission, nous ne pouvons pas confirmer votre séjour. Veuillez nous contacter pour gérer le remboursement.\n\nMerci de votre compréhension.",
            'de' => "Sehr geehrter Gast,\n\nLeider müssen wir Ihnen mitteilen, dass wir im Rahmen unseres Hausrechts Ihren Aufenthalt nicht bestätigen können. Bitte kontaktieren Sie uns zur Abwicklung der Rückerstattung.\n\nVielen Dank für Ihr Verständnis.",
            default => "Dear guest,\n\nWe regret to inform you that, exercising our right of admission, we cannot confirm your stay. Please contact us to arrange the corresponding refund.\n\nThank you for your understanding.",
        };
    }
}
