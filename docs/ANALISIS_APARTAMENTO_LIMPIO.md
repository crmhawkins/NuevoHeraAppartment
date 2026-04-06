# 📋 Análisis: Vista de Apartamento Limpio para Clientes

## 🎯 Objetivo
Permitir que los clientes vean las fotos del apartamento limpio el día de su entrada, accediendo mediante un enlace enviado por WhatsApp.

---

## 🔍 Análisis de la Lógica Propuesta

### ✅ Lógica Correcta: "Última limpieza anterior al día de entrada"

**Ventajas:**
- ✅ Lógica clara y directa
- ✅ Asegura que se muestre la limpieza más reciente antes de la entrada
- ✅ No depende de que la limpieza esté vinculada directamente a la reserva

**Consideraciones:**
1. **Estado de la limpieza**: Debe estar completada (`status_id = 3`)
2. **Fechas**: Usar `fecha_fin` o `fecha_comienzo` para comparar con `fecha_entrada` de la reserva
3. **Apartamento**: Debe ser del mismo apartamento que la reserva
4. **Fotos**: La limpieza debe tener fotos asociadas (tabla `photos` con `limpieza_id`)

**Mejora sugerida:**
- Si no hay limpieza anterior, buscar la más reciente del apartamento (aunque sea posterior)
- O mostrar un mensaje informativo si no hay fotos disponibles

---

## 📊 Estructura de Datos

### Tablas Involucradas

1. **`reservas`**
   - `id` - ID de la reserva
   - `token` - Token único para acceso público
   - `apartamento_id` - ID del apartamento
   - `fecha_entrada` - Fecha de entrada del cliente
   - `cliente_id` - ID del cliente

2. **`apartamento_limpieza`**
   - `id` - ID de la limpieza
   - `apartamento_id` - ID del apartamento
   - `reserva_id` - ID de la reserva (opcional, puede ser NULL)
   - `fecha_comienzo` - Fecha de inicio de limpieza
   - `fecha_fin` - Fecha de fin de limpieza
   - `status_id` - Estado (2 = en proceso, 3 = completada)
   - `tipo_limpieza` - Tipo de limpieza (apartamento, zona_comun)

3. **`photos`**
   - `id` - ID de la foto
   - `limpieza_id` - ID de la limpieza (FK a `apartamento_limpieza`)
   - `url` - Ruta de la imagen (ej: `images/foto.jpg`)
   - `photo_categoria_id` - Categoría de la foto
   - `descripcion` - Descripción opcional

### Relaciones Eloquent

```php
// Reserva
Reserva->apartamento() // BelongsTo Apartamento
Reserva->cliente()     // BelongsTo Cliente

// ApartamentoLimpieza
ApartamentoLimpieza->apartamento() // BelongsTo Apartamento
ApartamentoLimpieza->fotos()        // HasMany Photo
ApartamentoLimpieza->reserva()     // BelongsTo Reserva (opcional)

// Photo
Photo->apartamentoLimpieza() // BelongsTo ApartamentoLimpieza
```

---

## 🛠️ Implementación Requerida

### 1. **Ruta Pública** (`routes/web.php`)

```php
Route::get('/apartamento-limpio/{token}', [ApartamentoLimpioController::class, 'show'])
    ->name('apartamento.limpio.show');
```

**Características:**
- ✅ Pública (sin autenticación)
- ✅ Acceso mediante token único de la reserva
- ✅ No requiere login

---

### 2. **Controlador** (`app/Http/Controllers/ApartamentoLimpioController.php`)

#### Método `show($token)`

**Lógica:**
1. **Validar token y obtener reserva**
   ```php
   $reserva = Reserva::where('token', $token)->firstOrFail();
   ```

2. **Obtener apartamento**
   ```php
   $apartamento = $reserva->apartamento;
   ```

3. **Buscar última limpieza anterior al día de entrada**
   ```php
   $limpieza = ApartamentoLimpieza::where('apartamento_id', $reserva->apartamento_id)
       ->where('tipo_limpieza', 'apartamento')
       ->where('status_id', 3) // Completada
       ->where(function($query) use ($reserva) {
           $query->where('fecha_fin', '<', $reserva->fecha_entrada)
                 ->orWhere('fecha_comienzo', '<', $reserva->fecha_entrada);
       })
       ->orderBy('fecha_fin', 'desc')
       ->orderBy('fecha_comienzo', 'desc')
       ->with('fotos')
       ->first();
   ```

4. **Obtener fotos de la limpieza**
   ```php
   if ($limpieza) {
       $fotos = $limpieza->fotos()
           ->whereNotNull('url')
           ->orderBy('created_at', 'asc')
           ->get();
   }
   ```

5. **Retornar vista con datos**
   ```php
   return view('apartamento-limpio.show', compact('reserva', 'apartamento', 'limpieza', 'fotos'));
   ```

**Casos especiales:**
- Si no hay limpieza: mostrar mensaje informativo
- Si no hay fotos: mostrar mensaje indicando que no hay fotos disponibles
- Si el token es inválido: 404

---

### 3. **Vista** (`resources/views/apartamento-limpio/show.blade.php`)

**Características:**
- ✅ Diseño responsive y moderno (usar estilos apple-*)
- ✅ Galería de imágenes (lightbox o carousel)
- ✅ Información del apartamento
- ✅ Fecha de la limpieza
- ✅ Multiidioma (usar `__()` helper)
- ✅ Sin autenticación requerida

**Estructura sugerida:**
```html
- Header con logo
- Título: "Tu apartamento está listo"
- Información del apartamento
- Fecha de limpieza
- Galería de fotos (grid responsive)
- Footer con información de contacto
```

**Estilos:**
- Usar variables CSS del sistema (como en `dni/upload.blade.php`)
- Componentes apple-* si están disponibles
- Bootstrap para grid y responsive

---

### 4. **Integración WhatsApp** (Futuro)

**Cuando se implemente el envío automático:**

1. **Comando/Job para enviar WhatsApp el día de entrada**
   - Buscar reservas con `fecha_entrada = hoy`
   - Verificar que existe limpieza con fotos
   - Enviar mensaje con imagen y botón

2. **Estructura del mensaje WhatsApp:**
   ```
   📸 Tu apartamento está listo para tu llegada!
   
   [Imagen del apartamento limpio]
   
   Haz clic en el botón para ver todas las fotos:
   [Botón: "Ver Fotos del Apartamento"]
   ```

3. **URL del botón:**
   ```
   https://tu-dominio.com/apartamento-limpio/{token}
   ```

---

## 🔄 Flujo Completo

```
1. Limpieza completada → Fotos subidas a `photos` con `limpieza_id`
2. Día de entrada de la reserva → Sistema detecta reserva
3. Sistema busca última limpieza anterior a fecha_entrada
4. Sistema envía WhatsApp con:
   - Imagen destacada (primera foto de la limpieza)
   - Texto personalizado
   - Botón con enlace: /apartamento-limpio/{token}
5. Cliente hace clic en botón → Accede a vista pública
6. Vista muestra todas las fotos de la limpieza
```

---

## ✅ Checklist de Implementación

### Fase 1: Funcionalidad Básica
- [ ] Crear ruta `/apartamento-limpio/{token}`
- [ ] Crear controlador `ApartamentoLimpioController`
- [ ] Implementar lógica de búsqueda de limpieza
- [ ] Crear vista `apartamento-limpio/show.blade.php`
- [ ] Implementar galería de fotos
- [ ] Añadir traducciones multiidioma
- [ ] Probar con diferentes escenarios:
  - [ ] Reserva con limpieza anterior
  - [ ] Reserva sin limpieza
  - [ ] Limpieza sin fotos
  - [ ] Token inválido

### Fase 2: Mejoras y Optimizaciones
- [ ] Añadir lightbox para ver fotos en grande
- [ ] Optimizar carga de imágenes (lazy loading)
- [ ] Añadir metadatos SEO
- [ ] Añadir analytics/tracking (opcional)

### Fase 3: Integración WhatsApp (Futuro)
- [ ] Crear comando/job para envío automático
- [ ] Implementar envío de mensaje con imagen
- [ ] Implementar botón de acción en WhatsApp
- [ ] Probar flujo completo

---

## 🧪 Casos de Prueba

### Caso 1: Reserva con limpieza anterior
- **Input**: Token de reserva con `fecha_entrada = 2025-11-07`
- **Limpieza**: `fecha_fin = 2025-11-06`, `status_id = 3`, con 5 fotos
- **Expected**: Mostrar las 5 fotos de la limpieza

### Caso 2: Reserva sin limpieza anterior
- **Input**: Token de reserva con `fecha_entrada = 2025-11-07`
- **Limpieza**: No existe limpieza anterior
- **Expected**: Mostrar mensaje: "No hay fotos disponibles de la limpieza previa"

### Caso 3: Limpieza sin fotos
- **Input**: Token de reserva con limpieza anterior
- **Limpieza**: Existe pero sin fotos en `photos`
- **Expected**: Mostrar mensaje: "La limpieza no tiene fotos disponibles"

### Caso 4: Token inválido
- **Input**: Token que no existe en BD
- **Expected**: Error 404

---

## 📝 Notas Técnicas

1. **Seguridad:**
   - El token es único y aleatorio, suficiente para acceso público
   - No exponer información sensible (precios, datos internos)
   - Validar que la reserva existe y está activa

2. **Performance:**
   - Usar `with('fotos')` para eager loading
   - Optimizar consulta de limpieza con índices
   - Lazy loading de imágenes en la vista

3. **UX:**
   - Vista clara y simple
   - Fácil navegación entre fotos
   - Información relevante visible

---

## 🎨 Diseño Sugerido

Inspiración: Similar a `dni/upload.blade.php` o `gracias.blade.php`
- Header con logo
- Contenedor centrado
- Grid de imágenes responsive
- Footer minimalista




