# Bankinter Scraper API

Endpoint REST que permite que un PC externo (normalmente un Windows que el
usuario tiene siempre encendido) suba el Excel exportado por el scraper de
Bankinter y dispare la importacion al CRM.

Esta arquitectura existe porque:

1. Bankinter detecta y bloquea las IPs de datacenter (Coolify, AWS, etc.).
2. Instalar Chromium + Playwright dentro del contenedor del CRM es complejo.
3. Es mas seguro mantener las credenciales bancarias en una maquina aislada.

El scraper sigue ejecutandose con `node bankinter-scraper.js` en el PC
externo. Cuando termina, el PC envia el Excel resultante a este endpoint.
La logica de procesamiento del Excel (`BankinterScraperService::procesarExcel`)
no cambia.

---

## Endpoint

```
POST /api/bankinter/scraper/import
```

- **Auth**: token compartido en la cabecera `X-Scraper-Token` (no usa Sanctum).
- **Throttle**: maximo 5 peticiones por minuto por IP.
- **Body**: `multipart/form-data` con dos campos:
  - `file`: archivo Excel (`.xls` o `.xlsx`), hasta 10 MB.
  - `cuenta_alias`: alias de la cuenta a importar (debe existir en
    `config('services.bankinter.cuentas')`, por ejemplo `hawkins` o `helen`).

---

## Configuracion del token

1. Generar un token aleatorio largo. Algunas formas validas:

   ```bash
   # Linux / macOS
   openssl rand -hex 32

   # Windows PowerShell
   [Convert]::ToHexString((1..32 | ForEach-Object { Get-Random -Max 256 }))
   ```

2. Anadir la siguiente linea al archivo `.env` del CRM (no esta en
   `.env.example` por seguridad):

   ```
   BANKINTER_SCRAPER_API_TOKEN=ef059abe2fc1a8d48f682c657b1ce2642ba274bc3164b89a800a04f829a262b4
   ```

   > El valor mostrado es solo un ejemplo. Genera el tuyo y no lo compartas.

3. Limpiar la cache de configuracion:

   ```bash
   php artisan config:clear
   ```

4. Anadir el mismo valor en la configuracion del cliente externo (PC con el
   scraper). Debe enviarse en cada peticion en la cabecera `X-Scraper-Token`.

Si la variable `BANKINTER_SCRAPER_API_TOKEN` no esta definida en el `.env`
del CRM, todas las peticiones devolveran `401 Unauthorized` por seguridad
(no hay valor por defecto).

---

## Ejemplo de request con curl

```bash
curl -X POST https://crm.apartamentosalgeciras.com/api/bankinter/scraper/import \
  -H "X-Scraper-Token: ef059abe2fc1a8d48f682c657b1ce2642ba274bc3164b89a800a04f829a262b4" \
  -F "cuenta_alias=hawkins" \
  -F "file=@/ruta/al/movimientos.xlsx"
```

---

## Ejemplo de respuesta exitosa (HTTP 200)

```json
{
  "success": true,
  "total_filas": 42,
  "procesados": 38,
  "duplicados": 4,
  "errores": 0,
  "ingresos_creados": 21,
  "gastos_creados": 17,
  "hashes_huerfanos_eliminados": 0,
  "filas_importe_cero": 0
}
```

## Ejemplos de respuesta de error

### 401 Unauthorized (token ausente o incorrecto)

```json
{ "error": "Unauthorized" }
```

### 422 Validation failed (cuenta_alias o archivo invalidos)

```json
{
  "error": "Validation failed",
  "details": {
    "file": ["The file must be a file of type: xls, xlsx."],
    "cuenta_alias": ["The selected cuenta alias is invalid."]
  }
}
```

### 500 (fallo procesando el Excel)

```json
{
  "success": false,
  "error": "El archivo Excel esta vacio"
}
```

---

## Notas de seguridad

- **Comparacion timing-safe**: el controller usa `hash_equals()` para validar
  el token, evitando ataques por tiempo de respuesta.
- **Rotacion de token**: rota el `BANKINTER_SCRAPER_API_TOKEN` periodicamente
  (por ejemplo cada 90 dias) y siempre tras una posible filtracion. Tras
  cambiarlo, recuerda actualizar tambien el cliente del PC externo y ejecutar
  `php artisan config:clear`.
- **Throttle**: el endpoint esta limitado a 5 peticiones/minuto por IP. El
  scraper externo deberia hacer una sola subida por ejecucion.
- **HTTPS obligatorio**: el token viaja en una cabecera HTTP. Asegurate de
  invocar siempre el endpoint mediante HTTPS, nunca HTTP plano.
- **Logging sanitizado**: el controller loguea IP, alias y resumen del
  resultado, pero nunca el contenido del Excel ni el token.
- **IP whitelist (opcional)**: para mayor seguridad puedes restringir el
  endpoint a la IP publica del PC externo a nivel de proxy reverso (Nginx,
  Caddy o Cloudflare). Ejemplo en Nginx:

  ```nginx
  location = /api/bankinter/scraper/import {
      allow 203.0.113.42;   # IP publica del PC del usuario
      deny all;
      proxy_pass http://app:8000;
  }
  ```

- **Almacenamiento del Excel**: el archivo recibido se guarda en
  `storage/app/bankinter/{alias}_uploaded_{timestamp}.xlsx`. No se borra
  automaticamente para permitir auditoria; conviene tener una rutina de
  limpieza periodica si el volumen crece.
