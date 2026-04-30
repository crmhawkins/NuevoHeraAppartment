# Revenue Scraper — Test local

Scripts Python para validar la viabilidad del scraping de Airbnb y
Booking.com **antes** de construir nada en el CRM.

> **Cero impacto en producción.** Todo se ejecuta en tu máquina local
> (la conexión doméstica te da una IP residencial española, que es lo
> que necesitamos). No toca el CRM, ni la BD de producción, ni el
> servidor IA.

---

## 1. Qué hace cada script

| Archivo | Qué hace |
|---|---|
| `scrape_airbnb.py` | Scrapea N URLs de Airbnb usando `pyairbnb` (intercepta GraphQL interno). Sin browser, rápido. |
| `scrape_booking.py` | Scrapea N URLs de Booking. Primero intenta `httpx` (rápido), si no obtiene datos suficientes cae a Camoufox (Firefox stealth). |
| `run_test.py` | Lanza los dos anteriores con la misma config, guarda un JSON único de resultados. |

---

## 2. Requisitos en tu máquina

- **Python 3.11 o 3.12** (Camoufox necesita >= 3.10).
- **Conexión a internet doméstica** (NO desde una VPN datacenter, NO desde Coolify).
- Espacio en disco: ~500 MB (Camoufox descarga su Firefox patcheado).

### Comprobar Python en Windows

```powershell
python --version
# Debe decir Python 3.11.x o 3.12.x
```

Si no lo tienes instalado, descárgalo de https://www.python.org/downloads/
y marca la casilla **"Add Python to PATH"** durante la instalación.

---

## 3. Setup (una sola vez)

Abre PowerShell en la carpeta del scraper:

```powershell
cd D:\proyectos\programasivan\NuevoHeraAppartment\revenue-scraper-local

# Crear entorno virtual
python -m venv venv

# Activar venv (Windows PowerShell)
.\venv\Scripts\Activate.ps1

# Si Windows bloquea la activación con "execution of scripts is disabled":
# Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser

# Instalar dependencias
pip install -r requirements.txt

# Descargar el binario de Camoufox (Firefox patcheado, ~150MB)
camoufox fetch
```

> ✅ Si `camoufox fetch` termina sin error y todo `pip install` corre,
> ya está. Si te falla `pip install camoufox`, dime el error.

---

## 4. Configurar URLs de prueba

Copia el ejemplo de `.env`:

```powershell
copy .env.example .env
```

Edita `.env` con notepad o tu editor:

```
AIRBNB_TEST_URLS=https://www.airbnb.es/rooms/12345678,https://www.airbnb.es/rooms/87654321
BOOKING_TEST_URLS=https://www.booking.com/hotel/es/algeciras-centro-1.es.html
FECHA_DESDE=2026-05-01
FECHA_HASTA=2026-05-15
DELAY_MIN=5
DELAY_MAX=30
OUTPUT_DIR=./output
```

**¿Qué URLs poner?**
- 2-3 URLs de Airbnb que sean **comparables a tu apartamento Costa 2A**
  (mismo barrio, capacidad similar, formato similar).
- 2-3 URLs de Booking igual.
- Para la primera prueba: pocos URLs (2 de cada), después escalas.

> ⚠️ Si no tienes URLs específicas a mano, podemos usar tus propios
> apartamentos en Booking/Airbnb como prueba (deberían dar datos OK
> seguro). Eso nos sirve para validar el pipeline antes de buscar
> competencia real.

---

## 5. Lanzar el test

Con el venv activado:

```powershell
# Airbnb solo
python scrape_airbnb.py

# Booking solo
python scrape_booking.py

# Ambos juntos con un único JSON de salida
python run_test.py
```

El output va a `./output/<timestamp>.json`.

---

## 6. Qué deberíamos ver

### Caso éxito — Airbnb
```
Listing  | Title           | Rating | Noches OK | Min€   | Max€  | Estado
12345678 | Apartamento BA  | 4.85   | 14        | 65€    | 95€   | OK
```

### Caso éxito — Booking
```
URL                              | Método  | Title           | Precio | Estado
booking.com/hotel/.../algecira… | httpx   | Hotel Hawkins   | 75€    | OK
```

### Caso problema — Booking nos bloquea
```
URL                              | Método   | Title | Precio | Estado
booking.com/hotel/.../algecira… | camoufox |       |        | HTTP 403
```

Si Booking devuelve 403 / CAPTCHA / contenido vacío en pocas requests,
**ya sabemos que necesitamos plan B**:
- Webshare 10 IPs residenciales: ~3€/mes
- O reducir frecuencia y aceptar menos datos

---

## 7. Test plan recomendado (orden lógico)

### Día 1
1. Lanzar `python scrape_airbnb.py` con **2 URLs** y rango de 7 días.
2. Verificar JSON de salida tiene precios coherentes.
3. Lanzar `python scrape_booking.py` con **2 URLs** y rango de 7 días.
4. Si funciona → ir al Día 2. Si falla → diagnóstico, decidir plan B.

### Día 2-3
1. Subir a **5 URLs** Airbnb + **5 URLs** Booking.
2. Lanzar `python run_test.py` 3 veces al día durante 2 días para ver
   si hay degradación de éxito (el bloqueo no es inmediato).
3. Si tras 6 ejecuciones (~30 requests Booking total) seguimos OK,
   buena señal.

### Día 4-7
1. Si los datos parecen estables → confirmar viabilidad.
2. **Decidir si seguimos con Fase 1 del plan**: integración con CRM.
3. Si Booking nos empieza a bloquear → plan B (Webshare o reducir).

---

## 8. Troubleshooting

### "ImportError: No module named pyairbnb"
Activa el venv: `.\venv\Scripts\Activate.ps1` y reinstala.

### "camoufox: command not found"
El venv no está activado, o `pip install camoufox[geoip]` no se completó.
Reinstala: `pip install --upgrade camoufox[geoip] && camoufox fetch`.

### Booking devuelve HTTP 403 o página de "Robot detected"
- Confirma que estás en tu **conexión doméstica**, no VPN.
- Reduce DELAY_MIN/DELAY_MAX a algo más alto (10-60s).
- Reduce el número de URLs (2 max para empezar).
- Si persiste tras 2-3 intentos: **plan B necesario**.

### Airbnb devuelve datos vacíos
- pyairbnb a veces necesita versión actualizada: `pip install --upgrade pyairbnb`.
- Verifica que la URL es correcta (`/rooms/<numero>` debe aparecer).

### Camoufox abre Firefox visible (no headless)
- Verifica que en el script tenemos `headless=True` (es el default en
  nuestros scripts).

---

## 9. Output: estructura del JSON

```json
{
  "started_at": "2026-04-29T22:15:00Z",
  "fecha_desde": "2026-05-01",
  "fecha_hasta": "2026-05-15",
  "airbnb": [
    {
      "url": "https://www.airbnb.es/rooms/12345678",
      "room_id": "12345678",
      "title": "Apartamento Centro Algeciras",
      "rating": 4.85,
      "reviews_count": 142,
      "calendar": [
        {"fecha": "2026-05-01", "disponible": true, "precio": 65, "min_noches": 1},
        {"fecha": "2026-05-02", "disponible": true, "precio": 75, "min_noches": 1},
        ...
      ]
    }
  ],
  "booking": [
    {
      "url": "...",
      "metodo": "httpx",
      "title": "Hotel Hawkins",
      "precio_visible": "75 €",
      "rating": 8.5,
      "scrapeado_at": "2026-04-29T22:15:30Z"
    }
  ]
}
```

---

## 10. Lo siguiente cuando esto funcione

Cuando confirmes que el scraper extrae datos correctamente:

1. Yo integro los scrapers en el CRM (rama `feature/revenue-management`,
   YA creada).
2. Migraciones BD locales para almacenar histórico.
3. Vista matriz en el CRM.
4. Botón "aplicar precios" → empuja a Channex (modo dry-run primero).
5. **Cuando todo esté validado en local**, decides si desplegar a
   producción.

**Cero cambios en producción hasta que tú lo apruebes explícitamente.**

---

## 11. Seguridad y datos

- Los outputs JSON quedan en `./output/` local, **no se suben a GitHub**
  (incluido en `.gitignore`).
- El `.env` con URLs reales tampoco se sube.
- No se almacena PII de huéspedes en ningún momento.
- No se republica el contenido scrapeado: uso interno exclusivo.
