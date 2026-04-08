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

---

## Endpoint de credenciales cifradas

```
GET /api/bankinter/scraper/credentials
```

Desde abril de 2026 las credenciales Bankinter se gestionan desde el CRM
(`Configuracion > Credenciales > Bankinter`) y se almacenan cifradas en la
tabla `bankinter_credentials`. Para que el PC externo no necesite un `.env`
sincronizado manualmente, puede obtenerlas via este endpoint.

- **Auth**: mismo token compartido en la cabecera `X-Scraper-Token`.
- **Throttle**: maximo 30 peticiones por minuto por IP.
- **Respuesta**: JSON con el payload de cuentas cifrado con **AES-256-GCM**
  (autenticado). El PC externo debe compartir la misma clave simetrica que
  el CRM para descifrarlo.

### Configuracion de la clave de cifrado

Generar una clave aleatoria de 32 bytes codificada en base64:

```bash
# Linux / macOS
openssl rand -base64 32

# Windows PowerShell
[Convert]::ToBase64String([byte[]](1..32 | ForEach-Object { Get-Random -Max 256 }))
```

Anadir al `.env` del CRM:

```
BANKINTER_ENCRYPTION_KEY=base64_de_32_bytes
```

Y replicar exactamente el mismo valor en el PC externo que consume el
endpoint. Tras editar el `.env`, ejecutar `php artisan config:clear`.

### Formato del payload (JSON cifrado)

```json
{
  "format": "aes-256-gcm",
  "iv": "<base64 - 12 bytes>",
  "ciphertext": "<base64 - ciphertext crudo>",
  "auth_tag": "<base64 - 16 bytes>"
}
```

El contenido descifrado es un JSON con esta estructura:

```json
{
  "generated_at": "2026-04-08T09:12:34+02:00",
  "count": 2,
  "cuentas": [
    {
      "alias": "hawkins",
      "label": "Hawkins S.L.",
      "user": "12345678X",
      "password": "plaintext-password",
      "iban": "ES9121000418450200051332",
      "bank_id": 1
    }
  ]
}
```

### Ejemplo de request con curl

```bash
curl https://crm.apartamentosalgeciras.com/api/bankinter/scraper/credentials \
  -H "X-Scraper-Token: <mismo token que import>"
```

### Ejemplo de descifrado en Node.js

```javascript
const crypto = require('crypto');

function descifrar(respuesta, keyBase64) {
    const key = Buffer.from(keyBase64, 'base64'); // 32 bytes
    const iv = Buffer.from(respuesta.iv, 'base64');
    const ciphertext = Buffer.from(respuesta.ciphertext, 'base64');
    const authTag = Buffer.from(respuesta.auth_tag, 'base64');

    const decipher = crypto.createDecipheriv('aes-256-gcm', key, iv);
    decipher.setAuthTag(authTag);

    const plaintext = Buffer.concat([decipher.update(ciphertext), decipher.final()]);
    return JSON.parse(plaintext.toString('utf8'));
}
```

### Ejemplo de descifrado en Python

```python
from base64 import b64decode
from cryptography.hazmat.primitives.ciphers.aead import AESGCM
import json

def descifrar(respuesta, key_base64):
    key = b64decode(key_base64)               # 32 bytes
    iv = b64decode(respuesta['iv'])            # 12 bytes
    ciphertext = b64decode(respuesta['ciphertext'])
    tag = b64decode(respuesta['auth_tag'])     # 16 bytes
    aesgcm = AESGCM(key)
    # cryptography espera ciphertext || tag concatenados
    plaintext = aesgcm.decrypt(iv, ciphertext + tag, None)
    return json.loads(plaintext.decode('utf-8'))
```

### Notas de seguridad

- El CRM jamas devuelve las passwords en claro fuera de este endpoint
  cifrado. En la UI (Configuracion > Credenciales > Bankinter) la password
  nunca se imprime en el HTML.
- El endpoint loguea unicamente la IP del cliente y el numero de cuentas
  entregadas. Nunca loguea el payload, la clave ni el ciphertext.
- Si la tabla `bankinter_credentials` esta vacia,
  `BankinterScraperService::obtenerConfigCuenta()` hace fallback a
  `config('services.bankinter.cuentas')` para no romper la retrocompatibilidad.
- Para migrar las credenciales actuales del `.env` a la BD, ejecuta:
  ```
  php artisan bankinter:migrar-credenciales
  ```
  Pasa `--force` para sobrescribir las que ya existen.
