<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

trait LogsUserActivity
{
    /**
     * Log user activity with detailed information
     *
     * @param string $action
     * @param string $resource
     * @param mixed $resourceId
     * @param array $additionalData
     * @param string $level
     */
    protected function logUserActivity(
        string $action,
        string $resource,
        $resourceId = null,
        array $additionalData = [],
        string $level = 'info'
    ): void {
        $user = Auth::user();
        $request = request();
        
        $logData = [
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'Guest',
            'user_email' => $user ? $user->email : null,
            'user_role' => $user ? $user->role : null,
            'action' => $action,
            'resource' => $resource,
            'resource_id' => $resourceId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'additional_data' => $additionalData
        ];

        // Log with appropriate level
        Log::channel('daily')->$level('User Activity', $logData);
    }

    /**
     * Log CRUD operations
     */
    protected function logCreate(string $resource, $resourceId, array $data = []): void
    {
        $this->logUserActivity('CREATE', $resource, $resourceId, [
            'created_data' => $this->sanitizeData($data)
        ]);
    }

    protected function logRead(string $resource, $resourceId = null, array $filters = []): void
    {
        $this->logUserActivity('READ', $resource, $resourceId, [
            'filters' => $filters
        ]);
    }

    protected function logUpdate(string $resource, $resourceId, array $oldData = [], array $newData = []): void
    {
        $this->logUserActivity('UPDATE', $resource, $resourceId, [
            'old_data' => $this->sanitizeData($oldData),
            'new_data' => $this->sanitizeData($newData),
            'changes' => $this->getChanges($oldData, $newData)
        ]);
    }

    protected function logDelete(string $resource, $resourceId, array $data = []): void
    {
        $this->logUserActivity('DELETE', $resource, $resourceId, [
            'deleted_data' => $this->sanitizeData($data)
        ]);
    }

    /**
     * Log authentication events
     */
    protected function logLogin(): void
    {
        $this->logUserActivity('LOGIN', 'AUTHENTICATION', null, [], 'info');
    }

    protected function logLogout(): void
    {
        $this->logUserActivity('LOGOUT', 'AUTHENTICATION', null, [], 'info');
    }

    protected function logLoginFailed(string $email, string $reason = 'Invalid credentials'): void
    {
        $this->logUserActivity('LOGIN_FAILED', 'AUTHENTICATION', null, [
            'attempted_email' => $email,
            'reason' => $reason
        ], 'warning');
    }

    /**
     * Log business operations
     */
    protected function logReservationAction(string $action, $reservaId, array $data = []): void
    {
        $this->logUserActivity($action, 'RESERVA', $reservaId, $data);
    }

    protected function logApartmentAction(string $action, $apartamentoId, array $data = []): void
    {
        $this->logUserActivity($action, 'APARTAMENTO', $apartamentoId, $data);
    }

    protected function logClientAction(string $action, $clienteId, array $data = []): void
    {
        $this->logUserActivity($action, 'CLIENTE', $clienteId, $data);
    }

    protected function logCleaningAction(string $action, $limpiezaId, array $data = []): void
    {
        $this->logUserActivity($action, 'LIMPIEZA', $limpiezaId, $data);
    }

    protected function logInvoiceAction(string $action, $facturaId, array $data = []): void
    {
        $this->logUserActivity($action, 'FACTURA', $facturaId, $data);
    }

    protected function logInventoryAction(string $action, $inventarioId, array $data = []): void
    {
        $this->logUserActivity($action, 'INVENTARIO', $inventarioId, $data);
    }

    protected function logIncidentAction(string $action, $incidenciaId, array $data = []): void
    {
        $this->logUserActivity($action, 'INCIDENCIA', $incidenciaId, $data);
    }

    /**
     * Log system events
     */
    protected function logSystemEvent(string $event, array $data = []): void
    {
        $this->logUserActivity('SYSTEM_EVENT', 'SYSTEM', null, $data, 'info');
    }

    protected function logError(string $error, array $context = []): void
    {
        $this->logUserActivity('ERROR', 'SYSTEM', null, [
            'error' => $error,
            'context' => $context
        ], 'error');
    }

    /**
     * Log API calls
     */
    protected function logApiCall(string $endpoint, string $method, array $data = [], int $statusCode = null): void
    {
        $this->logUserActivity('API_CALL', 'EXTERNAL_API', null, [
            'endpoint' => $endpoint,
            'method' => $method,
            'request_data' => $this->sanitizeData($data),
            'status_code' => $statusCode
        ]);
    }

    /**
     * Log file operations
     */
    protected function logFileOperation(string $operation, string $filename, array $data = []): void
    {
        $this->logUserActivity('FILE_' . strtoupper($operation), 'FILE', null, [
            'filename' => $filename,
            'additional_data' => $data
        ]);
    }

    /**
     * Log export operations
     */
    protected function logExport(string $type, array $filters = [], int $recordCount = null): void
    {
        $this->logUserActivity('EXPORT', 'EXPORT', null, [
            'export_type' => $type,
            'filters' => $filters,
            'record_count' => $recordCount
        ]);
    }

    /**
     * Log import operations
     */
    protected function logImport(string $type, int $recordCount = null, array $errors = []): void
    {
        $this->logUserActivity('IMPORT', 'IMPORT', null, [
            'import_type' => $type,
            'record_count' => $recordCount,
            'errors' => $errors
        ]);
    }

    /**
     * Sanitize sensitive data before logging
     */
    private function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'token', 'api_key', 'secret',
            'dni', 'telefono', 'email', 'credit_card', 'bank_account',
            'ssn', 'social_security_number', 'pin', 'otp'
        ];

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get changes between old and new data
     */
    private function getChanges(array $oldData, array $newData): array
    {
        $changes = [];
        
        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }
}
