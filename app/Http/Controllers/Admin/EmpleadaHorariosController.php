<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmpleadaHorario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmpleadaHorariosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $activo = $request->get('activo');
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'asc');

        $query = EmpleadaHorario::with('user');

        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($activo !== null) {
            $query->where('activo', $activo);
        }

        // Ordenar por la columna correcta
        if ($sort === 'name') {
            $query->join('users', 'empleada_horarios.user_id', '=', 'users.id')
                  ->orderBy('users.name', $order)
                  ->select('empleada_horarios.*');
        } else {
            $query->orderBy($sort, $order);
        }

        $horarios = $query->paginate(20);

        return view('admin.empleada-horarios.index', compact(
            'horarios', 
            'search', 
            'activo', 
            'sort', 
            'order'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $empleadas = User::where('role', 'LIMPIEZA')
            ->where('inactive', null)
            ->whereDoesntHave('empleadaHorario')
            ->get();

        return view('admin.empleada-horarios.create', compact('empleadas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:empleada_horarios,user_id',
            'horas_contratadas_dia' => 'required|integer|min:1|max:12',
            'dias_libres_semana' => 'required|integer|min:0|max:7',
            'hora_inicio_atencion' => 'required|date_format:H:i',
            'hora_fin_atencion' => 'required|date_format:H:i|after:hora_inicio_atencion',
            'lunes' => 'boolean',
            'martes' => 'boolean',
            'miercoles' => 'boolean',
            'jueves' => 'boolean',
            'viernes' => 'boolean',
            'sabado' => 'boolean',
            'domingo' => 'boolean',
            'observaciones' => 'nullable|string|max:1000'
        ], [
            'user_id.required' => 'Debe seleccionar una empleada',
            'user_id.unique' => 'Esta empleada ya tiene un horario configurado',
            'horas_contratadas_dia.required' => 'Las horas contratadas por día son obligatorias',
            'dias_libres_semana.required' => 'Los días libres por semana son obligatorios',
            'hora_inicio_atencion.required' => 'La hora de inicio de atención es obligatoria',
            'hora_fin_atencion.required' => 'La hora de fin de atención es obligatoria',
            'hora_fin_atencion.after' => 'La hora de fin debe ser posterior a la hora de inicio'
        ]);

        try {
            EmpleadaHorario::create([
                'user_id' => $request->user_id,
                'horas_contratadas_dia' => $request->horas_contratadas_dia,
                'dias_libres_semana' => $request->dias_libres_semana,
                'hora_inicio_atencion' => $request->hora_inicio_atencion,
                'hora_fin_atencion' => $request->hora_fin_atencion,
                'lunes' => $request->boolean('lunes'),
                'martes' => $request->boolean('martes'),
                'miercoles' => $request->boolean('miercoles'),
                'jueves' => $request->boolean('jueves'),
                'viernes' => $request->boolean('viernes'),
                'sabado' => $request->boolean('sabado'),
                'domingo' => $request->boolean('domingo'),
                'activo' => true,
                'observaciones' => $request->observaciones
            ]);

            return redirect()->route('admin.empleada-horarios.index')
                ->with('success', 'Horario de empleada creado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error creando horario de empleada: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el horario: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(EmpleadaHorario $empleadaHorario)
    {
        $empleadaHorario->load(['user', 'turnos' => function($query) {
            $query->orderBy('fecha', 'desc')->limit(10);
        }]);

        return view('admin.empleada-horarios.show', compact('empleadaHorario'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmpleadaHorario $empleadaHorario)
    {
        $empleadaHorario->load('user');
        
        return view('admin.empleada-horarios.edit', compact('empleadaHorario'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmpleadaHorario $empleadaHorario)
    {
        $request->validate([
            'horas_contratadas_dia' => 'required|integer|min:1|max:12',
            'hora_inicio_atencion' => 'required|date_format:H:i',
            'hora_fin_atencion' => 'required|date_format:H:i|after:hora_inicio_atencion',
            'lunes' => 'boolean',
            'martes' => 'boolean',
            'miercoles' => 'boolean',
            'jueves' => 'boolean',
            'viernes' => 'boolean',
            'sabado' => 'boolean',
            'domingo' => 'boolean',
            'activo' => 'required|in:0,1',
            'observaciones' => 'nullable|string|max:1000',
            'dias_libres_semana_actual' => 'nullable|array',
            'dias_libres_semana_actual.*' => 'integer|min:0|max:6'
        ]);

        try {
            // Actualizar el horario básico
            // NO incluir dias_libres_semana porque se calcula automáticamente con el mutator
            $empleadaHorario->update([
                'horas_contratadas_dia' => $request->horas_contratadas_dia,
                'hora_inicio_atencion' => $request->hora_inicio_atencion,
                'hora_fin_atencion' => $request->hora_fin_atencion,
                'lunes' => $request->boolean('lunes'),
                'martes' => $request->boolean('martes'),
                'miercoles' => $request->boolean('miercoles'),
                'jueves' => $request->boolean('jueves'),
                'viernes' => $request->boolean('viernes'),
                'sabado' => $request->boolean('sabado'),
                'domingo' => $request->boolean('domingo'),
                'activo' => $request->has('activo') && $request->activo == '1',
                'observaciones' => $request->observaciones
            ]);

            // Actualizar días libres para la semana actual
            if ($request->has('dias_libres_semana_actual')) {
                $semanaInicio = now()->startOfWeek();
                $diasLibres = $request->dias_libres_semana_actual ?: [];
                
                // Buscar o crear el registro de días libres para esta semana
                $diasLibresSemana = \App\Models\EmpleadaDiasLibres::updateOrCreate(
                    [
                        'empleada_horario_id' => $empleadaHorario->id,
                        'semana_inicio' => $semanaInicio
                    ],
                    [
                        'dias_libres' => $diasLibres,
                        'observaciones' => 'Configurado desde el panel de administración'
                    ]
                );
            }

            return redirect()->route('admin.empleada-horarios.index')
                ->with('success', 'Horario de empleada actualizado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error actualizando horario de empleada: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el horario: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmpleadaHorario $empleadaHorario)
    {
        try {
            $empleadaHorario->delete();

            return redirect()->route('admin.empleada-horarios.index')
                ->with('success', 'Horario de empleada eliminado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error eliminando horario de empleada: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al eliminar el horario: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(EmpleadaHorario $empleadaHorario)
    {
        try {
            $empleadaHorario->update(['activo' => !$empleadaHorario->activo]);
            
            $status = $empleadaHorario->activo ? 'activado' : 'desactivado';
            
            return response()->json([
                'success' => true,
                'message' => "Horario {$status} exitosamente",
                'activo' => $empleadaHorario->activo
            ]);

        } catch (\Exception $e) {
            Log::error('Error cambiando estado del horario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener empleadas sin horario configurado
     */
    public function empleadasSinHorario()
    {
        $empleadas = User::where('role', 'LIMPIEZA')
            ->where('inactive', null)
            ->whereDoesntHave('empleadaHorario')
            ->get();

        return response()->json($empleadas);
    }

    /**
     * Crear horario rápido para empleada
     */
    public function crearHorarioRapido(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:empleada_horarios,user_id',
            'horas_contratadas_dia' => 'required|integer|min:1|max:12'
        ]);

        try {
            EmpleadaHorario::create([
                'user_id' => $request->user_id,
                'horas_contratadas_dia' => $request->horas_contratadas_dia,
                'dias_libres_semana' => 2,
                'hora_inicio_atencion' => '08:00:00',
                'hora_fin_atencion' => '17:00:00',
                'lunes' => true,
                'martes' => true,
                'miercoles' => true,
                'jueves' => true,
                'viernes' => true,
                'sabado' => false,
                'domingo' => false,
                'activo' => true,
                'observaciones' => 'Horario creado automáticamente - configurar según necesidades'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horario creado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando horario rápido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el horario: ' . $e->getMessage()
            ], 500);
        }
    }
}