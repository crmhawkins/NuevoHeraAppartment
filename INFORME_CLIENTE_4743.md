# 📋 Informe de Revisión - Cliente ID 4743

**Fecha:** 2025-01-10  
**Reserva ID:** 5036

## 🔍 Situación Actual

### 📸 Fotos Registradas en Base de Datos

**Total de fotos encontradas:** 1

| ID | Categoría | Tipo | URL | Fecha Creación |
|----|-----------|------|-----|----------------|
| 18285 | 13 | Frontal | `imagesCliente/1765364328_4743_FrontalDNI_69395268d732a.jpg` | 2025-12-10 10:58:48 |

**⚠️ PROBLEMA DETECTADO:**
- Solo existe **1 foto** (frontal, categoría 13)
- **FALTA la foto trasera** (categoría 14)
- El usuario reporta que solo ve la trasera, pero en BD solo hay frontal registrada

### 📝 Datos del Cliente para MIR

**Datos completos:**
- ✅ **Nombre:** ANDRES
- ✅ **Apellido1:** MARTIN
- ✅ **Número Identificación:** 29661A6D1
- ✅ **Sexo:** Masculino
- ✅ **Nacionalidad:** ESP
- ✅ **Teléfono:** 637397801
- ✅ **Email:** davidmp4482@gmail.com
- ✅ **Código Postal:** 29603
- ✅ **Dirección:** Urb hacienda cortes calle delfin 10
- ✅ **Localidad:** Marbella
- ✅ **Provincia:** Málaga
- ✅ **data_dni:** 1 (marcado como completado)

**❌ DATOS FALTANTES PARA MIR:**
- ❌ **fecha_nacimiento:** NULL (OBLIGATORIO)
- ❌ **fecha_expedicion_doc:** NULL (OBLIGATORIO)

## 🔎 Análisis del Problema

### Hipótesis sobre las fotos:

1. **La foto "trasera" se subió pero se guardó incorrectamente como "frontal"**
   - El colega pudo haber subido la trasera en el campo frontal
   - El sistema la guardó con categoría 13 (frontal) cuando debería ser 14 (trasera)

2. **La foto física existe en producción pero no está registrada en BD**
   - El archivo físico podría estar en `public/imagesCliente/` en producción
   - Pero no hay registro en la tabla `photos` con categoría 14

3. **Problema de visualización**
   - El usuario ve solo la trasera, pero en BD solo hay frontal
   - Podría haber un problema de mapeo entre categorías y visualización

### Campos MIR Requeridos (según ReservaPagoController):

Según el código en `app/Http/Controllers/ReservaPagoController.php` línea 470-498, los campos obligatorios para MIR son:

```php
'nombre' => 'Nombre',
'apellido1' => 'Primer Apellido',
'fecha_nacimiento' => 'Fecha de Nacimiento',  // ❌ FALTA
'nacionalidad' => 'Nacionalidad',
'tipo_documento' => 'Tipo de Documento',
'num_identificacion' => 'Número de Identificación',
'fecha_expedicion_doc' => 'Fecha de Expedición del Documento',  // ❌ FALTA
'sexo' => 'Sexo',
'email' => 'Email',
'telefono_movil' => 'Teléfono Móvil',
'provincia' => 'Provincia',
```

## 📊 Resumen

| Aspecto | Estado | Observaciones |
|---------|--------|---------------|
| **Fotos** | ⚠️ Incompleto | Solo 1 foto (frontal). Falta trasera. |
| **Datos MIR** | ❌ Incompleto | Faltan `fecha_nacimiento` y `fecha_expedicion_doc` |
| **Archivo físico** | ❓ Desconocido | No verificado en producción (solo en local) |

## 🎯 Recomendaciones

1. **Verificar en producción:**
   - Buscar archivos físicos en `public/imagesCliente/` que contengan "4743"
   - Verificar si hay una foto trasera que no esté registrada en BD

2. **Revisar logs:**
   - Buscar en logs de producción (2025-12-10 alrededor de las 10:58) para ver qué ocurrió al subir las fotos

3. **Completar datos MIR:**
   - Solicitar `fecha_nacimiento` y `fecha_expedicion_doc` al cliente
   - Estos campos son obligatorios para el envío al MIR

4. **Verificar categoría de foto:**
   - Si existe la foto física pero está mal categorizada, corregir el registro en BD
   - Si falta la foto trasera, solicitar al cliente que la suba nuevamente

## 🐛 PROBLEMA CRÍTICO DETECTADO

### ⚠️ Bug en la Validación de `data_dni`

**El sistema marca `data_dni = true` SIN validar campos obligatorios para MIR.**

#### Lugares donde se marca `data_dni = true` sin validación:

1. **Línea 731** - `saveAdditionalData()`:
   ```php
   // Marcar como completado si es cliente
   if ($personaTipo === 'cliente') {
       $persona->update(['data_dni' => true]);  // ❌ SIN VALIDACIÓN
   }
   ```

2. **Línea 1707** - `saveExtractedData()`:
   ```php
   $updateData = [
       'fecha_nacimiento' => $data['fecha_nacimiento'] ?? $persona->fecha_nacimiento,  // Puede ser NULL
       'fecha_expedicion_doc' => $data['fecha_expedicion'] ?? $persona->fecha_expedicion_doc,  // Puede ser NULL
       'data_dni' => true  // ❌ Se marca aunque los campos anteriores sean NULL
   ];
   ```

3. **Línea 981** - Similar sin validación

#### Función de validación existente pero NO utilizada:

Existe `verificarDatosCompletos()` (línea 113) que SÍ valida:
- nombre
- apellido1
- num_identificacion
- **fecha_nacimiento** ✅
- **fecha_expedicion_doc** ✅
- sexo
- nacionalidadStr

**PERO esta función NO se llama antes de marcar `data_dni = true`.**

### Consecuencia:

El cliente puede completar el proceso y marcar `data_dni = 1` incluso cuando faltan campos obligatorios para MIR, lo que genera:
- ✅ `data_dni = 1` (marcado como completado)
- ❌ `fecha_nacimiento = NULL` (falta)
- ❌ `fecha_expedicion_doc = NULL` (falta)
- ❌ No se puede enviar al MIR

## 🔧 Código de Referencia

- **Categorías de fotos:**
  - 13 = Frontal DNI
  - 14 = Trasera DNI

- **Controlador:** `app/Http/Controllers/DNIScannerController.php`
- **Línea 2113:** `$sides = ['front' => ['categoria' => 13, 'nombre' => 'FrontalDNI'], 'rear' => ['categoria' => 14, 'nombre' => 'TraseraDNI']];`
- **Función de validación (NO usada):** `verificarDatosCompletos()` línea 113

