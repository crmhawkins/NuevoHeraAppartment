# Sistema de Logs Profesional - Apartamentos Hawkins

## Descripción General

Se ha implementado un sistema de logs profesional y completo que rastrea todas las actividades de los usuarios en la plataforma. El sistema está diseñado para ser no intrusivo y no afectar la lógica de negocio existente.

## Componentes del Sistema

### 1. Trait LogsUserActivity
**Ubicación:** `app/Traits/LogsUserActivity.php`

Este trait proporciona métodos para logging de actividades de usuario y se incluye automáticamente en todos los controladores a través del `Controller` base.

#### Métodos Principales:
- `logUserActivity()` - Logging general de actividades
- `logCreate()`, `logRead()`, `logUpdate()`, `logDelete()` - Operaciones CRUD
- `logReservationAction()` - Acciones específicas de reservas
- `logApartmentAction()` - Acciones específicas de apartamentos
- `logCleaningAction()` - Acciones de limpieza
- `logInvoiceAction()` - Acciones de facturación
- `logIncidentAction()` - Gestión de incidencias
- `logSystemEvent()` - Eventos del sistema
- `logError()` - Logging de errores
- `logApiCall()` - Llamadas a APIs externas
- `logFileOperation()` - Operaciones de archivos
- `logExport()` / `logImport()` - Operaciones de exportación/importación

### 2. Middleware de Logging
**Ubicación:** `app/Http/Middleware/LogUserActivity.php`

Middleware que registra automáticamente todas las peticiones HTTP y respuestas.

#### Características:
- Registra cada petición HTTP con detalles completos
- Incluye información del usuario, IP, User-Agent
- Calcula tiempo de ejecución
- Sanitiza datos sensibles automáticamente
- Diferentes niveles de log según el código de respuesta

### 3. Middleware de Autenticación
**Ubicación:** `app/Http/Middleware/LogAuthentication.php`

Middleware especializado para eventos de autenticación.

#### Eventos Registrados:
- Intentos de login (exitosos y fallidos)
- Logout de usuarios
- Solicitudes de reset de contraseña
- Confirmaciones de reset de contraseña

### 4. Servicio de Logging
**Ubicación:** `app/Services/LoggingService.php`

Servicio especializado para operaciones de negocio específicas.

#### Métodos Especializados:
- `logReservationLifecycle()` - Ciclo de vida de reservas
- `logApartmentManagement()` - Gestión de apartamentos
- `logCleaningOperation()` - Operaciones de limpieza
- `logFinancialOperation()` - Operaciones financieras
- `logInventoryOperation()` - Gestión de inventario
- `logIncidentManagement()` - Gestión de incidencias
- `logCommunication()` - Eventos de comunicación
- `logSystemHealth()` - Salud del sistema
- `logPerformance()` - Métricas de rendimiento
- `logSecurityEvent()` - Eventos de seguridad

### 5. Controlador de Logs
**Ubicación:** `app/Http/Controllers/LogsController.php`

Interfaz web para visualizar y gestionar logs.

#### Rutas Disponibles:
- `GET /admin/logs` - Dashboard principal
- `GET /admin/logs/files` - Lista de archivos de log
- `GET /admin/logs/view/{filename}` - Ver contenido de log
- `GET /admin/logs/download/{filename}` - Descargar archivo de log
- `GET /admin/logs/search` - Buscar en logs
- `POST /admin/logs/clear` - Limpiar logs

### 6. Comandos Artisan

#### CleanOldLogs
**Comando:** `php artisan logs:clean --days=30`

Limpia archivos de log antiguos para evitar problemas de espacio en disco.

#### GenerateLogReport
**Comando:** `php artisan logs:report --days=7 --output=console`

Genera reportes detallados de actividad de logs.

## Configuración

### 1. Middleware Registrado
El middleware se ha registrado automáticamente en los grupos `web` y `api` en `app/Http/Kernel.php`.

### 2. Tareas Programadas
Se ha agregado una tarea programada para limpiar logs automáticamente:
```php
$schedule->command('logs:clean --days=30')->dailyAt('02:00');
```

### 3. Configuración de Logging
Los logs se escriben en el canal `daily` configurado en `config/logging.php`.

## Uso en Controladores

### Ejemplo Básico
```php
// En cualquier controlador que extienda de Controller
public function store(Request $request)
{
    $data = $request->validated();
    $apartamento = Apartamento::create($data);
    
    // Log de la creación
    $this->logCreate('APARTAMENTO', $apartamento->id, $data);
    
    return redirect()->back()->with('success', 'Apartamento creado');
}
```

### Ejemplo Avanzado
```php
public function update(Request $request, $id)
{
    $apartamento = Apartamento::findOrFail($id);
    $oldData = $apartamento->toArray();
    
    $data = $request->validated();
    $apartamento->update($data);
    
    // Log detallado de la actualización
    $this->logUpdate('APARTAMENTO', $id, $oldData, $data);
    
    return redirect()->back()->with('success', 'Apartamento actualizado');
}
```

### Logging de Errores
```php
try {
    // Operación que puede fallar
    $this->processPayment($data);
} catch (\Exception $e) {
    // Log del error
    $this->logError('Error en procesamiento de pago', [
        'payment_data' => $data,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    throw $e;
}
```

## Estructura de los Logs

### Formato de Log
```json
{
    "type": "User Activity",
    "user_id": 123,
    "user_name": "Juan Pérez",
    "user_email": "juan@example.com",
    "user_role": "ADMIN",
    "action": "CREATE",
    "resource": "APARTAMENTO",
    "resource_id": 456,
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "url": "https://app.example.com/admin/apartamentos",
    "method": "POST",
    "timestamp": "2024-01-15T10:30:00.000Z",
    "additional_data": {
        "created_data": {
            "titulo": "Apartamento 2A",
            "edificio_id": 1
        }
    }
}
```

### Niveles de Log
- **INFO**: Operaciones normales, consultas, navegación
- **WARNING**: Eventos que requieren atención (login fallidos, etc.)
- **ERROR**: Errores del sistema, excepciones
- **DEBUG**: Información detallada para debugging

## Seguridad y Privacidad

### Datos Sensibles Sanitizados
El sistema automáticamente sanitiza los siguientes campos:
- `password`, `password_confirmation`
- `token`, `api_key`, `secret`
- `dni`, `telefono`, `email`
- `credit_card`, `bank_account`
- `ssn`, `social_security_number`
- `pin`, `otp`

### Retención de Datos
- Los logs se mantienen por 30 días por defecto
- Se pueden limpiar manualmente o automáticamente
- Los logs antiguos se pueden archivar antes de eliminar

## Monitoreo y Alertas

### Métricas Disponibles
- Total de peticiones por período
- Tasa de errores
- Usuarios únicos activos
- Rutas más accedidas
- Errores más frecuentes
- Distribución horaria de actividad
- Métricas de rendimiento

### Dashboard Web
Accede a `/admin/logs` para ver:
- Estadísticas en tiempo real
- Búsqueda avanzada en logs
- Visualización de archivos de log
- Descarga de logs para análisis

## Mantenimiento

### Limpieza Automática
```bash
# Limpiar logs de más de 30 días
php artisan logs:clean --days=30

# Limpiar logs de más de 7 días
php artisan logs:clean --days=7
```

### Generación de Reportes
```bash
# Reporte de 7 días en consola
php artisan logs:report --days=7

# Reporte de 30 días guardado en archivo
php artisan logs:report --days=30 --output=file
```

### Verificación del Sistema
```bash
# Verificar que los logs se están generando
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Verificar comandos disponibles
php artisan list | grep logs
```

## Integración con Log Viewer

El sistema está diseñado para trabajar con el paquete `opcodesio/log-viewer` ya instalado. Los logs se pueden visualizar a través de la interfaz web en `/admin/logs` o usando el log viewer del paquete.

## Consideraciones de Rendimiento

- El logging es asíncrono y no bloquea las operaciones principales
- Los datos sensibles se sanitizan antes de escribir
- Los logs se escriben en archivos diarios para facilitar la rotación
- El middleware tiene un impacto mínimo en el rendimiento

## Troubleshooting

### Si los logs no se generan:
1. Verificar permisos en `storage/logs/`
2. Verificar configuración en `config/logging.php`
3. Verificar que el middleware esté registrado correctamente

### Si hay errores de sintaxis:
1. Ejecutar `php artisan config:clear`
2. Ejecutar `php artisan cache:clear`
3. Verificar que todos los archivos estén correctamente guardados

### Para debugging:
1. Usar `php artisan logs:report` para ver estadísticas
2. Revisar logs en `storage/logs/laravel-YYYY-MM-DD.log`
3. Usar la interfaz web en `/admin/logs` para búsquedas avanzadas
