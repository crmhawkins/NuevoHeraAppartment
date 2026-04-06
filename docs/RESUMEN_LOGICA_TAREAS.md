# 📋 Resumen Ejecutivo - Lógica de Generación de Tareas

## 🎯 Objetivo
Sistema inteligente que asigna automáticamente tareas de limpieza a empleadas disponibles, optimizando recursos y considerando vacaciones.

## ⚡ Lógica Principal

### 📅 **FINES DE SEMANA** (Sábado/Domingo)
- **1 empleada** trabaja
- **7 horas** fijas (08:00-15:00)
- **TODAS las tareas** de limpieza se asignan
- **Prioridad**: Apartamentos → Zonas Comunes → Lavandería

### 🏢 **ENTRE SEMANA** (Lunes-Viernes)
- **Múltiples empleadas** pueden trabajar
- **Horas variables** según contratación
- **Lógica adaptativa** según vacaciones:

#### Sin Vacaciones:
- Empleada 8h → 8h de trabajo
- Empleada 6h → 6h de trabajo

#### Con Vacaciones:
- Empleada 8h → 7h de trabajo (reducción 1h)
- Empleada 6h → 4h de trabajo (reducción 2h)

## 🎯 Sistema de Prioridades

| Prioridad | Tipo | Descripción | Cuándo se usa |
|-----------|------|-------------|---------------|
| **10** | Apartamentos | Limpieza apartamentos | Con vacaciones |
| **8** | Apartamentos | Limpieza apartamentos | Sin vacaciones |
| **7** | Zonas Comunes | Áreas compartidas | Siempre |
| **5** | Lavandería | Edificio Costa + cocina | Si hay tiempo |

## 🔄 Proceso de Asignación

### 1. **Detección de Contexto**
```php
$esFinDeSemana = $diaSemana == 0 || $diaSemana == 6;
$hayVacaciones = $empleadasEnVacaciones->isNotEmpty();
```

### 2. **Cálculo de Horas**
```php
// Fin de semana
$horas = 7.0;

// Entre semana sin vacaciones
$horas = $empleada->horas_contratadas_dia;

// Entre semana con vacaciones
$horas = $empleada->horas_contratadas_dia >= 8 ? 7.0 : 4.0;
```

### 3. **Asignación por Prioridad**
```php
1. Apartamentos (prioridad 10/8)
   - Ordenar por prioridad_limpieza DESC
   - Asignar hasta agotar tiempo

2. Zonas Comunes (prioridad 7)
   - Ordenar por prioridad_limpieza DESC
   - Asignar tiempo restante

3. Lavandería (prioridad 5)
   - Solo Edificio Costa
   - Solo si hay tiempo restante
```

## 📊 Ejemplo Práctico

### Escenario: Lunes con vacaciones
- **Empleadas**: María (8h), Ana (6h)
- **Vacaciones**: Carmen (8h) ausente
- **Resultado**:
  - María: 7h → 8 tareas (6 apartamentos + 2 zonas)
  - Ana: 4h → 4 tareas (3 apartamentos + 1 zona)

## 🛠️ Gestión Manual

### Funcionalidades Disponibles:
- ✅ **Editar tareas**: Cambiar tipo, elemento, prioridad
- ✅ **Añadir tareas**: Nuevas tareas al turno
- ✅ **Eliminar tareas**: Quitar tareas no necesarias
- ✅ **Reordenar**: Cambiar orden de ejecución
- ✅ **Estados**: Pendiente → En Progreso → Completada

### Interfaz:
- **Tabla interactiva** con checkboxes
- **Modal de edición** para añadir/modificar
- **Botones de acción** para cada tarea
- **Validaciones** en tiempo real

## 🔍 Logging y Debugging

### Niveles de Log:
- **INFO**: Proceso general, estadísticas
- **WARNING**: Tiempo excedido, empleadas no disponibles
- **ERROR**: Errores en creación de tareas
- **DEBUG**: Detalles de cada tarea asignada

### Ejemplo de Log:
```
🚀 Generando turnos para 2024-01-15 (Lunes, Fin de semana: No)
👥 Empleadas disponibles: 2
🏖️ Empleadas en vacaciones: 1
🎯 Asignando tareas para turno 123: 7h disponibles, vacaciones: Sí
🏠 Apartamentos asignados: 6 tareas, tiempo usado: 360min
✅ Total tareas asignadas: 8, tiempo total: 420min de 420min disponibles
```

## ⚙️ Configuración

### Variables Clave:
```php
// Horas fijas fin de semana
const HORAS_FIN_SEMANA = 7.0;

// Reducciones con vacaciones
const REDUCCION_8H = 1.0; // 8h → 7h
const REDUCCION_6H = 2.0; // 6h → 4h

// Prioridades
const PRIORIDAD_APARTAMENTOS_VACACIONES = 10;
const PRIORIDAD_APARTAMENTOS_NORMAL = 8;
const PRIORIDAD_ZONAS_COMUNES = 7;
const PRIORIDAD_LAVANDERIA = 5;
```

## 🚀 Beneficios

### Para Administradores:
- **Automatización**: Generación automática de turnos
- **Flexibilidad**: Edición manual cuando sea necesario
- **Visibilidad**: Logs detallados para seguimiento
- **Eficiencia**: Optimización de recursos

### Para Limpiadoras:
- **Claridad**: Tareas bien definidas y ordenadas
- **Planificación**: Tiempo estimado para cada tarea
- **Progreso**: Estados visibles de cada tarea
- **Realismo**: Carga de trabajo ajustada a disponibilidad

### Para el Sistema:
- **Robustez**: Manejo de errores y validaciones
- **Escalabilidad**: Fácil añadir nuevos tipos de tareas
- **Mantenibilidad**: Código bien estructurado
- **Trazabilidad**: Logs completos para debugging

## 📈 Métricas de Éxito

- **Cobertura**: 100% de apartamentos activos asignados
- **Eficiencia**: Tiempo asignado ≤ tiempo disponible
- **Flexibilidad**: 100% de tareas editables manualmente
- **Trazabilidad**: Logs detallados para cada operación
- **Usabilidad**: Interfaz intuitiva para gestión manual

---

**El sistema está diseñado para ser inteligente, flexible y eficiente, proporcionando una solución completa para la gestión de tareas de limpieza.**

