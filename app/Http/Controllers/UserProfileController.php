<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Holidays;
use App\Models\HolidaysPetitions;
use App\Models\HolidaysStatus;

class UserProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the user profile page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $hoy = Carbon::today();
            
            // Obtener estadísticas del usuario
            $stats = $this->getUserStats($user);
            
            // Obtener vacaciones del usuario (días disponibles)
            $holidays = Holidays::where('admin_user_id', $user->id)->first();
            
            // Obtener solicitudes de vacaciones del usuario (sin relaciones por ahora)
            $vacations = DB::table('holidays_petition')
                ->where('admin_user_id', $user->id)
                ->orderBy('from', 'desc')
                ->get();
            
            // Obtener estadísticas de vacaciones
            $vacationStats = $this->getVacationStats($user, $holidays, $vacations);
            
            return view('user.profile', compact('user', 'stats', 'holidays', 'vacations', 'vacationStats'));
            
        } catch (\Exception $e) {
            // Log del error para debugging
            Log::error('Error en UserProfileController: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Retornar vista con datos mínimos en caso de error
            $user = Auth::user();
            $stats = [
                'limpiezas_mes' => 0,
                'limpiezas_completadas_mes' => 0,
                'incidencias_mes' => 0,
                'incidencias_resueltas_mes' => 0,
                'horas_trabajadas_mes' => 0,
                'dias_trabajados_mes' => 0,
            ];
            $holidays = null;
            $vacations = collect();
            $vacationStats = [
                'dias_totales' => 22,
                'dias_usados' => 0,
                'dias_restantes' => 22,
                'dias_solicitados' => 0,
                'dias_aprobados' => 0,
                'dias_pendientes' => 0,
                'proximo_periodo' => null,
            ];
            
            return view('user.profile', compact('user', 'stats', 'holidays', 'vacations', 'vacationStats'));
        }
    }

    /**
     * Update user profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'birth_date' => 'nullable|date',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $user->update($request->only([
            'name', 'email', 'phone', 'address', 'birth_date', 
            'emergency_contact', 'emergency_phone'
        ]));

        return back()->with('success', 'Perfil actualizado correctamente');
    }

    /**
     * Update user password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Contraseña actualizada correctamente');
    }

    /**
     * Update user avatar.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAvatar(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior si existe
            if ($user->avatar && file_exists(public_path('storage/' . $user->avatar))) {
                unlink(public_path('storage/' . $user->avatar));
            }

            // Guardar nuevo avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $avatarPath]);
        }

        return back()->with('success', 'Avatar actualizado correctamente');
    }

    /**
     * Update user vacations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateVacations(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'quantity' => 'required|numeric|min:0|max:365',
        ]);

        // Actualizar o crear registro de vacaciones
        $holidays = Holidays::updateOrCreate(
            ['admin_user_id' => $user->id],
            [
                'quantity' => $request->quantity,
                'first_period' => 1, // Por defecto
            ]
        );

        return back()->with('success', 'Vacaciones actualizadas correctamente');
    }

    /**
     * Test method for debugging statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testStats()
    {
        $user = Auth::user();
        $stats = $this->getUserStats($user);
        
        return response()->json([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'stats' => $stats
        ]);
    }

    /**
     * Get user statistics.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    private function getUserStats($user)
    {
        $hoy = Carbon::today();
        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();
        
        $stats = [
            'limpiezas_mes' => 0,
            'limpiezas_completadas_mes' => 0,
            'incidencias_total' => 0,
            'incidencias_resueltas_total' => 0,
            'incidencias_mes' => 0,
            'incidencias_resueltas_mes' => 0,
            'horas_trabajadas_mes' => 0,
            'dias_trabajados_mes' => 0,
        ];

        try {
            // Estadísticas de limpiezas
            if ($user->role === 'LIMPIEZA') {
                $limpiezasMes = DB::table('apartamento_limpieza')
                    ->where('empleada_id', $user->id)
                    ->whereBetween('fecha_comienzo', [$inicioMes, $finMes])
                    ->get();

                $stats['limpiezas_mes'] = $limpiezasMes->count();
                $stats['limpiezas_completadas_mes'] = $limpiezasMes->where('status_id', 2)->count();

                // Horas trabajadas este mes
                $fichajesMes = DB::table('fichajes')
                    ->where('user_id', $user->id)
                    ->whereBetween('hora_entrada', [$inicioMes, $finMes])
                    ->whereNotNull('hora_salida')
                    ->get();

                foreach ($fichajesMes as $fichaje) {
                    $entrada = Carbon::parse($fichaje->hora_entrada);
                    $salida = Carbon::parse($fichaje->hora_salida);
                    $stats['horas_trabajadas_mes'] += $entrada->diffInHours($salida);
                }

                $stats['dias_trabajados_mes'] = $fichajesMes->count();
            }

            // Estadísticas de incidencias (TOTALES)
            $incidenciasTotal = DB::table('incidencias')
                ->where('empleada_id', $user->id)
                ->get();

            $stats['incidencias_total'] = $incidenciasTotal->count();
            $stats['incidencias_resueltas_total'] = $incidenciasTotal->where('estado', 'resuelta')->count();

            // Debug logging
            Log::info('UserProfileController - Usuario ID: ' . $user->id);
            Log::info('UserProfileController - Incidencias totales: ' . $stats['incidencias_total']);
            Log::info('UserProfileController - Incidencias resueltas: ' . $stats['incidencias_resueltas_total']);

            // Estadísticas de incidencias del MES
            $incidenciasMes = DB::table('incidencias')
                ->where('empleada_id', $user->id)
                ->whereBetween('created_at', [$inicioMes, $finMes])
                ->get();

            $stats['incidencias_mes'] = $incidenciasMes->count();
            $stats['incidencias_resueltas_mes'] = $incidenciasMes->where('estado', 'resuelta')->count();

        } catch (\Exception $e) {
            // Si hay error, mantener valores por defecto
        }

        return $stats;
    }

    /**
     * Get vacation statistics.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Holidays  $holidays
     * @param  \Illuminate\Support\Collection  $vacations
     * @return array
     */
    private function getVacationStats($user, $holidays, $vacations)
    {
        $hoy = Carbon::today();
        $anioActual = $hoy->year;
        
        $stats = [
            'dias_totales' => $holidays ? $holidays->quantity : 0,
            'dias_usados' => 0,
            'dias_restantes' => $holidays ? $holidays->quantity : 0,
            'dias_solicitados' => 0,
            'dias_aprobados' => 0,
            'dias_pendientes' => 0,
            'proximo_periodo' => null,
        ];

        try {
            // Obtener estados de vacaciones
            $statuses = DB::table('holidays_status')->get()->keyBy('id');
            
            // Calcular días usados y estadísticas por estado
            foreach ($vacations as $vacation) {
                $dias = $vacation->total_days;
                $stats['dias_solicitados'] += $dias;
                
                // Obtener el nombre del estado
                $statusName = $statuses->get($vacation->holidays_status_id)->name ?? 'Pendiente';
                
                // Mapear estados de la base de datos
                if ($statusName === 'Aceptadas') {
                    $stats['dias_aprobados'] += $dias;
                    $stats['dias_usados'] += $dias;
                } elseif ($statusName === 'Pendientes') {
                    $stats['dias_pendientes'] += $dias;
                }
            }
            
            // Calcular días restantes
            $stats['dias_restantes'] = $stats['dias_totales'] - $stats['dias_usados'];

            // Próximo período de vacaciones (solicitudes aprobadas futuras)
            $nextVacation = $vacations
                ->where('holidays_status_id', $statuses->where('name', 'Aceptadas')->first()->id ?? null)
                ->where('from', '>=', $hoy->toDateString())
                ->sortBy('from')
                ->first();

            if ($nextVacation) {
                $stats['proximo_periodo'] = [
                    'fecha_inicio' => Carbon::parse($nextVacation->from)->format('d/m/Y'),
                    'fecha_fin' => Carbon::parse($nextVacation->to)->format('d/m/Y'),
                    'dias' => $nextVacation->total_days,
                ];
            }

        } catch (\Exception $e) {
            // Si hay error, mantener valores por defecto
        }

        return $stats;
    }
}
