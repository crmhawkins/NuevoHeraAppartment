# 🧹 Dashboard de Limpiadoras - Plataforma de Gestión

## 📋 Descripción

Se ha implementado un dashboard específico para las limpiadoras de la plataforma, siguiendo el **Style Guide de Limpieza** (`PLATAFORMA_GESTION_LIMPIEZA_STYLE_GUIDE.md`). Este dashboard proporciona una interfaz optimizada para móviles con todas las funcionalidades necesarias para el trabajo diario de las limpiadoras.

## 🎯 Características Principales

### 1. **Dashboard Personalizado**
- **Header con saludo personalizado** y fecha actual
- **Estado del fichaje** (jornada activa/inactiva)
- **Estadísticas del día** (limpiezas totales, completadas, pendientes)
- **Porcentaje de completado de la semana**

### 2. **Gestión de Limpiezas**
- **Próximas limpiezas** programadas para hoy y mañana
- **Estado de cada limpieza** (pendiente, en proceso, completada)
- **Acceso directo** a continuar o iniciar limpiezas
- **Distinción visual** entre apartamentos y zonas comunes

### 3. **Acciones Rápidas**
- **Ver todas las limpiezas** (acceso al sistema principal)
- **Reportar incidencias** (crear nuevas incidencias)
- **Gestionar incidencias** (ver incidencias existentes)
- **Control de jornada** (iniciar/finalizar fichaje)

### 4. **Seguimiento de Incidencias**
- **Incidencias abiertas** del usuario
- **Prioridades visuales** (baja, media, alta, urgente)
- **Acceso directo** a detalles de cada incidencia

### 5. **Estadísticas de Calidad**
- **Análisis de calidad** de la última semana
- **Métricas visuales** por nivel de calidad
- **Histórico de rendimiento**

## 🚀 Funcionalidades Técnicas

### **Responsive Design**
- **Mobile-first** siguiendo el style guide
- **Breakpoints**: 768px (tablet) y 480px (móvil)
- **Adaptación automática** de tablas a cards en móvil
- **Botones táctiles** de tamaño mínimo 44px

### **Overlay de Carga**
- **Indicador visual** de progreso
- **Mensajes personalizados** según la acción
- **Animaciones suaves** de entrada/salida
- **Barra de progreso** animada

### **Actualización en Tiempo Real**
- **Estadísticas automáticas** cada 5 minutos
- **API endpoint** para datos del mes
- **Caché inteligente** de datos

## 📱 Diseño y UX

### **Paleta de Colores (Style Guide)**
- **Azul principal**: `#007AFF` (botones principales)
- **Verde**: `#28a745` (éxito, completado)
- **Amarillo**: `#ffc107` (advertencia, pendiente)
- **Rojo**: `#dc3545` (peligro, finalizar)
- **Gris**: `#6C6C70` (texto secundario)

### **Componentes Visuales**
- **Tarjetas con bordes redondeados** (15px)
- **Sombras suaves** para profundidad
- **Gradientes** para elementos principales
- **Iconos FontAwesome** para claridad visual

### **Navegación Intuitiva**
- **Jerarquía visual clara** de información
- **Acciones principales destacadas**
- **Estados visuales** para cada elemento
- **Feedback inmediato** en todas las acciones

## 🔧 Implementación Técnica

### **Controlador**
```php
app/Http/Controllers/LimpiadoraDashboardController.php
```

**Métodos principales:**
- `index()`: Dashboard principal con estadísticas del día
- `estadisticas()`: API para estadísticas del mes

### **Vista**
```php
resources/views/limpiadora/dashboard.blade.php
```

**Características:**
- Extiende `layouts.appPersonal`
- Incluye CSS del style guide
- JavaScript para overlay de carga
- Responsive design completo

### **Rutas**
```php
// Dashboard principal
GET /limpiadora/dashboard → limpiadora.dashboard

// API de estadísticas
GET /limpiadora/estadisticas → limpiadora.estadisticas
```

### **CSS**
```css
public/css/limpiadora-dashboard.css
```

**Incluye:**
- Estilos del dashboard
- Overlay de carga
- Responsive breakpoints
- Animaciones y transiciones

## 🔐 Seguridad y Acceso

### **Middleware de Autenticación**
- **Autenticación requerida** para todas las rutas
- **Verificación de rol** (empleada, limpiadora)
- **Redirección automática** después del login

### **Redirección por Rol**
```php
// Middleware RedirectAfterLogin
if (in_array($user->role, ['empleada', 'limpiadora'])) {
    return redirect('/limpiadora/dashboard');
}
```

### **Acceso a Datos**
- **Solo datos del usuario autenticado**
- **Filtrado por empleada_id**
- **Sin acceso a información de otros usuarios**

## 📊 Datos y Estadísticas

### **Estadísticas del Día**
- Limpiezas programadas para hoy
- Limpiezas completadas
- Limpiezas pendientes
- Porcentaje de completado de la semana

### **Estadísticas del Mes**
- Total de limpiezas del mes
- Limpiezas completadas del mes
- Horas trabajadas del mes
- Porcentaje de eficiencia

### **Datos de Calidad**
- Análisis de fotos de la última semana
- Distribución por nivel de calidad
- Tendencias de rendimiento

## 🎨 Personalización

### **Configuración de Colores**
Los colores se pueden personalizar editando las variables CSS en:
```css
public/css/limpiadora-dashboard.css
```

### **Modificación de Layout**
El layout se puede personalizar editando:
```php
resources/views/limpiadora/dashboard.blade.php
```

### **Agregar Nuevas Funcionalidades**
Para agregar nuevas funcionalidades:
1. **Controlador**: Agregar métodos en `LimpiadoraDashboardController`
2. **Vista**: Agregar secciones en `dashboard.blade.php`
3. **CSS**: Agregar estilos en `limpiadora-dashboard.css`
4. **Rutas**: Registrar en `routes/web.php`

## 🚀 Despliegue

### **Requisitos**
- Laravel 8+ 
- Base de datos con tablas: `users`, `apartamento_limpieza`, `fichajes`, `incidencias`
- CSS y JavaScript del style guide

### **Pasos de Despliegue**
1. **Copiar archivos** a sus ubicaciones correspondientes
2. **Ejecutar migraciones** si es necesario
3. **Limpiar caché**: `php artisan view:clear`
4. **Verificar rutas**: `php artisan route:list --name=limpiadora`

### **Verificación**
- Acceder como usuario con rol `empleada` o `limpiadora`
- Verificar redirección automática al dashboard
- Comprobar funcionalidad responsive en móvil
- Verificar overlay de carga en acciones

## 🐛 Solución de Problemas

### **Problemas Comunes**

#### **Dashboard no carga**
- Verificar que el usuario tenga rol `empleada` o `limpiadora`
- Comprobar que las rutas estén registradas
- Verificar que no haya errores en el controlador

#### **Estilos no se aplican**
- Verificar que `gestion-buttons.css` esté incluido
- Comprobar que `limpiadora-dashboard.css` esté incluido
- Limpiar caché de vistas: `php artisan view:clear`

#### **Redirección incorrecta**
- Verificar middleware `RedirectAfterLogin`
- Comprobar middleware `MobileRedirect`
- Verificar configuración de roles en la base de datos

### **Logs y Debugging**
- **Logs de Laravel**: `storage/logs/laravel.log`
- **Errores del navegador**: Consola de desarrollador
- **Rutas registradas**: `php artisan route:list`

## 📈 Futuras Mejoras

### **Funcionalidades Planificadas**
- **Notificaciones push** para nuevas limpiezas
- **Sincronización offline** para trabajo sin conexión
- **Gamificación** con badges y logros
- **Integración con calendario** del dispositivo

### **Optimizaciones Técnicas**
- **Lazy loading** de datos
- **Caché Redis** para estadísticas
- **WebSockets** para actualizaciones en tiempo real
- **PWA** para instalación como app

## 📞 Soporte

### **Documentación Relacionada**
- `PLATAFORMA_GESTION_LIMPIEZA_STYLE_GUIDE.md` - Guía de estilos
- `SOLUCION_LOGIN_419.md` - Solución de problemas de login
- `.cursorrules` - Reglas del repositorio

### **Archivos del Sistema**
- **Controlador**: `app/Http/Controllers/LimpiadoraDashboardController.php`
- **Vista**: `resources/views/limpiadora/dashboard.blade.php`
- **CSS**: `public/css/limpiadora-dashboard.css`
- **Rutas**: `routes/web.php` (líneas 460-463)

---

*Dashboard implementado siguiendo las mejores prácticas del style guide y optimizado para uso móvil.*
