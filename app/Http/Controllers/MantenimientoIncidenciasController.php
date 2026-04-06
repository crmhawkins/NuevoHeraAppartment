<?php

namespace App\Http\Controllers;

use App\Models\Incidencia;
use App\Models\Apartamento;
use App\Models\ZonaComun;
use App\Models\ApartamentoLimpieza;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\AlertService;
use App\Services\NotificationService;

class MantenimientoIncidenciasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:MANTENIMIENTO');
    }

    /**
     * Listado de todas las incidencias (mismo layout que el dashboard).
     */
    public function index(Request $request)
    {
        $query = Incidencia::with(['apartamento', 'zonaComun'])
            ->orderByRaw("CASE WHEN prioridad = 'urgente' THEN 1 WHEN prioridad = 'alta' THEN 2 WHEN prioridad = 'media' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $incidencias = $query->paginate(15);

        $estadisticas = [
            'pendientes' => Incidencia::where('estado', 'pendiente')->count(),
            'en_proceso' => Incidencia::where('estado', 'en_proceso')->count(),
            'resueltas' => Incidencia::where('estado', 'resuelta')->count(),
        ];

        return view('mantenimiento.incidencias.index', compact('incidencias', 'estadisticas'));
    }

    /**
     * Mostrar formulario para crear nueva incidencia
     */
    public function create()
    {
        $user = Auth::user();
        
        // Obtener apartamentos y zonas comunes disponibles
        $apartamentos = Apartamento::orderBy('nombre')->get();
        $zonasComunes = ZonaComun::activas()->ordenadas()->get();
        
        // Obtener limpiezas en proceso (puede que el usuario de mantenimiento no tenga limpiezas asignadas)
        $limpiezasEnProceso = ApartamentoLimpieza::whereIn('status_id', [1, 2]) // En proceso o iniciada
            ->with(['apartamento', 'zonaComun'])
            ->get();

        return view('mantenimiento.incidencias.create', compact('apartamentos', 'zonasComunes', 'limpiezasEnProceso'));
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
            $user = Auth::user();
            
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
                'empleada_id' => $user->id,
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
                $user->name
            );

            // Crear notificación de nueva incidencia
            NotificationService::notifyNewIncident($incidencia);

            Log::info('Incidencia creada desde mantenimiento', [
                'incidencia_id' => $incidencia->id,
                'user_id' => $user->id,
                'titulo' => $request->titulo
            ]);

            return redirect()->route('mantenimiento.incidencias.index')
                ->with('status', 'Incidencia creada correctamente. Los administradores han sido notificados.');

        } catch (\Exception $e) {
            Log::error('Error al crear incidencia desde mantenimiento', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al crear la incidencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Detalle de una incidencia.
     */
    public function show(Incidencia $incidencia)
    {
        $incidencia->load(['apartamento', 'zonaComun', 'empleada', 'limpieza']);

        return view('mantenimiento.incidencias.show', compact('incidencia'));
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

            Log::info('Fotos añadidas a incidencia desde mantenimiento', [
                'incidencia_id' => $incidencia->id,
                'user_id' => Auth::id(),
                'fotos_count' => count($fotos)
            ]);

            return redirect()->route('mantenimiento.incidencias.show', $incidencia)
                ->with('status', 'Fotos añadidas correctamente');

        } catch (\Exception $e) {
            Log::error('Error al añadir fotos a incidencia desde mantenimiento', [
                'incidencia_id' => $incidencia->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al añadir las fotos: ' . $e->getMessage());
        }
    }

    /**
     * Marcar incidencia como resuelta (desde mantenimiento).
     */
    public function resolver(Request $request, Incidencia $incidencia)
    {
        $validator = Validator::make($request->all(), [
            'solucion' => 'required|string|max:2000',
        ], [
            'solucion.required' => 'Indica qué se ha hecho para resolver la incidencia.',
            'solucion.max' => 'La solución no puede superar 2000 caracteres.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $user = Auth::user();

            $incidencia->update([
                'estado' => 'resuelta',
                'solucion' => $request->solucion,
                'admin_resuelve_id' => $user->id,
                'fecha_resolucion' => now(),
            ]);

            return redirect()->route('mantenimiento.incidencias.show', $incidencia)
                ->with('status', 'Incidencia marcada como resuelta correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al resolver: ' . $e->getMessage());
        }
    }
}
