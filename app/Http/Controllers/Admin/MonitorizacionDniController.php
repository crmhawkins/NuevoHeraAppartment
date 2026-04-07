<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\Huesped;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitorizacionDniController extends Controller
{
    /**
     * Dashboard de monitorización de DNI para reservas próximas.
     */
    public function index(Request $request)
    {
        // Rango de fechas por defecto: hoy + 7 días
        $fechaDesde = $request->input('fecha_desde', Carbon::today()->toDateString());
        $fechaHasta = $request->input('fecha_hasta', Carbon::today()->addDays(7)->toDateString());
        $estadoFiltro = $request->input('estado_dni', 'todos');

        // Obtener reservas activas (no canceladas) con check-in en el rango
        $reservasQuery = Reserva::with(['cliente', 'apartamento'])
            ->whereDate('fecha_entrada', '>=', $fechaDesde)
            ->whereDate('fecha_entrada', '<=', $fechaHasta)
            ->activas()
            ->orderBy('fecha_entrada', 'asc');

        $reservas = $reservasQuery->get();

        // Para cada reserva, calcular el estado del DNI
        $reservasConEstado = $reservas->map(function ($reserva) {
            $huespedes = Huesped::where('reserva_id', $reserva->id)->get();
            $totalHuespedes = $huespedes->count();

            // Contar huéspedes con DNI completo (tienen numero_identificacion)
            $huespedesConDni = $huespedes->filter(function ($h) {
                return !empty($h->numero_identificacion);
            })->count();

            // También verificar el cliente principal
            $clienteTieneDni = $reserva->cliente && !empty($reserva->cliente->num_identificacion);

            // Determinar estado del DNI
            // Total de personas esperadas
            $totalEsperado = max($reserva->numero_personas ?? 1, 1);

            // Contar DNIs completos (cliente principal + huéspedes adicionales)
            $dniCompletos = $huespedesConDni;
            if ($clienteTieneDni && $totalHuespedes == 0) {
                // Si no hay huéspedes registrados pero el cliente tiene DNI, contar 1
                $dniCompletos = 1;
            } elseif ($clienteTieneDni) {
                // Si ya hay huéspedes, verificar si el cliente está entre ellos
                $clienteEnHuespedes = $huespedes->contains(function ($h) use ($reserva) {
                    return $h->numero_identificacion == $reserva->cliente->num_identificacion;
                });
                if (!$clienteEnHuespedes) {
                    $dniCompletos++;
                }
            }

            // Estado: completo, parcial, sin_datos, mir_enviado
            if ($reserva->mir_enviado) {
                $estadoDni = 'enviado_mir';
            } elseif ($dniCompletos >= $totalEsperado) {
                $estadoDni = 'completo';
            } elseif ($dniCompletos > 0) {
                $estadoDni = 'parcial';
            } else {
                $estadoDni = 'sin_datos';
            }

            $reserva->estado_dni_calculado = $estadoDni;
            $reserva->dni_completos = $dniCompletos;
            $reserva->total_esperado = $totalEsperado;
            $reserva->total_huespedes_registrados = $totalHuespedes;

            return $reserva;
        });

        // Filtrar por estado si se seleccionó
        if ($estadoFiltro !== 'todos') {
            $reservasConEstado = $reservasConEstado->filter(function ($r) use ($estadoFiltro) {
                return $r->estado_dni_calculado === $estadoFiltro;
            });
        }

        // Calcular estadísticas
        $totalReservas = $reservas->count();
        $dniCompleto = $reservas->filter(function ($r) {
            // Recalcular para stats sin filtro
            $huespedes = Huesped::where('reserva_id', $r->id)->get();
            $totalHuespedes = $huespedes->count();
            $huespedesConDni = $huespedes->filter(fn($h) => !empty($h->numero_identificacion))->count();
            $clienteTieneDni = $r->cliente && !empty($r->cliente->num_identificacion);
            $totalEsperado = max($r->numero_personas ?? 1, 1);
            $dniCompletos = $huespedesConDni;
            if ($clienteTieneDni && $totalHuespedes == 0) {
                $dniCompletos = 1;
            } elseif ($clienteTieneDni) {
                $clienteEnHuespedes = $huespedes->contains(fn($h) => $h->numero_identificacion == $r->cliente->num_identificacion);
                if (!$clienteEnHuespedes) $dniCompletos++;
            }
            return $r->mir_enviado || $dniCompletos >= $totalEsperado;
        })->count();

        $dniPendiente = $reservas->filter(function ($r) {
            $huespedes = Huesped::where('reserva_id', $r->id)->get();
            $totalHuespedes = $huespedes->count();
            $huespedesConDni = $huespedes->filter(fn($h) => !empty($h->numero_identificacion))->count();
            $clienteTieneDni = $r->cliente && !empty($r->cliente->num_identificacion);
            $dniCompletos = $huespedesConDni;
            if ($clienteTieneDni && $totalHuespedes == 0) {
                $dniCompletos = 1;
            } elseif ($clienteTieneDni) {
                $clienteEnHuespedes = $huespedes->contains(fn($h) => $h->numero_identificacion == $r->cliente->num_identificacion);
                if (!$clienteEnHuespedes) $dniCompletos++;
            }
            return $dniCompletos > 0 && !$r->mir_enviado && $dniCompletos < max($r->numero_personas ?? 1, 1);
        })->count();

        $sinDatos = $totalReservas - $dniCompleto - $dniPendiente;

        return view('admin.monitorizacion-dni.index', compact(
            'reservasConEstado',
            'totalReservas',
            'dniCompleto',
            'dniPendiente',
            'sinDatos',
            'fechaDesde',
            'fechaHasta',
            'estadoFiltro'
        ));
    }

    /**
     * Detalle de DNI de una reserva específica.
     */
    public function detalle($reservaId)
    {
        $reserva = Reserva::with(['cliente', 'apartamento'])->findOrFail($reservaId);
        $huespedes = Huesped::where('reserva_id', $reserva->id)->get();

        $totalEsperado = max($reserva->numero_personas ?? 1, 1);

        return view('admin.monitorizacion-dni.detalle', compact(
            'reserva',
            'huespedes',
            'totalEsperado'
        ));
    }
}
