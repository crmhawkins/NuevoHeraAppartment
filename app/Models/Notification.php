<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'priority',
        'category',
        'action_url',
        'expires_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    // Tipos de notificaciones
    const TYPE_RESERVA = 'reserva';
    const TYPE_INCIDENCIA = 'incidencia';
    const TYPE_LIMPIEZA = 'limpieza';
    const TYPE_FACTURACION = 'facturacion';
    const TYPE_INVENTARIO = 'inventario';
    const TYPE_SISTEMA = 'sistema';
    const TYPE_WHATSAPP = 'whatsapp';
    const TYPE_CHANNEX = 'channex';

    // Prioridades
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Categorías
    const CATEGORY_INFO = 'info';
    const CATEGORY_WARNING = 'warning';
    const CATEGORY_ERROR = 'error';
    const CATEGORY_SUCCESS = 'success';

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope para notificaciones por tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para notificaciones por prioridad
     */
    public function scopeOfPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope para notificaciones no expiradas
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Marcar como leída
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
        return $this;
    }

    /**
     * Marcar como no leída
     */
    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
        return $this;
    }

    /**
     * Verificar si está leída
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * Verificar si está expirada
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Obtener el tiempo transcurrido desde la creación
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Obtener el color de la notificación según la prioridad
     */
    public function getColorAttribute()
    {
        return match($this->priority) {
            self::PRIORITY_CRITICAL => 'danger',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_MEDIUM => 'info',
            self::PRIORITY_LOW => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Obtener el icono según el tipo
     */
    public function getIconAttribute()
    {
        return match($this->type) {
            self::TYPE_RESERVA => 'fas fa-calendar-check',
            self::TYPE_INCIDENCIA => 'fas fa-exclamation-triangle',
            self::TYPE_LIMPIEZA => 'fas fa-broom',
            self::TYPE_FACTURACION => 'fas fa-file-invoice',
            self::TYPE_INVENTARIO => 'fas fa-boxes',
            self::TYPE_SISTEMA => 'fas fa-cog',
            self::TYPE_WHATSAPP => 'fab fa-whatsapp',
            self::TYPE_CHANNEX => 'fas fa-exchange-alt',
            default => 'fas fa-bell'
        };
    }

    /**
     * Crear notificación para múltiples usuarios
     */
    public static function createForUsers($userIds, $type, $title, $message, $data = [], $priority = self::PRIORITY_MEDIUM, $category = self::CATEGORY_INFO, $actionUrl = null, $expiresAt = null)
    {
        $notifications = [];
        
        foreach ($userIds as $userId) {
            $notifications[] = self::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'priority' => $priority,
                'category' => $category,
                'action_url' => $actionUrl,
                'expires_at' => $expiresAt
            ]);
        }
        
        return $notifications;
    }

    /**
     * Crear notificación para administradores
     */
    public static function createForAdmins($type, $title, $message, $data = [], $priority = self::PRIORITY_MEDIUM, $category = self::CATEGORY_INFO, $actionUrl = null, $expiresAt = null)
    {
        $adminIds = User::where('role', 'ADMIN')->pluck('id')->toArray();
        return self::createForUsers($adminIds, $type, $title, $message, $data, $priority, $category, $actionUrl, $expiresAt);
    }

    /**
     * Crear notificación para limpiadoras
     */
    public static function createForCleaners($type, $title, $message, $data = [], $priority = self::PRIORITY_MEDIUM, $category = self::CATEGORY_INFO, $actionUrl = null, $expiresAt = null)
    {
        $cleanerIds = User::where('role', 'LIMPIEZA')->pluck('id')->toArray();
        return self::createForUsers($cleanerIds, $type, $title, $message, $data, $priority, $category, $actionUrl, $expiresAt);
    }

    /**
     * Limpiar notificaciones expiradas
     */
    public static function cleanExpired()
    {
        return self::where('expires_at', '<', now())->delete();
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public static function getStats($userId = null, $days = 7)
    {
        $query = self::where('created_at', '>=', now()->subDays($days));
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return [
            'total' => $query->count(),
            'unread' => $query->unread()->count(),
            'by_type' => $query->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_priority' => $query->selectRaw('priority, count(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
        ];
    }
}
