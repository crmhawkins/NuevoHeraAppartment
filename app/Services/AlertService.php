<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AlertService
{
    /**
     * Crear una nueva alerta
     */
    public static function create($data)
    {
        $alert = Alert::create(array_merge($data, [
            'user_id' => $data['user_id'] ?? Auth::id()
        ]));

        return $alert;
    }

    /**
     * Crear alerta para un usuario específico
     */
    public static function createForUser($userId, $data)
    {
        return self::create(array_merge($data, ['user_id' => $userId]));
    }

    /**
     * Crear alerta para todos los usuarios con un rol específico
     */
    public static function createForRole($role, $data)
    {
        $users = User::where('role', $role)->get();
        
        foreach ($users as $user) {
            self::createForUser($user->id, $data);
        }
    }

    /**
     * Crear alerta para todos los usuarios
     */
    public static function createForAll($data)
    {
        $users = User::all();
        
        foreach ($users as $user) {
            self::createForUser($user->id, $data);
        }
    }

    /**
     * Obtener alertas no leídas del usuario actual
     */
    public static function getUnreadAlerts($userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        return Alert::forUser($userId)
                   ->unread()
                   ->active()
                   ->orderBy('created_at', 'desc')
                   ->get();
    }

    /**
     * Marcar alerta como leída
     */
    public static function markAsRead($alertId, $userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        $alert = Alert::where('id', $alertId)
                     ->where('user_id', $userId)
                     ->first();
        
        if ($alert) {
            $alert->markAsRead();
            return true;
        }
        
        return false;
    }

    /**
     * Ejemplos de alertas predefinidas
     */
    public static function createReservationPendingAlert($reservationId, $userId)
    {
        return self::createForUser($userId, [
            'type' => 'warning',
            'scenario' => 'reservation_pending',
            'title' => 'Reserva Pendiente de Confirmación',
            'content' => 'Tienes una reserva pendiente que requiere tu atención.',
            'action_url' => "/admin/reservas/{$reservationId}/edit",
            'action_text' => 'Ver Reserva',
            'metadata' => ['reservation_id' => $reservationId]
        ]);
    }

    public static function createPaymentDueAlert($reservationId, $userId)
    {
        return self::createForUser($userId, [
            'type' => 'error',
            'scenario' => 'payment_due',
            'title' => 'Pago Pendiente',
            'content' => 'Hay un pago pendiente que requiere atención inmediata.',
            'action_url' => "/admin/reservas/{$reservationId}/payment",
            'action_text' => 'Gestionar Pago',
            'metadata' => ['reservation_id' => $reservationId]
        ]);
    }

    public static function createMaintenanceAlert($apartamentoId, $userId)
    {
        return self::createForUser($userId, [
            'type' => 'warning',
            'scenario' => 'maintenance_required',
            'title' => 'Mantenimiento Requerido',
            'content' => 'Se ha reportado un problema de mantenimiento en un apartamento.',
            'action_url' => "/admin/apartamentos/{$apartamentoId}/maintenance",
            'action_text' => 'Ver Detalles',
            'metadata' => ['apartamento_id' => $apartamentoId]
        ]);
    }

    /**
     * Crear alerta para observaciones de limpieza
     */
    public static function createCleaningObservationAlert($limpiezaId, $apartamentoNombre, $observacion)
    {
        // Obtener todos los usuarios con rol ADMIN
        $adminUsers = User::where('role', 'ADMIN')->get();
        
        foreach ($adminUsers as $adminUser) {
            self::createForUser($adminUser->id, [
                'type' => 'warning',
                'scenario' => 'cleaning_observation',
                'title' => 'Observación en Limpieza',
                'content' => "Se ha añadido una observación en la limpieza del apartamento {$apartamentoNombre}: {$observacion}",
                'action_url' => "/aparatamento-limpieza/{$limpiezaId}/show",
                'action_text' => 'Ver Limpieza',
                'is_dismissible' => true,
                'metadata' => [
                    'limpieza_id' => $limpiezaId,
                    'apartamento_nombre' => $apartamentoNombre,
                    'observacion' => $observacion
                ]
            ]);
        }
    }

    /**
     * Crear alerta para incidencias creadas por limpiadoras
     */
    public static function createIncidentAlert($incidenciaId, $titulo, $tipo, $elementoNombre, $prioridad, $empleadaNombre)
    {
        // Obtener todos los usuarios con rol ADMIN
        $adminUsers = User::where('role', 'ADMIN')->get();
        
        foreach ($adminUsers as $adminUser) {
            self::createForUser($adminUser->id, [
                'type' => $prioridad === 'urgente' ? 'error' : ($prioridad === 'alta' ? 'warning' : 'info'),
                'scenario' => 'incident_created',
                'title' => 'Nueva Incidencia Reportada',
                'content' => "La limpiadora {$empleadaNombre} ha reportado una incidencia en {$tipo} '{$elementoNombre}': {$titulo}",
                'action_url' => "/admin/incidencias/{$incidenciaId}",
                'action_text' => 'Ver Incidencia',
                'is_dismissible' => true,
                'metadata' => [
                    'incidencia_id' => $incidenciaId,
                    'titulo' => $titulo,
                    'tipo' => $tipo,
                    'elemento_nombre' => $elementoNombre,
                    'prioridad' => $prioridad,
                    'empleada_nombre' => $empleadaNombre
                ]
            ]);
        }
    }
}
