<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlertController extends Controller
{
    /**
     * Obtener alertas no leídas del usuario
     */
    public function getUnreadAlerts(): JsonResponse
    {
        $alerts = AlertService::getUnreadAlerts();
        
        return response()->json([
            'success' => true,
            'alerts' => $alerts
        ]);
    }

    /**
     * Marcar alerta como leída
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'alert_id' => 'required|integer|exists:alerts,id'
        ]);

        $success = AlertService::markAsRead($request->alert_id);
        
        return response()->json([
            'success' => $success
        ]);
    }

    /**
     * Marcar todas las alertas como leídas
     */
    public function markAllAsRead(): JsonResponse
    {
        $userId = auth()->id();
        
        Alert::where('user_id', $userId)
             ->where('is_read', false)
             ->update(['is_read' => true]);
        
        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Crear una nueva alerta (para administradores)
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:info,warning,error,success',
            'scenario' => 'required|string',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'action_url' => 'nullable|string',
            'action_text' => 'nullable|string',
            'is_dismissible' => 'boolean',
            'expires_at' => 'nullable|date'
        ]);

        $alert = AlertService::create($request->all());
        
        return response()->json([
            'success' => true,
            'alert' => $alert
        ]);
    }

    /**
     * Eliminar una alerta
     */
    public function destroy($id): JsonResponse
    {
        $alert = Alert::where('id', $id)
                     ->where('user_id', auth()->id())
                     ->first();
        
        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alerta no encontrada'
            ], 404);
        }
        
        $alert->delete();
        
        return response()->json([
            'success' => true
        ]);
    }
}
