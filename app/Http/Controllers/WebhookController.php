<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\ChatGpt;
use App\Models\Cliente;
use App\Models\MensajeChat;
use App\Models\PromptAsistente;
use App\Models\RatePlan;
use App\Models\Reserva;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    private $apiUrl;
    private $apiToken;

    public function __construct()
    {
        $this->apiUrl = env('CHANNEX_URL');
        $this->apiToken = env('CHANNEX_TOKEN');
    }

    /**
     * Verify the Channex webhook secret if configured.
     * Returns a JSON error response if verification fails, or null if OK.
     */
    private function verifyWebhookSecret(Request $request, string $eventType)
    {
        $secret = $request->header('X-Webhook-Secret') ?? $request->header('X-Channex-Secret');
        $expectedSecret = env('CHANNEX_WEBHOOK_SECRET');

        if ($expectedSecret && $secret !== $expectedSecret) {
            Log::warning("[Webhook] Invalid signature for [{$eventType}]", [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return null;
    }

    private function saveToWebhooksFolder($filename, $data)
    {
        // Ruta completa para guardar en la carpeta "webhooks"
        $path = "webhooks/{$filename}";
        Storage::disk('publico')->put($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function ariChanges(Request $request, $id)
    {
        if ($error = $this->verifyWebhookSecret($request, 'ari-changes')) return $error;
        $apartamento = Apartamento::find($id);

        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Formato para el nombre del archivo
        $filename = "ariChanges_{$fecha}.txt";

        $this->saveToWebhooksFolder($filename, $request->all());

        return response()->json(['status' => true]);
    }

    /**
     * Maneja webhooks de reservas de Channex
     *
     * Según la documentación de Channex:
     * - Cada modificación de reserva genera un nuevo revision_id
     * - El booking_id permanece igual para la misma reserva
     * - Se debe verificar si la reserva ya existe antes de crear una nueva
     * - Las modificaciones incluyen cambios en fechas, habitaciones, precios, etc.
     *
     * LÓGICA IMPLEMENTADA:
     * 1. Si la reserva existe (modificación): ACTUALIZAR la existente
     * 2. Si la reserva NO existe (nueva): CREAR una nueva
     * 3. NUNCA crear nueva reserva después de actualizar una existente
     *
     * @param Request $request
     * @param int $id ID del apartamento
     * @return \Illuminate\Http\JsonResponse
     */
    public function bookingAny(Request $request, $id)
    {
        if ($error = $this->verifyWebhookSecret($request, 'booking-any')) return $error;
        // Guardar la request entrante como archivo para depuración
        $fileName = 'booking_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.json';
        Storage::disk('local')->put('logs/bookings/' . $fileName, json_encode($request->all(), JSON_PRETTY_PRINT));

        $evento = $request->input('event');

        // === GESTIÓN DE MENSAJES ===
        if ($evento === 'message') {
            $payload = $request->input('payload');
            $messageId = $payload['ota_message_id'];

            if (!MensajeChat::where('channex_message_id', $messageId)->exists() && $payload['sender'] != 'property') {
                // VALIDACIÓN: Verificar si las contestaciones están desactivadas para esta reserva
                // Si conversacion_plataforma = true, significa que las contestaciones están desactivadas
                $reserva = Reserva::where('id_channex', $payload['booking_id'])->first();

                if ($reserva && $reserva->conversacion_plataforma === true) {
                    Log::info("🚫 Contestaciones desactivadas para esta reserva - No se responderá al mensaje", [
                        'booking_id' => $payload['booking_id'],
                        'reserva_id' => $reserva->id,
                        'codigo_reserva' => $reserva->codigo_reserva,
                        'sender' => $payload['sender'],
                        'conversacion_plataforma' => $reserva->conversacion_plataforma
                    ]);

                    // Asegurar que message nunca sea NULL
                    $messageText = $payload['message'] ?? '';
                    if (empty($messageText) && ($payload['have_attachment'] ?? false)) {
                        $messageText = '[Adjunto]';
                    }

                    // Guardar el mensaje pero sin responder
                    MensajeChat::create([
                        'channex_message_id' => $messageId,
                        'booking_id' => $payload['booking_id'],
                        'thread_id' => $payload['message_thread_id'],
                        'property_id' => $payload['property_id'],
                        'sender' => $payload['sender'],
                        'message' => $messageText,
                        'attachments' => $payload['attachments'] ?? [],
                        'have_attachment' => $payload['have_attachment'] ?? false,
                        'received_at' => Carbon::parse($request->input('timestamp')),
                        'openai_thread_id' => null,
                    ]);

                    return response()->json([
                        'status' => true,
                        'message' => 'Contestaciones desactivadas para esta reserva - No se responde',
                        'ignored' => true
                    ]);
                }

                // VALIDACIÓN: Verificar si es un mensaje repetido de un contestador automático
                // Buscar mensajes idénticos del mismo booking_id en los últimos 10 minutos
                // Asegurar que message nunca sea NULL para la verificación
                $messageTextForVerification = $payload['message'] ?? '';
                if (empty($messageTextForVerification) && ($payload['have_attachment'] ?? false)) {
                    $messageTextForVerification = '[Adjunto]';
                }
                $mensajeRepetido = $this->verificarMensajeRepetidoChannex(
                    $payload['booking_id'],
                    $messageTextForVerification,
                    $payload['sender']
                );

                if ($mensajeRepetido) {
                    // Asegurar que message nunca sea NULL
                    $messageText = $payload['message'] ?? '';
                    if (empty($messageText) && ($payload['have_attachment'] ?? false)) {
                        $messageText = '[Adjunto]';
                    }

                    Log::info("🔄 Mensaje repetido detectado en Channex - No se responderá para evitar bucle con contestador automático", [
                        'booking_id' => $payload['booking_id'],
                        'sender' => $payload['sender'],
                        'mensaje' => substr($messageText, 0, 100),
                        'mensaje_anterior_id' => $mensajeRepetido->id,
                        'fecha_mensaje_anterior' => $mensajeRepetido->received_at
                    ]);

                    // Guardar el mensaje pero sin responder (marcado como repetido en logs)
                    $mensajeChat = MensajeChat::create([
                        'channex_message_id' => $messageId,
                        'booking_id' => $payload['booking_id'],
                        'thread_id' => $payload['message_thread_id'],
                        'property_id' => $payload['property_id'],
                        'sender' => $payload['sender'],
                        'message' => $messageText,
                        'attachments' => $payload['attachments'] ?? [],
                        'have_attachment' => $payload['have_attachment'] ?? false,
                        'received_at' => Carbon::parse($request->input('timestamp')),
                        'openai_thread_id' => null,
                    ]);

                    return response()->json([
                        'status' => true,
                        'message' => 'Mensaje repetido detectado - No se responde para evitar bucle',
                        'ignored' => true
                    ]);
                }

                // Asegurar que message nunca sea NULL
                $messageText = $payload['message'] ?? '';
                if (empty($messageText) && ($payload['have_attachment'] ?? false)) {
                    $messageText = '[Adjunto]';
                }

                // Guardamos el mensaje en la base de datos
                $mensajeChat = MensajeChat::create([
                    'channex_message_id' => $messageId,
                    'booking_id' => $payload['booking_id'],
                    'thread_id' => $payload['message_thread_id'], // Channex thread_id
                    'property_id' => $payload['property_id'],
                    'sender' => $payload['sender'],
                    'message' => $messageText,
                    'attachments' => $payload['attachments'] ?? [],
                    'have_attachment' => $payload['have_attachment'] ?? false,
                    'received_at' => Carbon::parse($request->input('timestamp')),
                    'openai_thread_id' => null, // Inicialmente vacío, se llenará después
                ]);

                // // Verificar si ya existe un hilo de OpenAI asociado o crearlo
                // $openaiThreadId = $this->getOrCreateOpenAIThread($payload['message_thread_id']);  // Obtén o crea el hilo de OpenAI

                // // Actualizar el mensaje con el ID del hilo de OpenAI
                // $mensajeChat->update(['openai_thread_id' => $openaiThreadId]);

                // // Procesar el mensaje con OpenAI y obtener la respuesta
                // $responseMessage = $this->procesarMensajeConAsistente($payload['message'], $openaiThreadId);
                //function enviarMensajeOpenAiChatCompletions($id, $nuevoMensaje, $remitente)

                // Usar booking_id como remitente (no 'guest') para que cada conversación tenga su propio historial
                $remitente = 'channex_' . ($payload['booking_id'] ?? $payload['sender']);
                $enviaChatGPT = $this->enviarMensajeOpenAiChatCompletions($mensajeChat->id, $messageText, $remitente);
                // Enviar la respuesta a Channex
                $this->enviarRespuestaAChannex($enviaChatGPT, $payload['booking_id']);

                return response()->json(['status' => true, 'message' => 'Mensaje registrado', 'content' => $enviaChatGPT]);
            }
            return response()->json(['status' => true, 'message' => 'El Mensaje ya estaba registrado']);
        }


        // Buscar el apartamento
        $apartamento = Apartamento::find($id);
        if (!$apartamento) {
            return response()->json(['status' => false, 'message' => 'Apartamento no encontrado'], 404);
        }

        $revisionId = $request->input('payload.revision_id');
        $bookingId = $request->input('payload.booking_id');


        if (!$revisionId || !$bookingId) {
            return response()->json(['status' => true, 'message' => 'No revision_id or booking_id found']);
        }

        try {

        // Obtener la reserva desde Channex
        $bookingResponse = Http::withHeaders([
            'user-api-key' => $this->apiToken,
        ])->get("https://app.channex.io/api/v1/bookings/{$bookingId}");

        if (!$bookingResponse->successful()) {
            Log::error('Error al obtener reserva de Channex', [
                'booking_id' => $bookingId,
                'status' => $bookingResponse->status(),
                'body' => $bookingResponse->body(),
            ]);

            // Notificación interna Channex
            try {
                app(\App\Services\NotificationService::class)->notifyChannexError(
                    'Error al obtener reserva de Channex API',
                    ['booking_id' => $bookingId, 'http_status' => $bookingResponse->status()]
                );
            } catch (\Exception $notifEx) {
                Log::error('No se pudo crear notificación de error Channex', ['error' => $notifEx->getMessage()]);
            }

            // Alerta WhatsApp
            try {
                $whatsapp = app(\App\Services\WhatsappNotificationService::class);
                $whatsapp->sendToConfiguredRecipients(
                    "⚠️ ALERTA CHANNEX\n\nError HTTP {$bookingResponse->status()} al obtener reserva de Channex\nBooking ID: {$bookingId}"
                );
            } catch (\Exception $alertEx) {
                Log::error('No se pudo enviar alerta WhatsApp de Channex', ['error' => $alertEx->getMessage()]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Error al obtener la información de la reserva',
                'error' => $bookingResponse->body()
            ], 500);
        }

        $bookingData = $bookingResponse->json()['data']['attributes'] ?? [];
        $estadoReserva = $bookingData['status'] ?? null; // Ej: "new", "cancelled"

        // Si la reserva ha sido cancelada
        if ($estadoReserva === 'cancelled') {
            $codigoReserva = $bookingData['ota_reservation_code'] ?? $bookingData['booking_id'];

            $reserva = Reserva::where('codigo_reserva', $codigoReserva)
                ->orWhere('id_channex', $bookingId)
                ->first();

            if (!$reserva) {
                Log::warning('[Channex] Cancellation received but reservation not found', [
                    'booking_id' => $bookingId,
                    'codigo_reserva' => $codigoReserva,
                    'estado_channex' => $estadoReserva,
                ]);
                \App\Services\AlertaEquipoService::alertar(
                    'CANCELACIÓN NO PROCESADA',
                    "Booking ID: {$bookingId}\nCodigo: {$codigoReserva}\nNo se encontró la reserva en el sistema.",
                    'channex_cancel_fail'
                );
                return response()->json(['error' => 'Reservation not found'], 404);
            }

            if ($reserva) {
                $reserva->estado_id = 4; // ID 4 es "Cancelado"
                $reserva->save();

                // === NUEVO BLOQUE: RESTABLECER DISPONIBILIDAD COMPLETA ===
                $roomType = RoomType::find($reserva->room_type_id);
                if ($roomType && $roomType->id_channex) {
                    $start = Carbon::parse($reserva->fecha_entrada);
                    $end = Carbon::parse($reserva->fecha_salida)->subDay(); // No incluir el checkout

                    $values = [];
                    for ($date = $start; $date->lte($end); $date->addDay()) {
                        $values[] = [
                            'property_id'   => $apartamento->id_channex,
                            'room_type_id'  => $roomType->id_channex,
                            'date'          => $date->toDateString(),
                            'availability'  => 1,
                        ];
                    }

                    Http::withHeaders([
                        'user-api-key' => $this->apiToken,
                    ])->post("{$this->apiUrl}/availability", [
                        'values' => $values
                    ]);
                }
                // Llamar a la función fullSync
                return response()->json(['status' => true, 'message' => 'Reserva cancelada actualizada en el sistema']);
            }
        }

        // Verificar si es una reserva nueva o una modificación
        $codigoReserva = $bookingData['ota_reservation_code'] ?? $bookingData['booking_id'];
        $reservaExistente = Reserva::where('codigo_reserva', $codigoReserva)
            ->orWhere('id_channex', $bookingId)
            ->first();

        Log::info('Procesando webhook de reserva', [
            'evento' => $evento,
            'booking_id' => $bookingId,
            'revision_id' => $revisionId,
            'codigo_reserva' => $codigoReserva,
            'es_modificacion' => $reservaExistente ? true : false,
            'estado_channex' => $estadoReserva
        ]);

        // Acceso null-safe a datos de cliente de Channex (pueden venir vacíos o incompletos)
        $customer = $bookingData['customer'] ?? [];
        $telefono = !empty($customer['phone'] ?? null) ? preg_replace('/\D/', '', $customer['phone']) : '';
        $nombre = $customer['name'] ?? '';
        $apellido = $customer['surname'] ?? '';
        $email = !empty($customer['mail'] ?? null) ? $customer['mail'] : null;
        $direccion = $customer['address'] ?? '';
        $nacionalidad = $customer['country'] ?? '';

        // Usar transacción para que cliente + reserva se creen/actualicen de forma atómica
        $cliente = DB::transaction(function () use ($customer, $telefono, $nombre, $apellido, $email, $direccion, $nacionalidad) {
            if (!empty($email)) {
                return Cliente::firstOrCreate(
                    ['email' => $email],
                    [
                        'alias' => trim($nombre . ' ' . $apellido),
                        'nombre' => $nombre,
                        'apellido1' => $apellido,
                        'telefono' => $telefono,
                        'direccion' => $direccion,
                        'nacionalidad' => $nacionalidad,
                    ]
                );
            } else {
                $cliente = !empty($telefono) ? Cliente::where('telefono', $telefono)->first() : null;

                if (!$cliente) {
                    $cliente = Cliente::create([
                        'alias' => trim($nombre . ' ' . $apellido),
                        'nombre' => $nombre,
                        'apellido1' => $apellido,
                        'telefono' => $telefono,
                        'direccion' => $direccion,
                        'nacionalidad' => $nacionalidad,
                        'email' => null,
                    ]);
                }

                return $cliente;
            }
        });

        // === LÓGICA DE MODIFICACIÓN vs NUEVA RESERVA ===
        // Si es una modificación, actualizar la reserva existente
        if ($reservaExistente) {
            Log::info('Modificando reserva existente', [
                'codigo_reserva' => $codigoReserva,
                'id_channex' => $bookingId,
                'revision_id' => $revisionId,
                'reserva_id' => $reservaExistente->id,
                'datos_actuales' => [
                    'fecha_entrada' => $reservaExistente->fecha_entrada,
                    'fecha_salida' => $reservaExistente->fecha_salida,
                    'precio' => $reservaExistente->precio,
                    'numero_personas' => $reservaExistente->numero_personas,
                    'neto' => $reservaExistente->neto
                ]
            ]);

            // Usar transacción para que todas las actualizaciones de la reserva sean atómicas
            $cambios = DB::transaction(function () use ($reservaExistente, $cliente, $bookingData) {
                // Actualizar datos del cliente si han cambiado
                $reservaExistente->cliente_id = $cliente->id;

                // Actualizar datos generales de la reserva
                $reservaExistente->update([
                    'cliente_id' => $cliente->id,
                    'origen' => $bookingData['ota_name'],
                    'neto' => floatval(str_replace(',', '.', $bookingData['amount'])),
                    'comision' => floatval(str_replace(',', '.', $bookingData['ota_commission'])),
                    'updated_at' => now(),
                ]);

                // Actualizar las habitaciones con los nuevos datos (fechas y precios)
                $cambios = [];
                foreach ($bookingData['rooms'] as $room) {
                    $ratePlanId = $room['rate_plan_id'] ?? null;
                    if (!$ratePlanId) {
                        Log::error('Rate Plan ID no encontrado en la reserva', ['room' => $room]);
                        continue;
                    }

                    $ratePlan = RatePlan::where('id_channex', $ratePlanId)->first();
                    if (!$ratePlan) {
                        Log::error('RatePlan no encontrado en la base de datos', ['rate_plan_id' => $ratePlanId]);
                        continue;
                    }

                    $roomTypeId = $ratePlan->room_type_id;

                    // Detectar cambios importantes (antes de actualizar)
                    if ($reservaExistente->fecha_entrada != $room['checkin_date']) {
                        $cambios['fecha_entrada'] = [
                            'anterior' => $reservaExistente->fecha_entrada,
                            'nuevo' => $room['checkin_date']
                        ];
                    }
                    if ($reservaExistente->fecha_salida != Carbon::parse($room['checkout_date'])->toDateString()) {
                        $cambios['fecha_salida'] = [
                            'anterior' => $reservaExistente->fecha_salida,
                            'nuevo' => Carbon::parse($room['checkout_date'])->toDateString()
                        ];
                    }
                    if ($reservaExistente->precio != floatval(str_replace(',', '.', $room['amount']))) {
                        $cambios['precio'] = [
                            'anterior' => $reservaExistente->precio,
                            'nuevo' => floatval(str_replace(',', '.', $room['amount']))
                        ];
                    }
                    if ($reservaExistente->numero_personas != $room['occupancy']['adults']) {
                        $cambios['numero_personas'] = [
                            'anterior' => $reservaExistente->numero_personas,
                            'nuevo' => $room['occupancy']['adults']
                        ];
                    }

                    // Detectar cambios en información de niños
                    $numeroNinosNuevo = ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0);
                    $edadesNinosNuevas = $room['occupancy']['ages'] ?? [];

                    if ($reservaExistente->numero_ninos != $numeroNinosNuevo) {
                        $cambios['numero_ninos'] = [
                            'anterior' => $reservaExistente->numero_ninos,
                            'nuevo' => $numeroNinosNuevo
                        ];
                    }

                    if ($reservaExistente->edades_ninos != $edadesNinosNuevas) {
                        $cambios['edades_ninos'] = [
                            'anterior' => $reservaExistente->edades_ninos,
                            'nuevo' => $edadesNinosNuevas
                        ];
                    }

                    // Actualizar la reserva existente con los nuevos datos
                    $reservaExistente->update([
                        'fecha_entrada' => $room['checkin_date'],
                        'fecha_salida' => Carbon::parse($room['checkout_date'])->toDateString(),
                        'precio' => floatval(str_replace(',', '.', $room['amount'])),
                        'numero_personas' => $room['occupancy']['adults'],
                        'numero_ninos' => ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0),
                        'edades_ninos' => $room['occupancy']['ages'] ?? [],
                        'notas_ninos' => $this->generarNotasNinos($room['occupancy']),
                        'room_type_id' => $roomTypeId,
                    ]);

                    Log::info('Reserva actualizada con nuevos datos', [
                        'reserva_id' => $reservaExistente->id,
                        'fecha_entrada' => $room['checkin_date'],
                        'fecha_salida' => Carbon::parse($room['checkout_date'])->toDateString(),
                        'precio' => floatval(str_replace(',', '.', $room['amount'])),
                        'numero_personas' => $room['occupancy']['adults'],
                        'numero_ninos' => ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0),
                        'edades_ninos' => $room['occupancy']['ages'] ?? [],
                        'cambios_detectados' => $cambios
                    ]);
                }

                return $cambios;
            });

            Log::info('Reserva existente actualizada', [
                'reserva_id' => $reservaExistente->id,
                'codigo_reserva' => $codigoReserva
            ]);

            // IMPORTANTE: No crear nueva reserva, solo actualizar la existente
            Log::info('Modificación completada - NO se creará nueva reserva');
        } else {
            Log::info('Creando nueva reserva', [
                'codigo_reserva' => $codigoReserva,
                'booking_id' => $bookingId
            ]);
        }

        // Solo crear nueva reserva si NO es una modificación
        if (!$reservaExistente) {
            DB::transaction(function () use ($bookingData, $bookingId, $codigoReserva, $cliente, $apartamento) {
                // Double-check inside transaction to prevent duplicate inserts
                if (Reserva::where('id_channex', $bookingId)->exists()) {
                    Log::info('Double-check: reserva ya existe, abortando creación', ['booking_id' => $bookingId]);
                    return;
                }

                // Log warning if overlap detected (reservation still created - it's confirmed in OTA)
                foreach ($bookingData['rooms'] as $checkRoom) {
                    $overlap = \App\Services\ReservationValidationService::findOverlap(
                        $apartamento->id,
                        $checkRoom['checkin_date'],
                        \Carbon\Carbon::parse($checkRoom['checkout_date'])->toDateString(),
                        null
                    );

                    if ($overlap) {
                        Log::warning("ReservationValidation: solapamiento detectado [Channex webhook]", [
                            'apartamento_id' => $apartamento->id,
                            'fecha_entrada' => $checkRoom['checkin_date'],
                            'fecha_salida' => \Carbon\Carbon::parse($checkRoom['checkout_date'])->toDateString(),
                            'conflicto_reserva_id' => $overlap->id,
                            'conflicto_codigo' => $overlap->codigo_reserva,
                        ]);

                        \App\Services\AlertaEquipoService::alertar(
                            'DOBLE RESERVA DETECTADA',
                            "Apartamento: {$apartamento->titulo}\n"
                            . "Reserva nueva: {$codigoReserva} ({$checkRoom['checkin_date']} - " . \Carbon\Carbon::parse($checkRoom['checkout_date'])->toDateString() . ")\n"
                            . "Conflicto con: #{$overlap->id} ({$overlap->fecha_entrada} - {$overlap->fecha_salida})\n"
                            . "Resolver manualmente.",
                            'doble_reserva'
                        );
                    }
                }

                foreach ($bookingData['rooms'] as $room) {
                    $ratePlanId = $room['rate_plan_id'] ?? null;
                    if (!$ratePlanId) {
                        Log::error('Rate Plan ID no encontrado en la reserva', ['room' => $room]);
                        continue;
                    }

                    $ratePlan = RatePlan::where('id_channex', $ratePlanId)->first();
                    if (!$ratePlan) {
                        Log::error('RatePlan no encontrado en la base de datos', ['rate_plan_id' => $ratePlanId]);
                        continue;
                    }

                    $roomTypeId = $ratePlan->room_type_id;

                    $nuevaReserva = Reserva::create([
                        'cliente_id' => $cliente->id,
                        'apartamento_id' => $apartamento->id,
                        'room_type_id' => $roomTypeId,
                        'origen' => $bookingData['ota_name'],
                        'fecha_entrada' => $room['checkin_date'],
                        'fecha_salida' => Carbon::parse($room['checkout_date'])->toDateString(),
                        'codigo_reserva' => $codigoReserva,
                        'precio' => floatval(str_replace(',', '.', $room['amount'])),
                        'numero_personas' => $room['occupancy']['adults'],
                        'numero_ninos' => ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0),
                        'edades_ninos' => $room['occupancy']['ages'] ?? [],
                        'notas_ninos' => $this->generarNotasNinos($room['occupancy']),
                        'neto' => floatval(str_replace(',', '.', $bookingData['amount'])),
                        'comision' => floatval(str_replace(',', '.', $bookingData['ota_commission'])),
                        'estado_id' => 1, // Nueva reserva
                        'id_channex' => $bookingId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Si la reserva es de hoy y son más de las 14:00, intentar enviar claves por Channex
                    // (solo si ya tiene mensaje de bienvenida, que se enviará después por el cron)
                    \App\Console\Kernel::enviarClavesPorChannexSiEsNecesario($nuevaReserva);

                    Log::info('Nueva reserva creada', [
                        'codigo_reserva' => $codigoReserva,
                        'booking_id' => $bookingId,
                        'fecha_entrada' => $room['checkin_date'],
                        'fecha_salida' => Carbon::parse($room['checkout_date'])->toDateString(),
                        'precio' => floatval(str_replace(',', '.', $room['amount'])),
                        'numero_personas' => $room['occupancy']['adults'],
                        'numero_ninos' => ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0),
                        'edades_ninos' => $room['occupancy']['ages'] ?? []
                    ]);
                }
            });

            // Generar y programar código de acceso para TTLock
            try {
                $nuevaReserva = Reserva::where('id_channex', $bookingId)
                    ->where('apartamento_id', $apartamento->id)
                    ->latest()
                    ->first();
                if ($nuevaReserva) {
                    app(\App\Services\AccessCodeService::class)->generarYProgramar($nuevaReserva);
                }
            } catch (\Exception $e) {
                Log::error('AccessCodeService error en webhook: ' . $e->getMessage());
            }
        }

        // Marcar la revisión como revisada en Channex
        $ackResponse = Http::withHeaders([
            'user-api-key' => $this->apiToken,
        ])->post("https://app.channex.io/api/v1/booking_revisions/{$revisionId}/ack", ['values' => []]);

        if (!$ackResponse->successful()) {
            return response()->json([
                'status' => false,
                'message' => 'Error al marcar la reserva como revisada',
                'error' => $ackResponse->body()
            ], $ackResponse->status());
        }

        $mensaje = $reservaExistente
            ? 'Reserva modificada y marcada como revisada'
            : 'Nueva reserva guardada y marcada como revisada';

        $response = [
            'status' => true,
            'message' => $mensaje,
            'tipo' => $reservaExistente ? 'modificacion' : 'nueva',
            'codigo_reserva' => $codigoReserva,
            'revision_id' => $revisionId
        ];

        // Si es una modificación, incluir información sobre los cambios
        if ($reservaExistente && isset($cambios) && !empty($cambios)) {
            $response['cambios'] = $cambios;
            $response['message'] .= ' - Campos actualizados: ' . implode(', ', array_keys($cambios));
        }

        return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook de reserva', [
                'booking_id' => $bookingId ?? null,
                'revision_id' => $revisionId ?? null,
                'apartamento_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Notificación interna Channex
            try {
                app(\App\Services\NotificationService::class)->notifyChannexError(
                    'Error procesando webhook Channex',
                    ['error' => $e->getMessage(), 'booking_id' => $bookingId ?? null]
                );
            } catch (\Exception $notifEx) {
                Log::error('No se pudo crear notificación de error Channex', ['error' => $notifEx->getMessage()]);
            }

            // Alerta WhatsApp para fallo crítico de reserva Channex
            try {
                $whatsapp = app(\App\Services\WhatsappNotificationService::class);
                $whatsapp->sendToConfiguredRecipients(
                    "🔴 ALERTA CHANNEX\n\nError procesando webhook de reserva\nBooking ID: " . ($bookingId ?? 'N/A') . "\nApartamento ID: {$id}\nError: {$e->getMessage()}"
                );
            } catch (\Exception $alertEx) {
                Log::error('No se pudo enviar alerta WhatsApp de Channex', ['error' => $alertEx->getMessage()]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Error interno al procesar el webhook de reserva',
            ], 500);
        }
    }

    function enviarMensajeOpenAiChatCompletions($id, $nuevoMensaje, $remitente)
    {
        // Usar Hawkins AI (aiapi.hawkins.es) en lugar de OpenAI directo
        $config = config('services.hawkins_whatsapp_ai');
        $endpoint = $config['base_url'];
        if (!str_ends_with($endpoint, '/chat/chat')) {
            $endpoint = rtrim($endpoint, '/') . (str_ends_with($endpoint, '/chat') ? '/chat' : '/chat/chat');
        }
        $apiKey = $config['api_key'];
        $modelo = $config['model'];

        $promptAsistente = PromptAsistente::first();

        // Guardar el mensaje del usuario
        ChatGpt::create([
            'id_mensaje' => $id,
            'remitente' => $remitente,
            'mensaje' => $nuevoMensaje,
            'respuesta' => null,
            'status' => 0,
            'date' => now()
        ]);

        // Historial: últimos 20 mensajes con respuesta
        $historialArray = ChatGpt::where('remitente', $remitente)
            ->where('status', 1)->whereNotNull('respuesta')->where('respuesta', '!=', '')
            ->orderBy('date', 'desc')->limit(20)->get()->reverse()
            ->flatMap(fn($c) => array_filter([
                !empty($c->mensaje) ? "Usuario: " . trim($c->mensaje) : null,
                !empty($c->respuesta) ? "Asistente: " . trim($c->respuesta) : null,
            ]))->toArray();

        $historialTexto = implode("\n", $historialArray);

        // Construir prompt con MISMAS reglas que WhatsApp (herramientas, brevedad, seguridad)
        $promptBase = $promptAsistente ? $promptAsistente->prompt : "Eres María, el asistente virtual de Apartamentos Hawkins.";
        $promptCompleto = $promptBase;

        $promptCompleto .= "\n\nCONTEXTO: Esta conversación es con un huésped que contacta vía Booking/Airbnb. "
            . "Responde en el IDIOMA del huésped (detecta su idioma por el mensaje). Sé breve y directo. "
            . "Responde SOLO a lo que se te pregunta. NO des información que no se ha pedido.\n\n"
            . "FORMATO OBLIGATORIO PARA USAR HERRAMIENTAS:\n"
            . "- Cuando necesites ejecutar una acción, usa: [FUNCION:nombre_funcion:parametro1=valor1:parametro2=valor2]\n"
            . "- Funciones disponibles:\n"
            . "  * [FUNCION:obtener_claves:codigo_reserva=XXXXXXX] - Para dar claves de acceso al huésped\n"
            . "  * [FUNCION:notificar_tecnico:descripcion_problema=XXX:urgencia=alta] - Para reportar averías\n"
            . "  * [FUNCION:notificar_limpieza:tipo_limpieza=XXX:observaciones=XXX] - Para solicitudes de limpieza\n"
            . "- NO escribas explicaciones antes o después del formato [FUNCION:...]\n"
            . "- Si NO necesitas usar herramientas, responde normalmente.\n\n"
            . "HORARIO DE ENTREGA DE CLAVES:\n"
            . "- Las claves se entregan a las 15:00h del día de entrada.\n"
            . "- Si la reserva es para HOY pero NO son las 15:00h, informa que estarán disponibles a esa hora.\n"
            . "- NUNCA des códigos de acceso genéricos. USA la función obtener_claves para verificar.\n\n"
            . "SEGURIDAD:\n"
            . "- NUNCA menciones códigos de emergencia.\n"
            . "- NUNCA inventes códigos de acceso. Siempre usa la función obtener_claves.\n"
            . "- NUNCA ofrezcas descuentos, compensaciones o cosas gratis.\n\n"
            . "BREVEDAD:\n"
            . "- Responde SOLO a lo preguntado.\n"
            . "- NO repitas información de bienvenida, WiFi, horarios etc. a menos que se pregunte.\n"
            . "- Máximo 2-3 frases por respuesta salvo que sea necesario más.";

        if (!empty($historialArray)) {
            $promptCompleto .= "\n\nHISTORIAL:\n" . $historialTexto;
        }

        $promptCompleto .= "\n\nUsuario: " . $nuevoMensaje . "\n\nAsistente:";

        // Llamar a Hawkins AI (SSL verify desactivado porque el certificado
        // de aiapi.hawkins.es puede estar caducado/FNMT y el cert bundle del
        // contenedor no lo reconoce)
        $response = Http::withHeaders([
            'x-api-key' => $apiKey, 'Content-Type' => 'application/json',
        ])->timeout(60)->withoutVerifying()->post($endpoint, ['prompt' => $promptCompleto, 'modelo' => $modelo]);

        if ($response->failed()) {
            Log::error('[Booking IA] Error al enviar a Hawkins AI: ' . $response->body());
            return null;
        }

        $respuestaTexto = $response->json('respuesta') ?? null;
        if (!$respuestaTexto) {
            Log::warning('[Booking IA] Respuesta vacía', ['data' => $response->json()]);
            return null;
        }

        Log::info('[Booking IA] Respuesta recibida', ['preview' => substr($respuestaTexto, 0, 100)]);

        // DETECTAR Y EJECUTAR FUNCIONES [FUNCION:...]
        if (preg_match('/\[FUNCION:([^:\]]+):?(.*?)\]/', $respuestaTexto, $matches)) {
            $nombreFuncion = trim($matches[1]);
            $parametrosStr = $matches[2] ?? '';
            $parametros = [];
            foreach (explode(':', $parametrosStr) as $param) {
                if (strpos($param, '=') !== false) {
                    [$key, $value] = explode('=', $param, 2);
                    $parametros[trim($key)] = trim($value);
                }
            }

            Log::info('[Booking IA] Función detectada', ['funcion' => $nombreFuncion, 'params' => $parametros]);

            if ($nombreFuncion === 'obtener_claves') {
                $codigo = $parametros['codigo_reserva'] ?? $this->bookingDetectarCodigo($nuevoMensaje, $historialTexto);
                $respuestaTexto = $this->bookingEjecutarObtenerClaves($codigo, $promptBase, $historialTexto, $nuevoMensaje, $endpoint, $apiKey, $modelo);
            } elseif ($nombreFuncion === 'notificar_tecnico') {
                $desc = $parametros['descripcion_problema'] ?? $nuevoMensaje;
                $urgencia = $parametros['urgencia'] ?? 'media';
                $this->bookingGestionarAveria($remitente, $desc);
                $respuestaTexto = $this->bookingLlamarConContexto($promptBase, $historialTexto, $nuevoMensaje,
                    "He notificado al técnico sobre el problema. Te contactarán pronto.", $endpoint, $apiKey, $modelo);
            } elseif ($nombreFuncion === 'notificar_limpieza') {
                $tipo = $parametros['tipo_limpieza'] ?? 'Limpieza general';
                $obs = $parametros['observaciones'] ?? '';
                $this->bookingGestionarLimpieza($remitente, $tipo . ($obs ? " - $obs" : ''));
                $respuestaTexto = $this->bookingLlamarConContexto($promptBase, $historialTexto, $nuevoMensaje,
                    "He notificado al equipo de limpieza. Te avisaremos cuando esté confirmado.", $endpoint, $apiKey, $modelo);
            }
        }

        // Limpiar marcadores de función restantes
        $respuestaTexto = preg_replace('/\[FUNCION:[^\]]+\]/', '', $respuestaTexto);
        $respuestaTexto = trim($respuestaTexto);

        // Guardar la respuesta
        ChatGpt::where('remitente', $remitente)
            ->whereNull('respuesta')->orderByDesc('created_at')->limit(1)
            ->update(['respuesta' => $respuestaTexto, 'status' => 1]);

        return $respuestaTexto;
    }

    // === HERRAMIENTAS DE BOOKING IA ===

    private function bookingDetectarCodigo($mensaje, $historial)
    {
        if (preg_match('/\b([A-Z0-9]{8,15})\b/i', $mensaje, $m)) {
            if (preg_match('/[0-9]/', $m[1])) return strtoupper($m[1]);
        }
        if (preg_match('/\b([0-9]{8,15})\b/', $mensaje, $m)) return $m[1];
        if (!empty($historial)) {
            if (preg_match('/\b([A-Z0-9]{8,15})\b/i', $historial, $m) && preg_match('/[0-9]/', $m[1])) {
                return strtoupper($m[1]);
            }
        }
        return null;
    }

    private function bookingEjecutarObtenerClaves($codigoReserva, $promptBase, $historial, $mensaje, $endpoint, $apiKey, $modelo)
    {
        if (!$codigoReserva) {
            return $this->bookingLlamarConContexto($promptBase, $historial, $mensaje,
                "No se pudo identificar el código de reserva. Pide al huésped su código de reserva.", $endpoint, $apiKey, $modelo);
        }

        $reserva = \App\Models\Reserva::where('codigo_reserva', $codigoReserva)->first();
        if (!$reserva) {
            return $this->bookingLlamarConContexto($promptBase, $historial, $mensaje,
                "No se encontró reserva con código {$codigoReserva}. Pide que verifique el código.", $endpoint, $apiKey, $modelo);
        }

        $fechaEntrada = \Carbon\Carbon::parse($reserva->fecha_entrada);
        $horaActual = now()->format('H:i');

        if (empty($reserva->dni_entregado)) {
            $url = 'https://crm.apartamentosalgeciras.com/dni-user/' . $reserva->token;
            return $this->bookingLlamarConContexto($promptBase, $historial, $mensaje,
                "Para dar las claves, el huésped necesita completar su DNI primero. Link: {$url}", $endpoint, $apiKey, $modelo);
        }

        if ($fechaEntrada->isToday()) {
            if ($horaActual < '15:00') {
                return $this->bookingLlamarConContexto($promptBase, $historial, $mensaje,
                    "Las claves estarán disponibles a partir de las 15:00h de hoy.", $endpoint, $apiKey, $modelo);
            }
            $claveApto = $reserva->apartamento->claves ?? 'No asignada';
            $claveEdificio = optional($reserva->apartamento->edificioName)->clave ?? 'No asignada';
            return $this->bookingLlamarConContexto($promptBase, $historial, $mensaje,
                "Código puerta edificio: *{$claveEdificio}*\nCódigo apartamento: *{$claveApto}*", $endpoint, $apiKey, $modelo);
        }

        return $this->bookingLlamarConContexto($promptBase, $historial, $mensaje,
            "Las claves se entregan el día de entrada ({$fechaEntrada->format('d/m/Y')}). Aún no es ese día.", $endpoint, $apiKey, $modelo);
    }

    private function bookingLlamarConContexto($promptBase, $historial, $mensaje, $resultadoFuncion, $endpoint, $apiKey, $modelo)
    {
        $promptCompleto = $promptBase;
        $promptCompleto .= "\n\nCONTEXTO: Conversación vía Booking/Airbnb. Responde en el idioma del huésped. Sé breve.";
        if (!empty($historial)) {
            $promptCompleto .= "\n\nHISTORIAL:\n" . $historial;
        }
        $promptCompleto .= "\n\nUsuario: " . $mensaje;
        $promptCompleto .= "\nAsistente: [He ejecutado una función: " . $resultadoFuncion . "]\n\nAsistente:";

        $response = Http::withHeaders([
            'x-api-key' => $apiKey, 'Content-Type' => 'application/json',
        ])->timeout(60)->post($endpoint, ['prompt' => $promptCompleto, 'modelo' => $modelo]);

        if ($response->failed()) {
            Log::error('[Booking IA] Error en segunda llamada: ' . $response->body());
            return $resultadoFuncion;
        }

        $respuesta = $response->json('respuesta') ?? $resultadoFuncion;
        $respuesta = preg_replace('/\[FUNCION:[^\]]+\]/', '', $respuesta);
        return trim($respuesta) ?: $resultadoFuncion;
    }

    private function bookingGestionarAveria($remitente, $descripcion)
    {
        Log::info('[Booking IA] Avería reportada', ['remitente' => $remitente, 'descripcion' => $descripcion]);
        \App\Services\AlertaEquipoService::enviarWhatsApp(
            "🔧 AVERÍA REPORTADA (Booking)\n\nDescripción: {$descripcion}\nRemitente: {$remitente}", 'averia'
        );
    }

    private function bookingGestionarLimpieza($remitente, $descripcion)
    {
        Log::info('[Booking IA] Limpieza solicitada', ['remitente' => $remitente, 'descripcion' => $descripcion]);
        \App\Services\AlertaEquipoService::enviarWhatsApp(
            "🧹 LIMPIEZA SOLICITADA (Booking)\n\nTipo: {$descripcion}\nRemitente: {$remitente}", 'limpieza'
        );
    }
    private function enviarRespuestaAChannex($mensaje, $bookingId)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://app.channex.io/api/v1/bookings/{$bookingId}/messages",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'message' => [
                    'message' => $mensaje
                ],
            ]),
            CURLOPT_HTTPHEADER => [
                'user-api-key: ' . $this->apiToken,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        Log::info('Respuesta enviada a Channex:', ['booking_id' => $bookingId, 'response' => $response]);

        return $response;
    }

        /**
     * Envía un mensaje automático a Channex usando el bookingId
     * @param string $mensaje El mensaje a enviar
     * @param string $bookingId El ID de la reserva en Channex
     * @return mixed La respuesta de la API
     */
    public static function enviarMensajeAutomaticoAChannex($mensaje, $bookingId)
    {
        $apiToken = env('CHANNEX_TOKEN');

        if (!$apiToken || !$bookingId) {
            Log::error('Faltan credenciales o bookingId para enviar mensaje a Channex', [
                'apiToken' => $apiToken ? 'presente' : 'ausente',
                'bookingId' => $bookingId,
                'token_length' => $apiToken ? strlen($apiToken) : 0
            ]);
            return false;
        }

        $url = "https://app.channex.io/api/v1/bookings/{$bookingId}/messages";
        
        $payload = [
            'message' => [
                'message' => $mensaje
            ],
        ];

        // Loggear información antes de enviar
        Log::info('Intentando enviar mensaje a Channex', [
            'booking_id' => $bookingId,
            'url' => $url,
            'mensaje_length' => strlen($mensaje),
            'mensaje_preview' => substr($mensaje, 0, 100),
            'token_presente' => !empty($apiToken),
            'token_length' => strlen($apiToken)
        ]);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // Timeout de 30 segundos
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'user-api-key: ' . $apiToken,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        $curlErrno = curl_errno($curl);
        curl_close($curl);

        // Loggear respuesta completa
        Log::info('Respuesta de Channex API', [
            'booking_id' => $bookingId,
            'http_code' => $httpCode,
            'curl_error' => $curlError ?: 'ninguno',
            'curl_errno' => $curlErrno ?: 0,
            'response_length' => strlen($response),
            'response_preview' => substr($response, 0, 500),
            'response_full' => $response
        ]);

        // Verificar errores de cURL
        if ($curlError) {
            Log::error('Error cURL al enviar mensaje a Channex', [
                'booking_id' => $bookingId,
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno,
                'url' => $url
            ]);
            return false;
        }

        // Verificar código HTTP
        if ($httpCode === 200 || $httpCode === 201) {
            Log::info('Mensaje automático enviado exitosamente a Channex', [
                'booking_id' => $bookingId,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return true;
        } else {
            // Intentar parsear respuesta de error
            $errorData = null;
            if (!empty($response)) {
                $errorData = json_decode($response, true);
            }

            Log::error('Error al enviar mensaje automático a Channex', [
                'booking_id' => $bookingId,
                'http_code' => $httpCode,
                'response' => $response,
                'response_parsed' => $errorData,
                'url' => $url,
                'payload' => $payload
            ]);
            return false;
        }
    }

    /**
     * Crea un mensaje de texto plano para el chat basado en el tipo de mensaje
     * @param string $tipo Tipo de mensaje: 'dni', 'claves', 'bienvenida', 'consulta', 'ocio', 'despedida'
     * @param array $datos Datos necesarios para el mensaje
     * @param string $idioma Idioma del mensaje
     * @return string Mensaje formateado para el chat
     */
    public static function crearMensajeChat($tipo, $datos, $idioma = 'en')
    {
        switch ($tipo) {
            case 'dni':
                $token = $datos['token'];
                $url = "https://crm.apartamentosalgeciras.com/dni-user/{$token}";

                switch ($idioma) {
                    case 'es':
                        return "¡Gracias por reservar en los apartamentos Hawkins!\n\nLa legislación española nos obliga a solicitar su Documento Nacional de Identidad o pasaporte. Es obligatorio que nos lo facilite o no podrá alojarse en el apartamento.\n\nPuede rellenar sus datos aquí: {$url}\n\nLas claves de acceso se le enviarán el día de su llegada por WhatsApp y correo electrónico.";
                    case 'fr':
                        return "Merci de réserver chez les appartements Hawkins!\n\nLa législation espagnole nous oblige à vous demander votre carte d'identité nationale ou votre passeport. Il est obligatoire que vous nous le fournissiez, sinon vous ne pourrez pas séjourner dans l'appartement.\n\nVous pouvez remplir vos informations ici: {$url}\n\nLes codes d'accès vous seront envoyés le jour de votre arrivée par WhatsApp et e-mail.";
                    case 'de':
                        return "Danke, dass Sie sich für die Hawkins Apartments entschieden haben!\n\nDie spanische Gesetzgebung verpflichtet uns, Ihren Personalausweis oder Ihren Reisepass anzufordern. Es ist obligatorisch, dass Sie uns diesen zur Verfügung stellen, ansonsten können Sie nicht in der Wohnung übernachten.\n\nSie können Ihre Informationen hier ausfüllen: {$url}\n\nDie Zugangscodes werden Ihnen am Tag Ihrer Ankunft per WhatsApp und E-Mail zugesendet.";
                    default: // en
                        return "Thank you for booking at Hawkins Apartments!\n\nThe Spanish legislation requires us to request your National Identity Document or your passport. It is mandatory that you provide it to us or you will not be able to stay in the apartment.\n\nYou can fill out your information here: {$url}\n\nThe access codes will be sent to you on the day of your arrival by WhatsApp and email.";
                }
                break;

            case 'claves':
                $nombre = $datos['nombre'];
                $apartamento = $datos['apartamento'];
                $claveEntrada = $datos['claveEntrada'];
                $clavePiso = $datos['clavePiso'];
                $url = $datos['url'] ?? 'https://goo.gl/maps/qb7AxP1JAxx5yg3N9';

                switch ($idioma) {
                    case 'es':
                        return "¡Hola {$nombre}!\n\nLa ubicación de los apartamentos es: {$url}\n\nTu apartamento es el {$apartamento}. Los códigos para entrar son:\n• Puerta principal: {$claveEntrada}\n• Puerta de tu apartamento: {$clavePiso}\n\n¡Espero que pases una estancia maravillosa!";
                    case 'fr':
                        return "Bonjour {$nombre}!\n\nL'emplacement des appartements est: {$url}\n\nVotre appartement est le {$apartamento}. Les codes pour entrer sont:\n• Porte principale: {$claveEntrada}\n• Porte de votre appartement: {$clavePiso}\n\nJ'espère que vous passerez un séjour merveilleux!";
                    case 'de':
                        return "Hallo {$nombre}!\n\nDie Lage der Apartments ist: {$url}\n\nIhr Apartment ist das {$apartamento}. Die Codes zum Betreten sind:\n• Haupteingangstür: {$claveEntrada}\n• Tür Ihrer Wohnung: {$clavePiso}\n\nIch hoffe, Sie haben einen wunderbaren Aufenthalt!";
                    default: // en
                        return "Hello {$nombre}!\n\nThe location of the apartments is: {$url}\n\nYour apartment is {$apartamento}. The codes to enter are:\n• Main door: {$claveEntrada}\n• Your apartment door: {$clavePiso}\n\nI hope you have a wonderful stay!";
                }
                break;

            case 'bienvenida':
                $nombre = $datos['nombre'];

                switch ($idioma) {
                    case 'es':
                        return "¡Hola {$nombre}! ¡Bienvenido a los apartamentos Hawkins! Esperamos que disfrutes de tu estancia.";
                    case 'fr':
                        return "Bonjour {$nombre}! Bienvenue aux appartements Hawkins! Nous espérons que vous apprécierez votre séjour.";
                    case 'de':
                        return "Hallo {$nombre}! Willkommen in den Hawkins Apartments! Wir hoffen, Sie genießen Ihren Aufenthalt.";
                    default: // en
                        return "Hello {$nombre}! Welcome to Hawkins Apartments! We hope you enjoy your stay.";
                }
                break;

            case 'consulta':
                $nombre = $datos['nombre'];

                switch ($idioma) {
                    case 'es':
                        return "¡Hola {$nombre}! ¿Tienes alguna consulta o necesitas ayuda con algo durante tu estancia? Estamos aquí para ayudarte.";
                    case 'fr':
                        return "Bonjour {$nombre}! Avez-vous des questions ou avez-vous besoin d'aide pour quelque chose pendant votre séjour? Nous sommes là pour vous aider.";
                    case 'de':
                        return "Hallo {$nombre}! Haben Sie Fragen oder brauchen Sie Hilfe bei etwas während Ihres Aufenthalts? Wir sind hier, um Ihnen zu helfen.";
                    default: // en
                        return "Hello {$nombre}! Do you have any questions or need help with anything during your stay? We are here to help you.";
                }
                break;

            case 'ocio':
                $nombre = $datos['nombre'];

                switch ($idioma) {
                    case 'es':
                        return "¡Hola {$nombre}! ¿Te gustaría conocer algunos lugares interesantes para visitar o actividades para hacer en la zona? ¡Estamos encantados de recomendarte!";
                    case 'fr':
                        return "Bonjour {$nombre}! Souhaitez-vous connaître quelques endroits intéressants à visiter ou des activités à faire dans la région? Nous serions ravis de vous recommander!";
                    case 'de':
                        return "Hallo {$nombre}! Möchten Sie einige interessante Orte zum Besuchen oder Aktivitäten in der Gegend kennenlernen? Wir würden uns freuen, Ihnen zu empfehlen!";
                    default: // en
                        return "Hello {$nombre}! Would you like to know some interesting places to visit or activities to do in the area? We'd be happy to recommend!";
                }
                break;

            case 'despedida':
                $nombre = $datos['nombre'];

                switch ($idioma) {
                    case 'es':
                        return "¡Hola {$nombre}! Esperamos que hayas disfrutado de tu estancia en los apartamentos Hawkins. ¡Que tengas un buen viaje de regreso!";
                    case 'fr':
                        return "Bonjour {$nombre}! Nous espérons que vous avez apprécié votre séjour aux appartements Hawkins. Bon voyage de retour!";
                    case 'de':
                        return "Hallo {$nombre}! Wir hoffen, Sie haben Ihren Aufenthalt in den Hawkins Apartments genossen. Gute Heimreise!";
                    default: // en
                        return "Hello {$nombre}! We hope you enjoyed your stay at Hawkins Apartments. Have a good trip back!";
                }
                break;

            default:
                return "Mensaje no reconocido";
        }
    }

    public function bookingUnmappedRoom(Request $request, $id)
    {
        $apartamento = Apartamento::find($id);

        $fecha = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "bookingUnmappedRoom_{$fecha}.txt";

        $this->saveToWebhooksFolder($filename, $request->all());

        return response()->json(['status' => true]);
    }

    public function bookingUnmappedRate(Request $request, $id)
    {
        $apartamento = Apartamento::find($id);

        $fecha = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "bookingUnmappedRate_{$fecha}.txt";

        $this->saveToWebhooksFolder($filename, $request->all());

        return response()->json(['status' => true]);
    }

    public function message(Request $request, $id)
    {
        $apartamento = Apartamento::find($id);

        $fecha = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "message_{$fecha}.txt";

        $this->saveToWebhooksFolder($filename, $request->all());

        return response()->json(['status' => true]);
    }

    public function review(Request $request, $id)
    {
        $apartamento = Apartamento::find($id);

        $fecha = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "review_{$fecha}.txt";

        $this->saveToWebhooksFolder($filename, $request->all());

        return response()->json(['status' => true]);
    }

    public function reservationRequest(Request $request, $id)
    {
        $apartamento = Apartamento::find($id);

        $fecha = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "reservationRequest_{$fecha}.txt";

        $this->saveToWebhooksFolder($filename, $request->all());

        return response()->json(['status' => true]);
    }

    public function syncError(Request $request, $id)
    {
        $apartamento = Apartamento::find($id);

        $fecha = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "syncError_{$fecha}.txt";

        $this->saveToWebhooksFolder($filename, $request->all());

        return response()->json(['status' => true]);
    }

    public function alterationRequest(Request $request, $id)
    {
        $apartamento = Apartamento::find($id);

        $fecha = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "alterationRequest_{$fecha}.txt";

        $this->saveToWebhooksFolder($filename, $request->all());

        return response()->json(['status' => true]);
    }

    /**
     * Genera notas descriptivas sobre los niños en la reserva
     *
     * @param array $occupancy
     * @return string|null
     */
    private function generarNotasNinos($occupancy)
    {
        $notas = [];

        $totalNinos = ($occupancy['children'] ?? 0) + ($occupancy['infants'] ?? 0);

        if ($totalNinos > 0) {
            $notas[] = "Niños: {$totalNinos}";

            // Información específica sobre infants (bebés)
            if (isset($occupancy['infants']) && $occupancy['infants'] > 0) {
                $notas[] = "Bebés: {$occupancy['infants']}";
            }

            // Información específica sobre children (niños)
            if (isset($occupancy['children']) && $occupancy['children'] > 0) {
                $notas[] = "Niños mayores: {$occupancy['children']}";
            }

            if (isset($occupancy['ages']) && is_array($occupancy['ages'])) {
                $edades = [];
                foreach ($occupancy['ages'] as $edad) {
                    if ($edad <= 2) {
                        $edades[] = "bebé ({$edad} años)";
                    } elseif ($edad <= 12) {
                        $edades[] = "niño ({$edad} años)";
                    } else {
                        $edades[] = "adolescente ({$edad} años)";
                    }
                }
                $notas[] = "Edades: " . implode(', ', $edades);
            }

            // Información adicional sobre cunas si hay bebés
            if (isset($occupancy['ages']) && in_array(0, $occupancy['ages'])) {
                $notas[] = "Se requiere cuna para bebé";
            }

            // Información sobre camas adicionales si hay niños
            if ($totalNinos > 0) {
                $notas[] = "Se pueden proporcionar camas adicionales para niños";
            }

            // Información específica sobre infants
            if (isset($occupancy['infants']) && $occupancy['infants'] > 0) {
                $notas[] = "Consideraciones especiales para bebés";
            }
        }

        return !empty($notas) ? implode('. ', $notas) . '.' : null;
    }

    /**
     * Normaliza un mensaje eliminando códigos, IDs, números de solicitud y otros elementos variables
     * para comparar el contenido real del mensaje
     *
     * @param string $mensaje Mensaje original
     * @return string Mensaje normalizado
     */
    private function normalizarMensajeParaComparacion($mensaje)
    {
        // Asegurar que el mensaje nunca sea NULL
        if ($mensaje === null || $mensaje === '') {
            return '';
        }
        
        // Convertir a minúsculas y eliminar espacios extra
        $normalizado = trim(strtolower($mensaje));

        // Eliminar prefijos comunes de Channex/email
        $normalizado = preg_replace('/\[request received\].*?from.*?suite.*?hawkins.*?exterior.*?\d+[a-z]/i', '', $normalizado);
        $normalizado = preg_replace('/##-.*?por favor.*?escriba.*?respuesta.*?por encima.*?esta.*?línea.*?##/i', '', $normalizado);
        $normalizado = preg_replace('/<img[^>]*>/i', '', $normalizado);
        $normalizado = preg_replace('/reply after this/i', '', $normalizado);

        // Eliminar códigos de solicitud como (39386268), (39386265), etc.
        $normalizado = preg_replace('/\([0-9]{6,}\)/i', '', $normalizado);

        // Eliminar códigos alfanuméricos al final como [Y7EG4J-PPLRK], [ND0PR5-05YP7], etc.
        $normalizado = preg_replace('/\[[A-Z0-9\-]+\]/i', '', $normalizado);

        // Eliminar líneas de separación como "--------------------------------"
        $normalizado = preg_replace('/-{3,}/', '', $normalizado);

        // Eliminar texto de servicio como "Este correo electrónico es un servicio de TravelPerk."
        $normalizado = preg_replace('/este.*?correo.*?electrónico.*?es.*?un.*?servicio.*?de.*?travelperk/i', '', $normalizado);

        // Eliminar URLs y enlaces
        $normalizado = preg_replace('/https?:\/\/[^\s]+/i', '', $normalizado);
        $normalizado = preg_replace('/<a[^>]*>.*?<\/a>/i', '', $normalizado);

        // Eliminar tags HTML
        $normalizado = strip_tags($normalizado);

        // Eliminar números de teléfono (formato variado)
        $normalizado = preg_replace('/\+?[0-9]{1,3}[\s\-]?[0-9]{1,4}[\s\-]?[0-9]{1,4}[\s\-]?[0-9]{1,9}/', '', $normalizado);

        // Eliminar múltiples espacios, saltos de línea y caracteres especiales repetidos
        $normalizado = preg_replace('/\s+/', ' ', $normalizado);
        // NO eliminar todos los caracteres especiales, solo normalizar espacios
        // $normalizado = preg_replace('/[^\w\sáéíóúñü]/u', '', $normalizado);

        // Eliminar espacios al inicio y final
        $normalizado = trim($normalizado);

        return $normalizado;
    }

    /**
     * Verifica si un mensaje de Channex es repetido (contestador automático)
     * Busca mensajes similares del mismo booking_id en los últimos 10 minutos
     * que ya hayan sido respondidos, ignorando códigos y IDs variables
     *
     * @param string $bookingId ID de la reserva en Channex
     * @param string $contenido Contenido del mensaje
     * @param string $sender Remitente del mensaje
     * @return MensajeChat|null Mensaje repetido encontrado o null
     */
    private function verificarMensajeRepetidoChannex($bookingId, $contenido, $sender)
    {
        try {
            // Asegurar que el contenido nunca sea NULL
            $contenido = $contenido ?? '';
            
            // Normalizar el contenido eliminando códigos, IDs y elementos variables
            $contenidoNormalizado = $this->normalizarMensajeParaComparacion($contenido);

            Log::info("🔍 Verificando mensaje repetido", [
                'booking_id' => $bookingId,
                'sender' => $sender,
                'contenido_original_length' => strlen($contenido),
                'contenido_normalizado_length' => strlen($contenidoNormalizado),
                'contenido_normalizado_preview' => substr($contenidoNormalizado, 0, 150)
            ]);

            // Si el mensaje normalizado es muy corto, no aplicar la detección (podría ser un saludo simple)
            if (strlen($contenidoNormalizado) < 30) {
                Log::info("⚠️ Mensaje normalizado muy corto, no se aplica detección", [
                    'length' => strlen($contenidoNormalizado)
                ]);
                return null;
            }

            // Buscar mensajes del mismo booking_id en los últimos 15 minutos (aumentado de 10)
            $fechaLimite = Carbon::now()->subMinutes(15);

            $mensajesRecientes = MensajeChat::where('booking_id', $bookingId)
                ->where('sender', $sender)
                ->where('received_at', '>=', $fechaLimite)
                ->orderBy('received_at', 'desc')
                ->limit(15) // Revisar los últimos 15 mensajes
                ->get();

            Log::info("📊 Mensajes recientes encontrados", [
                'count' => $mensajesRecientes->count(),
                'booking_id' => $bookingId
            ]);

            $mensajeAnterior = null;
            foreach ($mensajesRecientes as $mensaje) {
                $mensajeNormalizado = $this->normalizarMensajeParaComparacion($mensaje->message ?? '');

                // Si el mensaje normalizado es muy corto, saltarlo
                if (strlen($mensajeNormalizado) < 30) {
                    continue;
                }

                // Comparar mensajes normalizados
                if ($mensajeNormalizado === $contenidoNormalizado) {
                    // Mensaje idéntico después de normalización
                    Log::info("✅ Mensaje idéntico encontrado", [
                        'mensaje_id' => $mensaje->id,
                        'received_at' => $mensaje->received_at
                    ]);
                    $mensajeAnterior = $mensaje;
                    break;
                }

                // Verificar similitud alta (más del 85% de similitud - más agresivo)
                // para capturar variaciones menores del contestador automático
                if (strlen($contenidoNormalizado) > 30 && strlen($mensajeNormalizado) > 30) {
                    $similitud = similar_text($contenidoNormalizado, $mensajeNormalizado, $percent);
                    if ($percent > 85) {
                        Log::info("🔍 Mensaje similar detectado", [
                            'similitud' => round($percent, 2) . '%',
                            'mensaje_id' => $mensaje->id,
                            'mensaje_actual_preview' => substr($contenidoNormalizado, 0, 100),
                            'mensaje_anterior_preview' => substr($mensajeNormalizado, 0, 100)
                        ]);
                        $mensajeAnterior = $mensaje;
                        break;
                    }
                }
            }

            // Si encontramos un mensaje anterior similar, verificar que ya se haya respondido
            if ($mensajeAnterior) {
                // Buscar respuesta en ChatGpt usando el sender como remitente
                // Verificar si hay alguna respuesta reciente (últimos 15 minutos)
                $respuestaExistente = ChatGpt::where('remitente', $sender)
                    ->where('date', '>=', $fechaLimite)
                    ->where('status', 1) // Respondido
                    ->whereNotNull('respuesta')
                    ->where('respuesta', '!=', '')
                    ->orderBy('date', 'desc')
                    ->limit(10) // Revisar las últimas 10 respuestas
                    ->get();

                Log::info("📨 Respuestas encontradas en ChatGpt", [
                    'count' => $respuestaExistente->count(),
                    'sender' => $sender
                ]);

                // Si hay al menos una respuesta reciente, considerar que ya se respondió
                // No necesitamos comparar el contenido exacto, solo verificar que hay respuesta
                if ($respuestaExistente->count() > 0) {
                    Log::info("✅ Mensaje repetido encontrado en Channex y ya respondido", [
                        'booking_id' => $bookingId,
                        'sender' => $sender,
                        'mensaje_anterior_id' => $mensajeAnterior->id,
                        'fecha_anterior' => $mensajeAnterior->received_at,
                        'respuestas_encontradas' => $respuestaExistente->count(),
                        'tiempo_transcurrido' => Carbon::now()->diffInSeconds($mensajeAnterior->received_at) . ' segundos',
                        'contenido_normalizado' => substr($contenidoNormalizado, 0, 150)
                    ]);
                    return $mensajeAnterior;
                } else {
                    Log::info("⚠️ Mensaje similar encontrado pero sin respuesta aún", [
                        'mensaje_anterior_id' => $mensajeAnterior->id,
                        'fecha_anterior' => $mensajeAnterior->received_at
                    ]);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("❌ Error verificando mensaje repetido en Channex: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // En caso de error, no bloquear el mensaje (mejor responder que no responder)
            return null;
        }
    }
}

