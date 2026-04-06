<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LoggingService
{
    /**
     * Log business operations with context
     */
    public function logBusinessOperation(
        string $operation,
        string $entity,
        $entityId = null,
        array $context = [],
        string $level = 'info'
    ): void {
        $user = Auth::user();
        
        $logData = [
            'type' => 'BUSINESS_OPERATION',
            'operation' => $operation,
            'entity' => $entity,
            'entity_id' => $entityId,
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'System',
            'user_role' => $user ? $user->role : null,
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];

        Log::channel('daily')->$level('Business Operation', $logData);
    }

    /**
     * Log reservation lifecycle events
     */
    public function logReservationLifecycle(string $event, $reservaId, array $data = []): void
    {
        $this->logBusinessOperation(
            'RESERVATION_' . strtoupper($event),
            'RESERVA',
            $reservaId,
            $data
        );
    }

    /**
     * Log apartment management events
     */
    public function logApartmentManagement(string $action, $apartamentoId, array $data = []): void
    {
        $this->logBusinessOperation(
            'APARTMENT_' . strtoupper($action),
            'APARTAMENTO',
            $apartamentoId,
            $data
        );
    }

    /**
     * Log cleaning operations
     */
    public function logCleaningOperation(string $action, $limpiezaId, array $data = []): void
    {
        $this->logBusinessOperation(
            'CLEANING_' . strtoupper($action),
            'LIMPIEZA',
            $limpiezaId,
            $data
        );
    }

    /**
     * Log financial operations
     */
    public function logFinancialOperation(string $operation, $entityId, array $data = []): void
    {
        $this->logBusinessOperation(
            'FINANCIAL_' . strtoupper($operation),
            'FINANCIAL',
            $entityId,
            $data
        );
    }

    /**
     * Log inventory operations
     */
    public function logInventoryOperation(string $action, $inventarioId, array $data = []): void
    {
        $this->logBusinessOperation(
            'INVENTORY_' . strtoupper($action),
            'INVENTARIO',
            $inventarioId,
            $data
        );
    }

    /**
     * Log incident management
     */
    public function logIncidentManagement(string $action, $incidenciaId, array $data = []): void
    {
        $this->logBusinessOperation(
            'INCIDENT_' . strtoupper($action),
            'INCIDENCIA',
            $incidenciaId,
            $data
        );
    }

    /**
     * Log communication events
     */
    public function logCommunication(string $channel, string $action, array $data = []): void
    {
        $this->logBusinessOperation(
            'COMMUNICATION_' . strtoupper($action),
            'COMMUNICATION',
            null,
            array_merge($data, ['channel' => $channel])
        );
    }

    /**
     * Log system health events
     */
    public function logSystemHealth(string $component, string $status, array $data = []): void
    {
        $level = $status === 'ERROR' ? 'error' : 'info';
        
        $this->logBusinessOperation(
            'SYSTEM_HEALTH',
            'SYSTEM',
            null,
            array_merge($data, [
                'component' => $component,
                'status' => $status
            ]),
            $level
        );
    }

    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $executionTime, array $metrics = []): void
    {
        $this->logBusinessOperation(
            'PERFORMANCE',
            'SYSTEM',
            null,
            array_merge($metrics, [
                'operation' => $operation,
                'execution_time_ms' => $executionTime
            ])
        );
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, array $data = []): void
    {
        $this->logBusinessOperation(
            'SECURITY_' . strtoupper($event),
            'SECURITY',
            null,
            $data,
            'warning'
        );
    }

    /**
     * Log data export/import operations
     */
    public function logDataOperation(string $operation, string $type, int $recordCount = null, array $data = []): void
    {
        $this->logBusinessOperation(
            'DATA_' . strtoupper($operation),
            'DATA',
            null,
            array_merge($data, [
                'operation_type' => $type,
                'record_count' => $recordCount
            ])
        );
    }

    /**
     * Log API integrations
     */
    public function logApiIntegration(string $service, string $action, array $data = []): void
    {
        $this->logBusinessOperation(
            'API_INTEGRATION',
            'EXTERNAL_API',
            null,
            array_merge($data, [
                'service' => $service,
                'action' => $action
            ])
        );
    }

    /**
     * Log user session events
     */
    public function logUserSession(string $event, array $data = []): void
    {
        $this->logBusinessOperation(
            'USER_SESSION_' . strtoupper($event),
            'USER_SESSION',
            null,
            $data
        );
    }

    /**
     * Log maintenance operations
     */
    public function logMaintenance(string $operation, array $data = []): void
    {
        $this->logBusinessOperation(
            'MAINTENANCE_' . strtoupper($operation),
            'MAINTENANCE',
            null,
            $data
        );
    }

    /**
     * Get log statistics for dashboard
     */
    public function getLogStatistics(int $days = 7): array
    {
        $logPath = storage_path('logs');
        $files = glob($logPath . '/laravel-*.log');
        
        $stats = [
            'total_requests' => 0,
            'errors' => 0,
            'warnings' => 0,
            'unique_users' => 0,
            'most_active_users' => [],
            'most_accessed_routes' => [],
            'error_rate' => 0
        ];
        
        // This is a simplified version - in production you might want to use a proper log analysis tool
        foreach ($files as $file) {
            if (filemtime($file) >= now()->subDays($days)->timestamp) {
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                
                foreach ($lines as $line) {
                    if (strpos($line, 'HTTP Request') !== false) {
                        $stats['total_requests']++;
                    } elseif (strpos($line, 'ERROR') !== false) {
                        $stats['errors']++;
                    } elseif (strpos($line, 'WARNING') !== false) {
                        $stats['warnings']++;
                    }
                }
            }
        }
        
        if ($stats['total_requests'] > 0) {
            $stats['error_rate'] = round(($stats['errors'] / $stats['total_requests']) * 100, 2);
        }
        
        return $stats;
    }
}
