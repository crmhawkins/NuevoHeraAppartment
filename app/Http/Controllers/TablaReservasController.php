<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\Cliente;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TablaReservasController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m'));
        $carbonDate = \Carbon\Carbon::createFromFormat('Y-m', $date);
        $dateObject = Carbon::createFromFormat('Y-m', $date);
        $daysInMonth = $dateObject->daysInMonth;

        $monthName = ucfirst($dateObject->locale('es')->isoFormat('MMMM YYYY')); // Formatea el mes en español
        $year = $dateObject->year; // año
        $apartamentos = Apartamento::all();

        foreach ($apartamentos as $apartamento) {
            // Obtener reservas que tienen su fecha de entrada o fecha de salida dentro del mes y año actual seleccionado
            $reservas = Reserva::where('apartamento_id', $apartamento->id)
                                ->where('estado_id', '!=', 4) // <-- Añadimos esta condición
                                ->where(function($query) use ($dateObject) {
                                    // Filtrar reservas cuya fecha de entrada o salida caigan dentro del mes seleccionado
                                    $query->whereMonth('fecha_entrada', $dateObject->month)
                                        ->whereYear('fecha_entrada', $dateObject->year)
                                        ->orWhere(function($query) use ($dateObject) {
                                            // Reservas cuyo mes de salida esté en el mes seleccionado
                                            $query->whereMonth('fecha_salida', $dateObject->month)
                                                    ->whereYear('fecha_salida', $dateObject->year);
                                        })
                                        ->orWhere(function($query) use ($dateObject) {
                                            // Reservas que abarcan el mes de marzo (entradas de febrero con salidas en marzo o abril)
                                            $query->where('fecha_entrada', '<=', $dateObject->endOfMonth())
                                                    ->where('fecha_salida', '>=', $dateObject->startOfMonth());
                                        });
                                })
                                ->orderBy('fecha_entrada', 'asc')
                                ->get();

            $apartamento->reservas = $reservas; // Añadir reservas al apartamento

            foreach ($reservas as $reserva) {
                $reserva->fecha_entrada = Carbon::parse($reserva->fecha_entrada);
                $reserva->fecha_salida = Carbon::parse($reserva->fecha_salida);
            }
        }

        return view('admin.reservas.tabla', compact('apartamentos', 'daysInMonth', 'monthName', 'carbonDate', 'date', 'year'));
    }


}
