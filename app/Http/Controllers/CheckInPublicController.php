<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Huesped;
use App\Models\Photo;
use App\Services\MIRService;
use App\Services\AlertaEquipoService;

class CheckInPublicController extends Controller
{
    /**
     * Step 1: Show DNI upload form
     */
    public function index($token)
    {
        $reserva = $this->getReservaByToken($token);

        if (!$reserva) {
            abort(404, 'Reserva no encontrada');
        }

        $cliente = $reserva->cliente;
        if (!$cliente) {
            abort(404, 'Cliente no encontrado');
        }

        // If DNI already submitted for this reservation, redirect to success
        if (!empty($reserva->dni_entregado)) {
            return redirect()->route('checkin.public.success', $token);
        }

        // Set locale
        $this->setAppLocale($cliente);

        // Store reservation context in session
        session([
            'checkin_token' => $token,
            'checkin_reserva_id' => $reserva->id,
        ]);

        return view('checkin.step1', [
            'token' => $token,
            'reserva' => $reserva,
            'cliente' => $cliente,
        ]);
    }

    /**
     * Process uploaded DNI images via AI extraction
     */
    public function processImages(Request $request, $token)
    {
        $request->headers->set('Accept', 'application/json');

        $reserva = $this->getReservaByToken($token);
        if (!$reserva) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada'], 404);
        }

        $request->validate([
            'dni_front' => 'required|file|max:10240|mimes:jpeg,jpg,png,webp',
            'dni_back' => 'nullable|file|max:10240|mimes:jpeg,jpg,png,webp',
            'doc_type' => 'nullable|string|in:dni,passport',
        ]);

        $docType = $request->input('doc_type', 'dni');
        $isPassport = $docType === 'passport';

        // Save uploaded files
        $frontFile = $request->file('dni_front');
        $frontPath = $this->saveUploadedImage($frontFile, $reserva->cliente_id, $isPassport ? 'passport' : 'front');

        $backPath = null;
        if (!$isPassport && $request->hasFile('dni_back')) {
            $backFile = $request->file('dni_back');
            $backPath = $this->saveUploadedImage($backFile, $reserva->cliente_id, 'rear');
        }

        // Process with Hawkins AI
        $extractedData = [];
        try {
            $aiSide = $isPassport ? 'passport' : 'front';
            Log::info('[CheckIn AI] Enviando foto a Hawkins AI', ['path' => $frontPath, 'side' => $aiSide, 'doc_type' => $docType]);
            $frontResult = $this->sendToAI($frontPath, $aiSide);
            Log::info('[CheckIn AI] Resultado frontal', [
                'success' => $frontResult['success'],
                'data_keys' => !empty($frontResult['data']) ? array_keys($frontResult['data']) : [],
                'error' => $frontResult['error'] ?? null,
            ]);
            if ($frontResult['success'] && !empty($frontResult['data'])) {
                $extractedData = $this->mapAIDataToFormFields($frontResult['data'], 'front');
                Log::info('[CheckIn AI] Datos mapeados del frontal', ['fields' => array_keys($extractedData)]);
            }

            if ($backPath) {
                Log::info('[CheckIn AI] Enviando foto reverso a Hawkins AI', ['path' => $backPath]);
                $backResult = $this->sendToAI($backPath, 'rear');
                Log::info('[CheckIn AI] Resultado reverso', [
                    'success' => $backResult['success'],
                    'data_keys' => !empty($backResult['data']) ? array_keys($backResult['data']) : [],
                ]);
                if ($backResult['success'] && !empty($backResult['data'])) {
                    $backMapped = $this->mapAIDataToFormFields($backResult['data'], 'rear');
                    $extractedData = array_merge($extractedData, $backMapped);
                }
            }
        } catch (\Exception $e) {
            Log::error('[CheckIn AI] Excepcion en extraccion', ['error' => $e->getMessage()]);
        }

        // Para pasaportes extranjeros, pre-rellenar dirección con la del apartamento
        if ($isPassport) {
            $apartamento = $reserva->apartamento;
            $edificio = $apartamento->edificio ?? null;
            if (empty($extractedData['address']) && $apartamento) {
                $extractedData['address'] = $apartamento->direccion ?? ($edificio->direccion ?? '');
            }
            if (empty($extractedData['city'])) {
                $extractedData['city'] = $apartamento->localidad ?? ($edificio->localidad ?? 'Algeciras');
            }
            if (empty($extractedData['postal_code'])) {
                $extractedData['postal_code'] = $apartamento->codigo_postal ?? ($edificio->codigo_postal ?? '11201');
            }
            if (empty($extractedData['province'])) {
                $extractedData['province'] = $apartamento->provincia ?? ($edificio->provincia ?? 'Cádiz');
            }
            $extractedData['doc_type'] = 'passport';
        } else {
            $extractedData['doc_type'] = 'dni';
        }

        Log::info('[CheckIn AI] Datos finales enviados al formulario', [
            'total_fields' => count($extractedData),
            'fields' => array_keys($extractedData),
            'doc_type' => $docType,
        ]);

        // Store image paths in session for later use
        session([
            'checkin_dni_front' => $frontPath,
            'checkin_dni_back' => $backPath,
        ]);

        // Pre-fill with existing client data
        $cliente = $reserva->cliente;
        if ($cliente) {
            if (empty($extractedData['first_name']) && $cliente->nombre) {
                $extractedData['first_name'] = $cliente->nombre;
            }
            if (empty($extractedData['last_name'])) {
                $apellidos = trim(($cliente->apellido1 ?? '') . ' ' . ($cliente->apellido2 ?? ''));
                if ($apellidos) $extractedData['last_name'] = $apellidos;
            }
            if (empty($extractedData['document_number']) && $cliente->num_identificacion) {
                $extractedData['document_number'] = $cliente->num_identificacion;
            }
            if (empty($extractedData['phone']) && $cliente->telefono_movil) {
                $extractedData['phone'] = $cliente->telefono_movil;
            }
            if (empty($extractedData['email']) && $cliente->email) {
                $extractedData['email'] = $cliente->email;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $extractedData,
        ]);
    }

    /**
     * Step 2: Show data confirmation form
     */
    public function form($token)
    {
        $reserva = $this->getReservaByToken($token);
        if (!$reserva) {
            abort(404, 'Reserva no encontrada');
        }

        $cliente = $reserva->cliente;
        if (!$cliente) {
            abort(404, 'Cliente no encontrado');
        }

        // If no images uploaded, redirect back to step 1
        if (!session()->has('checkin_dni_front')) {
            return redirect()->route('checkin.public.index', $token)
                ->with('error', __('Por favor sube tu documento primero.'));
        }

        $this->setAppLocale($cliente);

        $numeroPersonas = max(1, $reserva->numero_personas ?? 1);

        return view('checkin.step2', [
            'token' => $token,
            'reserva' => $reserva,
            'cliente' => $cliente,
            'numeroPersonas' => $numeroPersonas,
        ]);
    }

    /**
     * Store check-in data
     */
    public function store(Request $request, $token)
    {
        $reserva = $this->getReservaByToken($token);
        if (!$reserva) {
            abort(404, 'Reserva no encontrada');
        }

        $cliente = $reserva->cliente;
        if (!$cliente) {
            abort(404, 'Cliente no encontrado');
        }

        // Validate main guest data
        $request->validate([
            'guests' => 'required|array|min:1|max:10',
            'guests.0.first_name' => 'required|string|max:255',
            'guests.0.last_name' => 'required|string|max:255',
            'guests.0.gender' => 'required|in:M,F,O',
            'guests.0.birth_date' => 'required|date',
            'guests.0.nationality' => 'required|string|max:255',
            'guests.0.document_type' => 'required|in:DNI,NIE,Passport',
            'guests.0.document_number' => 'required|string|max:50',
            'guests.0.expiry_date' => 'required|date',
            'guests.0.address' => 'required|string|max:500',
            'guests.0.postal_code' => 'required|string|max:20',
            'guests.0.city' => 'required|string|max:255',
            'guests.0.country' => 'required|string|max:255',
            'guests.0.phone' => 'required|string|max:50',
            'guests.0.email' => 'required|email|max:255',
            'guests.*.first_name' => 'required|string|max:255',
            'guests.*.last_name' => 'required|string|max:255',
            'guests.*.document_type' => 'required|in:DNI,NIE,Passport',
            'guests.*.document_number' => 'required|string|max:50',
            'guests.*.birth_date' => 'required|date',
            'guests.*.nationality' => 'required|string|max:255',
            'guests.*.gender' => 'required|in:M,F,O',
            'signature_data' => 'required|string',
        ]);

        $guestsData = $request->input('guests', []);

        // Save signature
        $signaturePath = null;
        if ($request->filled('signature_data')) {
            $signaturePath = $this->saveSignature($request->input('signature_data'), $reserva->id);
        }

        // Process guest 0 -> update Cliente
        $mainGuest = $guestsData[0];
        $this->updateCliente($cliente, $mainGuest);

        // Save DNI photos for the main client
        $this->savePhotos($reserva, $cliente->id, 'cliente');

        // Process ALL guests -> create/update Huesped records (incluido el titular)
        // MIR exige al menos un Huesped con numero_identificacion
        $existingHuespedes = Huesped::where('reserva_id', $reserva->id)->orderBy('id')->get();

        for ($i = 0; $i < count($guestsData); $i++) {
            $guestData = $guestsData[$i];
            $huesped = $existingHuespedes->get($i) ?? new Huesped();
            $this->updateHuesped($huesped, $guestData, $reserva->id);
        }

        // Mark reservation as DNI submitted
        $reserva->update(['dni_entregado' => true]);

        // Check if all MIR data is complete
        $cliente->refresh();
        if ($this->verificarDatosCompletos($cliente)) {
            $cliente->update(['data_dni' => true]);
        }

        // Generar código de acceso si no tiene uno aún
        if (empty($reserva->codigo_acceso)) {
            try {
                $accessCodeService = app(\App\Services\AccessCodeService::class);
                $accessCodeService->generarYProgramar($reserva);
                $reserva->refresh();
                Log::info('Código de acceso generado tras check-in', [
                    'reserva_id' => $reserva->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Error generando código de acceso tras check-in', [
                    'reserva_id' => $reserva->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Si tiene código pero no se envió a la cerradura, reintentar ahora (momento del check-in)
        if (!empty($reserva->codigo_acceso) && !$reserva->codigo_enviado_cerradura) {
            try {
                $accessCodeService = app(\App\Services\AccessCodeService::class);
                $accessCodeService->reintentarOFallback($reserva);
                $reserva->refresh();
                Log::info('Reintento de envío a cerradura tras check-in', [
                    'reserva_id' => $reserva->id,
                    'enviado' => $reserva->codigo_enviado_cerradura,
                ]);
            } catch (\Exception $e) {
                Log::error('Error reintentando envío a cerradura tras check-in', [
                    'reserva_id' => $reserva->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('CheckIn completed via new form', [
            'reserva_id' => $reserva->id,
            'cliente_id' => $cliente->id,
            'guests_count' => count($guestsData),
        ]);

        // [FIX 2026-04-19] Pasamos por enviarSiLista() que incluye el preflight
        // (Nivel 1 deterministico + Nivel 3 IA con web_search). Si el preflight
        // detecta un problema (CP no existe, apellido mal partido, etc.), NO se
        // envia a MIR, se marca mir_estado='error_validacion' y se alerta por
        // WhatsApp. Esto evita lotes rechazados por MIR horas despues.
        $reserva->refresh();
        $reserva->load(['cliente', 'apartamento.edificio']);
        try {
            $mirService = new MIRService();
            $resultado = $mirService->enviarSiLista($reserva);
            if ($resultado === null) {
                // enviarSiLista devuelve null si los datos no estan listos o
                // la validacion fallo. No es bug — el propio servicio ya
                // envia alerta WhatsApp si procede.
                Log::info('[MIR] enviarSiLista devolvio null (datos no listos o validacion fallida)', [
                    'reserva_id' => $reserva->id,
                    'mir_estado' => $reserva->fresh()->mir_estado,
                ]);
            } elseif (!empty($resultado['success'])) {
                Log::info('[MIR] Envío inmediato tras checkin OK', [
                    'reserva_id' => $reserva->id,
                    'codigo_reserva' => $reserva->codigo_reserva,
                ]);
            } else {
                Log::warning('[MIR] Envío inmediato tras checkin FALLIDO (reintentará en cron)', [
                    'reserva_id' => $reserva->id,
                    'error' => $resultado['mensaje'] ?? $resultado['error'] ?? 'desconocido',
                ]);
                $this->alertarFalloMIR($reserva, $resultado['mensaje'] ?? $resultado['error'] ?? 'Error desconocido');
            }
        } catch (\Exception $e) {
            Log::error('[MIR] Excepción en envío inmediato tras checkin', [
                'reserva_id' => $reserva->id,
                'error' => $e->getMessage(),
            ]);
            $this->alertarFalloMIR($reserva, $e->getMessage());
        }

        // Clear session
        session()->forget(['checkin_dni_front', 'checkin_dni_back', 'checkin_token', 'checkin_reserva_id']);
        session()->flash('checkin_complete', true);

        return redirect()->route('checkin.public.success', $token);
    }

    /**
     * Success page - solo accesible si el check-in fue completado
     */
    public function success($token)
    {
        $reserva = $this->getReservaByToken($token);
        if (!$reserva) {
            abort(404, 'Reserva no encontrada');
        }

        // Solo accesible si ya completó el check-in
        if (empty($reserva->dni_entregado)) {
            return redirect()->route('checkin.public.index', $token);
        }

        $cliente = $reserva->cliente;
        if ($cliente) {
            $this->setAppLocale($cliente);
        }

        $codigosAcceso = null;
        if ($reserva->codigo_acceso) {
            $codigosAcceso = [
                'codigo_acceso' => $reserva->codigo_acceso,
                'apartamento_titulo' => $reserva->apartamento ? $reserva->apartamento->titulo : null,
                'clave_edificio' => $reserva->apartamento && $reserva->apartamento->edificio
                    ? $reserva->apartamento->edificio->clave
                    : null,
            ];
        }

        return view('checkin.success', [
            'token' => $token,
            'reserva' => $reserva,
            'codigosAcceso' => $codigosAcceso,
        ]);
    }

    /**
     * Change language
     */
    public function changeLocale($token, $locale)
    {
        if (in_array($locale, ['es', 'en'])) {
            session(['locale' => $locale]);

            $reserva = $this->getReservaByToken($token);
            if ($reserva && $reserva->cliente) {
                $reserva->cliente->update(['idioma_establecido' => $locale]);
            }
        }
        return back();
    }

    // ========== Private Helpers ==========

    private function getReservaByToken($token)
    {
        return Reserva::with(['apartamento', 'apartamento.edificio', 'cliente', 'estado'])
            ->where('token', $token)
            ->first();
    }

    private function setAppLocale($cliente)
    {
        $locale = session('locale')
            ?? $cliente->idioma_establecido
            ?? $cliente->idioma
            ?? 'es';

        if (!in_array($locale, ['es', 'en'])) {
            $locale = 'es';
        }

        \App::setLocale($locale);
        session(['locale' => $locale]);
    }

    private function saveUploadedImage($file, $personaId, $side)
    {
        $filename = 'dni_checkin_' . $side . '_' . time() . '_' . $personaId . '.' . $file->getClientOriginalExtension();
        $dir = storage_path('app/temp');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir . '/' . $filename;
        $file->move($dir, $filename);
        return $path;
    }

    private function saveSignature($signatureBase64, $reservaId)
    {
        $parts = explode(';base64,', $signatureBase64);
        if (count($parts) !== 2) return null;

        $imageData = base64_decode($parts[1], true);
        if (!$imageData) return null;

        // Validar que sea una imagen real (PNG o JPEG)
        $imageInfo = @getimagesizefromstring($imageData);
        if ($imageInfo === false || !in_array($imageInfo[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG])) {
            Log::warning('Firma rechazada: no es una imagen válida', ['reserva_id' => $reservaId]);
            return null;
        }

        $dir = storage_path('app/signatures');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'firma_reserva_' . $reservaId . '_' . time() . '.png';
        $path = $dir . '/' . $filename;
        file_put_contents($path, $imageData);

        return $path;
    }

    private function updateCliente(Cliente $cliente, array $data)
    {
        $apellidos = explode(' ', $data['last_name'] ?? '', 2);

        $updateData = [
            'nombre' => $data['first_name'] ?? $cliente->nombre,
            'apellido1' => $apellidos[0] ?? $cliente->apellido1,
            'apellido2' => $apellidos[1] ?? $cliente->apellido2 ?? '',
            'sexo_str' => $this->mapGender($data['gender'] ?? ''),
            'fecha_nacimiento' => $data['birth_date'] ?? $cliente->fecha_nacimiento,
            'nacionalidadStr' => $data['nationality'] ?? $cliente->nacionalidadStr,
            'tipo_documento_str' => $data['document_type'] ?? $cliente->tipo_documento_str ?? 'DNI',
            'num_identificacion' => $data['document_number'] ?? $cliente->num_identificacion,
            'fecha_expedicion_doc' => $data['exp_date'] ?? $cliente->fecha_expedicion_doc,
            'telefono_movil' => $data['phone'] ?? $cliente->telefono_movil,
            'email' => $data['email'] ?? $cliente->email,
            'direccion' => $data['address'] ?? $cliente->direccion,
            'codigo_postal' => $data['postal_code'] ?? $cliente->codigo_postal,
            'localidad' => $data['city'] ?? $cliente->localidad,
            'provincia' => $data['province'] ?? $data['city'] ?? $cliente->provincia,
            'numero_soporte_documento' => $data['document_support_number'] ?? $cliente->numero_soporte_documento,
            'nacionalidad' => $this->mapNacionalidadCodigo($data['nationality'] ?? $cliente->nacionalidad ?? ''),
        ];

        // Map sexo to numeric if the model uses numeric
        if (isset($data['gender'])) {
            $sexoMap = ['M' => 1, 'F' => 2, 'O' => 0];
            $updateData['sexo'] = $sexoMap[$data['gender']] ?? 0;
        }

        // Map document type to numeric
        if (isset($data['document_type'])) {
            $tipoDocMap = ['DNI' => 1, 'NIE' => 3, 'Passport' => 2];
            $updateData['tipo_documento'] = $tipoDocMap[$data['document_type']] ?? 1;
        }

        $cliente->update(array_filter($updateData, function ($v) {
            return $v !== null && $v !== '';
        }));
    }

    private function updateHuesped(Huesped $huesped, array $data, int $reservaId)
    {
        $apellidos = explode(' ', $data['last_name'] ?? '', 2);

        $huespedData = [
            'reserva_id' => $reservaId,
            'nombre' => $data['first_name'] ?? '',
            'primer_apellido' => $apellidos[0] ?? '',
            'segundo_apellido' => $apellidos[1] ?? '',
            'sexo_str' => $this->mapGender($data['gender'] ?? ''),
            'fecha_nacimiento' => $data['birth_date'] ?? null,
            'nacionalidadStr' => $data['nationality'] ?? '',
            'tipo_documento_str' => $data['document_type'] ?? 'DNI',
            'numero_identificacion' => $data['document_number'] ?? '',
            'telefono_movil' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'direccion' => $data['address'] ?? '',
            'codigo_postal' => $data['postal_code'] ?? '',
            'localidad' => $data['city'] ?? '',
            'provincia' => $data['province'] ?? $data['city'] ?? '',
            'relacion_parentesco' => $data['relationship'] ?? '',
        ];

        if (isset($data['gender'])) {
            $sexoMap = ['M' => 1, 'F' => 2, 'O' => 0];
            $huespedData['sexo'] = $sexoMap[$data['gender']] ?? 0;
        }

        $huesped->fill($huespedData);
        $huesped->save();
    }

    private function mapGender($gender)
    {
        $map = ['M' => 'Masculino', 'F' => 'Femenino', 'O' => 'Otro'];
        return $map[$gender] ?? '';
    }

    private function savePhotos(Reserva $reserva, $clienteId, $tipo)
    {
        $frontPath = session('checkin_dni_front');
        $backPath = session('checkin_dni_back');

        if ($frontPath && file_exists($frontPath)) {
            $this->createPhoto($reserva, $clienteId, $frontPath, 13, $tipo); // 13 = DNI frontal
        }

        if ($backPath && file_exists($backPath)) {
            $this->createPhoto($reserva, $clienteId, $backPath, 14, $tipo); // 14 = DNI trasera
        }
    }

    private function createPhoto(Reserva $reserva, $personaId, $tempPath, $categoriaId, $tipo)
    {
        try {
            $ext = pathinfo($tempPath, PATHINFO_EXTENSION) ?: 'jpg';
            $prefix = $tipo === 'cliente' ? 'cliente' : 'huesped';
            $catName = $categoriaId === 13 ? 'frontal' : 'trasera';
            $filename = "dni_{$prefix}_{$catName}_{$personaId}_" . time() . ".{$ext}";

            $destDir = storage_path('app/photos/dni');
            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $destPath = $destDir . '/' . $filename;
            copy($tempPath, $destPath);

            $photoData = [
                'reserva_id' => $reserva->id,
                'photo_categoria_id' => $categoriaId,
                'url' => 'private/photos/dni/' . $filename,
                'nombre' => $filename,
            ];

            if ($tipo === 'cliente') {
                $photoData['cliente_id'] = $personaId;
            } else {
                $photoData['huespedes_id'] = $personaId;
            }

            Photo::create($photoData);

            Log::info('Photo saved for check-in', [
                'reserva_id' => $reserva->id,
                'persona_id' => $personaId,
                'tipo' => $tipo,
                'categoria' => $categoriaId,
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving check-in photo', [
                'error' => $e->getMessage(),
                'reserva_id' => $reserva->id,
            ]);
        }
    }

    private function verificarDatosCompletos($cliente)
    {
        $required = [
            'nombre', 'apellido1', 'fecha_nacimiento', 'nacionalidadStr',
            'tipo_documento', 'num_identificacion', 'fecha_expedicion_doc',
            'sexo', 'email', 'telefono_movil', 'provincia',
        ];

        foreach ($required as $campo) {
            if (empty($cliente->$campo)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Send image to Hawkins AI for document extraction
     * (Reuses the same AI endpoint as DNIScannerController)
     */
    private function sendToAI($imagePath, $side)
    {
        $baseUrl = config('services.hawkins_ai.url', env('HAWKINS_AI_URL'));
        $apiKey = config('services.hawkins_ai.api_key', env('HAWKINS_AI_API_KEY'));
        $model = config('services.hawkins_ai.model', env('HAWKINS_AI_MODEL', 'qwen2.5vl:latest'));

        if (empty($baseUrl) || empty($apiKey)) {
            Log::warning('Hawkins AI not configured: HAWKINS_AI_URL or HAWKINS_AI_API_KEY missing.');
            return ['success' => false, 'error' => 'AI not configured', 'data' => []];
        }

        $fullUrl = rtrim($baseUrl, '/') . '/chat/analyze-image';

        if ($side === 'passport') {
            $prompt = 'Extrae de la imagen del PASAPORTE todos los datos. Busca los datos tanto en el texto visible como en la zona MRZ (las dos lineas de caracteres en la parte inferior). Responde únicamente con un objeto JSON válido. No añadas texto adicional. Usa formato de fecha YYYY-MM-DD. Para nacionalidad usa el codigo ISO de 2 letras (ej: FR, DE, GB, IT, MA, US). Para sexo: M o F.

{
"nombre": "",
"apellidos": "",
"fecha_nacimiento": "",
"lugar_nacimiento": "",
"nacionalidad": "",
"fecha_expedicion": "",
"fecha_caducidad": "",
"numero_dni_o_pasaporte": "",
"tipo_documento": "Passport",
"sexo": ""
}';
        } elseif ($side === 'front') {
            $prompt = 'Extrae de la imagen del DNI o pasaporte español TODOS los datos solicitados. Responde únicamente con un objeto JSON válido EXACTAMENTE en este formato. No añadas texto, explicaciones ni caracteres adicionales. Usa el formato de fecha YYYY-MM-DD. El numero_soporte es el código alfanumérico pequeño que aparece en el DNI español (suele empezar por letras como BAA, BAB, etc.).

{
"nombre": "",
"apellidos": "",
"fecha_nacimiento": "",
"lugar_nacimiento": "",
"nacionalidad": "",
"fecha_expedicion": "",
"fecha_caducidad": "",
"numero_dni_o_pasaporte": "",
"numero_soporte": "",
"tipo_documento": "",
"sexo": ""
}';
        } else {
            $prompt = 'Extrae de la imagen del REVERSO del DNI español TODOS los datos. Responde únicamente con un objeto JSON válido. No añadas texto adicional. El codigo_postal es el numero de 5 digitos que aparece en la direccion (ej: 28001, 11201, 08012). Extráelo de la linea de domicilio separándolo del resto de la dirección.

{
"direccion": "",
"localidad": "",
"codigo_postal": "",
"provincia": "",
"lugar_nacimiento": ""
}';
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'image' => new \CURLFile($imagePath, 'image/jpeg', 'dni_' . $side . '.jpg'),
                'prompt' => $prompt,
                'modelo' => $model,
            ],
            CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey],
            // SSL verify desactivado: aiapi.hawkins.es es dominio propio y el
            // certificado (FNMT) puede caducar sin aviso. Ya rompio el chatbot
            // de WhatsApp y el checkin de DNI el 15/04/2026.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError || $httpCode !== 200) {
            Log::error('AI error in CheckInPublic', [
                'url' => $fullUrl,
                'http_code' => $httpCode,
                'curl_error' => $curlError,
            ]);
            return ['success' => false, 'error' => $curlError ?: "HTTP $httpCode", 'data' => []];
        }

        // Parse response - try multiple strategies
        $data = $this->parseAIResponse($response);

        return ['success' => !empty($data), 'data' => $data];
    }

    private function parseAIResponse($response)
    {
        // Strategy 1: Direct JSON
        $data = json_decode($response, true);
        if (is_array($data) && !empty($data)) {
            // If response has a nested 'response', 'respuesta' or 'data' key
            $innerKey = $data['response'] ?? $data['respuesta'] ?? $data['content'] ?? null;
            if ($innerKey) {
                Log::debug('[CheckIn AI] Extrayendo JSON de key anidada', ['raw' => substr((string)$innerKey, 0, 300)]);
                $inner = json_decode($innerKey, true);
                if (is_array($inner)) return $inner;
                return $this->extractJsonFromText((string)$innerKey);
            }
            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            }
            return $data;
        }

        // Strategy 2: Extract JSON from text
        return $this->extractJsonFromText($response);
    }

    private function extractJsonFromText($text)
    {
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $text, $matches)) {
            $parsed = json_decode($matches[0], true);
            if (is_array($parsed)) return $parsed;
        }
        return [];
    }

    private function mapAIDataToFormFields(array $aiData, $side)
    {
        if ($side === 'front') {
            $mapped = [];
            if (!empty($aiData['nombre'])) $mapped['first_name'] = $aiData['nombre'];
            if (!empty($aiData['apellidos'])) $mapped['last_name'] = $aiData['apellidos'];
            if (!empty($aiData['fecha_nacimiento'])) $mapped['birth_date'] = $aiData['fecha_nacimiento'];
            if (!empty($aiData['nacionalidad'])) $mapped['nationality'] = $aiData['nacionalidad'];
            if (!empty($aiData['numero_dni_o_pasaporte'])) $mapped['document_number'] = $aiData['numero_dni_o_pasaporte'];
            if (!empty($aiData['dni'])) $mapped['document_number'] = $aiData['dni'];
            if (!empty($aiData['tipo_documento'])) $mapped['document_type'] = $this->normalizeDocType($aiData['tipo_documento']);
            if (!empty($aiData['sexo'])) $mapped['gender'] = $this->normalizeGender($aiData['sexo']);
            if (!empty($aiData['fecha_expedicion'])) $mapped['exp_date'] = $aiData['fecha_expedicion'];
            if (!empty($aiData['fecha_caducidad'])) $mapped['expiry_date'] = $aiData['fecha_caducidad'];
            if (!empty($aiData['numero_soporte'])) $mapped['document_support_number'] = $aiData['numero_soporte'];
            return $mapped;
        }

        // Rear side
        $mapped = [];
        if (!empty($aiData['direccion'])) $mapped['address'] = $aiData['direccion'];
        if (!empty($aiData['localidad'])) $mapped['city'] = $aiData['localidad'];
        if (!empty($aiData['codigo_postal'])) $mapped['postal_code'] = $aiData['codigo_postal'];
        if (!empty($aiData['provincia'])) $mapped['province'] = $aiData['provincia'];
        if (!empty($aiData['lugar_nacimiento'])) $mapped['birth_place'] = $aiData['lugar_nacimiento'];
        return $mapped;
    }

    private function normalizeDocType($type)
    {
        $type = strtoupper(trim($type));
        if (in_array($type, ['DNI', 'D.N.I.', 'D.N.I'])) return 'DNI';
        if (in_array($type, ['NIE', 'N.I.E.', 'N.I.E'])) return 'NIE';
        if (strpos($type, 'PASAPORTE') !== false || strpos($type, 'PASSPORT') !== false) return 'Passport';
        return 'DNI';
    }

    private function normalizeGender($gender)
    {
        $gender = strtoupper(trim($gender));
        if (in_array($gender, ['M', 'MASCULINO', 'MALE', 'HOMBRE', 'H'])) return 'M';
        if (in_array($gender, ['F', 'FEMENINO', 'FEMALE', 'MUJER'])) return 'F';
        return 'O';
    }

    private function mapNacionalidadCodigo($nacionalidad)
    {
        if (empty($nacionalidad)) return null;
        $map = [
            'ESPAÑOLA' => 'ES', 'ESPANOLA' => 'ES', 'SPAIN' => 'ES', 'ESPAÑA' => 'ES', 'ESP' => 'ES', 'ES' => 'ES',
            'FRANCESA' => 'FR', 'FRENCH' => 'FR', 'FRANCE' => 'FR', 'FR' => 'FR',
            'ALEMANA' => 'DE', 'GERMAN' => 'DE', 'GERMANY' => 'DE', 'DE' => 'DE',
            'ITALIANA' => 'IT', 'ITALIAN' => 'IT', 'ITALY' => 'IT', 'IT' => 'IT',
            'PORTUGUESA' => 'PT', 'PORTUGUESE' => 'PT', 'PORTUGAL' => 'PT', 'PT' => 'PT',
            'BRITÁNICA' => 'GB', 'BRITISH' => 'GB', 'UK' => 'GB', 'GB' => 'GB',
            'MARROQUÍ' => 'MA', 'MOROCCAN' => 'MA', 'MOROCCO' => 'MA', 'MA' => 'MA',
            'RUMANA' => 'RO', 'ROMANIAN' => 'RO', 'ROMANIA' => 'RO', 'RO' => 'RO',
        ];
        $upper = strtoupper(trim($nacionalidad));
        return $map[$upper] ?? (strlen($upper) <= 3 ? $upper : null);
    }

    /**
     * Enviar alerta cuando el envío a MIR falla.
     * Se envía por email al admin y se intenta por WhatsApp si hay config.
     */
    private function alertarFalloMIR(Reserva $reserva, string $error)
    {
        AlertaEquipoService::mirFallo($reserva, $error);
    }
}
