# 🏢 Guía de Estilos - Plataforma Admin

## 📋 Descripción General

Esta guía define el sistema de diseño unificado para toda la plataforma administrativa, incluyendo las rutas `/clientes`, `/apartamentos`, `/empleados`, `/limpiezas`, y todas las funcionalidades del panel de administración.

## 🎨 Paleta de Colores

### Colores Principales del Sistema
```scss
$color-primero: #0F1739;      /* Azul oscuro - Color principal */
$color-segundo: #BBB6D4;      /* Lavanda claro - Color secundario */
$color-tercero: #3B3F64;      /* Azul medio - Color terciario */
$color-cuarto: #e8eef7;       /* Azul muy claro - Fondo */
$color-quinto: #757191;       /* Gris azulado */
$color-sexto: #4e598d;        /* Azul grisáceo */
$color-septimo: #918cac;      /* Lavanda medio */
$color-octavo: #4862d3;       /* Azul brillante */
```

### Aplicación de Colores
```css
.bg-color-primero {
    background-color: #0F1739;    /* Sidebar, headers principales */
    color: white;
}

.bg-color-segundo {
    background-color: #BBB6D4;    /* Fondos alternativos */
    color: #0F1739;
}

.bg-color-tercero {
    background-color: #3B3F64;    /* Elementos destacados */
    color: white;
}
```

### Colores de Estado
```css
--success: #28a745              /* Verde - Éxito */
--warning: #ffc107              /* Amarillo - Advertencia */
--danger: #dc3545               /* Rojo - Error */
--info: #17a2b8                 /* Azul info */
--primary: #667eea              /* Azul primario Bootstrap */
```

## 🔘 Sistema de Botones

### Botones Principales (`.btn`)

#### Estilo Base
```css
.btn {
    border-radius: 8px;                    /* Bordes redondeados */
    font-weight: 500;
    transition: all 0.2s ease-in-out;
    border: none;
    padding: 0.5rem 1rem;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
```

#### Variantes de Botones

##### Botón Primario (`.btn-primary`)
- **Uso**: Acciones principales como "Nuevo Cliente", "Guardar", "Filtrar"
- **Estilo**: Fondo azul con texto blanco
- **Hover**: Elevación y sombra

##### Botón Secundario (`.btn-outline-secondary`)
- **Uso**: Acciones secundarias como "Limpiar", "Cancelar"
- **Estilo**: Borde gris con texto gris
- **Hover**: Fondo gris con texto blanco

##### Botón de Guardar (`.btn-guardar`)
```css
.btn-guardar {
    --bs-btn-color: #fff;
    --bs-btn-bg: #0F1739;           /* Color principal */
    --bs-btn-border-color: #0F1739;
    --bs-btn-hover-color: #0F1739;
    --bs-btn-hover-bg: #BBB6D4;     /* Color secundario */
    --bs-btn-hover-border-color: #BBB6D4;
    transition: all 0.5s ease-in-out;
}
```

##### Botón de Terminar (`.btn-terminar`)
```css
.btn-terminar {
    --bs-btn-color: #fff;
    --bs-btn-bg: #757191;           /* Color quinto */
    --bs-btn-border-color: #757191;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #3B3F64;     /* Color terciario */
    --bs-btn-hover-border-color: #757191;
}
```

### Botones de Acción
```css
.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.125rem;
}
```

## 🎴 Sistema de Tarjetas

### Tarjetas Principales (`.card`)
```css
.card {
    background: #FFFFFF;
    border-radius: 8px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border: none;
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}
```

### Headers de Tarjetas (`.card-header`)
```css
.card-header {
    background-color: #FFFFFF;
    border: none;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e3e6f0;
}

.card-header h5 {
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 0;
}
```

### Cuerpo de Tarjetas (`.card-body`)
```css
.card-body {
    padding: 1.25rem;
    background: #FFFFFF;
}
```

### Tarjetas de Estadísticas
```css
.card.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.card.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.card.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
}

.card.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}
```

## 📊 Componentes de Datos

### Tablas (`.table`)
```css
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e3e6f0;
    padding: 1rem;
    background-color: #f8f9fc;
}

.table td {
    border-bottom: 1px solid #f8f9fa;
    padding: 1rem;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fc;
}
```

### Formularios
```css
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    transition: all 0.2s ease-in-out;
    padding: 0.5rem 0.75rem;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-label {
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 0.5rem;
}

.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #e3e6f0;
    color: #6c757d;
    border-radius: 8px 0 0 8px;
}
```

### Badges y Estados
```css
.badge {
    font-size: 0.75em;
    font-weight: 500;
    padding: 0.5em 0.75em;
    border-radius: 6px;
}

.badge.bg-primary {
    background-color: #667eea !important;
}
```

## 🎨 Elementos de Interfaz

### Sidebar (`.navbar-dark.bg-color-primero`)
```css
#mainNavbar {
    background-color: #0F1739 !important;
    color: white;
    z-index: 55;
}

.navbar-nav .nav-link {
    color: rgba(255, 255, 255, 0.8);
    transition: color 0.2s ease;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
    color: white;
}

.dropdown-menu {
    background-color: white;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.dropdown-item {
    color: #5a5c69;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f8f9fc;
    color: #0F1739;
}
```

### Headers de Página
```css
.h1, .h2, .h3, .h4, .h5, .h6 {
    color: #5a5c69;
    font-weight: 600;
}

.h2 {
    font-size: 1.75rem;
    margin-bottom: 0.5rem;
}

.text-muted {
    color: #858796 !important;
}
```

### Iconos
```css
.fas, .fa, .fab {
    margin-right: 0.5rem;
}

.text-primary {
    color: #667eea !important;
}

.text-muted {
    color: #858796 !important;
}
```

## 📱 Responsive Design

### Breakpoints
```css
/* Mobile */
@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between .btn {
        width: 100%;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
}

/* Small Mobile */
@media (max-width: 576px) {
    .container-fluid {
        padding: 0.5rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
}
```

### Grid System
```css
.row {
    margin-left: -0.75rem;
    margin-right: -0.75rem;
}

.col-lg-3, .col-md-6, .col-12 {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}
```

## 🔄 Estados y Transiciones

### Hover Effects
```css
.card:hover {
    transform: translateY(-2px);
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.table-hover tbody tr:hover {
    background-color: #f8f9fc;
}
```

### Transiciones
```css
.card {
    transition: transform 0.2s ease-in-out;
}

.btn {
    transition: all 0.2s ease-in-out;
}

.form-control, .form-select {
    transition: all 0.2s ease-in-out;
}
```

## 📋 Tipografía

### Jerarquía de Textos
- **Títulos principales**: `font-size: 1.75rem`, `font-weight: 600`
- **Subtítulos**: `font-size: 1rem`, `font-weight: 400`
- **Texto del cuerpo**: `font-size: 0.9rem`, `font-weight: 400`
- **Etiquetas**: `font-size: 0.875rem`, `font-weight: 600`

### Familias de Fuentes
```css
font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
```

## 🎯 Principios de Diseño

### 1. Consistencia Visual
- **Colores**: Usar siempre la paleta definida
- **Espaciado**: Mantener consistencia con padding y margins
- **Bordes**: `8px` para botones y tarjetas
- **Sombras**: Sombra estándar para tarjetas

### 2. Jerarquía Visual
- **Headers**: Color principal (#0F1739)
- **Botones principales**: Azul Bootstrap (#667eea)
- **Botones secundarios**: Outline gris
- **Estados**: Colores semánticos (success, warning, danger)

### 3. Responsive Design
- **Mobile-first**: Diseño optimizado para móviles
- **Breakpoints**: 768px y 576px
- **Adaptación**: Elementos se ajustan al tamaño de pantalla

### 4. Accesibilidad
- **Contraste**: Colores con suficiente contraste
- **Tamaños**: Botones mínimos de 44px en móvil
- **Estados**: Hover y active claramente diferenciados

## 🚀 Implementación

### 1. Layout Base
```html
@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <!-- Contenido aquí -->
</div>
@endsection
```

### 2. Estructura de Tarjetas
```html
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0 fw-semibold text-dark">
            <i class="fas fa-users me-2 text-primary"></i>
            Título de la Tarjeta
        </h5>
    </div>
    <div class="card-body">
        <!-- Contenido -->
    </div>
</div>
```

### 3. Botones
```html
<!-- Botón principal -->
<button class="btn btn-primary">
    <i class="fas fa-plus me-2"></i>Acción Principal
</button>

<!-- Botón secundario -->
<button class="btn btn-outline-secondary">
    <i class="fas fa-times me-2"></i>Acción Secundaria
</button>
```

### 4. Tablas
```html
<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th class="border-0">
                    <i class="fas fa-user me-1 text-primary"></i>
                    Columna
                </th>
            </tr>
        </thead>
        <tbody>
            <!-- Filas de datos -->
        </tbody>
    </table>
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
            <h3>Procesando...</h3>
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
    background: rgba(15, 23, 57, 0.95);  /* Color principal con transparencia */
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
    border-radius: 15px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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
    border: 4px solid rgba(15, 23, 57, 0.2);  /* Color principal */
    border-top: 4px solid #0F1739;              /* Color principal */
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

.loading-text h3 {
    color: #0F1739;              /* Color principal */
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
    background: rgba(15, 23, 57, 0.1);  /* Color principal */
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0F1739, #4862d3);  /* Colores del sistema */
    border-radius: 4px;
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    color: #0F1739;              /* Color principal */
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
function showLoadingOverlay(message = 'Procesando...') {
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

### Uso del Overlay en Formularios y AJAX
```javascript
// Función para guardar cliente con overlay
function guardarCliente() {
    showLoadingOverlay('Guardando cliente...');
    
    setTimeout(() => {
        const formulario = document.getElementById('formCliente');
        if (formulario) {
            formulario.submit();
        } else {
            hideLoadingOverlay();
            mostrarModalError('Error al guardar el cliente.');
        }
    }, 500);
}

// Función para peticiones AJAX con overlay
function cargarDatos() {
    showLoadingOverlay('Cargando datos...');
    
    fetch('/api/datos')
        .then(response => response.json())
        .then(data => {
            hideLoadingOverlay();
            procesarDatos(data);
        })
        .catch(error => {
            hideLoadingOverlay();
            mostrarModalError('Error al cargar los datos.');
        });
}

// Función para eliminar con overlay
function eliminarCliente(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este cliente?')) {
        showLoadingOverlay('Eliminando cliente...');
        
        fetch(`/clientes/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => {
            hideLoadingOverlay();
            if (response.ok) {
                location.reload();
            } else {
                mostrarModalError('Error al eliminar el cliente.');
            }
        })
        .catch(error => {
            hideLoadingOverlay();
            mostrarModalError('Error de conexión.');
        });
    }
}
```

## 📝 Notas Importantes

- **NO cambiar** los colores sin consultar esta guía
- **Mantener** la consistencia en toda la plataforma admin
- **Usar** las variables SCSS definidas
- **Respetar** los border-radius establecidos (8px)
- **Seguir** la jerarquía de botones definida
- **Implementar** responsive design en todos los componentes
- **Implementar** `showLoadingOverlay` en TODAS las peticiones AJAX y envíos de formularios
- **Usar** mensajes descriptivos en el overlay según la acción que se esté realizando
- **Aplicar** el overlay en: creación, edición, eliminación, búsquedas, filtros y navegación

---

*Esta guía debe ser consultada antes de realizar cambios en el diseño de la plataforma administrativa.*
