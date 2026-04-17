<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Incidencia;
use App\Models\Apartamento;
use App\Models\ZonaComun;
use App\Models\ApartamentoLimpieza;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\AlertService;
use App\Services\NotificationService;

class GestionIncidenciasController extends Controller
{
    /**
     * Constructor del controlador
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar el listado de incidencias de la empleada
     */
    public function index()
    {
        try {
            $empleada = Auth::user();
            
            // Debug: Log del usuario autenticado
            Log::info('Usuario autenticado:', ['id' => $empleada->id, 'name' => $empleada->name]);
            
            // Prueba simple sin relaciones complejas
            $incidencias = Incidencia::where('empleada_id', $empleada->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
            return view('gestion.incidencias.index', compact('incidencias'));

        } catch (\Exception $e) {
            Log::error('Error en GestionIncidenciasController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Retornar una vista de error simple
            return response()->view('errors.simple', [
                'message' => 'Error al cargar incidencias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar formulario para crear nueva incidencia
     */
    public function create()
    {
        $empleada = Auth::user();
        
        // Obtener apartamentos y zonas comunes disponibles
        $apartamentos = Apartamento::orderBy('nombre')->get();
        $zonasComunes = ZonaComun::activas()->ordenadas()->get();
        
        // Obtener limpiezas en proceso de la empleada
        $limpiezasEnProceso = ApartamentoLimpieza::where('empleada_id', $empleada->id)
            ->whereIn('status_id', [1, 2]) // En proceso o iniciada
            ->with(['apartamento', 'zonaComun'])
            ->get();

        return view('gestion.incidencias.create', compact('apartamentos', 'zonasComunes', 'limpiezasEnProceso'));
    }

    /**
     * Guardar nueva incidencia
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'tipo' => 'required|in:apartamento,zona_comun',
            'apartamento_id' => 'nullable|exists:apartamentos,id',
            'zona_comun_id' => 'nullable|exists:zona_comuns,id',
            'apartamento_limpieza_id' => 'nullable|exists:apartamento_limpieza,id',
            'prioridad' => 'required|in:baja,media,alta,urgente',
            'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'titulo.required' => 'El título es obligatorio',
            'descripcion.required' => 'La descripción es obligatoria',
            'tipo.required' => 'Debe seleccionar el tipo de elemento',
            'prioridad.required' => 'Debe seleccionar la prioridad',
            'fotos.*.image' => 'Los archivos deben ser imágenes',
            'fotos.*.max' => 'Las imágenes no pueden superar 2MB'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $empleada = Auth::user();
            
            // Procesar fotos si se subieron
            $fotos = [];
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $path = $foto->store('incidencias', 'public');
                    $fotos[] = $path;
                }
            }

            // Crear la incidencia
            $incidencia = Incidencia::create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'tipo' => $request->tipo,
                'apartamento_id' => $request->apartamento_id,
                'zona_comun_id' => $request->zona_comun_id,
                'apartamento_limpieza_id' => $request->apartamento_limpieza_id,
                'empleada_id' => $empleada->id,
                'prioridad' => $request->prioridad,
                'estado' => 'pendiente',
                'fotos' => !empty($fotos) ? $fotos : null
            ]);

            // Crear alerta para los administradores
            $elementoNombre = '';
            if ($request->tipo === 'apartamento' && $request->apartamento_id) {
                $apartamento = Apartamento::find($request->apartamento_id);
                $elementoNombre = $apartamento ? $apartamento->nombre : 'Apartamento';
            } elseif ($request->tipo === 'zona_comun' && $request->zona_comun_id) {
                $zonaComun = ZonaComun::find($request->zona_comun_id);
                $elementoNombre = $zonaComun ? $zonaComun->nombre : 'Zona Común';
            }

            // Crear la alerta usando AlertService
            AlertService::createIncidentAlert(
                $incidencia->id,
                $request->titulo,
                $request->tipo === 'apartamento' ? 'Apartamento' : 'Zona Común',
                $elementoNombre,
                $request->prioridad,
                $empleada->name
            );

            // [2026-04-17] Notificar automaticamente a mantenimiento por WhatsApp.
            // Antes solo se disparaba WhatsApp desde el flujo de limpieza (items
            // con avería) o desde el boton manual de admin. Aqui lo extendemos
            // al store generico del CRM para que cualquier incidencia creada por
            // una empleada llegue al tecnico sin accion manual adicional.
            // Envuelto en try/catch: si falla el envio (red, whatsapp api), la
            // incidencia se crea igual; el error queda en el log.
            try {
                \App\Services\TecnicoNotificationService::notifyTechniciansAboutIncident($incidencia);
                \Illuminate\Support\Facades\Log::info('Tecnicos notificados automaticamente sobre la incidencia', [
                    'incidencia_id' => $incidencia->id,
                    'origen' => 'gestion.incidencias.store',
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error notificando tecnicos automaticamente: ' . $e->getMessage(), [
                    'incidencia_id' => $incidencia->id,
                ]);
            }

            // Alertar tambien al equipo de gestion por WhatsApp (informativo)
            try {
                \App\Services\AlertaEquipoService::alertar(
                    'INCIDENCIA - ' . strtoupper($request->prioridad ?? 'media'),
                    "Reportada por: " . $empleada->name . "\n"
                    . ($request->tipo === 'apartamento' ? 'Apartamento: ' : 'Zona comun: ') . $elementoNombre . "\n"
                    . "Titulo: " . $request->titulo . "\n"
                    . "Descripcion: " . $request->descripcion,
                    'incidencia_generica'
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error WhatsApp alerta equipo incidencia: ' . $e->getMessage(), [
                    'incidencia_id' => $incidencia->id,
                ]);
            }

            // Log the creation
            $this->logCreate('INCIDENCIA', $incidencia->id, $incidencia->toArray());

            // Crear notificación de nueva incidencia
            NotificationService::notifyNewIncident($incidencia);

            return redirect()->route('gestion.incidencias.index')
                ->with('success', 'Incidencia creada correctamente. Los administradores han sido notificados.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear la incidencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar detalles de una incidencia
     */
    public function show(Incidencia $incidencia)
    {
        // Verificar que la empleada solo pueda ver sus propias incidencias
        if ($incidencia->empleada_id !== Auth::id()) {
            abort(403, 'No tienes permiso para ver esta incidencia');
        }

        $incidencia->load(['apartamento', 'zonaComun', 'limpieza', 'empleada']);

        return view('gestion.incidencias.show', compact('incidencia'));
    }

    /**
     * Mostrar formulario para editar incidencia
     */
    public function edit(Incidencia $incidencia)
    {
        // Verificar que la empleada solo pueda editar sus propias incidencias
        if ($incidencia->empleada_id !== Auth::id()) {
            abort(403, 'No tienes permiso para editar esta incidencia');
        }

        // Solo permitir editar si está pendiente
        if ($incidencia->estado !== 'pendiente') {
            return redirect()->route('gestion.incidencias.show', $incidencia)
                ->with('error', 'Solo se pueden editar incidencias pendientes');
        }

        $empleada = Auth::user();
        $apartamentos = Apartamento::orderBy('nombre')->get();
        $zonasComunes = ZonaComun::activas()->ordenadas()->get();
        $limpiezasEnProceso = ApartamentoLimpieza::where('empleada_id', $empleada->id)
            ->whereIn('status_id', [1, 2])
            ->with(['apartamento', 'zonaComun'])
            ->get();

        return view('gestion.incidencias.edit', compact('incidencia', 'apartamentos', 'zonasComunes', 'limpiezasEnProceso'));
    }

    /**
     * Actualizar incidencia
     */
    public function update(Request $request, Incidencia $incidencia)
    {
        // Verificar permisos
        if ($incidencia->empleada_id !== Auth::id()) {
            abort(403, 'No tienes permiso para editar esta incidencia');
        }

        if ($incidencia->estado !== 'pendiente') {
            return redirect()->route('gestion.incidencias.show', $incidencia)
                ->with('error', 'Solo se pueden editar incidencias pendientes');
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'tipo' => 'required|in:apartamento,zona_comun',
            'apartamento_id' => 'nullable|exists:apartamentos,id',
            'zona_comun_id' => 'nullable|exists:zona_comuns,id',
            'apartamento_limpieza_id' => 'nullable|exists:apartamento_limpieza,id',
            'prioridad' => 'required|in:baja,media,alta,urgente',
            'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Procesar nuevas fotos si se subieron
            $fotos = $incidencia->fotos ?? [];
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $path = $foto->store('incidencias', 'public');
                    $fotos[] = $path;
                }
            }

            // Actualizar la incidencia
            $incidencia->update([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'tipo' => $request->tipo,
                'apartamento_id' => $request->apartamento_id,
                'zona_comun_id' => $request->zona_comun_id,
                'apartamento_limpieza_id' => $request->apartamento_limpieza_id,
                'prioridad' => $request->prioridad,
                'fotos' => $fotos
            ]);

            return redirect()->route('gestion.incidencias.show', $incidencia)
                ->with('success', 'Incidencia actualizada correctamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la incidencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Añadir fotos a una incidencia existente (permitido en cualquier estado)
     * Cualquier usuario autenticado puede añadir fotos
     */
    public function addPhotos(Request $request, Incidencia $incidencia)
    {
        // Cualquier usuario autenticado puede añadir fotos a cualquier incidencia

        $validator = Validator::make($request->all(), [
            'fotos.*' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'fotos.*.required' => 'Debes seleccionar al menos una foto',
            'fotos.*.image' => 'Los archivos deben ser imágenes',
            'fotos.*.max' => 'Las imágenes no pueden superar 2MB'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with('error', 'Error al validar las fotos');
        }

        try {
            // Procesar nuevas fotos
            $fotos = $incidencia->fotos ?? [];
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $path = $foto->store('incidencias', 'public');
                    $fotos[] = $path;
                }
            }

            // Actualizar solo las fotos
            $incidencia->update([
                'fotos' => $fotos
            ]);

            Log::info('Fotos añadidas a incidencia', [
                'incidencia_id' => $incidencia->id,
                'empleada_id' => Auth::id(),
                'fotos_count' => count($fotos)
            ]);

            return redirect()->route('gestion.incidencias.show', $incidencia)
                ->with('success', 'Fotos añadidas correctamente');

        } catch (\Exception $e) {
            Log::error('Error al añadir fotos a incidencia', [
                'incidencia_id' => $incidencia->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al añadir las fotos: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar incidencia (solo si está pendiente)
     */
    public function destroy(Incidencia $incidencia)
    {
        // Verificar permisos
        if ($incidencia->empleada_id !== Auth::id()) {
            abort(403, 'No tienes permiso para eliminar esta incidencia');
        }

        if ($incidencia->estado !== 'pendiente') {
            return redirect()->route('gestion.incidencias.index')
                ->with('error', 'Solo se pueden eliminar incidencias pendientes');
        }

        try {
            // Eliminar fotos del storage
            if ($incidencia->fotos) {
                foreach ($incidencia->fotos as $foto) {
                    Storage::disk('public')->delete($foto);
                }
            }

            $incidencia->delete();

            return redirect()->route('gestion.incidencias.index')
                ->with('success', 'Incidencia eliminada correctamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar la incidencia: ' . $e->getMessage());
        }
    }
}
