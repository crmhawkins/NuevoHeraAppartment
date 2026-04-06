# Sistema de Escaneo de DNI - Documentación

## 📋 Descripción

Sistema de captura guiada de DNI similar a la aplicación FNMT, que permite a los usuarios capturar imágenes de documentos de identidad con detección automática y guía visual.

## 🚀 Características

- **Detección automática** de documentos en tiempo real
- **Guía visual** con marco superpuesto
- **Feedback inmediato** sobre posición y calidad
- **Captura optimizada** para procesamiento con IA
- **Flujo guiado** frontal → trasera
- **Interfaz responsive** para móviles
- **Procesamiento de imágenes** con correcciones automáticas

## 📁 Estructura de Archivos

```
app/Http/Controllers/
└── DNIScannerController.php          # Controlador principal

resources/views/dni/
└── scanner.blade.php                 # Vista principal del scanner

public/css/
└── scanner-overlay.css               # Estilos del overlay y guías

public/js/
├── camera-controller.js              # Manejo de cámara
├── document-detector.js              # Detección de documentos
├── overlay-manager.js                # Gestión de interfaz
├── image-processor.js                # Procesamiento de imágenes
└── main-scanner.js                   # Controlador principal JS

routes/web.php                        # Rutas del sistema
```

## 🔗 Rutas

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/dni-scanner/{token}` | Mostrar scanner de DNI |
| POST | `/dni-scanner/process` | Procesar imagen capturada |
| POST | `/dni-scanner/complete` | Completar verificación |

## 🎯 Uso

### Acceso al Scanner

```php
// URL de acceso
https://tu-dominio.com/dni-scanner/{token}

// Donde {token} es el token único de la reserva
```

### Flujo de Usuario

1. **Inicialización**: El usuario accede con el token de su reserva
2. **Detección**: El sistema detecta automáticamente el DNI en la cámara
3. **Guía**: Muestra feedback visual sobre posición y calidad
4. **Captura Frontal**: Usuario captura el frontal del DNI
5. **Procesamiento**: Imagen se envía a la IA para extracción de datos
6. **Captura Trasera**: Usuario captura la trasera del DNI
7. **Finalización**: Proceso completado y datos guardados

## ⚙️ Configuración

### Parámetros de Detección

```javascript
// En document-detector.js
const dniConfig = {
    aspectRatio: 1.585,           // Proporción DNI español
    aspectRatioTolerance: 0.1,    // Tolerancia de proporción
    minArea: 0.1,                 // Área mínima (10% del frame)
    maxArea: 0.8,                 // Área máxima (80% del frame)
    minWidth: 200,                // Ancho mínimo en píxeles
    minHeight: 120                // Alto mínimo en píxeles
};
```

### Parámetros de Procesamiento

```javascript
// En image-processor.js
const config = {
    maxWidth: 1920,               // Ancho máximo de imagen
    maxHeight: 1080,              // Alto máximo de imagen
    quality: 0.8,                 // Calidad JPEG (0-1)
    format: 'image/jpeg'          // Formato de salida
};
```

## 🔧 Integración con IA

### Método de Integración

En `DNIScannerController.php`, método `sendToAI()`:

```php
private function sendToAI($imagePath, $side)
{
    // TODO: Reemplazar con tu integración real
    $client = new \GuzzleHttp\Client();
    
    $response = $client->post('https://tu-ia-api.com/process-dni', [
        'multipart' => [
            [
                'name' => 'image',
                'contents' => fopen($imagePath, 'r'),
                'filename' => 'dni_' . $side . '.jpg'
            ],
            [
                'name' => 'side',
                'contents' => $side
            ]
        ],
        'timeout' => 30
    ]);
    
    return json_decode($response->getBody(), true);
}
```

### Formato de Respuesta Esperado

```json
{
    "success": true,
    "data": {
        "nombre": "Juan",
        "apellido1": "Pérez",
        "apellido2": "García",
        "dni": "12345678A",
        "fecha_nacimiento": "1990-03-15",
        "sexo": "Masculino",
        "fecha_expedicion": "2020-01-15",
        "nacionalidad": "Española"
    }
}
```

## 📱 Compatibilidad

### Navegadores Soportados

- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 11+
- ✅ Edge 79+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

### Características Requeridas

- `getUserMedia()` para acceso a cámara
- `Canvas API` para procesamiento de imágenes
- `WebRTC` para captura de video
- `ES6+` para JavaScript moderno

## 🔒 Seguridad

### Medidas Implementadas

- **Validación de token** en todas las rutas
- **CSRF protection** en formularios
- **Limpieza automática** de archivos temporales
- **Validación de imágenes** antes del procesamiento
- **Logs de auditoría** para todas las operaciones

### Datos Sensibles

- Las imágenes se procesan en memoria cuando es posible
- Archivos temporales se eliminan automáticamente
- No se almacenan imágenes sin procesar
- Logs no contienen datos personales

## 🐛 Debugging

### Logs del Sistema

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep "DNIScanner"

# Logs específicos del scanner
grep "DNIScanner" storage/logs/laravel.log
```

### Debugging en JavaScript

```javascript
// Acceder al scanner desde consola
window.dniScanner.getStatus()

// Verificar estado de la cámara
window.dniScanner.cameraController.isCameraWorking()

// Ver configuración actual
window.dniScanner.documentDetector.getConfiguration()
```

### Variables de Debug

```javascript
// Habilitar logs detallados
localStorage.setItem('dni-scanner-debug', 'true');

// Ver información de detección
window.dniScanner.documentDetector.lastDetectionResult
```

## 📊 Métricas y Monitoreo

### KPIs del Sistema

- Tiempo promedio de detección
- Tasa de éxito de captura
- Calidad promedio de imágenes
- Tiempo de procesamiento por imagen

### Alertas Configurables

- Fallos en detección de documentos
- Errores de procesamiento de IA
- Problemas de conectividad
- Rendimiento degradado

## 🔄 Mantenimiento

### Limpieza de Archivos Temporales

```bash
# Limpiar archivos temporales antiguos
find storage/app/temp -name "temp_*" -mtime +1 -delete
```

### Actualización de Configuración

```php
// En el controlador, actualizar configuración
$scanner->configure([
    'minConfidence' => 85,
    'detectionInterval' => 150
]);
```

## 🚨 Solución de Problemas

### Problemas Comunes

1. **Cámara no funciona**
   - Verificar permisos del navegador
   - Comprobar que no esté siendo usada por otra app
   - Probar en modo incógnito

2. **Detección no funciona**
   - Verificar iluminación
   - Asegurar que el DNI esté plano
   - Comprobar que esté dentro del marco

3. **Error de procesamiento**
   - Verificar conectividad con IA
   - Comprobar logs del servidor
   - Validar formato de respuesta de IA

### Códigos de Error

| Código | Descripción | Solución |
|--------|-------------|----------|
| CAMERA_001 | Cámara no disponible | Verificar permisos |
| CAMERA_002 | Error de inicialización | Reiniciar navegador |
| DETECT_001 | Documento no detectado | Mejorar iluminación |
| DETECT_002 | Confianza insuficiente | Ajustar posición |
| PROCESS_001 | Error de IA | Verificar conectividad |
| PROCESS_002 | Formato inválido | Validar respuesta IA |

## 📈 Mejoras Futuras

### Funcionalidades Planificadas

- [ ] Soporte para múltiples tipos de documento
- [ ] Verificación biométrica con selfie
- [ ] Detección de documentos falsos
- [ ] Modo offline con sincronización
- [ ] Integración con más proveedores de IA
- [ ] Análisis de calidad avanzado

### Optimizaciones

- [ ] Compresión de imágenes más eficiente
- [ ] Detección más rápida con Web Workers
- [ ] Cache de configuraciones
- [ ] Lazy loading de componentes
- [ ] Service Worker para funcionalidad offline

## 📞 Soporte

Para soporte técnico o reportar bugs:

1. Revisar logs del sistema
2. Verificar configuración
3. Probar en diferentes navegadores
4. Contactar al equipo de desarrollo

---

**Versión**: 1.0.0  
**Última actualización**: {{ date('Y-m-d') }}  
**Autor**: Sistema de Apartamentos Hawkins



