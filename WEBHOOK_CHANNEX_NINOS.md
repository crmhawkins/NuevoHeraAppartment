# Sistema de Webhooks de Channex - Información de Niños

## Descripción General

Este documento describe cómo funciona el sistema de webhooks de Channex y cómo se ha implementado la funcionalidad para capturar y almacenar información sobre niños en las reservas.

## Flujo del Webhook

### 1. Webhook Inicial
Cuando Channex envía una notificación de reserva, primero llega un payload básico:

```json
{
    "timestamp": "2025-08-30T12:02:18.272877Z",
    "event": "booking",
    "user_id": null,
    "payload": {
        "property_id": "0c58587d-990c-44c2-8144-d131fbe44b73",
        "booking_id": "2555579d-345e-4368-a7d6-84bbdfd79f1d",
        "revision_id": "280c27d8-6e86-4739-9e87-710a329e0dfd"
    },
    "property_id": "0c58587d-990c-44c2-8144-d131fbe44b73"
}
```

### 2. Obtención de Datos Completos
El sistema hace una llamada a la API de Channex para obtener la información completa de la reserva:

```bash
GET https://app.channex.io/api/v1/bookings/{booking_id}
```

### 3. Estructura de Datos de Ocupación
La respuesta de Channex incluye información detallada sobre la ocupación:

```json
{
    "occupancy": {
        "adults": 1,
        "children": 1,
        "infants": 0,
        "ages": [2]
    }
}
```

**Nota importante**: La estructura real de Channex incluye:
- `adults`: Número de adultos
- `children`: Número de niños mayores
- `infants`: Número de bebés
- `ages`: Array con las edades de todos los menores (niños + bebés)

El campo `ages` contiene las edades de todos los menores, no solo de los niños.

## Campos Añadidos a la Tabla Reservas

Se han añadido los siguientes campos para almacenar información de niños:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `numero_ninos` | integer | Número total de niños en la reserva |
| `edades_ninos` | json | Array con las edades de cada niño |
| `notas_ninos` | text | Notas descriptivas generadas automáticamente |

## Procesamiento de Información de Niños

### Creación de Reserva
Cuando se crea una nueva reserva, se procesa la información de niños:

```php
Reserva::create([
    // ... otros campos ...
    'numero_ninos' => ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0),
    'edades_ninos' => $room['occupancy']['ages'] ?? [],
    'notas_ninos' => $this->generarNotasNinos($room['occupancy']),
]);
```

### Actualización de Reserva
Cuando se actualiza una reserva existente, se detectan cambios en la información de niños:

```php
$reservaExistente->update([
    // ... otros campos ...
    'numero_ninos' => ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0),
    'edades_ninos' => $room['occupancy']['ages'] ?? [],
    'notas_ninos' => $this->generarNotasNinos($room['occupancy']),
]);
```

**Nota**: El campo `numero_ninos` se calcula sumando `children` + `infants` para obtener el total de menores.

## Generación Automática de Notas

El método `generarNotasNinos()` crea notas descriptivas basadas en la información de niños:

### Ejemplos de Notas Generadas

**Con niños de 5 y 8 años:**
```
"Niños: 2. Edades: niño (5 años), niño (8 años). Se pueden proporcionar camas adicionales para niños."
```

**Con bebé de 0 años:**
```
"Niños: 1. Edades: bebé (0 años). Se requiere cuna para bebé. Se pueden proporcionar camas adicionales para niños."
```

**Con adolescentes de 14 y 16 años:**
```
"Niños: 2. Edades: adolescente (14 años), adolescente (16 años). Se pueden proporcionar camas adicionales para niños."
```

## Categorización de Edades

El sistema categoriza automáticamente a los niños según su edad:

- **0-2 años**: Bebé
- **3-12 años**: Niño
- **13+ años**: Adolescente

## Detección de Cambios

El sistema detecta y registra cambios en la información de niños:

```php
$numeroNinosNuevo = ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0);
$edadesNinosNuevas = $room['occupancy']['ages'] ?? [];

if ($reservaExistente->numero_ninos != $numeroNinosNuevo) {
    $cambios['numero_ninos'] = [
        'anterior' => $reservaExistente->numero_ninos,
        'nuevo' => $numeroNinosNuevo
    ];
}

if ($reservaExistente->edades_ninos != $edadesNinosNuevas) {
    $cambios['edades_ninos'] = [
        'anterior' => $reservaExistente->edades_ninos,
        'nuevo' => $edadesNinosNuevas
    ];
}
```

**Nota**: El sistema calcula el total de niños sumando `children` + `infants` y compara las edades del array `ages`.

## Logs y Auditoría

Todas las operaciones relacionadas con niños se registran en los logs:

```php
Log::info('Reserva actualizada con nuevos datos', [
    'reserva_id' => $reservaExistente->id,
    'numero_ninos' => ($room['occupancy']['children'] ?? 0) + ($room['occupancy']['infants'] ?? 0),
    'edades_ninos' => $room['occupancy']['ages'] ?? [],
    'cambios_detectados' => $cambios
]);
```

**Nota**: Los logs incluyen el cálculo total de niños y las edades del array `ages`.

## Casos de Uso

### 1. Reserva Familiar
- **Adults**: 2
- **Children**: 2
- **Infants**: 0
- **Ages**: [5, 8]
- **Total Niños**: 2
- **Notas**: Información sobre camas adicionales y necesidades específicas

### 2. Reserva con Bebé
- **Adults**: 2
- **Children**: 0
- **Infants**: 1
- **Ages**: [0]
- **Total Niños**: 1
- **Notas**: Requerimiento de cuna y consideraciones especiales

### 3. Reserva Solo Adultos
- **Adults**: 2
- **Children**: 0
- **Infants**: 0
- **Ages**: []
- **Total Niños**: 0
- **Notas**: null (sin notas)

### 4. Reserva Real (Ejemplo de Postman)
- **Adults**: 1
- **Children**: 1
- **Infants**: 0
- **Ages**: [2]
- **Total Niños**: 1
- **Notas**: Niño de 2 años, se pueden proporcionar camas adicionales

## Consideraciones Técnicas

### Base de Datos
- Los campos se añadieron mediante migración
- `edades_ninos` se almacena como JSON para flexibilidad
- `notas_ninos` permite texto largo para información detallada

### API de Channex
- Los campos `children`, `infants` y `ages` son opcionales
- `children` = niños mayores, `infants` = bebés
- `ages` contiene edades de todos los menores (niños + bebés)
- Se manejan valores por defecto para evitar errores
- La información se valida antes de almacenar

### Cálculo de Total de Niños
- **Total Niños** = `children` + `infants`
- Esto permite distinguir entre niños mayores y bebés
- Las edades se almacenan en el array `ages` para todos los menores

### Rendimiento
- Las notas se generan automáticamente sin consultas adicionales
- La detección de cambios es eficiente
- Los logs se optimizan para no impactar el rendimiento

## Próximos Pasos

1. **Validación**: Probar con reservas reales de Channex
2. **Interfaz**: Añadir campos de niños en las vistas de administración
3. **Reportes**: Crear reportes que incluyan información de niños
4. **Notificaciones**: Configurar alertas para reservas con niños
5. **Integración**: Conectar con sistemas de limpieza y mantenimiento

## Archivos Modificados

- `database/migrations/2025_09_01_100006_add_children_fields_to_reservas_table.php`
- `app/Models/Reserva.php`
- `app/Http/Controllers/WebhookController.php`
- `public/ejemplo_webhook_channex_completo.json`
- `WEBHOOK_CHANNEX_NINOS.md` (este archivo)

## Contacto

Para preguntas o problemas relacionados con esta funcionalidad, contactar al equipo de desarrollo.
