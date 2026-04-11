<?php

namespace App\Http\Controllers;

use App\Models\MensajeChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChannexMensajesController extends Controller
{
    /**
     * Muestra la vista de conversaciones Channex (Booking/Airbnb) estilo chat.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        // Obtener conversaciones agrupadas por booking_id con el ultimo mensaje de cada una
        $subQuery = MensajeChat::selectRaw('MAX(id) as id')
            ->whereNotNull('booking_id')
            ->groupBy('booking_id');

        $conversacionesQuery = MensajeChat::whereIn('id', $subQuery)
            ->orderBy('received_at', 'desc');

        if ($search) {
            $conversacionesQuery->where(function ($q) use ($search) {
                $q->where('booking_id', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $conversaciones = $conversacionesQuery->get();

        return view('admin.channex-mensajes.index', compact('conversaciones'));
    }

    /**
     * Devuelve los mensajes de una conversacion especifica (por booking_id) en JSON.
     */
    public function mensajes($bookingId)
    {
        $mensajes = MensajeChat::where('booking_id', $bookingId)
            ->orderBy('received_at', 'asc')
            ->get();

        return response()->json($mensajes);
    }
}
