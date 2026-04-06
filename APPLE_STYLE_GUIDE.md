# 🍎 Guía de Estilos - Sistema de Gestión de Alquiler
## Diseño Inspirado en Apple - iOS/macOS

---

## 📱 **PRINCIPIOS DE DISEÑO**

### **Filosofía Apple**
- **Simplicidad**: Menos es más - interfaces limpias y sin distracciones
- **Claridad**: Información clara y legible con jerarquía visual bien definida
- **Profundidad**: Uso sutil de sombras, capas y efectos de profundidad
- **Deferencia**: El contenido es lo más importante, la interfaz se adapta
- **Direct Manipulation**: Interacciones directas e intuitivas

### **Valores de Marca**
- **Profesional**: Limpio, confiable y eficiente
- **Moderno**: Tecnología actualizada con diseño contemporáneo
- **Accesible**: Fácil de usar para todos los usuarios
- **Eficiente**: Flujos de trabajo optimizados

---

## 🎨 **PALETA DE COLORES**

### **Colores Principales**
```css
/* Azul Apple - Color principal */
--apple-blue: #007AFF;
--apple-blue-dark: #0056CC;
--apple-blue-light: #4DA3FF;
--apple-blue-pale: rgba(0, 122, 255, 0.1);

/* Grises del Sistema */
--system-gray: #8E8E93;
--system-gray-2: #AEAEB2;
--system-gray-3: #C7C7CC;
--system-gray-4: #D1D1D6;
--system-gray-5: #E5E5EA;
--system-gray-6: #F2F2F7;

/* Colores de Estado */
--success-green: #34C759;
--success-green-dark: #30D158;
--warning-orange: #FF9500;
--warning-orange-dark: #FF6B00;
--error-red: #FF3B30;
--error-red-dark: #D70015;
--info-blue: #5AC8FA;

/* Colores de Fondo */
--system-background: #FFFFFF;
--system-background-secondary: #F2F2F7;
--system-background-tertiary: #FFFFFF;
--system-background-card: rgba(255, 255, 255, 0.95);

/* Colores de Texto */
--text-primary: #1D1D1F;
--text-secondary: #8E8E93;
--text-tertiary: #C7C7CC;
--text-inverse: #FFFFFF;
```

---

## 🔤 **TIPOGRAFÍA**

### **Jerarquía de Texto**
```css
/* Títulos Principales */
--font-size-large-title: 34px;
--font-size-title-1: 28px;
--font-size-title-2: 22px;
--font-size-title-3: 20px;

/* Cuerpo de Texto */
--font-size-headline: 17px;
--font-size-body: 17px;
--font-size-callout: 16px;
--font-size-subhead: 15px;
--font-size-footnote: 13px;
--font-size-caption-1: 12px;
--font-size-caption-2: 11px;

/* Pesos de Fuente */
--font-weight-regular: 400;
--font-weight-medium: 500;
--font-weight-semibold: 600;
--font-weight-bold: 700;
```

### **Familia de Fuentes**
```css
/* Fuente Principal - SF Pro Display (Apple) */
font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Helvetica Neue', 'Nunito', sans-serif;

/* Fuente Monoespaciada */
font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
```

---

## 📐 **ESPACIADO Y LAYOUT**

### **Sistema de Espaciado (8px Grid)**
```css
--spacing-xs: 4px;
--spacing-sm: 8px;
--spacing-md: 16px;
--spacing-lg: 24px;
--spacing-xl: 32px;
--spacing-xxl: 48px;
--spacing-xxxl: 64px;

/* Márgenes y Padding Específicos */
--container-padding: 16px;
--section-spacing: 24px;
--card-padding: 20px;
--list-item-padding: 16px;
--button-padding: 16px 24px;
```

### **Bordes Redondeados**
```css
--border-radius-xs: 4px;
--border-radius-sm: 8px;
--border-radius-md: 12px;
--border-radius-lg: 16px;
--border-radius-xl: 20px;
--border-radius-full: 50%;
```

---

## 🎯 **COMPONENTES ESPECÍFICOS**

### **1. Header/Navigation**
```css
/* Header Principal */
.app-header {
    background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
    height: 52px;
    border-radius: 0 0 45px 45px;
    box-shadow: 0 2px 20px rgba(0, 122, 255, 0.3);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}

/* Navegación */
.navbar {
    background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
    box-shadow: 0 2px 20px rgba(0, 122, 255, 0.15);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.navbar-brand img {
    height: 40px;
    filter: brightness(0) invert(1);
}

.nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 500;
    padding: 12px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.nav-link:hover,
.nav-link.active {
    color: #FFFFFF !important;
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
}

/* Dropdown Menus */
.dropdown-menu {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0, 0, 0, 0.04);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    padding: 8px;
    margin-top: 8px;
}

.dropdown-item {
    border-radius: 8px;
    padding: 12px 16px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: rgba(0, 122, 255, 0.1);
    color: #007AFF;
    transform: translateX(4px);
}
```

### **2. Contenedor Principal**
```css
/* Contenedor Principal */
.contenedor-principal {
    margin-top: 80px;
    padding: 24px;
    background: #F2F2F7;
    min-height: calc(100vh - 80px);
}

/* Header de Sección */
.nav-top {
    margin-bottom: 24px;
}

.font-titulo {
    font-size: 28px;
    font-weight: 700;
    color: #1D1D1F;
    margin: 0;
    letter-spacing: -0.01em;
}
```

### **3. Tarjetas de Contenido**
```css
/* Tarjeta Principal */
.card {
    background: #FFFFFF;
    border: none;
    border-radius: 16px;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-bottom: 24px;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.card-header {
    background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h5 {
    font-size: 18px;
    font-weight: 600;
    color: #1D1D1F;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-header i {
    color: #007AFF;
    font-size: 20px;
}

.card-body {
    padding: 24px;
    background: #FFFFFF;
}
```

### **4. Botones**
```css
/* Botón Primario */
.btn-primary {
    background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
    border: none;
    border-radius: 12px;
    padding: 16px 24px;
    font-size: 17px;
    font-weight: 600;
    color: #FFFFFF;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
    text-transform: none;
    letter-spacing: -0.01em;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 122, 255, 0.4);
    color: #FFFFFF;
}

.btn-primary:active {
    transform: translateY(0);
}

/* Botón Secundario */
.btn-secondary {
    background: #F2F2F7;
    border: 1px solid #E5E5EA;
    border-radius: 12px;
    padding: 16px 24px;
    font-size: 17px;
    font-weight: 600;
    color: #8E8E93;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #E5E5EA;
    color: #1D1D1F;
    transform: translateY(-2px);
}

/* Botón de Éxito */
.btn-success {
    background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
    border: none;
    border-radius: 12px;
    padding: 16px 24px;
    font-size: 17px;
    font-weight: 600;
    color: #FFFFFF;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(52, 199, 89, 0.3);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(52, 199, 89, 0.4);
    color: #FFFFFF;
}

/* Botón de Información */
.btn-info {
    background: linear-gradient(135deg, #5AC8FA 0%, #007AFF 100%);
    border: none;
    border-radius: 12px;
    padding: 16px 24px;
    font-size: 17px;
    font-weight: 600;
    color: #FFFFFF;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(90, 200, 250, 0.3);
}

.btn-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(90, 200, 250, 0.4);
    color: #FFFFFF;
}

/* Botón Deshabilitado */
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

/* Botones de Acción */
.btn-group .btn {
    margin-right: 8px;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
```

### **5. Tablas**
```css
/* Tabla Principal */
.table {
    background: #FFFFFF;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    margin-bottom: 0;
}

/* Encabezado de Tabla */
.table thead {
    background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
    border-bottom: 2px solid #E5E5EA;
}

.table thead th {
    background: transparent;
    color: #1D1D1F;
    font-weight: 600;
    border: none;
    padding: 16px 12px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    vertical-align: middle;
}

/* Cuerpo de Tabla */
.table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #F2F2F7;
}

.table tbody tr:hover {
    background: rgba(0, 122, 255, 0.02);
    transform: scale(1.01);
}

.table tbody tr:last-child {
    border-bottom: none;
}

.table tbody td {
    padding: 16px 12px;
    vertical-align: middle;
    border: none;
    color: #1D1D1F;
    font-size: 15px;
}

/* Controles de Tabla */
.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: #F2F2F7;
    border-top: 1px solid #E5E5EA;
}

.table-info {
    font-size: 14px;
    color: #8E8E93;
    font-weight: 500;
}

.table-search {
    display: flex;
    align-items: center;
    gap: 12px;
}

.table-search input {
    border: 1px solid #E5E5EA;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 14px;
    background: #FFFFFF;
}

.table-search input:focus {
    outline: none;
    border-color: #007AFF;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}
```

### **6. Filtros**
```css
/* Sección de Filtros */
.filters-section {
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 24px;
    overflow: hidden;
}

.filters-header {
    background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
    padding: 20px 24px;
    border-bottom: 1px solid #E5E5EA;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filters-header:hover {
    background: linear-gradient(135deg, #E5E5EA 0%, #D1D1D6 100%);
}

.filters-title {
    font-size: 18px;
    font-weight: 600;
    color: #1D1D1F;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.filters-title i {
    color: #007AFF;
    font-size: 20px;
}

.filters-body {
    padding: 24px;
    background: #FFFFFF;
}

/* Campos de Filtro */
.form-label {
    font-weight: 600;
    color: #1D1D1F;
    margin-bottom: 8px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control,
.form-select {
    border: 1px solid #E5E5EA;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 15px;
    background: #FFFFFF;
    transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
    outline: none;
    border-color: #007AFF;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

/* Botones de Filtro */
.filters-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.filters-actions .btn {
    flex: 1;
    max-width: 200px;
}
```

### **7. Badges y Estados**
```css
/* Badges de Estado */
.badge {
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge.bg-success {
    background: linear-gradient(135deg, #34C759 0%, #30D158 100%) !important;
    color: #FFFFFF !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #FF9500 0%, #FF6B00 100%) !important;
    color: #FFFFFF !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #FF3B30 0%, #D70015 100%) !important;
    color: #FFFFFF !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #8E8E93 0%, #6C6C70 100%) !important;
    color: #FFFFFF !important;
}

/* Badges de Contador */
.badge-counter {
    background: #007AFF;
    color: #FFFFFF;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 12px;
    min-width: 20px;
    text-align: center;
    line-height: 1;
}
```

### **8. Barras de Progreso**
```css
/* Barra de Progreso */
.progress {
    background: #E5E5EA;
    border-radius: 10px;
    height: 8px;
    overflow: hidden;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
    background: linear-gradient(90deg, #007AFF 0%, #4DA3FF 100%);
}

.progress-bar.bg-success {
    background: linear-gradient(90deg, #34C759 0%, #30D158 100%);
}

.progress-bar.bg-warning {
    background: linear-gradient(90deg, #FF9500 0%, #FF6B00 100%);
}

.progress-bar.bg-danger {
    background: linear-gradient(90deg, #FF3B30 0%, #D70015 100%);
}

/* Texto de Progreso */
.progress-text {
    font-size: 12px;
    font-weight: 600;
    color: #8E8E93;
    margin-top: 4px;
}
```

### **9. Iconos y Elementos Visuales**
```css
/* Iconos */
.icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.icon-primary {
    background: rgba(0, 122, 255, 0.1);
    color: #007AFF;
}

.icon-success {
    background: rgba(52, 199, 89, 0.1);
    color: #34C759;
}

.icon-warning {
    background: rgba(255, 149, 0, 0.1);
    color: #FF9500;
}

.icon-danger {
    background: rgba(255, 59, 48, 0.1);
    color: #FF3B30;
}

/* Iconos de Cámara */
.camera-icon {
    color: #FF3B30;
    font-size: 16px;
    margin-right: 4px;
}

/* Iconos de Análisis */
.analysis-icon {
    color: #007AFF;
    font-size: 14px;
    margin-right: 4px;
}
```

### **10. Modales**
```css
/* Modal */
.modal-content {
    background: #FFFFFF;
    border: none;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
    border-bottom: 1px solid #E5E5EA;
    padding: 20px 24px;
}

.modal-title {
    font-size: 20px;
    font-weight: 600;
    color: #1D1D1F;
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-title i {
    color: #007AFF;
    font-size: 22px;
}

.modal-body {
    padding: 24px;
    background: #FFFFFF;
}

.modal-footer {
    background: #F2F2F7;
    border-top: 1px solid #E5E5EA;
    padding: 20px 24px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

/* Botón de Cerrar */
.btn-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #8E8E93;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.btn-close:hover {
    background: rgba(0, 0, 0, 0.1);
    color: #1D1D1F;
}
```

---

## 📱 **RESPONSIVE DESIGN**

### **Breakpoints**
```css
/* Mobile First */
--breakpoint-xs: 0px;
--breakpoint-sm: 576px;
--breakpoint-md: 768px;
--breakpoint-lg: 992px;
--breakpoint-xl: 1200px;
--breakpoint-xxl: 1400px;
```

### **Contenedores Responsivos**
```css
/* Contenedor Principal */
.container-fluid {
    padding: 0 16px;
}

@media (min-width: 768px) {
    .container-fluid {
        padding: 0 24px;
        max-width: 720px;
        margin: 0 auto;
    }
}

@media (min-width: 992px) {
    .container-fluid {
        max-width: 960px;
        padding: 0 32px;
    }
}

@media (min-width: 1200px) {
    .container-fluid {
        max-width: 1140px;
        padding: 0 40px;
    }
}
```

### **Tabla Responsiva**
```css
/* Tabla Responsiva */
.table-responsive {
    border-radius: 12px;
    overflow: hidden;
}

@media (max-width: 768px) {
    .table {
        font-size: 14px;
    }
    
    .table th,
    .table td {
        padding: 12px 8px;
    }
    
    .btn-group .btn {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .card-body {
        padding: 16px;
    }
    
    .filters-body {
        padding: 16px;
    }
}
```

---

## 🎭 **ANIMACIONES Y TRANSICIONES**

### **Transiciones Base**
```css
/* Transiciones Globales */
* {
    transition: all 0.2s ease;
}

/* Transiciones Específicas */
.fast-transition {
    transition: all 0.15s ease;
}

.medium-transition {
    transition: all 0.3s ease;
}

.slow-transition {
    transition: all 0.5s ease;
}
```

### **Animaciones Personalizadas**
```css
/* Animación de Pulso */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(52, 199, 89, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(52, 199, 89, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(52, 199, 89, 0);
    }
}

/* Animación de Slide */
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Animación de Fade */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Animación de Loading */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #E5E5EA;
    border-top: 2px solid #007AFF;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
```

### **Estados de Hover y Active**
```css
/* Estados Interactivos */
.interactive-element {
    transition: all 0.2s ease;
}

.interactive-element:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.interactive-element:active {
    transform: translateY(0);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

/* Hover en Filas de Tabla */
.table tbody tr:hover {
    background: rgba(0, 122, 255, 0.02);
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
```

---

## 🔧 **UTILIDADES CSS**

### **Espaciado**
```css
/* Márgenes */
.m-0 { margin: 0; }
.m-1 { margin: 4px; }
.m-2 { margin: 8px; }
.m-3 { margin: 16px; }
.m-4 { margin: 24px; }
.m-5 { margin: 32px; }

/* Padding */
.p-0 { padding: 0; }
.p-1 { padding: 4px; }
.p-2 { padding: 8px; }
.p-3 { padding: 16px; }
.p-4 { padding: 24px; }
.p-5 { padding: 32px; }

/* Gap */
.gap-1 { gap: 4px; }
.gap-2 { gap: 8px; }
.gap-3 { gap: 16px; }
.gap-4 { gap: 24px; }
.gap-5 { gap: 32px; }
```

### **Flexbox**
```css
/* Flexbox Utilities */
.d-flex { display: flex; }
.flex-column { flex-direction: column; }
.flex-row { flex-direction: row; }
.justify-center { justify-content: center; }
.justify-between { justify-content: space-between; }
.justify-end { justify-content: flex-end; }
.align-center { align-items: center; }
.align-start { align-items: flex-start; }
.align-end { align-items: flex-end; }
.flex-1 { flex: 1; }
.flex-wrap { flex-wrap: wrap; }
```

### **Texto**
```css
/* Texto */
.text-center { text-align: center; }
.text-left { text-align: left; }
.text-right { text-align: right; }
.text-uppercase { text-transform: uppercase; }
.text-bold { font-weight: 700; }
.text-medium { font-weight: 500; }
.text-light { font-weight: 300; }

/* Colores de Texto */
.text-primary { color: #007AFF; }
.text-secondary { color: #8E8E93; }
.text-success { color: #34C759; }
.text-warning { color: #FF9500; }
.text-error { color: #FF3B30; }
.text-dark { color: #1D1D1F; }
.text-light { color: #FFFFFF; }
```

### **Bordes y Sombras**
```css
/* Bordes */
.border-radius-sm { border-radius: 8px; }
.border-radius-md { border-radius: 12px; }
.border-radius-lg { border-radius: 16px; }
.border-radius-full { border-radius: 50%; }

/* Sombras */
.shadow-sm { box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); }
.shadow-md { box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12); }
.shadow-lg { box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16); }
```

---

## 🎨 **ESTADOS Y FEEDBACK**

### **Estados de Carga**
```css
/* Loading State */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}
```

### **Estados de Error**
```css
/* Error State */
.error-state {
    background: rgba(255, 59, 48, 0.1);
    border: 1px solid rgba(255, 59, 48, 0.2);
    border-radius: 8px;
    padding: 16px;
    color: #FF3B30;
    display: flex;
    align-items: center;
    gap: 12px;
}

.error-icon {
    color: #FF3B30;
    font-size: 20px;
    flex-shrink: 0;
}
```

### **Estados de Éxito**
```css
/* Success State */
.success-state {
    background: rgba(52, 199, 89, 0.1);
    border: 1px solid rgba(52, 199, 89, 0.2);
    border-radius: 8px;
    padding: 16px;
    color: #34C759;
    display: flex;
    align-items: center;
    gap: 12px;
}

.success-icon {
    color: #34C759;
    font-size: 20px;
    flex-shrink: 0;
}
```

### **Estados Vacíos**
```css
/* Empty State */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #8E8E93;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h5 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1D1D1F;
}

.empty-state p {
    font-size: 15px;
    margin: 0;
}
```

---

## 📋 **CHECKLIST DE IMPLEMENTACIÓN**

### **Fase 1: Base (Completado)**
- [x] Configurar variables CSS globales
- [x] Implementar tipografía del sistema
- [x] Establecer sistema de espaciado
- [x] Configurar paleta de colores

### **Fase 2: Componentes (En Progreso)**
- [x] Header/Navigation
- [x] Tarjetas de contenido
- [x] Botones
- [x] Tablas
- [x] Filtros
- [x] Badges y estados
- [x] Barras de progreso
- [x] Modales

### **Fase 3: Responsive (Pendiente)**
- [ ] Breakpoints móviles
- [ ] Grid system
- [ ] Contenedores adaptativos
- [ ] Navegación móvil

### **Fase 4: Interacciones (Pendiente)**
- [ ] Animaciones de transición
- [ ] Estados hover/active
- [ ] Feedback visual
- [ ] Loading states

### **Fase 5: Optimización (Pendiente)**
- [ ] Performance CSS
- [ ] Accesibilidad
- [ ] Testing cross-browser
- [ ] Documentación final

---

## 🚀 **IMPLEMENTACIÓN RÁPIDA**

### **CSS Base (app.scss)**
```scss
// Variables globales
:root {
    // Colores
    --apple-blue: #007AFF;
    --apple-blue-dark: #0056CC;
    --system-gray: #8E8E93;
    --system-gray-2: #AEAEB2;
    --system-gray-6: #F2F2F7;
    --success-green: #34C759;
    --error-red: #FF3B30;
    
    // Tipografía
    --font-size-body: 17px;
    --font-size-headline: 17px;
    --font-size-title: 22px;
    
    // Espaciado
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    
    // Bordes
    --border-radius-sm: 8px;
    --border-radius-md: 12px;
    --border-radius-lg: 16px;
}

// Reset y base
* {
    box-sizing: border-box;
    transition: all 0.2s ease;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Nunito', sans-serif;
    font-size: var(--font-size-body);
    line-height: 1.4;
    color: #1D1D1F;
    background: var(--system-gray-6);
    margin: 0;
    padding: 0;
}

// Componentes principales
.card {
    background: #FFFFFF;
    border-radius: var(--border-radius-lg);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    border: none;
    overflow: hidden;
}

.btn-primary {
    background: linear-gradient(135deg, var(--apple-blue) 0%, var(--apple-blue-dark) 100%);
    border: none;
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md) var(--spacing-lg);
    font-weight: 600;
    color: #FFFFFF;
    box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
    
    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 122, 255, 0.4);
    }
}

.table {
    background: #FFFFFF;
    border-radius: var(--border-radius-md);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    
    thead {
        background: linear-gradient(135deg, #F2F2F7 0%, #E5E5EA 100%);
    }
    
    tbody tr:hover {
        background: rgba(0, 122, 255, 0.02);
        transform: scale(1.01);
    }
}
```

---

## 🎯 **OBJETIVOS DE IMPLEMENTACIÓN**

1. **Experiencia de Usuario Consistente**: Diseño unificado en toda la plataforma
2. **Accesibilidad**: Cumplir con estándares WCAG 2.1
3. **Performance**: CSS optimizado y eficiente
4. **Mantenibilidad**: Código limpio y bien documentado
5. **Escalabilidad**: Fácil de extender y modificar
6. **Responsive**: Funciona perfectamente en todos los dispositivos

---

*Esta guía de estilos debe ser implementada gradualmente, comenzando por los componentes más utilizados y expandiéndose al resto de la plataforma. El diseño inspirado en Apple proporciona una experiencia de usuario moderna, limpia y profesional.*
