<?php

namespace App\Http\Controllers;

use App\Models\ChatGpt;
use App\Models\Cliente;
use App\Models\Configuraciones;
use App\Models\Mensaje;
use App\Models\MensajeAuto;
use App\Models\PromptAsistente;
use App\Models\Reparaciones;
use App\Models\Reserva;
use App\Models\LimpiadoraGuardia;
use App\Models\WhatsappTemplate;
use App\Models\EmailNotificaciones;
use App\Models\Whatsapp;
use App\Services\ClienteService;
use CURLFile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\geocoding\PhoneNumberOfflineGeocoder;
use libphonenumber\PhoneNumberFormat;
use Illuminate\Support\Facades\Log;
use Laravel\Prompts\Prompt;
use PhpOption\None;
use App\Models\WhatsappLog;
use App\Models\Setting;
use App\Models\WhatsappMensaje;
use Carbon\Carbon;
use App\Models\WhatsappEstadoMensaje;
use App\Models\Incidencia;
use App\Models\User;
use App\Services\AlertService;
use App\Services\NotificationService;

class WhatsappController extends Controller
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function hookWhatsapp(Request $request)
    {
        $responseJson = env('WHATSAPP_KEY', 'valorPorDefecto');

        $query = $request->all();
        $mode = $query['hub_mode'];
        $token = $query['hub_verify_token'];
        $challenge = $query['hub_challenge'];

        // Formatear la fecha y hora actual
        $dateTime = Carbon::now()->format('Y-m-d_H-i-s'); // Ejemplo de formato: 2023-11-13_15-30-25

        // Crear un nombre de archivo con la fecha y hora actual
        $filename = "hookWhatsapp_{$dateTime}.txt";

        Storage::disk('local')->put($filename, json_encode($request->all()));

        return response($challenge, 200)->header('Content-Type', 'text/plain');

    }

    public function processHookWhatsapp(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        // 1. Guardar el JSON original
        WhatsappLog::create(['contenido' => $data]);

        // 2. Guardar el archivo en disco
        if (!Storage::exists('whatsapp/json')) {
            Storage::makeDirectory('whatsapp/json');
        }

        $timestamp = now()->format('Ymd_His_u');
        Storage::put("whatsapp/json/{$timestamp}.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 3. Extraer los datos
        $entry = $data['entry'][0]['changes'][0]['value'] ?? [];

        // 4. Procesar mensajes entrantes
        if (isset($entry['messages'])) {
            foreach ($entry['messages'] as $mensaje) {
                $this->procesarMensajeYResponder($mensaje, $entry);
            }
        }

        // 5. Procesar estados de mensajes enviados
        if (isset($entry['statuses'])) {
            foreach ($entry['statuses'] as $status) {
                $this->procesarStatus($status);
            }
        }

        return response(200)->header('Content-Type', 'text/plain');
    }

    public function procesarStatus(array $status)
    {
        $mensaje = WhatsappMensaje::where('recipient_id', $status['id'])->first(); // CAMBIO AQUÍ

        if ($mensaje) {
            // Guardar último estado
            $mensaje->estado = $status['status'];
            $mensaje->conversacion_id = $status['conversation']['id'] ?? null;
            $mensaje->origen_conversacion = $status['conversation']['origin']['type'] ?? null;
            $mensaje->expiracion_conversacion = isset($status['conversation']['expiration_timestamp'])
                ? Carbon::createFromTimestamp($status['conversation']['expiration_timestamp'])
                : null;
            $mensaje->billable = $status['pricing']['billable'] ?? null;
            $mensaje->categoria_precio = $status['pricing']['category'] ?? null;
            $mensaje->modelo_precio = $status['pricing']['pricing_model'] ?? null;
            $mensaje->errores = $status['errors'] ?? null;
            $mensaje->save();

            // Guardar en histórico
            WhatsappEstadoMensaje::create([
                'whatsapp_mensaje_id' => $mensaje->id,
                'estado' => $status['status'],
                'recipient_id' => $status['recipient_id'] ?? null,
                'fecha_estado' => isset($status['timestamp']) ? Carbon::createFromTimestamp($status['timestamp']) : now(),
            ]);
            return response()->json(['status' => 'ok', 'mensaje' => $mensaje]);
        } else {
            Log::warning("⚠️ No se encontró mensaje con recipient_id = {$status['id']} para guardar estado.");
        }
        return response()->json(['status' => 'faile']);

    }



    public function procesarMensajeYResponder(array $mensaje, array $entry)
    {
        $waId = $mensaje['from'];
        $tipo = $mensaje['type'];
        $id = $mensaje['id'];
        $timestamp = $mensaje['timestamp'] ?? null;

        $contenido = null;
        if ($tipo === 'text') {
            $contenido = $mensaje['text']['body'];
        } elseif ($tipo === 'image' && isset($mensaje['image']['id'])) {
            $contenido = '[Imagen] ' . $mensaje['image']['id'];
        } elseif ($tipo === 'audio' && isset($mensaje['audio']['id'])) {
            $contenido = '[Audio] ' . $mensaje['audio']['id'];
        } elseif ($tipo === 'document') {
            $contenido = '[Documento] ' . ($mensaje['document']['filename'] ?? 'sin nombre');
        }

        // Verificar si el mensaje ya existe para evitar duplicados
        $whatsappMensaje = WhatsappMensaje::firstOrCreate(
            ['mensaje_id' => $id],
            [
                'tipo' => $tipo,
                'contenido' => $contenido,
                'remitente' => $waId,
                'fecha_mensaje' => $timestamp ? Carbon::createFromTimestamp($timestamp) : now(),
                'metadata' => $mensaje
            ]
        );

        // Si el mensaje ya existía, no procesar de nuevo
        if ($whatsappMensaje->wasRecentlyCreated === false) {
            Log::info("🔄 Mensaje duplicado detectado - Ya procesado anteriormente", [
                'mensaje_id' => $id,
                'remitente' => $waId
            ]);
            return response()->json(['status' => 'duplicate', 'message' => 'Mensaje ya procesado']);
        }

            // Solo si es texto, responde con ChatGPT
        if ($tipo === 'text') {
            // COMANDO ESPECIAL: /clear - Limpiar historial de conversación
            if (trim($contenido) === '/clear' || strtolower(trim($contenido)) === '/clear') {
                $this->limpiarHistorialConversacion($waId);

                // Crear registro del comando con status especial para que no se incluya en historial
                $chat = ChatGpt::create([
                    'id_mensaje' => $id,
                    'remitente' => $waId,
                    'mensaje' => $contenido,
                    'respuesta' => '✅ Historial de conversación limpiado. Empezamos de nuevo.',
                    'status' => 3, // Status 3 = comando /clear, no incluir en historial
                    'type' => 'text',
                    'date' => now(),
                ]);

                // Enviar respuesta confirmando limpieza
                $this->contestarWhatsapp($waId, '✅ Historial de conversación limpiado. Empezamos de nuevo.', $whatsappMensaje);

                Log::info("🧹 Historial limpiado para remitente", ['remitente' => $waId]);

                return response()->json(['status' => 'cleared', 'message' => 'Historial limpiado']);
            }

            // VALIDACIÓN: Verificar si es un mensaje repetido de un contestador automático
            // Buscar mensajes idénticos del mismo remitente en los últimos 10 minutos
            $mensajeRepetido = $this->verificarMensajeRepetido($waId, $contenido);

            if ($mensajeRepetido) {
                Log::info("🔄 Mensaje repetido detectado - No se responderá para evitar bucle con contestador automático", [
                    'remitente' => $waId,
                    'mensaje' => substr($contenido, 0, 100),
                    'mensaje_anterior_id' => $mensajeRepetido->id,
                    'fecha_mensaje_anterior' => $mensajeRepetido->date
                ]);

                // Crear registro pero sin responder
                $chat = ChatGpt::create([
                    'id_mensaje' => $id,
                    'whatsapp_mensaje_id' => $whatsappMensaje->id,
                    'remitente' => $waId,
                    'mensaje' => $contenido,
                    'respuesta' => null,
                    'status' => 2, // 2 = mensaje repetido, no responder
                    'type' => 'text',
                    'date' => now(),
                ]);

                return response()->json([
                    'status' => 'ignored',
                    'reason' => 'Mensaje repetido detectado - No se responde para evitar bucle'
                ]);
            }

            // 1. Siempre crear el registro de entrada
            $chat = ChatGpt::create([
                'id_mensaje' => $id,
                'whatsapp_mensaje_id' => $whatsappMensaje->id,
                'remitente' => $waId,
                'mensaje' => $contenido,
                'respuesta' => null, // respuesta aún no disponible
                'status' => 0, // pendiente de respuesta
                'type' => 'text',
                'date' => now(),
            ]);

            // 2. Clasificar el mensaje y notificar si procede
           /*  try {
                Log::info("🔍 Iniciando clasificación del mensaje: {$contenido}");
                $categoria = $this->clasificarMensaje($contenido);
                Log::info("📋 Mensaje clasificado como: {$categoria}");

                if ($categoria === 'averia') {
                    Log::info("🚨 Mensaje clasificado como AVERÍA - Iniciando gestión");
                    $this->gestionarAveria($waId, $contenido);
                } elseif ($categoria === 'limpieza') {
                    Log::info("🧹 Mensaje clasificado como LIMPIEZA - Iniciando gestión");
                    $this->gestionarLimpieza($waId, $contenido);
                } else {
                    Log::info("📝 Mensaje clasificado como: {$categoria} - No requiere notificación");
                }
            } catch (\Throwable $e) {
                Log::error('❌ Error en clasificación o notificación: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
            } */

            // 3. Intentar obtener respuesta de ChatGPT
            $respuestaTexto = $this->enviarMensajeOpenAiChatCompletions($contenido, $waId);

            if ($respuestaTexto) {
                // 3. Solo si hay respuesta, actualizar la fila y contestar
                $chat->update([
                    'respuesta' => $respuestaTexto,
                    'status' => 1,
                ]);

                $response = $this->contestarWhatsapp($waId, $respuestaTexto, $whatsappMensaje);
                return response()->json(['status' => 'ok', 'respuesta' => $respuestaTexto]);
            } else {
                Log::warning("❌ Error de IA local. No se contestó a {$waId}.");
                return response()->json(['status' => 'failed', 'message' => 'No se obtuvo respuesta de la IA']);

                // Se mantiene la fila con status = 0 y respuesta = null
            }
        }

    }

    function enviarMensajeOpenAiChatCompletions($nuevoMensaje, $remitente)
    {
        // Configuración de la IA local Hawkins para WhatsApp
        $config = config('services.hawkins_whatsapp_ai');
        $endpoint = $config['base_url'];

        // Asegurar que la URL termine en /chat/chat
        if (!str_ends_with($endpoint, '/chat/chat')) {
            // Si termina en /chat, agregar /chat
            if (str_ends_with($endpoint, '/chat')) {
                $endpoint = rtrim($endpoint, '/chat') . '/chat/chat';
            } else {
                // Si termina en /, agregar chat/chat
                $endpoint = rtrim($endpoint, '/') . '/chat/chat';
            }
        }

        $apiKey = $config['api_key'];
        $modelo = $config['model'];

        // Obtener el prompt completo de la base de datos
        $promptAsistente = PromptAsistente::first();
        $promptBase = $promptAsistente ? trim($promptAsistente->prompt) : "Eres María, el asistente virtual de Apartamentos Hawkins. Tu rol es ayudar a los clientes de forma educada, formal pero cercana.";

        // Usar el prompt de la BD tal cual (sin modificaciones)
        $promptSystem = $promptBase;

        // Obtener historial de conversación PRIMERO para poder verificar contexto
        // Historial: solo mensajes de las últimas 2 horas y desde el último /clear
        // Incluir mensajes con status=1 que tienen respuesta Y mensajes recientes (últimos 30 minutos) aunque no tengan status=1

        // Fecha límite: máximo 2 horas hacia atrás (definida fuera del try para que esté disponible en todo el método)
        $fechaLimite2Horas = now()->subHours(2);
        // Ventana ampliada para mensajes recientes: 30 minutos (para capturar conversaciones activas)
        // Definida fuera del try para que esté disponible en todo el método
        $ventanaRecientes = now()->subMinutes(30);

        try {

            $query = ChatGpt::where('remitente', $remitente)
                ->where('mensaje', '!=', '/clear') // También excluir mensajes /clear explícitamente
                ->where(function($dateQ) use ($fechaLimite2Horas) {
                    // Solo mensajes de las últimas 2 horas
                    $dateQ->where(function($d) use ($fechaLimite2Horas) {
                        $d->where('created_at', '>=', $fechaLimite2Horas)
                          ->orWhere('date', '>=', $fechaLimite2Horas);
                    });
                })
                ->where(function($q) use ($ventanaRecientes) {
                    // Incluir mensajes con status=1 que tienen respuesta
                    $q->where(function($subQ) {
                        $subQ->where('status', 1)
                             ->whereNotNull('respuesta')
                             ->where('respuesta', '!=', '');
                    })
                    // O incluir mensajes recientes (últimos 30 minutos) aunque no tengan status=1
                    // Esto asegura que se capturen mensajes del asistente recientes incluso si aún no tienen status=1
                    ->orWhere(function($subQ) use ($ventanaRecientes) {
                        $subQ->where(function($dateQ) use ($ventanaRecientes) {
                            $dateQ->where('created_at', '>=', $ventanaRecientes)
                                  ->orWhere('date', '>=', $ventanaRecientes);
                        })
                        ->where(function($msgQ) {
                            // Incluir tanto mensajes del usuario como respuestas del asistente
                            $msgQ->where(function($m) {
                                $m->whereNotNull('mensaje')
                                  ->where('mensaje', '!=', '');
                            })
                            ->orWhere(function($r) {
                                $r->whereNotNull('respuesta')
                                  ->where('respuesta', '!=', '');
                            });
                        });
                    });
                });

            // Buscar el último mensaje /clear para este remitente
            $ultimoClear = ChatGpt::where('remitente', $remitente)
                ->where('mensaje', '/clear')
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            // Debug: ver todos los mensajes del remitente sin filtros
            $todosMensajes = ChatGpt::where('remitente', $remitente)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['id', 'mensaje', 'respuesta', 'date', 'created_at', 'status']);

            Log::info("🔍 DEBUG todos los mensajes del remitente (últimos 10)", [
                'total' => ChatGpt::where('remitente', $remitente)->count(),
                'mensajes' => $todosMensajes->map(function($m) {
                    return [
                        'id' => $m->id,
                        'mensaje_preview' => substr($m->mensaje ?? '', 0, 30),
                        'respuesta_preview' => substr($m->respuesta ?? '', 0, 30),
                        'date' => $m->date,
                        'created_at' => $m->created_at ? $m->created_at->toDateTimeString() : null,
                        'status' => $m->status
                    ];
                })->toArray()
            ]);

            // Si hay un /clear previo, solo incluir mensajes después de ese /clear
            // Aplicar siempre, sin importar cuándo fue el /clear
            if ($ultimoClear) {
                // Usar created_at del /clear porque es más preciso (incluye hora)
                // Si no tiene created_at, usar date pero parsearlo correctamente
                $fechaClearRaw = $ultimoClear->created_at ? $ultimoClear->created_at : ($ultimoClear->date ? $ultimoClear->date : now());

                // Convertir a Carbon para asegurar comparación correcta
                $fechaClear = is_string($fechaClearRaw) ? Carbon::parse($fechaClearRaw) : $fechaClearRaw;

                // Si date es solo fecha (sin hora), usar created_at del mensaje para comparar
                // Comparar usando created_at de los mensajes porque es más preciso
                $query->where('created_at', '>', $fechaClear);

                Log::info("🧹 Filtro /clear aplicado", [
                    'fecha_clear_created_at' => $ultimoClear->created_at ? $ultimoClear->created_at->toDateTimeString() : null,
                    'fecha_clear_date' => $ultimoClear->date,
                    'fecha_clear_usada' => $fechaClear->toDateTimeString(),
                    'comparacion' => 'created_at > ' . $fechaClear->toDateTimeString()
                ]);
            }

            // Debug: contar mensajes antes de procesar
            $totalMensajesQuery = $query->count();
            Log::info("🔍 DEBUG historial antes de procesar", [
                'total_mensajes_query' => $totalMensajesQuery,
                'fecha_limite_2_horas' => $fechaLimite2Horas->toDateTimeString(),
                'ventana_recientes' => $ventanaRecientes->toDateTimeString(),
                'tiene_ultimo_clear' => $ultimoClear ? true : false
            ]);

            $historialArray = $query
                ->orderBy('date', 'asc') // Orden cronológico ascendente
                ->orderBy('created_at', 'asc')
                ->limit(40) // Límite razonable de mensajes
                ->get();

            // Debug: ver algunos mensajes encontrados
            Log::info("🔍 DEBUG mensajes encontrados", [
                'total_registros' => $historialArray->count(),
                'primeros_3' => $historialArray->take(3)->map(function($chat) {
                    return [
                        'id' => $chat->id,
                        'mensaje' => substr($chat->mensaje ?? '', 0, 50),
                        'respuesta' => substr($chat->respuesta ?? '', 0, 50),
                        'date' => $chat->date,
                        'created_at' => $chat->created_at,
                        'status' => $chat->status
                    ];
                })->toArray()
            ]);

            $historialArray = $historialArray->flatMap(function ($chat) {
                    $mensajes = [];
                    // No incluir el mensaje /clear en el historial
                    if (!empty($chat->mensaje) && trim($chat->mensaje) !== '/clear') {
                        $mensajes[] = "Usuario: " . trim($chat->mensaje);
                    }
                    // Incluir respuesta si existe
                    if (!empty($chat->respuesta)) {
                        $mensajes[] = "Asistente: " . trim($chat->respuesta);
                    }
                    return $mensajes;
                })
                ->filter(function($mensaje) {
                    // Filtrar mensajes vacíos
                    return !empty(trim($mensaje));
                })
                ->toArray();

            // Limitar a los últimos 40 elementos (20 pares de usuario/asistente) después de procesar
            $historialArray = array_slice($historialArray, -40);

            // Formatear fecha del último clear de forma segura
            $ultimoClearFecha = null;
            if ($ultimoClear) {
                $fechaClear = $ultimoClear->date ?: $ultimoClear->created_at;
                // Convertir a Carbon si es una cadena
                if (is_string($fechaClear)) {
                    try {
                        $ultimoClearFecha = Carbon::parse($fechaClear)->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        Log::warning("Error parseando fecha del último clear: " . $e->getMessage());
                        $ultimoClearFecha = $fechaClear; // Usar el valor original si falla el parseo
                    }
                } else {
                    $ultimoClearFecha = $fechaClear->format('Y-m-d H:i:s');
                }
            }

            Log::info("📋 Historial obtenido", [
                'remitente' => $remitente,
                'total_lineas' => count($historialArray),
                'fecha_limite' => $fechaLimite2Horas->format('Y-m-d H:i:s'),
                'ultimo_clear' => $ultimoClearFecha,
                'primeras_lineas' => array_slice($historialArray, 0, 4),
                'ultimas_lineas' => array_slice($historialArray, -4)
            ]);
        } catch (\Exception $e) {
            Log::error("❌ Error obteniendo historial: " . $e->getMessage());
            // En caso de error, intentar obtener historial básico sin filtros complejos
            $historialArray = ChatGpt::where('remitente', $remitente)
                ->where('status', 1)
                ->whereNotNull('respuesta')
                ->where('respuesta', '!=', '')
                ->where('mensaje', '!=', '/clear')
                ->orderBy('created_at', 'asc')
                ->limit(20)
                ->get()
                ->flatMap(function ($chat) {
                    $mensajes = [];
                    if (!empty($chat->mensaje) && trim($chat->mensaje) !== '/clear') {
                        $mensajes[] = "Usuario: " . trim($chat->mensaje);
                    }
                    if (!empty($chat->respuesta)) {
                        $mensajes[] = "Asistente: " . trim($chat->respuesta);
                    }
                    return $mensajes;
                })
                ->filter(function($mensaje) {
                    return !empty(trim($mensaje));
                })
                ->toArray();
        }

        // Siempre usar historial si existe (ya está filtrado por las últimas 2 horas y mensajes recientes)
        // El historial ya incluye mensajes de las últimas 2 horas y mensajes recientes (últimos 30 minutos)
        // Solo limpiar si realmente no hay historial
        $usarHistorial = !empty($historialArray);

        // Convertir a string para pasar a funciones si es necesario
        $historialTexto = $usarHistorial ? implode("\n", $historialArray) : '';

        // Detectar código de reserva en el mensaje actual y en el historial (solo para información en el prompt)
        $codigoEnMensajeActual = $this->detectarCodigoReserva($nuevoMensaje);
        $codigoEnHistorial = null;
        if (!empty($historialTexto)) {
            $codigoEnHistorial = $this->detectarCodigoReserva($historialTexto);
        }
        $codigoDisponible = $codigoEnMensajeActual ?: $codigoEnHistorial;

        // Definir las tools (funciones) disponibles
        $tools = [
            [
                "type" => "function",
                "function" => [
                    "name" => "obtener_claves",
                    "description" => "Devuelve la clave de acceso al apartamento según el código de reserva, solo si es la fecha de entrada, ha pasado la hora de entrada y el cliente ha entregado el DNI.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "codigo_reserva" => [
                                "type" => "string",
                                "description" => "Código de la reserva del cliente"
                            ]
                        ],
                        "required" => ["codigo_reserva"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "notificar_tecnico",
                    "description" => "Notifica al técnico cuando hay una avería real que requiere intervención inmediata. Solo usar cuando el problema no se puede resolver con información general o cuando después de intentar resolver el problema con la información general no se ha resuelto el problema.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "descripcion_problema" => [
                                "type" => "string",
                                "description" => "Descripción detallada del problema reportado por el cliente"
                            ],
                            "urgencia" => [
                                "type" => "string",
                                "enum" => ["baja", "media", "alta"],
                                "description" => "Nivel de urgencia del problema"
                            ]
                        ],
                        "required" => ["descripcion_problema", "urgencia"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "notificar_limpieza",
                    "description" => "Notifica al equipo de limpieza cuando hay una solicitud de limpieza que requiere intervención. Solo usar cuando el cliente solicita limpieza específica o cuando después de intentar resolver el problema con la información general no se ha resuelto el problema.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "tipo_limpieza" => [
                                "type" => "string",
                                "description" => "Tipo de limpieza solicitada (ej: limpieza general, cambio de ropa, etc.)"
                            ],
                            "observaciones" => [
                                "type" => "string",
                                "description" => "Observaciones adicionales del cliente"
                            ]
                        ],
                        "required" => ["tipo_limpieza"]
                    ]
                ]
            ]
        ];

        // Usar el prompt de la BD tal cual, sin modificaciones
        $promptCompleto = $promptBase;

        // Agregar contexto sobre el canal de comunicación (sin modificar el prompt base)
        $promptCompleto .= "\n\nIDIOMA: SIEMPRE responde en ESPAÑOL. Todas tus respuestas deben estar en español, sin excepciones. NO respondas en inglés ni en ningún otro idioma.\n\n" .
            "CONTEXTO: Esta conversación está teniendo lugar por WhatsApp. El cliente ya está hablando contigo por WhatsApp, por lo tanto NO debes sugerirle que contacte por WhatsApp, ya que ya está aquí. Si necesita ayuda adicional, puedes proporcionarla directamente en esta conversación.\n\n" .
            "FORMATO OBLIGATORIO PARA USAR HERRAMIENTAS (FUNCIONES):\n" .
            "- Cuando necesites usar una herramienta, DEBES usar EXACTAMENTE este formato y SOLO este formato: [FUNCION:nombre_funcion:parametro1=valor1:parametro2=valor2]\n" .
            "- Ejemplos CORRECTOS:\n" .
            "  * [FUNCION:obtener_claves:codigo_reserva=5570112385]\n" .
            "  * [FUNCION:notificar_tecnico:descripcion_problema=No funciona el aire acondicionado:urgencia=alta]\n" .
            "  * [FUNCION:notificar_limpieza:tipo_limpieza=Limpieza general:observaciones=Necesito cambio de toallas]\n" .
            "- Formatos INCORRECTOS que NO debes usar:\n" .
            "  * JSON: {\"action\": \"obtener_claves\", \"code\": \"5570112385\"}\n" .
            "  * Texto en inglés: \"We will call obtener_claves with reservation_code\"\n" .
            "  * Explicaciones antes/después del formato\n" .
            "- REGLAS IMPORTANTES:\n" .
            "  * NO escribas explicaciones antes o después del formato [FUNCION:...]\n" .
            "  * NO uses otros formatos como JSON, texto plano, o frases en inglés\n" .
            "  * Si necesitas usar una herramienta, escribe SOLO el formato [FUNCION:...] sin texto adicional\n" .
            "  * Si NO necesitas usar ninguna herramienta, responde normalmente en español sin usar ningún formato especial\n\n" .
            "PROCEDIMIENTO PARA PROBLEMAS CON CLAVES:\n" .
            "- Cuando un cliente tenga problemas con las claves (no las ha recibido, no funcionan, etc.), VERIFICA PRIMERO si ya proporcionó su código de reserva en mensajes anteriores del historial.\n" .
            "- Si el cliente YA proporcionó su código de reserva en el historial, usa INMEDIATAMENTE la función obtener_claves con ese código. NO vuelvas a pedir el código.\n" .
            "- Si el cliente NO ha proporcionado su código de reserva aún, entonces pídeselo UNA SOLA VEZ.\n" .
            "- Cuando el cliente dice que no tiene las claves o no le han llegado, SI YA TIENES SU CÓDIGO DE RESERVA (del historial o del mensaje actual), usa INMEDIATAMENTE la función obtener_claves para verificar:\n" .
            "  * Si la reserva existe y es válida\n" .
            " * La fecha de entrada de la reserva\n" .
            " * Si es el día de entrada y la hora actual\n" .
            " * Si el cliente ha entregado el DNI\n" .
            " * Si las claves están disponibles según el horario\n" .
            "- La función obtener_claves te dará toda la información necesaria y te indicará qué hacer según la situación.\n" .
            "- NO preguntes al cliente información que puedes obtener automáticamente usando las herramientas.\n" .
            "- NO pidas el código de reserva múltiples veces si ya lo tienes en el historial.\n\n" .
            "HORARIO DE ENTREGA DE CLAVES:\n" .
            "- Las claves se entregan a las 14:00h del día de entrada.\n" .
            "- El acceso oficial al apartamento es a partir de las 15:00h.\n" .
            "- Si la reserva es para HOY pero aún NO son las 14:00h, debes informar al cliente que las claves estarán disponibles a las 14:00h y NO proporcionar más ayuda sobre claves hasta esa hora.\n\n" .
            "SEGURIDAD - CÓDIGO DE EMERGENCIA:\n" .
            "- NUNCA menciones, proporciones o hagas referencia al código de emergencia en tus respuestas.\n" .
            "- El código de emergencia es información confidencial que NO debes compartir bajo ninguna circunstancia.\n" .
            "- Si el cliente necesita ayuda con acceso, usa la función obtener_claves para verificar su situación y proporcionar la ayuda adecuada según el horario y el estado de su reserva.\n\n" .
            "POLÍTICA DE PRECIOS Y COMPENSACIONES - PROHIBICIÓN ABSOLUTA:\n" .
            "- BAJO NINGÚN CONCEPTO puedes ofrecer, prometer, sugerir o mencionar:\n" .
            "  * Compensaciones económicas de cualquier tipo\n" .
            "  * Descuentos en reservas, servicios o productos\n" .
            "  * Extras gratis o servicios adicionales sin costo\n" .
            "  * Cupones de descuento o promociones especiales\n" .
            "  * Reembolsos parciales o totales\n" .
            "  * Bonificaciones o créditos\n" .
            "  * Cualquier tipo de beneficio económico o material gratuito\n" .
            "- DEBES regirte ÚNICAMENTE a los precios establecidos en la lista oficial de precios.\n" .
            "- NUNCA ofrezcas nada gratis, ningún descuento, ni ningún cupón bajo ninguna circunstancia.\n" .
            "- Si un cliente solicita compensación, descuento o algo gratis, debes explicarle educadamente que no tienes autorización para ofrecer ese tipo de beneficios y que los precios están establecidos según la lista oficial.\n" .
            "- Esta es una PROHIBICIÓN ABSOLUTA que NO tiene excepciones bajo ninguna circunstancia.\n\n" .
            "PREVENCIÓN DE INCIDENCIAS DUPLICADAS:\n" .
            "- ANTES de usar las funciones notificar_tecnico o notificar_limpieza, DEBES verificar SIEMPRE el historial de conversación para ver si YA se registró una incidencia similar en esta misma conversación.\n" .
            "- Busca en el historial mensajes del Asistente que contengan frases como:\n" .
            "  * \"He notificado al técnico\"\n" .
            "  * \"He notificado al equipo de limpieza\"\n" .
            "  * \"ya ha sido notificado\"\n" .
            "  * \"ya fue registrada\"\n" .
            "  * \"He ejecutado una función\" relacionada con notificaciones\n" .
            "- Si encuentras que YA se registró una incidencia similar en esta conversación (mismo tipo: avería o limpieza), NO uses la función de nuevo.\n" .
            "- En su lugar, responde al cliente informándole que la incidencia ya fue registrada anteriormente en esta conversación y que el equipo correspondiente ya ha sido notificado.\n" .
            "- SOLO usa las funciones notificar_tecnico o notificar_limpieza si NO encuentras ninguna mención previa de que ya se registró esa incidencia en el historial de esta conversación.\n" .
            "- Esta verificación es CRÍTICA para evitar registrar la misma incidencia múltiples veces.";

        // 2. Historial de conversación (solo si se debe usar y no está vacío)
        if ($usarHistorial && !empty($historialArray)) {
            $promptCompleto .= "\n\nHISTORIAL DE CONVERSACIÓN:\n" . implode("\n", $historialArray);
            Log::info("✅ Historial incluido en el prompt", [
                'lineas_historial' => count($historialArray),
                'historial_preview' => array_slice($historialArray, -3)
            ]);
        } else {
            Log::info("⚠️ Historial NO incluido en el prompt", [
                'usar_historial' => $usarHistorial,
                'historial_vacio' => empty($historialArray),
                'total_lineas_historial' => count($historialArray)
            ]);
        }

        // 3. Nuevo mensaje del usuario
        $promptCompleto .= "\n\nUsuario: " . $nuevoMensaje . "\n\nAsistente:";

        // Detectar código de reserva en el mensaje para logging
        $codigoEnMensaje = $this->detectarCodigoReserva($nuevoMensaje);

        Log::info("🤖 Enviando mensaje a IA local Hawkins", [
            'endpoint' => $endpoint,
            'modelo' => $modelo,
            'remitente' => $remitente,
            'mensaje' => substr($nuevoMensaje, 0, 100),
            'codigo_detectado' => $codigoEnMensaje,
            'usar_historial' => $usarHistorial,
            'historial_lineas' => count($historialArray),
            'tiene_historial' => !empty($historialArray) && $usarHistorial,
            'ultimo_clear_encontrado' => $ultimoClear ? ($ultimoClear->date ?? $ultimoClear->created_at) : null,
            'ultimas_lineas_historial' => $usarHistorial ? array_slice($historialArray, -4) : [], // Últimas 4 líneas para debug
            'prompt_length' => strlen($promptCompleto),
            'prompt_preview' => substr($promptCompleto, 0, 500) . '...' // Primeros 500 caracteres del prompt
        ]);

        // Log completo del prompt sin truncar
        Log::info("📤 PROMPT COMPLETO ENVIADO A LA IA (SIN TRUNCAR)\n" . $promptCompleto);

        // Guardar prompt completo en archivo para evitar truncamiento
        try {
            $logDir = storage_path('logs/prompts_ia');
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/prompt_' . date('Y-m-d_H-i-s') . '_' . substr(md5($remitente . time()), 0, 8) . '.txt';
            file_put_contents($logFile, "=== PROMPT COMPLETO ENVIADO A LA IA ===\n");
            file_put_contents($logFile, "Fecha: " . now()->toDateTimeString() . "\n", FILE_APPEND);
            file_put_contents($logFile, "Remitente: {$remitente}\n", FILE_APPEND);
            file_put_contents($logFile, "Mensaje: {$nuevoMensaje}\n", FILE_APPEND);
            file_put_contents($logFile, "========================================\n\n", FILE_APPEND);
            file_put_contents($logFile, $promptCompleto, FILE_APPEND);
            Log::info("💾 Prompt completo guardado en archivo", ['archivo' => $logFile]);
        } catch (\Exception $e) {
            Log::warning("⚠️ No se pudo guardar prompt en archivo", ['error' => $e->getMessage()]);
        }

        // Llamar a la API local
        $response = $this->hacerPeticionIALocal($endpoint, $apiKey, $promptCompleto, $modelo, 60);

        if ($response->failed()) {
            Log::error("❌ Error llamando a IA local Hawkins: " . $response->body());
            return null;
        }

        $data = $response->json();

        if (!isset($data['success']) || !$data['success']) {
            Log::error("❌ Error en respuesta de IA local: " . json_encode($data));
            return null;
        }

        $respuestaTexto = $data['respuesta'] ?? null;

        if (!$respuestaTexto) {
            Log::warning("⚠️ Respuesta vacía de IA local");
            return null;
        }


        Log::info("📝 Respuesta recibida de IA local", [
            'respuesta_preview' => substr($respuestaTexto, 0, 200),
            'tiene_funcion' => preg_match('/\[FUNCION:/', $respuestaTexto) ? 'sí' : 'no'
        ]);

        // Solo ejecutar función si la IA explícitamente indica que quiere usarla
        // La IA debe decidir cuándo usar las herramientas, no el código
        $funcionDetectada = false;
        $nombreFuncion = null;
        $parametrosStr = null;

        // Formato 1: [FUNCION:nombre:parametros]
        if (preg_match('/\[FUNCION:([^:]+):(.+?)\]/', $respuestaTexto, $matches)) {
            $nombreFuncion = trim($matches[1]);
            $parametrosStr = $matches[2];
            $funcionDetectada = true;
        }
        // Formato 2: Texto plano "We will call obtener_claves with reservation_code "5570112385"."
        elseif (preg_match('/will call\s+(\w+)\s+with\s+reservation_code\s+["\']?([0-9]+)["\']?/i', $respuestaTexto, $matches)) {
            $nombreFuncion = trim($matches[1]);
            $codigo = trim($matches[2]);
            if ($nombreFuncion === 'obtener_claves') {
                $parametrosStr = 'codigo_reserva=' . $codigo;
                $funcionDetectada = true;
                Log::info("🔧 Función detectada en formato texto (will call): {$nombreFuncion} con código {$codigo}");
            }
        }
        // Formato 3: JSON con "action" y "code" (formato que está usando la IA)
        elseif (preg_match('/\{[^}]*"action"\s*:\s*"([^"]+)"[^}]*"code"\s*:\s*"([^"]+)"/', $respuestaTexto, $matches)) {
            $nombreFuncion = trim($matches[1]);
            $codigo = trim($matches[2]);
            // Mapear nombres de función
            if ($nombreFuncion === 'obtener_claves') {
                $parametrosStr = 'codigo_reserva=' . $codigo;
                $funcionDetectada = true;
                Log::info("🔧 Función detectada en formato JSON (action/code): {$nombreFuncion} con código {$codigo}");
            }
        }
        // Formato 3: JSON con "function" y "arguments" (formato alternativo)
        elseif (preg_match('/\{[^}]*"function"\s*:\s*"([^"]+)"[^}]*"arguments"\s*:\s*\{([^}]+)\}/', $respuestaTexto, $matches)) {
            $nombreFuncion = trim($matches[1]);
            // Parsear argumentos JSON
            if (preg_match('/"reservation_code"\s*:\s*"([^"]+)"/', $matches[2], $argMatches)) {
                $parametrosStr = 'codigo_reserva=' . $argMatches[1];
            } elseif (preg_match('/"codigo_reserva"\s*:\s*"([^"]+)"/', $matches[2], $argMatches)) {
                $parametrosStr = 'codigo_reserva=' . $argMatches[1];
            } elseif (preg_match('/"descripcion_problema"\s*:\s*"([^"]+)"[^}]*"urgencia"\s*:\s*"([^"]+)"/', $matches[2], $argMatches)) {
                $parametrosStr = 'descripcion_problema=' . $argMatches[1] . ':urgencia=' . $argMatches[2];
            } elseif (preg_match('/"tipo_limpieza"\s*:\s*"([^"]+)"[^}]*"observaciones"\s*:\s*"([^"]+)"/', $matches[2], $argMatches)) {
                $parametrosStr = 'tipo_limpieza=' . $argMatches[1] . ':observaciones=' . $argMatches[2];
            }
            $funcionDetectada = true;
            Log::info("🔧 Función detectada en formato JSON (function/arguments): {$nombreFuncion}");
        }

        if ($funcionDetectada) {
            // Parsear parámetros
            $parametros = [];
            foreach (explode(':', $parametrosStr) as $param) {
                if (strpos($param, '=') !== false) {
                    list($key, $value) = explode('=', $param, 2);
                    $parametros[trim($key)] = trim($value);
                }
            }

            Log::info("🔧 Función detectada: {$nombreFuncion}", ['parametros' => $parametros]);

            // Ejecutar función correspondiente
            if ($nombreFuncion === 'obtener_claves') {
                $codigoReserva = $parametros['codigo_reserva'] ?? null;
                // Si no hay código en parámetros, intentar detectarlo
                if (!$codigoReserva) {
                    $codigoReserva = $this->detectarCodigoReserva($nuevoMensaje);
                    if (!$codigoReserva) {
                        $codigoReserva = $this->detectarCodigoReserva($historialTexto);
                    }
                }

                // Validar que el código sea válido antes de ejecutar
                if (!$codigoReserva || strlen($codigoReserva) < 8) {
                    Log::warning("⚠️ Código de reserva inválido o no proporcionado: " . ($codigoReserva ?? 'null'));
                    // Devolver mensaje pidiendo el código en lugar de ejecutar la función
                    $mensajeError = "Para poder proporcionarte las claves, necesito tu código de reserva, por favor.";
                    return $this->llamarIALocalConContexto($promptSystem, $historialTexto, $nuevoMensaje, $mensajeError, $endpoint, $apiKey, $modelo);
                }


                $resultadoFuncion = $this->ejecutarObtenerClaves($codigoReserva, $remitente, $promptSystem, $historialTexto, $nuevoMensaje, $endpoint, $apiKey, $modelo);
                return $resultadoFuncion;

            } elseif ($nombreFuncion === 'notificar_tecnico') {
                // Verificar si ya se registró una incidencia de avería en esta conversación
                if ($this->verificarIncidenciaYaRegistradaEnHistorial($historialTexto, 'averia')) {
                    Log::info("⚠️ Incidencia de avería ya registrada en esta conversación - No se ejecutará de nuevo");
                    $mensajeFuncion = "La incidencia ya fue registrada anteriormente en esta conversación. Nuestro equipo técnico ya ha sido notificado y te contactará pronto.";
                    return $this->llamarIALocalConContexto($promptSystem, $historialTexto, $nuevoMensaje, $mensajeFuncion, $endpoint, $apiKey, $modelo);
                }

                $descripcion = $parametros['descripcion_problema'] ?? ($parametros['descripcion'] ?? $nuevoMensaje);
                $urgencia = $parametros['urgencia'] ?? 'media';
                $resultadoFuncion = $this->ejecutarNotificarTecnico($remitente, $descripcion, $urgencia, $promptSystem, $historialTexto, $nuevoMensaje, $endpoint, $apiKey, $modelo);
                return $resultadoFuncion;

            } elseif ($nombreFuncion === 'notificar_limpieza') {
                // Verificar si ya se registró una incidencia de limpieza en esta conversación
                if ($this->verificarIncidenciaYaRegistradaEnHistorial($historialTexto, 'limpieza')) {
                    Log::info("⚠️ Incidencia de limpieza ya registrada en esta conversación - No se ejecutará de nuevo");
                    $mensajeFuncion = "La solicitud de limpieza ya fue registrada anteriormente en esta conversación. Nuestro equipo de limpieza ya ha sido notificado y te avisaremos cuando esté confirmado.";
                    return $this->llamarIALocalConContexto($promptSystem, $historialTexto, $nuevoMensaje, $mensajeFuncion, $endpoint, $apiKey, $modelo);
                }

                $tipoLimpieza = $parametros['tipo_limpieza'] ?? $nuevoMensaje;
                $observaciones = $parametros['observaciones'] ?? '';
                $resultadoFuncion = $this->ejecutarNotificarLimpieza($remitente, $tipoLimpieza, $observaciones, $promptSystem, $historialTexto, $nuevoMensaje, $endpoint, $apiKey, $modelo);
                return $resultadoFuncion;
            }
        }

        return $respuestaTexto;
    }

    /**
     * Detectar código de reserva en el mensaje
     * Busca patrones alfanuméricos o numéricos que parezcan códigos de reserva
     */
    private function detectarCodigoReserva($mensaje)
    {
        // Limpiar el mensaje
        $mensajeLimpio = trim($mensaje);

        // Buscar códigos alfanuméricos de 8-15 caracteres (formato típico de códigos de reserva)
        // Patrón: letras y números, sin espacios, entre 8 y 15 caracteres
        if (preg_match('/\b([A-Z0-9]{8,15})\b/i', $mensajeLimpio, $matches)) {
            $codigo = strtoupper($matches[1]);

            // Aceptar códigos con letras o solo numéricos (pero con al menos 8 dígitos)
            if (preg_match('/[A-Z]/i', $codigo) || (preg_match('/^[0-9]{8,15}$/', $codigo))) {
                // Verificar que no sea solo letras (debe tener números)
                if (preg_match('/[0-9]/', $codigo)) {
                    Log::info("🔍 Código de reserva detectado: {$codigo}");
                    return $codigo;
                }
            }
        }

        // También buscar códigos numéricos de 8-15 dígitos (para códigos como 5215046897)
        if (preg_match('/\b([0-9]{8,15})\b/', $mensajeLimpio, $matches)) {
            $codigo = $matches[1];
            Log::info("🔍 Código de reserva numérico detectado: {$codigo}");
            return $codigo;
        }

        return null;
    }

    /**
     * Ejecutar función obtener_claves
     */
    private function ejecutarObtenerClaves($codigoReserva, $remitente, $promptSystem, $historial, $nuevoMensaje, $endpoint, $apiKey, $modelo)
    {
        // Si no se proporciona código, intentar detectarlo del mensaje y del historial
        if (!$codigoReserva) {
            $codigoReserva = $this->detectarCodigoReserva($nuevoMensaje);
            if (!$codigoReserva && !empty($historial)) {
                // Buscar en historial (el historial viene como string con saltos de línea)
                $codigoReserva = $this->detectarCodigoReserva($historial);
            }
        }

        if (!$codigoReserva) {
            $mensajeError = "No se pudo identificar el código de reserva. Por favor, proporciona tu código de reserva.";
            return $this->llamarIALocalConContexto($promptSystem, $historial, $nuevoMensaje, $mensajeError, $endpoint, $apiKey, $modelo);
        }

        $reserva = Reserva::where('codigo_reserva', $codigoReserva)->first();

        if (!$reserva) {
            $mensajeError = "No se encontró ninguna reserva con el código: {$codigoReserva}. Por favor, verifica que el código sea correcto.";
            return $this->llamarIALocalConContexto($promptSystem, $historial, $nuevoMensaje, $mensajeError, $endpoint, $apiKey, $modelo);
        }

        $fechaEntrada = Carbon::parse($reserva->fecha_entrada);
        $horaActual = now()->format('H:i');

        if (empty($reserva->dni_entregado)) {
            $url = 'https://crm.apartamentosalgeciras.com/dni-user/' . $reserva->token;
            $mensajeFuncion = "Para poder proporcionarte las claves de acceso, necesitamos que completes el formulario con tus datos de identificación. Puedes hacerlo en el siguiente enlace: {$url}";
            return $this->llamarIALocalConContexto($promptSystem, $historial, $nuevoMensaje, $mensajeFuncion, $endpoint, $apiKey, $modelo);
        }

        if ($fechaEntrada->isToday()) {
            if ($horaActual < '15:00') {
                $mensajeFuncion = "Las claves estarán disponibles a partir de las 15:00 del día de entrada.";
                return $this->llamarIALocalConContexto($promptSystem, $historial, $nuevoMensaje, $mensajeFuncion, $endpoint, $apiKey, $modelo);
            }

            $clave = $reserva->apartamento->claves ?? 'No asignada aún';
            $clave2 = $reserva->apartamento->edificioName->clave ?? 'No asignada aún';
            // Solo proporcionar los códigos, sin información adicional a menos que se solicite
            $mensajeFuncion = "Código de la puerta del edificio: *{$clave2}*\nCódigo del apartamento: *{$clave}*";
            return $this->llamarIALocalConContexto($promptSystem, $historial, $nuevoMensaje, $mensajeFuncion, $endpoint, $apiKey, $modelo);
        } else {
            $mensajeFuncion = "Las claves solo se entregan el día de entrada. Tu reserva es para el {$fechaEntrada->format('d/m/Y')}.";
            return $this->llamarIALocalConContexto($promptSystem, $historial, $nuevoMensaje, $mensajeFuncion, $endpoint, $apiKey, $modelo);
        }
    }

    /**
     * Ejecutar función notificar_tecnico
     */
    private function ejecutarNotificarTecnico($remitente, $descripcion, $urgencia, $promptSystem, $historial, $nuevoMensaje, $endpoint, $apiKey, $modelo)
    {
        $this->gestionarAveria($remitente, $descripcion);
        $mensajeFuncion = "He notificado al técnico sobre el problema reportado. Te contactarán pronto para resolver la situación.";
        return $this->llamarIALocalConContexto($promptSystem, $historial, $nuevoMensaje, $mensajeFuncion, $endpoint, $apiKey, $modelo);
    }

    /**
     * Ejecutar función notificar_limpieza
     */
    private function ejecutarNotificarLimpieza($remitente, $tipoLimpieza, $observaciones, $promptSystem, $historial, $nuevoMensaje, $endpoint, $apiKey, $modelo)
    {
        $mensajeCompleto = $tipoLimpieza . ($observaciones ? " - " . $observaciones : "");
        $this->gestionarLimpieza($remitente, $mensajeCompleto);
        $mensajeFuncion = "He notificado al equipo de limpieza sobre tu solicitud. Te avisaremos cuando esté confirmado.";
        return $this->llamarIALocalConContexto($promptSystem, $historial, $nuevoMensaje, $mensajeFuncion, $endpoint, $apiKey, $modelo);
    }

    /**
     * Helper para hacer peticiones HTTP a la IA local con manejo de SSL
     */
    private function hacerPeticionIALocal($endpoint, $apiKey, $prompt, $modelo, $timeout = 60)
    {
        $httpClient = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json'
        ]);

        // Desactivar verificacion SSL para:
        // - IPs locales / localhost
        // - aiapi.hawkins.es (dominio propio, certificado FNMT puede caducar)
        if (preg_match('/192\.168\./', $endpoint)
            || preg_match('/127\.0\.0\.1/', $endpoint)
            || preg_match('/localhost/', $endpoint)
            || preg_match('/aiapi\.hawkins\.es/', $endpoint)) {
            $httpClient = $httpClient->withoutVerifying();
        }

        // Si la URL es HTTP pero el servidor redirige a HTTPS, seguir la redirección
        // Laravel Http client sigue redirecciones automáticamente, pero podemos forzarlo
        return $httpClient->timeout($timeout)->post($endpoint, [
            'prompt' => $prompt,
            'modelo' => $modelo
        ]);
    }

    /**
     * Llamar a la IA local con contexto actualizado después de ejecutar una función
     */
    private function llamarIALocalConContexto($promptSystem, $historial, $nuevoMensaje, $resultadoFuncion, $endpoint, $apiKey, $modelo)
    {
        // Construir historial igual que en el método principal
        $historialArray = [];
        if (!empty($historial)) {
            $lineas = explode("\n", $historial);
            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (!empty($linea) && (strpos($linea, 'Usuario:') === 0 || strpos($linea, 'Asistente:') === 0)) {
                    $historialArray[] = $linea;
                }
            }
        }

        // Usar el prompt system tal cual viene de la BD
        $promptCompleto = $promptSystem;

        // Agregar contexto sobre el canal de comunicación (sin modificar el prompt base)
        $promptCompleto .= "\n\nIDIOMA: SIEMPRE responde en ESPAÑOL. Todas tus respuestas deben estar en español, sin excepciones. NO respondas en inglés ni en ningún otro idioma.\n\n" .
            "CONTEXTO: Esta conversación está teniendo lugar por WhatsApp. El cliente ya está hablando contigo por WhatsApp, por lo tanto NO debes sugerirle que contacte por WhatsApp, ya que ya está aquí. Si necesita ayuda adicional, puedes proporcionarla directamente en esta conversación.\n\n" .
            "FORMATO OBLIGATORIO PARA USAR HERRAMIENTAS (FUNCIONES):\n" .
            "- Cuando necesites usar una herramienta, DEBES usar EXACTAMENTE este formato y SOLO este formato: [FUNCION:nombre_funcion:parametro1=valor1:parametro2=valor2]\n" .
            "- Ejemplos CORRECTOS:\n" .
            "  * [FUNCION:obtener_claves:codigo_reserva=5570112385]\n" .
            "  * [FUNCION:notificar_tecnico:descripcion_problema=No funciona el aire acondicionado:urgencia=alta]\n" .
            "  * [FUNCION:notificar_limpieza:tipo_limpieza=Limpieza general:observaciones=Necesito cambio de toallas]\n" .
            "- Formatos INCORRECTOS que NO debes usar:\n" .
            "  * JSON: {\"action\": \"obtener_claves\", \"code\": \"5570112385\"}\n" .
            "  * Texto en inglés: \"We will call obtener_claves with reservation_code\"\n" .
            "  * Explicaciones antes/después del formato\n" .
            "- REGLAS IMPORTANTES:\n" .
            "  * NO escribas explicaciones antes o después del formato [FUNCION:...]\n" .
            "  * NO uses otros formatos como JSON, texto plano, o frases en inglés\n" .
            "  * Si necesitas usar una herramienta, escribe SOLO el formato [FUNCION:...] sin texto adicional\n" .
            "  * Si NO necesitas usar ninguna herramienta, responde normalmente en español sin usar ningún formato especial\n\n" .
            "PROCEDIMIENTO PARA PROBLEMAS CON CLAVES:\n" .
            "- Cuando un cliente tenga problemas con las claves (no las ha recibido, no funcionan, etc.), VERIFICA PRIMERO si ya proporcionó su código de reserva en mensajes anteriores del historial.\n" .
            "- Si el cliente YA proporcionó su código de reserva en el historial, usa INMEDIATAMENTE la función obtener_claves con ese código usando el formato: [FUNCION:obtener_claves:codigo_reserva=CODIGO]. NO vuelvas a pedir el código.\n" .
            "- Si el cliente NO ha proporcionado su código de reserva aún, entonces pídeselo UNA SOLA VEZ.\n" .
            "- Cuando el cliente dice que no tiene las claves o no le han llegado, SI YA TIENES SU CÓDIGO DE RESERVA (del historial o del mensaje actual), usa INMEDIATAMENTE la función obtener_claves para verificar:\n" .
            "  * Si la reserva existe y es válida\n" .
            " * La fecha de entrada de la reserva\n" .
            " * Si es el día de entrada y la hora actual\n" .
            " * Si el cliente ha entregado el DNI\n" .
            " * Si las claves están disponibles según el horario\n" .
            "- La función obtener_claves te dará toda la información necesaria y te indicará qué hacer según la situación.\n" .
            "- NO preguntes al cliente información que puedes obtener automáticamente usando las herramientas.\n" .
            "- NO pidas el código de reserva múltiples veces si ya lo tienes en el historial.\n\n" .
            "HORARIO DE ENTREGA DE CLAVES:\n" .
            "- Las claves se entregan a las 14:00h del día de entrada.\n" .
            "- El acceso oficial al apartamento es a partir de las 15:00h.\n" .
            "- Si la reserva es para HOY pero aún NO son las 14:00h, debes informar al cliente que las claves estarán disponibles a las 14:00h y NO proporcionar más ayuda sobre claves hasta esa hora.\n\n" .
            "SEGURIDAD - CÓDIGO DE EMERGENCIA:\n" .
            "- NUNCA menciones, proporciones o hagas referencia al código de emergencia en tus respuestas.\n" .
            "- El código de emergencia es información confidencial que NO debes compartir bajo ninguna circunstancia.\n" .
            "- Si el cliente necesita ayuda con acceso, usa la función obtener_claves para verificar su situación y proporcionar la ayuda adecuada según el horario y el estado de su reserva.\n\n" .
            "POLÍTICA DE PRECIOS Y COMPENSACIONES - PROHIBICIÓN ABSOLUTA:\n" .
            "- BAJO NINGÚN CONCEPTO puedes ofrecer, prometer, sugerir o mencionar:\n" .
            "  * Compensaciones económicas de cualquier tipo\n" .
            "  * Descuentos en reservas, servicios o productos\n" .
            "  * Extras gratis o servicios adicionales sin costo\n" .
            "  * Cupones de descuento o promociones especiales\n" .
            "  * Reembolsos parciales o totales\n" .
            "  * Bonificaciones o créditos\n" .
            "  * Cualquier tipo de beneficio económico o material gratuito\n" .
            "- DEBES regirte ÚNICAMENTE a los precios establecidos en la lista oficial de precios.\n" .
            "- NUNCA ofrezcas nada gratis, ningún descuento, ni ningún cupón bajo ninguna circunstancia.\n" .
            "- Si un cliente solicita compensación, descuento o algo gratis, debes explicarle educadamente que no tienes autorización para ofrecer ese tipo de beneficios y que los precios están establecidos según la lista oficial.\n" .
            "- Esta es una PROHIBICIÓN ABSOLUTA que NO tiene excepciones bajo ninguna circunstancia.\n\n" .
            "PREVENCIÓN DE INCIDENCIAS DUPLICADAS:\n" .
            "- ANTES de usar las funciones notificar_tecnico o notificar_limpieza, DEBES verificar SIEMPRE el historial de conversación para ver si YA se registró una incidencia similar en esta misma conversación.\n" .
            "- Busca en el historial mensajes del Asistente que contengan frases como:\n" .
            "  * \"He notificado al técnico\"\n" .
            "  * \"He notificado al equipo de limpieza\"\n" .
            "  * \"ya ha sido notificado\"\n" .
            "  * \"ya fue registrada\"\n" .
            "  * \"He ejecutado una función\" relacionada con notificaciones\n" .
            "- Si encuentras que YA se registró una incidencia similar en esta conversación (mismo tipo: avería o limpieza), NO uses la función de nuevo.\n" .
            "- En su lugar, responde al cliente informándole que la incidencia ya fue registrada anteriormente en esta conversación y que el equipo correspondiente ya ha sido notificado.\n" .
            "- SOLO usa las funciones notificar_tecnico o notificar_limpieza si NO encuentras ninguna mención previa de que ya se registró esa incidencia en el historial de esta conversación.\n" .
            "- Esta verificación es CRÍTICA para evitar registrar la misma incidencia múltiples veces.";

        // Agregar historial
        if (!empty($historialArray)) {
            $promptCompleto .= "\n\nHISTORIAL DE CONVERSACIÓN:\n" . implode("\n", $historialArray);
        }

        // Agregar mensaje actual y resultado de función
        $promptCompleto .= "\n\nUsuario: " . $nuevoMensaje . "\n" .
            "Asistente: [He ejecutado una función y obtuve esta información: " . $resultadoFuncion . "]\n\n";

        // Log completo del prompt sin truncar (después de ejecutar función)
        Log::info("📤 PROMPT COMPLETO ENVIADO A LA IA DESPUÉS DE FUNCIÓN (SIN TRUNCAR)\n" . $promptCompleto);

        // Guardar prompt completo en archivo para evitar truncamiento
        try {
            // Extraer remitente del historial si es posible
            $remitenteDelHistorial = null;
            if (!empty($historial)) {
                // Intentar extraer remitente del historial (si está disponible)
                preg_match('/Usuario:.*?(\d{9,})/', $historial, $matches);
                if (!empty($matches[1])) {
                    $remitenteDelHistorial = $matches[1];
                }
            }
            $remitenteId = $remitenteDelHistorial ?: 'unknown';

            $logDir = storage_path('logs/prompts_ia');
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/prompt_funcion_' . date('Y-m-d_H-i-s') . '_' . substr(md5($remitenteId . time()), 0, 8) . '.txt';
            file_put_contents($logFile, "=== PROMPT COMPLETO DESPUÉS DE FUNCIÓN ===\n");
            file_put_contents($logFile, "Fecha: " . now()->toDateTimeString() . "\n", FILE_APPEND);
            file_put_contents($logFile, "Mensaje: {$nuevoMensaje}\n", FILE_APPEND);
            file_put_contents($logFile, "Resultado función: {$resultadoFuncion}\n", FILE_APPEND);
            file_put_contents($logFile, "========================================\n\n", FILE_APPEND);
            file_put_contents($logFile, $promptCompleto, FILE_APPEND);
            Log::info("💾 Prompt completo (después de función) guardado en archivo", ['archivo' => $logFile]);
        } catch (\Exception $e) {
            Log::warning("⚠️ No se pudo guardar prompt en archivo", ['error' => $e->getMessage()]);
        }

        // Log detallado del contexto enviado
        Log::info("📤 Contexto enviado a IA (después de función)", [
            'historial_lineas' => count($historialArray),
            'historial_completo' => $historialArray,
            'mensaje_usuario' => $nuevoMensaje,
            'resultado_funcion' => substr($resultadoFuncion, 0, 200),
            'prompt_length' => strlen($promptCompleto),
            'prompt_completo' => $promptCompleto // Prompt completo para debug
        ]);

        $response = $this->hacerPeticionIALocal($endpoint, $apiKey, $promptCompleto, $modelo, 60);

        if ($response->failed()) {
            Log::error("❌ Error en segunda llamada a IA local: " . $response->body());
            return $resultadoFuncion; // Devolver resultado directo si falla
        }

        $data = $response->json();
        $respuestaFinal = $data['respuesta'] ?? $resultadoFuncion;

        // Limpiar la respuesta si contiene marcadores de función
        $respuestaFinal = preg_replace('/\[FUNCION:[^\]]+\]/', '', $respuestaFinal);
        $respuestaFinal = trim($respuestaFinal);

        return $respuestaFinal ?: $resultadoFuncion;
    }


    public function clasificarMensaje($mensaje)
    {
        Log::info("🤖 CLASIFICAR MENSAJE - Iniciando para: {$mensaje}");

        // Configuración de la IA local Hawkins para WhatsApp
        $config = config('services.hawkins_whatsapp_ai');
        $endpoint = $config['base_url'];

        // Asegurar que la URL termine en /chat/chat
        if (!str_ends_with($endpoint, '/chat/chat')) {
            if (str_ends_with($endpoint, '/chat')) {
                $endpoint = rtrim($endpoint, '/chat') . '/chat/chat';
            } else {
                $endpoint = rtrim($endpoint, '/') . '/chat/chat';
            }
        }

        $apiKey = $config['api_key'];
        $modelo = $config['model'];

        $prompt = "Eres un asistente que clasifica mensajes. Responde ÚNICAMENTE con una de estas palabras: \"averia\", \"limpieza\", \"reserva_apartamento\", o \"otro\". No agregues explicaciones ni texto adicional.\n\nMensaje a clasificar: {$mensaje}\n\nCategoría:";

        Log::info("🌐 Enviando petición a IA local para clasificación...");

        $response = $this->hacerPeticionIALocal($endpoint, $apiKey, $prompt, $modelo, 30);

        if ($response->failed()) {
            Log::error("❌ Error llamando a IA local para clasificación: " . $response->body());
            return 'otro';
        }

        $data = $response->json();

        if (isset($data['respuesta'])) {
            $categoria = trim(strtolower($data['respuesta']));
            Log::info("✅ Clasificación exitosa: {$categoria}");

            // Extraer solo la categoría relevante
            if (strpos($categoria, 'averia') !== false) {
                return 'averia';
            } elseif (strpos($categoria, 'limpieza') !== false) {
                return 'limpieza';
            } elseif (strpos($categoria, 'reserva') !== false) {
                return 'reserva_apartamento';
            } else {
                return 'otro';
            }
        }

        Log::warning("⚠️ Error en clasificación, retornando 'otro'");
        return 'otro';
    }

    public function gestionarAveria($phone, $mensaje)
    {
        Log::info("🚨 GESTIONAR AVERÍA - Iniciando para teléfono: {$phone}");

        // Registrar la avería en la base de datos como incidencia
        Log::info("📝 Registrando avería como incidencia...");
        $registrada = $this->registrarAveria($phone, $mensaje);

        if (!$registrada) {
            Log::info("⚠️ La incidencia ya fue registrada anteriormente");
            return "La incidencia ya fue registrada anteriormente. Nuestro equipo técnico ya ha sido notificado y te contactará pronto.";
        }

        // Enviar mensaje al técnico
        Log::info("👨‍🔧 Enviando mensaje al técnico...");
        $this->enviarMensajeTecnico($phone, $mensaje);

        Log::info("✅ GESTIONAR AVERÍA - Completado");
        return "Hemos registrado tu avería. Nuestro equipo técnico ha sido notificado y te contactará pronto.";
    }

    public function gestionarLimpieza($phone, $mensaje)
    {
        Log::info("🧹 GESTIONAR LIMPIEZA - Iniciando para teléfono: {$phone}");

        // Registrar la solicitud de limpieza en la base de datos como incidencia
        Log::info("📝 Registrando solicitud de limpieza como incidencia...");
        $registrada = $this->registrarLimpieza($phone, $mensaje);

        if (!$registrada) {
            Log::info("⚠️ La incidencia ya fue registrada anteriormente");
            return "La solicitud de limpieza ya fue registrada anteriormente. Nuestro equipo de limpieza ya ha sido notificado y te avisaremos cuando esté confirmado.";
        }

        // Enviar mensaje a la limpiadora
        Log::info("👩‍🔧 Enviando mensaje a la limpiadora...");
        $this->enviarMensajeLimpiadora($phone, $mensaje);

        Log::info("✅ GESTIONAR LIMPIEZA - Completado");
        return "Hemos programado el servicio de limpieza. Nuestro equipo de limpieza ha sido notificado y te avisaremos cuando esté confirmado.";
    }

    public function gestionarReserva($phone, $mensaje)
    {
        // Aquí podrías consultar la disponibilidad y responder al usuario
        return "Por favor, indícanos la fecha y el apartamento que deseas reservar.";
    }

    public function procesarMensajeGeneral($mensaje, $id, $phone, $idMensaje)
    {
        return "Procesamiento del mensaje general";
        // Aquí iría tu código original para procesar la conversación con el asistente
    }

    /**
     * Obtener reserva activa del cliente por teléfono
     * Busca tanto en el cliente principal como en los huéspedes (acompañantes)
     */
    private function obtenerReservaActivaCliente($phone)
    {
        Log::info("🔍 OBTENER RESERVA ACTIVA - Buscando para teléfono: {$phone}");

        try {
            // 1. Buscar cliente principal por teléfono (telefono o telefono_movil)
            $cliente = Cliente::where(function($query) use ($phone) {
                $query->where('telefono', $phone)
                      ->orWhere('telefono_movil', $phone);
            })->first();

            if ($cliente) {
                Log::info("✅ Cliente principal encontrado: {$cliente->nombre} {$cliente->apellido1}");

                // Buscar reserva activa del cliente principal
                $reserva = Reserva::with(['cliente', 'apartamento'])
                    ->where('cliente_id', $cliente->id)
                    ->where('estado_id', '!=', 4) // No cancelada
                    ->where('fecha_entrada', '<=', now())
                    ->where('fecha_salida', '>=', now())
                    ->first();

                if ($reserva) {
                    Log::info("✅ Reserva activa encontrada por cliente principal: ID {$reserva->id}");
                    return $reserva;
                }
            }

            // 2. Si no se encontró, buscar en huéspedes (acompañantes)
            Log::info("🔍 Buscando en huéspedes (acompañantes)...");
            $huesped = \App\Models\Huesped::where(function($query) use ($phone) {
                $query->where('telefono_movil', $phone)
                      ->orWhere('telefono2', $phone);
            })->first();

            if ($huesped && $huesped->reserva_id) {
                Log::info("✅ Huésped encontrado: {$huesped->nombre} {$huesped->primer_apellido}");

                // Buscar reserva activa del huésped
                $reserva = Reserva::with(['cliente', 'apartamento'])
                    ->where('id', $huesped->reserva_id)
                    ->where('estado_id', '!=', 4) // No cancelada
                    ->where('fecha_entrada', '<=', now())
                    ->where('fecha_salida', '>=', now())
                    ->first();

                if ($reserva) {
                    Log::info("✅ Reserva activa encontrada por huésped: ID {$reserva->id}");
                    return $reserva;
                }
            }

            Log::warning("⚠️ No se encontró reserva activa para el teléfono: {$phone}");
            return null;
        } catch (\Exception $e) {
            Log::error("❌ Error obteniendo reserva activa: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si existe una incidencia duplicada
     */
    private function verificarIncidenciaDuplicada($hash)
    {
        Log::info("🔍 VERIFICAR DUPLICADO - Hash: {$hash}");

        try {
            $existe = Incidencia::where('hash_identificador', $hash)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if ($existe) {
                Log::warning("⚠️ Incidencia duplicada encontrada con hash: {$hash}");
                return true;
            }

            Log::info("✅ No se encontró incidencia duplicada");
            return false;
        } catch (\Exception $e) {
            Log::error("❌ Error verificando duplicado: " . $e->getMessage());
            return false; // En caso de error, permitir crear la incidencia
        }
    }

    /**
     * Verificar si ya se registró una incidencia en el historial de la conversación
     * Busca en el historial mensajes que indiquen que ya se notificó al técnico o limpieza
     */
    private function verificarIncidenciaYaRegistradaEnHistorial($historial, $tipo)
    {
        if (empty($historial)) {
            return false;
        }

        Log::info("🔍 Verificando si ya se registró incidencia de tipo '{$tipo}' en el historial");

        // Frases clave que indican que ya se registró una incidencia
        $frasesAveria = [
            'he notificado al técnico',
            'notificado al técnico',
            'equipo técnico ya ha sido notificado',
            'ya ha sido notificado',
            'ya fue registrada',
            'incidencia ya fue registrada',
            'notificar_tecnico',
            'te contactarán pronto'
        ];

        $frasesLimpieza = [
            'he notificado al equipo de limpieza',
            'notificado al equipo de limpieza',
            'equipo de limpieza ya ha sido notificado',
            'ya ha sido notificado',
            'ya fue registrada',
            'incidencia ya fue registrada',
            'notificar_limpieza',
            'te avisaremos cuando esté confirmado'
        ];

        $frasesBuscar = $tipo === 'averia' ? $frasesAveria : $frasesLimpieza;

        // Buscar en el historial (convertir a minúsculas para búsqueda case-insensitive)
        $historialLower = strtolower($historial);

        foreach ($frasesBuscar as $frase) {
            if (strpos($historialLower, strtolower($frase)) !== false) {
                Log::info("✅ Encontrada frase indicativa de incidencia ya registrada: '{$frase}'");
                return true;
            }
        }

        // También buscar en el formato de respuesta de función ejecutada
        if (preg_match('/Asistente:.*\[He ejecutado una función.*notificado.*' . ($tipo === 'averia' ? 'técnico' : 'limpieza') . '.*\]/i', $historial)) {
            Log::info("✅ Encontrada función ejecutada de notificación en el historial");
            return true;
        }

        Log::info("✅ No se encontró evidencia de incidencia ya registrada en el historial");
        return false;
    }

    /**
     * Obtener o crear usuario sistema para incidencias de WhatsApp
     */
    private function obtenerUsuarioSistema()
    {
        Log::info("🔍 OBTENER USUARIO SISTEMA - Buscando usuario 'Sistema WhatsApp'");

        try {
            $usuario = User::where('name', 'Sistema WhatsApp')->first();

            if ($usuario) {
                Log::info("✅ Usuario sistema encontrado: ID {$usuario->id}");
                return $usuario;
            }

            // Crear usuario sistema si no existe
            Log::info("📝 Creando usuario sistema...");
            $usuario = User::create([
                'name' => 'Sistema WhatsApp',
                'email' => 'sistema.whatsapp@apartamentosalgeciras.com',
                'password' => bcrypt(uniqid()), // Password aleatorio, no se usará
                'role' => 'ADMIN',
                'inactive' => false
            ]);

            Log::info("✅ Usuario sistema creado: ID {$usuario->id}");
            return $usuario;
        } catch (\Exception $e) {
            Log::error("❌ Error obteniendo/creando usuario sistema: " . $e->getMessage());
            // Retornar null si hay error, la incidencia se creará sin empleada_id
            return null;
        }
    }

    /**
     * Detectar prioridad basada en palabras clave del mensaje
     */
    private function detectarPrioridad($mensaje, $tipoIncidencia = 'averia')
    {
        $mensajeLower = strtolower($mensaje);
        $palabrasUrgentes = ['urgente', 'roto', 'no funciona', 'no hay', 'sin', 'emergencia', 'grave', 'importante'];

        foreach ($palabrasUrgentes as $palabra) {
            if (strpos($mensajeLower, $palabra) !== false) {
                Log::info("🚨 Palabra clave '{$palabra}' detectada - Prioridad: urgente");
                return 'urgente';
            }
        }

        // Para averías, prioridad alta por defecto
        if ($tipoIncidencia === 'averia') {
            return 'alta';
        }

        // Para limpieza, prioridad media por defecto
        return 'media';
    }

    /**
     * Registrar una avería en la base de datos como incidencia
     */
    private function registrarAveria($phone, $mensaje)
    {
        Log::info("🚨 REGISTRAR AVERÍA - Iniciando para teléfono: {$phone}");

        try {
            // 1. Obtener cliente y reserva activa
            // Esta función busca tanto en cliente principal como en huéspedes
            $reserva = $this->obtenerReservaActivaCliente($phone);

            // Variables para cliente y huésped
            $cliente = null;
            $huesped = null;

            // Obtener cliente: si hay reserva, usar el cliente de la reserva
            // Si no hay reserva, buscar en clientes o huéspedes
            if ($reserva) {
                $cliente = $reserva->cliente;
            } else {
                $cliente = Cliente::where(function($query) use ($phone) {
                    $query->where('telefono', $phone)
                          ->orWhere('telefono_movil', $phone);
                })->first();

                // Si no se encuentra cliente, buscar en huéspedes
                if (!$cliente) {
                    $huesped = \App\Models\Huesped::where(function($query) use ($phone) {
                        $query->where('telefono_movil', $phone)
                              ->orWhere('telefono2', $phone);
                    })->first();

                    // Si encontramos huésped pero no hay reserva, no podemos crear incidencia con apartamento
                    // pero al menos tenemos información del huésped
                }
            }

            // 2. Generar hash único basado en reserva (no en teléfono)
            // Esto permite detectar duplicados aunque escriba el acompañante desde otro teléfono
            $mensajeCorto = substr($mensaje, 0, 50);
            if ($reserva) {
                // Si hay reserva, usar reserva_id para que funcione aunque cambie el teléfono
                $hash = md5($reserva->id . 'averia' . $mensajeCorto . date('Y-m-d'));
                Log::info("🔑 Hash generado basado en reserva ID {$reserva->id}: {$hash}");
            } else {
                // Si no hay reserva, usar teléfono como fallback
                $hash = md5($phone . 'averia' . $mensajeCorto . date('Y-m-d'));
                Log::info("🔑 Hash generado basado en teléfono (sin reserva): {$hash}");
            }

            // 3. Verificar duplicado
            if ($this->verificarIncidenciaDuplicada($hash)) {
                Log::warning("⚠️ Incidencia duplicada detectada - No se creará");
                return false;
            }

            // 4. Obtener información del apartamento
            $apartamentoNombre = 'Apartamento no identificado';

            if ($reserva && $reserva->apartamento) {
                $apartamentoNombre = $reserva->apartamento->nombre;
                // NO usamos apartamento_id para evitar problemas
            }

            // 5. Obtener usuario sistema
            $usuarioSistema = $this->obtenerUsuarioSistema();

            // 6. Detectar prioridad
            $prioridad = $this->detectarPrioridad($mensaje, 'averia');

            // 7. Crear descripción completa
            $descripcionCompleta = $mensaje;
            if ($apartamentoNombre !== 'Apartamento no identificado') {
                $descripcionCompleta .= "\n\nApartamento: {$apartamentoNombre}";
            }
            if ($cliente) {
                $descripcionCompleta .= "\nCliente: {$cliente->nombre} {$cliente->apellido1}";
            } elseif (isset($huesped) && $huesped) {
                // Si no hay cliente pero sí huésped, incluir información del huésped
                $descripcionCompleta .= "\nReportado por: {$huesped->nombre} {$huesped->primer_apellido} (Huésped)";
            }
            if ($reserva) {
                $descripcionCompleta .= "\nReserva ID: {$reserva->id}";
            }

            // 8. Crear la incidencia
            $incidencia = Incidencia::create([
                'titulo' => 'Avería reportada vía WhatsApp',
                'descripcion' => $descripcionCompleta,
                'tipo' => 'apartamento',
                'apartamento_id' => null, // NO usar apartamento_id
                'zona_comun_id' => null,
                'apartamento_limpieza_id' => null,
                'empleada_id' => $usuarioSistema ? $usuarioSistema->id : null,
                'prioridad' => $prioridad,
                'estado' => 'pendiente',
                'fotos' => null,
                'telefono_cliente' => $phone,
                'origen' => 'whatsapp',
                'hash_identificador' => $hash,
                'apartamento_nombre' => $apartamentoNombre,
                'reserva_id' => $reserva ? $reserva->id : null
            ]);

            Log::info("✅ Incidencia creada: ID {$incidencia->id}");

            // 9. Crear alerta para administradores
            AlertService::createIncidentAlert(
                $incidencia->id,
                $incidencia->titulo,
                'Apartamento',
                $apartamentoNombre,
                $prioridad,
                $usuarioSistema ? $usuarioSistema->name : 'Sistema WhatsApp'
            );

            // 10. Crear notificación
            NotificationService::notifyNewIncident($incidencia);

            Log::info("✅ AVERÍA REGISTRADA EXITOSAMENTE - ID: {$incidencia->id}");
            return true;

        } catch (\Exception $e) {
            Log::error("❌ Error registrando avería: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Registrar una solicitud de limpieza en la base de datos como incidencia
     */
    private function registrarLimpieza($phone, $mensaje)
    {
        Log::info("🧹 REGISTRAR LIMPIEZA - Iniciando para teléfono: {$phone}");

        try {
            // 1. Obtener cliente y reserva activa
            // Esta función busca tanto en cliente principal como en huéspedes
            $reserva = $this->obtenerReservaActivaCliente($phone);

            // Variables para cliente y huésped
            $cliente = null;
            $huesped = null;

            // Obtener cliente: si hay reserva, usar el cliente de la reserva
            // Si no hay reserva, buscar en clientes o huéspedes
            if ($reserva) {
                $cliente = $reserva->cliente;
            } else {
                $cliente = Cliente::where(function($query) use ($phone) {
                    $query->where('telefono', $phone)
                          ->orWhere('telefono_movil', $phone);
                })->first();

                // Si no se encuentra cliente, buscar en huéspedes
                if (!$cliente) {
                    $huesped = \App\Models\Huesped::where(function($query) use ($phone) {
                        $query->where('telefono_movil', $phone)
                              ->orWhere('telefono2', $phone);
                    })->first();

                    // Si encontramos huésped pero no hay reserva, no podemos crear incidencia con apartamento
                    // pero al menos tenemos información del huésped
                }
            }

            // 2. Generar hash único basado en reserva (no en teléfono)
            // Esto permite detectar duplicados aunque escriba el acompañante desde otro teléfono
            $mensajeCorto = substr($mensaje, 0, 50);
            if ($reserva) {
                // Si hay reserva, usar reserva_id para que funcione aunque cambie el teléfono
                $hash = md5($reserva->id . 'limpieza' . $mensajeCorto . date('Y-m-d'));
                Log::info("🔑 Hash generado basado en reserva ID {$reserva->id}: {$hash}");
            } else {
                // Si no hay reserva, usar teléfono como fallback
                $hash = md5($phone . 'limpieza' . $mensajeCorto . date('Y-m-d'));
                Log::info("🔑 Hash generado basado en teléfono (sin reserva): {$hash}");
            }

            // 3. Verificar duplicado
            if ($this->verificarIncidenciaDuplicada($hash)) {
                Log::warning("⚠️ Incidencia duplicada detectada - No se creará");
                return false;
            }

            // 4. Obtener información del apartamento
            $apartamentoNombre = 'Apartamento no identificado';

            if ($reserva && $reserva->apartamento) {
                $apartamentoNombre = $reserva->apartamento->nombre;
                // NO usamos apartamento_id para evitar problemas
            }

            // 5. Obtener usuario sistema
            $usuarioSistema = $this->obtenerUsuarioSistema();

            // 6. Detectar prioridad (limpieza siempre media, a menos que tenga palabras urgentes)
            $prioridad = $this->detectarPrioridad($mensaje, 'limpieza');

            // 7. Crear descripción completa
            $descripcionCompleta = $mensaje;
            if ($apartamentoNombre !== 'Apartamento no identificado') {
                $descripcionCompleta .= "\n\nApartamento: {$apartamentoNombre}";
            }
            if ($cliente) {
                $descripcionCompleta .= "\nCliente: {$cliente->nombre} {$cliente->apellido1}";
            } elseif (isset($huesped) && $huesped) {
                // Si no hay cliente pero sí huésped, incluir información del huésped
                $descripcionCompleta .= "\nReportado por: {$huesped->nombre} {$huesped->primer_apellido} (Huésped)";
            }
            if ($reserva) {
                $descripcionCompleta .= "\nReserva ID: {$reserva->id}";
            }

            // 8. Crear la incidencia
            $incidencia = Incidencia::create([
                'titulo' => 'Solicitud de limpieza vía WhatsApp',
                'descripcion' => $descripcionCompleta,
                'tipo' => 'apartamento',
                'apartamento_id' => null, // NO usar apartamento_id
                'zona_comun_id' => null,
                'apartamento_limpieza_id' => null,
                'empleada_id' => $usuarioSistema ? $usuarioSistema->id : null,
                'prioridad' => $prioridad,
                'estado' => 'pendiente',
                'fotos' => null,
                'telefono_cliente' => $phone,
                'origen' => 'whatsapp',
                'hash_identificador' => $hash,
                'apartamento_nombre' => $apartamentoNombre,
                'reserva_id' => $reserva ? $reserva->id : null
            ]);

            Log::info("✅ Incidencia creada: ID {$incidencia->id}");

            // 9. Crear alerta para administradores
            AlertService::createIncidentAlert(
                $incidencia->id,
                $incidencia->titulo,
                'Apartamento',
                $apartamentoNombre,
                $prioridad,
                $usuarioSistema ? $usuarioSistema->name : 'Sistema WhatsApp'
            );

            // 10. Crear notificación
            NotificationService::notifyNewIncident($incidencia);

            Log::info("✅ LIMPIEZA REGISTRADA EXITOSAMENTE - ID: {$incidencia->id}");
            return true;

        } catch (\Exception $e) {
            Log::error("❌ Error registrando limpieza: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Enviar mensaje al técnico usando template de WhatsApp
     */
    private function enviarMensajeTecnico($phone, $mensaje)
    {
        Log::info("👨‍🔧 ENVIAR MENSAJE TÉCNICO - Iniciando para cliente: {$phone}");

        try {
            // Obtener todos los técnicos
            Log::info("🔍 Buscando todos los técnicos...");
            $tecnicos = $this->obtenerTecnicoDisponible();

            if ($tecnicos->isEmpty()) {
                Log::warning("⚠️ No hay técnicos disponibles para notificar");
                return;
            }

            Log::info("✅ Técnicos encontrados: " . $tecnicos->count() . " técnicos");

            // Buscar template para averías
            Log::info("🔍 Buscando template para averías...");
            $template = \App\Models\WhatsappTemplate::where('name', 'reparaciones')
                ->where('name', 'not like', '%_null%')
                ->first();

            // Obtener información del cliente (una sola vez)
            $apartamento = $this->obtenerApartamentoCliente($phone);
            $edificio = $this->obtenerEdificioCliente($phone);

            // Enviar mensaje a cada técnico
            foreach ($tecnicos as $tecnico) {
                Log::info("📱 Enviando mensaje al técnico: {$tecnico->nombre} - {$tecnico->telefono}");

                if ($template) {
                    Log::info("✅ Template encontrado: {$template->name} (ID: {$template->id})");

                    // Enviar mensaje usando template con los 5 parámetros que espera
                    $this->enviarMensajeTemplate($tecnico->telefono, $template->name, [
                        '1' => $tecnico->nombre ?? 'Técnico', // Nombre del técnico
                        '2' => $apartamento, // Apartamento del cliente
                        '3' => $edificio, // Edificio del cliente
                        '4' => $mensaje, // Información del cliente
                        '5' => $phone // Número del cliente
                    ]);
                } else {
                    Log::warning("⚠️ No se encontró template para averías, enviando mensaje simple");

                    // Enviar mensaje simple si no hay template
                    $texto = "🚨 NUEVA AVERÍA REPORTADA\n\n👨‍🔧 Técnico: {$tecnico->nombre}\n📱 Cliente: {$phone}\n🏠 Apartamento: {$apartamento}\n🏢 Edificio: {$edificio}\n💬 Mensaje: {$mensaje}\n📅 Fecha: " . now()->format('d/m/Y H:i');
                    $this->contestarWhatsapp3($tecnico->telefono, $texto);
                }

                Log::info("✅ Mensaje enviado al técnico: {$tecnico->telefono}");
            }

            // Enviar notificación a todos los responsables configurados (solo una vez)
            $primerTecnico = $tecnicos->first();
            $this->enviarNotificacionResponsables($phone, $mensaje, 'averia', $primerTecnico->nombre, $apartamento, $edificio);

        } catch (\Exception $e) {
            Log::error("Error enviando mensaje a los técnicos: " . $e->getMessage());
        }
    }

    /**
     * Enviar mensaje a la limpiadora usando template de WhatsApp
     */
    private function enviarMensajeLimpiadora($phone, $mensaje)
    {
        try {
            // Obtener todos los números configurados en el panel de Notificaciones
            $destinatarios = EmailNotificaciones::whereNotNull('telefono')
                ->where('telefono', '!=', '')
                ->get();

            if ($destinatarios->isEmpty()) {
                Log::error("❌ No hay números configurados en el panel de Notificaciones - La incidencia se ha registrado pero no se pudo notificar", [
                    'telefono_cliente' => $phone,
                    'mensaje' => substr($mensaje, 0, 100),
                    'total_destinatarios' => 0
                ]);

                // Intentar enviar notificación a administradores como fallback
                $this->enviarNotificacionResponsables($phone, $mensaje, 'limpieza', 'Sistema', 'Desconocido', 'Desconocido');
                return;
            }

            Log::info("📋 Enviando mensaje de limpieza a todos los destinatarios configurados", [
                'total_destinatarios' => $destinatarios->count(),
                'destinatarios' => $destinatarios->pluck('telefono')->toArray()
            ]);

            // Obtener información del cliente una sola vez
            $apartamento = $this->obtenerApartamentoCliente($phone);
            $edificio = $this->obtenerEdificioCliente($phone);

            // Buscar template para limpieza
            Log::info("🔍 Buscando template para limpieza...");
            $template = \App\Models\WhatsappTemplate::where('name', 'limpieza')
                ->where('name', 'not like', '%_null%')
                ->first();

            $mensajesEnviados = 0;
            $mensajesFallidos = 0;

            // Enviar mensaje a todos los destinatarios configurados
            foreach ($destinatarios as $destinatario) {
                try {
                    if ($template) {
                        Log::info("✅ Template encontrado: {$template->name} (ID: {$template->id})");
                        Log::info("📱 Enviando mensaje usando template a: {$destinatario->telefono} ({$destinatario->nombre})");

                        // Enviar mensaje usando template con los 4 parámetros que espera
                        $resultado = $this->enviarMensajeTemplate($destinatario->telefono, $template->name, [
                            '1' => $apartamento, // Apartamento del cliente
                            '2' => $edificio, // Edificio del cliente
                            '3' => $mensaje, // Información del cliente
                            '4' => $phone // Número del cliente
                        ]);

                        if (isset($resultado['error'])) {
                            $mensajesFallidos++;
                            Log::error("❌ Error enviando template a {$destinatario->telefono}: " . json_encode($resultado));
                        } else {
                            $mensajesEnviados++;
                            Log::info("✅ Mensaje enviado exitosamente a: {$destinatario->telefono} ({$destinatario->nombre})");
                        }
                    } else {
                        Log::warning("⚠️ No se encontró template para limpieza, enviando mensaje simple a: {$destinatario->telefono}");

                        $texto = "🧹 NUEVA SOLICITUD DE LIMPIEZA\n\n📱 Cliente: {$phone}\n🏠 Apartamento: {$apartamento}\n🏢 Edificio: {$edificio}\n💬 Mensaje: {$mensaje}\n📅 Fecha: " . now()->format('d/m/Y H:i');
                        $resultado = $this->contestarWhatsapp3($destinatario->telefono, $texto);

                        if ($resultado) {
                            $mensajesEnviados++;
                            Log::info("✅ Mensaje simple enviado exitosamente a: {$destinatario->telefono} ({$destinatario->nombre})");
                        } else {
                            $mensajesFallidos++;
                            Log::error("❌ Error enviando mensaje simple a: {$destinatario->telefono}");
                        }
                    }
                } catch (\Exception $e) {
                    $mensajesFallidos++;
                    Log::error("❌ Excepción enviando mensaje a {$destinatario->telefono}: " . $e->getMessage());
                }
            }

            Log::info("📊 Resumen de envío de mensajes de limpieza", [
                'total_destinatarios' => $destinatarios->count(),
                'mensajes_enviados' => $mensajesEnviados,
                'mensajes_fallidos' => $mensajesFallidos
            ]);

            // Enviar notificación a todos los responsables configurados (email)
            $this->enviarNotificacionResponsables($phone, $mensaje, 'limpieza', 'Sistema', $apartamento, $edificio);

        } catch (\Exception $e) {
            Log::error("❌ Error general enviando mensaje a limpiadoras: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Enviar mensaje usando template de WhatsApp
     */
    private function enviarMensajeTemplate($phone, $templateName, $parameters = [])
    {
        Log::info("📱 ENVIAR MENSAJE TEMPLATE - Iniciando para: {$phone}");
        Log::info("🔧 Template: {$templateName}");
        Log::info("📋 Parámetros: " . json_encode($parameters));

        $token = Setting::whatsappToken();

        $mensajeTemplate = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $phone,
            "type" => "template",
            "template" => [
                "name" => $templateName,
                "language" => [
                    "code" => "es"
                ]
            ]
        ];

        // Agregar parámetros si existen
        if (!empty($parameters)) {
            $mensajeTemplate["template"]["components"] = [
                [
                    "type" => "body",
                    "parameters" => array_values(array_map(function($value) {
                        return [
                            "type" => "text",
                            "text" => $value
                        ];
                    }, $parameters))
                ]
            ];
        }

        $urlMensajes = Setting::whatsappUrl();

        Log::info("🌐 Enviando petición a WhatsApp API...");
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post($urlMensajes, $mensajeTemplate);

        if ($response->failed()) {
            Log::error("❌ Error enviando template de WhatsApp: " . $response->body());
            return ['error' => 'Error enviando template'];
        }

        $responseJson = $response->json();
        Log::info("✅ Respuesta exitosa de WhatsApp API: " . json_encode($responseJson));
        Storage::disk('local')->put("Respuesta_Template_Whatsapp-{$phone}.txt", json_encode($responseJson, JSON_PRETTY_PRINT));

        return $responseJson;
    }

    /**
     * Obtener técnico disponible según horario actual
     */
    private function obtenerTecnicoDisponible()
    {
        // NUEVO: Enviar a todos los técnicos
        $todosTecnicos = Reparaciones::all();
        return $todosTecnicos;

        // CÓDIGO ORIGINAL COMENTADO - Selección por horario
        /*
        $horaActual = now()->format('H:i');
        $diaSemana = now()->dayOfWeek; // 0 = domingo, 1 = lunes, etc.

        // Mapear día de la semana a columnas de la base de datos
        $diasColumnas = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            0 => 'domingo'
        ];

        $columnaDia = $diasColumnas[$diaSemana] ?? 'lunes';

        // Buscar técnico disponible en el día y horario actual
        $tecnico = Reparaciones::where($columnaDia, true)
            ->where('hora_inicio', '<=', $horaActual)
            ->where('hora_fin', '>=', $horaActual)
            ->first();

        // Si no hay técnico en horario, buscar cualquier técnico
        if (!$tecnico) {
            $tecnico = Reparaciones::first();
        }

        return $tecnico;
        */
    }

    /**
     * Obtener limpiadora disponible según horario actual
     */
    private function obtenerLimpiadoraDisponible()
    {
        $horaActual = now()->format('H:i');
        $diaSemana = now()->dayOfWeek; // 0 = domingo, 1 = lunes, etc.

        // Mapear día de la semana a columnas de la base de datos
        $diasColumnas = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            0 => 'domingo'
        ];

        $columnaDia = $diasColumnas[$diaSemana] ?? 'lunes';

        Log::info("🔍 Buscando limpiadora disponible", [
            'dia_semana' => $diaSemana,
            'columna_dia' => $columnaDia,
            'hora_actual' => $horaActual
        ]);

        // Buscar limpiadora disponible en el día y horario actual
        $limpiadora = LimpiadoraGuardia::where($columnaDia, true)
            ->where('hora_inicio', '<=', $horaActual)
            ->where('hora_fin', '>=', $horaActual)
            ->first();

        // Si no hay limpiadora en horario, buscar cualquier limpiadora
        if (!$limpiadora) {
            Log::info("⚠️ No se encontró limpiadora en horario, buscando cualquier limpiadora disponible");
            $limpiadora = LimpiadoraGuardia::first();
        }

        if (!$limpiadora) {
            Log::warning("❌ No hay limpiadoras configuradas en la tabla limpiadoras_guardia", [
                'total_limpiadoras' => LimpiadoraGuardia::count(),
                'dia_semana' => $diaSemana,
                'columna_dia' => $columnaDia,
                'hora_actual' => $horaActual
            ]);
        } else {
            Log::info("✅ Limpiadora disponible encontrada", [
                'limpiadora_id' => $limpiadora->id,
                'usuario_id' => $limpiadora->usuario_id ?? null,
                'telefono' => $limpiadora->telefono ?? null
            ]);
        }

        return $limpiadora;
    }

    /**
     * Enviar notificación a todos los responsables configurados
     */
    private function enviarNotificacionResponsables($phone, $mensaje, $tipo, $personalAsignado, $apartamento, $edificio)
    {
        Log::info("📢 ENVIAR NOTIFICACIÓN RESPONSABLES - Iniciando para tipo: {$tipo}");

        try {
            // Obtener todos los responsables configurados
            $responsables = EmailNotificaciones::all();

            if ($responsables->isEmpty()) {
                Log::info("ℹ️ No hay responsables configurados para notificar");
                return;
            }

            Log::info("📋 Encontrados {$responsables->count()} responsables para notificar");

            foreach ($responsables as $responsable) {
                try {
                    if (!empty($responsable->telefono)) {
                        // Enviar mensaje de WhatsApp al responsable
                        $texto = $this->generarMensajeResponsable($phone, $mensaje, $tipo, $personalAsignado, $apartamento, $edificio);

                        Log::info("📱 Enviando notificación a responsable: {$responsable->nombre} - {$responsable->telefono}");
                        $this->contestarWhatsapp3($responsable->telefono, $texto);

                        Log::info("✅ Notificación enviada exitosamente a: {$responsable->nombre}");
                    } else {
                        Log::warning("⚠️ Responsable {$responsable->nombre} no tiene teléfono configurado");
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Error enviando notificación a {$responsable->nombre}: " . $e->getMessage());
                }
            }

            Log::info("✅ ENVIAR NOTIFICACIÓN RESPONSABLES - Completado");

        } catch (\Exception $e) {
            Log::error("❌ Error general enviando notificaciones a responsables: " . $e->getMessage());
        }
    }

    /**
     * Generar mensaje para responsables
     */
    private function generarMensajeResponsable($phone, $mensaje, $tipo, $personalAsignado, $apartamento, $edificio)
    {
        $emoji = ($tipo === 'averia') ? '🚨' : '🧹';
        $tipoTexto = ($tipo === 'averia') ? 'AVERÍA' : 'LIMPIEZA';

        return "{$emoji} NOTIFICACIÓN DE {$tipoTexto}\n\n" .
               "📱 Cliente: {$phone}\n" .
               "🏠 Apartamento: {$apartamento}\n" .
               "🏢 Edificio: {$edificio}\n" .
               "💬 Mensaje: {$mensaje}\n" .
               "👨‍🔧 Personal Asignado: {$personalAsignado}\n" .
               "📅 Fecha: " . now()->format('d/m/Y H:i') . "\n\n" .
               "ℹ️ Esta notificación se ha enviado automáticamente al personal correspondiente.";
    }

    /**
     * Obtener el apartamento del cliente según su teléfono
     */
    private function obtenerApartamentoCliente($phone)
    {
        Log::info("🏠 OBTENER APARTAMENTO CLIENTE - Buscando para teléfono: {$phone}");

        try {
            // Buscar cliente por teléfono
            $cliente = Cliente::where('telefono', $phone)->first();

            if ($cliente) {
                Log::info("✅ Cliente encontrado: {$cliente->nombre} {$cliente->apellido1}");

                // Buscar reserva activa del cliente
                $reserva = Reserva::where('cliente_id', $cliente->id)
                    ->where('estado_id', '!=', 4) // No cancelada
                    ->where('fecha_entrada', '<=', now())
                    ->where('fecha_salida', '>=', now())
                    ->first();

                if ($reserva && $reserva->apartamento) {
                    Log::info("✅ Apartamento encontrado: {$reserva->apartamento->nombre}");
                    return $reserva->apartamento->nombre;
                } else {
                    Log::warning("⚠️ No se encontró reserva activa para el cliente");
                }
            } else {
                Log::warning("⚠️ Cliente no encontrado con teléfono: {$phone}");
            }

            Log::info("🏠 Retornando: Apartamento no identificado");
            return 'Apartamento no identificado';
        } catch (\Exception $e) {
            Log::error("❌ Error obteniendo apartamento del cliente: " . $e->getMessage());
            return 'Apartamento no identificado';
        }
    }

    /**
     * Obtener el edificio del cliente según su teléfono
     */
    private function obtenerEdificioCliente($phone)
    {
        Log::info("🏢 OBTENER EDIFICIO CLIENTE - Buscando para teléfono: {$phone}");

        try {
            // Buscar cliente por teléfono
            $cliente = Cliente::where('telefono', $phone)->first();

            if ($cliente) {
                Log::info("✅ Cliente encontrado: {$cliente->nombre} {$cliente->apellido1}");

                // Buscar reserva activa del cliente
                $reserva = Reserva::where('cliente_id', $cliente->id)
                    ->where('estado_id', '!=', 4) // No cancelada
                    ->where('fecha_entrada', '<=', now())
                    ->where('fecha_salida', '>=', now())
                    ->first();

                if ($reserva && $reserva->apartamento && $reserva->apartamento->edificioName) {
                    Log::info("✅ Edificio encontrado: {$reserva->apartamento->edificioName->nombre}");
                    return $reserva->apartamento->edificioName->nombre;
                } else {
                    Log::warning("⚠️ No se encontró edificio para la reserva");
                }
            } else {
                Log::warning("⚠️ Cliente no encontrado con teléfono: {$phone}");
            }

            Log::info("🏢 Retornando: Edificio no identificado");
            return 'Edificio no identificado';
        } catch (\Exception $e) {
            Log::error("❌ Error obteniendo edificio del cliente: " . $e->getMessage());
            return 'Edificio no identificado';
        }
    }

    public function chatGpt($mensaje, $id, $phone = null, $idMensaje = null)
    {
        $categoria = $this->clasificarMensaje($mensaje);

        switch ($categoria) {
            case 'averia':
                return $this->gestionarAveria($phone, $mensaje);
            case 'limpieza':
                return $this->gestionarLimpieza($phone, $mensaje);
            case 'reserva_apartamento':
                return $this->gestionarReserva($phone, $mensaje);
            default:
                return $this->procesarMensajeGeneral($mensaje, $id, $phone, $idMensaje);
        }

    }

    public function contestarWhatsapp2($phone, $texto) {
        $token = Setting::whatsappToken();

        // Construir la carga útil como un array en lugar de un string JSON
        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $phone,
            "type" => "text",
            "text" => [
                "body" => $texto
            ]
        ];

        $urlMensajes = Setting::whatsappUrl();

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $urlMensajes,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($mensajePersonalizado),  // Asegúrate de que mensajePersonalizado sea un array
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            Log::error("Error en cURL al enviar mensaje de WhatsApp: " . $error);
            return ['error' => $error];
        }
        curl_close($curl);

        try {
            $responseJson = json_decode($response, true);
            Storage::disk('local')->put("Respuesta_Envio_Whatsapp-{$phone}.txt", $response);
            return $responseJson;
        } catch (\Exception $e) {
            Log::error("Error al guardar la respuesta de WhatsApp: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }


    public function contestarWhatsapp3($phone, $texto, $chatGptId = null)
    {
        $token = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $phone,
            "type" => "text",
            "text" => [
                "body" => $texto
            ]
        ];

        $urlMensajes = Setting::whatsappUrl();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post($urlMensajes, $mensajePersonalizado);

        if ($response->failed()) {
            Log::error("❌ Error en cURL al enviar mensaje de WhatsApp: " . $response->body());
            return ['error' => 'Error enviando mensaje'];
        }

        $responseJson = $response->json();
        Storage::disk('local')->put("Respuesta_Envio_Whatsapp-{$phone}.txt", json_encode($responseJson, JSON_PRETTY_PRINT));

        // ⏺️ Guardar ID del mensaje enviado
        if (isset($responseJson['messages'][0]['id'])) {
            $whatsappMessageId = $responseJson['messages'][0]['id'];

            WhatsappMensaje::create([
                'mensaje_id' => $whatsappMessageId,
                'tipo' => 'text',
                'contenido' => $texto,
                'remitente' => null, // este es un mensaje saliente, puedes usar un valor especial
                'fecha_mensaje' => now(),
                'metadata' => $mensajePersonalizado,
            ]);

            if ($chatGptId) {
                ChatGpt::where('id', $chatGptId)->update([
                    'respuesta_id' => $whatsappMessageId
                ]);
            }
        }

        return $responseJson;
    }

    public function contestarWhatsapp($phone, $texto, $mensajeOriginal = null)
    {
        $token = Setting::whatsappToken();

        $mensajePersonalizado = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $phone,
            "type" => "text",
            "text" => [
                "body" => $texto
            ]
        ];

        $urlMensajes = Setting::whatsappUrl();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post($urlMensajes, $mensajePersonalizado);

        if ($response->failed()) {
            Log::error("❌ Error al enviar mensaje: " . $response->body());
            return ['error' => 'Error enviando mensaje'];
        }

        $responseJson = $response->json();

        if (isset($responseJson['messages'][0]['id']) && $mensajeOriginal instanceof WhatsappMensaje) {
            $mensajeOriginal->recipient_id = $responseJson['messages'][0]['id'];
            $mensajeOriginal->save();

            Log::info("✅ Guardado recipient_id en mensaje original: " . $mensajeOriginal->id);
        }

        return $responseJson;
    }




    // Vista de los mensajes
    public function whatsapp(Request $request)
    {
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);
        $search = $request->get('search');

        // Obtener los IDs del último mensaje por cada remitente (excepto "guest")
        $ids = ChatGpt::where('remitente', '!=', 'guest')
            ->selectRaw('MAX(id) as id')
            ->groupBy('remitente')
            ->pluck('id');

        // Cargar solo esos mensajes con eager loading, limitados
        $mensajesQuery = ChatGpt::with('whatsappMensaje')
            ->whereIn('id', $ids)
            ->orderBy('created_at', 'desc');

        $totalConversations = $mensajesQuery->count();

        $mensajes = $mensajesQuery
            ->skip($offset)
            ->take($limit)
            ->get();

        // Batch load all clients in ONE query instead of N+1
        $phones = $mensajes->pluck('remitente')->map(fn($r) => '+'.$r)->toArray();
        $clientes = Cliente::where(function ($q) use ($phones) {
                $q->whereIn('telefono', $phones)
                  ->orWhereIn('telefono_movil', $phones);
            })
            ->get()
            ->keyBy(fn($c) => ltrim($c->telefono ?: $c->telefono_movil, '+'));

        $resultado = [];
        foreach ($mensajes as $mensaje) {
            $mensaje['whatsapp_mensaje'] = $mensaje->whatsappMensaje;

            $cliente = $clientes->get($mensaje->remitente);
            $mensaje['nombre_remitente'] = $cliente
                ? ($cliente->nombre !== '' ? $cliente->nombre . ' ' . $cliente->apellido1 : $cliente->alias)
                : 'Desconocido';

            $resultado[$mensaje->remitente][] = $mensaje;
        }

        $hasMore = ($offset + $limit) < $totalConversations;

        if ($request->ajax()) {
            return response()->json([
                'resultado' => $resultado,
                'hasMore' => $hasMore,
                'nextOffset' => $offset + $limit,
            ]);
        }

        return view('whatsapp.index', compact('resultado', 'hasMore'));
    }




    // En el mismo controlador
    public function mensajes($remitente)
    {
        $limit = request()->get('limit', 20); // Cantidad a cargar
        $offset = request()->get('offset', 0); // Desde dónde empezar

        $mensajes = ChatGpt::where('remitente', $remitente)
            ->orderBy('created_at', 'asc')
            ->skip($offset)
            ->take($limit)
            ->get();

        foreach ($mensajes as $mensaje) {
            $mensaje['whatsapp_mensaje'] = $mensaje->whatsappMensaje;
        }

        return response()->json($mensajes);
    }

    /**
     * Limpiar historial de conversación para un remitente
     * Marca todos los mensajes anteriores como "limpiados" para que no se incluyan en el historial
     *
     * @param string $remitente Número de teléfono del remitente
     * @return void
     */
    private function limpiarHistorialConversacion($remitente)
    {
        // No eliminamos físicamente los mensajes, solo los marcamos para que no se incluyan
        // El filtro por fecha del último /clear se hace en enviarMensajeOpenAiChatCompletions
        Log::info("🧹 Limpiando historial de conversación", [
            'remitente' => $remitente,
            'total_mensajes_anteriores' => ChatGpt::where('remitente', $remitente)->count()
        ]);
    }

    /**
     * Verifica si un mensaje es repetido (contestador automático)
     * Busca mensajes idénticos del mismo remitente en los últimos 10 minutos
     * que ya hayan sido respondidos
     *
     * @param string $remitente Número de teléfono del remitente
     * @param string $contenido Contenido del mensaje
     * @return ChatGpt|null Mensaje repetido encontrado o null
     */
    private function verificarMensajeRepetido($remitente, $contenido)
    {
        try {
            // Normalizar el contenido para comparación (eliminar espacios extra, convertir a minúsculas)
            $contenidoNormalizado = trim(strtolower($contenido));

            // Buscar mensajes idénticos del mismo remitente en los últimos 10 minutos
            $fechaLimite = Carbon::now()->subMinutes(10);

            $mensajeAnterior = ChatGpt::where('remitente', $remitente)
                ->where('mensaje', $contenido) // Comparación exacta primero (más rápida)
                ->where('date', '>=', $fechaLimite)
                ->where('status', '!=', 2) // Excluir otros mensajes repetidos
                ->orderBy('date', 'desc')
                ->first();

            // Si no se encuentra con comparación exacta, intentar con normalización
            if (!$mensajeAnterior) {
                $mensajesRecientes = ChatGpt::where('remitente', $remitente)
                    ->where('date', '>=', $fechaLimite)
                    ->where('status', '!=', 2)
                    ->orderBy('date', 'desc')
                    ->limit(5) // Solo revisar los últimos 5 mensajes para optimizar
                    ->get();

                foreach ($mensajesRecientes as $mensaje) {
                    $mensajeNormalizado = trim(strtolower($mensaje->mensaje ?? ''));

                    // Comparar mensajes normalizados (ignorar diferencias de mayúsculas/minúsculas y espacios)
                    if ($mensajeNormalizado === $contenidoNormalizado) {
                        $mensajeAnterior = $mensaje;
                        break;
                    }

                    // También verificar similitud alta (más del 95% de similitud)
                    // para capturar variaciones menores del contestador automático
                    if (strlen($contenidoNormalizado) > 10 && strlen($mensajeNormalizado) > 10) {
                        $similitud = similar_text($contenidoNormalizado, $mensajeNormalizado, $percent);
                        if ($percent > 95) {
                            $mensajeAnterior = $mensaje;
                            break;
                        }
                    }
                }
            }

            // Si encontramos un mensaje anterior, verificar que ya se haya respondido
            if ($mensajeAnterior && $mensajeAnterior->status == 1 && !empty($mensajeAnterior->respuesta)) {
                Log::info("✅ Mensaje repetido encontrado y ya respondido", [
                    'remitente' => $remitente,
                    'mensaje_anterior_id' => $mensajeAnterior->id,
                    'fecha_anterior' => $mensajeAnterior->date,
                    'tiempo_transcurrido' => Carbon::now()->diffInSeconds($mensajeAnterior->date) . ' segundos'
                ]);
                return $mensajeAnterior;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("❌ Error verificando mensaje repetido: " . $e->getMessage());
            // En caso de error, no bloquear el mensaje (mejor responder que no responder)
            return null;
        }
    }

    /**
     * Método de prueba para la IA local Hawkins
     * Permite probar la integración sin necesidad de WhatsApp
     *
     * Uso: GET /chatgpt/{texto} o GET /test-ia-local?mensaje=Hola&remitente=34612345678
     */
    public function chatGptPruebas($texto = null)
    {
        $mensaje = request()->get('mensaje', $texto);
        $remitente = request()->get('remitente', '34600000000'); // Remitente de prueba por defecto

        if (!$mensaje) {
            return response()->json([
                'error' => 'Debes proporcionar un mensaje',
                'uso' => 'GET /chatgpt/{texto} o GET /test-ia-local?mensaje=Hola&remitente=34612345678'
            ], 400);
        }

        Log::info("🧪 PRUEBA IA LOCAL - Mensaje: {$mensaje}, Remitente: {$remitente}");

        try {
            // Llamar al método principal que usa la IA local
            $respuesta = $this->enviarMensajeOpenAiChatCompletions($mensaje, $remitente);

            if (!$respuesta) {
                return response()->json([
                    'error' => 'No se obtuvo respuesta de la IA local',
                    'mensaje_enviado' => $mensaje,
                    'remitente' => $remitente
                ], 500);
            }

            return response()->json([
                'success' => true,
                'mensaje_enviado' => $mensaje,
                'remitente' => $remitente,
                'respuesta_ia' => $respuesta,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error en prueba de IA local: " . $e->getMessage());

            return response()->json([
                'error' => 'Error al procesar la petición',
                'mensaje' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Método de prueba interactiva con la IA local
     * Simula una conversación completa con historial
     */
    public function testIAChatInteractivo()
    {
        $remitente = request()->get('remitente', '34600000000');
        $mensaje = request()->get('mensaje', 'Hola');
        $codigoReserva = request()->get('codigo_reserva', 'HMR2Y2RSF4');

        // Usar URL local para pruebas
        $config = [
            'base_url' => config('services.hawkins_whatsapp_ai.base_url', env('HAWKINS_WHATSAPP_AI_URL')),
            'api_key' => config('services.hawkins_whatsapp_ai.api_key', env('HAWKINS_WHATSAPP_AI_API_KEY')),
            'model' => config('services.hawkins_whatsapp_ai.model', 'qwen3:latest'),
        ];

        $endpoint = $config['base_url'];
        $apiKey = $config['api_key'];
        $modelo = $config['model'];

        $promptAsistente = PromptAsistente::first();
        $promptBase = $promptAsistente ? $promptAsistente->prompt : "Eres un asistente de apartamentos turísticos.";

        // Construir instrucciones sobre funciones disponibles
        $instruccionesFunciones = "\n\nFUNCIONES DISPONIBLES:\n" .
            "Cuando el usuario te proporcione un código de reserva y necesite las claves, DEBES usar: [FUNCION:obtener_claves:codigo_reserva=CODIGO]\n" .
            "Cuando haya un problema técnico o avería que requiera intervención, usa: [FUNCION:notificar_tecnico:descripcion=DESCRIPCION:urgencia=alta|media|baja]\n" .
            "Cuando soliciten limpieza, usa: [FUNCION:notificar_limpieza:tipo_limpieza=TIPO:observaciones=OBS]\n\n" .
            "IMPORTANTE: Si el usuario menciona un código de reserva y necesita claves, usa obtener_claves INMEDIATAMENTE.\n" .
            "Si NO necesitas ejecutar ninguna función, responde normalmente al usuario de forma natural y útil.";

        $promptSystem = $promptBase . $instruccionesFunciones;

        // Obtener historial de conversación (solo mensajes completos)
        $historialArray = ChatGpt::where('remitente', $remitente)
            ->where('status', 1)
            ->whereNotNull('respuesta')
            ->where('respuesta', '!=', '')
            ->orderBy('date', 'asc')
            ->limit(20)
            ->get()
            ->flatMap(function ($chat) {
                $mensajes = [];
                if (!empty($chat->mensaje)) {
                    $mensajes[] = "Usuario: " . trim($chat->mensaje);
                }
                if (!empty($chat->respuesta)) {
                    $mensajes[] = "Asistente: " . trim($chat->respuesta);
                }
                return $mensajes;
            })
            ->toArray();

        $historialTexto = implode("\n", $historialArray);

        // Construir prompt completo
        $promptCompleto = $promptSystem;
        if (!empty($historialArray)) {
            $promptCompleto .= "\n\n" . implode("\n", $historialArray);
        }
        $promptCompleto .= "\n\nUsuario: " . $mensaje . "\nAsistente:";

        Log::info("🧪 PRUEBA INTERACTIVA - Enviando a IA local", [
            'endpoint' => $endpoint,
            'modelo' => $modelo,
            'mensaje' => $mensaje,
            'historial_lineas' => count($historialArray),
            'prompt_length' => strlen($promptCompleto)
        ]);

        try {
            $response = $this->hacerPeticionIALocal($endpoint, $apiKey, $promptCompleto, $modelo, 30);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Error en la petición HTTP',
                    'status_code' => $response->status(),
                    'body' => $response->body()
                ], 500);
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                return response()->json([
                    'error' => 'Error en respuesta de IA',
                    'data' => $data
                ], 500);
            }

            $respuestaTexto = $data['respuesta'] ?? null;

            if (!$respuestaTexto) {
                return response()->json([
                    'error' => 'Respuesta vacía de IA',
                    'data' => $data
                ], 500);
            }

            // Detectar función
            $funcionDetectada = false;
            $nombreFuncion = null;
            $parametros = [];

            if (preg_match('/\[FUNCION:([^:]+):(.+?)\]/', $respuestaTexto, $matches)) {
                $nombreFuncion = trim($matches[1]);
                $parametrosStr = $matches[2];
                $funcionDetectada = true;

                foreach (explode(':', $parametrosStr) as $param) {
                    if (strpos($param, '=') !== false) {
                        list($key, $value) = explode('=', $param, 2);
                        $parametros[trim($key)] = trim($value);
                    }
                }
            }

            // Si hay código de reserva en el mensaje y no se detectó función, sugerirla
            $codigoDetectado = $this->detectarCodigoReserva($mensaje);
            if (!$codigoDetectado) {
                $codigoDetectado = $this->detectarCodigoReserva($historialTexto);
            }

            return response()->json([
                'success' => true,
                'mensaje_enviado' => $mensaje,
                'respuesta_ia' => $respuestaTexto,
                'funcion_detectada' => $funcionDetectada,
                'nombre_funcion' => $nombreFuncion,
                'parametros' => $parametros,
                'codigo_detectado_en_mensaje' => $codigoDetectado,
                'historial_lineas' => count($historialArray),
                'prompt_enviado' => $promptCompleto, // Para debug
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error en prueba interactiva: " . $e->getMessage());

            return response()->json([
                'error' => 'Excepción al procesar',
                'mensaje' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Método de prueba directa a la API de IA local (sin historial)
     * Útil para verificar la conexión básica
     */
    public function testIALocalDirecta()
    {
        $mensaje = request()->get('mensaje', 'Hola, ¿cómo estás?');

        // Configuración de la IA local Hawkins para WhatsApp
        $config = config('services.hawkins_whatsapp_ai');
        $endpoint = $config['base_url'];

        // Asegurar que la URL termine en /chat/chat
        if (!str_ends_with($endpoint, '/chat/chat')) {
            if (str_ends_with($endpoint, '/chat')) {
                $endpoint = rtrim($endpoint, '/chat') . '/chat/chat';
            } else {
                $endpoint = rtrim($endpoint, '/') . '/chat/chat';
            }
        }

        $apiKey = $config['api_key'];
        $modelo = $config['model'];

        Log::info("🧪 PRUEBA DIRECTA IA LOCAL", [
            'endpoint' => $endpoint,
            'modelo' => $modelo,
            'mensaje' => $mensaje
        ]);

        try {
            $response = $this->hacerPeticionIALocal($endpoint, $apiKey, $mensaje, $modelo, 30);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Error en la petición HTTP',
                    'status_code' => $response->status(),
                    'body' => $response->body(),
                    'config' => [
                        'endpoint' => $endpoint,
                        'modelo' => $modelo,
                        'api_key_set' => !empty($apiKey)
                    ]
                ], 500);
            }

            $data = $response->json();

            return response()->json([
                'success' => true,
                'mensaje_enviado' => $mensaje,
                'respuesta_completa' => $data,
                'respuesta_texto' => $data['respuesta'] ?? null,
                'config' => [
                    'endpoint' => $endpoint,
                    'modelo' => $modelo
                ],
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error en prueba directa: " . $e->getMessage());

            return response()->json([
                'error' => 'Excepción al procesar la petición',
                'mensaje' => $e->getMessage(),
                'config' => [
                    'endpoint' => $endpoint,
                    'modelo' => $modelo,
                    'api_key_set' => !empty($apiKey)
                ],
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

}
