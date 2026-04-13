<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Reserva;
use App\Models\Incidencia;
use App\Models\Apartamento;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Crear notificación de nueva reserva
     */
    public static function notifyNewReservation(Reserva $reserva)
    {
        $apartamento = $reserva->apartamento;
        $cliente = $reserva->cliente;
        
        $title = "Nueva Reserva - {$apartamento->titulo}";
        $message = "Reserva #{$reserva->codigo_reserva} para {$cliente->alias} del {$reserva->fecha_entrada} al {$reserva->fecha_salida}";
        
        $data = [
            'reserva_id' => $reserva->id,
            'codigo_reserva' => $reserva->codigo_reserva,
            'apartamento' => $apartamento->titulo,
            'cliente' => $cliente->alias,
            'fecha_entrada' => $reserva->fecha_entrada,
            'fecha_salida' => $reserva->fecha_salida,
            'precio' => $reserva->precio,
            'origen' => $reserva->origen
        ];
        
        $actionUrl = route('reservas.show', $reserva->id);
        
        // Notificar a administradores
        $notifications = Notification::createForAdmins(
            Notification::TYPE_RESERVA,
            $title,
            $message,
            $data,
            Notification::PRIORITY_MEDIUM,
            Notification::CATEGORY_SUCCESS,
            $actionUrl
        );
        
        // Broadcast en tiempo real
        self::broadcastNotifications($notifications);
        
        Log::info('Notificación de nueva reserva creada', [
            'reserva_id' => $reserva->id,
            'notifications_count' => count($notifications)
        ]);
        
        return $notifications;
    }

    /**
     * Crear notificación de actualización de reserva
     */
    public static function notifyReservationUpdate(Reserva $reserva, $oldData)
    {
        $apartamento = $reserva->apartamento;
        $cliente = $reserva->cliente;
        
        $title = "Reserva Actualizada - {$apartamento->titulo}";
        $message = "Reserva #{$reserva->codigo_reserva} ha sido actualizada para {$cliente->alias}";
        
        $data = [
            'reserva_id' => $reserva->id,
            'codigo_reserva' => $reserva->codigo_reserva,
            'apartamento' => $apartamento->titulo,
            'cliente' => $cliente->alias,
            'fecha_entrada' => $reserva->fecha_entrada,
            'fecha_salida' => $reserva->fecha_salida,
            'precio' => $reserva->precio,
            'origen' => $reserva->origen,
            'old_data' => $oldData,
            'changes' => array_diff_assoc($reserva->toArray(), $oldData)
        ];
        
        $actionUrl = route('reservas.show', $reserva->id);
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_RESERVA,
            $title,
            $message,
            $data,
            Notification::PRIORITY_MEDIUM,
            Notification::CATEGORY_INFO,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de cancelación de reserva
     */
    public static function notifyReservationCancellation(Reserva $reserva, $reason = null)
    {
        $apartamento = $reserva->apartamento;
        $cliente = $reserva->cliente;
        
        $title = "Reserva Cancelada - {$apartamento->titulo}";
        $message = "Reserva #{$reserva->codigo_reserva} cancelada para {$cliente->alias}";
        
        if ($reason) {
            $message .= " - Motivo: {$reason}";
        }
        
        $data = [
            'reserva_id' => $reserva->id,
            'codigo_reserva' => $reserva->codigo_reserva,
            'apartamento' => $apartamento->titulo,
            'cliente' => $cliente->alias,
            'fecha_entrada' => $reserva->fecha_entrada,
            'fecha_salida' => $reserva->fecha_salida,
            'reason' => $reason
        ];
        
        $actionUrl = route('reservas.show', $reserva->id);
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_RESERVA,
            $title,
            $message,
            $data,
            Notification::PRIORITY_HIGH,
            Notification::CATEGORY_WARNING,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de check-in
     */
    public static function notifyCheckIn(Reserva $reserva)
    {
        $apartamento = $reserva->apartamento;
        $cliente = $reserva->cliente;
        
        $title = "Check-in Realizado - {$apartamento->titulo}";
        $message = "{$cliente->alias} ha realizado check-in en {$apartamento->titulo}";
        
        $data = [
            'reserva_id' => $reserva->id,
            'codigo_reserva' => $reserva->codigo_reserva,
            'apartamento' => $apartamento->titulo,
            'cliente' => $cliente->alias,
            'fecha_entrada' => $reserva->fecha_entrada
        ];
        
        $actionUrl = route('reservas.show', $reserva->id);
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_RESERVA,
            $title,
            $message,
            $data,
            Notification::PRIORITY_MEDIUM,
            Notification::CATEGORY_SUCCESS,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de check-out
     */
    public static function notifyCheckOut(Reserva $reserva)
    {
        $apartamento = $reserva->apartamento;
        $cliente = $reserva->cliente;
        
        $title = "Check-out Realizado - {$apartamento->titulo}";
        $message = "{$cliente->alias} ha realizado check-out de {$apartamento->titulo} - Apartamento listo para limpieza";
        
        $data = [
            'reserva_id' => $reserva->id,
            'codigo_reserva' => $reserva->codigo_reserva,
            'apartamento' => $apartamento->titulo,
            'cliente' => $cliente->alias,
            'fecha_salida' => $reserva->fecha_salida
        ];
        
        $actionUrl = route('gestion.apartamentos.index');
        
        // Notificar a administradores y limpiadoras
        $adminNotifications = Notification::createForAdmins(
            Notification::TYPE_RESERVA,
            $title,
            $message,
            $data,
            Notification::PRIORITY_MEDIUM,
            Notification::CATEGORY_INFO,
            $actionUrl
        );
        
        $cleanerNotifications = Notification::createForCleaners(
            Notification::TYPE_LIMPIEZA,
            "Apartamento Listo para Limpieza - {$apartamento->titulo}",
            "El apartamento {$apartamento->titulo} está listo para limpieza tras el check-out de {$cliente->alias}",
            $data,
            Notification::PRIORITY_HIGH,
            Notification::CATEGORY_INFO,
            $actionUrl
        );
        
        $allNotifications = array_merge($adminNotifications, $cleanerNotifications);
        self::broadcastNotifications($allNotifications);
        
        return $allNotifications;
    }

    /**
     * Crear notificación de nueva incidencia
     */
    public static function notifyNewIncident(Incidencia $incidencia)
    {
        $apartamento = $incidencia->apartamento;
        $empleada = $incidencia->empleada;
        
        $title = "Nueva Incidencia - {$incidencia->titulo}";
        $empleadaNombre = $empleada ? $empleada->name : ($incidencia->origen === 'whatsapp' ? 'Sistema WhatsApp' : 'Sistema');
        $message = "Incidencia reportada por {$empleadaNombre}";
        
        if ($apartamento) {
            $message .= " en {$apartamento->titulo}";
        } elseif ($incidencia->apartamento_nombre) {
            $message .= " en {$incidencia->apartamento_nombre}";
        }
        
        $data = [
            'incidencia_id' => $incidencia->id,
            'titulo' => $incidencia->titulo,
            'descripcion' => $incidencia->descripcion,
            'tipo' => $incidencia->tipo,
            'prioridad' => $incidencia->prioridad,
            'apartamento' => $apartamento ? $apartamento->titulo : ($incidencia->apartamento_nombre ?? null),
            'empleada' => $empleadaNombre,
            'estado' => $incidencia->estado
        ];
        
        $actionUrl = route('admin.incidencias.show', $incidencia->id);
        
        $priority = match($incidencia->prioridad) {
            'critica' => Notification::PRIORITY_CRITICAL,
            'alta' => Notification::PRIORITY_HIGH,
            'media' => Notification::PRIORITY_MEDIUM,
            'baja' => Notification::PRIORITY_LOW,
            default => Notification::PRIORITY_MEDIUM
        };
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_INCIDENCIA,
            $title,
            $message,
            $data,
            $priority,
            Notification::CATEGORY_WARNING,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de incidencia resuelta
     */
    public static function notifyIncidentResolved(Incidencia $incidencia)
    {
        $apartamento = $incidencia->apartamento;
        
        $title = "Incidencia Resuelta - {$incidencia->titulo}";
        $message = "La incidencia ha sido marcada como resuelta";
        
        if ($apartamento) {
            $message .= " en {$apartamento->titulo}";
        }
        
        $data = [
            'incidencia_id' => $incidencia->id,
            'titulo' => $incidencia->titulo,
            'apartamento' => $apartamento ? $apartamento->titulo : null,
            'estado' => $incidencia->estado
        ];
        
        $actionUrl = route('admin.incidencias.show', $incidencia->id);
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_INCIDENCIA,
            $title,
            $message,
            $data,
            Notification::PRIORITY_LOW,
            Notification::CATEGORY_SUCCESS,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de limpieza completada
     */
    public static function notifyCleaningCompleted($apartamentoId, $empleadaId)
    {
        $apartamento = Apartamento::find($apartamentoId);
        $empleada = User::find($empleadaId);
        
        $title = "Limpieza Completada - {$apartamento->titulo}";
        $message = "La limpieza ha sido completada por {$empleada->name}";
        
        $data = [
            'apartamento_id' => $apartamentoId,
            'apartamento' => $apartamento->titulo,
            'empleada' => $empleada->name,
            'completada_at' => now()
        ];
        
        $actionUrl = route('gestion.apartamentos.index');
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_LIMPIEZA,
            $title,
            $message,
            $data,
            Notification::PRIORITY_MEDIUM,
            Notification::CATEGORY_SUCCESS,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de nueva factura
     */
    public static function notifyNewInvoice($invoiceId, $clienteId, $total)
    {
        $cliente = Cliente::find($clienteId);
        
        $title = "Nueva Factura Generada";
        $message = "Factura por €{$total} generada para {$cliente->alias}";
        
        $data = [
            'invoice_id' => $invoiceId,
            'cliente' => $cliente->alias,
            'total' => $total,
            'fecha' => now()
        ];
        
        $actionUrl = route('invoices.show', $invoiceId);
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_FACTURACION,
            $title,
            $message,
            $data,
            Notification::PRIORITY_LOW,
            Notification::CATEGORY_INFO,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de stock bajo
     */
    public static function notifyLowStock($articuloId, $articuloNombre, $stockActual, $stockMinimo)
    {
        $title = "Stock Bajo - {$articuloNombre}";
        $message = "El artículo {$articuloNombre} tiene stock bajo: {$stockActual} unidades (mínimo: {$stockMinimo})";
        
        $data = [
            'articulo_id' => $articuloId,
            'articulo' => $articuloNombre,
            'stock_actual' => $stockActual,
            'stock_minimo' => $stockMinimo
        ];
        
        $actionUrl = '/admin'; // Inventario no tiene ruta propia aún
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_INVENTARIO,
            $title,
            $message,
            $data,
            Notification::PRIORITY_MEDIUM,
            Notification::CATEGORY_WARNING,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de error del sistema
     */
    public static function notifySystemError($error, $context = [])
    {
        $title = "Error del Sistema";
        $message = "Se ha producido un error: {$error}";
        
        $data = [
            'error' => $error,
            'context' => $context,
            'timestamp' => now()
        ];
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_SISTEMA,
            $title,
            $message,
            $data,
            Notification::PRIORITY_CRITICAL,
            Notification::CATEGORY_ERROR
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de mensaje de WhatsApp
     */
    public static function notifyWhatsAppMessage($phone, $message, $categoria)
    {
        $title = "Mensaje de WhatsApp - {$categoria}";
        $message = "Nuevo mensaje de {$phone}: " . substr($message, 0, 100) . "...";
        
        $data = [
            'phone' => $phone,
            'message' => $message,
            'categoria' => $categoria,
            'timestamp' => now()
        ];
        
        $actionUrl = route('admin.whatsapp.index');
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_WHATSAPP,
            $title,
            $message,
            $data,
            Notification::PRIORITY_MEDIUM,
            Notification::CATEGORY_INFO,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de error de integración Channex
     */
    public static function notifyChannexError($error, $context = [])
    {
        $title = "Error de Integración Channex";
        $message = "Error en sincronización con Channex: {$error}";
        
        $data = [
            'error' => $error,
            'context' => $context,
            'timestamp' => now()
        ];
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_CHANNEX,
            $title,
            $message,
            $data,
            Notification::PRIORITY_HIGH,
            Notification::CATEGORY_ERROR
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Crear notificación de reserva próxima a vencer
     */
    public static function notifyUpcomingReservation(Reserva $reserva, $daysUntil = 1)
    {
        $apartamento = $reserva->apartamento;
        $cliente = $reserva->cliente;
        
        $title = "Reserva Próxima - {$apartamento->titulo}";
        $message = "La reserva #{$reserva->codigo_reserva} vence en {$daysUntil} día(s)";
        
        $data = [
            'reserva_id' => $reserva->id,
            'codigo_reserva' => $reserva->codigo_reserva,
            'apartamento' => $apartamento->titulo,
            'cliente' => $cliente->alias,
            'fecha_entrada' => $reserva->fecha_entrada,
            'days_until' => $daysUntil
        ];
        
        $actionUrl = route('reservas.show', $reserva->id);
        
        $notifications = Notification::createForAdmins(
            Notification::TYPE_RESERVA,
            $title,
            $message,
            $data,
            Notification::PRIORITY_MEDIUM,
            Notification::CATEGORY_WARNING,
            $actionUrl
        );
        
        self::broadcastNotifications($notifications);
        
        return $notifications;
    }

    /**
     * Broadcast notificaciones en tiempo real
     */
    private static function broadcastNotifications($notifications)
    {
        if (empty($notifications)) {
            return;
        }
        
        try {
            foreach ($notifications as $notification) {
                Broadcast::event('notification.created', [
                    'notification' => $notification->load('user'),
                    'user_id' => $notification->user_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error broadcasting notifications: ' . $e->getMessage());
        }
    }

    /**
     * Marcar todas las notificaciones como leídas para un usuario
     */
    public static function markAllAsRead($userId)
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Obtener notificaciones para un usuario
     */
    public static function getForUser($userId, $limit = 20, $unreadOnly = false)
    {
        $query = Notification::where('user_id', $userId)
            ->notExpired()
            ->orderBy('created_at', 'desc');
        
        if ($unreadOnly) {
            $query->unread();
        }
        
        return $query->limit($limit)->get();
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public static function getUnreadCount($userId)
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->notExpired()
            ->count();
    }

    /**
     * Limpiar notificaciones antiguas
     */
    public static function cleanOldNotifications($days = 30)
    {
        return Notification::where('created_at', '<', now()->subDays($days))->delete();
    }
}
