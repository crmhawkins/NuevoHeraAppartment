<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Photo;

class DNIScannerController extends Controller
{
    /**
     * Verificar datos del cliente y mostrar opciones
     */
    public function index($token)
    {
        try {
            // Obtener la reserva por token con relaciones
            $reserva = Reserva::with(['apartamento', 'cliente', 'estado'])
                ->where('token', $token)
                ->first();
            
            if (!$reserva) {
                Log::warning('Token de reserva no encontrado', ['token' => $token]);
                abort(404, 'Reserva no encontrada');
            }
            
            // Obtener el cliente y recargar para obtener el idioma actualizado
            $cliente = $reserva->cliente;
            
            if (!$cliente) {
                Log::error('Cliente no encontrado para la reserva', ['reserva_id' => $reserva->id]);
                abort(404, 'Cliente no encontrado');
            }
            
            // Recargar el cliente para obtener el idioma actualizado
            $cliente->refresh();
            
            // Verificar si esta reserva ya tiene DNI entregado.
            // Usamos reserva->dni_entregado (estado por reserva) y no cliente->data_dni (estado global del cliente)
            // para evitar saltar a "gracias" en nuevas reservas del mismo cliente.
            if (!empty($reserva->dni_entregado)) {
                Log::info('Reserva ya tiene DNI entregado', [
                    'reserva_id' => $reserva->id,
                    'cliente_id' => $cliente->id,
                    'dni_entregado' => $reserva->dni_entregado
                ]);
                return redirect()->route('gracias.index', $cliente->idioma ? $cliente->idioma : 'es');
            }
            
            // Establecer el idioma de la aplicación
            // Prioridad: query param (para forzar) > sesión > cliente > default
            $locale = request()->query('lang');
            
            if (!$locale) {
                $locale = session('locale');
            }
            
            // Si no hay en sesión, usar el del cliente
            if (!$locale && $cliente->idioma) {
                $locale = $cliente->idioma;
                // Actualizar la sesión con el idioma del cliente para futuras cargas
                session(['locale' => $locale]);
                session()->save();
            }
            
            // Si aún no hay idioma, usar español por defecto
            if (!$locale) {
                $locale = 'es';
                session(['locale' => $locale]);
                session()->save();
            }
            
            // Establecer el locale en la aplicación
            \App::setLocale($locale);
            
            // Asegurar que la sesión esté sincronizada
            if (session('locale') !== $locale) {
                session(['locale' => $locale]);
                session()->save();
            }
            
            Log::info('Idioma establecido para la página', [
                'locale_final' => $locale,
                'query_lang' => request()->query('lang'),
                'session_locale' => session('locale'),
                'cliente_idioma' => $cliente->idioma,
                'app_locale' => \App::getLocale(),
                'session_id' => session()->getId()
            ]);
            
            Log::info('Mostrando opciones de verificación DNI', [
                'reserva_id' => $reserva->id,
                'cliente_id' => $cliente->id,
                'token' => $token,
                'locale' => $locale
            ]);
            
            return view('dni.index', compact('reserva', 'cliente', 'token', 'locale'));
            
        } catch (\Exception $e) {
            Log::error('Error en index de DNI: ' . $e->getMessage(), [
                'token' => $token,
                'error' => $e->getTraceAsString()
            ]);
            abort(500, 'Error interno del servidor');
        }
    }
    
    /**
     * Verificar si el cliente tiene datos completos para MIR
     * Basado en los campos obligatorios definidos en ReservaPagoController::verificarDatosMIR()
     */
    private function verificarDatosCompletos($cliente)
    {
        // Campos obligatorios para MIR según ReservaPagoController::verificarDatosMIR()
        $camposRequeridos = [
            'nombre' => 'Nombre',
            'apellido1' => 'Primer Apellido',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'nacionalidadStr' => 'Nacionalidad',
            'tipo_documento' => 'Tipo de Documento',
            'num_identificacion' => 'Número de Identificación',
            'fecha_expedicion_doc' => 'Fecha de Expedición del Documento',
            'sexo' => 'Sexo',
            'email' => 'Email',
            'telefono_movil' => 'Teléfono Móvil',
            'provincia' => 'Provincia',
        ];
        
        $datosFaltantes = [];
        foreach ($camposRequeridos as $campo => $nombre) {
            if (empty($cliente->$campo)) {
                $datosFaltantes[] = $nombre;
            }
        }
        
        // Si hay datos faltantes, loggear y retornar false
        if (!empty($datosFaltantes)) {
            Log::warning('Datos incompletos para MIR', [
                'cliente_id' => $cliente->id,
                'datos_faltantes' => $datosFaltantes,
                'campos_vacios' => array_keys(array_filter($camposRequeridos, function($campo) use ($cliente) {
                    return empty($cliente->$campo);
                }, ARRAY_FILTER_USE_KEY))
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Mostrar el scanner de DNI (cámara)
     */
    public function showScanner($token)
    {
        try {
            // Obtener la reserva por token con relaciones
            $reserva = Reserva::with(['apartamento', 'cliente'])->where('token', $token)->first();
            
            if (!$reserva) {
                Log::warning('Token de reserva no encontrado', ['token' => $token]);
                abort(404, 'Reserva no encontrada');
            }
            
            // Obtener el cliente
            $cliente = $reserva->cliente;
            
            if (!$cliente) {
                Log::error('Cliente no encontrado para la reserva', ['reserva_id' => $reserva->id]);
                abort(404, 'Cliente no encontrado');
            }
            
            // Verificar si esta reserva ya tiene DNI entregado
            if (!empty($reserva->dni_entregado)) {
                Log::info('Reserva ya tiene DNI entregado', [
                    'reserva_id' => $reserva->id,
                    'cliente_id' => $cliente->id,
                    'dni_entregado' => $reserva->dni_entregado
                ]);
                return redirect()->route('gracias.index', $cliente->idioma ? $cliente->idioma : 'es');
            }
            
            // Establecer el idioma de la aplicación
            $locale = session('locale', $cliente->idioma ?? 'es');
            \App::setLocale($locale);
            
            // Obtener huéspedes de la reserva
            $huespedes = \App\Models\Huesped::where('reserva_id', $reserva->id)->get();
            
            // Calcular número de adultos (1 cliente principal + huéspedes)
            $numeroAdultos = max(1, $reserva->numero_personas ?? 1);
            
            Log::info('Mostrando scanner de DNI', [
                'reserva_id' => $reserva->id,
                'cliente_id' => $cliente->id,
                'token' => $token,
                'locale' => $locale,
                'numero_adultos' => $numeroAdultos
            ]);
            
            return view('dni.scanner', compact('reserva', 'cliente', 'token', 'locale', 'huespedes', 'numeroAdultos'));
            
        } catch (\Exception $e) {
            Log::error('Error mostrando scanner de DNI: ' . $e->getMessage(), [
                'token' => $token,
                'error' => $e->getTraceAsString()
            ]);
            abort(500, 'Error interno del servidor');
        }
    }
    
    /**
     * Mostrar formulario de subida de imágenes
     */
    public function showUpload($token)
    {
        try {
            // Obtener la reserva por token con relaciones
            $reserva = Reserva::with(['apartamento', 'cliente'])->where('token', $token)->first();
            
            if (!$reserva) {
                Log::warning('Token de reserva no encontrado', ['token' => $token]);
                abort(404, 'Reserva no encontrada');
            }
            
            // Obtener el cliente
            $cliente = $reserva->cliente;
            
            if (!$cliente) {
                Log::error('Cliente no encontrado para la reserva', ['reserva_id' => $reserva->id]);
                abort(404, 'Cliente no encontrado');
            }
            
            // Establecer el idioma de la aplicación
            $locale = session('locale', $cliente->idioma ?? 'es');
            \App::setLocale($locale);
            
            // Verificar si esta reserva ya tiene DNI entregado
            if (!empty($reserva->dni_entregado)) {
                Log::info('Reserva ya tiene DNI entregado', [
                    'reserva_id' => $reserva->id,
                    'cliente_id' => $cliente->id,
                    'dni_entregado' => $reserva->dni_entregado
                ]);
                return redirect()->route('gracias.index', $cliente->idioma ? $cliente->idioma : 'es');
            }
            
            // Obtener huéspedes de la reserva
            $huespedes = \App\Models\Huesped::where('reserva_id', $reserva->id)->get();
            
            // Calcular número de adultos (1 cliente principal + huéspedes)
            $numeroAdultos = max(1, $reserva->numero_personas ?? 1);
            
            Log::info('Mostrando formulario de subida de DNI', [
                'reserva_id' => $reserva->id,
                'cliente_id' => $cliente->id,
                'token' => $token,
                'numero_adultos' => $numeroAdultos,
                'huespedes_count' => $huespedes->count(),
                'locale' => $locale
            ]);
            
            return view('dni.upload', compact('reserva', 'cliente', 'token', 'huespedes', 'numeroAdultos', 'locale'));
            
        } catch (\Exception $e) {
            Log::error('Error mostrando formulario de subida: ' . $e->getMessage(), [
                'token' => $token,
                'error' => $e->getTraceAsString()
            ]);
            abort(500, 'Error interno del servidor');
        }
    }
    
    /**
     * Procesar una imagen individual al subirla
     */
    public function processSingleImage(Request $request, $token)
    {
        // Forzar respuesta JSON siempre
        $request->headers->set('Accept', 'application/json');
        
        try {
            // Obtener la reserva por token
            $reserva = Reserva::where('token', $token)->with('cliente')->first();
            
            if (!$reserva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }
            
            // Validar que hay archivo
            if (!$request->hasFile('image')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se ha subido ninguna imagen'
                ], 400);
            }
            
            $file = $request->file('image');
            $side = $request->input('side'); // 'front' o 'rear'
            $personaIndex = $request->input('persona_index', 0);
            $personaTipo = $request->input('persona_tipo', 'cliente');
            $personaId = $request->input('persona_id');
            
            // Validar archivo
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo inválido'
                ], 400);
            }
            
            // Validar tamaño del archivo (máximo 10MB)
            if ($file->getSize() > 10 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo es demasiado grande. Máximo 10MB.'
                ], 400);
            }
            
            // Validar tipo de archivo
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de archivo no permitido. Solo se permiten imágenes JPEG, PNG o WEBP.'
                ], 400);
            }
            
            // Determinar persona y si es huésped nuevo (aún no creado)
            $esHuespedNuevo = false;
            $persona = null;
            
            if ($personaTipo === 'cliente') {
                $persona = $reserva->cliente;
                if (!$persona) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cliente no encontrado'
                    ], 404);
                }
            } else {
                // Para huéspedes: si existe, usarlo; si no, procesar temporalmente
                if ($personaId) {
                    $persona = \App\Models\Huesped::find($personaId);
                }
                
                // Si no existe el huésped, marcarlo como nuevo (se creará al enviar el formulario)
                if (!$persona) {
                    $esHuespedNuevo = true;
                    // Usar un ID temporal para guardar la imagen
                    $personaId = $personaId ?? 'temp_' . $personaIndex . '_' . time();
                    Log::info('Procesando imagen para huésped nuevo (aún no creado)', [
                        'persona_index' => $personaIndex,
                        'persona_id_temp' => $personaId,
                        'side' => $side
                    ]);
                }
            }
            
            // Guardar imagen (usar ID temporal si es huésped nuevo)
            $imagePersonaId = $esHuespedNuevo ? $personaId : $persona->id;
            
            try {
                $imagePath = $this->guardarImagen($file, $imagePersonaId, $side, $personaTipo);
            } catch (\Exception $e) {
                Log::error('Error guardando imagen temporal', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la imagen: ' . $e->getMessage()
                ], 500);
            }
            
            // Procesar con IA
            $result = $this->sendToAI($imagePath, $side);
            
            if ($result['success']) {
                // Loggear datos antes de validar para debugging
                Log::info('Datos recibidos de IA antes de validar', [
                    'persona_tipo' => $personaTipo,
                    'persona_id' => $esHuespedNuevo ? $personaId : $persona->id,
                    'es_huesped_nuevo' => $esHuespedNuevo,
                    'side' => $side,
                    'data' => $result['data'],
                    'data_keys' => array_keys($result['data'] ?? [])
                ]);
                
                // Validar que los datos extraídos sean válidos (no datos por defecto)
                $validationResult = $this->validateExtractedData($result['data'], $side);
                if (!$validationResult) {
                    Log::warning('Datos extraídos inválidos o por defecto detectados', [
                        'persona_tipo' => $personaTipo,
                        'persona_id' => $esHuespedNuevo ? $personaId : $persona->id,
                        'side' => $side,
                        'data' => $result['data'],
                        'validation_failed' => true
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudieron extraer los datos del documento. Por favor, intenta de nuevo o envía las imágenes por WhatsApp.',
                        'error_type' => 'invalid_data',
                        'ai_response' => $result['data'] ?? null,
                        'ai_raw_response' => $result['ai_raw_response'] ?? null
                    ], 400);
                }
                
                // Si es huésped nuevo, guardar datos temporalmente en sesión
                if ($esHuespedNuevo) {
                    $this->guardarDatosTemporales($reserva->token, $personaIndex, $side, $result['data'], $imagePath);
                    Log::info('Datos temporales guardados para huésped nuevo', [
                        'token' => $reserva->token,
                        'persona_index' => $personaIndex,
                        'side' => $side
                    ]);
                } else {
                    // Si el huésped existe, guardar datos directamente
                    $this->saveExtractedData($reserva, $persona, $side, $result['data'], $personaTipo);
                    // Recargar persona para obtener datos actualizados
                    $persona->refresh();
                }
                
                // Preparar datos para respuesta - incluir TODOS los campos extraídos
                // Usar datos de la IA directamente (siempre disponibles, incluso para huéspedes nuevos)
                $extractedData = [];
                $rawData = $result['data'];
                
                if ($side === 'front') {
                    // Datos del frontal - usar datos de la IA directamente
                    if ($personaTipo === 'cliente') {
                        $extractedData = [
                            'nombre' => $rawData['nombre'] ?? ($persona ? $persona->nombre : ''),
                            'apellido1' => $rawData['apellido1'] ?? ($persona ? $persona->apellido1 : ''),
                            'apellido2' => $rawData['apellido2'] ?? ($persona ? ($persona->apellido2 ?? '') : ''),
                            'num_identificacion' => $rawData['dni'] ?? ($persona ? $persona->num_identificacion : ''),
                            'fecha_nacimiento' => $rawData['fecha_nacimiento'] ?? ($persona ? $persona->fecha_nacimiento : ''),
                            'sexo' => $rawData['sexo'] ?? ($persona ? $persona->sexo : ''),
                            'fecha_expedicion_doc' => $rawData['fecha_expedicion'] ?? ($persona ? $persona->fecha_expedicion_doc : ''),
                            'fecha_caducidad' => $rawData['fecha_caducidad'] ?? null,
                            'lugar_nacimiento' => $rawData['lugar_nacimiento'] ?? null,
                            'nacionalidad' => $rawData['nacionalidad'] ?? ($persona ? ($persona->nacionalidadStr ?? $persona->nacionalidad) : ''),
                            'tipo_documento' => $rawData['tipo_documento'] ?? ($persona ? ($persona->tipo_documento_str ?? 'DNI') : 'DNI')
                        ];
                    } else {
                        // Para huéspedes (existentes o nuevos)
                        $extractedData = [
                            'nombre' => $rawData['nombre'] ?? ($persona ? $persona->nombre : ''),
                            'primer_apellido' => $rawData['apellido1'] ?? ($persona ? $persona->primer_apellido : ''),
                            'segundo_apellido' => $rawData['apellido2'] ?? ($persona ? ($persona->segundo_apellido ?? '') : ''),
                            'numero_identificacion' => $rawData['dni'] ?? ($persona ? $persona->numero_identificacion : ''),
                            'fecha_nacimiento' => $rawData['fecha_nacimiento'] ?? ($persona ? $persona->fecha_nacimiento : ''),
                            'sexo' => $rawData['sexo'] ?? ($persona ? $persona->sexo : ''),
                            'fecha_expedicion' => $rawData['fecha_expedicion'] ?? ($persona ? $persona->fecha_expedicion : ''),
                            'fecha_caducidad' => $rawData['fecha_caducidad'] ?? null,
                            'lugar_nacimiento' => $rawData['lugar_nacimiento'] ?? null,
                            'nacionalidad' => $rawData['nacionalidad'] ?? ($persona ? ($persona->nacionalidadStr ?? $persona->nacionalidad) : ''),
                            'tipo_documento' => $rawData['tipo_documento'] ?? ($persona ? ($persona->tipo_documento_str ?? 'DNI') : 'DNI')
                        ];
                    }
                } else if ($side === 'rear') {
                    // Datos del reverso (dirección y lugar de nacimiento) - usar datos de la IA directamente
                    $extractedData = [
                        'direccion' => $rawData['direccion'] ?? ($persona ? ($persona->direccion ?? '') : ''),
                        'localidad' => $rawData['localidad'] ?? ($persona ? ($persona->localidad ?? '') : ''),
                        'codigo_postal' => $rawData['codigo_postal'] ?? ($persona ? ($persona->codigo_postal ?? '') : ''),
                        'provincia' => $rawData['provincia'] ?? ($persona ? ($persona->provincia ?? '') : ''),
                        'lugar_nacimiento' => $rawData['lugar_nacimiento'] ?? null
                    ];
                }
                
                Log::info('Imagen procesada exitosamente', [
                    'persona_tipo' => $personaTipo,
                    'persona_id' => $esHuespedNuevo ? $personaId : ($persona ? $persona->id : null),
                    'es_huesped_nuevo' => $esHuespedNuevo,
                    'side' => $side,
                    'data_extracted' => !empty($extractedData),
                    'extracted_fields' => array_keys($extractedData),
                    'tiene_lugar_nacimiento' => isset($extractedData['lugar_nacimiento']) && !empty($extractedData['lugar_nacimiento']),
                    'lugar_nacimiento_valor' => $extractedData['lugar_nacimiento'] ?? null
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Imagen procesada correctamente',
                    'side' => $side,
                    'extracted_data' => $extractedData,
                    'persona_index' => $personaIndex
                ]);
            } else {
                Log::error('Error procesando imagen con IA', [
                    'persona_tipo' => $personaTipo,
                    'persona_id' => $persona->id,
                    'side' => $side,
                    'error' => $result['error'] ?? 'Error desconocido',
                    'error_type' => $result['error_type'] ?? 'unknown',
                    'ai_url' => $result['ai_url'] ?? 'N/A'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Error al procesar el documento con IA. Por favor, intenta de nuevo. Si el problema persiste, envía las imágenes del DNI por WhatsApp.',
                    'error' => $result['error'] ?? 'Error desconocido',
                    'error_type' => $result['error_type'] ?? 'ai_error',
                    'ai_url' => $result['ai_url'] ?? 'N/A',
                    'http_status' => $result['http_status'] ?? null,
                    'whatsapp_message' => 'Puedes enviar las imágenes del DNI por WhatsApp y las procesaremos manualmente.'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Error procesando imagen individual: ' . $e->getMessage(), [
                'token' => $token,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Obtener información del modelo de IA para debugging
            $model = env('HAWKINS_AI_MODEL', 'qwen2.5vl:latest');
            $baseUrl = env('HAWKINS_AI_URL', 'https://192.168.1.45');
            $aiUrl = rtrim($baseUrl, '/') . '/chat/analyze-image';
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor. Por favor, intenta de nuevo o envía las imágenes por WhatsApp.',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'error_type' => 'server_error',
                'debug_info' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'ai_model' => $model,
                    'ai_url' => $aiUrl
                ] : null
            ], 500);
        } catch (\Throwable $e) {
            // Capturar también errores fatales de PHP
            Log::error('Error fatal procesando imagen individual: ' . $e->getMessage(), [
                'token' => $token,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor. Por favor, intenta de nuevo o envía las imágenes por WhatsApp.',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'error_type' => 'fatal_error'
            ], 500);
        }
    }
    
    /**
     * Guardar datos adicionales (una vez procesadas las imágenes)
     */
    public function saveAdditionalData(Request $request, $token)
    {
        try {
            // Obtener la reserva por token
            $reserva = Reserva::where('token', $token)->with('cliente')->first();
            
            if (!$reserva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }
            
            $cliente = $reserva->cliente;
            $numeroAdultos = max(1, $reserva->numero_personas ?? 1);
            
            // Cargar huéspedes directamente desde la base de datos
            $huespedes = \App\Models\Huesped::where('reserva_id', $reserva->id)->get();
            
            $procesados = 0;
            $errores = [];
            
            // Procesar cada adulto
            for ($index = 0; $index < $numeroAdultos; $index++) {
                try {
                    $personaTipo = $request->input("persona_tipo.{$index}", 'cliente');
                    $personaId = $request->input("persona_id.{$index}", $cliente->id);
                    
                    // Determinar persona
                    if ($personaTipo === 'cliente') {
                        $persona = $cliente;
                    } else {
                        $persona = \App\Models\Huesped::find($personaId);
                        if (!$persona) {
                            $errores[] = "Adulto " . ($index + 1) . ": Huésped no encontrado";
                            continue;
                        }
                    }
                    
                    // Guardar datos adicionales
                    // Cliente principal (índice 0): ambos campos obligatorios
                    // Acompañantes (índice > 0): al menos uno de los dos según Real Decreto 933/2021
                    $datosAdicionales = [];
                    
                    $telefono = $request->input("telefono_movil.{$index}", '');
                    $email = $request->input("email.{$index}", '');
                    
                    if ($index === 0) {
                        // Cliente principal: ambos campos obligatorios
                        if (empty($telefono)) {
                            $errores[] = "Cliente Principal: El teléfono móvil es obligatorio";
                            continue;
                        }
                        if (empty($email)) {
                            $errores[] = "Cliente Principal: El email es obligatorio";
                            continue;
                        }
                        // Validar formato de email
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $errores[] = "Cliente Principal: El formato del email no es válido";
                            continue;
                        }
                        $datosAdicionales['telefono_movil'] = $telefono;
                        $datosAdicionales['email'] = $email;
                    } else {
                        // Acompañantes: al menos uno de los dos
                        if (empty($telefono) && empty($email)) {
                            $errores[] = "Adulto " . ($index + 1) . ": Es obligatorio proporcionar al menos un método de contacto (teléfono o email) según la legislación española (Real Decreto 933/2021)";
                            continue;
                        }
                        
                        if (!empty($telefono)) {
                            $datosAdicionales['telefono_movil'] = $telefono;
                        }
                        
                        if (!empty($email)) {
                            // Validar formato de email si se proporciona
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $errores[] = "Adulto " . ($index + 1) . ": El formato del email no es válido";
                                continue;
                            }
                            $datosAdicionales['email'] = $email;
                        }
                    }
                    
                    // Dirección puede venir de campos ocultos (extraída del DNI) o de campos manuales
                    // Priorizar campos manuales si están disponibles (el usuario los completó)
                    $direccion = $request->input("direccion_manual.{$index}") ?: $request->input("direccion.{$index}", '');
                    if (!empty($direccion)) {
                        $datosAdicionales['direccion'] = $direccion;
                    }
                    
                    $localidad = $request->input("localidad_manual.{$index}") ?: $request->input("localidad.{$index}", '');
                    if (!empty($localidad)) {
                        $datosAdicionales['localidad'] = $localidad;
                    }
                    
                    // Código postal puede venir de codigo_postal_manual o codigo_postal (hidden)
                    $codigoPostal = $request->input("codigo_postal_manual.{$index}", '');
                    if (empty($codigoPostal)) {
                        $codigoPostal = $request->input("codigo_postal.{$index}", '');
                    }
                    // Código postal es obligatorio según Real Decreto 933/2021 para TODOS los huéspedes (cliente principal y acompañantes)
                    if (empty($codigoPostal)) {
                        $personaNombre = $index === 0 ? 'Cliente Principal' : "Adulto " . ($index + 1);
                        $errores[] = "{$personaNombre}: El código postal es obligatorio según la legislación española (Real Decreto 933/2021) para todos los huéspedes";
                        continue;
                    }
                    if (!empty($codigoPostal)) {
                        $datosAdicionales['codigo_postal'] = $codigoPostal;
                    }
                    
                    $provincia = $request->input("provincia_manual.{$index}") ?: $request->input("provincia.{$index}", '');
                    if (!empty($provincia)) {
                        $datosAdicionales['provincia'] = $provincia;
                    }
                    
                    // Lugar de nacimiento manual (si no se extrajo del documento)
                    $lugarNacimiento = $request->input("lugar_nacimiento_manual.{$index}", '');
                    if (!empty($lugarNacimiento)) {
                        $datosAdicionales['lugar_nacimiento'] = trim($lugarNacimiento);
                        Log::info('Lugar de nacimiento manual proporcionado', [
                            'persona_tipo' => $personaTipo,
                            'persona_id' => $persona->id,
                            'lugar_nacimiento' => $lugarNacimiento
                        ]);
                    }
                    
                    // Solo para huéspedes: relación de parentesco
                    if ($personaTipo === 'huesped') {
                        $relacionParentesco = $request->input("relacion_parentesco.{$index}", '');
                        if (!empty($relacionParentesco)) {
                            $datosAdicionales['relacion_parentesco'] = $relacionParentesco;
                        }
                    }
                    
                    // Actualizar persona con datos adicionales
                    if (!empty($datosAdicionales)) {
                        Log::info('Intentando guardar datos adicionales', [
                            'persona_tipo' => $personaTipo,
                            'persona_id' => $persona->id,
                            'index' => $index,
                            'datos' => $datosAdicionales,
                            'request_codigo_postal' => $request->input("codigo_postal.{$index}"),
                            'request_codigo_postal_manual' => $request->input("codigo_postal_manual.{$index}"),
                            'request_relacion_parentesco' => $request->input("relacion_parentesco.{$index}")
                        ]);
                        
                        $persona->update($datosAdicionales);
                        
                        // Recargar para verificar que se guardó
                        $persona->refresh();
                        
                        Log::info('Datos adicionales guardados', [
                            'persona_tipo' => $personaTipo,
                            'persona_id' => $persona->id,
                            'index' => $index,
                            'datos_guardados' => $datosAdicionales,
                            'codigo_postal_guardado' => $persona->codigo_postal,
                            'relacion_parentesco_guardado' => $persona->relacion_parentesco ?? 'N/A'
                        ]);
                    } else {
                        Log::warning('No hay datos adicionales para guardar', [
                            'persona_tipo' => $personaTipo,
                            'persona_id' => $persona->id,
                            'index' => $index
                        ]);
                    }
                    
                    // Guardar fotos permanentemente (no interrumpir si falla)
                    $fotosGuardadas = [];
                    try {
                        $fotosGuardadas = $this->guardarFotosPermanentes($reserva, $persona, $personaTipo, $index, $token);
                        Log::info('Resultado de guardar fotos permanentes', [
                            'persona_tipo' => $personaTipo,
                            'persona_id' => $persona->id ?? null,
                            'index' => $index,
                            'fotos_guardadas' => $fotosGuardadas
                        ]);
                    } catch (\Exception $photoError) {
                        // Loggear error pero continuar con el proceso
                        Log::error('Error guardando fotos permanentes (continuando proceso)', [
                            'persona_tipo' => $personaTipo,
                            'persona_id' => $persona->id ?? null,
                            'index' => $index,
                            'error' => $photoError->getMessage(),
                            'trace' => $photoError->getTraceAsString()
                        ]);
                        // No lanzar excepción para que el proceso continúe
                    }
                    
                    // Marcar como completado si es cliente
                    if ($personaTipo === 'cliente') {
                        // Recargar el cliente para obtener los datos más recientes
                        $persona->refresh();
                        
                        // SIEMPRE marcar dni_entregado = true cuando se sube el DNI (independientemente de datos completos)
                        $reserva->update(['dni_entregado' => true]);
                        Log::info('dni_entregado actualizado en reserva - DNI subido', [
                            'reserva_id' => $reserva->id,
                            'cliente_id' => $persona->id,
                            'dni_entregado' => true
                        ]);
                        
                        // data_dni = true SOLO si tiene todos los datos obligatorios para MIR
                        if ($this->verificarDatosCompletos($persona)) {
                            $persona->update(['data_dni' => true]);
                            // Recargar el cliente para asegurar que data_dni esté actualizado
                            $persona->refresh();
                            Log::info('data_dni marcado como true - datos completos para MIR', [
                                'reserva_id' => $reserva->id,
                                'cliente_id' => $persona->id
                            ]);
                        } else {
                            Log::warning('No se puede marcar data_dni = true: faltan datos obligatorios para MIR', [
                                'reserva_id' => $reserva->id,
                                'cliente_id' => $persona->id,
                                'fecha_nacimiento' => $persona->fecha_nacimiento,
                                'fecha_expedicion_doc' => $persona->fecha_expedicion_doc,
                                'email' => $persona->email,
                                'telefono_movil' => $persona->telefono_movil,
                                'provincia' => $persona->provincia
                            ]);
                        }
                    }
                    
                    $procesados++;
                    
                } catch (\Exception $e) {
                    Log::error('Error guardando datos adicionales para adulto ' . ($index + 1), [
                        'error' => $e->getMessage(),
                        'token' => $token
                    ]);
                    $errores[] = "Adulto " . ($index + 1) . ": " . $e->getMessage();
                }
            }
            
            // Recargar la reserva y el cliente para obtener el estado actualizado
            $reserva->refresh();
            $cliente = $reserva->cliente;
            if ($cliente) {
                $cliente->refresh();
            }
            
            // Verificar que los datos se guardaron correctamente
            $datosVerificados = [];
            $fotosVerificadas = [];
            
            // Verificar datos del cliente
            if ($cliente) {
                $datosVerificados['cliente'] = [
                    'id' => $cliente->id,
                    'data_dni' => $cliente->data_dni,
                    'telefono_movil' => !empty($cliente->telefono_movil),
                    'email' => !empty($cliente->email),
                    'codigo_postal' => !empty($cliente->codigo_postal),
                    'direccion' => !empty($cliente->direccion)
                ];
                
                // Verificar fotos del cliente en BD
                $fotosCliente = \App\Models\Photo::where('reserva_id', $reserva->id)
                    ->where('cliente_id', $cliente->id)
                    ->whereIn('photo_categoria_id', [13, 14])
                    ->get();
                
                $fotosVerificadas['cliente'] = [
                    'frontal' => $fotosCliente->where('photo_categoria_id', 13)->isNotEmpty(),
                    'trasera' => $fotosCliente->where('photo_categoria_id', 14)->isNotEmpty(),
                    'total' => $fotosCliente->count()
                ];
            }
            
            // Verificar datos de huéspedes (ya cargados arriba)
            foreach ($huespedes as $huesped) {
                $datosVerificados['huespedes'][$huesped->id] = [
                    'id' => $huesped->id,
                    'telefono_movil' => !empty($huesped->telefono_movil),
                    'email' => !empty($huesped->email),
                    'codigo_postal' => !empty($huesped->codigo_postal)
                ];
                
                $fotosHuesped = \App\Models\Photo::where('reserva_id', $reserva->id)
                    ->where('huespedes_id', $huesped->id)
                    ->whereIn('photo_categoria_id', [13, 14])
                    ->get();
                
                $fotosVerificadas['huespedes'][$huesped->id] = [
                    'frontal' => $fotosHuesped->where('photo_categoria_id', 13)->isNotEmpty(),
                    'trasera' => $fotosHuesped->where('photo_categoria_id', 14)->isNotEmpty(),
                    'total' => $fotosHuesped->count()
                ];
            }
            
            Log::info('Verificación de datos y fotos guardados', [
                'reserva_id' => $reserva->id,
                'datos_verificados' => $datosVerificados,
                'fotos_verificadas' => $fotosVerificadas,
                'procesados' => $procesados,
                'errores' => $errores
            ]);
            
            // Determinar URL de redirección
            $redirectUrl = null;
            if ($cliente && $cliente->data_dni) {
                // Si el cliente tiene data_dni = true, redirigir a gracias
                $idioma = $cliente->idioma ?: session('locale', 'es');
                $redirectUrl = route('gracias.index', $idioma);
            } else {
                // Si no, redirigir al índice (que debería redirigir a gracias si data_dni es true)
                $redirectUrl = route('dni.scanner.index', $token);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Datos adicionales guardados correctamente',
                'procesados' => $procesados,
                'errores' => $errores,
                'redirect_url' => $redirectUrl,
                'data_dni' => $cliente ? $cliente->data_dni : false,
                'verificacion' => [
                    'datos' => $datosVerificados,
                    'fotos' => $fotosVerificadas
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error guardando datos adicionales: ' . $e->getMessage(), [
                'token' => $token,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }
    
    /**
     * Procesar imágenes subidas (método legacy - mantener por compatibilidad)
     */
    public function processUpload(Request $request, $token)
    {
        try {
            // Obtener la reserva por token
            $reserva = Reserva::where('token', $token)->first();
            
            if (!$reserva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }
            
            $cliente = $reserva->cliente;
            
            // Obtener arrays de archivos y datos
            $frontales = $request->file('frontal', []);
            $traseras = $request->file('trasera', []);
            $personaTipos = $request->input('persona_tipo', []);
            $personaIds = $request->input('persona_id', []);
            
            $procesados = 0;
            $errores = [];
            
            // Procesar cada persona
            foreach ($frontales as $index => $frontalFile) {
                try {
                    $personaTipo = $personaTipos[$index] ?? 'cliente';
                    $personaId = $personaIds[$index] ?? $cliente->id;
                    
                    // Validar archivo frontal
                    if (!$frontalFile || !$frontalFile->isValid()) {
                        $errores[] = "Adulto " . ($index + 1) . ": Imagen frontal inválida";
                        continue;
                    }
                    
                    // Determinar si es cliente o huésped
                    if ($personaTipo === 'cliente') {
                        $persona = $cliente;
                    } else {
                        $persona = \App\Models\Huesped::find($personaId);
                        if (!$persona) {
                            $errores[] = "Adulto " . ($index + 1) . ": Huésped no encontrado";
                            continue;
                        }
                    }
                    
                    // Procesar imagen frontal
                    $frontalPath = $this->guardarImagen($frontalFile, $persona->id, 'front', $personaTipo);
                    $resultFront = $this->sendToAI($frontalPath, 'front');
                    
                    if ($resultFront['success']) {
                        $this->saveExtractedData($reserva, $persona, 'front', $resultFront['data'], $personaTipo);
                    }
                    
                    // Procesar imagen trasera si existe
                    if (isset($traseras[$index]) && $traseras[$index]->isValid()) {
                        $traseraPath = $this->guardarImagen($traseras[$index], $persona->id, 'rear', $personaTipo);
                        $resultRear = $this->sendToAI($traseraPath, 'rear');
                        
                        if ($resultRear['success']) {
                            $this->saveExtractedData($reserva, $persona, 'rear', $resultRear['data'], $personaTipo);
                        }
                    }
                    
                    // Guardar datos adicionales que no vienen del DNI o que pueden ser editados
                    // Nota: La dirección ya se extrae del reverso del DNI, pero permitimos editarla
                    $datosAdicionales = [];
                    
                    if ($request->has('telefono_movil.' . $index)) {
                        $datosAdicionales['telefono_movil'] = $request->input('telefono_movil.' . $index);
                    }
                    
                    if ($request->has('email.' . $index)) {
                        $datosAdicionales['email'] = $request->input('email.' . $index);
                    }
                    
                    // Dirección: si viene del formulario, actualizar (puede ser editada aunque venga del DNI)
                    if ($request->has('direccion.' . $index)) {
                        $direccion = $request->input('direccion.' . $index);
                        // Actualizar si hay un valor (permite sobrescribir la del DNI si se editó)
                        if (!empty($direccion)) {
                            $datosAdicionales['direccion'] = $direccion;
                        }
                    }
                    
                    if ($request->has('localidad.' . $index)) {
                        $datosAdicionales['localidad'] = $request->input('localidad.' . $index);
                    }
                    
                    if ($request->has('codigo_postal.' . $index)) {
                        $datosAdicionales['codigo_postal'] = $request->input('codigo_postal.' . $index);
                    }
                    
                    if ($request->has('provincia.' . $index)) {
                        $datosAdicionales['provincia'] = $request->input('provincia.' . $index);
                    }
                    
                    // Solo para huéspedes: relación de parentesco
                    if ($personaTipo === 'huesped' && $request->has('relacion_parentesco.' . $index)) {
                        $datosAdicionales['relacion_parentesco'] = $request->input('relacion_parentesco.' . $index);
                    }
                    
                    // Actualizar persona con datos adicionales
                    if (!empty($datosAdicionales)) {
                        $persona->update($datosAdicionales);
                        
                        Log::info('Datos adicionales guardados', [
                            'persona_tipo' => $personaTipo,
                            'persona_id' => $persona->id,
                            'datos' => $datosAdicionales
                        ]);
                    }
                    
                    // Marcar como completado si es cliente
                    if ($personaTipo === 'cliente') {
                        // Recargar el cliente para obtener los datos más recientes
                        $persona->refresh();
                        
                        // SIEMPRE marcar dni_entregado = true cuando se sube el DNI (independientemente de datos completos)
                        $reserva->update(['dni_entregado' => true]);
                        Log::info('dni_entregado actualizado en reserva - DNI subido (procesamiento múltiple)', [
                            'reserva_id' => $reserva->id,
                            'cliente_id' => $persona->id,
                            'dni_entregado' => true
                        ]);
                        
                        // data_dni = true SOLO si tiene todos los datos obligatorios para MIR
                        if ($this->verificarDatosCompletos($persona)) {
                            $persona->update(['data_dni' => true]);
                            Log::info('data_dni marcado como true - datos completos', [
                                'cliente_id' => $persona->id
                            ]);
                        } else {
                            Log::warning('No se puede marcar data_dni = true: faltan datos obligatorios para MIR', [
                                'cliente_id' => $persona->id,
                                'fecha_nacimiento' => $persona->fecha_nacimiento,
                                'fecha_expedicion_doc' => $persona->fecha_expedicion_doc,
                                'email' => $persona->email,
                                'telefono_movil' => $persona->telefono_movil,
                                'provincia' => $persona->provincia
                            ]);
                        }
                    }
                    
                    $procesados++;
                    
                } catch (\Exception $e) {
                    Log::error('Error procesando documento para adulto ' . ($index + 1), [
                        'error' => $e->getMessage(),
                        'token' => $token
                    ]);
                    $errores[] = "Adulto " . ($index + 1) . ": " . $e->getMessage();
                }
            }
            
            if ($procesados === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo procesar ningún documento. ' . implode(', ', $errores)
                ], 400);
            }
            
            Log::info('Imágenes subidas y procesadas', [
                'reserva_id' => $reserva->id,
                'cliente_id' => $cliente->id,
                'procesados' => $procesados,
                'errores' => $errores
            ]);
            
            $mensaje = $procesados > 0 
                ? "Se procesaron {$procesados} documento(s) correctamente."
                : "Error al procesar los documentos.";
            
            if (!empty($errores)) {
                $mensaje .= " Errores: " . implode(', ', $errores);
            }
            
            return response()->json([
                'success' => $procesados > 0,
                'message' => $mensaje,
                'redirect_url' => route('gracias.index', $cliente->idioma ? $cliente->idioma : 'es')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error procesando imágenes subidas: ' . $e->getMessage(), [
                'token' => $token,
                'error' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar las imágenes: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Guardar imagen subida
     */
    private function guardarImagen($file, $personaId, $side, $tipo = 'cliente')
    {
        try {
            $prefix = $tipo === 'cliente' ? 'cliente' : 'huesped';
            $filename = 'dni_' . $prefix . '_' . $side . '_' . time() . '_' . $personaId . '.' . $file->getClientOriginalExtension();
            $path = storage_path('app/temp/' . $filename);
            
            // Crear directorio si no existe
            $dir = dirname($path);
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new \Exception('No se pudo crear el directorio temporal: ' . $dir);
                }
            }
            
            // Verificar permisos de escritura
            if (!is_writable($dir)) {
                throw new \Exception('El directorio temporal no tiene permisos de escritura: ' . $dir);
            }
            
            // Mover archivo
            if (!$file->move($dir, $filename)) {
                throw new \Exception('No se pudo mover el archivo al directorio temporal');
            }
            
            // Verificar que el archivo se guardó correctamente
            if (!file_exists($path)) {
                throw new \Exception('El archivo no se guardó correctamente: ' . $path);
            }
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Error en guardarImagen', [
                'error' => $e->getMessage(),
                'persona_id' => $personaId,
                'side' => $side,
                'tipo' => $tipo,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-lanzar para que el método que llama pueda manejarlo
        }
    }
    
    /**
     * Procesar imagen capturada
     */
    public function processImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|string', // Base64 image
                'side' => 'required|in:front,rear',
                'token' => 'required|string'
            ]);
            
            Log::info('Procesando imagen DNI', [
                'side' => $request->side,
                'token' => $request->token,
                'image_size' => strlen($request->image)
            ]);
            
            // Obtener la reserva por token
            $reserva = Reserva::where('token', $request->token)->first();
            
            if (!$reserva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }
            
            // Decodificar imagen base64
            $imageData = $this->decodeBase64Image($request->image);
            
            if (!$imageData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de imagen inválido'
                ], 400);
            }
            
            // Guardar imagen temporal
            $filename = 'temp_' . time() . '_' . $request->side . '.jpg';
            $tempPath = storage_path('app/temp/' . $filename);
            
            // Crear directorio si no existe
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            
            file_put_contents($tempPath, $imageData);
            
            // Enviar a IA para procesamiento
            $result = $this->sendToAI($tempPath, $request->side);
            
            // Limpiar archivo temporal
            unlink($tempPath);
            
            if ($result['success']) {
                // Guardar datos extraídos
                $cliente = $reserva->cliente;
                $this->saveExtractedData($reserva, $cliente, $request->side, $result['data'], 'cliente');
                
                Log::info('Imagen procesada exitosamente', [
                    'reserva_id' => $reserva->id,
                    'side' => $request->side,
                    'data_extracted' => !empty($result['data'])
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Imagen procesada correctamente',
                    'data' => $result['data'],
                    'next_step' => $request->side === 'front' ? 'rear' : 'complete'
                ]);
            } else {
                Log::error('Error procesando imagen con IA', [
                    'reserva_id' => $reserva->id,
                    'side' => $request->side,
                    'error' => $result['error']
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error procesando la imagen: ' . $result['error']
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Error procesando imagen DNI: ' . $e->getMessage(), [
                'request_data' => $request->except(['image']),
                'error' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
    
    /**
     * Decodificar imagen base64
     */
    private function decodeBase64Image($base64String)
    {
        // Remover el prefijo data:image/...;base64,
        $base64String = preg_replace('#^data:image/\w+;base64,#i', '', $base64String);
        
        // Decodificar
        $imageData = base64_decode($base64String);
        
        // Verificar que se decodificó correctamente
        if ($imageData === false) {
            return false;
        }
        
        // Verificar que es una imagen válida
        $imageInfo = getimagesizefromstring($imageData);
        if ($imageInfo === false) {
            return false;
        }
        
        return $imageData;
    }
    
    /**
     * Enviar imagen a IA Hawkins para procesamiento
     */
    private function sendToAI($imagePath, $side)
    {
        try {
            Log::info('Enviando imagen a IA Hawkins', [
                'image_path' => $imagePath,
                'side' => $side
            ]);
            
            // Configuración de IA Hawkins (Ollama)
            // HAWKINS_AI_URL debe ser la URL base sin /chat (ej: https://192.168.1.45)
            $baseUrl = config('services.hawkins_ai.url', env('HAWKINS_AI_URL'));
            $apiKey = config('services.hawkins_ai.api_key', env('HAWKINS_AI_API_KEY'));
            $model = config('services.hawkins_ai.model', env('HAWKINS_AI_MODEL', 'qwen2.5vl:latest'));
            
            // Construir la URL completa: baseUrl/chat/analyze-image
            $aiEndpoint = rtrim($baseUrl, '/') . '/chat/analyze-image';
            
            Log::info('Configuración IA Hawkins', [
                'base_url' => $baseUrl,
                'endpoint' => $aiEndpoint,
                'model' => $model,
                'has_api_key' => !empty($apiKey)
            ]);
            
            if (empty($baseUrl) || empty($apiKey)) {
                Log::error('Configuración de IA Hawkins no disponible');
                return [
                    'success' => false,
                    'error' => 'Configuración de IA no disponible. Por favor, envía las imágenes del DNI por WhatsApp.',
                    'error_type' => 'ai_not_configured',
                    'debug_info' => [
                        'base_url' => $baseUrl,
                        'has_api_key' => !empty($apiKey)
                    ]
                ];
            }
            
            // Verificar que existe la imagen
            if (!file_exists($imagePath)) {
                Log::error('Imagen no encontrada', ['path' => $imagePath]);
            return [
                    'success' => false,
                    'error' => 'Imagen no encontrada'
                ];
            }
            
            // Preparar prompt según el lado del documento (formato exacto como Postman)
            // Incluir TODOS los campos requeridos por la legislación española
            if ($side === 'front') {
                $prompt = 'Extrae de la imagen del DNI o pasaporte español TODOS los datos solicitados. Busca cuidadosamente TODOS los campos visibles en el documento. Responde únicamente con un objeto JSON válido EXACTAMENTE en este formato. No añadas texto, explicaciones ni caracteres adicionales. Usa el formato de fecha YYYY-MM-DD. Si no se encuentra un campo, devuélvelo como cadena vacía.

{
"nombre": "",
"apellidos": "",
"fecha_nacimiento": "",
"lugar_nacimiento": "",
"nacionalidad": "",
"fecha_expedicion": "",
"fecha_caducidad": "",
"numero_dni_o_pasaporte": "",
"tipo_documento": "",
"sexo": ""
}

INSTRUCCIONES ESPECÍFICAS:
1. FECHA DE EXPEDICIÓN: Busca el campo "EMISIÓN" o "EXPEDICIÓN" en el DNI. La fecha aparece en formato DD MM YYYY (ej: 22 06 2023). Conviértela a YYYY-MM-DD (2023-06-22). IMPORTANTE: Verifica que el año sea razonable (entre 2000 y el año actual + 10 años). No confundas la fecha de expedición con la de caducidad.
2. FECHA DE CADUCIDAD: Busca el campo "VALIDEZ", "VÁLIDO HASTA", "VALID UNTIL", "CADUCIDAD", "EXPIRES" en el DNI. La fecha aparece en formato DD MM YYYY (ej: 22 06 2033). Conviértela a YYYY-MM-DD (2033-06-22). La fecha de caducidad siempre es POSTERIOR a la fecha de expedición (normalmente 10 años después).
3. LUGAR DE NACIMIENTO: En el REVERSO del DNI español, busca el campo "LUGAR DE NACIMIENTO". Este campo es OBLIGATORIO y aparece claramente en el reverso del documento.
4. DIRECCIÓN COMPLETA: En el REVERSO del DNI, busca el campo "DOMICILIO". Extrae TODA la dirección completa incluyendo calle, número, piso, puerta, etc. (ej: "C. VIRGEN. DEL VALLE 2B P01 B"). No solo la ciudad.
5. El tipo de documento debe ser "DNI", "NIE" o "Pasaporte" según el documento que estés analizando.
6. El sexo puede aparecer como "M"/"F", "Masculino"/"Femenino", o "Hombre"/"Mujer".';
            } else {
                // Prompt para reverso con el mismo formato que funciona en frontal
                // IMPORTANTE: El reverso contiene dirección Y lugar de nacimiento
                $prompt = 'Extrae de la imagen del REVERSO del DNI español TODOS los datos solicitados. Busca cuidadosamente el campo "DOMICILIO" para la dirección completa y el campo "LUGAR DE NACIMIENTO" para el lugar de nacimiento. Responde únicamente con un objeto JSON válido EXACTAMENTE en este formato. No añadas texto, explicaciones ni caracteres adicionales. Si no se encuentra un campo, devuélvelo como cadena vacía.

{
"direccion": "",
"localidad": "",
"codigo_postal": "",
"provincia": "",
"lugar_nacimiento": ""
}

INSTRUCCIONES ESPECÍFICAS:
1. DIRECCIÓN COMPLETA: Busca el campo "DOMICILIO" en el reverso. Extrae TODA la dirección incluyendo calle, número, piso, puerta, bloque, etc. (ej: "C. VIRGEN. DEL VALLE 2B P01 B"). No solo la ciudad.
2. LOCALIDAD: Extrae la ciudad que aparece después de la dirección (ej: "SEVILLA").
3. PROVINCIA: Extrae la provincia que aparece después de la localidad (puede ser la misma que la localidad si es una ciudad capital de provincia).
4. CÓDIGO POSTAL: Si aparece en el documento, extráelo. Si no aparece, déjalo vacío.
5. LUGAR DE NACIMIENTO: Busca el campo "LUGAR DE NACIMIENTO" en el reverso del DNI. Este campo es OBLIGATORIO y aparece claramente marcado. Extrae la ciudad y provincia de nacimiento (ej: "SEVILLA" o "SEVILLA, SEVILLA").';
            }
            
            // URL completa de la API: baseUrl/chat/analyze-image
            $fullUrl = rtrim($baseUrl, '/') . '/chat/analyze-image';
            
            Log::info('Llamando a IA Hawkins', [
                'url' => $fullUrl,
                'side' => $side,
                'model' => $model,
                'image_path' => $imagePath
            ]);
            
            // Llamar a IA Hawkins usando cURL (formato exacto como Postman)
            // IMPORTANTE: Ignorar verificación SSL para IPs locales con certificados autofirmados
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0, // Sin timeout como en Postman
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => [
                    'image' => new \CURLFile($imagePath, 'image/jpeg', 'dni_' . $side . '.jpg'),
                    'prompt' => $prompt,
                    'modelo' => $model
                ],
                CURLOPT_HTTPHEADER => [
                    'X-API-Key: ' . $apiKey
                ],
                CURLOPT_SSL_VERIFYPEER => app()->environment('production'),
                CURLOPT_SSL_VERIFYHOST => app()->environment('production') ? 2 : 0
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            $curlInfo = curl_getinfo($curl);
            curl_close($curl);
            
            Log::info('Respuesta de IA Hawkins', [
                'url' => $fullUrl,
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500)
            ]);
            
            if ($curlError) {
                Log::error('Error cURL en IA Hawkins', [
                    'url' => $fullUrl,
                    'error' => $curlError,
                    'http_code' => $httpCode
                ]);
                
                // Determinar tipo de error más específico
                $errorType = 'connection_error';
                $errorMessage = 'Error de conexión con el servicio de IA.';
                
                if (strpos($curlError, 'Connection timed out') !== false) {
                    $errorType = 'timeout_error';
                    $errorMessage = 'El servicio de IA no respondió a tiempo. El servidor puede estar sobrecargado o no estar disponible.';
                } elseif (strpos($curlError, 'Could not resolve host') !== false) {
                    $errorType = 'dns_error';
                    $errorMessage = 'No se pudo resolver la dirección del servidor de IA. Verifica la configuración de red.';
                } elseif (strpos($curlError, 'Connection refused') !== false) {
                    $errorType = 'connection_refused';
                    $errorMessage = 'El servidor de IA rechazó la conexión. El servicio puede no estar disponible.';
                } elseif (strpos($curlError, 'SSL') !== false || strpos($curlError, 'certificate') !== false) {
                    $errorType = 'ssl_error';
                    $errorMessage = 'Error de certificado SSL con el servidor de IA. Esto es normal para servidores locales con certificados autofirmados.';
                }
                
                return [
                    'success' => false,
                    'message' => $errorMessage . ' Por favor, intenta de nuevo más tarde o envía las imágenes por WhatsApp.',
                    'error' => "Error cURL: {$curlError}",
                    'error_type' => $errorType,
                    'ai_url' => $fullUrl,
                    'ai_raw_response' => null,
                    'ai_http_code' => $httpCode
                ];
            }
            
            if ($httpCode !== 200) {
                Log::error('Error HTTP en IA Hawkins', [
                    'url' => $fullUrl,
                    'http_code' => $httpCode,
                    'raw_response' => $response,
                    'response_length' => strlen($response)
                ]);
                
                // Intentar parsear la respuesta como JSON si es posible
                $errorResponse = null;
                if (!empty($response)) {
                    $errorResponse = json_decode($response, true);
                }
                
                return [
                    'success' => false,
                    'message' => 'El servicio de IA no está disponible. Por favor, intenta de nuevo más tarde o envía las imágenes por WhatsApp.',
                    'error' => "HTTP {$httpCode}: " . substr($response, 0, 200),
                    'error_type' => 'ai_error',
                    'ai_url' => $fullUrl,
                    'http_status' => $httpCode,
                    'ai_raw_response' => $response,
                    'ai_response_parsed' => $errorResponse,
                    'ai_http_code' => $httpCode
                ];
            }
            
            // Parsear respuesta con múltiples estrategias
            $responseData = json_decode($response, true);
            $jsonError = json_last_error();
            
            // Loggear la respuesta RAW completa de la IA (guardar en log diario para debugging)
            Log::info('Respuesta de IA Hawkins', [
                'url' => $fullUrl,
                'http_code' => $httpCode,
                'response_length' => strlen($response),
                'is_json' => $jsonError === JSON_ERROR_NONE,
                'json_error' => $jsonError !== JSON_ERROR_NONE ? json_last_error_msg() : null,
                'response_preview' => substr($response, 0, 1000), // Primeros 1000 caracteres para debugging
                'response_structure' => is_array($responseData) ? array_keys($responseData) : 'not_array'
            ]);
            
            // Guardar respuesta completa en storage para análisis detallado
            try {
                // Crear directorio si no existe
                $directory = 'ai_responses';
                if (!Storage::disk('local')->exists($directory)) {
                    Storage::disk('local')->makeDirectory($directory);
                }
                
                $logFileName = 'ai_response_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';
                Storage::disk('local')->put("{$directory}/{$logFileName}", $response);
                Log::info('Respuesta completa guardada para análisis', ['file' => $logFileName]);
            } catch (\Exception $e) {
                Log::warning('No se pudo guardar respuesta completa', ['error' => $e->getMessage()]);
            }
            
            // ESTRATEGIA 1: Si la respuesta es JSON válido, procesarla
            if ($jsonError === JSON_ERROR_NONE && is_array($responseData) && !empty($responseData)) {
                $extractedData = $this->extractDataFromStructuredResponse($responseData);
                if (!empty($extractedData)) {
                    Log::info('Datos extraídos usando estrategia 1 (JSON estructurado)', [
                        'data_keys' => array_keys($extractedData)
                    ]);
                }
            } else {
                $extractedData = [];
            }
            
            // ESTRATEGIA 2: Si no se encontraron datos, buscar JSON en texto plano
            if (empty($extractedData)) {
                Log::info('Intentando estrategia 2: extraer JSON de texto plano');
                $extractedData = $this->extractJsonFromText($response);
                if (!empty($extractedData)) {
                    Log::info('Datos extraídos usando estrategia 2 (JSON en texto)', [
                        'data_keys' => array_keys($extractedData)
                    ]);
                }
            }
            
            // ESTRATEGIA 3: Si aún no hay datos, intentar parsear la respuesta como string directamente
            if (empty($extractedData) && is_string($response) && !empty($response)) {
                Log::info('Intentando estrategia 3: parsear respuesta como string');
                // Intentar limpiar y parsear
                $cleaned = trim($response);
                // Quitar posibles prefijos/sufijos de texto
                $cleaned = preg_replace('/^[^{]*/', '', $cleaned); // Quitar texto antes de {
                $cleaned = preg_replace('/[^}]*$/', '', $cleaned); // Quitar texto después de }
                if (!empty($cleaned)) {
                    $json = json_decode($cleaned, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        $extractedData = $this->extractDataFromStructuredResponse($json);
                        if (!empty($extractedData)) {
                            Log::info('Datos extraídos usando estrategia 3 (string limpiado)', [
                                'data_keys' => array_keys($extractedData)
                            ]);
                        }
                    }
                }
            }
            
            // ESTRATEGIA 4: Si responseData existe pero no tiene estructura esperada, buscar en todos los campos
            if (empty($extractedData) && is_array($responseData) && !empty($responseData)) {
                Log::info('Intentando estrategia 4: buscar en todos los campos de responseData');
                $extractedData = $this->extractJsonFromText($responseData);
                if (!empty($extractedData)) {
                    Log::info('Datos extraídos usando estrategia 4 (búsqueda recursiva)', [
                        'data_keys' => array_keys($extractedData)
                    ]);
                }
            }
            
            // Normalizar campos según formato de respuesta
            if (!empty($extractedData)) {
                $extractedData = $this->normalizeExtractedData($extractedData, $side);
            }
            
            // Si después de todas las estrategias no hay datos, devolver error detallado
            if (empty($extractedData)) {
                Log::error('No se pudieron extraer datos de la respuesta después de todas las estrategias', [
                    'url' => $fullUrl,
                    'http_code' => $httpCode,
                    'response_length' => strlen($response),
                    'response_preview' => substr($response, 0, 1000),
                    'response_structure' => is_array($responseData) ? array_keys($responseData) : 'not_array',
                    'is_json_valid' => $jsonError === JSON_ERROR_NONE,
                    'json_error_msg' => $jsonError !== JSON_ERROR_NONE ? json_last_error_msg() : null,
                    'response_type' => gettype($responseData)
                ]);
                
                return [
                    'success' => false,
                    'message' => 'No se pudieron extraer los datos del documento. Por favor, intenta de nuevo o envía las imágenes por WhatsApp.',
                    'error' => 'No se encontraron datos válidos en la respuesta de la IA',
                    'error_type' => 'parse_error',
                    'ai_url' => $fullUrl,
                    'ai_raw_response' => $response,
                    'ai_response_parsed' => $responseData,
                    'response_structure' => is_array($responseData) ? array_keys($responseData) : 'not_array',
                    'ai_http_code' => $httpCode,
                    'response_length' => strlen($response)
                ];
            }
            
            Log::info('Datos extraídos por IA Hawkins', [
                'side' => $side,
                'data_keys' => array_keys($extractedData)
            ]);
            
            return [
                'success' => true,
                'data' => $extractedData
            ];
            
        } catch (\Exception $e) {
            Log::error('Error enviando imagen a IA Hawkins', [
                'url' => $fullUrl ?? 'N/A',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al procesar el documento. Por favor, intenta de nuevo o envía las imágenes por WhatsApp.',
                'error' => $e->getMessage(),
                'error_type' => 'ai_error',
                'ai_url' => $fullUrl ?? 'N/A',
                'ai_raw_response' => null,
                'exception' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }
    
    /**
     * Normalizar datos extraídos según formato de respuesta de la IA
     */
    private function normalizeExtractedData($data, $side)
    {
        $normalized = [];
        
        if ($side === 'front') {
            // Normalizar campos del frontal
            $normalized['nombre'] = $data['nombre'] ?? $data['name'] ?? '';
            $normalized['apellido1'] = $data['apellido1'] ?? $data['primer_apellido'] ?? '';
            $normalized['apellido2'] = $data['apellido2'] ?? $data['segundo_apellido'] ?? '';
            
            // Si viene como 'apellidos' completo, separarlo
            if (empty($normalized['apellido1']) && isset($data['apellidos'])) {
                $apellidos = explode(' ', $data['apellidos'], 2);
                $normalized['apellido1'] = $apellidos[0] ?? '';
                $normalized['apellido2'] = $apellidos[1] ?? '';
            }
            
            $normalized['dni'] = $data['dni'] ?? $data['numero'] ?? $data['numero_dni_o_pasaporte'] ?? '';
            $normalized['num_identificacion'] = $normalized['dni'];
            $normalized['numero_identificacion'] = $normalized['dni'];
            
            // Normalizar fecha de nacimiento
            if (isset($data['fecha_nacimiento'])) {
                $normalized['fecha_nacimiento'] = $this->normalizeDate($data['fecha_nacimiento']);
            }
            
            // Normalizar fecha de expedición
            if (isset($data['fecha_expedicion'])) {
                $fechaExp = $this->normalizeDate($data['fecha_expedicion']);
                // Validar que el año sea razonable (corregir errores comunes)
                if ($fechaExp) {
                    $fechaCarbon = \Carbon\Carbon::parse($fechaExp);
                    $añoActual = now()->year;
                    // Si el año es mayor a 10 años en el futuro, probablemente es un error (confundió expedición con caducidad)
                    if ($fechaCarbon->year > ($añoActual + 10)) {
                        Log::warning('Fecha de expedición sospechosa (confundida con caducidad), corrigiendo', [
                            'fecha_original' => $fechaExp,
                            'año_original' => $fechaCarbon->year,
                            'año_corregido' => $fechaCarbon->year - 10
                        ]);
                        // Restar 10 años si parece ser un error (normalmente DNI es válido 10 años)
                        $fechaCarbon->subYears(10);
                        $fechaExp = $fechaCarbon->format('Y-m-d');
                    }
                    // También validar que no sea anterior a 2000 (DNIs antiguos pueden ser anteriores, pero es raro)
                    if ($fechaCarbon->year < 2000 && $fechaCarbon->year > 1900) {
                        Log::info('Fecha de expedición anterior a 2000 (posible DNI antiguo)', [
                            'fecha' => $fechaExp
                        ]);
                    }
                }
                $normalized['fecha_expedicion'] = $fechaExp;
            }
            
            // Normalizar fecha de caducidad (importante para verificar validez del documento)
            if (isset($data['fecha_caducidad'])) {
                $normalized['fecha_caducidad'] = $this->normalizeDate($data['fecha_caducidad']);
                
                // Validar que la caducidad sea posterior a la expedición
                if ($normalized['fecha_expedicion'] && $normalized['fecha_caducidad']) {
                    $expedicion = \Carbon\Carbon::parse($normalized['fecha_expedicion']);
                    $caducidad = \Carbon\Carbon::parse($normalized['fecha_caducidad']);
                    if ($caducidad->lte($expedicion)) {
                        Log::warning('Fecha de caducidad anterior o igual a expedición, posible error', [
                            'expedicion' => $normalized['fecha_expedicion'],
                            'caducidad' => $normalized['fecha_caducidad']
                        ]);
                    }
                    // Validar que la diferencia sea aproximadamente 10 años (tolerancia de 1 año)
                    $diferenciaAños = $caducidad->diffInYears($expedicion);
                    if ($diferenciaAños < 9 || $diferenciaAños > 11) {
                        Log::warning('Diferencia inusual entre expedición y caducidad', [
                            'expedicion' => $normalized['fecha_expedicion'],
                            'caducidad' => $normalized['fecha_caducidad'],
                            'diferencia_años' => $diferenciaAños
                        ]);
                    }
                }
            }
            
            // Normalizar sexo
            if (isset($data['sexo'])) {
                $sexo = strtoupper(trim($data['sexo']));
                if ($sexo === 'M' || $sexo === 'MASCULINO' || $sexo === 'HOMBRE' || $sexo === 'MALE') {
                    $normalized['sexo'] = 'Masculino';
                } elseif ($sexo === 'F' || $sexo === 'FEMENINO' || $sexo === 'MUJER' || $sexo === 'FEMALE') {
                    $normalized['sexo'] = 'Femenino';
                } else {
                    $normalized['sexo'] = $data['sexo'];
                }
            }
            
            // Normalizar nacionalidad
            $normalized['nacionalidad'] = $data['nacionalidad'] ?? $data['nacionalidadStr'] ?? '';
            
            // Normalizar lugar de nacimiento (importante en DNI español)
            $normalized['lugar_nacimiento'] = trim($data['lugar_nacimiento'] ?? '');
            
            // Normalizar tipo de documento
            $normalized['tipo_documento'] = trim($data['tipo_documento'] ?? '');
            
        } else if ($side === 'rear') {
            // Normalizar campos del reverso (dirección y lugar de nacimiento)
            $normalized['direccion'] = trim($data['direccion'] ?? '');
            $normalized['localidad'] = trim($data['localidad'] ?? $data['ciudad'] ?? '');
            $normalized['codigo_postal'] = trim($data['codigo_postal'] ?? $data['cp'] ?? '');
            $normalized['provincia'] = trim($data['provincia'] ?? '');
            
            // Lugar de nacimiento también puede venir del reverso
            $normalized['lugar_nacimiento'] = trim($data['lugar_nacimiento'] ?? '');
        }
        
        return $normalized;
    }
    
    /**
     * Normalizar formato de fecha
     */
    private function normalizeDate($date)
    {
        if (empty($date)) {
            return null;
        }
        
        // Si ya está en formato YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        
        // Si viene en formato DD/MM/YYYY o DD-MM-YYYY
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }
        
        // Intentar parsear con Carbon
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Error parseando fecha: ' . $date);
            return null;
        }
    }
    
    /**
     * Extraer datos de una respuesta estructurada (JSON parseado)
     * Busca datos en múltiples formatos comunes
     * 
     * @param array $responseData Respuesta parseada como array
     * @return array Datos extraídos (puede estar vacío)
     */
    private function extractDataFromStructuredResponse($responseData)
    {
        if (!is_array($responseData) || empty($responseData)) {
            return [];
        }
        
        $extractedData = [];
        
        // Formato 1: respuesta directa con JSON en 'respuesta'
        if (isset($responseData['respuesta'])) {
            $respuestaJson = $responseData['respuesta'];
            if (is_string($respuestaJson)) {
                $decoded = json_decode($respuestaJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $extractedData = $decoded;
                } else {
                    // Si no es JSON válido, intentar extraer JSON del texto
                    $extractedData = $this->extractJsonFromText($respuestaJson) ?? [];
                }
            } elseif (is_array($respuestaJson)) {
                $extractedData = $respuestaJson;
            }
        }
        
        // Formato 2: datos directamente en 'data'
        if (empty($extractedData) && isset($responseData['data'])) {
            if (is_array($responseData['data'])) {
                $extractedData = $responseData['data'];
            } elseif (is_string($responseData['data'])) {
                $decoded = json_decode($responseData['data'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $extractedData = $decoded;
                } else {
                    $extractedData = $this->extractJsonFromText($responseData['data']) ?? [];
                }
            }
        }
        
        // Formato 3: datos directamente en la raíz (campos esperados)
        if (empty($extractedData)) {
            $expectedFields = ['nombre', 'dni', 'numero', 'numero_dni_o_pasaporte', 'direccion', 'localidad', 'apellido1', 'apellidos'];
            $hasExpectedFields = false;
            foreach ($expectedFields as $field) {
                if (isset($responseData[$field])) {
                    $hasExpectedFields = true;
                    break;
                }
            }
            if ($hasExpectedFields) {
                $extractedData = $responseData;
            }
        }
        
        // Formato 4: buscar en 'result', 'resultado', 'content', 'message', etc.
        if (empty($extractedData)) {
            $possibleKeys = ['result', 'resultado', 'content', 'message', 'output', 'text', 'response'];
            foreach ($possibleKeys as $key) {
                if (isset($responseData[$key])) {
                    $value = $responseData[$key];
                    if (is_array($value)) {
                        $extractedData = $value;
                        break;
                    } elseif (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $extractedData = $decoded;
                            break;
                        } else {
                            $extracted = $this->extractJsonFromText($value);
                            if ($extracted !== null) {
                                $extractedData = $extracted;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        return $extractedData;
    }
    
    /**
     * Extraer JSON de texto plano - busca JSON en cualquier formato dentro del texto
     * 
     * @param string|array $data Datos a procesar (puede ser string, array o cualquier campo)
     * @return array|null Datos extraídos o null si no se encuentra JSON válido
     */
    private function extractJsonFromText($data)
    {
        if (is_array($data)) {
            // Si ya es un array, buscar JSON en campos de texto
            foreach ($data as $key => $value) {
                if (is_string($value) && !empty($value)) {
                    $extracted = $this->extractJsonFromText($value);
                    if ($extracted !== null) {
                        return $extracted;
                    }
                } elseif (is_array($value)) {
                    $extracted = $this->extractJsonFromText($value);
                    if ($extracted !== null) {
                        return $extracted;
                    }
                }
            }
            return null;
        }
        
        if (!is_string($data) || empty($data)) {
            return null;
        }
        
        // Estrategia 1: Intentar parsear directamente como JSON
        $json = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json) && !empty($json)) {
            Log::info('JSON encontrado directamente en respuesta', [
                'json_keys' => array_keys($json)
            ]);
            return $json;
        }
        
        // Estrategia 2: Buscar JSON entre llaves {} usando regex
        // Buscar el primer objeto JSON válido
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $data, $matches)) {
            $jsonStr = $matches[0];
            $json = json_decode($jsonStr, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json) && !empty($json)) {
                Log::info('JSON extraído de texto usando regex', [
                    'json_keys' => array_keys($json),
                    'preview' => substr($jsonStr, 0, 200)
                ]);
                return $json;
            }
        }
        
        // Estrategia 3: Buscar JSON que empiece después de palabras clave comunes
        $keywords = ['respuesta', 'data', 'resultado', 'json', 'result', 'content', 'message'];
        foreach ($keywords as $keyword) {
            // Buscar después de la palabra clave
            $pattern = '/' . preg_quote($keyword, '/') . '\s*[:=]\s*(\{.*\})/is';
            if (preg_match($pattern, $data, $matches)) {
                $jsonStr = $matches[1];
                $json = json_decode($jsonStr, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json) && !empty($json)) {
                    Log::info('JSON encontrado después de palabra clave', [
                        'keyword' => $keyword,
                        'json_keys' => array_keys($json)
                    ]);
                    return $json;
                }
            }
        }
        
        // Estrategia 4: Intentar limpiar JSON malformado (quitar caracteres antes/después)
        // Buscar la primera { y última } y extraer todo lo que hay entre ellas
        $firstBrace = strpos($data, '{');
        $lastBrace = strrpos($data, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $jsonStr = substr($data, $firstBrace, $lastBrace - $firstBrace + 1);
            $json = json_decode($jsonStr, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json) && !empty($json)) {
                Log::info('JSON extraído limpiando texto alrededor', [
                    'json_keys' => array_keys($json)
                ]);
                return $json;
            }
        }
        
        return null;
    }
    
    /**
     * Guardar datos extraídos
     */
    private function saveExtractedData($reserva, $persona, $side, $data, $tipo = 'cliente')
    {
        try {
            if ($side === 'front' && !empty($data)) {
                if ($tipo === 'cliente') {
                    // Determinar tipo de documento
                    $tipoDoc = $data['tipo_documento'] ?? '';
                    $tipoDocStr = '';
                    $tipoDocCode = 'D'; // Por defecto DNI
                    
                    if (stripos($tipoDoc, 'pasaporte') !== false || stripos($tipoDoc, 'passport') !== false) {
                        $tipoDocCode = 'P';
                        $tipoDocStr = 'Pasaporte';
                    } elseif (stripos($tipoDoc, 'nie') !== false) {
                        $tipoDocCode = 'N';
                        $tipoDocStr = 'NIE';
                    } else {
                        $tipoDocCode = 'D';
                        $tipoDocStr = 'DNI';
                    }
                    
                    // Guardar datos del frontal en Cliente con TODOS los campos extraídos
                    $updateData = [
                        'nombre' => $data['nombre'] ?? $persona->nombre,
                        'apellido1' => $data['apellido1'] ?? $persona->apellido1,
                        'apellido2' => $data['apellido2'] ?? $persona->apellido2,
                        'num_identificacion' => $data['dni'] ?? $persona->num_identificacion,
                        'fecha_nacimiento' => $data['fecha_nacimiento'] ?? $persona->fecha_nacimiento,
                        'sexo' => $data['sexo'] ?? $persona->sexo,
                        'fecha_expedicion_doc' => $data['fecha_expedicion'] ?? $persona->fecha_expedicion_doc,
                        'nacionalidadStr' => $data['nacionalidad'] ?? $persona->nacionalidadStr,
                        'tipo_documento' => $tipoDocCode,
                        'tipo_documento_str' => $tipoDocStr,
                        // NO marcar data_dni aquí - se validará después de actualizar
                    ];
                    
                    // Agregar lugar de nacimiento si está disponible (puede no estar en el modelo, pero lo intentamos)
                    if (isset($data['lugar_nacimiento']) && !empty($data['lugar_nacimiento'])) {
                        // Si el modelo tiene un campo para lugar de nacimiento, lo guardamos
                        // Por ahora lo guardamos en observaciones o se puede agregar al modelo
                    }
                    
                    // Agregar fecha de caducidad si está disponible
                    if (isset($data['fecha_caducidad']) && !empty($data['fecha_caducidad'])) {
                        // Verificar si el documento está caducado
                        $fechaCaducidad = \Carbon\Carbon::parse($data['fecha_caducidad']);
                        if ($fechaCaducidad->isPast()) {
                            Log::warning('Documento caducado detectado', [
                                'cliente_id' => $persona->id,
                                'fecha_caducidad' => $data['fecha_caducidad']
                            ]);
                        }
                    }
                    
                    $persona->update($updateData);
                    
                    // Recargar el cliente para obtener los datos actualizados
                    $persona->refresh();
                    
                    // SIEMPRE marcar dni_entregado = true cuando se guarda el frontal del DNI
                    $reserva->update(['dni_entregado' => true]);
                    Log::info('dni_entregado actualizado en reserva - frontal guardado', [
                        'reserva_id' => $reserva->id,
                        'cliente_id' => $persona->id,
                        'dni' => $data['dni'] ?? 'N/A'
                    ]);
                    
                    // data_dni = true SOLO si tiene todos los datos obligatorios para MIR
                    if ($this->verificarDatosCompletos($persona)) {
                        $persona->update(['data_dni' => true]);
                        Log::info('Datos del frontal guardados en Cliente - data_dni marcado como true', [
                            'cliente_id' => $persona->id,
                            'dni' => $data['dni'] ?? 'N/A'
                        ]);
                    } else {
                        Log::warning('Datos del frontal guardados pero NO se marca data_dni = true: faltan datos obligatorios para MIR', [
                            'cliente_id' => $persona->id,
                            'dni' => $data['dni'] ?? 'N/A',
                            'fecha_nacimiento' => $persona->fecha_nacimiento,
                            'fecha_expedicion_doc' => $persona->fecha_expedicion_doc,
                            'email' => $persona->email,
                            'telefono_movil' => $persona->telefono_movil,
                            'provincia' => $persona->provincia
                        ]);
                    }
                } else {
                    // Determinar tipo de documento para Huesped
                    $tipoDoc = $data['tipo_documento'] ?? '';
                    $tipoDocStr = '';
                    $tipoDocCode = '1'; // Por defecto DNI (1)
                    
                    if (stripos($tipoDoc, 'pasaporte') !== false || stripos($tipoDoc, 'passport') !== false) {
                        $tipoDocCode = '2';
                        $tipoDocStr = 'Pasaporte';
                    } elseif (stripos($tipoDoc, 'nie') !== false) {
                        $tipoDocCode = '3';
                        $tipoDocStr = 'NIE';
                    } else {
                        $tipoDocCode = '1';
                        $tipoDocStr = 'DNI';
                    }
                    
                    // Guardar datos del frontal en Huesped con TODOS los campos extraídos
                    $updateData = [
                        'nombre' => $data['nombre'] ?? $persona->nombre,
                        'primer_apellido' => $data['apellido1'] ?? $persona->primer_apellido,
                        'segundo_apellido' => $data['apellido2'] ?? $persona->segundo_apellido,
                        'numero_identificacion' => $data['dni'] ?? $persona->numero_identificacion,
                        'fecha_nacimiento' => $data['fecha_nacimiento'] ?? $persona->fecha_nacimiento,
                        'sexo' => $data['sexo'] ?? $persona->sexo,
                        'fecha_expedicion' => $data['fecha_expedicion'] ?? $persona->fecha_expedicion,
                        'nacionalidadStr' => $data['nacionalidad'] ?? $persona->nacionalidadStr,
                        'tipo_documento' => $tipoDocCode,
                        'tipo_documento_str' => $tipoDocStr
                    ];
                    
                    // Guardar fecha de caducidad si está disponible
                    if (isset($data['fecha_caducidad']) && !empty($data['fecha_caducidad'])) {
                        $fechaCaducidad = \Carbon\Carbon::parse($data['fecha_caducidad']);
                        $updateData['fecha_caducidad'] = $fechaCaducidad->format('Y-m-d');
                        
                        // Verificar si el documento está caducado
                        if ($fechaCaducidad->isPast()) {
                            Log::warning('Documento caducado detectado en Huesped', [
                                'huesped_id' => $persona->id,
                                'fecha_caducidad' => $data['fecha_caducidad']
                            ]);
                        }
                    }
                    
                    // Guardar lugar de nacimiento si está disponible (puede venir del frontal o del reverso)
                    if (isset($data['lugar_nacimiento']) && !empty($data['lugar_nacimiento'])) {
                        $updateData['lugar_nacimiento'] = trim($data['lugar_nacimiento']);
                    }
                    
                    $persona->update($updateData);
                    
                    Log::info('Datos del frontal guardados en Huesped', [
                        'huesped_id' => $persona->id,
                        'dni' => $data['dni'] ?? 'N/A',
                        'tipo_documento' => $tipoDocStr
                    ]);
                }
            }
            
            // Procesar datos del reverso (donde aparece la dirección en el DNI)
            if ($side === 'rear' && !empty($data)) {
                $updateData = [];
                
                // Extraer dirección del reverso del DNI
                // La IA debería extraer: direccion, localidad, codigo_postal, provincia
                if (isset($data['direccion']) && !empty($data['direccion'])) {
                    $updateData['direccion'] = $data['direccion'];
                }
                
                if (isset($data['localidad']) && !empty($data['localidad'])) {
                    $updateData['localidad'] = $data['localidad'];
                }
                
                if (isset($data['codigo_postal']) && !empty($data['codigo_postal'])) {
                    $updateData['codigo_postal'] = $data['codigo_postal'];
                }
                
                if (isset($data['provincia']) && !empty($data['provincia'])) {
                    $updateData['provincia'] = $data['provincia'];
                }
                
                // Lugar de nacimiento también puede venir del reverso del DNI
                if (isset($data['lugar_nacimiento']) && !empty($data['lugar_nacimiento'])) {
                    $updateData['lugar_nacimiento'] = trim($data['lugar_nacimiento']);
                }
                
                // Si hay datos de dirección o lugar de nacimiento, actualizar
                if (!empty($updateData)) {
                    $persona->update($updateData);
                    
                    Log::info('Datos de dirección del reverso guardados', [
                        'persona_tipo' => $tipo,
                        'persona_id' => $persona->id,
                        'direccion' => $updateData
                    ]);
                }
            }
            
            // Guardar imagen procesada (opcional)
            $this->saveProcessedImage($persona->id, $side, $data, $tipo);
            
        } catch (\Exception $e) {
            Log::error('Error guardando datos extraídos: ' . $e->getMessage(), [
                'reserva_id' => $reserva->id,
                'side' => $side
            ]);
        }
    }
    
    /**
     * Validar que los datos extraídos sean válidos (no datos por defecto)
     */
    private function validateExtractedData($data, $side)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        
        // Lista de valores por defecto/simulados que no deben aceptarse
        $defaultValues = [
            'nombre' => ['Juan', 'María', 'Ejemplo', 'Test', 'Default'],
            'apellido1' => ['Pérez', 'García', 'Ejemplo', 'Test', 'Default'],
            'dni' => ['12345678A', '00000000A', '11111111A'],
            'num_identificacion' => ['12345678A', '00000000A', '11111111A'],
            'numero_identificacion' => ['12345678A', '00000000A', '11111111A'],
        ];
        
        if ($side === 'front') {
            // Validar datos del frontal (buscar en múltiples formatos según respuesta de IA)
            $nombre = strtolower(trim($data['nombre'] ?? ''));
            
            // Buscar apellidos en diferentes formatos
            $apellido1 = '';
            if (isset($data['apellido1'])) {
                $apellido1 = strtolower(trim($data['apellido1']));
            } elseif (isset($data['primer_apellido'])) {
                $apellido1 = strtolower(trim($data['primer_apellido']));
            } elseif (isset($data['apellidos'])) {
                // Si viene como "apellidos" completo, separar
                $apellidos = explode(' ', trim($data['apellidos']), 2);
                $apellido1 = strtolower(trim($apellidos[0] ?? ''));
            }
            
            // Buscar DNI en diferentes formatos
            $dni = '';
            if (isset($data['dni'])) {
                $dni = trim($data['dni']);
            } elseif (isset($data['num_identificacion'])) {
                $dni = trim($data['num_identificacion']);
            } elseif (isset($data['numero_identificacion'])) {
                $dni = trim($data['numero_identificacion']);
            } elseif (isset($data['numero_dni_o_pasaporte'])) {
                $dni = trim($data['numero_dni_o_pasaporte']);
            }
            
            Log::info('Validación de datos frontal', [
                'nombre' => $nombre,
                'apellido1' => $apellido1,
                'dni' => $dni,
                'data_keys' => array_keys($data)
            ]);
            
            // Verificar que no sean valores por defecto
            foreach ($defaultValues['nombre'] as $defaultNombre) {
                if ($nombre === strtolower($defaultNombre)) {
                    Log::warning('Nombre por defecto detectado', ['nombre' => $nombre]);
                    return false;
                }
            }
            
            foreach ($defaultValues['apellido1'] as $defaultApellido) {
                if ($apellido1 === strtolower($defaultApellido)) {
                    Log::warning('Apellido por defecto detectado', ['apellido' => $apellido1]);
                    return false;
                }
            }
            
            foreach ($defaultValues['dni'] as $defaultDni) {
                if ($dni === $defaultDni) {
                    Log::warning('DNI por defecto detectado', ['dni' => $dni]);
                    return false;
                }
            }
            
            // Verificar que haya datos mínimos válidos
            if (empty($nombre) || empty($apellido1) || empty($dni)) {
                return false;
            }
            
            // Verificar formato básico de DNI (8 dígitos + letra o formato similar)
            // Pero también aceptar pasaportes y otros formatos
            if (!empty($dni)) {
                // Si es DNI español (8-9 dígitos + letra)
                if (preg_match('/^[0-9]{8,9}[A-Z]?$/', $dni)) {
                    // Formato correcto
                } elseif (strlen($dni) < 5) {
                    // Si es muy corto, puede ser inválido
                    Log::warning('DNI muy corto', ['dni' => $dni, 'length' => strlen($dni)]);
                    return false;
                }
                // Si tiene más de 5 caracteres, puede ser pasaporte u otro documento, aceptarlo
            }
        } else if ($side === 'rear') {
            // Para el reverso, validar que haya al menos dirección o localidad
            $direccion = trim($data['direccion'] ?? '');
            $localidad = trim($data['localidad'] ?? '');
            
            // Si no hay datos de dirección, está bien (puede ser pasaporte)
            // Pero si hay datos, deben ser válidos
            if (!empty($direccion) && strlen($direccion) < 5) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Guardar imagen procesada
     */
    private function saveProcessedImage($personaId, $side, $data, $tipo = 'cliente')
    {
        try {
            // Si tu IA devuelve la imagen procesada, la guardamos
            if (isset($data['processed_image']) && !empty($data['processed_image'])) {
                $imageData = base64_decode($data['processed_image']);
                
                $filename = 'dni_' . $side . '_' . time() . '.jpg';
                $path = 'photos/' . $filename;
                $fullPath = public_path($path);
                
                // Crear directorio si no existe
                if (!file_exists(dirname($fullPath))) {
                    mkdir(dirname($fullPath), 0755, true);
                }
                
                file_put_contents($fullPath, $imageData);
                
                // Determinar categoría de foto
                $categoriaId = $side === 'front' ? 13 : 14; // 13=frontal, 14=trasera
                
                // Guardar en base de datos
                Photo::create([
                    'cliente_id' => $clienteId,
                    'photo_categoria_id' => $categoriaId,
                    'url' => $path,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                Log::info('Imagen procesada guardada', [
                    'cliente_id' => $clienteId,
                    'side' => $side,
                    'path' => $path
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error guardando imagen procesada: ' . $e->getMessage(), [
                'cliente_id' => $clienteId,
                'side' => $side
            ]);
        }
    }
    
    /**
     * Guardar datos temporales para huéspedes nuevos (aún no creados)
     */
    private function guardarDatosTemporales($token, $personaIndex, $side, $data, $imagePath)
    {
        $sessionKey = "dni_temp_data_{$token}_{$personaIndex}";
        $tempData = session($sessionKey, []);
        
        // Guardar datos del lado procesado
        $tempData[$side] = $data;
        $tempData["{$side}_image_path"] = $imagePath;
        $tempData['persona_index'] = $personaIndex;
        $tempData['updated_at'] = now()->toDateTimeString();
        
        // Si es el frontal, guardar también datos básicos
        if ($side === 'front') {
            $tempData['nombre'] = $data['nombre'] ?? '';
            $tempData['apellido1'] = $data['apellido1'] ?? '';
            $tempData['apellido2'] = $data['apellido2'] ?? '';
            $tempData['dni'] = $data['dni'] ?? '';
        }
        
        // Si es el reverso, actualizar lugar de nacimiento si viene
        if ($side === 'rear' && isset($data['lugar_nacimiento'])) {
            $tempData['lugar_nacimiento'] = $data['lugar_nacimiento'];
        }
        
        session([$sessionKey => $tempData]);
        
        Log::info('Datos temporales guardados en sesión', [
            'session_key' => $sessionKey,
            'persona_index' => $personaIndex,
            'side' => $side,
            'data_keys' => array_keys($tempData)
        ]);
    }
    
    /**
     * Obtener datos temporales de un huésped nuevo
     */
    public function getTemporaryData($token, $personaIndex)
    {
        $sessionKey = "dni_temp_data_{$token}_{$personaIndex}";
        return session($sessionKey, []);
    }
    
    /**
     * Completar proceso de verificación
     */
    public function completeVerification(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string'
            ]);
            
            $reserva = Reserva::where('token', $request->token)->first();
            
            if (!$reserva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }
            
            $cliente = $reserva->cliente;
            
            // Recargar el cliente para obtener los datos más recientes
            $cliente->refresh();
            
            // SIEMPRE marcar dni_entregado = true cuando se completa la verificación (independientemente de datos completos)
            $reserva->update(['dni_entregado' => true]);
            Log::info('dni_entregado actualizado en reserva - verificación completada', [
                'reserva_id' => $reserva->id,
                'cliente_id' => $cliente->id
            ]);
            
            // data_dni = true SOLO si tiene todos los datos obligatorios para MIR
            if ($this->verificarDatosCompletos($cliente)) {
                $cliente->update([
                    'data_dni' => true,
                    'updated_at' => now()
                ]);
                
                Log::info('Verificación de DNI completada - data_dni marcado como true', [
                    'reserva_id' => $reserva->id,
                    'cliente_id' => $cliente->id
                ]);
            } else {
                Log::warning('Verificación de DNI completada pero NO se marca data_dni = true: faltan datos obligatorios para MIR', [
                    'reserva_id' => $reserva->id,
                    'cliente_id' => $cliente->id,
                    'fecha_nacimiento' => $cliente->fecha_nacimiento,
                    'fecha_expedicion_doc' => $cliente->fecha_expedicion_doc,
                    'email' => $cliente->email,
                    'telefono_movil' => $cliente->telefono_movil,
                    'provincia' => $cliente->provincia
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Verificación completada exitosamente',
                'redirect_url' => route('gracias.index', $cliente->idioma ? $cliente->idioma : 'es')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error completando verificación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
    
    /**
     * Guardar fotos permanentemente en public/imagesCliente/ y crear registros en photos
     * @return array Array con información de las fotos guardadas ['front' => bool, 'rear' => bool]
     */
    private function guardarFotosPermanentes($reserva, $persona, $personaTipo, $index, $token)
    {
        $resultado = ['front' => false, 'rear' => false];
        
        try {
            // Verificar que la persona existe y tiene ID
            if (!$persona || !$persona->id) {
                Log::warning('Persona no válida para guardar fotos', [
                    'persona_tipo' => $personaTipo,
                    'index' => $index,
                    'persona_id' => $persona->id ?? null
                ]);
                return $resultado;
            }
            
            $sides = ['front' => ['categoria' => 13, 'nombre' => 'FrontalDNI'], 'rear' => ['categoria' => 14, 'nombre' => 'TraseraDNI']];
            
            Log::info('Iniciando guardado de fotos permanentes', [
                'persona_tipo' => $personaTipo,
                'persona_id' => $persona->id,
                'index' => $index,
                'reserva_id' => $reserva->id
            ]);
            
            foreach ($sides as $side => $config) {
                $imagePath = null;
                
                // Buscar la foto procesada
                // 1. Para huéspedes nuevos: buscar en sesión primero
                if ($personaTipo === 'huesped') {
                    $sessionKey = "dni_temp_data_{$token}_{$index}";
                    $tempData = session($sessionKey, []);
                    $imagePath = $tempData["{$side}_image_path"] ?? null;
                    
                    // Verificar que el archivo existe si está en sesión
                    if ($imagePath && !file_exists($imagePath)) {
                        Log::warning('Ruta en sesión no existe', [
                            'image_path' => $imagePath,
                            'persona_tipo' => $personaTipo,
                            'index' => $index
                        ]);
                        $imagePath = null; // Resetear para buscar en storage
                    }
                }
                
                // 2. Buscar en storage/app/temp/ por patrón (para clientes y huéspedes si no está en sesión)
                if (!$imagePath || !file_exists($imagePath)) {
                    $prefix = $personaTipo === 'cliente' ? 'cliente' : 'huesped';
                    $personaId = $persona->id;
                    
                    // Buscar archivos que coincidan con el patrón
                    $tempDir = storage_path('app/temp');
                    if (!is_dir($tempDir)) {
                        Log::warning('Directorio temp no existe', ['temp_dir' => $tempDir]);
                        continue;
                    }
                    
                    // Buscar todos los archivos que empiecen con el patrón
                    // Formato: dni_{prefix}_{side}_{timestamp}_{personaId}.{extension}
                    $pattern = "dni_{$prefix}_{$side}_*_{$personaId}.*";
                    $files = glob($tempDir . '/' . $pattern);
                    
                    // Si no encuentra con el patrón exacto, buscar por prefijo más flexible
                    if (empty($files)) {
                        $pattern2 = "dni_{$prefix}_{$side}_*";
                        $allFiles = glob($tempDir . '/' . $pattern2);
                        if ($allFiles) {
                            // Filtrar por ID de persona en el nombre (debe estar al final antes de la extensión)
                            $files = array_filter($allFiles, function($file) use ($personaId) {
                                $basename = basename($file);
                                // Buscar el ID en el nombre del archivo
                                return strpos($basename, '_' . $personaId . '.') !== false || 
                                       strpos($basename, '_' . $personaId) !== false;
                            });
                        }
                    }
                    
                    if (!empty($files)) {
                        // Filtrar solo archivos que existen y son legibles
                        $files = array_filter($files, function($file) {
                            return file_exists($file) && is_readable($file);
                        });
                        
                        if (!empty($files)) {
                            // Ordenar por tiempo de modificación (más reciente primero)
                            usort($files, function($a, $b) {
                                $timeA = @filemtime($a) ?: 0;
                                $timeB = @filemtime($b) ?: 0;
                                return $timeB - $timeA;
                            });
                            $imagePath = $files[0];
                        }
                    }
                }
                
                // Si encontramos la foto, moverla a public/imagesCliente/
                if ($imagePath && file_exists($imagePath) && is_readable($imagePath)) {
                    try {
                        // Verificar que es un archivo válido
                        if (!is_file($imagePath)) {
                            Log::warning('Ruta no es un archivo válido', ['image_path' => $imagePath]);
                            continue;
                        }
                        
                        // Crear directorio si no existe
                        $uploadPath = public_path('imagesCliente');
                        if (!file_exists($uploadPath)) {
                            if (!mkdir($uploadPath, 0755, true)) {
                                Log::error('No se pudo crear directorio', ['upload_path' => $uploadPath]);
                                continue;
                            }
                        }
                        
                        // Verificar permisos de escritura
                        if (!is_writable($uploadPath)) {
                            Log::error('Directorio no tiene permisos de escritura', ['upload_path' => $uploadPath]);
                            continue;
                        }
                        
                        // Generar nombre único para el archivo final
                        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
                        // Validar extensión
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                        if (!in_array($extension, $allowedExtensions)) {
                            Log::warning('Extensión no permitida', [
                                'extension' => $extension,
                                'image_path' => $imagePath
                            ]);
                            continue;
                        }
                        
                        $imageName = time() . '_' . $persona->id . '_' . $config['nombre'] . '_' . uniqid() . '.' . $extension;
                        $destinationPath = $uploadPath . '/' . $imageName;
                        
                        // Verificar que no exista ya el archivo (muy improbable pero por seguridad)
                        if (file_exists($destinationPath)) {
                            $imageName = time() . '_' . $persona->id . '_' . $config['nombre'] . '_' . uniqid() . '_' . rand(1000, 9999) . '.' . $extension;
                            $destinationPath = $uploadPath . '/' . $imageName;
                        }
                        
                        // Mover/copiar el archivo
                        if (@copy($imagePath, $destinationPath)) {
                            // Verificar que se copió correctamente
                            if (!file_exists($destinationPath) || filesize($destinationPath) === 0) {
                                Log::error('Archivo copiado pero está vacío o no existe', [
                                    'destination' => $destinationPath,
                                    'source' => $imagePath
                                ]);
                                @unlink($destinationPath); // Limpiar archivo vacío
                                continue;
                            }
                            // Comprimir imagen si es necesario
                            $this->comprimirImagenSiNecesario($destinationPath);
                            
                            $imageUrl = 'imagesCliente/' . $imageName;
                            
                            // Determinar si es huésped o cliente
                            $esHuesped = ($personaTipo === 'huesped');
                            
                            // Buscar si ya existe una foto para esta persona, reserva y categoría
                            $query = \App\Models\Photo::where('reserva_id', $reserva->id)
                                ->where('photo_categoria_id', $config['categoria']);
                            
                            if ($esHuesped) {
                                $query->where('huespedes_id', $persona->id);
                            } else {
                                $query->where('cliente_id', $persona->id);
                            }
                            
                            $imagenExistente = $query->first();
                            
                            if ($imagenExistente) {
                                // Actualizar imagen existente
                                $rutaImagenAntigua = public_path($imagenExistente->url);
                                if (file_exists($rutaImagenAntigua)) {
                                    @unlink($rutaImagenAntigua);
                                }
                                $imagenExistente->url = $imageUrl;
                                $imagenExistente->save();
                                
                                Log::info('Imagen actualizada en BD', [
                                    'persona_tipo' => $personaTipo,
                                    'persona_id' => $persona->id,
                                    'side' => $side,
                                    'categoria' => $config['categoria'],
                                    'url' => $imageUrl
                                ]);
                            } else {
                                // Crear nuevo registro
                                try {
                                    $photoData = [
                                        'reserva_id' => $reserva->id,
                                        'photo_categoria_id' => $config['categoria'],
                                        'url' => $imageUrl,
                                    ];
                                    
                                    if ($esHuesped) {
                                        $photoData['huespedes_id'] = $persona->id;
                                    } else {
                                        $photoData['cliente_id'] = $persona->id;
                                    }
                                    
                                    $photo = \App\Models\Photo::create($photoData);
                                    
                                    Log::info('Nueva imagen guardada en BD', [
                                        'persona_tipo' => $personaTipo,
                                        'persona_id' => $persona->id,
                                        'side' => $side,
                                        'categoria' => $config['categoria'],
                                        'url' => $imageUrl,
                                        'photo_id' => $photo->id
                                    ]);
                                } catch (\Exception $dbError) {
                                    Log::error('Error creando registro en BD', [
                                        'persona_tipo' => $personaTipo,
                                        'persona_id' => $persona->id,
                                        'side' => $side,
                                        'error' => $dbError->getMessage(),
                                        'photo_data' => $photoData
                                    ]);
                                    // Intentar eliminar el archivo copiado si falla la BD
                                    @unlink($destinationPath);
                                    throw $dbError; // Re-lanzar para que se capture en el catch externo
                                }
                            }
                            
                            // Eliminar archivo temporal después de moverlo (solo si está en storage/app/temp)
                            if (strpos($imagePath, storage_path('app/temp')) !== false) {
                                @unlink($imagePath);
                            }
                            
                            // Marcar como guardado exitosamente
                            $resultado[$side] = true;
                            
                            Log::info('Foto guardada exitosamente', [
                                'persona_tipo' => $personaTipo,
                                'persona_id' => $persona->id,
                                'side' => $side,
                                'url' => $imageUrl,
                                'destination' => $destinationPath
                            ]);
                        } else {
                            $error = error_get_last();
                            Log::warning('No se pudo copiar la imagen temporal', [
                                'from' => $imagePath,
                                'to' => $destinationPath,
                                'error' => $error['message'] ?? 'Error desconocido',
                                'source_exists' => file_exists($imagePath),
                                'source_readable' => is_readable($imagePath),
                                'dest_dir_writable' => is_writable($uploadPath)
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error guardando foto permanente', [
                            'persona_tipo' => $personaTipo,
                            'persona_id' => $persona->id ?? null,
                            'side' => $side,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    Log::warning('No se encontró foto procesada para guardar', [
                        'persona_tipo' => $personaTipo,
                        'persona_id' => $persona->id ?? null,
                        'side' => $side,
                        'index' => $index,
                        'temp_dir' => storage_path('app/temp'),
                        'temp_dir_exists' => is_dir(storage_path('app/temp')),
                        'session_key' => $personaTipo === 'huesped' ? "dni_temp_data_{$token}_{$index}" : null
                    ]);
                    
                    // Listar archivos en temp para debugging
                    $tempDir = storage_path('app/temp');
                    if (is_dir($tempDir)) {
                        $allFiles = glob($tempDir . '/dni_*');
                        Log::info('Archivos encontrados en temp', [
                            'count' => count($allFiles),
                            'files' => array_map('basename', array_slice($allFiles, 0, 10)) // Primeros 10 para no saturar logs
                        ]);
                    }
                }
            }
            
            Log::info('Finalizado guardado de fotos permanentes', [
                'persona_tipo' => $personaTipo,
                'persona_id' => $persona->id,
                'index' => $index,
                'resultado' => $resultado
            ]);
            
            return $resultado;
        } catch (\Exception $e) {
            Log::error('Error en guardarFotosPermanentes', [
                'persona_tipo' => $personaTipo,
                'persona_id' => $persona->id ?? null,
                'index' => $index,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $resultado;
        }
    }
    
    /**
     * Comprimir imagen si es necesario (similar al método del DNIController)
     */
    private function comprimirImagenSiNecesario($imagePath)
    {
        try {
            // Verificar que el archivo existe y es legible
            if (!file_exists($imagePath) || !is_readable($imagePath)) {
                Log::warning('Archivo no existe o no es legible para comprimir', ['path' => $imagePath]);
                return;
            }
            
            $maxSize = 2 * 1024 * 1024; // 2MB
            $fileSize = filesize($imagePath);
            
            if ($fileSize <= $maxSize) {
                return; // No necesita compresión
            }
            
            $imageInfo = @getimagesize($imagePath);
            if (!$imageInfo) {
                Log::warning('No se pudo obtener información de la imagen', ['path' => $imagePath]);
                return;
            }
            
            $mimeType = $imageInfo['mime'];
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            
            // Verificar dimensiones válidas
            if ($width <= 0 || $height <= 0) {
                Log::warning('Dimensiones de imagen inválidas', [
                    'path' => $imagePath,
                    'width' => $width,
                    'height' => $height
                ]);
                return;
            }
            
            // Crear imagen desde archivo
            $source = null;
            switch ($mimeType) {
                case 'image/jpeg':
                    $source = @imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $source = @imagecreatefrompng($imagePath);
                    break;
                case 'image/webp':
                    $source = @imagecreatefromwebp($imagePath);
                    break;
                default:
                    Log::info('Tipo MIME no soportado para compresión', ['mime' => $mimeType, 'path' => $imagePath]);
                    return; // Tipo no soportado
            }
            
            if (!$source) {
                Log::warning('No se pudo crear recurso de imagen desde archivo', ['path' => $imagePath, 'mime' => $mimeType]);
                return;
            }
            
            // Redimensionar si es muy grande (máximo 1920px de ancho)
            $newWidth = $width;
            $newHeight = $height;
            if ($width > 1920) {
                $newWidth = 1920;
                $newHeight = intval(($height * 1920) / $width);
            }
            
            // Crear nueva imagen redimensionada
            $destination = @imagecreatetruecolor($newWidth, $newHeight);
            if (!$destination) {
                imagedestroy($source);
                Log::error('No se pudo crear imagen destino para compresión', ['path' => $imagePath]);
                return;
            }
            
            // Preservar transparencia para PNG
            if ($mimeType === 'image/png') {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
            }
            
            $copyResult = @imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            if (!$copyResult) {
                imagedestroy($source);
                imagedestroy($destination);
                Log::error('No se pudo redimensionar imagen', ['path' => $imagePath]);
                return;
            }
            
            // Guardar comprimida
            $saveResult = false;
            $quality = 85; // Calidad JPEG
            if ($mimeType === 'image/png') {
                $saveResult = @imagepng($destination, $imagePath, 8); // Nivel de compresión PNG (0-9)
            } else {
                $saveResult = @imagejpeg($destination, $imagePath, $quality);
            }
            
            // Liberar memoria
            imagedestroy($source);
            imagedestroy($destination);
            
            if ($saveResult) {
                $newSize = filesize($imagePath);
                Log::info('Imagen comprimida exitosamente', [
                    'path' => $imagePath,
                    'original_size' => $fileSize,
                    'new_size' => $newSize,
                    'reduction' => round((1 - ($newSize / $fileSize)) * 100, 2) . '%'
                ]);
            } else {
                Log::warning('No se pudo guardar imagen comprimida', ['path' => $imagePath]);
            }
        } catch (\Exception $e) {
            Log::warning('Error comprimiendo imagen (continuando de todas formas)', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}



