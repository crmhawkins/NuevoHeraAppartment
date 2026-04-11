<?php

namespace App\Http\Controllers;

use App\Models\MensajeChat;
use App\Models\ChatGpt;
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
        // Obtener mensajes del huesped desde tabla mensajes
        $mensajesHuesped = MensajeChat::where('booking_id', $bookingId)
            ->orderBy('received_at', 'asc')
            ->get();

        // Obtener respuestas de la IA desde tabla whatsapp_mensaje_chatgpt
        // id_mensaje es varchar, mensajes.id es int — convertimos a string para el match
        $mensajeIds = $mensajesHuesped->pluck('id')->map(fn($id) => (string)$id)->toArray();
        $respuestasIA = ChatGpt::whereIn('id_mensaje', $mensajeIds)
            ->whereNotNull('respuesta')
            ->where('respuesta', '!=', '')
            ->get()
            ->keyBy('id_mensaje');

        // Combinar: por cada mensaje del huesped, añadir la respuesta de la IA si existe
        $resultado = [];
        foreach ($mensajesHuesped as $msg) {
            $resultado[] = [
                'id' => $msg->id,
                'booking_id' => $msg->booking_id,
                'sender' => $msg->sender,
                'message' => $msg->message,
                'received_at' => $msg->received_at,
                'type' => 'guest',
            ];

            // Si hay respuesta de la IA para este mensaje
            if (isset($respuestasIA[$msg->id])) {
                $respuesta = $respuestasIA[$msg->id];
                $resultado[] = [
                    'id' => 'ai_' . $respuesta->id,
                    'booking_id' => $msg->booking_id,
                    'sender' => 'Hawkins AI',
                    'message' => $respuesta->respuesta,
                    'received_at' => $respuesta->created_at,
                    'type' => 'hotel',
                ];
            }
        }

        return response()->json($resultado);
    }
}
