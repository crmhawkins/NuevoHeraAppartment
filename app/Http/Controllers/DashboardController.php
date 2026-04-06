<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Gastos;
use App\Models\Ingresos;
use App\Models\Cliente;
use App\Models\Apartamento;
use App\Models\CategoriaGastos;
use App\Models\Estado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request) {
        // **Obtenemos la fecha actual**
        $now = Carbon::now();

        // **Fechas predeterminadas si no se seleccionan**
        $fechaInicio = Carbon::parse($request->input('fecha_inicio', $now->startOfMonth()->toDateString()))->startOfDay();
        $fechaFin = Carbon::parse($request->input('fecha_fin', $now->endOfMonth()->toDateString()))->endOfDay();

        // **Crear clave de caché única para este rango de fechas**
        $cacheKey = 'dashboard_' . $fechaInicio->format('Y-m-d') . '_' . $fechaFin->format('Y-m-d');
        
        // **Intentar obtener datos del caché primero (válido por 5 minutos)**
        $cachedData = Cache::remember($cacheKey, 300, function () use ($fechaInicio, $fechaFin) {
            return $this->calculateDashboardData($fechaInicio, $fechaFin);
        });

        // **Si hay datos en caché, usarlos**
        if ($cachedData) {
            return view('admin.dashboard', $cachedData);
        }

        // **Si no hay caché, calcular los datos**
        $data = $this->calculateDashboardData($fechaInicio, $fechaFin);
        
        // **Guardar en caché**
        Cache::put($cacheKey, $data, 300);

        return view('admin.dashboard', $data);
    }

    private function calculateDashboardData($fechaInicio, $fechaFin) {
        // **Optimización: Una sola consulta para obtener todas las reservas con relaciones**
        // Para facturación/ingresos, filtrar solo por fecha_salida (el ingreso se contabiliza cuando el cliente sale)
        $reservas = Reserva::with(['cliente:id,nacionalidad,sexo,fecha_nacimiento', 'apartamento:id,titulo', 'estado:id'])
            ->where('estado_id', '!=', 4)
            ->whereBetween('fecha_salida', [$fechaInicio, $fechaFin])
            ->get();

        // **Optimización: Obtener apartamentos una sola vez**
        $apartamentosDisponibles = Apartamento::whereNotNull('id_channex')->count();
        $apartamentos = Apartamento::whereNotNull('id_channex')->get(['id', 'titulo']);

        // **Optimización: Calcular ocupación de forma más eficiente**
        $ocupacionData = $this->calculateOcupacionOptimizada($reservas, $apartamentos, $fechaInicio, $fechaFin);

        // **Optimización: Usar consultas agregadas para estadísticas**
        $estadisticas = $this->calculateEstadisticasOptimizadas($reservas, $fechaInicio, $fechaFin);

        // **Optimización: Calcular gráficos de forma más eficiente**
        $graficos = $this->calculateGraficosOptimizados($reservas, $fechaInicio, $fechaFin);

        // **Optimización: Calcular estadísticas anuales de forma más eficiente**
        $estadisticasAnuales = $this->calculateEstadisticasAnualesOptimizadas($fechaInicio, $fechaFin);

        // **Optimización: Obtener datos para filtros de forma más eficiente**
        $datosFiltros = $this->getDatosFiltrosOptimizados();

        // **Combinar todos los datos**
        return array_merge(
            $ocupacionData,
            $estadisticas,
            $graficos,
            $estadisticasAnuales,
            $datosFiltros,
            [
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'reservas' => $reservas,
            ]
        );
    }

    private function calculateOcupacionOptimizada($reservas, $apartamentos, $fechaInicio, $fechaFin) {
        // **Optimización: Calcular ocupación de forma más simple y eficiente**
        $nochesOcupadas = 0;
        $totalNochesPosibles = $apartamentos->count() * ($fechaInicio->diffInDays($fechaFin) + 1);

        // **Optimización: Usar consulta SQL directa para calcular ocupación**
        // Calcular días necesarios dinámicamente
        $diasNecesarios = $fechaInicio->diffInDays($fechaFin) + 1;
        
        // Generar secuencias dinámicamente (máximo 1000 días para seguridad)
        $maxDias = min($diasNecesarios, 1000);
        $secuencias = [];
        for ($i = 0; $i < $maxDias; $i++) {
            $secuencias[] = "SELECT $i as seq";
        }
        $secuenciasSQL = implode(' UNION ', $secuencias);
        
        $result = DB::select("
            SELECT COUNT(*) as noches_ocupadas
            FROM (
                SELECT DISTINCT 
                    r.apartamento_id,
                    d.dia
                FROM reservas r
                CROSS JOIN (
                    SELECT DATE_ADD(?, INTERVAL seq.seq DAY) as dia
                    FROM (
                        $secuenciasSQL
                    ) seq
                    WHERE DATE_ADD(?, INTERVAL seq.seq DAY) <= ?
                ) d
                WHERE r.estado_id != 4
                AND r.apartamento_id IN (SELECT id FROM apartamentos WHERE id_channex IS NOT NULL)
                AND d.dia >= r.fecha_entrada
                AND d.dia < r.fecha_salida
            ) as ocupacion
        ", [$fechaInicio->toDateString(), $fechaInicio->toDateString(), $fechaFin->toDateString()]);

        $nochesOcupadas = $result[0]->noches_ocupadas ?? 0;
        $porcentajeOcupacion = ($totalNochesPosibles > 0) ? round(($nochesOcupadas / $totalNochesPosibles) * 100, 2) : 0;

        // **Apartamentos libres hoy**
        $hoy = Carbon::today();
        $apartamentosOcupadosHoy = Reserva::where('estado_id', '!=', 4)
            ->where('fecha_entrada', '<=', $hoy)
            ->where('fecha_salida', '>', $hoy)
            ->pluck('apartamento_id');

        $apartamentosLibresHoy = Apartamento::whereNotIn('id', $apartamentosOcupadosHoy)
            ->whereNotNull('edificio_id')
            ->whereNotNull('id_channex')
            ->with(['reservas' => function($query) {
                $query->where('estado_id', '!=', 4)
                      ->orderBy('fecha_entrada', 'desc');
            }])
            ->get()
            ->map(function($apartamento) {
                $ultimaReserva = $apartamento->reservas->where('fecha_salida', '<=', Carbon::today())->first();
                $proximaReserva = $apartamento->reservas->where('fecha_entrada', '>', Carbon::today())->first();
                
                $apartamento->ultima_reserva = $ultimaReserva ? Carbon::parse($ultimaReserva->fecha_salida)->format('d/m/Y') : null;
                $apartamento->proxima_reserva = $proximaReserva ? Carbon::parse($proximaReserva->fecha_entrada)->format('d/m/Y') : null;
                
                return $apartamento;
            });

        return [
            'porcentajeOcupacion' => $porcentajeOcupacion,
            'nochesOcupadas' => $nochesOcupadas,
            'totalNochesPosibles' => $totalNochesPosibles,
            'apartamentosLibresHoy' => $apartamentosLibresHoy,
        ];
    }

    private function calculateEstadisticasOptimizadas($reservas, $fechaInicio, $fechaFin) {
        // **Optimización: Calcular estadísticas básicas de forma más eficiente**
        $countReservas = $reservas->count();
        $sumPrecio = $reservas->sum(function ($reserva) {
            $precio = $reserva->precio;
            if (is_string($precio)) {
                $precio = str_replace(',', '.', $precio);
                return is_numeric($precio) ? floatval($precio) : 0;
            }
            return is_numeric($precio) ? floatval($precio) : 0;
        });

        // **Obtener categorías que se contabilizan por separado**
        $categoriasIngresosSeparadas = \App\Models\CategoriaIngresos::where('contabilizar_misma_empresa', true)->pluck('id')->toArray();
        $categoriasGastosSeparadas = \App\Models\CategoriaGastos::where('contabilizar_misma_empresa', true)->pluck('id')->toArray();
        
        // **Categorías específicas a excluir de ingresos: 12 (EXCLUIDO) siempre debe excluirse**
        $categoriasIngresosExcluidas = [12]; // EXCLUIDO - nunca debe contabilizarse
        $categoriasIngresosParaExclusion = array_merge($categoriasIngresosSeparadas, $categoriasIngresosExcluidas);
        
        // **Categorías específicas a excluir (45 y 53) - solo para el cálculo principal**
        $categoriasExcluidasEspecificas = [45, 53];
        $categoriasGastosSeparadasParaExclusion = array_merge($categoriasGastosSeparadas, $categoriasExcluidasEspecificas);
        
        // **Para gastos separados SOLO categorías de obra (con check activo), EXCLUYENDO 45 y 53**
        $categoriasExcluidasDeSeparados = [45, 53]; // Forzar exclusión de estas categorías
        
        // Buscar categorías por nombre para excluir también
        $categoriaDevolucionSocio = \App\Models\CategoriaGastos::where('nombre', 'like', '%DEVOLUCION%SOCIO%')->first();
        $categoriaPrestamos = \App\Models\CategoriaGastos::where('nombre', 'like', '%PRESTAMO%')->first();
        
        if ($categoriaDevolucionSocio) {
            $categoriasExcluidasDeSeparados[] = $categoriaDevolucionSocio->id;
        }
        if ($categoriaPrestamos) {
            $categoriasExcluidasDeSeparados[] = $categoriaPrestamos->id;
        }
        
        // Excluir las categorías específicas de los gastos separados
        $categoriasGastosSeparadasObra = array_diff($categoriasGastosSeparadas, $categoriasExcluidasDeSeparados);

        // **Optimización: Usar consultas agregadas para ingresos y gastos (EXCLUYENDO categorías separadas y EXCLUIDO)**
        $ingresos = Ingresos::whereBetween('date', [$fechaInicio, $fechaFin]);
        if (!empty($categoriasIngresosParaExclusion)) {
            $ingresos = $ingresos->whereNotIn('categoria_id', $categoriasIngresosParaExclusion);
        }
        $ingresos = $ingresos->sum('quantity');
        
        $gastos = Gastos::whereBetween('date', [$fechaInicio, $fechaFin]);
        if (!empty($categoriasGastosSeparadasParaExclusion)) {
            $gastos = $gastos->whereNotIn('categoria_id', $categoriasGastosSeparadasParaExclusion);
        }
        // Suma de ABS(quantity) por fila para que coincida con el total del modal (cada fila muestra abs(quantity))
        $gastos = $gastos->sum(DB::raw('ABS(quantity)'));

        // **Calcular ingresos para beneficio (excluyendo categorías de contabilización separada y EXCLUIDO)**
        $ingresosBeneficio = Ingresos::whereBetween('date', [$fechaInicio, $fechaFin]);
        if (!empty($categoriasIngresosParaExclusion)) {
            $ingresosBeneficio = $ingresosBeneficio->whereNotIn('categoria_id', $categoriasIngresosParaExclusion);
        }
        $ingresosBeneficio = $ingresosBeneficio->sum('quantity');

        // **Calcular gastos para beneficio (excluyendo categorías de contabilización separada)**
        $gastosBeneficio = Gastos::whereBetween('date', [$fechaInicio, $fechaFin]);
        if (!empty($categoriasGastosSeparadasParaExclusion)) {
            $gastosBeneficio = $gastosBeneficio->whereNotIn('categoria_id', $categoriasGastosSeparadasParaExclusion);
        }
        $gastosBeneficio = $gastosBeneficio->sum(DB::raw('ABS(quantity)'));

        // **Calcular ingresos y gastos de categorías marcadas para contabilización separada**
        $ingresosMismaEmpresa = 0;
        $gastosMismaEmpresa = 0;
        
        if (!empty($categoriasIngresosSeparadas)) {
            $ingresosMismaEmpresa = Ingresos::whereBetween('date', [$fechaInicio, $fechaFin])
                ->whereIn('categoria_id', $categoriasIngresosSeparadas)
                ->sum('quantity');
        }
        
        // **Gastos separados SOLO para categorías de obra (con check activo), EXCLUYENDO 45 y 53**
        if (!empty($categoriasGastosSeparadasObra)) {
            // Suma de ABS(quantity) por fila para que coincida con el total del modal
            $gastosMismaEmpresa = Gastos::whereBetween('date', [$fechaInicio, $fechaFin])
                ->whereIn('categoria_id', $categoriasGastosSeparadasObra)
                ->whereNotIn('categoria_id', [45, 53]) // Forzar exclusión de 45 y 53
                ->sum(DB::raw('ABS(quantity)'));
        }

        // **Calcular categorías específicas 45 y 53 por separado**
        $categoria45 = \App\Models\CategoriaGastos::find(45);
        $categoria53 = \App\Models\CategoriaGastos::find(53);
        
        $gastosCategoria45 = abs(Gastos::whereBetween('date', [$fechaInicio, $fechaFin])
            ->where('categoria_id', 45)
            ->sum('quantity'));
            
        $gastosCategoria53 = abs(Gastos::whereBetween('date', [$fechaInicio, $fechaFin])
            ->where('categoria_id', 53)
            ->sum('quantity'));

            
        // **Obtener listas de gastos para las categorías específicas**
        $gastosListaCategoria45 = Gastos::whereBetween('date', [$fechaInicio, $fechaFin])
            ->where('categoria_id', 45)
            ->get();
            
        $gastosListaCategoria53 = Gastos::whereBetween('date', [$fechaInicio, $fechaFin])
            ->where('categoria_id', 53)
            ->get();

        // **Obtener listas de ingresos y gastos separados para contabilización separada**
        $ingresosListaSeparados = [];
        $gastosListaSeparados = [];
        
        if (!empty($categoriasIngresosSeparadas)) {
            $ingresosListaSeparados = Ingresos::whereBetween('date', [$fechaInicio, $fechaFin])
                ->whereIn('categoria_id', $categoriasIngresosSeparadas)
                ->with('categoria')
                ->get();
        }
        
        if (!empty($categoriasGastosSeparadasObra)) {
            $gastosListaSeparados = Gastos::whereBetween('date', [$fechaInicio, $fechaFin])
                ->whereIn('categoria_id', $categoriasGastosSeparadasObra)
                ->whereNotIn('categoria_id', [45, 53]) // Forzar exclusión de 45 y 53
                ->with('categoria')
                ->get();
        }

        // **Optimización: Obtener listas de ingresos y gastos solo si son necesarias**
        // Excluir categorías que se contabilizan por separado y EXCLUIDO de las listas
        $ingresosLista = Ingresos::whereBetween('date', [$fechaInicio, $fechaFin]);
        if (!empty($categoriasIngresosParaExclusion)) {
            $ingresosLista = $ingresosLista->whereNotIn('categoria_id', $categoriasIngresosParaExclusion);
        }
        $ingresosLista = $ingresosLista->with('categoria')->get();
        
        $gastosLista = Gastos::whereBetween('date', [$fechaInicio, $fechaFin]);
        if (!empty($categoriasGastosSeparadasParaExclusion)) {
            $gastosLista = $gastosLista->whereNotIn('categoria_id', $categoriasGastosSeparadasParaExclusion);
        }
        $gastosLista = $gastosLista->with('categoria')->get();
        $categoriasGastos = CategoriaGastos::all();

        // **Calcular reservas no facturadas**
        $reservasNoFacturadas = $reservas->where('no_facturar', true);
        $sumPrecioNoFacturado = $reservasNoFacturadas->sum(function ($reserva) {
            $precio = $reserva->precio;
            if (is_string($precio)) {
                $precio = str_replace(',', '.', $precio);
                return is_numeric($precio) ? floatval($precio) : 0;
            }
            return is_numeric($precio) ? floatval($precio) : 0;
        });

        return [
            'countReservas' => $countReservas,
            'sumPrecio' => $sumPrecio,
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'ingresosBeneficio' => $ingresosBeneficio,
            'gastosBeneficio' => $gastosBeneficio,
            'ingresosMismaEmpresa' => $ingresosMismaEmpresa,
            'gastosMismaEmpresa' => $gastosMismaEmpresa,
            'ingresosLista' => $ingresosLista,
            'gastosLista' => $gastosLista,
            'categoriasGastos' => $categoriasGastos,
            'countReservasNoFacturadas' => $reservasNoFacturadas->count(),
            'sumPrecioNoFacturado' => $sumPrecioNoFacturado,
            // **Categorías específicas 45 y 53**
            'categoria45' => $categoria45,
            'categoria53' => $categoria53,
            'gastosCategoria45' => $gastosCategoria45,
            'gastosCategoria53' => $gastosCategoria53,
            'gastosListaCategoria45' => $gastosListaCategoria45,
            'gastosListaCategoria53' => $gastosListaCategoria53,
            // **Contabilización separada**
            'ingresosListaSeparados' => $ingresosListaSeparados,
            'gastosListaSeparados' => $gastosListaSeparados,
            
        ];
    }

    private function calculateGraficosOptimizados($reservas, $fechaInicio, $fechaFin) {
        // **Optimización: Calcular gráficos usando las reservas ya cargadas**
        $countReservas = $reservas->count();

        // **Gráfico de Nacionalidades**
        $nacionalidades = $reservas->groupBy('cliente.nacionalidad')
            ->map(function ($group) {
                return $group->count();
            });

        $nacionalidadesConPorcentaje = $nacionalidades->map(function ($total) use ($countReservas) {
            return [
                'total' => $total,
                'porcentaje' => $countReservas > 0 ? round(($total / $countReservas) * 100, 2) : 0
            ];
        });

        $labels = $nacionalidadesConPorcentaje->keys()->map(fn($nacionalidad) => $nacionalidad ?? 'Sin especificar')->toArray();
        $data = $nacionalidadesConPorcentaje->pluck('porcentaje')->toArray();

        // **Gráfico de Rangos de Edad**
        $rangoDefinido = [
            'Ns-nc' => 0,
            '18-30' => 0,
            '31-45' => 0,
            '46-60' => 0,
            '60+' => 0,
        ];

        foreach ($reservas as $reserva) {
            if ($reserva->cliente && $reserva->cliente->fecha_nacimiento) {
                $edad = Carbon::parse($reserva->cliente->fecha_nacimiento)->age;
                if ($edad >= 18 && $edad <= 30) {
                    $rangoDefinido['18-30']++;
                } elseif ($edad >= 31 && $edad <= 45) {
                    $rangoDefinido['31-45']++;
                } elseif ($edad >= 46 && $edad <= 60) {
                    $rangoDefinido['46-60']++;
                } elseif ($edad > 60) {
                    $rangoDefinido['60+']++;
                }
            } else {
                $rangoDefinido['Ns-nc']++;
            }
        }

        $totalClientes = array_sum($rangoDefinido);
        $edadesPorcentaje = array_map(function ($total) use ($totalClientes) {
            return $totalClientes > 0 ? round(($total / $totalClientes) * 100, 2) : 0;
        }, $rangoDefinido);

        $rangoEdades = array_keys($rangoDefinido);
        $totalesEdades = array_values($edadesPorcentaje);

        // **Gráfico de Ocupantes**
        $ocupantesDefinidos = [
            '01' => 0, '02' => 0, '03' => 0, '04' => 0, '05' => 0, '06' => 0,
        ];

        $ocupantes = $reservas->groupBy('numero_personas')
            ->map(function ($group) {
                return $group->count();
            });

        foreach ($ocupantes as $numero => $total) {
            $key = str_pad($numero, 2, '0', STR_PAD_LEFT);
            if (array_key_exists($key, $ocupantesDefinidos)) {
                $ocupantesDefinidos[$key] = $total;
            }
        }

        $totalOcupantes = array_sum($ocupantesDefinidos);
        $porcentajesOcupantes = array_map(function ($total) use ($totalOcupantes) {
            return $totalOcupantes > 0 ? round(($total / $totalOcupantes) * 100, 2) : 0;
        }, $ocupantesDefinidos);

        $ocupantesLabels = array_keys($ocupantesDefinidos);
        $ocupantesData = array_values($porcentajesOcupantes);

        // **Gráfico de Sexo**
        $sexoDefinido = [
            'Hombre' => 0,
            'Mujer' => 0,
            'Sin definir' => 0,
        ];

        foreach ($reservas as $reserva) {
            if ($reserva->cliente) {
                $sexo = $reserva->cliente->sexo;
                if ($sexo === 'Masculino') {
                    $sexoDefinido['Hombre']++;
                } elseif ($sexo === 'Femenino') {
                    $sexoDefinido['Mujer']++;
                } else {
                    $sexoDefinido['Sin definir']++;
                }
            } else {
                $sexoDefinido['Sin definir']++;
            }
        }

        $totalSexo = array_sum($sexoDefinido);
        $porcentajesSexo = array_map(function ($total) use ($totalSexo) {
            return $totalSexo > 0 ? round(($total / $totalSexo) * 100, 2) : 0;
        }, $sexoDefinido);

        $sexoLabels = array_keys($sexoDefinido);
        $sexoData = array_values($porcentajesSexo);

        // **Gráfico de Prescriptores**
        $prescriptoresDefinidos = [
            'Booking' => 0,
            'Airbnb' => 0,
            'Externo' => 0,
        ];

        $normalizarOrigen = function($origen) {
            $origenLower = strtolower(trim($origen));
            if (str_contains($origenLower, 'booking') || str_contains($origenLower, 'bookingcom')) {
                return 'Booking';
            }
            if (str_contains($origenLower, 'airbnb') || str_contains($origenLower, 'airbn')) {
                return 'Airbnb';
            }
            return 'Externo';
        };

        $prescriptores = $reservas->groupBy('origen')
            ->map(function ($group) {
                return $group->count();
            });

        foreach ($prescriptores as $origen => $total) {
            $origenNormalizado = $normalizarOrigen($origen);
            if (array_key_exists($origenNormalizado, $prescriptoresDefinidos)) {
                $prescriptoresDefinidos[$origenNormalizado] += $total;
            }
        }

        $totalPrescriptores = array_sum($prescriptoresDefinidos);
        $porcentajesPrescriptores = array_map(function ($total) use ($totalPrescriptores) {
            return $totalPrescriptores > 0 ? round(($total / $totalPrescriptores) * 100, 2) : 0;
        }, $prescriptoresDefinidos);

        $prescriptoresLabels = array_keys($prescriptoresDefinidos);
        $prescriptoresData = array_values($porcentajesPrescriptores);

        // **Gráfico de Apartamentos**
        $apartamentos = Apartamento::select('id', 'titulo')->get();
        $apartamentosDefinidos = $apartamentos->pluck('titulo')->mapWithKeys(fn($titulo) => [$titulo => 0])->toArray();

        $reservasPorApartamento = $reservas->groupBy('apartamento_id')
            ->map(function ($group) {
                return $group->count();
            });

        foreach ($reservasPorApartamento as $apartamentoId => $total) {
            $apartamento = $apartamentos->find($apartamentoId);
            if ($apartamento && array_key_exists($apartamento->titulo, $apartamentosDefinidos)) {
                $apartamentosDefinidos[$apartamento->titulo] = $total;
            }
        }

        $totalReservasPorApartamento = array_sum($apartamentosDefinidos);
        $porcentajesApartamentos = array_map(function ($total) use ($totalReservasPorApartamento) {
            return $totalReservasPorApartamento > 0 ? round(($total / $totalReservasPorApartamento) * 100, 2) : 0;
        }, $apartamentosDefinidos);

        $apartamentosLabels = array_keys($apartamentosDefinidos);
        $apartamentosData = array_values($porcentajesApartamentos);

        // **Gastos por Categoría**
        $gastosPorCategoria = Gastos::select('categoria_gastos.nombre', DB::raw('SUM(ABS(gastos.quantity)) as total'))
            ->join('categoria_gastos', 'gastos.categoria_id', '=', 'categoria_gastos.id')
            ->whereBetween('gastos.date', [$fechaInicio, $fechaFin])
            ->groupBy('categoria_gastos.nombre')
            ->pluck('total', 'nombre');

        $totalGastos = array_sum($gastosPorCategoria->toArray());
        $porcentajesGastos = $gastosPorCategoria->map(function ($total) use ($totalGastos) {
            return $totalGastos > 0 ? round(($total / $totalGastos) * 100, 2) : 0;
        });

        $categoriasLabels = $porcentajesGastos->keys()->toArray();
        $categoriasData = $porcentajesGastos->values()->toArray();

        return [
            'labels' => $labels,
            'data' => $data,
            'rangoEdades' => $rangoEdades,
            'totalesEdades' => $totalesEdades,
            'ocupantesLabels' => $ocupantesLabels,
            'ocupantesData' => $ocupantesData,
            'sexoLabels' => $sexoLabels,
            'sexoData' => $sexoData,
            'prescriptoresLabels' => $prescriptoresLabels,
            'prescriptoresData' => $prescriptoresData,
            'apartamentosLabels' => $apartamentosLabels,
            'apartamentosData' => $apartamentosData,
            'categoriasLabels' => $categoriasLabels,
            'categoriasData' => $categoriasData,
        ];
    }

    private function calculateEstadisticasAnualesOptimizadas($fechaInicio, $fechaFin) {
        // **Optimización: Calcular estadísticas anuales de forma más eficiente**
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $anioActual = Carbon::now()->year;
        $anioAnterior = $anioActual - 1;

        // **Optimización: Usar consultas agregadas para estadísticas mensuales**
        $reservasAnioActual = [];
        $reservasAnioAnterior = [];
        $beneficiosAnioActual = [];
        $beneficiosAnioAnterior = [];
        $nochesReservadasAnioActual = [];
        $nochesReservadasAnioAnterior = [];
        $ingresosAnioActual = [];
        $ingresosAnioAnterior = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $fechaInicioMes = Carbon::create($anioActual, $mes, 1)->startOfMonth();
            $fechaFinMes = Carbon::create($anioActual, $mes, 1)->endOfMonth();

            // **Optimización: Usar consultas más eficientes para estadísticas mensuales**
            $reservasMesActual = Reserva::where('estado_id', '!=', 4)
                ->where(function ($query) use ($fechaInicioMes, $fechaFinMes) {
                    $query->whereBetween('fecha_entrada', [$fechaInicioMes, $fechaFinMes])
                        ->orWhereBetween('fecha_salida', [$fechaInicioMes, $fechaFinMes])
                        ->orWhere(function ($subQuery) use ($fechaInicioMes, $fechaFinMes) {
                            $subQuery->where('fecha_entrada', '<=', $fechaInicioMes)
                                    ->where('fecha_salida', '>=', $fechaFinMes);
                        });
                })
                ->count();

            $reservasAnioActual[] = $reservasMesActual;

            // **Año anterior**
            $fechaInicioMesAnterior = Carbon::create($anioAnterior, $mes, 1)->startOfMonth();
            $fechaFinMesAnterior = Carbon::create($anioAnterior, $mes, 1)->endOfMonth();

            $reservasMesAnterior = Reserva::where('estado_id', '!=', 4)
                ->where(function ($query) use ($fechaInicioMesAnterior, $fechaFinMesAnterior) {
                    $query->whereBetween('fecha_entrada', [$fechaInicioMesAnterior, $fechaFinMesAnterior])
                        ->orWhereBetween('fecha_salida', [$fechaInicioMesAnterior, $fechaFinMesAnterior])
                        ->orWhere(function ($subQuery) use ($fechaInicioMesAnterior, $fechaFinMesAnterior) {
                            $subQuery->where('fecha_entrada', '<=', $fechaInicioMesAnterior)
                                    ->where('fecha_salida', '>=', $fechaFinMesAnterior);
                        });
                })
                ->count();

            $reservasAnioAnterior[] = $reservasMesAnterior;

            // **Optimización: Calcular noches reservadas de forma más eficiente**
            $nochesMesActual = $this->calculateNochesReservadas($fechaInicioMes, $fechaFinMes);
            $nochesReservadasAnioActual[] = $nochesMesActual;

            $nochesMesAnterior = $this->calculateNochesReservadas($fechaInicioMesAnterior, $fechaFinMesAnterior);
            $nochesReservadasAnioAnterior[] = $nochesMesAnterior;

            // **Beneficios - Usar lógica dinámica (excluyendo EXCLUIDO - categoría 12)**
            $categoriasIngresosSeparadas = \App\Models\CategoriaIngresos::where('contabilizar_misma_empresa', true)->pluck('id')->toArray();
            $categoriasGastosSeparadas = \App\Models\CategoriaGastos::where('contabilizar_misma_empresa', true)->pluck('id')->toArray();
            
            // Excluir categoría 12 (EXCLUIDO) siempre
            $categoriasIngresosExcluidasMensual = [12]; // EXCLUIDO - nunca debe contabilizarse
            $categoriasIngresosParaExclusionMensual = array_merge($categoriasIngresosSeparadas, $categoriasIngresosExcluidasMensual);

            // **Calcular ingresos mensuales (misma lógica que "Cobrado")**
            $ingresosMesActual = Ingresos::whereYear('date', $anioActual)->whereMonth('date', $mes);
            if (!empty($categoriasIngresosParaExclusionMensual)) {
                $ingresosMesActual = $ingresosMesActual->whereNotIn('categoria_id', $categoriasIngresosParaExclusionMensual);
            }
            $ingresosMesActual = $ingresosMesActual->sum('quantity');
            $ingresosAnioActual[] = $ingresosMesActual;

            $ingresosMesAnterior = Ingresos::whereYear('date', $anioAnterior)->whereMonth('date', $mes);
            if (!empty($categoriasIngresosParaExclusionMensual)) {
                $ingresosMesAnterior = $ingresosMesAnterior->whereNotIn('categoria_id', $categoriasIngresosParaExclusionMensual);
            }
            $ingresosMesAnterior = $ingresosMesAnterior->sum('quantity');
            $ingresosAnioAnterior[] = $ingresosMesAnterior;

            // **Calcular gastos para beneficio**
            $gastosMesActual = Gastos::whereYear('date', $anioActual)->whereMonth('date', $mes);
            if (!empty($categoriasGastosSeparadas)) {
                $gastosMesActual = $gastosMesActual->whereNotIn('categoria_id', $categoriasGastosSeparadas);
            }
            $gastosMesActual = abs($gastosMesActual->sum('quantity'));
            $beneficiosAnioActual[] = $ingresosMesActual - $gastosMesActual;

            $gastosMesAnterior = Gastos::whereYear('date', $anioAnterior)->whereMonth('date', $mes);
            if (!empty($categoriasGastosSeparadas)) {
                $gastosMesAnterior = $gastosMesAnterior->whereNotIn('categoria_id', $categoriasGastosSeparadas);
            }
            $gastosMesAnterior = abs($gastosMesAnterior->sum('quantity'));
            $beneficiosAnioAnterior[] = $ingresosMesAnterior - $gastosMesAnterior;
        }

        // **Optimización: Calcular disponibilidad mensual de forma más eficiente**
        $disponibilidadMensual = $this->calcularDisponibilidadMensualOptimizada();

        return [
            'meses' => $meses,
            'reservasAnioActual' => $reservasAnioActual,
            'reservasAnioAnterior' => $reservasAnioAnterior,
            'nochesReservadasAnioActual' => $nochesReservadasAnioActual,
            'nochesReservadasAnioAnterior' => $nochesReservadasAnioAnterior,
            'beneficiosAnioActual' => $beneficiosAnioActual,
            'beneficiosAnioAnterior' => $beneficiosAnioAnterior,
            'ingresosAnioActual' => $ingresosAnioActual,
            'ingresosAnioAnterior' => $ingresosAnioAnterior,
            'anioActual' => $anioActual,
            'anioAnterior' => $anioAnterior,
            'disponibilidadMensual' => $disponibilidadMensual,
        ];
    }

    private function calculateNochesReservadas($fechaInicio, $fechaFin) {
        // **Optimización: Calcular noches reservadas usando consulta SQL directa**
        $result = DB::select("
            SELECT SUM(
                GREATEST(0, 
                    LEAST(DATEDIFF(?, fecha_salida), DATEDIFF(fecha_salida, ?)) + 1
                )
            ) as noches
            FROM reservas 
            WHERE estado_id != 4
            AND fecha_entrada <= ?
            AND fecha_salida >= ?
        ", [$fechaFin->toDateString(), $fechaInicio->toDateString(), $fechaFin->toDateString(), $fechaInicio->toDateString()]);

        return $result[0]->noches ?? 0;
    }

    private function getDatosFiltrosOptimizados() {
        // **Optimización: Obtener datos para filtros de forma más eficiente**
        $apartamentos = Apartamento::whereNotNull('id_channex')->get(['id', 'titulo']);
        
        $origenes = Reserva::whereNotNull('origen')
            ->distinct()
            ->pluck('origen')
            ->filter()
            ->values();
        
        $estados = Estado::all(['id', 'nombre']);

        return [
            'apartamentos' => $apartamentos,
            'origenes' => $origenes,
            'estados' => $estados,
        ];
    }

    /**
     * Calcular disponibilidad mensual optimizada
     */
    private function calcularDisponibilidadMensualOptimizada()
    {
        $now = Carbon::now();
        $anioActual = $now->year;
        $mesActual = $now->month;
        $totalApartamentos = Apartamento::whereNotNull('id_channex')->count();
        
        $disponibilidad = [];
        
        // **Optimización: Calcular disponibilidad mensual de forma más eficiente**
        for ($mes = 1; $mes <= 12; $mes++) {
            $anio = $anioActual;
            
            if ($mes > $mesActual) {
                $anio = $anioActual - 1;
            }
            
            $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
            $fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth();
            $diasEnMes = $fechaInicio->daysInMonth;
            
            $capacidadMaxima = $totalApartamentos * $diasEnMes;
            
            // **Optimización: Usar consulta SQL directa para calcular ocupación mensual**
            $nochesOcupadas = DB::select("
                SELECT COUNT(*) as noches_ocupadas
                FROM (
                    SELECT DISTINCT 
                        r.apartamento_id,
                        d.dia
                    FROM reservas r
                    CROSS JOIN (
                        SELECT DATE_ADD(?, INTERVAL seq.seq DAY) as dia
                        FROM (
                            SELECT 0 as seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
                            SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION
                            SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION
                            SELECT 30 UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35 UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION
                            SELECT 40 UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45 UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION
                            SELECT 50 UNION SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54 UNION SELECT 55 UNION SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59 UNION
                            SELECT 60 UNION SELECT 61 UNION SELECT 62 UNION SELECT 63 UNION SELECT 64 UNION SELECT 65 UNION SELECT 66 UNION SELECT 67 UNION SELECT 68 UNION SELECT 69 UNION
                            SELECT 70 UNION SELECT 71 UNION SELECT 72 UNION SELECT 73 UNION SELECT 74 UNION SELECT 75 UNION SELECT 76 UNION SELECT 77 UNION SELECT 78 UNION SELECT 79 UNION
                            SELECT 80 UNION SELECT 81 UNION SELECT 82 UNION SELECT 83 UNION SELECT 84 UNION SELECT 85 UNION SELECT 86 UNION SELECT 87 UNION SELECT 88 UNION SELECT 89 UNION
                            SELECT 90 UNION SELECT 91 UNION SELECT 92 UNION SELECT 93 UNION SELECT 94 UNION SELECT 95 UNION SELECT 96 UNION SELECT 97 UNION SELECT 98 UNION SELECT 99
                        ) seq
                        WHERE DATE_ADD(?, INTERVAL seq.seq DAY) <= ?
                    ) d
                    WHERE r.estado_id != 4
                    AND r.apartamento_id IN (SELECT id FROM apartamentos WHERE id_channex IS NOT NULL)
                    AND d.dia >= r.fecha_entrada
                    AND d.dia < r.fecha_salida
                ) as ocupacion
            ", [$fechaInicio->toDateString(), $fechaInicio->toDateString(), $fechaFin->toDateString()]);

            $nochesOcupadas = $nochesOcupadas[0]->noches_ocupadas ?? 0;
            $nochesDisponibles = $capacidadMaxima - $nochesOcupadas;
            $porcentajeDisponibilidad = $capacidadMaxima > 0 ? round(($nochesDisponibles / $capacidadMaxima) * 100, 2) : 0;
            $porcentajeOcupacion = $capacidadMaxima > 0 ? round(($nochesOcupadas / $capacidadMaxima) * 100, 2) : 0;
            
            $disponibilidad[] = [
                'mes' => $fechaInicio->format('M Y'),
                'anio' => $anio,
                'mes_numero' => $mes,
                'capacidad_maxima' => $capacidadMaxima,
                'noches_ocupadas' => $nochesOcupadas,
                'noches_disponibles' => $nochesDisponibles,
                'porcentaje_disponibilidad' => $porcentajeDisponibilidad,
                'porcentaje_ocupacion' => $porcentajeOcupacion,
                'total_apartamentos' => $totalApartamentos,
                'dias_en_mes' => $diasEnMes
            ];
        }
        
        return $disponibilidad;
    }

}
