<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;

class TestAlertSystem extends Command
{
    protected $signature = 'alerts:test {type=info} {--user=1}';
    protected $description = 'Probar el sistema de alertas';

    public function handle()
    {
        $type = $this->argument('type');
        $userId = $this->option('user');

        switch ($type) {
            case 'cleaning':
                AlertService::createCleaningObservationAlert(
                    1, 
                    'Apartamento de Prueba', 
                    'Esta es una observación de prueba para verificar el sistema de alertas.'
                );
                $this->info('Alerta de limpieza creada correctamente.');
                break;
                
            case 'info':
                AlertService::createForUser($userId, [
                    'type' => 'info',
                    'scenario' => 'system_notification',
                    'title' => 'Prueba del Sistema',
                    'content' => 'Esta es una alerta de prueba del sistema.',
                    'is_dismissible' => true
                ]);
                $this->info('Alerta de información creada correctamente.');
                break;
                
            case 'warning':
                AlertService::createForUser($userId, [
                    'type' => 'warning',
                    'scenario' => 'maintenance_required',
                    'title' => 'Mantenimiento Requerido',
                    'content' => 'Se requiere mantenimiento en un apartamento.',
                    'action_url' => '/admin/apartamentos',
                    'action_text' => 'Ver Apartamentos',
                    'is_dismissible' => true
                ]);
                $this->info('Alerta de advertencia creada correctamente.');
                break;
                
            case 'error':
                AlertService::createForUser($userId, [
                    'type' => 'error',
                    'scenario' => 'payment_due',
                    'title' => 'Pago Pendiente',
                    'content' => 'Hay un pago pendiente que requiere atención inmediata.',
                    'action_url' => '/admin/reservas',
                    'action_text' => 'Gestionar Pagos',
                    'is_dismissible' => false
                ]);
                $this->info('Alerta de error creada correctamente.');
                break;
                
            default:
                $this->error('Tipo de alerta no válido. Usa: info, warning, error, cleaning');
        }
    }
}
