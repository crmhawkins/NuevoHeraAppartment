<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TurnoTrabajo;
use App\Models\TareaAsignada;
use App\Models\TipoTarea;
use App\Models\Apartamento;
use App\Models\ZonaComun;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TurnosAdminController extends Controller
{
    public function index(Request $request)
    {
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));

        // Get all shifts for this date with tasks
        $turnos = TurnoTrabajo::with(['user', 'tareasAsignadas.tipoTarea', 'tareasAsignadas.apartamento', 'tareasAsignadas.zonaComun'])
            ->whereDate('fecha', $fecha)
            ->get();

        // Available task types for drag & drop
        $tiposTarea = TipoTarea::where('activo', 1)->orderBy('prioridad_base', 'desc')->get();

        // Available apartments and zones
        $apartamentos = Apartamento::whereNull('deleted_at')->orderBy('edificio_id')->get();
        $zonasComunes = ZonaComun::whereNull('deleted_at')->get();

        // Available cleaners
        $limpiadoras = User::where('role', 'LIMPIEZA')->get();

        return view('admin.turnos-panel.index', compact('turnos', 'tiposTarea', 'apartamentos', 'zonasComunes', 'limpiadoras', 'fecha'));
    }

    // Add task to a shift (AJAX)
    public function agregarTarea(Request $request)
    {
        $request->validate([
            'turno_id' => 'required|exists:turnos_trabajo,id',
            'tipo_tarea_id' => 'required|exists:tipos_tareas,id',
            'apartamento_id' => 'nullable|exists:apartamentos,id',
            'zona_comun_id' => 'nullable',
        ]);

        $turno = TurnoTrabajo::findOrFail($request->turno_id);
        $tipoTarea = TipoTarea::findOrFail($request->tipo_tarea_id);

        // Get next order
        $maxOrden = TareaAsignada::where('turno_id', $turno->id)->max('orden_ejecucion') ?? 0;

        $tarea = TareaAsignada::create([
            'turno_id' => $turno->id,
            'tipo_tarea_id' => $tipoTarea->id,
            'apartamento_id' => $request->apartamento_id,
            'zona_comun_id' => $request->zona_comun_id,
            'prioridad_calculada' => $tipoTarea->prioridad_base,
            'orden_ejecucion' => $maxOrden + 1,
            'estado' => 'pendiente',
        ]);

        return response()->json(['success' => true, 'tarea' => $tarea->load(['tipoTarea', 'apartamento', 'zonaComun'])]);
    }

    // Remove task from shift (AJAX)
    public function quitarTarea(Request $request, $tareaId)
    {
        $tarea = TareaAsignada::findOrFail($tareaId);

        if ($tarea->estado !== 'pendiente') {
            return response()->json(['success' => false, 'message' => 'Solo se pueden quitar tareas pendientes'], 422);
        }

        $tarea->delete();
        return response()->json(['success' => true]);
    }

    // Move task between shifts (AJAX - drag & drop)
    public function moverTarea(Request $request)
    {
        $request->validate([
            'tarea_id' => 'required|exists:tareas_asignadas,id',
            'turno_destino_id' => 'required|exists:turnos_trabajo,id',
            'orden' => 'nullable|integer',
        ]);

        $tarea = TareaAsignada::findOrFail($request->tarea_id);

        if ($tarea->estado !== 'pendiente') {
            return response()->json(['success' => false, 'message' => 'Solo se pueden mover tareas pendientes'], 422);
        }

        $tarea->update([
            'turno_id' => $request->turno_destino_id,
            'orden_ejecucion' => $request->orden ?? 99,
        ]);

        return response()->json(['success' => true, 'tarea' => $tarea->load(['tipoTarea', 'apartamento', 'zonaComun'])]);
    }

    // Regenerate shifts for a date
    public function regenerar(Request $request)
    {
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));

        \Artisan::call('turnos:generar', ['fecha' => $fecha, '--force' => true]);

        return response()->json(['success' => true, 'output' => \Artisan::output()]);
    }
}
