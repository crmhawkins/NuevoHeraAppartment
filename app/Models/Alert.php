<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'scenario',
        'title',
        'content',
        'action_url',
        'action_text',
        'is_dismissible',
        'is_read',
        'expires_at',
        'metadata'
    ];

    protected $casts = [
        'is_dismissible' => 'boolean',
        'is_read' => 'boolean',
        'expires_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Tipos de alertas disponibles
    const TYPES = [
        'info' => 'Información',
        'warning' => 'Advertencia',
        'error' => 'Error',
        'success' => 'Éxito'
    ];

    // Escenarios disponibles
    const SCENARIOS = [
        'reservation_pending' => 'Reserva Pendiente',
        'payment_due' => 'Pago Pendiente',
        'maintenance_required' => 'Mantenimiento Requerido',
        'cleaning_due' => 'Limpieza Pendiente',
        'cleaning_observation' => 'Observación de Limpieza',
        'incident_created' => 'Incidencia Creada',
        'check_in_reminder' => 'Recordatorio de Check-in',
        'check_out_reminder' => 'Recordatorio de Check-out',
        'system_notification' => 'Notificación del Sistema',
        'custom' => 'Personalizada'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
