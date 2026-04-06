# 🧹 Guía de Estilos - Plataforma de Gestión de Limpieza

## 📋 Descripción General

Esta guía define el sistema de diseño unificado para toda la plataforma de gestión de limpieza, incluyendo las rutas `/gestion`, `/gestion-edit`, `/gestion/reservas`, y todas las funcionalidades relacionadas.

## 🎨 Paleta de Colores

### Colores Principales
```css
--apple-blue: #007AFF          /* Azul principal */
--apple-blue-dark: #0056CC     /* Azul oscuro */
--apple-blue-light: #4DA3FF    /* Azul claro */
```

### Colores Secundarios
```css
--apple-gray: #6C6C70         /* Gris principal */
--apple-gray-light: #F2F2F7   /* Gris claro */
--apple-gray-dark: #1D1D1F    /* Gris oscuro */
```

### Colores de Estado
```css
--success: #28a745            /* Verde - Éxito */
--warning: #ffc107            /* Amarillo - Advertencia */
--danger: #dc3545             /* Rojo - Error */
--info: #17a2b8               /* Azul info */
```

## 🔘 Sistema de Botones

### Botones Principales (`.apple-btn`)

#### Estilo Base
```css
.apple-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 24px;
    border: none;
    border-radius: 12px;          /* Menos redondeado */
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: 0.95rem;
    text-align: center;
    justify-content: center;
    min-width: 140px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
```

#### Variantes de Botones

##### Botón Principal (`.apple-btn-primary`)
- **Uso**: Acciones principales como "Iniciar Jornada", "Guardar", "Terminar"
- **Estilo**: Gradiente azul sólido
- **Código**: `background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%)`

##### Botón Secundario (`.apple-btn-secondary`)
- **Uso**: Acciones secundarias como "Volver", "Cancelar"
- **Estilo**: Gradiente gris
- **Código**: `background: linear-gradient(135deg, #6c757d 0%, #495057 100%)`

##### Botón de Información (`.apple-btn-info`)
- **Uso**: Acciones informativas como "Gestionar Incidencias"
- **Estilo**: Fondo blanco con borde azul, texto azul
- **Hover**: Se convierte en azul con texto blanco

##### Botón de Éxito (`.apple-btn-success`)
- **Uso**: Confirmaciones, finalizaciones
- **Estilo**: Gradiente verde
- **Código**: `background: linear-gradient(135deg, #28a745 0%, #20c997 100%)`

##### Botón de Advertencia (`.apple-btn-warning`)
- **Uso**: Pausas, acciones que requieren atención
- **Estilo**: Gradiente amarillo/naranja
- **Código**: `background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)`

##### Botón de Peligro (`.apple-btn-danger`)
- **Uso**: Finalizar jornada, acciones destructivas
- **Estilo**: Gradiente rojo
- **Código**: `background: linear-gradient(135deg, #dc3545 0%, #c82333 100%)`

### Botones de Acción (`.action-button`)

#### Estilo Base
```css
.action-button {
    width: 48px !important;
    height: 48px !important;
    border-radius: 50% !important;    /* Perfectamente redondos */
    font-size: 18px !important;
    border: none !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
}
```

#### Colores por Función
- **Amenities** (`.amenities-btn`): `#9C27B0` (Morado)
- **Información** (`.info-btn`): `#2196F3` (Azul)
- **Editar** (`.edit-btn`): `#4CAF50` (Verde)
- **Crear** (`.create-btn`): `#FF9800` (Naranja)
- **Calendario** (`.calendar-btn`): `#607D8B` (Gris azulado)

## 🎴 Sistema de Tarjetas

### Tarjetas Principales (`.apple-card`)
```css
.apple-card {
    background: #FFFFFF;
    border-radius: 15px;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: var(--spacing-lg);
}
```

### Headers de Tarjetas (`.apple-card-header`)
```css
.apple-card-header {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%) !important;
    padding: var(--spacing-lg) var(--spacing-lg) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}
```

### Cuerpo de Tarjetas (`.apple-card-body`)
```css
.apple-card-body {
    padding: var(--spacing-lg);
    background: #FFFFFF;
}
```

## 📱 Componentes Específicos

### Banner de Próxima Reserva
```css
.siguiente-reserva-banner {
    background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
    border: 2px solid #2196F3;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
}
```

### Estadísticas Compactas
```css
.reserva-stats-compact {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 15px;
    padding: 15px;
    backdrop-filter: blur(10px);
}
```

### Filtros por Estado
```css
.estado-filter-btn {
    width: 100%;
    padding: 12px 8px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #6c757d;
    font-weight: 600;
    min-height: 80px;
}
```

## 📐 Espaciado y Dimensiones

### Variables de Espaciado
```css
:root {
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --spacing-xl: 32px;
    --spacing-xxl: 48px;
}
```

### Breakpoints Responsive
```css
/* Mobile */
@media (max-width: 768px) {
    .apple-btn {
        min-width: 120px;
        padding: 10px 20px;
        font-size: 0.9rem;
    }
    
    .action-button {
        width: 44px !important;
        height: 44px !important;
        font-size: 16px !important;
    }
}

/* Small Mobile */
@media (max-width: 480px) {
    .apple-container {
        padding: 5px;
    }
    
    .apple-card-header {
        padding: 15px;
    }
}
```

## 🔄 Estados y Transiciones

### Hover Effects
```css
.apple-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}
```

### Transiciones
```css
.apple-btn {
    transition: all 0.3s ease;
}

.action-button {
    transition: all 0.2s ease;
}
```

## 📋 Tipografía

### Jerarquía de Textos
- **Títulos principales**: `font-size: 20px`, `font-weight: 700`
- **Subtítulos**: `font-size: 14px`, `font-weight: 400`
- **Texto del cuerpo**: `font-size: 0.95rem`, `font-weight: 400`
- **Etiquetas**: `font-size: 0.8rem`, `font-weight: 500`

### Familias de Fuentes
```css
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
```

## 🎯 Principios de Diseño

### 1. Consistencia Visual
- **Colores**: Usar siempre la paleta definida
- **Espaciado**: Mantener consistencia con las variables CSS
- **Bordes**: `12px` para botones, `15px` para tarjetas

### 2. Jerarquía Visual
- **Botones principales**: Azul sólido
- **Botones secundarios**: Blanco con borde azul
- **Acciones**: Colores específicos por función

### 3. Responsive Design
- **Mobile-first**: Diseño optimizado para móviles
- **Breakpoints**: 768px y 480px
- **Adaptación**: Elementos se ajustan al tamaño de pantalla

### 4. Accesibilidad
- **Contraste**: Colores con suficiente contraste
- **Tamaños**: Botones mínimos de 44px en móvil
- **Estados**: Hover y active claramente diferenciados

## 🚀 Implementación

### 1. Incluir CSS Base
```html
<link rel="stylesheet" href="{{ asset('css/gestion-buttons.css') }}">
```

### 2. Usar Clases Correctas
```html
<!-- Botón principal -->
<button class="apple-btn apple-btn-primary">Acción Principal</button>

<!-- Botón secundario -->
<button class="apple-btn apple-btn-info">Acción Secundaria</button>

<!-- Botón de acción -->
<button class="action-button amenities-btn">
    <i class="fa fa-gift"></i>
</button>
```

### 3. Estructura de Tarjetas
```html
<div class="apple-card">
    <div class="apple-card-header">
        <div class="apple-card-title">
            <i class="fa-solid fa-broom"></i>
            <span>Título de la Tarjeta</span>
        </div>
    </div>
    <div class="apple-card-body">
        <!-- Contenido -->
    </div>
</div>
```

## 🔄 Sistema de Loading Overlay

### HTML del Overlay
```html
<!-- Overlay de Carga -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner">
            <div class="spinner"></div>
        </div>
        <div class="loading-text">
            <h3>Actualizando...</h3>
            <p>Por favor, espera mientras se procesa tu solicitud</p>
            <div class="loading-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <span class="progress-text" id="progressText">0%</span>
            </div>
        </div>
    </div>
</div>
```

### Estilos CSS del Overlay
```css
/* Overlay de Carga */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.loading-content {
    background: rgba(255, 255, 255, 0.98);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(0, 0, 0, 0.05);
    max-width: 400px;
    width: 90%;
}

.loading-spinner {
    margin-bottom: 24px;
}

.spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(0, 122, 255, 0.2);
    border-top: 4px solid var(--apple-blue);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

.loading-text h3 {
    color: #1D1D1F;
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 8px;
}

.loading-text p {
    color: #6C6C70;
    font-size: 14px;
    margin-bottom: 24px;
    line-height: 1.4;
}

.loading-progress {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: rgba(0, 122, 255, 0.1);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--apple-blue), #4DA3FF);
    border-radius: 4px;
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    color: var(--apple-blue);
    font-size: 14px;
    font-weight: 600;
}
```

### Animaciones del Overlay
```css
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

### Funciones JavaScript del Overlay
```javascript
// Funciones para el Overlay de Carga
function showLoadingOverlay(message = 'Actualizando...') {
    const overlay = document.getElementById('loadingOverlay');
    const messageElement = overlay.querySelector('h3');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    messageElement.textContent = message;
    progressFill.style.width = '0%';
    progressText.textContent = '0%';
    
    overlay.style.display = 'flex';
    
    // Simular progreso
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        
        progressFill.style.width = progress + '%';
        progressText.textContent = Math.round(progress) + '%';
    }, 200);
    
    // Guardar el intervalo para poder limpiarlo
    overlay.dataset.progressInterval = progressInterval;
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    // Completar la barra de progreso
    progressFill.style.width = '100%';
    progressText.textContent = '100%';
    
    // Limpiar el intervalo de progreso
    if (overlay.dataset.progressInterval) {
        clearInterval(overlay.dataset.progressInterval);
    }
    
    // Ocultar después de un pequeño delay para mostrar el 100%
    setTimeout(() => {
        overlay.style.display = 'none';
    }, 500);
}

function updateLoadingProgress(percentage) {
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    if (progressFill && progressText) {
        progressFill.style.width = percentage + '%';
        progressText.textContent = Math.round(percentage) + '%';
    }
}
```

### Uso del Overlay en Formularios
```javascript
// Función para guardar cambios con overlay
function guardarCambios() {
    // Mostrar overlay de carga
    showLoadingOverlay('Guardando cambios...');
    
    // Simular un pequeño delay para mostrar el overlay
    setTimeout(() => {
        const formularioLimpieza = document.getElementById('formPrincipalLimpieza');
        
        if (formularioLimpieza) {
            formularioLimpieza.submit();
        } else {
            hideLoadingOverlay();
            mostrarModalError('No se pudo encontrar el formulario de limpieza.');
        }
    }, 500);
}

// Función para finalizar limpieza con overlay
function finalizarLimpieza() {
    showLoadingOverlay('Finalizando limpieza...');
    
    setTimeout(() => {
        const formulario = document.getElementById('formFinalizar');
        if (formulario) {
            formulario.submit();
        } else {
            hideLoadingOverlay();
            mostrarModalError('Error al finalizar la limpieza.');
        }
    }, 500);
}
```

## 📝 Notas Importantes

- **NO cambiar** los colores sin consultar esta guía
- **Mantener** la consistencia en toda la plataforma
- **Usar** las variables CSS definidas
- **Respetar** los border-radius establecidos
- **Seguir** la jerarquía de botones definida
- **Implementar** `showLoadingOverlay` en TODAS las peticiones AJAX y envíos de formularios
- **Usar** mensajes descriptivos en el overlay según la acción que se esté realizando

---

*Esta guía debe ser consultada antes de realizar cambios en el diseño de la plataforma de gestión de limpieza.*
