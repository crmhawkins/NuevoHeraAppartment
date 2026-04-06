<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EnvioClavesEmail;
use App\Models\Apartamento;
use App\Models\MensajeAuto;
use App\Models\Reserva;
use App\Models\Setting;
use App\Services\ClienteService;
use App\Services\MetodoEntradaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckinLinkController extends Controller
{
    /**
     * Genera un enlace firmado con los datos de la reserva para enviar al huésped.
     */
    public function generarLink(Request $request, $reservaId)
    {
        $reserva = Reserva::with(['cliente', 'apartamento'])->find($reservaId);

        if (!$reserva) {
            return response()->json(['error' => 'Reserva no encontrada'], 404);
        }

        $payload = [
            'reserva_id' => $reserva->id,
            'nombre'     => $reserva->cliente->nombre ?? '',
            'apellido'   => $reserva->cliente->apellido1 ?? '',
            'email'      => $reserva->cliente->email ?? '',
            'telefono'   => $reserva->cliente->telefono_movil ?? $reserva->cliente->telefono ?? '',
            'checkin'    => $reserva->fecha_entrada,
            'checkout'   => $reserva->fecha_salida,
            'apartamento' => $reserva->apartamento->nombre ?? '',
            'exp'        => now()->addDays(7)->timestamp,
        ];

        $encoded   = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, config('app.key'));
        $token     = $encoded . '.' . $signature;

        $reserva->update(['token' => $token]);

        return response()->json([
            'url'   => config('services.checkin.url') . '/checkin?token=' . urlencode($token),
            'token' => $token,
        ]);
    }

    /**
     * Envía un mensaje de texto por WhatsApp (para acceso digital).
     * Replica la lógica de Kernel::enviarMensajeTextoWhatsapp
     */
    private function enviarMensajeTextoWhatsappInmediato(string $telefono, string $texto): ?string
    {
        $tokenEnv = Setting::whatsappToken();
        $urlMensajes = Setting::whatsappUrl();

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $telefono,
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $texto,
            ],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $urlMensajes,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $tokenEnv,
            ],
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            Log::error('Checkin API: Error cURL al enviar WhatsApp texto', [
                'telefono' => $telefono,
                'error' => $curlError,
            ]);
            return null;
        }

        return $response;
    }

    /**
     * Recibe los datos completos del huésped desde la app externa de registro.
     */
    public function recibirDatos(Request $request)
    {
        try {
            $request->validate([
                'token'  => 'required|string',
                'guests' => 'required|array|min:1',
                // Validación MIR para cada huésped recibido
                'guests.*.first_name'      => 'required|string|max:255',
                'guests.*.last_name'       => 'required|string|max:255',
                'guests.*.document_number' => 'required|string|max:20|regex:/^[A-Za-z0-9\-]+$/',
                'guests.*.document_type'   => 'required|string',
                'guests.*.birth_date'      => 'required|date|before:today|after:1900-01-01',
                'guests.*.gender'          => 'required|string',
                'guests.*.nationality'     => 'required|string|max:255',
                // Obligatorios al menos para el titular (guest 0)
                'guests.0.email'           => 'required|email|max:255',
                'guests.0.phone'           => 'required|string|max:20',
                'guests.0.address'         => 'required|string|max:500',
                'guests.0.postal_code'     => 'required|string|max:10',
            ]);

            $token = $request->input('token');
            $guests = $request->input('guests');

            $parts = explode('.', $token, 2);
            if (count($parts) !== 2) {
                return response()->json(['error' => 'Token inválido'], 401);
            }

            [$encoded, $signature] = $parts;

            $expectedSignature = hash_hmac('sha256', $encoded, config('app.key'));
            if (!hash_equals($expectedSignature, $signature)) {
                return response()->json(['error' => 'Firma inválida'], 401);
            }

            $payload = json_decode(base64_decode(str_pad(strtr($encoded, '-_', '+/'), strlen($encoded) + (4 - strlen($encoded) % 4) % 4, '=')), true);

            if (!$payload || !isset($payload['exp']) || $payload['exp'] < now()->timestamp) {
                return response()->json(['error' => 'Token expirado'], 401);
            }

            $reserva = Reserva::find($payload['reserva_id']);

            if (!$reserva || $reserva->token !== $token) {
                return response()->json(['error' => 'Token no coincide con la reserva'], 401);
            }

            // Verificar que la reserva no está cancelada
            if ($reserva->estado_id == 4) {
                return response()->json(['error' => 'Esta reserva ha sido cancelada'], 400);
            }

            // Los campos vienen con los nombres del app de registro de visitantes
            // y se mapean a los nombres del modelo Cliente del CRM
            if (empty($guests) || !isset($guests[0])) {
                return response()->json(['error' => 'No se recibieron datos de huéspedes'], 400);
            }

            $guest = $guests[0];

            $map = [
                // campo_registro_visitantes => campo_cliente_crm
                'first_name'       => 'nombre',
                'last_name'        => 'apellido1',
                'email'            => 'email',
                'phone'            => 'telefono_movil',
                'document_number'  => 'num_identificacion',
                'document_type'    => 'tipo_documento_str',
                'birth_date'       => 'fecha_nacimiento',
                'gender'           => 'sexo_str',
                'nationality'      => 'nacionalidadStr',
                'address'          => 'direccion',
                'postal_code'      => 'codigo_postal',
                'city'             => 'localidad',
            ];

            $clienteData = [];
            foreach ($map as $guestField => $clienteField) {
                if (!empty($guest[$guestField])) {
                    $clienteData[$clienteField] = $guest[$guestField];
                }
            }

            // Sanitizar campos de texto: eliminar caracteres de control y espacios extra
            foreach (['nombre', 'apellido1', 'direccion', 'localidad'] as $campo) {
                if (!empty($clienteData[$campo])) {
                    $clienteData[$campo] = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $clienteData[$campo]));
                }
            }
            // Normalizar número de documento a mayúsculas
            if (!empty($clienteData['num_identificacion'])) {
                $clienteData['num_identificacion'] = strtoupper($clienteData['num_identificacion']);
            }

            // Transacción DB: guardar datos del cliente, actualizar reserva y envío MIR
            DB::transaction(function () use ($reserva, $clienteData) {
                if (!empty($clienteData) && $reserva->cliente) {
                    $reserva->cliente->update($clienteData);
                }

                $reserva->update([
                    'dni_entregado' => 1,
                    'verificado'    => 1,
                    'token'         => null, // Invalidar token tras uso
                ]);

                // Auto-envío a MIR si todos los datos están completos
                try {
                    $mirService = new \App\Services\MIRService();
                    $mirResult = $mirService->enviarSiLista($reserva);
                    if ($mirResult) {
                        Log::info('MIR: Auto-envío tras checkin API', [
                            'reserva_id' => $reserva->id,
                            'success' => $mirResult['success'],
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('MIR: Error en auto-envío tras checkin API: ' . $e->getMessage());
                }
            });

            // Envío inmediato de claves de acceso por WhatsApp y Email (fuera de transacción)
            try {
                $reserva->load(['cliente', 'apartamento.edificioName']);
                $apartamentoReservado = Apartamento::find($reserva->apartamento_id);

                // Solo enviar si la reserva tiene codigo_acceso o claves del apartamento
                if (!empty($reserva->apartamento->claves)) {
                    // Comprobar si ya se enviaron las claves (categoria_id = 3)
                    $mensajeClavesExistente = MensajeAuto::where('reserva_id', $reserva->id)
                        ->where('categoria_id', 3)
                        ->first();

                    if (!$mensajeClavesExistente) {
                        $clienteService = app(ClienteService::class);
                        $idiomaCliente = $clienteService->idiomaCodigo($reserva->cliente->nacionalidad);
                        $kernel = app(\App\Console\Kernel::class);

                        // Obtener teléfono limpio del cliente (priorizar móvil, luego fijo)
                        $telefonoCliente = $reserva->cliente->telefono_movil ?? $reserva->cliente->telefono;
                        $phoneCliente = preg_replace('/\+|\s+/', '', $telefonoCliente);

                        // Comprobar método de entrada (digital vs físico)
                        $metodoEntrada = app(MetodoEntradaService::class)->resolverParaReserva($reserva);

                        if ($metodoEntrada === MetodoEntradaService::METODO_DIGITAL) {
                            // Enviar mensaje de acceso digital
                            $mensajeDigital = match (substr((string) $idiomaCliente, 0, 2)) {
                                'es' => "Tu acceso será mediante cerradura digital.\n\nLa entrega del código está pendiente de integración con nuestra plataforma de accesos (código único por cliente y ventana horaria). Si lo necesitas, contáctanos y te ayudamos.",
                                'fr' => "Votre accès se fera via une serrure digitale.\n\nLa livraison du code est en attente d'intégration avec notre plateforme d'accès (code unique par client et fenêtre horaire). Si besoin, contactez-nous.",
                                'de' => "Ihr Zugang erfolgt über ein digitales Schloss.\n\nDie Code-Zustellung wartet noch auf die Integration mit unserer Zugang-Plattform (ein Code pro Gast, zeitlich begrenzt). Bei Bedarf kontaktieren Sie uns.",
                                default => "Your access will be via a digital lock.\n\nCode delivery is pending integration with our access platform (unique code per guest and time window). If you need it, please contact us.",
                            };

                            if (!empty($phoneCliente)) {
                                $this->enviarMensajeTextoWhatsappInmediato($phoneCliente, $mensajeDigital);
                            }

                            Log::info('Checkin API: Acceso digital - mensaje placeholder enviado inmediatamente', [
                                'reserva_id' => $reserva->id,
                            ]);
                        } else {
                            // Obtener clave del edificio con null-check
                            $edificioObj = $reserva->apartamento->edificioName ?? null;
                            $claveEdificio = $edificioObj ? $edificioObj->clave : '';

                            // Enviar claves por WhatsApp
                            if (!empty($phoneCliente)) {
                                $edificioId = $apartamentoReservado->edificio_id ?? null;
                                $esEdificio1 = ($edificioId === 1);
                                $enlace = $esEdificio1 ? 'https://goo.gl/maps/qb7AxP1JAxx5yg3N9' : 'https://maps.app.goo.gl/t81tgLXnNYxKFGW4A';
                                $enlaceLimpio = $esEdificio1 ? 'goo.gl/maps/qb7AxP1JAxx5yg3N9' : 'maps.app.goo.gl/t81tgLXnNYxKFGW4A';

                                if ($reserva->apartamento_id === 1) {
                                    $kernel->clavesMensajeAtico(
                                        $reserva->cliente->nombre,
                                        $reserva->apartamento->titulo,
                                        $claveEdificio,
                                        $reserva->apartamento->claves,
                                        $phoneCliente,
                                        $idiomaCliente,
                                        $idiomaCliente == 'pt_PT' ? 'codigo_atico_por' : 'codigos_atico',
                                        $enlace,
                                        $enlaceLimpio
                                    );
                                } else {
                                    $kernel->clavesMensaje(
                                        $reserva->cliente->nombre == null ? $reserva->cliente->alias : $reserva->cliente->nombre,
                                        $reserva->apartamento->titulo,
                                        $claveEdificio,
                                        $reserva->apartamento->claves,
                                        $phoneCliente,
                                        $idiomaCliente,
                                        $enlace
                                    );
                                }

                                Log::info('Checkin API: Claves enviadas por WhatsApp inmediatamente', [
                                    'reserva_id' => $reserva->id,
                                    'telefono' => $phoneCliente,
                                ]);
                            }

                            // Enviar claves por Email (usar edificio_id entero, no la relación Eloquent)
                            $edificio = $apartamentoReservado->edificio_id ?? 1;
                            if ($reserva->apartamento_id === 1) {
                                $mensajeEmail = $kernel->clavesEmailAtico(
                                    $idiomaCliente,
                                    $reserva->cliente->nombre,
                                    $reserva->apartamento->titulo,
                                    $claveEdificio,
                                    $reserva->apartamento->claves
                                );
                            } else {
                                $mensajeEmail = $kernel->clavesEmail(
                                    $idiomaCliente,
                                    $reserva->cliente->nombre,
                                    $reserva->apartamento->titulo,
                                    $claveEdificio,
                                    $reserva->apartamento->claves,
                                    $edificio
                                );
                            }

                            if (!empty($reserva->cliente->email)) {
                                Mail::to($reserva->cliente->email)->send(new EnvioClavesEmail(
                                    'emails.envioClavesEmail',
                                    $mensajeEmail,
                                    'Hawkins Suite - Claves',
                                    null
                                ));
                            }

                            if (!empty($reserva->cliente->email_secundario) && filter_var($reserva->cliente->email_secundario, FILTER_VALIDATE_EMAIL)) {
                                Mail::to($reserva->cliente->email_secundario)->send(new EnvioClavesEmail(
                                    'emails.envioClavesEmail',
                                    $mensajeEmail,
                                    'Hawkins Suite - Claves',
                                    null
                                ));
                            }

                            Log::info('Checkin API: Claves enviadas por email inmediatamente', [
                                'reserva_id' => $reserva->id,
                            ]);
                        }

                        // Registrar el envío para evitar duplicados con la tarea programada
                        MensajeAuto::create([
                            'reserva_id' => $reserva->id,
                            'cliente_id' => $reserva->cliente_id,
                            'categoria_id' => 3,
                            'fecha_envio' => Carbon::now(),
                        ]);

                        Log::info('Checkin API: MensajeAuto categoria 3 registrado tras envío inmediato', [
                            'reserva_id' => $reserva->id,
                        ]);
                    } else {
                        Log::info('Checkin API: Claves ya enviadas anteriormente, no se reenvían', [
                            'reserva_id' => $reserva->id,
                        ]);
                    }
                } else {
                    Log::warning('Checkin API: No se enviaron claves - apartamento sin claves configuradas', [
                        'reserva_id' => $reserva->id,
                        'apartamento_id' => $reserva->apartamento_id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Checkin API: Error al enviar claves inmediatamente: ' . $e->getMessage(), [
                    'reserva_id' => $reserva->id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $reserva->load(['apartamento.edificio']);
            return response()->json([
                'success'            => true,
                'codigo_acceso'      => $reserva->codigo_acceso,
                'clave_edificio'     => $reserva->apartamento->edificio->clave ?? null,
                'clave_apartamento'  => $reserva->apartamento->claves ?? null,
                'apartamento_nombre' => $reserva->apartamento->nombre ?? null,
                'apartamento_titulo' => $reserva->apartamento->titulo ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('CheckinLinkController@recibirDatos error: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}
