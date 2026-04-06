# Comandos para Gestión de Reservas con Niños

## 📋 Descripción General

Este documento describe los comandos Artisan disponibles para gestionar la información de niños en las reservas, especialmente útil para el equipo de limpieza.

## 🚀 Comandos Disponibles

### 1. Actualizar Reservas con Información de Niños

**Comando:** `php artisan reservas:actualizar-ninos-hoy`

**Descripción:** Actualiza las reservas de hoy con información de niños desde la API de Channex.

**Opciones:**
- `--force`: Ejecuta sin confirmación del usuario

**Uso:**
```bash
# Con confirmación
php artisan reservas:actualizar-ninos-hoy

# Sin confirmación (útil para automatización)
php artisan reservas:actualizar-ninos-hoy --force
```

**Funcionalidad:**
- Busca todas las reservas de hoy con ID de Channex
- Hace llamadas a la API de Channex para obtener información actualizada
- Actualiza solo los campos relacionados con niños:
  - `numero_ninos`
  - `edades_ninos`
  - `notas_ninos`
- Registra todos los cambios en logs
- Muestra un resumen de la operación

**Ejemplo de salida:**
```
🚀 Iniciando actualización de reservas de hoy con información de niños...
📅 Encontradas 5 reservas de hoy para actualizar.
¿Deseas continuar con la actualización? (yes/no) [no]:
> yes

████████████████████████████████████████ 100%

📊 Resumen de la actualización:
✅ Reservas actualizadas: 3
ℹ️  Sin cambios: 2
❌ Errores: 0
🎯 Actualización completada.
```

### 2. Mostrar Reservas con Información de Niños

**Comando:** `php artisan reservas:mostrar-ninos-hoy`

**Descripción:** Muestra las reservas de hoy con información detallada de niños para el equipo de limpieza.

**Opciones:**
- `--formato=table`: Formato de tabla (por defecto)
- `--formato=json`: Formato JSON
- `--formato=csv`: Formato CSV

**Uso:**
```bash
# Formato tabla (por defecto)
php artisan reservas:mostrar-ninos-hoy

# Formato JSON
php artisan reservas:mostrar-ninos-hoy --formato=json

# Formato CSV
php artisan reservas:mostrar-ninos-hoy --formato=csv
```

**Funcionalidad:**
- Muestra todas las reservas de hoy
- Filtra reservas con y sin niños
- Proporciona información especial para limpieza
- Incluye recomendaciones específicas según las edades
- Múltiples formatos de salida

**Ejemplo de salida:**
```
🏠 Información de reservas de hoy con niños para el equipo de limpieza

📅 Fecha: 01/09/2025 - Total de reservas: 5

👶 Reservas CON niños: 2
👥 Reservas SIN niños: 3

+----+-------------+------------------+--------+--------+---------+-------+--------+--------------------------------+----------+
| ID | Apartamento | Cliente          | Entrada| Salida | Adultos | Niños | Edades | Notas                          | Estado   |
+----+-------------+------------------+--------+--------+---------+-------+--------+--------------------------------+----------+
| 1  | Apto 101    | Juan Pérez       | 01/09  | 05/09  | 2       | 2     | 5, 8   | Niños: 2. Niños mayores: 2... | Confirmada|
| 2  | Apto 203    | María García     | 01/09  | 03/09  | 2       | 1     | 0      | Niños: 1. Bebés: 1. Edades... | Confirmada|
+----+-------------+------------------+--------+--------+---------+-------+--------+--------------------------------+----------+

🔍 INFORMACIÓN ESPECIAL PARA LIMPIEZA:

🏠 Apartamento: Apto 101
👤 Cliente: Juan Pérez
📅 Entrada: 01/09/2025
👶 Niños: 2
🎂 Edades: niño (5 años), niño (8 años)
📝 Notas: Niños: 2. Niños mayores: 2. Edades: niño (5 años), niño (8 años). Se pueden proporcionar camas adicionales para niños.
🧹 Recomendaciones de limpieza:
   • Limpiar a fondo áreas de juego y dormitorios
   • Verificar enchufes y seguridad
```

### 3. Programar Actualización Automática

**Comando:** `php artisan reservas:programar-actualizacion-ninos`

**Descripción:** Configura la actualización automática diaria de información de niños.

**Opciones:**
- `--add-to-kernel`: Añade automáticamente al Kernel para ejecución automática

**Uso:**
```bash
# Mostrar instrucciones manuales
php artisan reservas:programar-actualizacion-ninos

# Configuración automática
php artisan reservas:programar-actualizacion-ninos --add-to-kernel
```

**Funcionalidad:**
- Proporciona instrucciones para configuración manual
- Opción de configuración automática
- Configura ejecución diaria a las 8:00 AM
- Incluye manejo de errores y logs

## 🔄 Automatización

### Configuración Automática

Para configurar la ejecución automática diaria:

```bash
php artisan reservas:programar-actualizacion-ninos --add-to-kernel
```

### Configuración Manual

Si prefieres configurar manualmente, añade esto en `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Actualizar información de niños en reservas de hoy
    $schedule->command('reservas:actualizar-ninos-hoy --force')
        ->dailyAt('08:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->onSuccess(function () {
            Log::info('Actualización automática de niños completada exitosamente');
        })
        ->onFailure(function () {
            Log::error('Error en actualización automática de niños');
        });
}
```

### Ejecución en Desarrollo

```bash
php artisan schedule:work
```

### Ejecución en Producción

Configura un cron job para ejecutar cada minuto:

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## 📊 Casos de Uso

### Para Administradores

1. **Actualización manual diaria:**
   ```bash
   php artisan reservas:actualizar-ninos-hoy
   ```

2. **Verificar estado de reservas:**
   ```bash
   php artisan reservas:mostrar-ninos-hoy
   ```

3. **Configurar automatización:**
   ```bash
   php artisan reservas:programar-actualizacion-ninos --add-to-kernel
   ```

### Para Equipo de Limpieza

1. **Ver reservas del día:**
   ```bash
   php artisan reservas:mostrar-ninos-hoy
   ```

2. **Exportar en CSV para planificación:**
   ```bash
   php artisan reservas:mostrar-ninos-hoy --formato=csv > reservas_hoy.csv
   ```

### Para Desarrollo

1. **Probar comandos:**
   ```bash
   php artisan reservas:actualizar-ninos-hoy --force
   php artisan reservas:mostrar-ninos-hoy --formato=json
   ```

2. **Ver logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep "niños\|children"
   ```

## 🔧 Configuración Requerida

### Variables de Entorno

Asegúrate de tener configurado en tu archivo `.env`:

```env
CHANNEX_TOKEN=tu_token_de_channex
CHANNEX_URL=https://app.channex.io/api/v1
```

### Permisos de Base de Datos

Los comandos requieren acceso a:
- Tabla `reservas`
- Tabla `clientes`
- Tabla `apartamentos`
- Tabla `estados`

### Dependencias

- Laravel HTTP Client
- Carbon para manejo de fechas
- Logging de Laravel

## 📝 Logs y Auditoría

### Logs de Actualización

Todas las actualizaciones se registran en `storage/logs/laravel.log`:

```
[2025-09-01 08:00:01] local.INFO: Reserva actualizada con información de niños {
    "reserva_id": 123,
    "codigo_reserva": "BK123456789",
    "cambios": {
        "numero_ninos": {"anterior": 0, "nuevo": 2},
        "edades_ninos": {"anterior": [], "nuevo": [5, 8]}
    },
    "fecha_entrada": "2025-09-01",
    "cliente": "Juan Pérez"
}
```

### Logs de Errores

Los errores también se registran con detalles completos:

```
[2025-09-01 08:00:02] local.ERROR: Error actualizando reserva con niños {
    "reserva_id": 124,
    "error": "cURL error 28: Operation timed out",
    "trace": "..."
}
```

## 🚨 Solución de Problemas

### Error: CHANNEX_TOKEN no configurado

```bash
❌ Error: CHANNEX_TOKEN no configurado en .env
```

**Solución:** Verifica que `CHANNEX_TOKEN` esté configurado en tu archivo `.env`

### Error: No hay reservas de hoy

```bash
ℹ️  No hay reservas de hoy con ID de Channex para actualizar.
```

**Solución:** Verifica que:
- Haya reservas para la fecha de hoy
- Las reservas tengan `id_channex` configurado
- Las reservas no estén canceladas (`estado_id != 4`)

### Error: API de Channex no responde

```bash
❌ Error obteniendo datos de Channex para reserva 123: 401
```

**Solución:** Verifica que:
- El token de Channex sea válido
- La API de Channex esté funcionando
- No haya límites de rate limiting

## 📞 Soporte

Para problemas o preguntas sobre estos comandos:

1. Revisa los logs en `storage/logs/laravel.log`
2. Verifica la configuración de variables de entorno
3. Comprueba la conectividad con la API de Channex
4. Contacta al equipo de desarrollo

## 🔄 Versiones y Compatibilidad

- **Laravel:** 8.x, 9.x, 10.x, 11.x
- **PHP:** 8.0+
- **Base de datos:** MySQL, PostgreSQL, SQLite
- **Sistema operativo:** Linux, macOS, Windows
