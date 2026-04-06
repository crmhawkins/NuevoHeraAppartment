<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Obtener notificaciones del usuario autenticado
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 20);
        $unreadOnly = $request->boolean('unread_only', false);
        
        $notifications = NotificationService::getForUser($userId, $limit, $unreadOnly);
        $unreadCount = NotificationService::getUnreadCount($userId);
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'total' => $notifications->count()
        ]);
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public function unreadCount()
    {
        $userId = Auth::id();
        $count = NotificationService::getUnreadCount($userId);
        
        return response()->json(['count' => $count]);
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);
        
        $notification->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    }

    /**
     * Marcar notificación como no leída
     */
    public function markAsUnread($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);
        
        $notification->markAsUnread();
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como no leída'
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        $userId = Auth::id();
        $count = NotificationService::markAllAsRead($userId);
        
        return response()->json([
            'success' => true,
            'message' => "Se marcaron {$count} notificaciones como leídas"
        ]);
    }

    /**
     * Eliminar notificación
     */
    public function destroy($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);
        
        $notification->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada'
        ]);
    }

    /**
     * Eliminar todas las notificaciones leídas
     */
    public function destroyRead()
    {
        $userId = Auth::id();
        $count = Notification::where('user_id', $userId)
            ->whereNotNull('read_at')
            ->delete();
        
        return response()->json([
            'success' => true,
            'message' => "Se eliminaron {$count} notificaciones leídas"
        ]);
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function stats(Request $request)
    {
        $userId = Auth::id();
        $days = $request->get('days', 7);
        
        $stats = Notification::getStats($userId, $days);
        
        return response()->json($stats);
    }

    /**
     * Obtener notificaciones por tipo
     */
    public function byType($type, Request $request)
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 20);
        
        $notifications = Notification::where('user_id', $userId)
            ->ofType($type)
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json($notifications);
    }

    /**
     * Obtener notificaciones por prioridad
     */
    public function byPriority($priority, Request $request)
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 20);
        
        $notifications = Notification::where('user_id', $userId)
            ->ofPriority($priority)
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json($notifications);
    }

    /**
     * Buscar notificaciones
     */
    public function search(Request $request)
    {
        $userId = Auth::id();
        $query = $request->get('q');
        $type = $request->get('type');
        $priority = $request->get('priority');
        $category = $request->get('category');
        $limit = $request->get('limit', 20);
        
        $notifications = Notification::where('user_id', $userId)
            ->notExpired();
        
        if ($query) {
            $notifications->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('message', 'like', "%{$query}%");
            });
        }
        
        if ($type) {
            $notifications->ofType($type);
        }
        
        if ($priority) {
            $notifications->ofPriority($priority);
        }
        
        if ($category) {
            $notifications->where('category', $category);
        }
        
        $notifications = $notifications->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json($notifications);
    }

    /**
     * Obtener notificaciones críticas
     */
    public function critical()
    {
        $userId = Auth::id();
        
        $notifications = Notification::where('user_id', $userId)
            ->ofPriority(Notification::PRIORITY_CRITICAL)
            ->unread()
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($notifications);
    }

    /**
     * Obtener notificaciones recientes (últimas 24 horas)
     */
    public function recent()
    {
        $userId = Auth::id();
        
        $notifications = Notification::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDay())
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($notifications);
    }

    /**
     * Obtener notificaciones expiradas
     */
    public function expired()
    {
        $userId = Auth::id();
        
        $notifications = Notification::where('user_id', $userId)
            ->where('expires_at', '<', now())
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($notifications);
    }

    /**
     * Limpiar notificaciones expiradas
     */
    public function cleanExpired()
    {
        $userId = Auth::id();
        
        $count = Notification::where('user_id', $userId)
            ->where('expires_at', '<', now())
            ->delete();
        
        return response()->json([
            'success' => true,
            'message' => "Se eliminaron {$count} notificaciones expiradas"
        ]);
    }

    /**
     * Obtener configuración de notificaciones del usuario
     */
    public function settings()
    {
        $user = Auth::user();
        
        // Aquí podrías implementar configuraciones de notificaciones por usuario
        $settings = [
            'email_notifications' => true,
            'push_notifications' => true,
            'sound_enabled' => true,
            'types' => [
                'reserva' => true,
                'incidencia' => true,
                'limpieza' => true,
                'facturacion' => true,
                'inventario' => true,
                'sistema' => true,
                'whatsapp' => true,
                'channex' => true
            ],
            'priorities' => [
                'critical' => true,
                'high' => true,
                'medium' => true,
                'low' => false
            ]
        ];
        
        return response()->json($settings);
    }

    /**
     * Actualizar configuración de notificaciones
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        
        // Aquí podrías guardar las configuraciones en la base de datos
        // Por ahora solo validamos y devolvemos éxito
        
        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sound_enabled' => 'boolean',
            'types' => 'array',
            'priorities' => 'array'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada correctamente'
        ]);
    }
}
