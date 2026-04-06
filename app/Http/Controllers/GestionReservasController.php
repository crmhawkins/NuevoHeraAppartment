<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Apartamento;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GestionReservasController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar vista principal de reservas
     */
    public function index()
    {
        $hoy = Carbon::today();
        
        // Obtener reservas de hoy (entradas y salidas)
        $reservasHoy = Reserva::with(['apartamento.edificio', 'cliente', 'estado'])
            ->where('estado_id', '!=', 4)
            ->where(function($query) use ($hoy) {
                $query->whereDate('fecha_entrada', $hoy)
                      ->orWhereDate('fecha_salida', $hoy);
            })
            ->orderBy('fecha_entrada')
            ->get();
        
        // Separar por tipo real
        $reservasEntradaHoy = $reservasHoy->where('fecha_entrada', $hoy->toDateString());
        $reservasSalidaHoy = $reservasHoy->where('fecha_salida', $hoy->toDateString());
        
        // Reservas ocupadas (que están actualmente en el apartamento)
        $reservasOcupadas = Reserva::with(['apartamento.edificio', 'cliente', 'estado'])
            ->where('estado_id', '!=', 4)
            ->where('fecha_entrada', '<=', $hoy)
            ->where('fecha_salida', '>', $hoy)
            ->orderBy('fecha_entrada')
            ->get();
        
        // Estadísticas
        $totalReservas = $reservasHoy->count();
        $totalEntradas = $reservasEntradaHoy->count();
        $totalSalidas = $reservasSalidaHoy->count();
        $totalOcupadas = $reservasOcupadas->count();

        return view('gestion.reservas.index', compact(
            'reservasHoy',
            'reservasEntradaHoy',
            'reservasSalidaHoy', 
            'reservasOcupadas',
            'totalReservas',
            'totalEntradas',
            'totalSalidas',
            'totalOcupadas',
            'hoy'
        ));
    }

    /**
     * Mostrar información detallada de una reserva
     */
    public function show($id)
    {
        $reserva = Reserva::with([
            'apartamento.edificio',
            'cliente',
            'estado'
        ])->findOrFail($id);

        // Calcular estadísticas
        $noches = $reserva->fecha_entrada && $reserva->fecha_salida 
            ? Carbon::parse($reserva->fecha_entrada)->diffInDays($reserva->fecha_salida)
            : 0;

        // Obtener amenities automáticos para niños si los hay
        $amenitiesNinos = collect();
        if ($reserva->numero_ninos > 0) {
            $amenitiesNinos = \App\Models\Amenity::paraNinos()->activos()->get();
        }

        return view('gestion.reservas.show', compact(
            'reserva',
            'noches',
            'amenitiesNinos'
        ));
    }

    /**
     * Buscar reservas con filtros avanzados
     */
    public function buscar(Request $request)
    {
        Log::info('=== MÉTODO BUSCAR INICIADO ===');
        Log::info('Request recibido:', ['method' => $request->method(), 'url' => $request->url()]);
        
        try {
            Log::info('Iniciando búsqueda de reservas');
            Log::info('Parámetros recibidos:', $request->all());
            
            $fecha = $request->get('fecha');
            $apartamentoId = $request->get('apartamento_id');
            $terminoBusqueda = $request->get('termino_busqueda');
            $tipoFiltro = $request->get('tipo_filtro', 'fecha'); // fecha, apartamento, busqueda
            
            Log::info('Parámetros procesados:', [
                'fecha' => $fecha,
                'apartamento_id' => $apartamentoId,
                'termino_busqueda' => $terminoBusqueda,
                'tipo_filtro' => $tipoFiltro
            ]);
            
            // Consulta con búsqueda real simplificada
            Log::info('=== ANTES DE LA CONSULTA ===');
            try {
                $query = Reserva::where('estado_id', '!=', 4);
                
                // Aplicar filtros de búsqueda
                if ($terminoBusqueda) {
                    Log::info('=== APLICANDO FILTRO TÉRMINO ===', ['termino' => $terminoBusqueda]);
                    
                    // Buscar por ID de reserva o código de reserva
                    if (is_numeric($terminoBusqueda)) {
                        // Si es un número largo (más de 6 dígitos), buscar por código de reserva
                        if (strlen($terminoBusqueda) > 6) {
                            Log::info('=== BÚSQUEDA POR CÓDIGO DE RESERVA ===', ['codigo' => $terminoBusqueda]);
                            $query->where('codigo_reserva', $terminoBusqueda);
                            
                            // Si es búsqueda por código, NO aplicar filtros de fecha
                            Log::info('=== OMITIENDO FILTROS DE FECHA PARA BÚSQUEDA POR CÓDIGO ===');
                        } else {
                            // Si es un número corto, buscar por ID
                            Log::info('=== BÚSQUEDA POR ID NUMÉRICO ===', ['id' => $terminoBusqueda]);
                            $query->where('id', $terminoBusqueda);
                            
                            // Si es búsqueda por ID, NO aplicar filtros de fecha
                            Log::info('=== OMITIENDO FILTROS DE FECHA PARA BÚSQUEDA POR ID ===');
                        }
                    } else {
                        // Buscar por cliente o apartamento usando joins
                        Log::info('=== BÚSQUEDA POR TEXTO ===', ['termino' => $terminoBusqueda]);
                        $query->join('clientes', 'reservas.cliente_id', '=', 'clientes.id')
                              ->join('apartamentos', 'reservas.apartamento_id', '=', 'apartamentos.id')
                              ->where(function($q) use ($terminoBusqueda) {
                                  $q->where('clientes.nombre', 'LIKE', "%{$terminoBusqueda}%")
                                    ->orWhere('clientes.apellidos', 'LIKE', "%{$terminoBusqueda}%")
                                    ->orWhere('clientes.email', 'LIKE', "%{$terminoBusqueda}%")
                                    ->orWhere('apartamentos.nombre', 'LIKE', "%{$terminoBusqueda}%");
                              })
                              ->select('reservas.*');
                    }
                }
                
                // Solo aplicar filtros de fecha si NO es búsqueda por ID o código
                if ($fecha && !($terminoBusqueda && is_numeric($terminoBusqueda))) {
                    Log::info('=== APLICANDO FILTRO FECHA ===', ['fecha' => $fecha]);
                    $fechaCarbon = Carbon::parse($fecha);
                    $query->where(function($q) use ($fechaCarbon) {
                        $q->where('fecha_entrada', $fechaCarbon->toDateString())
                          ->orWhere('fecha_salida', $fechaCarbon->toDateString())
                          ->orWhere(function($subQ) use ($fechaCarbon) {
                              $subQ->where('fecha_entrada', '<=', $fechaCarbon)
                                   ->where('fecha_salida', '>', $fechaCarbon);
                          });
                    });
                }
                
                if ($apartamentoId) {
                    Log::info('=== APLICANDO FILTRO APARTAMENTO ===', ['apartamento_id' => $apartamentoId]);
                    $query->where('apartamento_id', $apartamentoId);
                }
                
                // Limitar resultados para evitar problemas de memoria
                $reservas = $query->limit(100)->get();
                Log::info('=== CONSULTA EXITOSA ===', ['count' => $reservas->count()]);
                
            } catch (\Exception $e) {
                Log::error('=== ERROR EN CONSULTA ===', ['message' => $e->getMessage()]);
                throw $e;
            }
            
            // Separar por tipo real
            Log::info('=== PROCESANDO FILTROS POR FECHA ===');
            $reservasEntrada = $reservas->where('fecha_entrada', $fecha ? Carbon::parse($fecha)->toDateString() : null);
            Log::info('=== FILTRO ENTRADA COMPLETADO ===', ['count' => $reservasEntrada->count()]);
            
            $reservasSalida = $reservas->where('fecha_salida', $fecha ? Carbon::parse($fecha)->toDateString() : null);
            Log::info('=== FILTRO SALIDA COMPLETADO ===', ['count' => $reservasSalida->count()]);
            
            $reservasOcupadas = $reservas->filter(function($reserva) use ($fecha) {
                if (!$fecha) return false;
                $fechaCarbon = Carbon::parse($fecha);
                return $reserva->fecha_entrada <= $fechaCarbon && $reserva->fecha_salida > $fechaCarbon;
            });
            Log::info('=== FILTRO OCUPADAS COMPLETADO ===', ['count' => $reservasOcupadas->count()]);
            
            // Estadísticas
            Log::info('=== PROCESANDO ESTADÍSTICAS ===');
            $totalReservas = $reservas->count();
            $totalEntradas = $reservasEntrada->count();
            $totalSalidas = $reservasSalida->count();
            $totalOcupadas = $reservasOcupadas->count();
            Log::info('=== ESTADÍSTICAS COMPLETADAS ===', [
                'total' => $totalReservas,
                'entradas' => $totalEntradas,
                'salidas' => $totalSalidas,
                'ocupadas' => $totalOcupadas
            ]);
            
            // Si es una petición AJAX, devolver JSON
            if ($request->ajax()) {
                Log::info('=== RESPUESTA AJAX ===');
                try {
                                    $responseData = [
                    'reservas' => $reservas->toArray(),
                    'reservasEntradaHoy' => $reservasEntrada->toArray(),
                    'reservasSalidaHoy' => $reservasSalida->toArray(),
                    'reservasOcupadas' => $reservasOcupadas->toArray(),
                    'totalReservas' => $totalReservas,
                    'totalEntradas' => $totalEntradas,
                    'totalSalidas' => $totalSalidas,
                    'totalOcupadas' => $totalOcupadas,
                    'fecha' => $fecha ? Carbon::parse($fecha)->format('d/m/Y') : null,
                    'apartamento_id' => $apartamentoId,
                    'termino_busqueda' => $terminoBusqueda,
                    'mensaje' => 'Búsqueda completada exitosamente'
                ];
                    Log::info('=== DATOS AJAX PREPARADOS ===', ['keys' => array_keys($responseData)]);
                    
                    Log::info('=== RETORNANDO JSON ===');
                    Log::info('=== JSON ENCODE ===');
                    
                    try {
                        $jsonString = json_encode($responseData);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Log::error('=== ERROR JSON ENCODE ===', ['error' => json_last_error_msg()]);
                            throw new \Exception('Error encoding JSON: ' . json_last_error_msg());
                        }
                        
                        Log::info('=== JSON ENCODE EXITOSO ===', ['length' => strlen($jsonString)]);
                        Log::info('=== RETORNANDO RESPONSE ===');
                        
                        try {
                            Log::info('=== CREANDO RESPONSE ===');
                            
                            $response = new \Illuminate\Http\Response($jsonString, 200, [
                                'Content-Type' => 'application/json',
                                'Content-Length' => strlen($jsonString)
                            ]);
                            
                            Log::info('=== RESPONSE CREADO ===');
                            return $response;
                            
                        } catch (\Exception $e) {
                            Log::error('=== ERROR EN RESPONSE ===', ['message' => $e->getMessage()]);
                            throw $e;
                        }
                        
                    } catch (\Exception $e) {
                        Log::error('=== ERROR EN JSON ENCODE ===', ['message' => $e->getMessage()]);
                        throw $e;
                    }
                    
                } catch (\Exception $e) {
                    Log::error('=== ERROR EN RESPUESTA AJAX ===', ['message' => $e->getMessage()]);
                    throw $e;
                }
            }
            
            // Si no es AJAX, devolver vista con los resultados
            Log::info('=== ANTES DE RETORNAR VISTA ===');
            Log::info('=== PREPARANDO VARIABLES PARA COMPACT ===');
            
            try {
                $variables = compact(
                    'reservas',
                    'reservasEntrada',
                    'reservasSalida', 
                    'reservasOcupadas',
                    'totalReservas',
                    'totalEntradas',
                    'totalSalidas',
                    'totalOcupadas',
                    'fecha',
                    'apartamentoId',
                    'terminoBusqueda',
                    'tipoFiltro'
                );
                Log::info('=== COMPACT EXITOSO ===', ['variables' => array_keys($variables)]);
                
                Log::info('=== RETORNANDO VISTA ===');
                return view('gestion.reservas.index', $variables);
                
            } catch (\Exception $e) {
                Log::error('=== ERROR EN COMPACT O VISTA ===', ['message' => $e->getMessage()]);
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de reservas: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            if ($request->ajax()) {
                return response()->json(['error' => 'Error en la búsqueda: ' . $e->getMessage()], 500);
            }
            
            return back()->with('error', 'Error en la búsqueda: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener apartamentos para el selector
     */
    public function obtenerApartamentos()
    {
        try {
            Log::info('Iniciando obtención de apartamentos');
            
            // Verificar si el usuario está autenticado
            if (!Auth::check()) {
                Log::error('Usuario no autenticado');
                return response()->json(['error' => 'No autenticado'], 401);
            }
            
            Log::info('Usuario autenticado: ' . Auth::id());
            
            // Obtener apartamentos con relación edificio usando alias
            $apartamentos = \App\Models\Apartamento::with('edificioRel')
                ->orderBy('nombre')
                ->get();
                
            Log::info('Apartamentos encontrados: ' . $apartamentos->count());
            
            $apartamentosConEdificio = $apartamentos->map(function($apartamento) {
                $edificioNombre = 'N/A';
                try {
                    if ($apartamento->edificioRel && $apartamento->edificioRel instanceof \App\Models\Edificio) {
                        $edificioNombre = $apartamento->edificioRel->nombre;
                        Log::info('Edificio encontrado: ' . $edificioNombre);
                    } else {
                        Log::info('Edificio no encontrado o no es instancia válida para apartamento ' . $apartamento->id);
                    }
                } catch (\Exception $e) {
                    Log::error('Error obteniendo edificio para apartamento ' . $apartamento->id . ': ' . $e->getMessage());
                    $edificioNombre = 'N/A';
                }
                
                return [
                    'id' => $apartamento->id,
                    'nombre' => $apartamento->nombre,
                    'edificio' => $edificioNombre
                ];
            });
            
            Log::info('Apartamentos procesados correctamente');
            return response()->json($apartamentosConEdificio);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo apartamentos: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error obteniendo apartamentos: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener estadísticas de reservas
     */
    public function estadisticas()
    {
        $hoy = Carbon::today();
        $semana = Carbon::today()->addWeek();
        $mes = Carbon::today()->addMonth();

        $estadisticas = [
            'hoy' => [
                'entradas' => Reserva::whereDate('fecha_entrada', $hoy)->where('estado_id', '!=', 4)->count(),
                'salidas' => Reserva::whereDate('fecha_salida', $hoy)->where('estado_id', '!=', 4)->count(),
                'ocupadas' => Reserva::where('fecha_entrada', '<', $hoy)
                                   ->where('fecha_salida', '>', $hoy)
                                   ->where('estado_id', '!=', 4)->count()
            ],
            'semana' => [
                'entradas' => Reserva::whereBetween('fecha_entrada', [$hoy, $semana])->where('estado_id', '!=', 4)->count(),
                'salidas' => Reserva::whereBetween('fecha_salida', [$hoy, $semana])->where('estado_id', '!=', 4)->count()
            ],
            'mes' => [
                'entradas' => Reserva::whereBetween('fecha_entrada', [$hoy, $mes])->where('estado_id', '!=', 4)->count(),
                'salidas' => Reserva::whereBetween('fecha_salida', [$hoy, $mes])->where('estado_id', '!=', 4)->count()
            ]
        ];

        return response()->json($estadisticas);
    }
}
