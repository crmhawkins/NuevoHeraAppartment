<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminIncidenciasController extends Controller
{
    /**
     * Mostrar listado de todas las incidencias
     */
    public function index(Request $request)
    {
        $query = Incidencia::with(['apartamento', 'zonaComun', 'empleada', 'adminResuelve', 'limpieza']);

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('empleada')) {
            $query->where('empleada_id', $request->empleada);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        if ($request->filled('hoy') && $request->hoy === 'si') {
            $query->whereDate('created_at', today());
        }

        // Ordenar por prioridad y fecha
        $incidencias = $query->orderByRaw("
            CASE 
                WHEN prioridad = 'urgente' THEN 1
                WHEN prioridad = 'alta' THEN 2
                WHEN prioridad = 'media' THEN 3
                WHEN prioridad = 'baja' THEN 4
            END
        ")->orderBy('created_at', 'desc')->paginate(20);

        // Estadísticas
        $estadisticas = [
            'total' => Incidencia::count(),
            'pendientes' => Incidencia::where('estado', 'pendiente')->count(),
            'urgentes' => Incidencia::where('prioridad', 'urgente')->where('estado', '!=', 'resuelta')->count(),
            'hoy' => Incidencia::whereDate('created_at', today())->count(),
            'resueltas_hoy' => Incidencia::whereDate('fecha_resolucion', today())->count()
        ];

        // Filtros para la vista
        $filtros = [
            'estado' => $request->estado,
            'prioridad' => $request->prioridad,
            'tipo' => $request->tipo,
            'empleada' => $request->empleada,
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'hoy' => $request->hoy
        ];

        // Empleadas para el filtro
        $empleadas = User::where('role', 'USER')
            ->where('inactive', false)
            ->orderBy('name')
            ->get();

        return view('admin.incidencias.index', compact('incidencias', 'estadisticas', 'filtros', 'empleadas'));
    }

    /**
     * Mostrar detalles de una incidencia
     */
    public function show(Incidencia $incidencia)
    {
        $incidencia->load(['apartamento', 'zonaComun', 'empleada', 'adminResuelve', 'limpieza', 'tecnicoAsignado']);

        return view('admin.incidencias.show', compact('incidencia'));
    }

    /**
     * Mostrar formulario para editar incidencia
     */
    public function edit(Incidencia $incidencia)
    {
        $incidencia->load(['apartamento', 'zonaComun', 'empleada', 'adminResuelve', 'limpieza']);

        return view('admin.incidencias.edit', compact('incidencia'));
    }

    /**
     * Actualizar incidencia (resolver, cambiar estado, etc.)
     */
    public function update(Request $request, Incidencia $incidencia)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:pendiente,en_proceso,resuelta,cerrada',
            'solucion' => 'nullable|string|max:1000',
            'observaciones_admin' => 'nullable|string|max:1000'
        ], [
            'estado.required' => 'El estado es obligatorio',
            'solucion.max' => 'La solución no puede superar 1000 caracteres',
            'observaciones_admin.max' => 'Las observaciones no pueden superar 1000 caracteres'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $admin = Auth::user();
            
            $data = [
                'estado' => $request->estado,
                'observaciones_admin' => $request->observaciones_admin
            ];

            // Si se marca como resuelta, agregar admin y fecha
            if ($request->estado === 'resuelta') {
                $data['admin_resuelve_id'] = $admin->id;
                $data['fecha_resolucion'] = now();
                $data['solucion'] = $request->solucion;
            }

            // Si se cambia de resuelta a otro estado, limpiar campos
            if ($incidencia->estado === 'resuelta' && $request->estado !== 'resuelta') {
                $data['admin_resuelve_id'] = null;
                $data['fecha_resolucion'] = null;
                $data['solucion'] = null;
            }

            $incidencia->update($data);

            $mensaje = 'Incidencia actualizada correctamente';
            if ($request->estado === 'resuelta') {
                $mensaje .= ' y marcada como resuelta';
            }

            return redirect()->route('admin.incidencias.show', $incidencia)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la incidencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Marcar incidencia como resuelta
     */
    public function resolver(Request $request, Incidencia $incidencia)
    {
        $validator = Validator::make($request->all(), [
            'solucion' => 'required|string|max:1000'
        ], [
            'solucion.required' => 'La solución es obligatoria al resolver una incidencia',
            'solucion.max' => 'La solución no puede superar 1000 caracteres'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $admin = Auth::user();
            
            $incidencia->update([
                'estado' => 'resuelta',
                'solucion' => $request->solucion,
                'admin_resuelve_id' => $admin->id,
                'fecha_resolucion' => now()
            ]);

            return redirect()->route('admin.incidencias.show', $incidencia)
                ->with('success', 'Incidencia marcada como resuelta correctamente');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al resolver la incidencia: ' . $e->getMessage());
        }
    }

    /**
     * Cambiar prioridad de una incidencia
     */
    public function cambiarPrioridad(Request $request, Incidencia $incidencia)
    {
        $validator = Validator::make($request->all(), [
            'prioridad' => 'required|in:baja,media,alta,urgente'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Prioridad inválida'], 400);
        }

        try {
            $incidencia->update(['prioridad' => $request->prioridad]);

            return response()->json([
                'success' => true,
                'message' => 'Prioridad actualizada correctamente',
                'prioridad' => $request->prioridad
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar la prioridad'], 500);
        }
    }

    /**
     * Obtener incidencias pendientes para el dashboard
     */
    public function getPendientes()
    {
        $incidencias = Incidencia::where('estado', 'pendiente')
            ->with(['apartamento', 'zonaComun', 'empleada'])
            ->orderByRaw("
                CASE 
                    WHEN prioridad = 'urgente' THEN 1
                    WHEN prioridad = 'alta' THEN 2
                    WHEN prioridad = 'media' THEN 3
                    WHEN prioridad = 'baja' THEN 4
                END
            ")->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        return response()->json($incidencias);
    }

    /**
     * Notificar técnicos sobre una incidencia (manual desde admin)
     */
    public function notificarTecnicos(Incidencia $incidencia)
    {
        try {
            // Verificar que la incidencia existe
            $incidencia->load(['apartamento', 'empleada', 'tecnicoAsignado']);

            // Notificar a técnicos
            $resultado = \App\Services\TecnicoNotificationService::notifyTechniciansAboutIncident($incidencia);

            if ($resultado['success']) {
                $mensaje = $resultado['message'];
                if (!empty($resultado['errores'])) {
                    $mensaje .= '. Algunos técnicos no pudieron ser notificados.';
                }

                return redirect()->route('admin.incidencias.show', $incidencia)
                    ->with('success', $mensaje);
            } else {
                return redirect()->route('admin.incidencias.show', $incidencia)
                    ->with('error', $resultado['message']);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error notificando técnicos manualmente: ' . $e->getMessage());
            
            return redirect()->route('admin.incidencias.show', $incidencia)
                ->with('error', 'Error al notificar técnicos: ' . $e->getMessage());
        }
    }
}
