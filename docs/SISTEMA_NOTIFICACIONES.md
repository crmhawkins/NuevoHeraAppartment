# Sistema de Notificaciones en Tiempo Real

## Descripción General

El sistema de notificaciones implementado proporciona notificaciones en tiempo real para todas las acciones críticas del sistema de gestión de apartamentos Hawkins. Utiliza **Pusher** como servicio de broadcasting gratuito (hasta 200,000 mensajes/mes) y **Laravel Broadcasting** para la integración.

## Características Principales

### 🚨 **Tipos de Notificaciones**

1. **RESERVAS**
   - Nueva reserva creada
   - Reserva actualizada
   - Reserva cancelada
   - Check-in realizado
   - Check-out realizado
   - Reserva próxima a vencer

2. **INCIDENCIAS**
   - Nueva incidencia reportada
   - Incidencia resuelta
   - Cambio de prioridad

3. **LIMPIEZA**
   - Apartamento listo para limpieza
   - Limpieza completada
   - Problemas en limpieza

4. **FACTURACIÓN**
   - Nueva factura generada
   - Factura pagada
   - Factura vencida

5. **INVENTARIO**
   - Stock bajo de artículos
   - Artículos agotados

6. **SISTEMA**
   - Errores críticos
   - Fallos en integraciones
   - Eventos de seguridad

7. **WHATSAPP**
   - Mensajes de averías
   - Solicitudes de limpieza
   - Mensajes generales

8. **CHANNEX**
   - Errores de sincronización
   - Actualizaciones de disponibilidad

### 🎯 **Prioridades**

- **CRITICAL**: Requiere atención inmediata
- **HIGH**: Importante, revisar pronto
- **MEDIUM**: Normal, revisar cuando sea posible
- **LOW**: Informativo

### 📊 **Categorías**

- **INFO**: Información general
- **WARNING**: Advertencia
- **ERROR**: Error del sistema
- **SUCCESS**: Operación exitosa

## Arquitectura del Sistema

### Componentes Principales

1. **Modelo Notification** (`app/Models/Notification.php`)
   - Almacena todas las notificaciones
   - Relaciones con usuarios
   - Métodos de utilidad

2. **Servicio NotificationService** (`app/Services/NotificationService.php`)
   - Lógica de negocio para crear notificaciones
   - Métodos específicos por tipo de acción
   - Broadcasting automático

3. **Controlador NotificationController** (`app/Http/Controllers/NotificationController.php`)
   - API REST para gestionar notificaciones
   - Endpoints para CRUD y estadísticas

4. **Evento NotificationCreated** (`app/Events/NotificationCreated.php`)
   - Evento de broadcasting para tiempo real
   - Configuración de canales

5. **Componente Frontend** (`resources/views/components/notification-bell.blade.php`)
   - Campana de notificaciones
   - Interfaz de usuario
   - WebSocket integration

## Configuración

### Variables de Entorno

```env
# Broadcasting
BROADCAST_DRIVER=pusher

# Pusher Configuration
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

### Instalación de Dependencias

```bash
# Instalar Pusher JS
npm install pusher-js

# O usar CDN
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
```

### Migración de Base de Datos

```bash
php artisan migrate
```

## Uso del Sistema

### Crear Notificaciones

```php
use App\Services\NotificationService;

// Notificación de nueva reserva
NotificationService::notifyNewReservation($reserva);

// Notificación de incidencia
NotificationService::notifyNewIncident($incidencia);

// Notificación personalizada
Notification::createForAdmins(
    'reserva',
    'Título de la notificación',
    'Mensaje descriptivo',
    ['data' => 'adicional'],
    'high',
    'warning',
    '/admin/reservas/123'
);
```

### Integración en Controladores

```php
// En el método store
public function store(Request $request)
{
    $reserva = Reserva::create($request->all());
    
    // Log de la acción
    $this->logCreate('RESERVA', $reserva->id, $reserva->toArray());
    
    // Crear notificación
    NotificationService::notifyNewReservation($reserva);
    
    return redirect()->route('reservas.index');
}
```

### Frontend - Campana de Notificaciones

```blade
<!-- Incluir en el layout principal -->
@include('components.notification-bell')

<!-- O usar como componente -->
<x-notification-bell />
```

### API Endpoints

```javascript
// Obtener notificaciones
GET /api/notifications

// Marcar como leída
POST /api/notifications/{id}/read

// Marcar todas como leídas
POST /api/notifications/mark-all-read

// Obtener contador
GET /api/notifications/unread-count

// Estadísticas
GET /api/notifications/stats
```

## Características Avanzadas

### WebSocket en Tiempo Real

- **Canal Privado**: `private-notifications.{user_id}`
- **Canal Admin**: `notifications.admin`
- **Evento**: `notification.created`

### Filtros y Búsqueda

- Por tipo de notificación
- Por prioridad
- Por estado (leída/no leída)
- Búsqueda de texto
- Filtros por fecha

### Gestión Automática

- **Limpieza automática**: Notificaciones antiguas se eliminan automáticamente
- **Expiración**: Notificaciones pueden tener fecha de expiración
- **Retención**: 30 días por defecto (configurable)

### Comandos Artisan

```bash
# Limpiar notificaciones antiguas
php artisan notifications:clean --days=30

# Generar reporte de logs
php artisan logs:report --days=7
```

## Seguridad

### Protección de Datos

- **Sanitización**: Datos sensibles se filtran automáticamente
- **Autenticación**: Solo usuarios autenticados pueden ver sus notificaciones
- **Autorización**: Notificaciones privadas por usuario
- **CSRF**: Protección contra ataques CSRF

### Auditoría

- **Logs completos**: Todas las acciones se registran
- **Trazabilidad**: Seguimiento de quién hizo qué y cuándo
- **Retención**: Logs se mantienen por 30 días

## Monitoreo y Mantenimiento

### Métricas Disponibles

- Total de notificaciones
- Notificaciones no leídas
- Notificaciones por tipo
- Notificaciones por prioridad
- Actividad por usuario

### Tareas Programadas

```php
// En app/Console/Kernel.php
$schedule->command('notifications:clean --days=30')->dailyAt('03:00');
$schedule->command('logs:clean --days=30')->dailyAt('02:00');
```

### Logs del Sistema

- **Canal**: `daily`
- **Ubicación**: `storage/logs/`
- **Rotación**: Diaria
- **Retención**: 30 días

## Personalización

### Agregar Nuevos Tipos

1. **Definir constante en el modelo**:
```php
const TYPE_NUEVO_TIPO = 'nuevo_tipo';
```

2. **Crear método en NotificationService**:
```php
public static function notifyNuevoTipo($data)
{
    // Lógica de notificación
}
```

3. **Integrar en controlador**:
```php
NotificationService::notifyNuevoTipo($data);
```

### Personalizar Interfaz

- **Estilos**: Modificar CSS en el componente
- **Iconos**: Cambiar iconos por tipo
- **Colores**: Ajustar colores por prioridad
- **Sonidos**: Agregar notificaciones de audio

## Troubleshooting

### Problemas Comunes

1. **Notificaciones no aparecen**:
   - Verificar configuración de Pusher
   - Comprobar autenticación del usuario
   - Revisar logs del navegador

2. **WebSocket no conecta**:
   - Verificar variables de entorno
   - Comprobar firewall/proxy
   - Revisar configuración de Pusher

3. **Notificaciones duplicadas**:
   - Verificar que no se llame el servicio múltiples veces
   - Revisar lógica de controladores

### Logs de Debug

```bash
# Ver logs de broadcasting
tail -f storage/logs/laravel.log | grep "broadcasting"

# Ver logs de notificaciones
tail -f storage/logs/laravel.log | grep "notification"
```

## Rendimiento

### Optimizaciones

- **Índices de base de datos**: Optimizados para consultas frecuentes
- **Paginación**: Notificaciones se cargan por páginas
- **Caché**: Contador de notificaciones se cachea
- **Limpieza automática**: Evita acumulación excesiva

### Límites

- **Pusher**: 200,000 mensajes/mes (gratuito)
- **Base de datos**: Sin límite específico
- **Memoria**: Optimizado para grandes volúmenes

## Futuras Mejoras

### Funcionalidades Planificadas

1. **Notificaciones por email**: Envío automático de emails
2. **Notificaciones push**: Para dispositivos móviles
3. **Templates personalizables**: Plantillas de notificaciones
4. **Agrupación**: Agrupar notificaciones similares
5. **Programación**: Notificaciones programadas
6. **Integración con Slack**: Notificaciones en Slack
7. **Dashboard avanzado**: Panel de control más completo

### Escalabilidad

- **Redis**: Para mejor rendimiento en producción
- **Queue**: Procesamiento asíncrono de notificaciones
- **Microservicios**: Separación de responsabilidades
- **CDN**: Para assets estáticos

## Conclusión

El sistema de notificaciones implementado proporciona una solución completa y profesional para el seguimiento de todas las acciones críticas en la plataforma de apartamentos Hawkins. Es escalable, seguro y fácil de mantener, con características avanzadas como notificaciones en tiempo real, gestión automática y auditoría completa.

La integración con el sistema de logs existente asegura que todas las acciones queden registradas tanto para notificaciones como para auditoría, proporcionando una visión completa de la actividad del sistema.
