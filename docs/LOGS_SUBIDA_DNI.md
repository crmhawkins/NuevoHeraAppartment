# Cómo buscar logs de subida de DNI

## Dónde se sube el DNI

Hay **dos flujos** de subida de documentos:

### 1. Formulario clásico (DNIController)

- **Ruta:** `POST /dni-user/store` → `DNIController::store()`
- **Vista:** formulario por token (`/dni-user/{token}`) con campos frontal/trasera o pasaporte.

### 2. Scanner / subida con cámara (DNIScannerController)

- **Rutas:**  
  - `GET /dni-scanner/{token}/upload` → formulario de subida  
  - `POST /dni-scanner/{token}/upload` → subida de archivos  
  - `POST /dni-scanner/{token}/process-single-image` → procesar una imagen  
  - `POST /dni-scanner/complete` → completar verificación
- **Vista:** `/dni-scanner/{token}` (cámara o subida de archivos).

---

## Mensajes de log que indican subida de DNI

### Formulario clásico (DNIController)

| Mensaje en log | Significado |
|----------------|-------------|
| `=== INICIO PROCESO SUBIDA DNI ===` | Entrada al proceso de subida (store) |
| `=== FIN PROCESO SUBIDA DNI EXITOSO ===` | Subida completada correctamente |
| `=== INICIO GUARDAR IMAGEN ===` | Se está guardando una imagen (frontal/trasera/pasaporte) |
| `Imagen comprimida y guardada exitosamente` | Imagen guardada en disco |
| `Actualizando estado de reserva y cliente` | Se va a marcar DNI entregado en reserva/cliente |

### Scanner / subida (DNIScannerController)

| Mensaje en log | Significado |
|----------------|-------------|
| `Mostrando formulario de subida de DNI` | Alguien abrió la pantalla de subida |
| `Imagen procesada exitosamente` | Una imagen se procesó bien |
| `dni_entregado actualizado en reserva - DNI subido` | Reserva marcada con DNI entregado |
| `dni_entregado actualizado en reserva - verificación completada` | Verificación DNI completada (scanner) |
| `Verificación de DNI completada - data_dni marcado como true` | Cliente con datos DNI completos para MIR |
| `Imágenes subidas y procesadas` | Procesamiento múltiple de subida |

---

## Dónde están los logs

- **Canal por defecto:** `config/logging.php` → canal por defecto suele ser `stack` → `storage/logs/laravel.log`.
- Si usas canal **daily:** `storage/logs/laravel-YYYY-MM-DD.log` (un archivo por día).

---

## Comandos para ver si hoy se subieron DNIs

Ejecuta en la raíz del proyecto (donde está `storage/`).

### Si usas un solo archivo (`laravel.log`)

```bash
# Hoy: líneas que contengan "SUBIDA DNI" o "DNI subido" o "subida de DNI"
grep -E "INICIO PROCESO SUBIDA DNI|FIN PROCESO SUBIDA DNI EXITOSO|DNI subido|formulario de subida de DNI|verificación completada" storage/logs/laravel.log

# Solo las de hoy (filtrar por fecha en el log; formato típico: [2026-02-05 ...])
TODAY=$(date +%Y-%m-%d)
grep "\[$TODAY" storage/logs/laravel.log | grep -E "INICIO PROCESO SUBIDA DNI|FIN PROCESO SUBIDA DNI EXITOSO|DNI subido|formulario de subida de DNI|verificación completada"
```

### Si usas log diario (`laravel-YYYY-MM-DD.log`)

```bash
TODAY=$(date +%Y-%m-%d)
LOG_FILE="storage/logs/laravel-${TODAY}.log"

if [ -f "$LOG_FILE" ]; then
  echo "=== Subidas DNI hoy ($TODAY) ==="
  grep -E "INICIO PROCESO SUBIDA DNI|FIN PROCESO SUBIDA DNI EXITOSO|DNI subido|formulario de subida de DNI|verificación completada" "$LOG_FILE"
else
  echo "No existe $LOG_FILE"
fi
```

### Contar cuántas subidas exitosas hubo hoy

```bash
TODAY=$(date +%Y-%m-%d)

# En archivo único
grep "\[$TODAY" storage/logs/laravel.log 2>/dev/null | grep -c "FIN PROCESO SUBIDA DNI EXITOSO\|DNI subido\|verificación completada" || echo "0"

# En log diario
grep -c "FIN PROCESO SUBIDA DNI EXITOSO\|DNI subido\|verificación completada" "storage/logs/laravel-${TODAY}.log" 2>/dev/null || echo "0"
```

### Ver en tiempo real (mientras alguien sube)

```bash
tail -f storage/logs/laravel.log | grep --line-buffered -E "SUBIDA DNI|DNI subido|GUARDAR IMAGEN|subida de DNI|verificación completada"
```

---

## Script para buscar subidas DNI de hoy

En la raíz del proyecto puedes ejecutar (busca en log diario y en `laravel.log`):

```bash
#!/bin/bash
# Buscar subidas de DNI de hoy
TODAY=$(date +%Y-%m-%d)
PATTERN="INICIO PROCESO SUBIDA DNI|FIN PROCESO SUBIDA DNI EXITOSO|DNI subido|formulario de subida de DNI|verificación completada|dni_entregado actualizado|Imagen procesada exitosamente|INICIO GUARDAR IMAGEN|Imágenes subidas y procesadas"

echo "=== Subidas DNI hoy ($TODAY) ==="

# Log diario
LOG_DAILY="storage/logs/laravel-${TODAY}.log"
if [ -f "$LOG_DAILY" ]; then
  echo "--- En $LOG_DAILY ---"
  grep -E "$PATTERN" "$LOG_DAILY" || echo "(ninguna)"
else
  echo "No existe $LOG_DAILY"
fi

# Log único (solo líneas de hoy)
if [ -f "storage/logs/laravel.log" ] && [ -s "storage/logs/laravel.log" ]; then
  echo "--- En laravel.log (solo $TODAY) ---"
  grep "\[$TODAY" storage/logs/laravel.log | grep -E "$PATTERN" || echo "(ninguna)"
fi
```

---

## Resumen rápido

- **Formulario antiguo:** busca `INICIO PROCESO SUBIDA DNI` y `FIN PROCESO SUBIDA DNI EXITOSO`.
- **Scanner/subida:** busca `DNI subido`, `formulario de subida de DNI`, `verificación completada`, `dni_entregado actualizado`, `Imagen procesada exitosamente`.
- **Archivo de log:** `storage/logs/laravel.log` o `storage/logs/laravel-YYYY-MM-DD.log`.
- **Solo hoy:** filtrar líneas que empiecen por la fecha de hoy en el log, por ejemplo `[2026-02-06`.
