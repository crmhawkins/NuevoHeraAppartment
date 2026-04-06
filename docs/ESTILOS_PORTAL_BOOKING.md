# Guía de Estilos - Portal de Reservas (Estilo Booking.com)

## Paleta de Colores

### Colores Principales
- **Azul Primario (Booking Blue)**: `#003580` - Color principal de marca, botones principales
- **Azul Hover**: `#004585` - Estados hover de botones y enlaces
- **Azul Claro**: `#E9F0FF` - Fondos sutiles, highlights
- **Amarillo/Dorado**: `#FFB700` - Destacados, badges, ratings
- **Verde Éxito**: `#0D7377` - Confirmaciones, disponibilidad
- **Rojo Alerta**: `#EB5757` - Alertas, errores
- **Gris Oscuro**: `#333333` - Texto principal
- **Gris Medio**: `#666666` - Texto secundario
- **Gris Claro**: `#E8E8E8` - Bordes, separadores
- **Gris Muy Claro**: `#F5F5F5` - Fondos de cards
- **Blanco**: `#FFFFFF` - Fondos principales

### Colores de Estados
- **Hover**: `#004585` (azul más claro)
- **Activo**: `#002856` (azul más oscuro)
- **Deshabilitado**: `#CCCCCC`

## Tipografía

### Fuentes
- **Principal**: `BlinkMacSystemFont, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`
- **Tamaños**:
  - H1: `32px` (2rem) - Títulos principales
  - H2: `24px` (1.5rem) - Subtítulos
  - H3: `20px` (1.25rem) - Títulos de sección
  - Body: `16px` (1rem) - Texto normal
  - Small: `14px` (0.875rem) - Texto secundario
  - XSmall: `12px` (0.75rem) - Captions, badges

### Pesos
- **Normal**: 400
- **Semi-bold**: 600
- **Bold**: 700

## Componentes

### Botones

#### Botón Primario (CTA Principal)
```css
background: #003580
color: #FFFFFF
padding: 12px 24px
border-radius: 6px
font-weight: 600
font-size: 16px
border: none
transition: background 0.2s ease
```

#### Botón Secundario
```css
background: #FFFFFF
color: #003580
padding: 12px 24px
border-radius: 6px
font-weight: 600
font-size: 16px
border: 2px solid #003580
```

#### Botón Texto
```css
background: transparent
color: #003580
padding: 8px 16px
font-weight: 600
text-decoration: underline
```

### Cards de Propiedades

#### Estructura
- **Imagen**: 300x200px (ratio 3:2), border-radius: 8px en esquina superior
- **Contenido**: Padding 20px
- **Sombra**: `box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1)`
- **Hover**: `box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15)`
- **Border-radius**: `8px`
- **Border**: `1px solid #E8E8E8`

#### Layout
- Cards horizontales (flexbox o grid)
- Imagen a la izquierda
- Contenido a la derecha
- Espaciado entre cards: 24px

### Badges y Tags

#### Badge de Rating
```css
background: #003580
color: #FFFFFF
padding: 4px 8px
border-radius: 6px
font-size: 12px
font-weight: 600
```

#### Badge Destacado
```css
background: #FFB700
color: #333333
padding: 4px 8px
border-radius: 4px
font-size: 11px
font-weight: 600
```

### Iconos
- **Tamaño**: 16px-20px
- **Color**: `#666666` (gris medio)
- **Espaciado**: 8px entre icono y texto

### Espaciado

#### Grid System
- **Gutter**: 24px (espaciado entre columnas)
- **Padding Cards**: 20px interno
- **Margin Sections**: 40px entre secciones principales

#### Espaciado Vertical
- **XS**: 8px
- **SM**: 16px
- **MD**: 24px
- **LG**: 32px
- **XL**: 40px

### Formularios

#### Input Fields
```css
border: 2px solid #E8E8E8
border-radius: 6px
padding: 12px 16px
font-size: 16px
color: #333333
background: #FFFFFF
transition: border-color 0.2s ease
```

#### Input Focus
```css
border-color: #003580
outline: none
box-shadow: 0 0 0 3px rgba(0, 53, 128, 0.1)
```

### Layout Principal

#### Estructura
- **Contenedor**: max-width: 1200px, margin: 0 auto
- **Padding lateral**: 24px (responsive: 16px en móvil)
- **Background**: #FFFFFF
- **Fondo página**: #F5F5F5 (gris muy claro)

### Animaciones y Transiciones

#### Transiciones Estándar
```css
transition: all 0.2s ease
```

#### Efectos Hover
- Botones: background más claro
- Cards: sombra más pronunciada, transform: translateY(-2px)
- Links: color más oscuro

### Responsive Breakpoints

- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

### Características Específicas Booking.com

1. **Clean Design**: Mucho espacio en blanco, diseño limpio
2. **Confianza**: Badges de confianza visibles (ej: "Genial, 9.0")
3. **Imágenes**: Grandes, de alta calidad, primer plano del contenido
4. **Precios**: Destacados, grandes, con formato claro (€XXX)
5. **Call to Action**: Botones grandes y visibles
6. **Breadcrumbs**: Navegación clara
7. **Filtros**: Sidebar izquierdo (desktop) o collapsible (mobile)





