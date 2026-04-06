<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServiciosController extends Controller
{
    /**
     * Mostrar página de servicios disponibles
     */
    public function index()
    {
        // Mostrar solo servicios con precio (extras comprables)
        $servicios = Servicio::activos()
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->ordenados()
            ->get();

        return view('public.servicios.index', compact('servicios'));
    }

    /**
     * Formulario de reserva con rango de fechas (solo para servicios que lo requieren, ej. Alquiler de coche).
     */
    public function reservaRango(string $servicio)
    {
        $servicioModel = Servicio::activos()
            ->where('slug', $servicio)
            ->firstOrFail();

        if (!$servicioModel->esAlquilerCoche()) {
            return redirect()->route('web.servicios')->with('error', __('services.reserva_rango_only_car'));
        }

        return view('public.servicios.reserva-rango', compact('servicioModel'));
    }

    /**
     * Comprobar disponibilidad para un servicio con rango de fechas.
     * Para "Alquiler de coche" devuelve siempre no disponible.
     */
    public function comprobarDisponibilidad(Request $request, string $servicio)
    {
        $servicioModel = Servicio::activos()
            ->where('slug', $servicio)
            ->firstOrFail();

        if (!$servicioModel->esAlquilerCoche()) {
            return redirect()->route('web.servicios')->with('error', __('services.reserva_rango_only_car'));
        }

        $request->validate([
            'fecha_entrada' => 'required|date|after_or_equal:today',
            'fecha_salida' => 'required|date|after:fecha_entrada',
        ], [], [
            'fecha_entrada' => __('services.date_from'),
            'fecha_salida' => __('services.date_to'),
        ]);

        $fechaEntrada = $request->input('fecha_entrada');
        $fechaSalida = $request->input('fecha_salida');

        // Para Alquiler de coche: siempre no disponible
        $disponible = false;

        return view('public.servicios.disponibilidad-resultado', [
            'servicio' => $servicioModel,
            'fecha_entrada' => $fechaEntrada,
            'fecha_salida' => $fechaSalida,
            'disponible' => $disponible,
        ]);
    }
}
