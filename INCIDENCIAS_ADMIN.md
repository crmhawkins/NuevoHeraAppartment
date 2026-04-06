# Sistema de Gestión de Incidencias - Panel de Administración

## Descripción General

El sistema de gestión de incidencias permite a los administradores gestionar y resolver incidencias reportadas por el personal de limpieza y mantenimiento. Las incidencias pueden estar relacionadas con apartamentos o zonas comunes.

## Características Principales

### 📊 Dashboard
- **Widget de Incidencias**: Muestra el número de incidencias pendientes en tiempo real
- **Acceso Directo**: Clic en la tarjeta para ir a la gestión de incidencias
- **Indicadores Visuales**: Badges de colores según el estado

### 🔍 Gestión de Incidencias

#### Listado Principal (`/admin/incidencias`)
- **Filtros Avanzados**:
  - Estado (pendiente, en proceso, resuelta, cerrada)
  - Prioridad (baja, media, alta, urgente)
  - Tipo (apartamento, zona común)
  - Empleada que reportó
  - Rango de fechas
  - Solo incidencias de hoy

- **Estadísticas en Tiempo Real**:
  - Total de incidencias
  - Incidencias pendientes
  - Incidencias urgentes
  - Incidencias de hoy
  - Incidencias resueltas hoy

- **Acciones Rápidas**:
  - Ver detalles
  - Editar incidencia
  - Marcar como resuelta (modal)

#### Detalles de Incidencia (`/admin/incidencias/{id}`)
- **Información Completa**:
  - Título y descripción
  - Estado y prioridad
  - Tipo y elemento asociado
  - Fotos de la incidencia
  - Información de la empleada
  - Historial de cambios

- **Acciones Rápidas**:
  - Cambiar estado
  - Marcar como resuelta
  - Editar incidencia

#### Edición de Incidencia (`/admin/incidencias/{id}/edit`)
- **Campos Editables**:
  - Título y descripción
  - Estado y prioridad
  - Tipo de elemento
  - Apartamento o zona común
  - Observaciones del administrador
  - Empleada asignada
  - Limpieza relacionada

## Estados de Incidencias

1. **Pendiente** 🔴: Incidencia reportada, esperando atención
2. **En Proceso** 🔵: Incidencia siendo atendida
3. **Resuelta** 🟢: Incidencia solucionada
4. **Cerrada** ⚫: Incidencia finalizada completamente

## Prioridades

1. **Urgente** 🔴: Requiere atención inmediata
2. **Alta** 🟠: Requiere atención prioritaria
3. **Media** 🟡: Atención normal
4. **Baja** 🟢: Puede esperar

## Flujo de Trabajo

### 1. Reporte de Incidencia
- El personal reporta una incidencia desde su panel
- Se asigna automáticamente estado "pendiente"
- Se notifica a los administradores

### 2. Gestión Administrativa
- El administrador revisa la incidencia
- Puede cambiar el estado a "en proceso"
- Puede ajustar la prioridad según necesidad
- Agrega observaciones internas

### 3. Resolución
- El administrador marca la incidencia como "resuelta"
- Debe proporcionar descripción de la solución
- Se registra fecha y administrador que resuelve

### 4. Cierre
- La incidencia puede marcarse como "cerrada"
- Se mantiene el historial completo

## Acceso al Sistema

### Menú Principal
- **Ubicación**: Limpieza → Gestión de Incidencias
- **Ruta**: `/admin/incidencias`
- **Permisos**: Solo usuarios con rol ADMIN

### Dashboard
- **Tarjeta de Incidencias**: Acceso directo desde el dashboard principal
- **Contador en Tiempo Real**: Número de incidencias pendientes

## Funcionalidades Técnicas

### API Endpoints
- `GET /admin/incidencias` - Listado principal
- `GET /admin/incidencias/{id}` - Ver detalles
- `GET /admin/incidencias/{id}/edit` - Formulario de edición
- `PUT /admin/incidencias/{id}` - Actualizar incidencia
- `POST /admin/incidencias/{id}/resolver` - Marcar como resuelta
- `GET /admin/incidencias-pendientes` - API para dashboard

### Relaciones del Modelo
- **Apartamento**: Relación opcional con apartamento específico
- **Zona Común**: Relación opcional con zona común
- **Empleada**: Quien reporta la incidencia
- **Admin Resuelve**: Administrador que resuelve
- **Limpieza**: Relación opcional con tarea de limpieza

## Notificaciones

### Dashboard
- Badge rojo con número de incidencias pendientes
- Cambio de color según estado (rojo = pendientes, verde = sin pendientes)

### Mensajes del Sistema
- Confirmaciones de acciones exitosas
- Errores de validación
- Notificaciones de cambios de estado

## Filtros y Búsquedas

### Filtros Disponibles
- **Estado**: Filtrar por estado actual
- **Prioridad**: Filtrar por nivel de urgencia
- **Tipo**: Apartamento o zona común
- **Empleada**: Filtrar por quien reportó
- **Fechas**: Rango personalizable
- **Hoy**: Solo incidencias del día actual

### Ordenamiento
- **Prioridad**: Urgente → Alta → Media → Baja
- **Fecha**: Más recientes primero
- **Estado**: Pendientes primero

## Mantenimiento

### Archivos del Sistema
- **Controlador**: `app/Http/Controllers/Admin/AdminIncidenciasController.php`
- **Modelo**: `app/Models/Incidencia.php`
- **Vistas**: `resources/views/admin/incidencias/`
- **Rutas**: `routes/web.php` (líneas 139-142)

### Dependencias
- Laravel 8+
- Bootstrap 5
- Font Awesome 6
- jQuery (para funcionalidades AJAX)

## Solución de Problemas

### Problemas Comunes
1. **No se cargan las incidencias**: Verificar permisos de usuario
2. **Error en filtros**: Verificar que los parámetros sean válidos
3. **No se actualiza el contador**: Verificar la ruta `/admin/incidencias-pendientes`

### Logs
- Los errores se registran en `storage/logs/laravel.log`
- Verificar permisos de archivos y directorios
- Comprobar que las migraciones se hayan ejecutado

## Mejoras Futuras

### Funcionalidades Planificadas
- Notificaciones por email
- Sistema de tickets
- Reportes automáticos
- Integración con calendario
- Historial de cambios detallado
- Adjuntar archivos adicionales
- Comentarios y seguimiento

### Optimizaciones
- Paginación infinita
- Búsqueda en tiempo real
- Filtros guardados
- Exportación a PDF/Excel
- Dashboard con gráficos
- Métricas de rendimiento
