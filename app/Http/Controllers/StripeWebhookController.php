<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Reserva;
use App\Models\IntentoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    /**
     * Manejar webhooks de Stripe
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        Log::info('[ReservaWeb] Stripe webhook: recibido', [
            'payload_length' => strlen($payload),
            'signature_present' => !empty($sigHeader),
        ]);

        if (!$webhookSecret) {
            Log::warning('[ReservaWeb] Stripe webhook: secret no configurado');
            return response()->json(['error' => 'Webhook secret no configurado'], 400);
        }

        try {
            if (class_exists('\Stripe\Webhook')) {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $webhookSecret
                );
            } else {
                Log::warning('[ReservaWeb] Stripe webhook: SDK no disponible - procesando SIN verificacion de firma. Configure stripe/stripe-php para habilitar verificacion.', [
                    'ip' => $request->ip(),
                ]);
                $event = json_decode($payload, true);
            }
        } catch (\Exception $e) {
            Log::error('[ReservaWeb] Stripe webhook: firma inválida', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Firma inválida'], 400);
        }

        $eventType = $event['type'] ?? $event->type ?? null;
        Log::info('[ReservaWeb] Stripe webhook: evento', ['type' => $eventType]);

        // Procesar el evento
        switch ($eventType) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event['data']['object'] ?? $event->data->object ?? null);
                break;
            
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event['data']['object'] ?? $event->data->object ?? null);
                break;
            
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event['data']['object'] ?? $event->data->object ?? null);
                break;
            
            default:
                Log::info('[ReservaWeb] Stripe webhook: evento no manejado', [
                    'type' => $eventType ?? 'unknown',
                ]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Manejar sesión de checkout completada
     */
    private function handleCheckoutSessionCompleted($session)
    {
        try {
            $sessionId = $session['id'] ?? $session->id ?? null;

            Log::info('[ReservaWeb] Stripe checkout.session.completed: inicio', [
                'session_id' => $sessionId ? substr($sessionId, 0, 24) . '...' : null,
            ]);

            if (!$sessionId) {
                Log::warning('[ReservaWeb] Stripe checkout.session.completed: sin session_id');
                return;
            }

            $paymentIntentId = $session['payment_intent'] ?? $session->payment_intent ?? null;
            $paymentStatus = $session['payment_status'] ?? $session->payment_status ?? 'unknown';

            // Usar lockForUpdate para evitar condiciones de carrera con webhooks duplicados de Stripe
            $pago = DB::transaction(function () use ($sessionId, $paymentIntentId, $paymentStatus) {
                $pago = Pago::where('stripe_checkout_session_id', $sessionId)->lockForUpdate()->first();

                if (!$pago) {
                    Log::warning('[ReservaWeb] Stripe checkout.session.completed: pago no encontrado', [
                        'session_id_prefijo' => substr($sessionId, 0, 24),
                    ]);
                    return null;
                }

                if ($pago->estado === 'completado') {
                    Log::info('[ReservaWeb] Stripe checkout.session.completed: pago ya procesado (idempotencia)', [
                        'pago_id' => $pago->id,
                    ]);
                    return null; // Ya procesado
                }

                if ($paymentStatus === 'paid') {
                    $pago->update([
                        'estado' => 'completado',
                        'stripe_payment_intent_id' => $paymentIntentId,
                        'fecha_pago' => now(),
                    ]);
                    return $pago;
                }

                return null;
            });

            if (!$pago) {
                Log::info('[ReservaWeb] Stripe checkout.session.completed: sin cambios (ya procesado, no encontrado, o status no paid)', [
                    'session_id_prefijo' => substr($sessionId, 0, 24),
                    'payment_status' => $paymentStatus,
                ]);
                return;
            }

            Log::info('[ReservaWeb] Stripe checkout.session.completed: pago encontrado y actualizado', [
                'pago_id' => $pago->id,
                'reserva_id' => $pago->reserva_id,
                'payment_status' => $paymentStatus,
            ]);

            Log::info('[ReservaWeb] Stripe checkout.session.completed: pago actualizado a completado', [
                    'pago_id' => $pago->id,
                    'payment_intent_id' => $paymentIntentId,
                ]);

                // Actualizar reserva a confirmada
                if ($pago->reserva) {
                    $pago->reserva->update(['estado_id' => 1]); // Confirmada

                    Log::info('[ReservaWeb] Stripe checkout.session.completed: reserva confirmada', [
                        'reserva_id' => $pago->reserva->id,
                        'codigo_reserva' => $pago->reserva->codigo_reserva,
                        'apartamento_id' => $pago->reserva->apartamento_id,
                    ]);

                    // Cerrar disponibilidad en Channex tras confirmar pago
                    try {
                        $reserva = $pago->reserva;
                        $apartamento = \App\Models\Apartamento::with('roomTypes')->find($reserva->apartamento_id);
                        if ($apartamento && $apartamento->id_channex) {
                            $roomType = $apartamento->roomTypes()->whereNotNull('id_channex')->first();
                            if ($roomType) {
                                $startDate = \Carbon\Carbon::parse($reserva->fecha_entrada);
                                $endDate = \Carbon\Carbon::parse($reserva->fecha_salida)->subDay();
                                $values = [];
                                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                                    $values[] = [
                                        'property_id' => $apartamento->id_channex,
                                        'room_type_id' => $roomType->id_channex,
                                        'date' => $date->toDateString(),
                                        'availability' => 0,
                                    ];
                                }
                                if (!empty($values)) {
                                    $apiUrl = env('CHANNEX_URL', 'https://app.channex.io/api/v1');
                                    $apiToken = env('CHANNEX_TOKEN') ?: env('CHANNEX_API_TOKEN');
                                    \Illuminate\Support\Facades\Http::withHeaders(['user-api-key' => $apiToken])
                                        ->post("{$apiUrl}/availability", ['values' => $values]);
                                    Log::info('[ReservaWeb] Stripe webhook: disponibilidad cerrada en Channex', [
                                        'reserva_id' => $reserva->id,
                                        'apartamento_id' => $apartamento->id,
                                        'fechas' => count($values) . ' días',
                                    ]);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('[ReservaWeb] Stripe webhook: error al cerrar disponibilidad en Channex', [
                            'reserva_id' => $pago->reserva->id ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Crear notificación
                    \App\Models\Notification::createForAdmins(
                        \App\Models\Notification::TYPE_RESERVA,
                        'Nueva Reserva Confirmada',
                        "Reserva {$pago->reserva->codigo_reserva} confirmada y pagada",
                        [
                            'reserva_id' => $pago->reserva->id,
                            'pago_id' => $pago->id,
                        ],
                        \App\Models\Notification::PRIORITY_HIGH,
                        \App\Models\Notification::CATEGORY_SUCCESS,
                        route('admin.reservas.show', $pago->reserva->id)
                    );
                }

                // Actualizar intento de pago
                $updated = IntentoPago::where('stripe_checkout_session_id', $sessionId)
                    ->update([
                        'estado' => 'exitoso',
                        'stripe_payment_intent_id' => $paymentIntentId,
                        'respuesta_stripe' => is_array($session) ? $session : (array)$session,
                    ]);

                Log::info('[ReservaWeb] Stripe checkout.session.completed: intentos pago actualizados', [
                    'session_id_prefijo' => substr($sessionId, 0, 24),
                    'intentos_actualizados' => $updated,
                ]);

                // Si es un pago de extras, actualizar reserva_servicios
                if (isset($pago->metadata['tipo']) && $pago->metadata['tipo'] === 'extras') {
                    \App\Models\ReservaServicio::where('pago_id', $pago->id)
                        ->update([
                            'estado' => 'pagado',
                            'fecha_pago' => now(),
                            'stripe_payment_intent_id' => $paymentIntentId,
                        ]);

                    Log::info('[ReservaWeb] Stripe checkout.session.completed: extras marcados como pagados', [
                        'reserva_id' => $pago->reserva_id,
                    ]);

                    // Notificar al equipo si es early check-in o late checkout
                    $reserva = $pago->reserva;
                    $reservaServicios = \App\Models\ReservaServicio::where('pago_id', $pago->id)->with('servicio')->get();
                    foreach ($reservaServicios as $rs) {
                        $servicio = $rs->servicio;
                        if ($servicio && (
                            stripos($servicio->nombre, 'early') !== false ||
                            stripos($servicio->nombre, 'late') !== false ||
                            stripos($servicio->nombre, 'temprano') !== false ||
                            stripos($servicio->nombre, 'tardío') !== false ||
                            stripos($servicio->nombre, 'tardio') !== false
                        )) {
                            try {
                                \App\Services\AlertaEquipoService::alertar(
                                    'SERVICIO EXTRA CONTRATADO',
                                    "Reserva: {$reserva->codigo_reserva}\n"
                                    . "Servicio: {$servicio->nombre}\n"
                                    . "Apartamento: " . ($reserva->apartamento->titulo ?? 'N/A') . "\n"
                                    . "Entrada: {$reserva->fecha_entrada}\n"
                                    . "Salida: {$reserva->fecha_salida}\n"
                                    . "PRIORIZAR LIMPIEZA DE ESTE APARTAMENTO",
                                    'servicio_extra'
                                );
                            } catch (\Exception $e) {
                                Log::error('Error notificando servicio extra desde webhook: ' . $e->getMessage());
                            }
                        }
                    }
                }

                Log::info('[ReservaWeb] Stripe checkout.session.completed: flujo completado', [
                    'pago_id' => $pago->id,
                    'reserva_id' => $pago->reserva_id,
                ]);
        } catch (\Exception $e) {
            Log::error('[ReservaWeb] Stripe checkout.session.completed: excepción', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * Manejar payment intent exitoso
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        try {
            $paymentIntentId = $paymentIntent['id'] ?? $paymentIntent->id ?? null;
            
            if (!$paymentIntentId) {
                return;
            }

            $pago = Pago::where('stripe_payment_intent_id', $paymentIntentId)->first();
            
            if ($pago && $pago->estado !== 'completado') {
                $pago->update([
                    'estado' => 'completado',
                    'fecha_pago' => now(),
                ]);

                if ($pago->reserva) {
                    $pago->reserva->update(['estado_id' => 1]);

                    // Sync availability to Channex
                    try {
                        (new \App\Http\Controllers\ARIController())->fullSync();
                    } catch (\Exception $e) {
                        Log::error('Error syncing Channex after payment: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al procesar payment_intent.succeeded: ' . $e->getMessage());
        }
    }

    /**
     * Manejar payment intent fallido
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        try {
            $paymentIntentId = $paymentIntent['id'] ?? $paymentIntent->id ?? null;
            
            if (!$paymentIntentId) {
                return;
            }

            $pago = Pago::where('stripe_payment_intent_id', $paymentIntentId)->first();
            
            if ($pago) {
                $failureMessage = $paymentIntent['last_payment_error']['message'] ?? 'Pago fallido';

                $pago->update(['estado' => 'fallido']);

                // Registrar intento fallido
                IntentoPago::create([
                    'pago_id' => $pago->id,
                    'reserva_id' => $pago->reserva_id,
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'estado' => 'fallido',
                    'monto' => $pago->monto,
                    'moneda' => $pago->moneda,
                    'mensaje_error' => $failureMessage,
                    'respuesta_stripe' => is_array($paymentIntent) ? $paymentIntent : (array)$paymentIntent,
                    'fecha_intento' => now(),
                ]);

                // Alerta WhatsApp
                try {
                    $reserva = $pago->reserva;
                    $whatsapp = app(\App\Services\WhatsappNotificationService::class);
                    $whatsapp->sendToConfiguredRecipients(
                        "🔴 ALERTA PAGO FALLIDO\n\nPago fallido en Stripe\nReserva: " . ($reserva->codigo_reserva ?? 'N/A') . "\nMonto: {$pago->monto} {$pago->moneda}\nError: {$failureMessage}"
                    );
                } catch (\Exception $alertEx) {
                    Log::error('No se pudo enviar alerta WhatsApp de pago fallido', ['error' => $alertEx->getMessage()]);
                }

                // Notificación interna
                try {
                    \App\Models\Notification::createForAdmins(
                        \App\Models\Notification::TYPE_SISTEMA,
                        'Pago Stripe fallido',
                        "Pago fallido: {$failureMessage}",
                        ['pago_id' => $pago->id, 'reserva_id' => $pago->reserva_id],
                        \App\Models\Notification::PRIORITY_HIGH,
                        \App\Models\Notification::CATEGORY_ERROR
                    );
                } catch (\Exception $notifEx) {
                    Log::error('No se pudo crear notificación de pago fallido', ['error' => $notifEx->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al procesar payment_intent.payment_failed: ' . $e->getMessage());
        }
    }
}
