<?php

namespace App\Http\Controllers;

use App\Models\Anio;
use App\Models\Configuraciones;
use App\Models\EmailNotificaciones;
use App\Models\FormasPago;
use App\Models\LimpiadoraGuardia;
use App\Models\PromptAsistente;
use App\Models\Reparaciones;
use App\Models\SeoMeta;
use App\Models\Setting;
use App\Models\User;
use App\Models\Edificio;
use App\Services\MetodoEntradaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class ConfiguracionesController extends Controller
{
    /**
     * Mostrar la página principal de configuración con todas las pestañas
     * Mantiene compatibilidad con rutas legacy que apuntan a /configuracion
     */
    public function index(){
        // Cargar todas las variables necesarias para las diferentes pestañas
        $configuraciones = Configuraciones::all();
        $edificios = Edificio::orderBy('nombre')->get();
        
        // Variables para Contabilidad
        $anio = app('anio');
        $anioActual = date('Y');
        $anios = [];
        for ($i = 0; $i <= 5; $i++) {
            $anios[] = strval($anioActual - $i);
        }
        
        // Variables para Limpiadoras
        $limpiadorasUsers = User::where('inactive', null)->where('role', 'USER')->get();
        $limpiadorasGuardia = LimpiadoraGuardia::with('usuario')->get();
        
        // Variables para Prompt IA
        $prompt = PromptAsistente::all();
        
        return view('admin.configuraciones.index', compact(
            'configuraciones',
            'edificios',
            'anio',
            'anios',
            'limpiadorasUsers',
            'limpiadorasGuardia',
            'prompt'
        ));
    }

    /**
     * Actualizar método de entrada por edificio (física/digital).
     */
    public function updateMetodoEntrada(Request $request)
    {
        // Normalizar: si viene el option "por defecto" como string vacío (""),
        // lo convertimos a null para que la validación lo acepte y se guarde como "sin seleccionar".
        $metodos = $request->input('metodos', []);
        foreach ($metodos as $id => $metodo) {
            if ($metodo === '') {
                $metodos[$id] = null;
            }
        }
        $request->merge(['metodos' => $metodos]);

        $request->validate([
            'metodos' => 'required|array',
            'metodos.*' => 'nullable|string|in:' . MetodoEntradaService::METODO_FISICA . ',' . MetodoEntradaService::METODO_DIGITAL,
        ]);

        $ids = array_keys($metodos);

        $edificios = Edificio::whereIn('id', $ids)->get();
        foreach ($edificios as $edificio) {
            $edificio->metodo_entrada = $metodos[$edificio->id] ?: null;
            $edificio->save();
        }

        Alert::success('Éxito', 'Método de entrada actualizado correctamente.');
        return redirect()->to(route('configuracion.index') . '#pills-acceso', 303);
    }

    /**
     * Método legacy - redirigir a credenciales
     */
    public function edit($id, Request $request){
        return redirect()->route('configuracion.credenciales.index');
    }

    // Actualizar credenciales - maneja todas las secciones via _section hidden field
    public function update($id, Request $request){
        $section = $request->input('_section', 'plataformas');

        switch ($section) {
            case 'plataformas':
                $confi = Configuraciones::find($id);
                $confi->password_booking = $request->password_booking;
                $confi->password_airbnb = $request->password_airbnb;
                $confi->user_booking = $request->user_booking;
                $confi->user_airbnb = $request->user_airbnb;
                $confi->save();
                // Channex
                if ($request->has('channex_api_token')) {
                    Setting::set('channex_api_token', $request->channex_api_token, 'API Token de Channex');
                }
                if ($request->has('channex_webhook_url')) {
                    Setting::set('channex_webhook_url', $request->channex_webhook_url, 'Webhook URL de Channex');
                }
                Alert::success('Éxito', 'Credenciales de plataformas actualizadas correctamente.');
                break;

            case 'whatsapp':
                if ($request->has('whatsapp_token')) {
                    Setting::set('whatsapp_token', $request->whatsapp_token, 'Token de acceso WhatsApp Business API');
                }
                if ($request->has('whatsapp_phone_id')) {
                    Setting::set('whatsapp_phone_id', $request->whatsapp_phone_id, 'Phone Number ID de WhatsApp Business');
                }
                if ($request->has('whatsapp_api_version')) {
                    Setting::set('whatsapp_api_version', $request->whatsapp_api_version, 'Versión de la API de WhatsApp');
                }
                Alert::success('Éxito', 'Credenciales de WhatsApp actualizadas correctamente.');
                break;

            case 'ia':
                // Hawkins IA
                Setting::set('hawkins_ai_url', $request->hawkins_ai_url, 'URL de Hawkins IA');
                Setting::set('hawkins_ai_api_key', $request->hawkins_ai_api_key, 'API Key de Hawkins IA');
                Setting::set('hawkins_ai_model', $request->hawkins_ai_model, 'Modelo de Hawkins IA');
                // Ollama
                Setting::set('ollama_url', $request->ollama_url, 'URL de Ollama');
                Setting::set('ollama_api_key', $request->ollama_api_key, 'API Key de Ollama');
                Setting::set('ollama_model', $request->ollama_model, 'Modelo de Ollama');
                // OpenAI
                Setting::set('openai_api_key', $request->openai_api_key, 'API Key de OpenAI');
                Setting::set('openai_model', $request->openai_model, 'Modelo de OpenAI');
                // Anthropic
                Setting::set('anthropic_api_key', $request->anthropic_api_key, 'API Key de Anthropic');
                Alert::success('Éxito', 'Credenciales de IA actualizadas correctamente.');
                break;

            case 'pagos':
                Setting::set('stripe_key', $request->stripe_key, 'Stripe Publishable Key');
                Setting::set('stripe_secret', $request->stripe_secret, 'Stripe Secret Key');
                Setting::set('stripe_webhook_secret', $request->stripe_webhook_secret, 'Stripe Webhook Secret');
                Alert::success('Éxito', 'Credenciales de pagos actualizadas correctamente.');
                break;

            case 'cerraduras':
                Setting::set('tuya_app_url', $request->tuya_app_url, 'URL de Tuya App');
                Setting::set('tuya_app_api_key', $request->tuya_app_api_key, 'API Key de Tuya App');
                Alert::success('Éxito', 'Credenciales de cerraduras actualizadas correctamente.');
                break;

            case 'otros':
                Setting::set('recaptcha_site_key', $request->recaptcha_site_key, 'Recaptcha Site Key');
                Setting::set('recaptcha_secret_key', $request->recaptcha_secret_key, 'Recaptcha Secret Key');
                Setting::set('registro_visitantes_url', $request->registro_visitantes_url, 'URL del Registro de Visitantes');
                Alert::success('Éxito', 'Credenciales adicionales actualizadas correctamente.');
                break;

            default:
                // Legacy fallback
                $confi = Configuraciones::find($id);
                $confi->password_booking = $request->password_booking;
                $confi->password_airbnb = $request->password_airbnb;
                $confi->user_booking = $request->user_booking;
                $confi->user_airbnb = $request->user_airbnb;
                $confi->save();
                Alert::success('Éxito', 'Credenciales actualizadas correctamente.');
                break;
        }

        return redirect()->route('configuracion.credenciales.index');
    }

    // Crear reparador
    public function storeReparaciones(Request $request){
        //dd($request->all());
        $data = [
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'lunes' => isset($request->lunes) ? true : null,
            'martes' => isset($request->martes) ? true : null,
            'miercoles' => isset($request->miercoles) ? true : null,
            'jueves' => isset($request->jueves) ? true : null,
            'viernes' => isset($request->viernes) ? true : null,
            'sabado' => isset($request->sabado) ? true : null,
            'domingo' => isset($request->domingo) ? true : null
        ];

        $tecnicoNuevo = Reparaciones::create($data);

        Alert::success('Éxito', 'Técnico de reparaciones creado correctamente.');
        return redirect()->route('configuracion.reparaciones.index');
    }
    // Actualizar los reparadores
    public function updateReparaciones($id, Request $request){
        $data = [
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'lunes' => isset($request->lunes) ? true : null,
            'martes' => isset($request->martes) ? true : null,
            'miercoles' => isset($request->miercoles) ? true : null,
            'jueves' => isset($request->jueves) ? true : null,
            'viernes' => isset($request->viernes) ? true : null,
            'sabado' => isset($request->sabado) ? true : null,
            'domingo' => isset($request->domingo) ? true : null
        ];

        $reparaciones = Reparaciones::find($id);
        $reparaciones->update($data);
        Alert::success('Éxito', 'Técnico de reparaciones actualizado correctamente.');
        return redirect()->route('configuracion.reparaciones.index');
    }
    // Crear reparador
    public function storeLimpiadora(Request $request){
        //dd($request->all());
        $data = [
            'user_id' => $request->user_id,
            'telefono' => $request->telefono,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'lunes' => isset($request->lunes) ? true : null,
            'martes' => isset($request->martes) ? true : null,
            'miercoles' => isset($request->miercoles) ? true : null,
            'jueves' => isset($request->jueves) ? true : null,
            'viernes' => isset($request->viernes) ? true : null,
            'sabado' => isset($request->sabado) ? true : null,
            'domingo' => isset($request->domingo) ? true : null
        ];

        $limpiadoraNueva = LimpiadoraGuardia::create($data);

        Alert::success('Éxito', 'Limpiadora de guardia creada correctamente.');
        return redirect()->route('configuracion.limpiadoras.index');
    }
    // Actualizar los reparadores
    public function updateLimpiadora($id, Request $request){
        $data = [
            // 'user_id' => $request->user_id,
            'telefono' => $request->telefono,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'lunes' => isset($request->lunes) ? true : null,
            'martes' => isset($request->martes) ? true : null,
            'miercoles' => isset($request->miercoles) ? true : null,
            'jueves' => isset($request->jueves) ? true : null,
            'viernes' => isset($request->viernes) ? true : null,
            'sabado' => isset($request->sabado) ? true : null,
            'domingo' => isset($request->domingo) ? true : null
        ];

        $limpiadora = LimpiadoraGuardia::find($id);
        $limpiadora->update($data);
        Alert::success('Éxito', 'Limpiadora de guardia actualizada correctamente.');
        return redirect()->route('configuracion.limpiadoras.index');
    }

    // Obtener User y Pass de Booking
    public function deleteLimpiadora($id){
        try {
            $limpiadora = LimpiadoraGuardia::findOrFail($id);
            $limpiadora->delete();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Limpiadora de guardia eliminada correctamente.'
                ]);
            }
            
            Alert::success('Éxito', 'Limpiadora de guardia eliminada correctamente.');
            return redirect()->route('configuracion.limpiadoras.index');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar la limpiadora: ' . $e->getMessage()
                ], 500);
            }
            
            Alert::error('Error', 'No se pudo eliminar la limpiadora.');
            return redirect()->route('configuracion.limpiadoras.index');
        }
    }

    // Obtener User y Pass de Booking
    public function deleteReparaciones($id){
        try {
            $reparaciones = Reparaciones::findOrFail($id);
            $reparaciones->delete();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Técnico de reparaciones eliminado correctamente.'
                ]);
            }
            
            Alert::success('Éxito', 'Técnico de reparaciones eliminado correctamente.');
            return redirect()->route('configuracion.reparaciones.index');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el técnico: ' . $e->getMessage()
                ], 500);
            }
            
            Alert::error('Error', 'No se pudo eliminar el técnico.');
            return redirect()->route('configuracion.reparaciones.index');
        }
    }
    // Obtener User y Pass de Booking
    public function passBooking(){
        $configuraciones = Configuraciones::first();
        return response()->json([
            'user' => $configuraciones->user_booking,
            'pass' => $configuraciones->password_booking
        ]);
    }

    // Obtener User y Pass de Airbnb
    public function passAirbnb(){
        $configuraciones = Configuraciones::first();
        return response()->json([
            'user' => $configuraciones->user_airbnb,
            'pass' => $configuraciones->password_airbnb
        ]);
    }

    // Actualizar año de gestion
    public function updateAnio(Request $request){
        $anio = Anio::first();
        $anio->anio = $request->anio;
        $anio->save();

        Alert::success('Éxito', 'Año actualizado correctamente.');
        return redirect()->route('configuracion.contabilidad.index');
    }

    // Cierre del año
    public function cierreAnio(Request $request){
        $anio = Anio::first();
        $anio->anio = $request->anio;
        $anio->save();

        Alert::toast('Actualizado', 'success');

        return redirect()->route('configuracion.index');
    }

    // Establecer el Saldo inicial
    public function saldoInicial(Request $request){
        $anio = Anio::first();
        $saldo = $request->saldo_inicial;
    $saldo_inicial = str_replace(',', '.', $request->input('saldo_inicial'));

        if (!$anio) {
            $saldo_inicial = str_replace(',', '.', $request->input('saldo_inicial'));

            $nuevoAnio = Anio::create([
                'anio' => Carbon::now()->format('Y'),
                'saldo_inicial' => $saldo_inicial,
            ]);
        }else {
            $saldo_inicial = str_replace(',', '.', $request->input('saldo_inicial'));

            $anio->saldo_inicial = $saldo_inicial;
            $anio->save();
        }

        Alert::success('Éxito', 'Saldo inicial actualizado correctamente.');
        return redirect()->route('configuracion.contabilidad.index');
    }

    // Actualizar Prompt
    public function actualizarPrompt(Request  $request) {
        $prompt = PromptAsistente::first();
        if ($prompt != null) {
            $prompt->prompt = $request->prompt;
            $prompt->save();
            Alert::success('Éxito', 'Prompt actualizado correctamente.');
            return redirect()->route('configuracion.prompt-ia.index');

        }else {
            $promprNew = PromptAsistente::create([
                'prompt' => $request->prompt
            ]);
            Alert::success('Éxito', 'Prompt creado correctamente.');
            return redirect()->route('configuracion.prompt-ia.index');
        }
    }

    // Añadir personas de notificaciones
    public function addEmailNotificaciones(Request $request) {
        // dd($request);
        $crearPersona = EmailNotificaciones::create([
            'email' => $request->email,
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Email guardado correctamente',
            'redirect_url' => route('configuracion.notificaciones.index')
        ]);
    }

    // Borrar Persona de Notificaciones
    public function deleteEmailNotificaciones($id) {
        try {
            $persona = EmailNotificaciones::findOrFail($id);
            $persona->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Email eliminado correctamente',
                'redirect_url' => route('configuracion.notificaciones.index')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar el email: ' . $e->getMessage()
            ], 500);
        }
    }

    // Actualizar persona de notificaciones
    public function updateEmailNotificaciones($id, Request $request) {
        $persona = EmailNotificaciones::find($id);
        if (isset($request->telefono)) {
            //dd($request->telefono);
            $telefonoLimpio = $this->preformatPhone($request->telefono);
            $persona->update([
                'telefono' => $telefonoLimpio
            ]);

        } else {
            $persona->update($request->all());
        }
        //$persona ->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Email actualizado correctamente',
            'redirect_url' => route('configuracion.notificaciones.index')
        ]);
    }



    // Preformatear el numero de telefono
    public function preformatPhone($phone)
    {
        // Remove any non-digit characters from the phone number
        $phone = preg_replace('/\D+/', '', $phone);
        return $phone;
    }

    /**
     * Actualizar configuración de la plataforma del estado
     */
    public function updateEstado(Request $request)
    {
        try {
            $request->validate([
                'codigo_arrendador' => 'nullable|string|max:255',
                'aplicacion' => 'nullable|string|max:255',
                'credenciales' => 'nullable|string',
                'ca_path' => 'nullable|string|max:500'
            ]);

            // Actualizar cada setting
            Setting::set('codigo_arrendador', $request->codigo_arrendador, 'Código del arrendador para la plataforma del estado');
            Setting::set('aplicacion', $request->aplicacion, 'Nombre de la aplicación para identificación en plataforma del estado');
            Setting::set('credenciales', $request->credenciales, 'Credenciales de acceso a la plataforma del estado (JSON)');
            Setting::set('ca_path', $request->ca_path, 'Ruta del certificado CA para conexión segura con plataforma del estado');

            Alert::success('Éxito', 'Configuración de la plataforma del estado actualizada correctamente.');
            
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo actualizar la configuración: ' . $e->getMessage());
        }

        return redirect()->route('configuracion.plataforma-estado.index');
    }
    
    /**
     * Actualizar configuración MIR
     */
    public function updateMIR(Request $request)
    {
        try {
            $request->validate([
                'mir_arrendador' => 'nullable|string|max:255',
                'mir_codigo_arrendador' => 'nullable|string|max:255',
                'mir_codigo_establecimiento' => 'nullable|string|max:255',
                'mir_aplicacion' => 'nullable|string|max:255',
                'mir_usuario' => 'nullable|string|max:255',
                'mir_password' => 'nullable|string|max:255',
                'mir_entorno' => 'nullable|in:sandbox,production',
            ]);

            // Actualizar cada setting
            Setting::set('mir_arrendador', $request->mir_arrendador, 'Código arrendador para API MIR'); // Mantener compatibilidad
            Setting::set('mir_codigo_arrendador', $request->mir_codigo_arrendador ?? $request->mir_arrendador, 'Código arrendador para API MIR (nuevo formato)');
            Setting::set('mir_codigo_establecimiento', $request->mir_codigo_establecimiento, 'Código de establecimiento para API MIR');
            Setting::set('mir_aplicacion', $request->mir_aplicacion, 'Nombre de la aplicación para MIR');
            Setting::set('mir_usuario', $request->mir_usuario, 'Usuario para autenticación MIR');
            Setting::set('mir_password', $request->mir_password, 'Contraseña para autenticación MIR');
            Setting::set('mir_entorno', $request->mir_entorno ?? 'sandbox', 'Entorno MIR (sandbox/production)');

            Alert::success('Éxito', 'Configuración MIR actualizada correctamente.');
            
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo actualizar la configuración MIR: ' . $e->getMessage());
        }

        return redirect()->route('configuracion.mir.index');
    }
    
    /**
     * Actualizar configuración del portal público (host/host)
     */
    public function updatePortalPublico(Request $request)
    {
        Log::info('updatePortalPublico called', ['request' => $request->all(), 'method' => $request->method()]);
        
        try {
            // Validar antes de procesar
            $validated = $request->validate([
                'host_nombre' => 'nullable|string|max:255',
                'host_iniciales' => 'nullable|string|max:4',
                'host_descripcion' => 'nullable|string|max:5000',
                'host_idiomas' => 'nullable|array',
                'host_rating' => 'nullable|numeric|min:0|max:10',
                'host_reviews_count' => 'nullable|integer|min:0',
                'host_alojamientos_count' => 'nullable|integer|min:0',
            ], [
                'host_nombre.max' => 'El nombre no puede exceder 255 caracteres.',
                'host_iniciales.max' => 'Las iniciales no pueden exceder 4 caracteres.',
                'host_descripcion.max' => 'La descripción no puede exceder 5000 caracteres.',
                'host_rating.numeric' => 'La puntuación debe ser un número.',
                'host_rating.min' => 'La puntuación debe ser mayor o igual a 0.',
                'host_rating.max' => 'La puntuación debe ser menor o igual a 10.',
                'host_reviews_count.integer' => 'El número de comentarios debe ser un número entero.',
                'host_reviews_count.min' => 'El número de comentarios debe ser mayor o igual a 0.',
                'host_alojamientos_count.integer' => 'El número de alojamientos debe ser un número entero.',
                'host_alojamientos_count.min' => 'El número de alojamientos debe ser mayor o igual a 0.',
            ]);

            // Guardar cada setting - guardar EXACTAMENTE lo que viene del request
            $hostNombre = trim($request->input('host_nombre', ''));
            $hostNombre = $hostNombre === '' ? 'Apartamentos Algeciras' : $hostNombre;
            $result1 = Setting::set('host_nombre', $hostNombre, 'Nombre de la empresa/host');
            Log::info('host_nombre guardado', ['valor' => $hostNombre, 'id' => $result1->id]);
            
            $hostIniciales = trim($request->input('host_iniciales', ''));
            $hostIniciales = $hostIniciales === '' ? 'HA' : $hostIniciales;
            $result2 = Setting::set('host_iniciales', $hostIniciales, 'Iniciales del logo');
            Log::info('host_iniciales guardado', ['valor' => $hostIniciales, 'id' => $result2->id]);
            
            $hostDescripcion = trim($request->input('host_descripcion', ''));
            $hostDescripcion = $hostDescripcion === '' ? 'Alojamientos de calidad en el corazón de Algeciras' : $hostDescripcion;
            $result3 = Setting::set('host_descripcion', $hostDescripcion, 'Descripción del host');
            Log::info('host_descripcion guardado', ['valor' => $hostDescripcion, 'id' => $result3->id, 'input_raw' => $request->input('host_descripcion')]);
            
            $hostIdiomas = $request->input('host_idiomas', []);
            Setting::set('host_idiomas', json_encode(!empty($hostIdiomas) ? $hostIdiomas : ['Español', 'Inglés']), 'Idiomas hablados (JSON array)');
            
            if ($request->has('host_rating')) {
                Setting::set('host_rating', $request->input('host_rating'), 'Puntuación promedio del host');
            }
            if ($request->has('host_reviews_count')) {
                Setting::set('host_reviews_count', $request->input('host_reviews_count'), 'Número total de comentarios');
            }
            if ($request->has('host_alojamientos_count')) {
                Setting::set('host_alojamientos_count', $request->input('host_alojamientos_count'), 'Número de alojamientos gestionados');
            }

            Log::info('Todos los settings guardados correctamente');
            
            // Verificar que se guardaron
            $verificacion = Setting::whereIn('key', ['host_nombre', 'host_descripcion'])->get();
            Log::info('Verificación después de guardar', ['settings' => $verificacion->pluck('value', 'key')->toArray()]);
            
            Alert::success('Éxito', 'Configuración del portal público actualizada correctamente.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación al actualizar portal público', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            // Redirigir con los errores de validación (sin Alert::error para evitar el modal)
            return redirect()->route('configuracion.portal-publico.index')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error al actualizar portal público', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            Alert::error('Error', 'No se pudo actualizar la configuración: ' . $e->getMessage());
        }

        return redirect()->route('configuracion.portal-publico.index');
    }
    
    /**
     * ============================================
     * MÉTODOS PARA VISTAS SEPARADAS (REFACTORIZADAS)
     * ============================================
     */
    
    /**
     * Portal Público - Vista separada
     */
    public function portalPublico()
    {
        return view('admin.configuraciones.portal-publico');
    }
    
    /**
     * Credenciales - Vista separada (consolidada con todas las secciones)
     */
    public function credenciales()
    {
        $configuraciones = Configuraciones::all();

        // WhatsApp
        $whatsapp = [
            'token' => Setting::get('whatsapp_token', ''),
            'phone_id' => Setting::get('whatsapp_phone_id', config('services.whatsapp.phone_id')),
            'api_version' => Setting::get('whatsapp_api_version', config('services.whatsapp.api_version')),
        ];

        // Channex
        $channex = [
            'api_token' => Setting::get('channex_api_token', config('services.channex.api_token', env('CHANNEX_API_TOKEN', ''))),
            'webhook_url' => Setting::get('channex_webhook_url', config('services.channex.webhook_url', '')),
        ];

        // IA providers
        $ia = [
            'hawkins' => [
                'url' => Setting::get('hawkins_ai_url', config('services.hawkins_ai.base_url')),
                'api_key' => Setting::get('hawkins_ai_api_key', config('services.hawkins_ai.api_key')),
                'model' => Setting::get('hawkins_ai_model', config('services.hawkins_ai.model')),
            ],
            'ollama' => [
                'url' => Setting::get('ollama_url', config('services.ollama.base_url')),
                'api_key' => Setting::get('ollama_api_key', config('services.ollama.api_key')),
                'model' => Setting::get('ollama_model', config('services.ollama.model')),
            ],
            'openai' => [
                'api_key' => Setting::get('openai_api_key', config('services.openai.api_key')),
                'model' => Setting::get('openai_model', config('services.openai.model')),
            ],
            'anthropic' => [
                'api_key' => Setting::get('anthropic_api_key', config('services.anthropic.api_key')),
            ],
        ];

        // MIR
        $mir = [
            'codigo_arrendador' => Setting::get('mir_codigo_arrendador', Setting::get('mir_arrendador', '0000004735')),
            'codigo_establecimiento' => Setting::get('mir_codigo_establecimiento', '0000003984'),
            'aplicacion' => Setting::get('mir_aplicacion', 'Hawkins Suite'),
            'usuario' => Setting::get('mir_usuario', ''),
            'password' => Setting::get('mir_password', ''),
            'entorno' => Setting::get('mir_entorno', 'sandbox'),
        ];

        // Pagos
        $pagos = [
            'stripe' => [
                'key' => Setting::get('stripe_key', config('services.stripe.key')),
                'secret' => Setting::get('stripe_secret', config('services.stripe.secret')),
                'webhook_secret' => Setting::get('stripe_webhook_secret', config('services.stripe.webhook_secret')),
            ],
        ];

        // Cerraduras
        $cerraduras = [
            'tuya' => [
                'url' => Setting::get('tuya_app_url', config('services.tuya_app.url')),
                'api_key' => Setting::get('tuya_app_api_key', config('services.tuya_app.api_key')),
            ],
        ];

        // Otros
        $otros = [
            'recaptcha' => [
                'site_key' => Setting::get('recaptcha_site_key', config('services.recaptcha.site_key')),
                'secret_key' => Setting::get('recaptcha_secret_key', config('services.recaptcha.secret_key')),
            ],
            'registro_visitantes_url' => Setting::get('registro_visitantes_url', config('services.checkin.url')),
        ];

        // Prompt IA
        $prompt = PromptAsistente::all();

        // Bankinter - credenciales en BD
        $bankinterCredenciales = \App\Models\BankinterCredential::with('bank')->orderBy('alias')->get();
        $bancosDisponibles = \App\Models\Bancos::orderBy('nombre')->get();

        return view('admin.configuraciones.credenciales', compact(
            'configuraciones', 'whatsapp', 'channex', 'ia', 'mir',
            'pagos', 'cerraduras', 'otros', 'prompt',
            'bankinterCredenciales', 'bancosDisponibles'
        ));
    }
    
    /**
     * Contabilidad - Vista separada
     */
    public function contabilidad()
    {
        $anio = app('anio');
        $anioActual = date('Y');
        $anios = [];
        for ($i = 0; $i <= 5; $i++) {
            $anios[] = strval($anioActual - $i);
        }
        $saldo = Anio::where('anio', $anio)->where('is_close', null)->first();
        $formasPago = FormasPago::all();
        
        return view('admin.configuraciones.contabilidad', compact('anio', 'anios', 'saldo', 'formasPago'));
    }
    
    /**
     * Reparaciones - Vista separada
     */
    public function reparaciones()
    {
        $reparaciones = Reparaciones::all();
        return view('admin.configuraciones.reparaciones', compact('reparaciones'));
    }
    
    /**
     * Limpiadoras - Vista separada
     */
    public function limpiadoras()
    {
        $limpiadorasUsers = User::where('inactive', null)->where('role', 'USER')->get();
        $limpiadorasGuardia = LimpiadoraGuardia::with('usuario')->get();
        return view('admin.configuraciones.limpiadoras', compact('limpiadorasUsers', 'limpiadorasGuardia'));
    }
    
    /**
     * Notificaciones - Vista separada
     */
    public function notificaciones()
    {
        $emailsNotificaciones = EmailNotificaciones::all();
        return view('admin.configuraciones.notificaciones', compact('emailsNotificaciones'));
    }
    
    /**
     * Prompt IA - Vista separada
     */
    public function promptIa()
    {
        $prompt = PromptAsistente::all();
        return view('admin.configuraciones.prompt-ia', compact('prompt'));
    }
    
    /**
     * Plataforma Estado - Vista separada
     */
    public function plataformaEstado()
    {
        return view('admin.configuraciones.plataforma-estado');
    }
    
    /**
     * MIR Hospedajes - Vista separada con dashboard de estado de envíos
     */
    public function mirHospedajes()
    {
        $reservas = \App\Models\Reserva::with(['cliente', 'apartamento.edificio'])
            ->where('fecha_entrada', '>=', now()->subDays(30))
            ->where('fecha_entrada', '<=', now()->addDays(7))
            ->orderBy('fecha_entrada', 'desc')
            ->get();

        // Calcular estado MIR de cada reserva
        $contadores = ['enviado' => 0, 'pendiente' => 0, 'error' => 0, 'sin_dni' => 0];

        foreach ($reservas as $reserva) {
            if ($reserva->mir_enviado) {
                $estado = 'enviado';
            } elseif ($reserva->mir_estado === 'error') {
                $estado = 'error';
            } elseif ($reserva->cliente && !empty($reserva->cliente->num_identificacion)) {
                $estado = 'pendiente';
            } else {
                $estado = 'sin_dni';
            }
            $reserva->mir_status_computed = $estado;
            $contadores[$estado]++;
        }

        return view('admin.configuraciones.mir-hospedajes', compact('reservas', 'contadores'));
    }
    
    /**
     * SEO y SEM - Vista separada
     */
    public function seo()
    {
        // Obtener todas las rutas públicas principales
        $routes = [
            'web.index' => 'Inicio',
            'web.apartamentos' => 'Apartamentos',
            'web.reservas.portal' => 'Portal de Reservas',
            'web.reservas.show' => 'Detalle de Apartamento',
            'web.sobre-nosotros' => 'Sobre Nosotros',
            'web.contacto' => 'Contacto',
            'web.servicios' => 'Servicios',
            'web.preguntas-frecuentes' => 'Preguntas Frecuentes',
            'web.politica-cancelaciones' => 'Política de Cancelaciones',
        ];
        
        // Obtener todos los meta tags existentes
        $seoMetas = SeoMeta::whereIn('route_name', array_keys($routes))
            ->get()
            ->keyBy('route_name');
        
        return view('admin.configuraciones.seo', compact('routes', 'seoMetas'));
    }
    
    /**
     * Guardar o actualizar meta tags SEO
     */
    public function updateSeo(Request $request)
    {
        $request->validate([
            'route_name' => 'required|string',
            'page_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|url|max:500',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|string|max:50',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:500',
            'twitter_image' => 'nullable|url|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:100',
            'hreflang_es' => 'nullable|url|max:500',
            'hreflang_en' => 'nullable|url|max:500',
            'hreflang_fr' => 'nullable|url|max:500',
            'hreflang_de' => 'nullable|url|max:500',
            'hreflang_it' => 'nullable|url|max:500',
            'hreflang_pt' => 'nullable|url|max:500',
            'structured_data' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);
        
        try {
            $data = $request->only([
                'page_title',
                'meta_description',
                'meta_keywords',
                'og_title',
                'og_description',
                'og_image',
                'og_type',
                'twitter_card',
            'twitter_title',
                'twitter_description',
                'twitter_image',
                'canonical_url',
                'robots',
                'hreflang_es',
                'hreflang_en',
                'hreflang_fr',
                'hreflang_de',
                'hreflang_it',
                'hreflang_pt',
                'active',
            ]);
            
            // Procesar structured_data (JSON string a array)
            if ($request->filled('structured_data')) {
                $structuredData = json_decode($request->structured_data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['structured_data'] = $structuredData;
                } else {
                    throw new \Exception('JSON-LD inválido: ' . json_last_error_msg());
                }
            }
            
            // Convertir active a boolean
            $data['active'] = $request->has('active') ? true : false;
            
            $seoMeta = SeoMeta::updateOrCreate(
                ['route_name' => $request->route_name],
                $data
            );
            
            Alert::success('Éxito', 'Meta tags SEO actualizados correctamente.');
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar SEO', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            Alert::error('Error', 'No se pudo actualizar los meta tags: ' . $e->getMessage());
        }

        return redirect()->route('configuracion.seo.index');
    }

    // =========================================================================
    // Credenciales Bankinter (gestion desde Configuracion > Credenciales)
    // =========================================================================

    /**
     * Crear una nueva credencial Bankinter.
     */
    public function bankinterStore(Request $request)
    {
        $data = $request->validate([
            'alias' => 'required|alpha_dash|max:64|unique:bankinter_credentials,alias',
            'label' => 'nullable|string|max:255',
            'user' => 'required|string|max:255',
            'password' => 'required|string|max:500',
            'iban' => 'nullable|string|max:34',
            'bank_id' => 'nullable|integer|exists:bank_accounts,id',
            'enabled' => 'nullable|boolean',
        ]);

        $data['enabled'] = $request->boolean('enabled', true);

        \App\Models\BankinterCredential::create($data);

        Log::info('[Bankinter] Credencial creada', [
            'alias' => $data['alias'],
            'user_id' => auth()->id(),
        ]);

        Alert::success('Exito', 'Credencial Bankinter creada correctamente.');
        return redirect()->to(route('configuracion.credenciales.index') . '#secBankinter');
    }

    /**
     * Actualizar credencial Bankinter. Si password llega vacia, no se cambia.
     */
    public function bankinterUpdate(Request $request, $id)
    {
        $credencial = \App\Models\BankinterCredential::findOrFail($id);

        $data = $request->validate([
            'alias' => 'required|alpha_dash|max:64|unique:bankinter_credentials,alias,' . $credencial->id,
            'label' => 'nullable|string|max:255',
            'user' => 'required|string|max:255',
            'password' => 'nullable|string|max:500',
            'iban' => 'nullable|string|max:34',
            'bank_id' => 'nullable|integer|exists:bank_accounts,id',
            'enabled' => 'nullable|boolean',
        ]);

        // Si no se envio password (o esta vacio), no sobrescribir la existente
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['enabled'] = $request->boolean('enabled', false);

        $credencial->update($data);

        Log::info('[Bankinter] Credencial actualizada', [
            'alias' => $credencial->alias,
            'id' => $credencial->id,
            'password_cambiada' => isset($data['password']),
            'user_id' => auth()->id(),
        ]);

        Alert::success('Exito', 'Credencial Bankinter actualizada correctamente.');
        return redirect()->to(route('configuracion.credenciales.index') . '#secBankinter');
    }

    /**
     * Eliminar credencial Bankinter.
     */
    public function bankinterDestroy($id)
    {
        $credencial = \App\Models\BankinterCredential::findOrFail($id);
        $alias = $credencial->alias;
        $credencial->delete();

        Log::info('[Bankinter] Credencial eliminada', [
            'alias' => $alias,
            'id' => $id,
            'user_id' => auth()->id(),
        ]);

        Alert::success('Exito', "Credencial '{$alias}' eliminada.");
        return redirect()->to(route('configuracion.credenciales.index') . '#secBankinter');
    }

    /**
     * Activar/desactivar credencial Bankinter.
     */
    public function bankinterToggle($id)
    {
        $credencial = \App\Models\BankinterCredential::findOrFail($id);
        $credencial->enabled = !$credencial->enabled;
        $credencial->save();

        Log::info('[Bankinter] Credencial toggled', [
            'alias' => $credencial->alias,
            'id' => $credencial->id,
            'enabled' => $credencial->enabled,
            'user_id' => auth()->id(),
        ]);

        $estado = $credencial->enabled ? 'activada' : 'desactivada';
        Alert::success('Exito', "Credencial '{$credencial->alias}' {$estado}.");
        return redirect()->to(route('configuracion.credenciales.index') . '#secBankinter');
    }
}
