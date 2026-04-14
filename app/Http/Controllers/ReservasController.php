<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\ChatGpt;
use App\Models\Cliente;
use App\Models\Estado;
use App\Models\Huesped;
use App\Models\Invoices;
use App\Models\InvoicesReferenceAutoincrement;
use App\Models\MensajeAuto;
use App\Models\Photo;
use App\Models\RatePlan;
use App\Models\Reserva;
use App\Models\RoomType;
use App\Services\ChatGptService;
use App\Services\NotificationService;
use App\Services\MIRService;
use Carbon\Carbon;
use Carbon\Cli\Invoker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReservasController extends Controller
{
    protected $chatGptService;
    private $apiUrl = 'https://staging.channex.io/api/v1';
    private $apiToken = 'uMxPHon+J28pd17nie3qeU+kF7gUulWjb2UF5SRFr4rSIhmLHLwuL6TjY92JGxsx'; // Reemplaza con tu token de acceso

    public function __construct(ChatGptService $ChatGptService)
    {
        $this->chatGptService = $ChatGptService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    $orderBy = $request->get('order_by', 'created_at');
    $direction = $request->get('direction', 'desc');
    $perPage = $request->get('perPage', 10);
    $searchTerm = $request->get('search', '');

    // Obtener fechas del request, usando null como predeterminado si no se especifican
    $fechaEntrada = $request->get('fecha_entrada');
    $fechaSalida = $request->get('fecha_salida');

    // Log the search operation
    $this->logRead('RESERVAS', null, [
        'order_by' => $orderBy,
        'direction' => $direction,
        'per_page' => $perPage,
        'search' => $searchTerm,
        'fecha_entrada' => $fechaEntrada,
        'fecha_salida' => $fechaSalida
    ]);

    $query = Reserva::with('cliente');

    // Aplicar filtro de estado de reservas
    $filtroEstado = $request->get('filtro_estado', 'activas');

    if ($filtroEstado === 'activas') {
        $query->where('estado_id', '!=', 4);
    } elseif ($filtroEstado === 'eliminadas') {
        $query->onlyTrashed(); // Solo reservas eliminadas (soft delete)
    }
    // Si es 'todas', no aplicamos filtro de estado

    $apartamentos = Apartamento::all();

   // Aplicar filtros de fechas solo si se proporcionan
   if (!empty($fechaEntrada) && !empty($fechaSalida)) {
        $query->whereDate('fecha_entrada', '>=', $fechaEntrada)
          ->whereDate('fecha_salida', '<=', $fechaSalida);
    }
    elseif (!empty($fechaEntrada)) {
        // Si el usuario solo proporciona la fecha de entrada
        $query->whereDate('fecha_entrada', '=', $fechaEntrada);
    } elseif (!empty($fechaSalida)) {
        // Si el usuario solo proporciona la fecha de salida
        $query->whereDate('fecha_salida', '=', $fechaSalida);
    }

    if (!empty($searchTerm)) {
        $query->where(function ($q) use ($searchTerm) {
            $q->whereHas('cliente', function ($qCliente) use ($searchTerm) {
                $qCliente->where('alias', 'like', '%' . $searchTerm . '%');
            })->orWhere('codigo_reserva', 'like', '%' . $searchTerm . '%');
        });
    }

    $filtroApartamento = $request->get('filtro_apartamento');

    if (!empty($filtroApartamento)) {
        $query->where('apartamento_id', $filtroApartamento);
    }


    // // Aplicar filtros de fechas solo si se proporcionan
    // if (!empty($fechaEntrada)) {
    //     $query->whereDate('fecha_entrada', '=', $fechaEntrada);
    // }
    // if (!empty($fechaSalida)) {
    //     $query->whereDate('fecha_salida', '=', $fechaSalida);
    // }

    if(!$direction) {
        $direction = 'asc';
    }
    if(!$orderBy) {
        $orderBy = 'id';
    }
    $reservas = $query->orderBy($orderBy, $direction)->paginate($perPage)->appends([
        'order_by' => $orderBy,
        'direction' => $direction,
        'search' => $searchTerm,
        'perPage' => $perPage,
        'fecha_entrada' => $fechaEntrada,
        'fecha_salida' => $fechaSalida,
        'filtro_apartamento' => $filtroApartamento,
        'filtro_estado' => $filtroEstado,
    ]);

    return view('reservas.index', compact('reservas', 'apartamentos'));
}


    /**
     * Display a listing of the resource.
     */
    public function calendar()
    {
        return view('reservas.calendar');

    }

    public function obtenerApartamentos(){
        $apartamentos = Apartamento::all();
        return response()->json($apartamentos);
    }


    public function getReservas(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $reservas = Reserva::with(['cliente', 'apartamento'])
            ->whereDate('fecha_entrada', '<=', $end)
            ->whereDate('fecha_salida', '>=', $start)
            ->get();

        return response()->json($reservas);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clientes = Cliente::all();
        $apartamentos = Apartamento::with('roomTypes')->get(); // Incluir roomTypes en la consulta
        $ratePlans = RatePlan::all();
        $estados = Estado::all();

        return view('reservas.create', compact('clientes', 'apartamentos', 'estados', 'ratePlans'));
    }

    public function getRoomTypes($apartamento_id)
    {
        $roomTypes = RoomType::where('property_id', $apartamento_id)->get();
        return response()->json($roomTypes);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|integer',
            'apartamento_id' => 'required|integer',
            'estado_id' => 'required|integer',
            'origen' => 'required|string',
            'room_type_id' => 'required|string',
            'fecha_entrada' => 'required|date',
            'fecha_salida' => 'required|date|after:fecha_entrada',
            'codigo_reserva' => 'required|string',
            'precio' => 'required|string',
            'verificado' => 'nullable|integer',
            'dni_entregado' => 'nullable|integer',
            'enviado_webpol' => 'nullable|integer',
            'no_facturar' => 'nullable|boolean'
        ]);

        $input = $request->all();
        $input['precio'] = floatval(str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $input['precio'])));

        // Validar solapamiento de fechas
        $overlap = \App\Services\ReservationValidationService::findOverlap(
            (int) $input['apartamento_id'],
            $input['fecha_entrada'],
            $input['fecha_salida']
        );
        if ($overlap) {
            return redirect()->back()->withInput()->withErrors([
                'fecha_entrada' => "Ya existe una reserva (#{$overlap->id} - {$overlap->codigo_reserva}) para este apartamento en las fechas seleccionadas ({$overlap->fecha_entrada} a {$overlap->fecha_salida})."
            ]);
        }

        // Usar transacción para que la reserva + código de acceso sean atómicos
        $reserva = DB::transaction(function () use ($input) {
            // Crear la reserva con los datos validados
            $reserva = Reserva::create([
                'cliente_id' => $input['cliente_id'],
                'apartamento_id' => $input['apartamento_id'],
                'room_type_id' => $input['room_type_id'],
                'estado_id' => $input['estado_id'],
                'origen' => $input['origen'],
                'fecha_entrada' => $input['fecha_entrada'],
                'fecha_salida' => $input['fecha_salida'],
                'codigo_reserva' => $input['codigo_reserva'],
                'precio' => $input['precio'],
                'verificado' => $input['verificado'] ?? null,
                'dni_entregado' => $input['dni_entregado'] ?? null,
                'enviado_webpol' => $input['enviado_webpol'] ?? null,
                'no_facturar' => $input['no_facturar'] ?? false
            ]);

            // Log the creation
            $this->logCreate('RESERVA', $reserva->id, $reserva->toArray());

            // Generar y programar código de acceso para TTLock
            try {
                app(\App\Services\AccessCodeService::class)->generarYProgramar($reserva);
            } catch (\Exception $e) {
                \Log::error('AccessCodeService error en ReservasController: ' . $e->getMessage());
            }

            return $reserva;
        });

        // Crear notificación de nueva reserva (fuera de la transacción - es una acción externa)
        NotificationService::notifyNewReservation($reserva);

        // Si la reserva es de hoy y son más de las 14:00, intentar enviar claves por Channex
        \App\Console\Kernel::enviarClavesPorChannexSiEsNecesario($reserva);

        // Actualizar Channex para TODAS las reservas creadas manualmente (sin id_channex)
        // Esto incluye reservas presenciales, web, admin, manual, etc.
        // Solo excluimos las reservas que vienen de Channex (tienen id_channex) porque ya están sincronizadas
        if (empty($reserva->id_channex)) {
            // Obtener el room_type correcto del apartamento si no está asignado o no es válido
            $apartamento = Apartamento::with('roomTypes')->find($reserva->apartamento_id);
            $roomType = null;

            // Si la reserva ya tiene room_type_id, verificar que pertenezca al apartamento
            if ($reserva->room_type_id) {
                $roomType = RoomType::where('id', $reserva->room_type_id)
                    ->where('property_id', $reserva->apartamento_id)
                    ->first();
            }

            // Si no hay room_type válido, obtener el primero del apartamento con id_channex
            if (!$roomType && $apartamento) {
                $roomType = $this->obtenerRoomTypeParaChannex($apartamento);

                // Si encontramos un room_type, actualizar la reserva
                if ($roomType) {
                    $reserva->room_type_id = $roomType->id;
                    $reserva->save();
                }
            }

            // Actualizar Channex si tenemos todos los datos necesarios
            if ($apartamento && $apartamento->id_channex && $roomType && $roomType->id_channex) {
                $this->updateChannexAvailability($reserva);

                Log::info('Reserva creada manualmente - Channex actualizado (disponibilidad cerrada)', [
                    'reserva_id' => $reserva->id,
                    'apartamento_id' => $apartamento->id,
                    'apartamento_titulo' => $apartamento->titulo,
                    'room_type_id' => $roomType->id,
                    'origen' => $reserva->origen,
                    'fecha_entrada' => $reserva->fecha_entrada,
                    'fecha_salida' => $reserva->fecha_salida
                ]);
            } else {
                Log::warning('No se pudo actualizar Channex al crear reserva manual - faltan datos', [
                    'reserva_id' => $reserva->id,
                    'apartamento_id' => $apartamento ? $apartamento->id : null,
                    'apartamento_tiene_channex' => $apartamento ? !empty($apartamento->id_channex) : false,
                    'room_type_id' => $roomType ? $roomType->id : null,
                    'room_type_tiene_channex' => $roomType ? !empty($roomType->id_channex) : false,
                    'origen' => $reserva->origen
                ]);
            }
        } else {
            // Para reservas con id_channex (vienen de Channex), no actualizamos porque ya están sincronizadas
            Log::info('Reserva con id_channex creada - no se actualiza Channex (ya sincronizada)', [
                'reserva_id' => $reserva->id,
                'id_channex' => $reserva->id_channex,
                'origen' => $reserva->origen
            ]);
        }

        // Enviar enlace DNI automáticamente al crear la reserva
        try {
            // Generar token DNI
            $reserva->token = bin2hex(random_bytes(16));
            $reserva->save();

            // Enviar WhatsApp + Email con enlace DNI
            $this->enviarDniAutomatico($reserva);

            Log::info('DNI enviado automáticamente al crear reserva', ['reserva_id' => $reserva->id]);
        } catch (\Exception $e) {
            Log::error('Error al enviar DNI automático en creación de reserva', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage()
            ]);
        }

        return redirect()->route('reservas.index')->with('success', 'Reserva creada con éxito. Enlace DNI enviado al cliente.');
    }

    /**
     * Envío automático de DNI al crear reserva (WhatsApp + Email)
     */
    private function enviarDniAutomatico(Reserva $reserva)
    {
        $cliente = $reserva->cliente;
        if (!$cliente) return;

        $token = $reserva->token;

        // Detectar idioma
        $idiomaCliente = 'es';
        if ($cliente->nacionalidad) {
            try {
                $clienteService = app(\App\Services\ClienteService::class);
                $idiomaCliente = $clienteService->idiomaCodigo($cliente->nacionalidad) ?: 'es';
            } catch (\Exception $e) {
                $idiomaCliente = 'es';
            }
        }

        // WhatsApp
        $telefono = $cliente->telefono_movil ?? $cliente->telefono ?? null;
        if (!empty($telefono)) {
            $phoneCliente = $this->limpiarNumeroTelefono($telefono);
            try {
                $kernel = app(\App\Console\Kernel::class);
                $kernel->mensajesAutomaticosBoton('dni', $token, $phoneCliente, $idiomaCliente);
                Log::info('DNI WhatsApp enviado automáticamente', ['reserva_id' => $reserva->id, 'telefono' => $phoneCliente]);
            } catch (\Exception $e) {
                Log::error('Error DNI WhatsApp automático', ['reserva_id' => $reserva->id, 'error' => $e->getMessage()]);
            }
        }

        // Email
        $email = $cliente->email_secundario ?: $cliente->email;
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $kernel = app(\App\Console\Kernel::class);
                $mensajeEmail = $kernel->dniEmail($idiomaCliente, $token);
                $kernel->enviarEmail($email, 'emails.envioClavesEmail', $mensajeEmail, 'Hawkins Suite - DNI', $token);
                Log::info('DNI Email enviado automáticamente', ['reserva_id' => $reserva->id, 'email' => $email]);
            } catch (\Exception $e) {
                Log::error('Error DNI Email automático', ['reserva_id' => $reserva->id, 'error' => $e->getMessage()]);
            }
        }

        // Registrar para que el cron no lo duplique
        MensajeAuto::firstOrCreate(
            ['reserva_id' => $reserva->id, 'categoria_id' => 1],
            ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
        );
    }

    /**
     * Envía la actualización de disponibilidad a Channex después de crear una reserva.
     */
    private function updateChannexAvailability(Reserva $reserva)
{
    $startDate = Carbon::parse($reserva->fecha_entrada);
    $endDate = Carbon::parse($reserva->fecha_salida)->subDay(); // Restamos un día a la fecha de salida

    $apartamento = Apartamento::find($reserva->apartamento_id);
    $roomType = RoomType::find($reserva->room_type_id);

    if (!$apartamento || !$apartamento->id_channex || !$roomType || !$roomType->id_channex) {
        return; // Si falta algún ID necesario, no hacemos nada
    }

    $update = [
        'property_id' => $apartamento->id_channex,
        'room_type_id' => $roomType->id_channex,
        'date_from' => $startDate->toDateString(),
        'date_to' => $endDate->toDateString(),
        'update_type' => 'availability',
        'availability' => 0, // Bloqueamos la disponibilidad
    ];

    // Enviar actualización a Channex (sin verificación SSL)
    $response = Http::withHeaders([
        'user-api-key' => $this->apiToken,
    ])->post("{$this->apiUrl}/availability", ['values' => [$update]]);

    if (!$response->successful()) {
        Log::error('Error al actualizar disponibilidad en Channex', [
            'error' => $response->body(),
            'data' => $update
        ]);
    }
    return [$response->json(), $update];
}

    /**
     * Libera la disponibilidad en Channex para un apartamento en un rango de fechas.
     */
    private function liberarChannexAvailability($apartamento, $roomType, $fechaEntrada, $fechaSalida)
    {
        $startDate = Carbon::parse($fechaEntrada);
        $endDate = Carbon::parse($fechaSalida)->subDay(); // Restamos un día a la fecha de salida

        if (!$apartamento || !$apartamento->id_channex || !$roomType || !$roomType->id_channex) {
            return; // Si falta algún ID necesario, no hacemos nada
        }

        $update = [
            'property_id' => $apartamento->id_channex,
            'room_type_id' => $roomType->id_channex,
            'date_from' => $startDate->toDateString(),
            'date_to' => $endDate->toDateString(),
            'update_type' => 'availability',
            'availability' => 1, // Liberamos la disponibilidad
        ];

        // Enviar actualización a Channex (sin verificación SSL)
        $response = Http::withHeaders([
            'user-api-key' => $this->apiToken,
        ])->post("{$this->apiUrl}/availability", ['values' => [$update]]);

        if (!$response->successful()) {
            Log::error('Error al liberar disponibilidad en Channex', [
                'error' => $response->body(),
                'data' => $update
            ]);
        } else {
            Log::info('Disponibilidad liberada en Channex', [
                'apartamento_id' => $apartamento->id,
                'apartamento_titulo' => $apartamento->titulo,
                'room_type_id' => $roomType->id,
                'fecha_entrada' => $fechaEntrada,
                'fecha_salida' => $fechaSalida
            ]);
        }
        return [$response->json(), $update];
    }

    /**
     * Obtiene el room_type de un apartamento que tenga id_channex para usar en Channex.
     */
    private function obtenerRoomTypeParaChannex($apartamento)
    {
        if (!$apartamento || !$apartamento->id_channex) {
            return null;
        }

        // Obtener el primer room_type del apartamento que tenga id_channex
        return $apartamento->roomTypes()
            ->whereNotNull('id_channex')
            ->first();
    }


    /**
     * Display the specified resource.
     */
    public function show(Reserva $reserva)
    {
        // Cargar las relaciones necesarias
        $reserva->load([
            'apartamento.edificioName',
            'cliente',
            'estado',
            'serviciosExtras.servicio',
            'serviciosExtras.pago'
        ]);

        $huespedes = Huesped::where('reserva_id', $reserva->id)->get();
        $mensajes = MensajeAuto::with('categoria')->where('reserva_id', $reserva->id)->get();
        $photos = Photo::where('reserva_id', $reserva->id)->get();
        $factura = Invoices::where('reserva_id', $reserva->id)->first();

        return view('reservas.show', compact('reserva', 'mensajes', 'photos','huespedes', 'factura'));
    }

    /**
     * Enviar datos de la reserva a la plataforma externa (URL en PLATAFORMA_RESERVAS_URL).
     * Envía: fecha_entrada, fecha_salida, codigo_reserva, apartamento_id, nombre_apartamento, id_channex.
     */
    public function enviarPlataforma(Reserva $reserva)
    {
        $url = config('services.plataforma_reservas_url');

        if (empty($url)) {
            return redirect()->route('reservas.show', $reserva->id)
                ->with('error', 'No está configurada la URL de la plataforma. Añade PLATAFORMA_RESERVAS_URL en el .env');
        }

        $reserva->loadMissing('apartamento');

        $payload = [
            'fecha_entrada' => $reserva->fecha_entrada ? \Carbon\Carbon::parse($reserva->fecha_entrada)->format('Y-m-d') : null,
            'fecha_salida'  => $reserva->fecha_salida ? \Carbon\Carbon::parse($reserva->fecha_salida)->format('Y-m-d') : null,
            'codigo_reserva' => $reserva->codigo_reserva,
            'apartamento_id' => $reserva->apartamento_id,
            'nombre_apartamento' => $reserva->apartamento?->titulo ?? null,
            'id_channex' => $reserva->apartamento?->id_channex ?? null,
        ];

        try {
            // Petición POST con body JSON (la plataforma debe aceptar POST en su ruta)
            $response = Http::timeout(15)
                ->asJson()
                ->acceptJson()
                ->post($url, $payload);

            $status = $response->status();
            $body = $response->json() ?? $response->body();

            Log::info('Petición a plataforma externa', [
                'reserva_id' => $reserva->id,
                'codigo_reserva' => $reserva->codigo_reserva,
                'url' => $url,
                'status' => $status,
            ]);

            $payloadJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($response->successful()) {
                return redirect()->route('reservas.show', $reserva->id)
                    ->with('success', 'Datos enviados correctamente a la plataforma. Respuesta: ' . (is_array($body) ? json_encode($body) : $body))
                    ->with('plataforma_payload', $payloadJson)
                    ->with('plataforma_response', is_array($body) ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $body);
            }

            return redirect()->route('reservas.show', $reserva->id)
                ->with('error', 'La plataforma respondió con error (HTTP ' . $status . '). Respuesta: ' . (is_array($body) ? json_encode($body) : substr((string) $body, 0, 500)))
                ->with('plataforma_payload', $payloadJson)
                ->with('plataforma_response', is_array($body) ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : substr((string) $body, 0, 1000));
        } catch (\Exception $e) {
            Log::error('Error al enviar reserva a plataforma', [
                'reserva_id' => $reserva->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            $payloadJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return redirect()->route('reservas.show', $reserva->id)
                ->with('error', 'Error al conectar con la plataforma: ' . $e->getMessage())
                ->with('plataforma_payload', $payloadJson);
        }
    }

    /**
     * Enviar reserva a MIR (Servicio de Hospedajes)
     */
    public function enviarMIR(Reserva $reserva)
    {
        try {
            $mirService = new MIRService();
            $resultado = $mirService->enviarReserva($reserva);

            // Actualizar la reserva con el resultado
            $reserva->mir_enviado = $resultado['success'];
            $reserva->mir_estado = $resultado['estado'];
            $reserva->mir_respuesta = json_encode($resultado);
            $reserva->mir_fecha_envio = now();
            $reserva->mir_codigo_referencia = $resultado['codigo_referencia'] ?? null;
            $reserva->save();

            if ($resultado['success']) {
                Log::info('Reserva enviada exitosamente a MIR', [
                    'reserva_id' => $reserva->id,
                    'codigo_referencia' => $resultado['codigo_referencia'],
                ]);

                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Reserva enviada exitosamente a MIR. Codigo de referencia: ' . ($resultado['codigo_referencia'] ?? 'N/A'),
                        'codigo_referencia' => $resultado['codigo_referencia'] ?? null,
                    ]);
                }

                return redirect()->route('reservas.show', $reserva->id)
                    ->with('success', 'Reserva enviada exitosamente a MIR. Código de referencia: ' . ($resultado['codigo_referencia'] ?? 'N/A'));
            } else {
                Log::error('Error al enviar reserva a MIR', [
                    'reserva_id' => $reserva->id,
                    'error' => $resultado['mensaje'],
                ]);

                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al enviar la reserva a MIR: ' . $resultado['mensaje'],
                    ], 422);
                }

                return redirect()->route('reservas.show', $reserva->id)
                    ->with('error', 'Error al enviar la reserva a MIR: ' . $resultado['mensaje']);
            }

        } catch (\Exception $e) {
            Log::error('Excepción al enviar reserva a MIR', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error inesperado al enviar la reserva a MIR: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('reservas.show', $reserva->id)
                ->with('error', 'Error inesperado al enviar la reserva a MIR: ' . $e->getMessage());
        }
    }

    /**
     * Enviar enlace DNI al cliente por WhatsApp y Email
     */
    public function enviarDni($id)
    {
        try {
            $reserva = Reserva::with('cliente', 'apartamento')->findOrFail($id);
            $cliente = $reserva->cliente;

            if (!$cliente) {
                return response()->json(['success' => false, 'message' => 'No se encontró el cliente de esta reserva'], 422);
            }

            // Generar token si no existe
            if (empty($reserva->token)) {
                $reserva->token = bin2hex(random_bytes(16));
                $reserva->save();
            }

            $token = $reserva->token;
            $baseUrl = config('app.url');
            $dniUrl = "{$baseUrl}/dni-scanner/{$token}";
            $resultados = [];

            // Detectar idioma del cliente
            $idiomaCliente = 'es';
            if ($cliente->nacionalidad) {
                $clienteService = app(\App\Services\ClienteService::class);
                $idiomaCliente = $clienteService->idiomaCodigo($cliente->nacionalidad) ?: 'es';
            }

            // 1. Enviar WhatsApp con template de DNI (si tiene teléfono)
            $telefono = $cliente->telefono_movil ?? $cliente->telefono ?? null;
            if (!empty($telefono)) {
                $phoneCliente = $this->limpiarNumeroTelefono($telefono);
                try {
                    $kernel = app(\App\Console\Kernel::class);
                    $kernel->mensajesAutomaticosBoton('dni', $token, $phoneCliente, $idiomaCliente);
                    $resultados[] = "WhatsApp enviado a {$phoneCliente}";
                    Log::info('DNI WhatsApp enviado manualmente', ['reserva_id' => $reserva->id, 'telefono' => $phoneCliente]);
                } catch (\Exception $e) {
                    $resultados[] = "Error WhatsApp: {$e->getMessage()}";
                    Log::error('Error enviando DNI WhatsApp', ['reserva_id' => $reserva->id, 'error' => $e->getMessage()]);
                }
            } else {
                $resultados[] = "Sin teléfono - WhatsApp no enviado";
            }

            // 2. Enviar Email con enlace DNI (si tiene email)
            $email = $cliente->email_secundario ?: $cliente->email;
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $kernel = app(\App\Console\Kernel::class);
                    $mensajeEmail = $kernel->dniEmail($idiomaCliente, $token);
                    $kernel->enviarEmail($email, 'emails.envioClavesEmail', $mensajeEmail, 'Hawkins Suite - DNI', $token);
                    $resultados[] = "Email enviado a {$email}";
                    Log::info('DNI Email enviado manualmente', ['reserva_id' => $reserva->id, 'email' => $email]);
                } catch (\Exception $e) {
                    $resultados[] = "Error Email: {$e->getMessage()}";
                    Log::error('Error enviando DNI Email', ['reserva_id' => $reserva->id, 'error' => $e->getMessage()]);
                }
            } else {
                $resultados[] = "Sin email válido - Email no enviado";
            }

            // Registrar en MensajeAuto para evitar duplicados del cron
            MensajeAuto::firstOrCreate(
                ['reserva_id' => $reserva->id, 'categoria_id' => 1],
                ['cliente_id' => $reserva->cliente_id, 'fecha_envio' => Carbon::now()]
            );

            $mensaje = implode(' | ', $resultados);

            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'message' => $mensaje, 'url' => $dniUrl]);
            }

            return redirect()->route('reservas.show', $reserva->id)->with('success', "Enlace DNI enviado: {$mensaje}");

        } catch (\Exception $e) {
            Log::error('Error en enviarDni', ['id' => $id, 'error' => $e->getMessage()]);

            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Error al enviar enlace DNI: ' . $e->getMessage());
        }
    }

    /**
     * Limpiar número de teléfono para WhatsApp
     */
    private function limpiarNumeroTelefono($telefono)
    {
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);
        $telefono = ltrim($telefono, '+');
        if (strlen($telefono) === 9) {
            $telefono = '34' . $telefono;
        }
        return $telefono;
    }

    /**
     * Toggle el estado de conversacion_plataforma de una reserva
     */
    public function toggleConversacionPlataforma(Reserva $reserva)
    {
        try {
            // Cambiar el estado (toggle)
            $reserva->conversacion_plataforma = !$reserva->conversacion_plataforma;
            $reserva->save();

            $estadoTexto = $reserva->conversacion_plataforma ? 'desactivadas' : 'activadas';

            Log::info('Estado de conversacion_plataforma actualizado', [
                'reserva_id' => $reserva->id,
                'nuevo_estado' => $reserva->conversacion_plataforma,
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Contestaciones por plataforma {$estadoTexto} correctamente",
                    'conversacion_plataforma' => $reserva->conversacion_plataforma
                ]);
            }

            return redirect()->route('reservas.show', $reserva->id)
                ->with('success', "Contestaciones por plataforma {$estadoTexto} correctamente");

        } catch (\Exception $e) {
            Log::error('Error al actualizar conversacion_plataforma', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el estado: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('reservas.show', $reserva->id)
                ->with('error', 'Error al actualizar el estado: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $reserva = Reserva::where('id', $id)->first();
        $apartamentos = Apartamento::all();
        return view('reservas.edit', compact('reserva', 'apartamentos'));
    }

    public function updateReserva(Request $request, $id)
{
    // Validación
    $validated = $request->validate([
        'apartamento_id' => 'required|exists:apartamentos,id',
        'origen' => 'required|string|max:255',
        'fecha_entrada' => 'required|date',
        'fecha_salida' => 'required|date|after:fecha_entrada',
        'precio' => 'required|numeric|min:0',
        'no_facturar' => 'nullable|boolean',
    ]);

    // Buscar la reserva
    $reserva = Reserva::findOrFail($id);
    $oldData = $reserva->toArray();
    $validated['precio'] = floatval(str_replace(',', '.', $validated['precio']));

    // Actualizar los campos
    $reserva->update([
        'apartamento_id' => $validated['apartamento_id'],
        'origen' => $validated['origen'],
        'fecha_entrada' => $validated['fecha_entrada'],
        'fecha_salida' => $validated['fecha_salida'],
        'precio' => $validated['precio'],
        'no_facturar' => $validated['no_facturar'] ?? false,
    ]);

    // Log the update
    $this->logUpdate('RESERVA', $reserva->id, $oldData, $reserva->toArray());

    // Crear notificación de actualización de reserva
    NotificationService::notifyReservationUpdate($reserva, $oldData);

    // Actualizar Channex si es necesario (TODAS las reservas sin id_channex)
    // Solo excluimos las reservas que vienen de Channex (tienen id_channex) porque ya están sincronizadas
    if (empty($reserva->id_channex)) {
        // Detectar cambios
        $cambioApartamento = $oldData['apartamento_id'] != $validated['apartamento_id'];
        $cambioFechaEntrada = $oldData['fecha_entrada'] != $validated['fecha_entrada'];
        $cambioFechaSalida = $oldData['fecha_salida'] != $validated['fecha_salida'];

        $hayCambios = $cambioApartamento || $cambioFechaEntrada || $cambioFechaSalida;

        if ($hayCambios) {
            // Obtener apartamentos con sus roomTypes
            $apartamentoAnterior = Apartamento::with('roomTypes')->find($oldData['apartamento_id']);
            $apartamentoNuevo = Apartamento::with('roomTypes')->find($validated['apartamento_id']);

            // CASO 1: Cambió apartamento
            if ($cambioApartamento) {
                // Obtener room_type_id del apartamento anterior (desde oldData)
                $roomTypeAnterior = null;
                if (!empty($oldData['room_type_id'])) {
                    $roomTypeAnterior = RoomType::find($oldData['room_type_id']);
                }

                // Si no hay room_type_id en oldData, intentar obtenerlo del apartamento anterior
                if (!$roomTypeAnterior && $apartamentoAnterior) {
                    $roomTypeAnterior = $this->obtenerRoomTypeParaChannex($apartamentoAnterior);
                }

                // Obtener room_type_id del nuevo apartamento
                $roomTypeNuevo = $this->obtenerRoomTypeParaChannex($apartamentoNuevo);

                // Liberar apartamento anterior
                if ($apartamentoAnterior && $apartamentoAnterior->id_channex && $roomTypeAnterior && $roomTypeAnterior->id_channex) {
                    $this->liberarChannexAvailability(
                        $apartamentoAnterior,
                        $roomTypeAnterior,
                        $oldData['fecha_entrada'],
                        $oldData['fecha_salida']
                    );
                }

                // Bloquear nuevo apartamento
                if ($apartamentoNuevo && $apartamentoNuevo->id_channex && $roomTypeNuevo && $roomTypeNuevo->id_channex) {
                    // Actualizar room_type_id de la reserva si es necesario
                    if ($reserva->room_type_id != $roomTypeNuevo->id) {
                        $reserva->room_type_id = $roomTypeNuevo->id;
                        $reserva->save();
                    }

                    // Bloquear en Channex
                    $this->updateChannexAvailability($reserva);

                    Log::info('Apartamento cambiado en reserva manual - Channex actualizado', [
                        'reserva_id' => $reserva->id,
                        'apartamento_anterior_id' => $apartamentoAnterior->id,
                        'apartamento_anterior_titulo' => $apartamentoAnterior->titulo,
                        'apartamento_nuevo_id' => $apartamentoNuevo->id,
                        'apartamento_nuevo_titulo' => $apartamentoNuevo->titulo,
                        'origen' => $reserva->origen
                    ]);
                } else {
                    Log::warning('No se pudo actualizar Channex - faltan datos', [
                        'reserva_id' => $reserva->id,
                        'apartamento_nuevo_id' => $apartamentoNuevo ? $apartamentoNuevo->id : null,
                        'apartamento_nuevo_tiene_channex' => $apartamentoNuevo ? !empty($apartamentoNuevo->id_channex) : false,
                        'room_type_nuevo_id' => $roomTypeNuevo ? $roomTypeNuevo->id : null,
                        'room_type_nuevo_tiene_channex' => $roomTypeNuevo ? !empty($roomTypeNuevo->id_channex) : false
                    ]);
                }
            }
            // CASO 2: Solo cambió fecha (mismo apartamento)
            else if ($cambioFechaEntrada || $cambioFechaSalida) {
                // Obtener room_type del apartamento actual
                $roomTypeActual = $this->obtenerRoomTypeParaChannex($apartamentoNuevo);

                // Si no se encuentra, intentar usar el room_type_id de la reserva
                if (!$roomTypeActual && $reserva->room_type_id) {
                    $roomTypeActual = RoomType::find($reserva->room_type_id);
                }

                // Liberar fechas antiguas
                if ($apartamentoNuevo && $apartamentoNuevo->id_channex && $roomTypeActual && $roomTypeActual->id_channex) {
                    $this->liberarChannexAvailability(
                        $apartamentoNuevo,
                        $roomTypeActual,
                        $oldData['fecha_entrada'],
                        $oldData['fecha_salida']
                    );
                }

                // Bloquear nuevas fechas
                if ($apartamentoNuevo && $apartamentoNuevo->id_channex && $roomTypeActual && $roomTypeActual->id_channex) {
                    $this->updateChannexAvailability($reserva);

                    Log::info('Fechas cambiadas en reserva manual - Channex actualizado', [
                        'reserva_id' => $reserva->id,
                        'apartamento_id' => $apartamentoNuevo->id,
                        'fecha_entrada_anterior' => $oldData['fecha_entrada'],
                        'fecha_salida_anterior' => $oldData['fecha_salida'],
                        'fecha_entrada_nueva' => $validated['fecha_entrada'],
                        'fecha_salida_nueva' => $validated['fecha_salida'],
                        'origen' => $reserva->origen
                    ]);
                } else {
                    Log::warning('No se pudo actualizar Channex por cambio de fechas - faltan datos', [
                        'reserva_id' => $reserva->id,
                        'apartamento_id' => $apartamentoNuevo ? $apartamentoNuevo->id : null
                    ]);
                }
            }
        }
    }

    // Redirigir con mensaje
    return redirect()->route('reservas.index')->with('success', 'Reserva actualizada correctamente.');
}


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Buscar la reserva por su ID
        $reserva = Reserva::find($id);

        // Validar que la reserva existe
        if (!$reserva) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada'], 404);
        }

        // Intentar parsear la nueva fecha
        try {
            $newDate = Carbon::createFromFormat('Y-m-d', $request->new_date);
        } catch (\Carbon\Exceptions\InvalidFormatException $e) {
            return response()->json(['success' => false, 'message' => 'Formato de fecha inválido'], 400);
        }

        // Revisar si estamos actualizando la fecha de entrada o de salida
        if ($request->drag_type == 'start') {
            // Actualizar la fecha de entrada (se puede sumar o restar días)
            // La fecha de entrada debe ser estrictamente anterior a la fecha de salida (no se permite entrada y salida el mismo día)
            if ($newDate->lessThan($reserva->fecha_salida)) {
                $reserva->fecha_entrada = $newDate;
            } else {
                return response()->json(['success' => false, 'message' => 'La fecha de entrada debe ser anterior a la fecha de salida (no se permite entrada y salida el mismo día)'], 400);
            }
        } elseif ($request->drag_type == 'end') {
            // Actualizar la fecha de salida (se puede sumar o restar días)
            // La fecha de salida debe ser estrictamente posterior a la fecha de entrada (no se permite entrada y salida el mismo día)
            if ($newDate->greaterThan($reserva->fecha_entrada)) {
                $reserva->fecha_salida = $newDate;
            } else {
                return response()->json(['success' => false, 'message' => 'La fecha de salida debe ser posterior a la fecha de entrada (no se permite entrada y salida el mismo día)'], 400);
            }
        }

        // Guardar los cambios en la base de datos
        $reserva->save();

        // Devolver una respuesta JSON indicando éxito
        return response()->json(['success' => true, 'message' => 'Reserva actualizada correctamente']);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $reserva = Reserva::find($id);
        if ($reserva) {
            // Guardar datos antes de eliminar para liberar Channex
            $reservaData = $reserva->toArray();

            // Liberar Channex si es reserva presencial o web (sin id_channex)
            $origenLower = strtolower($reserva->origen);
            $esPresencialOWeb = (
                ($origenLower === 'presencial' || $origenLower === 'web')
                && empty($reserva->id_channex)
            );

            if ($esPresencialOWeb) {
                // Obtener apartamento y room_type antes de eliminar
                $apartamento = Apartamento::with('roomTypes')->find($reserva->apartamento_id);
                $roomType = null;

                // Intentar obtener el room_type de la reserva
                if ($reserva->room_type_id) {
                    $roomType = RoomType::where('id', $reserva->room_type_id)
                        ->where('property_id', $reserva->apartamento_id)
                        ->first();
                }

                // Si no hay room_type válido, obtener el primero del apartamento con id_channex
                if (!$roomType && $apartamento) {
                    $roomType = $this->obtenerRoomTypeParaChannex($apartamento);
                }

                // Liberar disponibilidad en Channex
                if ($apartamento && $apartamento->id_channex && $roomType && $roomType->id_channex) {
                    $this->liberarChannexAvailability(
                        $apartamento,
                        $roomType,
                        $reserva->fecha_entrada,
                        $reserva->fecha_salida
                    );

                    Log::info('Reserva presencial/web eliminada - Channex liberado', [
                        'reserva_id' => $reserva->id,
                        'apartamento_id' => $apartamento->id,
                        'apartamento_titulo' => $apartamento->titulo,
                        'room_type_id' => $roomType->id,
                        'origen' => $reserva->origen,
                        'fecha_entrada' => $reserva->fecha_entrada,
                        'fecha_salida' => $reserva->fecha_salida
                    ]);
                } else {
                    Log::warning('No se pudo liberar Channex al eliminar reserva presencial/web - faltan datos', [
                        'reserva_id' => $reserva->id,
                        'apartamento_id' => $apartamento ? $apartamento->id : null,
                        'apartamento_tiene_channex' => $apartamento ? !empty($apartamento->id_channex) : false,
                        'room_type_id' => $roomType ? $roomType->id : null,
                        'room_type_tiene_channex' => $roomType ? !empty($roomType->id_channex) : false
                    ]);
                }
            } else {
                // Para reservas con id_channex, no liberamos porque vienen de Channex
                // Channex manejará la cancelación automáticamente
                if (!empty($reserva->id_channex)) {
                    Log::info('Reserva con id_channex eliminada - Channex no se libera (gestionado por Channex)', [
                        'reserva_id' => $reserva->id,
                        'id_channex' => $reserva->id_channex,
                        'origen' => $reserva->origen
                    ]);
                }
            }

            // Log the deletion
            $this->logDelete('RESERVA', $reserva->id, $reservaData);

            // Crear notificación de cancelación de reserva
            NotificationService::notifyReservationCancellation($reserva, 'Eliminada por administrador');

            $reserva->delete(); // Esto ahora usa soft delete
            return redirect()->route('reservas.index')->with('success', 'Reserva eliminada correctamente.');
        } else {
            return redirect()->route('reservas.index')->with('error', 'Reserva no encontrada.');
        }
    }

    /**
     * Restaurar una reserva eliminada
     */
    public function restore(string $id)
    {
        $reserva = Reserva::onlyTrashed()->find($id);
        if ($reserva) {
            $reserva->restore();
            return redirect()->route('reservas.index')->with('success', 'Reserva restaurada correctamente.');
        } else {
            return redirect()->route('reservas.index')->with('error', 'Reserva no encontrada.');
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function actualizarBooking($reserva, Request $request)
    {
		$reserva = Reserva::where('codigo_reserva', $reserva)->first();
        $reserva->fecha_salida = $request->fecha_salida;
        $reserva->save();
        return response('La reserva de ha actualizado', 200);

    }
    /**
     * Remove the specified resource from storage.
     */
    public function actualizarAirbnb($reserva, Request $request)
    {
		$reserva = Reserva::where('codigo_reserva', $reserva)->first();
        $reserva->fecha_salida = $request->fecha_salida;
        $reserva->save();
        return response('La reserva de ha actualizado', 200);

    }

    public function agregarReserva(Request $request){

        // Obtenemos la Fecha de Hoy
        $hoy = Carbon::now();
        // Declaramos Variables
        $cliente = null;
        $reserva = null;
        $num_adultos = null;
        // Convertimos las Request en la data
        $data = $request->all();

        // Validamos que los campos obligatorios existan
        if (!isset($data['codigo_reserva'])) {
            return response()->json(['error' => 'Código de reserva requerido'], 400);
        }

        // Almacenamos la peticion en un archivo
        Storage::disk('local')->put($data['codigo_reserva'].'-' . $hoy .'.txt', json_encode($request->all()));

        // Comprobamos si la reserva ya existe
        $comprobarReserva = Reserva::where('codigo_reserva', $data['codigo_reserva'])->first();
        // Si la reserva no existe procedemos al registro
        if ($comprobarReserva == null) {
            // Obtenemos el Cliente si existe por el numero de telefono
            $verificarCliente = Cliente::where('telefono', $data['telefono'] ?? null)->first();
            // Validamos si existe el cliente
            if ($verificarCliente == null) {
                // Si no existe separamos el nombre y el numero de personas para el apartamento
				if (isset($data['alias']) && preg_match('/^(.*?)\n(\d+)\s*adulto(?:s)?/', $data['alias'], $matches)) {
                    // Establecemos el nombre y numero de adultos
					$nombre = trim($matches[1]);
					$num_adultos = $matches[2];

                    // Creamos el cliente
					$crearCliente = Cliente::create([
						'alias' => $nombre,
						'idiomas' => $data['idiomas'] ?? null,
						'telefono' => $data['telefono'] ?? null,
						'email_secundario' => $data['email'] ?? null,
					]);
					$cliente = $crearCliente;

				}else {
                    // Si existe creamos al cliente
					$crearCliente = Cliente::create([
						'alias' => $data['alias'] ?? 'Cliente',
						'idiomas' => $data['idiomas'] ?? null,
						'telefono' => $data['telefono'] ?? null,
						'email_secundario' => $data['email'] ?? null,
					]);
					$cliente = $crearCliente;
				}


            }else {
                // En caso que el cliente ya existe
                $cliente = $verificarCliente;
            }
            // Establece el idioma a español para reconocer 'jue' como 'jueves' y 'sep' como 'septiembre'
            $locale = 'es';
			Carbon::setLocale($locale);
            // Parseamos las Fechas
           	$fecha_entrada = Carbon::createFromFormat('Y-m-d', $data['fecha_entrada'] ?? date('Y-m-d'));
			$fecha_salida = Carbon::createFromFormat('Y-m-d', $data['fecha_salida'] ?? date('Y-m-d', strtotime('+1 day')));

            // Comprobamos el origen para obtener el ID del apartamento
                $origen = $data['origen'] ?? 'Web';
                if ($origen == 'Booking') {
                    // Si es booking lo obtenemos por el id del apartamento en booking
                    $apartamento = Apartamento::where('id_booking', $data['apartamento'] ?? null)->first();
                }
                else if($origen == 'Airbnb'){
                    // Si es de Airbnb lo obtenemos por el nombre del apartamento
                    $searchQuery = $request->input('apartamento');
                    $bestMatch = $this->findClosestMatch($searchQuery);

                    if ($bestMatch) {
                        $apartamento = $bestMatch;
                    }

                } else {
                    $apartamento = Apartamento::where('id_web', $data['apartamento'] ?? null)->first();

                }

                // Log warning if overlap (reservation still created - comes from OTA)
                \App\Services\ReservationValidationService::hasOverlap(
                    $apartamento->id,
                    $fecha_entrada->toDateString(),
                    $fecha_salida->toDateString(),
                    null,
                    'agregarReserva API'
                );

                // Formateamos el precio
                $precioOriginal = $data['precio'] ?? '0';
                $precioSinSimbolo = preg_replace('/[€\s]/', '', $precioOriginal);
                $precio = floatval($precioSinSimbolo);
                $roomType = RoomType::where('property_id', $apartamento->id)->first();

                // Creamos la Reserva dentro de una transacción
                DB::transaction(function () use ($data, $origen, $fecha_entrada, $fecha_salida, $precio, $apartamento, $cliente, $roomType, &$reserva) {
                    $crearReserva = Reserva::create([
                        'codigo_reserva' => $data['codigo_reserva'],
                        'room_type_id' => $roomType->id,
                        'origen' => $origen,
                        'fecha_entrada' =>  $fecha_entrada,
                        'fecha_salida' => $fecha_salida,
                        'precio' => $precio,
                        'apartamento_id' => $apartamento->id,
                        'cliente_id' => $cliente->id,
                        'estado_id' => 1,
                        //'numero_personas_plataforma' => $data['adultos'],
                        // 'numero_personas' => $data['numero_personas']
                    ]);
                    $reserva = $crearReserva;
                });

                // Si la reserva es de hoy y son más de las 14:00, intentar enviar claves por Channex
                \App\Console\Kernel::enviarClavesPorChannexSiEsNecesario($reserva);

                // Llamada al controlador ARIController para ejecutar fullSync
                $ariController = new \App\Http\Controllers\ARIController();  // Instanciar el ARIController
                $ariController->fullSync();  // Llamar a la función fullSync()
                return response('Registrado', 200);

        } else {
            return response('Ya existe la Reserva', 200);
        }

    }

    function levenshteinDistance($str1, $str2) {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        $matrix = [];

        for ($i = 0; $i <= $len1; $i++) {
            $matrix[$i][0] = $i;
        }

        for ($j = 0; $j <= $len2; $j++) {
            $matrix[0][$j] = $j;
        }

        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                if ($str1[$i - 1] == $str2[$j - 1]) {
                    $cost = 0;
                } else {
                    $cost = 1;
                }
                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,      // deletion
                    $matrix[$i][$j - 1] + 1,      // insertion
                    $matrix[$i - 1][$j - 1] + $cost  // substitution
                );
            }
        }

        return $matrix[$len1][$len2];
    }
    public function findClosestMatch($searchQuery) {
        // Obtener todos los nombres de apartamentos de la base de datos
        $apartments = Apartamento::all();

        $closestMatch = null;
        $shortestDistance = PHP_INT_MAX;

        foreach ($apartments as $apartment) {
            $distance = $this->levenshteinDistance($searchQuery, $apartment->nombre);
            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $closestMatch = $apartment;
            }
        }

        return $closestMatch;
    }

	public function cancelarAirBnb($reserva){
        // Conprobamos la reserva con el codigo de reserva
		$reserva = Reserva::where('codigo_reserva', $reserva)->first();
        // Si la reserva no existe
        if ($reserva== null) {
            return response('La reserva no existe', 404);
        }
        // Si la reserva existe
        // Cambiamos el estado a CAncelado
		$reserva->estado_id = 4;
		$reserva->save();

        return response('La reserva de ha cancelado', 200);

	}
	public function cancelarBooking($reserva){
        // return $reserva;
        // Conprobamos la reserva con el codigo de reserva
		$reservaCancelar = Reserva::where('codigo_reserva', $reserva)->first();

        if ($reservaCancelar != null && $reservaCancelar->estado_id === 4) {
            return response('La reserva ya esta cancelada', 201);
        }

        // Si la reserva no existe
        if ($reservaCancelar== null) {
            return response('La reserva no existe', 404);
        } else {
            // Cambiamos el estado a CAncelado
            $reservaCancelar->estado_id = 4;
            $reservaCancelar->save();
            return response('La reserva de ha cancelado', 200);
        }
	}


    public function getData() {
        $hoy = Carbon::now();
        $reservas = Reserva::whereDate('fecha_entrada', $hoy)
                ->where(function($query) {
                    $query->where('dni_entregado', true);
                })
                ->where(function($query) {
                    $query->where('enviado_webpol', false)
                        ->orWhereNull('enviado_webpol');
                })
                ->get();
        if (count($reservas) > 0) {
            foreach($reservas as $reserva){
                $reserva['cliente'] = $reserva->cliente;
            }
        }

        return response()->json($reservas, 200);
    }

    public function changeState(Request $request) {
        if (isset($request->id)) {

            $id = $request->id;
            $reserva = Reserva::find($id);
            $reserva->enviado_webpol = 1;
            $reserva->save();

        } else{

            return response()->json('No se encontro la propiedad ID en la petición.', 400);
        }


        return response()->json('Se actualizo el estado correctamente', 200);
    }

    public function facturarReservas(){

        $hoy = Carbon::now()->subDay(1); // La fecha actual
        $juevesPasado = Carbon::now()->subDays(5); // Restar 5 días para obtener el jueves de la semana pasada

        // Obtener reservas desde el jueves pasado hasta hoy (inclusive)
        $reservas = Reserva::whereDate('fecha_salida', '>=', $juevesPasado)
            ->whereDate('fecha_salida', '<=', $hoy)
            ->whereNotIn('estado_id', [5, 6]) // Filtrar estado_id diferente de 5 o 6
            ->get();
        foreach( $reservas as $reserva){
            // Cálculo correcto de la base imponible y el IVA
            // El precio de la reserva YA INCLUYE el IVA al 10%
            // Ejemplo: Si el precio es 180.00 € (con IVA incluido)
            // Base = 180.00 / 1.10 = 163.64 €
            // IVA = 180.00 - 163.64 = 16.36 €
            // Total = 180.00 € (precio original)
            $total = $reserva->precio; // Precio ya incluye IVA
            $base = $total / 1.10; // Descomponer el total en base imponible (IVA 10%)
            $iva = $total - $base; // Calcular el IVA

            $data = [
                'budget_id' => null,
                'cliente_id' => $reserva->cliente_id,
                'reserva_id' => $reserva->id,
                // 'invoice_status_id' => 1,
                'concepto' => 'Estancia en apartamento: '. $reserva->apartamento->titulo,
                'description' => '',
                'fecha' => $reserva->fecha_salida,
                'fecha_cobro' => null,
                'base' => round($base, 2),
                'iva' => round($iva, 2),
                'descuento' => null,
                'total' => round($total, 2),
            ];
            $crear = Invoices::create($data);
            $referencia = $this->generateBudgetReference($crear);
            $crear->reference = $referencia['reference'];
            $crear->reference_autoincrement_id = $referencia['id'];
            $crear->invoice_status_id = 1;
            // $crear->budget_status_id = 3;
            $crear->save();
            $reserva->estado_id = 5;
            $reserva->save();
            // return;

        }
        return response()->json($reservas);
    }

    public function generateReferenceTemp($reference){

        // Extrae los dos dígitos del final de la cadena usando expresiones regulares
        preg_match('/temp_(\d{2})/', $reference, $matches);
       // Incrementa el número primero
       if(count($matches) >= 1){
           $incrementedNumber = intval($matches[1]) + 1;
           // Asegura que el número tenga dos dígitos
           $formattedNumber = str_pad($incrementedNumber, 2, '0', STR_PAD_LEFT);
           // Concatena con la cadena "temp_"
           return "temp_" . $formattedNumber;
       }
   }

   private function generateReferenceDelete($reference){
        // Extrae los dos dígitos del final de la cadena usando expresiones regulares
        preg_match('/delete_(\d{2})/', $reference, $matches);
       // Incrementa el número primero
       if(count($matches) >= 1){
           $incrementedNumber = intval($matches[1]) + 1;
           // Asegura que el número tenga dos dígitos
           $formattedNumber = str_pad($incrementedNumber, 2, '0', STR_PAD_LEFT);
           // Concatena con la cadena "temp_"
           return "delete_" . $formattedNumber;
       }
   }

    public function generateBudgetReference(Invoices $invoices) {

       // Obtener la fecha actual del presupuesto
       $budgetCreationDate = $invoices->created_at ?? now();
       $datetimeBudgetCreationDate = new \DateTime($budgetCreationDate);

       // Formatear la fecha para obtener los componentes necesarios
       $year = $datetimeBudgetCreationDate->format('Y');
       $monthNum = $datetimeBudgetCreationDate->format('m');

       //dd($year, $monthNum, $budgetCreationDate, $datetimeBudgetCreationDate);
       // Buscar la última referencia autoincremental para el año y mes actual
       $latestReference = InvoicesReferenceAutoincrement::where('year', $year)
                           ->where('month_num', $monthNum)
                           ->orderBy('id', 'desc')
                           ->first();
        //dd($latestReference->reference_autoincrement);
       // Si no existe, empezamos desde 1, de lo contrario, incrementamos
       $newReferenceAutoincrement = $latestReference ? $latestReference->reference_autoincrement + 1 : 1;

       // Formatear el número autoincremental a 6 dígitos
       $formattedAutoIncrement = str_pad($newReferenceAutoincrement, 6, '0', STR_PAD_LEFT);

       // Crear la referencia
       $reference = $year . '/' . $monthNum . '/' . $formattedAutoIncrement;

       // Guardar o actualizar la referencia autoincremental en BudgetReferenceAutoincrement
       $referenceToSave = new InvoicesReferenceAutoincrement([
           'reference_autoincrement' => $newReferenceAutoincrement,
           'year' => $year,
           'month_num' => $monthNum,
           // Otros campos pueden ser asignados si son necesarios
       ]);
       $referenceToSave->save();

       // Devolver el resultado
       return [
           'id' => $referenceToSave->id,
           'reference' => $reference,
           'reference_autoincrement' => $newReferenceAutoincrement,
           'budget_reference_autoincrements' => [
               'year' => $year,
               'month_num' => $monthNum,
               // Añade aquí más si es necesario
           ],
       ];
   }

   public function getReservaIA($codigo){
        $reserva = Reserva::where('codigo_reserva', $codigo)->first();
        $data = [
            'codigo_reserva' => $reserva->codigo_reserva,
            'cliente' => $reserva->cliente->nombre == null ? $reserva->cliente->alias : $reserva->cliente->nombre .' ' . $reserva->cliente->apellido1,
            'apartamento' => $reserva->apartamento->titulo,
            'edificio' => $reserva->apartamento->edificioName->nombre,
            'fecha_entrada' => $reserva->fecha_entrada,
            'fecha_salida' => $reserva->fecha_salida,
        ];


        return response()->json($data);
   }


    // PRUEBAS CON LA INTELIGENCIA
    // PRUEBAS CON LA INTELIGENCIA
    public function probarIA(Request $request) {
        $mensaje = $request->input('texto');
        $contestacion = '';
        $response = '';

        // Verificar si el archivo de la conversación ya existe
        $filePath = storage_path('conversations/conversation.json');
        if (Storage::exists('conversations/conversation.json')) {
            $conversation = json_decode(Storage::get('conversations/conversation.json'), true);
        } else {
            $conversation = [];
        }

        if (isset($mensaje)) {
            $phone = '34622440984';

            // Obtener respuesta del servicio de IA
            $respuesta = $this->chatGptService->enviarMensajeAsistente($mensaje, $phone);

            // Añadir la nueva pregunta y respuesta al archivo JSON
            $conversation[] = [
                'pregunta' => $mensaje,
                'respuesta' => $respuesta
            ];

            // Guardar el archivo JSON actualizado
            Storage::put('conversations/conversation.json', json_encode($conversation));

            // Pasar la conversación a la vista junto con la respuesta
            return view('pruebasIA', compact('contestacion', 'response', 'conversation'));
        }

        // En caso de que no haya un mensaje, devolver la conversación previa
        return view('pruebasIA', compact('contestacion', 'conversation'));
    }
    public function mostrarInstrucciones()
    {
        // Verificar si el archivo existe
        if (Storage::exists('instrucciones.txt')) {
            $instrucciones = Storage::get('instrucciones.txt');
        } else {
            // Si no existe, creamos un archivo de ejemplo
            $instrucciones = "No hay instrucciones disponibles.";
            Storage::put('instrucciones.txt', $instrucciones);
        }

        // Retornar la vista con las instrucciones cargadas
        return response()->json(['instrucciones' => $instrucciones]);
    }

    public function guardarInstrucciones(Request $request)
    {
        $nuevasInstrucciones = $request->input('instrucciones');

        // Guardar las nuevas instrucciones en el archivo
        Storage::put('instrucciones.txt', $nuevasInstrucciones);

        return response()->json(['status' => 'Instrucciones actualizadas correctamente.']);
    }

    // public function chatGpt($mensaje, $id, $phone = null, $idMensaje)
    // {
    //     ini_set('max_execution_time', 200); // 300 segundos (5 minutos)

    //     $existeHilo = ChatGpt::find($idMensaje);
	// 	$mensajeAnterior = ChatGpt::where('remitente', $existeHilo->remitente)->get();

    //     if ($mensajeAnterior[1]->id_three == null) {
    //         //dd($existeHilo);
    //         $three_id = $this->crearHilo();
    //         //dd($three_id);
    //         $existeHilo->id_three = $three_id['id'];
    //         $existeHilo->save();
    //         $mensajeAnterior[1]->id_three = $three_id['id'];
    //         $mensajeAnterior[1]->save();
    //         //dd($existeHilo);
    //     } else {
    //         $three_id['id'] = $mensajeAnterior[1]->id_three;
    //         $existeHilo->id_three = $mensajeAnterior[1]->id_three;
    //         $existeHilo->save();
    //         $three_id['id'] = $existeHilo->id_three;
    //     }


    //     $hilo = $this->mensajeHilo($three_id['id'], $mensaje);
    //     // Independientemente de si el hilo es nuevo o existente, inicia la ejecución
    //     $ejecuccion = $this->ejecutarHilo($three_id['id']);
    //     // dd($ejecuccion);
    //     $ejecuccionStatus = $this->ejecutarHiloStatus($three_id['id'], $ejecuccion['id']);
    //     // dd($ejecuccionStatus,$ejecuccion);

    //     //dd($ejecuccionStatus);
    //     // Inicia un bucle para esperar hasta que el hilo se complete
    //     while (true) {
    //         //$ejecuccion = $this->ejecutarHilo($three_id['id']);

    //         if ($ejecuccionStatus['status'] === 'in_progress') {
    //             // Espera activa antes de verificar el estado nuevamente
    //             sleep(7); // Ajusta este valor según sea necesario

    //             // Verifica el estado del paso actual del hilo
    //             $pasosHilo = $this->ejecutarHiloISteeps($three_id['id'], $ejecuccion['id']);
    //             if ($pasosHilo['data'][0]['status'] === 'completed') {
    //                 // Si el paso se completó, verifica el estado general del hilo
    //                 $ejecuccionStatus = $this->ejecutarHiloStatus($three_id['id'],$ejecuccion['id']);
    //             }
    //         } elseif ($ejecuccionStatus['status'] === 'completed') {
    //             // El hilo ha completado su ejecución, obtiene la respuesta final
    //             $mensajes = $this->listarMensajes($three_id['id']);
    //             // dd($mensajes);
    //             if(count($mensajes['data']) > 0){
    //                 return $mensajes['data'][0]['content'][0]['text']['value'];
    //                 // return $mensajes['data'][0]['content'][0]['text'];
    //                 // return $mensajes;
    //                 // return json_encode($mensajes);
    //             }
    //         } else {
    //             // Maneja otros estados, por ejemplo, errores
    //             //dd($ejecuccionStatus);
    //             //return; // Sale del bucle si se encuentra un estado inesperado
    //         }
    //     }
    // }

    public function chatGpt($mensaje, $id, $phone = null, $idMensaje)
{
    ini_set('max_execution_time', 300); // Extiende el tiempo de ejecución

    $existeHilo = ChatGpt::find($idMensaje);
    $mensajeAnterior = ChatGpt::where('remitente', $existeHilo->remitente)->get();

    if ($mensajeAnterior[1]->id_three == null) {
        $three_id = $this->crearHilo();
        $existeHilo->id_three = $three_id['id'];
        $existeHilo->save();
        $mensajeAnterior[1]->id_three = $three_id['id'];
        $mensajeAnterior[1]->save();
    } else {
        $three_id['id'] = $mensajeAnterior[1]->id_three;
        $existeHilo->id_three = $mensajeAnterior[1]->id_three;
        $existeHilo->save();
        $three_id['id'] = $existeHilo->id_three;
    }

    $hilo = $this->mensajeHilo($three_id['id'], $mensaje);
    $ejecuccion = $this->ejecutarHilo($three_id['id']);
    $ejecuccionStatus = $this->ejecutarHiloStatus($three_id['id'], $ejecuccion['id']);

    $maxRetries = 5; // Número máximo de reintentos
    $retryCount = 0; // Contador de reintentos
    $timeoutSeconds = 180; // Máximo tiempo en segundos (3 minutos)
    $startTime = time(); // Tiempo de inicio

    // Bucle while con límite de tiempo y reintentos
    while (true) {
        if ((time() - $startTime) > $timeoutSeconds) {
            // Enviar una respuesta provisional al cliente
            return [
                "aviso" => false,
                "mensaje" => "Su consulta está siendo procesada, por favor espere unos momentos."
            ];
        }

        if ($retryCount >= $maxRetries) {
            // Enviar una respuesta provisional si se exceden los reintentos
            return [
                "aviso" => false,
                "mensaje" => "Estamos experimentando demoras, su respuesta llegará en breve."
            ];

             //"Estamos experimentando demoras, su respuesta llegará en breve.";
        }

        if ($ejecuccionStatus['status'] === 'in_progress') {
            sleep(7); // Pausa antes de la siguiente verificación
            $pasosHilo = $this->ejecutarHiloISteeps($three_id['id'], $ejecuccion['id']);

            if ($pasosHilo['data'][0]['status'] === 'completed') {
                $ejecuccionStatus = $this->ejecutarHiloStatus($three_id['id'], $ejecuccion['id']);
            }
        } elseif ($ejecuccionStatus['status'] === 'completed') {
            $mensajes = $this->listarMensajes($three_id['id']);
            if (count($mensajes['data']) > 0) {
                return $mensajes['data'][0]['content'][0]['text']['value'];
            }
        } else {
            // Incrementa el contador de reintentos
            $retryCount++;
            sleep(3); // Pausa entre reintentos
        }
    }
}

    public function crearHilo()
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads';

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v1"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Procesar la respuesta
        if ($response === false) {
            $response_data = json_decode($response, true);
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud: '.$response_data
            ];
            return $error;

        } else {
            $response_data = json_decode($response, true);
            //Storage::disk('local')->put('Respuesta_Peticion_ChatGPT-'.$id.'.txt', $response );
            return $response_data;
        }
    }
    public function recuperarHilo($id_thread)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'.$id_thread;

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v1"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Procesar la respuesta
        if ($response === false) {
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud'
            ];

        } else {
            $response_data = json_decode($response, true);
            // Storage::disk('local')->put('Respuesta_Peticion_ChatGPT-'.$id.'.txt', $response );
            return $response_data;
        }
    }
    public function ejecutarHilo($id_thread)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'.$id_thread.'/runs';

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v1"
        );

        $body = [
            "response_format" => [
                "type" => "json_object" // Especifica que quieres el formato de respuesta como JSON
            ],
            "assistant_id" => 'asst_tm1HTdOUuMtN20JhP9PDmUb2'
        ];
        // "assistant_id" => 'asst_zYokKNRE98fbjUsKpkSzmU9Y'
        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($body));

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);
        // Procesar la respuesta
        if ($response === false) {
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud'
            ];

        } else {
            $response_data = json_decode($response, true);
            return $response_data;
        }
    }
    public function mensajeHilo($id_thread, $pregunta)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'.$id_thread.'/messages';

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v1"
        );
        $body = [
            "role" => "user",
            "content" => $pregunta,
            // "response_format" => [
            //     "type" => "json_object" // Forzar que la respuesta sea en formato JSON
            // ]
        ];

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($body));


        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);
        // dd($response);
        // Procesar la respuesta
        if ($response === false) {
            $response_data = json_decode($response, true);
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud: '.$response_data
            ];
            return $error;

        } else {
            $response_data = json_decode($response, true);
            //Storage::disk('local')->put('Respuesta_Peticion_ChatGPT-'.$id.'.txt', $response );
            return $response_data;
        }
    }
    public function ejecutarHiloStatus($id_thread, $id_runs)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'. $id_thread .'/runs/'.$id_runs;

        $headers = array(
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v1"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Procesar la respuesta
        if ($response === false) {
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud'
            ];

        } else {
            $response_data = json_decode($response, true);
            return $response_data;
        }
    }
    public function ejecutarHiloISteeps($id_thread, $id_runs)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'.$id_thread. '/runs/' .$id_runs. '/steps';

        $headers = array(
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v1"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);

        // Procesar la respuesta
        if ($response === false) {
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud'
            ];

        } else {
            $response_data = json_decode($response, true);
            return $response_data;
        }
    }
    public function listarMensajes($id_thread)
    {
        $token = env('TOKEN_OPENAI', 'valorPorDefecto');
        $url = 'https://api.openai.com/v1/threads/'. $id_thread .'/messages';

        $headers = array(
            'Authorization: Bearer '. $token,
            "OpenAI-Beta: assistants=v1"
        );

        // Inicializar cURL y configurar las opciones
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($curl);
        curl_close($curl);
        // dd($response);

        // Procesar la respuesta
        if( $response === false ){
            $error = [
            'status' => 'error',
            'messages' => 'Error al realizar la solicitud'
            ];

        } else {
            $response_data = json_decode( $response, true );
            return $response_data;
        }
    }
    // public function enviarMensajeAsistente($mensaje, $asistenteId)
    // {
    //     try {
    //         // Enviar el mensaje al asistente con el ID específico
    //         $response = $this->client->chat()->create([
    //             'model' => 'gpt-3.5-turbo', // Modelo subyacente, aunque el asistente ya está preconfigurado
    //             'assistant' => $asistenteId, // ID del asistente
    //             'messages' => [
    //                 [
    //                     'role' => 'user',
    //                     'content' => $mensaje,
    //                 ],
    //             ],
    //         ]);

    //         return $this->parseResponse($response);

    //     } catch (\Exception $e) {
    //         // Manejo de errores
    //         return "Lo siento, ocurrió un error al procesar tu solicitud: " . $e->getMessage();
    //     }
    // }

    public function reservasCobradas(Request $request){

        $codigoReserva = $request->input('codigo_reserva');
        $reserva = Reserva::where('codigo_reserva', $codigoReserva)->first();

        $factura = Invoices::where('reserva_id', $reserva->id)->first();

        if ($factura !== null) {
            if ( $factura->invoice_status_id == 6) {
                return response()->json('Reserva ya esta en cobrada',200);
            }
            $factura->invoice_status_id = 6;
            $factura->fecha_cobro = Carbon::now();
            $factura->save();
            return response()->json('Añadido correctamente',200);
        }else {

            // Cálculo correcto de la base imponible y el IVA
            // El precio de la reserva YA INCLUYE el IVA al 10%
            // Ejemplo: Si el precio es 180.00 € (con IVA incluido)
            // Base = 180.00 / 1.10 = 163.64 €
            // IVA = 180.00 - 163.64 = 16.36 €
            // Total = 180.00 € (precio original)
            $total = $reserva->precio; // Precio ya incluye IVA
            $base = $total / 1.10; // Descomponer el total en base imponible (IVA 10%)
            $iva = $total - $base; // Calcular el IVA

            $data = [
                'budget_id' => null,
                'cliente_id' => $reserva->cliente_id,
                'reserva_id' => $reserva->reserva_id,
                'invoice_status_id' => 1,
                'concepto' => "Apartamento: ". $reserva->apartamento->titulo,
                'description' => null,
                'fecha' => $reserva->fecha_salida,
                'fecha_cobro' => Carbon::now(),
                'base' => round($base, 2),
                'iva' => round($iva, 2),
                'descuento' => isset($reserva->descuento) ? $reserva->descuento : null,
                'total' => round($total, 2),
            ];

            $crear = Invoices::create($data);
            $referencia = $this->generateBudgetReference($crear);
            $crear->reference = $referencia['reference'];
            $crear->reference_autoincrement_id = $referencia['id'];
            $crear->invoice_status_id = 6;
            $crear->save();

            return response()->json('Añadido correctamente',200);

        }
    }


    public function obtenerReservas(Request $request){
        $codigo = $request->codigo_reserva;
        $reserva = Reserva::where('codigo_reserva', $codigo)->first();
        if ($reserva) {
            $data = [
                'codigo_reserva' => $reserva->codigo_reserva,
                'cliente' => $reserva->cliente['nombre'] == null ? $reserva->cliente->alias : $reserva->cliente['nombre'] .' ' . $reserva->cliente['apellido1'],
                'apartamento' => $reserva->apartamento->titulo,
                'edificio' => isset($reserva->apartamento->edificioName->nombre) ? $reserva->apartamento->edificioName->nombre : 'Edificio Hawkins Suite',
                'fecha_entrada' => $reserva->fecha_entrada,
                'clave' => $reserva->apartamento->claves
            ];
            return response()->json($data, 200);
        }else {

            return response()->json('Error no se encontro reserva', 400);
        }
    }

    // public function obtenerReservasIA(Request $request)
    // {
    //     // Obtener la fecha y la hora actual
    //     $hoy = Carbon::now();
    //     $horaLimite = Carbon::createFromTime(14, 0, 0); // Hora límite: 14:00

    //     // Filtrar las reservas cuya fecha de entrada sea hoy o en el futuro
    //     $reservas = Reserva::whereDate('fecha_entrada', '>=', $hoy->toDateString())
    //         ->where(function ($query) use ($hoy, $horaLimite) {
    //             // Excluir las reservas cuya fecha de salida sea hoy y la hora sea mayor a las 14:00
    //             $query->whereDate('fecha_salida', '>', $hoy->toDateString())
    //                 ->orWhere(function ($query) use ($hoy, $horaLimite) {
    //                     $query->whereDate('fecha_salida', $hoy->toDateString())
    //                         ->whereTime('fecha_salida', '<', $horaLimite->toTimeString());
    //                 });
    //         })
    //         ->get();

    //     // Verificar si hay reservas y formatear los datos para la respuesta
    //     if ($reservas->isNotEmpty()) {
    //         $data = $reservas->map(function ($reserva) {
    //             return [
    //                 'codigo_reserva' => $reserva->codigo_reserva,
    //                 'cliente' => $reserva->cliente['nombre'] == null ? $reserva->cliente->alias : $reserva->cliente['nombre'] . ' ' . $reserva->cliente['apellido1'],
    //                 'apartamento' => $reserva->apartamento->titulo,
    //                 'edificio' => isset($reserva->apartamento->edificioName->nombre) ? $reserva->apartamento->edificioName->nombre : 'Edificio Hawkins Suite',
    //                 'fecha_entrada' => $reserva->fecha_entrada,
    //                 'clave' => $reserva->apartamento->claves
    //             ];
    //         });

    //         return response()->json($data, 200);
    //     } else {
    //         return response()->json('Error, no se encontraron reservas', 400);
    //     }
    // }
    public function obtenerReservasIA(Request $request)
    {
        // Obtener la fecha y la hora actual
        $hoy = Carbon::now();
        $horaLimite = Carbon::createFromTime(14, 0, 0); // Hora límite: 14:00

        // Filtrar las reservas cuya fecha de entrada sea hoy
        $reservas = Reserva::whereDate('fecha_entrada', $hoy->toDateString())
            ->where(function ($query) use ($hoy, $horaLimite) {
                // Excluir las reservas cuya fecha de salida sea hoy y la hora sea mayor a las 14:00
                $query->whereDate('fecha_salida', '>', $hoy->toDateString())
                    ->orWhere(function ($query) use ($hoy, $horaLimite) {
                        $query->whereDate('fecha_salida', $hoy->toDateString())
                            ->whereTime('fecha_salida', '<', $horaLimite->toTimeString());
                    });
            })
            ->get();

        // Verificar si hay reservas y formatear los datos para la respuesta
        if ($reservas->isNotEmpty()) {
            $data = $reservas->map(function ($reserva) {
                return [
                    'codigo_reserva' => $reserva->codigo_reserva,
                    'cliente' => $reserva->cliente['nombre'] == null ? $reserva->cliente->alias : $reserva->cliente['nombre'] . ' ' . $reserva->cliente['apellido1'],
                    'apartamento' => $reserva->apartamento->titulo,
                    'edificio' => isset($reserva->apartamento->edificioName->nombre) ? $reserva->apartamento->edificioName->nombre : 'Edificio Hawkins Suite',
                    'fecha_entrada' => $reserva->fecha_entrada,
                    'clave' => $reserva->apartamento->claves
                ];
            });

            return response()->json($data, 200);
        } else {
            return response()->json('Error, no se encontraron reservas para hoy', 400);
        }
    }

    public function avisarAveria(Request $request){
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites
        Storage::disk('publico')->put("aviso-tecnico_{$fecha}.txt", json_encode($request->all()));
        return response()->json('Hemos avisado al tecnico para una averia.', 200);
    }
    public function avisarLimpieza(Request $request){
        $fecha = Carbon::now()->format('Y-m-d_H-i-s'); // Puedes cambiar el formato según lo que necesites
        Storage::disk('publico')->put("aviso-limpieza_{$fecha}.txt", json_encode($request->all()));

        return response()->json('Hemos avisado al equipo de limpieza.', 200);
    }

    public function getApartamentosOcupacion()
    {
        try {
            // Obtener TODOS los apartamentos (misma lógica que el dashboard)
            $apartamentos = Apartamento::whereNotNull('edificio_id')
                ->whereNotNull('id_channex')
                ->with(['reservas' => function($query) {
                    $query->where('fecha_salida', '>=', Carbon::today())
                          ->where('fecha_entrada', '<=', Carbon::today()->addDays(30)) // Próximos 30 días
                          ->where('deleted_at', null)
                          ->where('estado_id', '!=', 4); // Excluir canceladas
                }])->get();

            // Formatear los datos para el frontend
            $apartamentosData = $apartamentos->map(function($apartamento) {
                return [
                    'id' => $apartamento->id,
                    'nombre' => $apartamento->nombre,
                    'reservas' => $apartamento->reservas->map(function($reserva) {
                        return [
                            'id' => $reserva->id,
                            'fecha_entrada' => $reserva->fecha_entrada,
                            'fecha_salida' => $reserva->fecha_salida,
                            'cliente_alias' => $reserva->cliente->alias ?? 'Sin cliente',
                            'codigo_reserva' => $reserva->codigo_reserva ?? 'Sin código'
                        ];
                    })
                ];
            });

            return response()->json($apartamentosData);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener apartamentos: ' . $e->getMessage()], 500);
        }
    }

}

