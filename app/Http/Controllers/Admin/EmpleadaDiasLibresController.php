<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmpleadaHorario;
use App\Models\EmpleadaDiasLibres;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmpleadaDiasLibresController extends Controller
{
    /**
     * Mostrar el calendario de días libres para una empleada
     */
    public function index(Request $request, EmpleadaHorario $empleadaHorario)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth());
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth()->addMonths(2));
        
        // Obtener todas las semanas en el rango
        $semanas = $this->obtenerSemanasEnRango($fechaInicio, $fechaFin);
        
        // Obtener días libres configurados para cada semana
        $fechasSemanas = $semanas->map(function($semana) {
            return $semana['lunes']->format('Y-m-d');
        });
        
        $diasLibresConfigurados = EmpleadaDiasLibres::where('empleada_horario_id', $empleadaHorario->id)
            ->whereIn('semana_inicio', $fechasSemanas)
            ->get()
            ->keyBy(function($item) {
                return $item->semana_inicio->format('Y-m-d');
            });
        
        return view('admin.empleada-dias-libres.index', compact(
            'empleadaHorario', 
            'semanas', 
            'diasLibresConfigurados',
            'fechaInicio',
            'fechaFin'
        ));
    }
    
    /**
     * Mostrar formulario para configurar días libres de una semana específica
     */
    public function create(Request $request, EmpleadaHorario $empleadaHorario)
    {
        $semanaInicio = Carbon::parse($request->get('semana', now()->startOfWeek()));
        
        // Obtener días libres existentes para esta semana
        $diasLibres = EmpleadaDiasLibres::where('empleada_horario_id', $empleadaHorario->id)
            ->where('semana_inicio', $semanaInicio)
            ->first();
        
        return view('admin.empleada-dias-libres.create', compact(
            'empleadaHorario', 
            'semanaInicio',
            'diasLibres'
        ));
    }
    
    /**
     * Guardar configuración de días libres para una semana
     */
    public function store(Request $request, EmpleadaHorario $empleadaHorario)
    {
        $request->validate([
            'semana_inicio' => 'required|date',
            'dias_libres' => 'nullable|array',
            'dias_libres.*' => 'integer|min:0|max:6',
            'observaciones' => 'nullable|string|max:500'
        ]);
        
        try {
            $semanaInicio = Carbon::parse($request->semana_inicio)->startOfWeek();
            $diasLibres = $request->dias_libres ?: [];
            
            $diasLibresSemana = EmpleadaDiasLibres::updateOrCreate(
                [
                    'empleada_horario_id' => $empleadaHorario->id,
                    'semana_inicio' => $semanaInicio
                ],
                [
                    'dias_libres' => $diasLibres,
                    'observaciones' => $request->observaciones
                ]
            );
            
            return redirect()->route('admin.empleada-dias-libres.index', $empleadaHorario)
                ->with('success', 'Días libres configurados exitosamente para la semana del ' . $semanaInicio->format('d/m/Y'));
                
        } catch (\Exception $e) {
            Log::error('Error configurando días libres: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al configurar los días libres: ' . $e->getMessage());
        }
    }
    
    /**
     * Eliminar configuración de días libres para una semana
     */
    public function destroy(EmpleadaHorario $empleadaHorario, $semanaInicio)
    {
        try {
            $semanaInicio = Carbon::parse($semanaInicio)->startOfWeek();
            
            EmpleadaDiasLibres::where('empleada_horario_id', $empleadaHorario->id)
                ->where('semana_inicio', $semanaInicio)
                ->delete();
            
            return redirect()->route('admin.empleada-dias-libres.index', $empleadaHorario)
                ->with('success', 'Días libres eliminados para la semana del ' . $semanaInicio->format('d/m/Y'));
                
        } catch (\Exception $e) {
            Log::error('Error eliminando días libres: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al eliminar los días libres: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener semanas en un rango de fechas
     */
    private function obtenerSemanasEnRango($fechaInicio, $fechaFin)
    {
        $semanas = collect();
        $fechaActual = Carbon::parse($fechaInicio)->startOfWeek();
        $fechaFin = Carbon::parse($fechaFin)->startOfWeek();
        
        while ($fechaActual->lte($fechaFin)) {
            $semanas->push([
                'lunes' => $fechaActual->copy(),
                'domingo' => $fechaActual->copy()->endOfWeek(),
                'numero_semana' => $fechaActual->weekOfYear,
                'mes' => $fechaActual->format('F Y'),
                'rango' => $fechaActual->format('d/m') . ' - ' . $fechaActual->copy()->endOfWeek()->format('d/m/Y')
            ]);
            
            $fechaActual->addWeek();
        }
        
        return $semanas;
    }
}