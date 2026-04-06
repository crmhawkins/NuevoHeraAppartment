<?php

namespace App\Http\Controllers;

use App\Models\ApartamentoLimpieza;
use App\Models\Fichaje;
use App\Models\Photo;
use Illuminate\Http\Request;
use App\Models\User;

class JornadaController extends Controller
{
    public function index(Request $request) {
        $anio = app('anio'); // Obtiene el año global usando el Service Provider
    
        // Obtener todos los usuarios activos (empleados de limpieza)
        $users = User::whereIn('role', ['USER', 'LIMPIEZA'])
                     ->where(function($query) {
                         $query->where('inactive', '=', 0)
                               ->orWhereNull('inactive');
                     })->get();
    
        // Usar la fecha y mes del request o, si no se proporcionan, usar valores por defecto
        $fecha_inicio = $request->input('fecha_inicio');
        $mes = $request->input('mes');
    
        foreach ($users as $user) {
            // Iniciar la consulta para fichajes del usuario
            $query = Fichaje::where('user_id', $user->id);
    
            if (!empty($fecha_inicio)) {
                // Filtrar fichajes por fecha específica dentro del año actual
                $query->whereDate('created_at', '=', $fecha_inicio);
            } elseif (!empty($mes)) {
                // Filtrar fichajes por mes dentro del año actual
                $query->whereMonth('created_at', '=', $mes);
            } else {
                // Si no se proporciona fecha ni mes, se usan los valores actuales del día/mes
                $query->whereDate('created_at', '=', date('Y-m-d'));
            }
            
            // Asegurar que solo se consideren fichajes dentro del año actual
            $query->whereYear('created_at', '=', $anio);
            
            // Ejecutar la consulta y asignar resultados
            $user->jornada = $query->get();
    
            // Añadir los datos de ApartamentoLimpieza y TurnoTrabajo con tareas asignadas a cada jornada
            foreach ($user->jornada as $jornada) {
                $jornada->limpiezas = ApartamentoLimpieza::where('user_id', $user->id)
                                                         ->whereDate('created_at', '=', $jornada->created_at)
                                                         ->get();

                // Buscar TurnoTrabajo para esta fecha y usuario
                $fechaJornada = \Carbon\Carbon::parse($jornada->created_at)->format('Y-m-d');
                $turnoTrabajo = \App\Models\TurnoTrabajo::where('user_id', $user->id)
                                                         ->whereDate('fecha', $fechaJornada)
                                                         ->with(['tareasAsignadas' => function($query) {
                                                             $query->with(['tipoTarea', 'apartamento', 'zonaComun'])
                                                                   ->orderBy('orden_ejecucion');
                                                         }])
                                                         ->first();
                
                $jornada->turnoTrabajo = $turnoTrabajo;
            }

            
        }
    
        // Devolver la vista con los usuarios y sus fichajes
        return view('admin.jornada.index', compact('users', 'request'));
    }
    
    
}
