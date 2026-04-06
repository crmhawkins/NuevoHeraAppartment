<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComprobarReserva extends Controller
{
    public function index($estado){
        // Comprobamos la reserva
        $reserva = Reserva::where('codigo_reserva', $estado)->first();
        // Si la reserva  existe
        if($reserva != null){
            return response()->json($reserva,200);
        }
        // Si no existe la reserva
        return response('La reserva no existe', 404);
    }

    public function verificarReserva($reserva){
        // Comprobamos la reserva
        $reservaCheck = Reserva::where('codigo_reserva', $reserva)->first();
        // Si la reserva  existe
        if($reservaCheck != null){
            return response()->json($reservaCheck, 200);
        }
        // Si no existe la reserva
        return response('La reserva no existe', 404);
    }

    public function comprobarReservaWeb($id){
        // Comprobamos la reserva
        $reserva = Reserva::where('codigo_reserva', $id)->first();
        // Si la reserva  existe
        if($reserva != null){
            return response()->json($reserva,200);
        }
        // Si no existe la reserva
        return response('La reserva no existe', 404);
    }
    public function obtenerReserva(Request $request)
    {
        if (isset($request->codigo)) {
            $reserva = Reserva::where('codigo_reserva', $request->codigo)->first();
            if ($reserva) {
                $cliente = Cliente::find($reserva->cliente_id);
                if ($cliente) {
                    return response()->json([
                        "reserva" => $reserva,
                        "cliente" => $cliente,
                        "link" => route('dni.index', $reserva->token),
                        "error" => false
                    ]);
                }else {
                    return response()->json([
                        "error" => true,
                        "mensaje" => "No se encontro ningun cliente de esa reserva"
                    ]);
                }
                
            } else {
                return response()->json([
                    "error" => true,
                    "mensaje" => "No se encontro ninguna reserva"
                ]);
            }
        }else{
            return response()->json([
                "error" => true,
                "mensaje" => "No se envio ningun codigo de reserva"
            ]);
        }
    }

    public function obtenerCodigos()
    {
        $hoy = Carbon::now();
        $reserva = Reserva::whereDate('fecha_entrada','>=' ,$hoy)->get();
        $data = [];
        foreach($reserva as $item){
            array_push($data, $item->codigo_reserva);
        }
        return response()->json([
            "data" => $data,
            "error" => false
        ]);
    }

    public function obtenerCodigosAirBnb()
    {
        $hoy = Carbon::now();
        $reserva = Reserva::whereDate('fecha_entrada','>=' ,$hoy)->where('origen', 'Airbnb')->get();
        
        $data = [];
        foreach($reserva as $item){
            array_push($data, $item->codigo_reserva);
        }
        return response()->json([
            "data" => $data,
            "error" => false
        ]);
    }
}
