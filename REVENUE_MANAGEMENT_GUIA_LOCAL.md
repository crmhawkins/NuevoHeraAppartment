# Revenue Management — Guía de prueba en local

> Esta es la guía paso a paso para probar el sistema **completo** en tu
> máquina sin tocar producción.

**Estado actual de la rama `feature/revenue-management`**: todo el código
está listo. Sin desplegar. Tu BD producción está intacta. Tu CRM
producción está intacto. Solo falta que tú lo levantes en local y lo
pruebes.

---

## 0. Lo que hay que tener corriendo

Necesitas **dos servicios corriendo en tu PC**:

1. **Scraper Python** (FastAPI en `localhost:8765`) — lanza Airbnb +
   Booking cuando se le pide.
2. **CRM Laravel local** — versión de la rama `feature/revenue-management`,
   conectada a una BD local (NO la de producción).

```
┌──────────────────────────────────┐         ┌──────────────────────────┐
│ Navegador                        │         │ scraper Python (FastAPI) │
│ http://localhost:8000/admin/     │ ─HTTP─► │ http://localhost:8765    │
│        reservas                  │         │                          │
└────────┬─────────────────────────┘         │ - pyairbnb               │
         │                                   │ - patchright (Chromium)  │
         ▼                                   └──────────┬───────────────┘
┌──────────────────────────────────┐                    │
│ Laravel local (php artisan serve)│                    ▼
│                                  │         ┌──────────────────────────┐
│ - rama feature/revenue-management│         │ Airbnb + Booking         │
│ - BD local (MariaDB/MySQL)       │         │ (internet, IP residencial│
│ - .env apunta a scraper:8765     │         │  desde tu PC)            │
└──────────────────────────────────┘         └──────────────────────────┘
```

---

## 1. Levantar el scraper Python

```powershell
cd D:\proyectos\programasivan\NuevoHeraAppartment\revenue-scraper-local

# Activar venv (ya creado en pruebas anteriores)
.\venv\Scripts\Activate.ps1

# Configurar .env
copy .env.example .env
notepad .env
```

Edita `.env` y añade:
```
SERVICE_TOKEN=cambia-este-token-largo-aleatorio-2026
CACHE_DIR=./cache
CACHE_TTL_MIN=60
SERVICE_PORT=8765
```

Lanza el servicio:
```powershell
.\venv\Scripts\python.exe service.py
```

Deberías ver:
```
INFO: Started server process
INFO: Uvicorn running on http://127.0.0.1:8765
```

Comprueba que vive (en otra ventana):
```powershell
curl http://127.0.0.1:8765/health
```
Respuesta esperada:
```json
{"status":"ok","service":"revenue-scraper","zonas":["algeciras_centro","algeciras_costa","bahia_completa"]}
```

**Deja esta ventana abierta**. El servicio debe seguir corriendo
mientras pruebas.

---

## 2. Levantar el CRM Laravel en local

> Esto es la parte que da más cosas. Si nunca has levantado el CRM en
> local, aquí van las opciones.

### Opción A — Con Laravel Sail / Docker (recomendado)
Si ya tienes Docker Desktop:
```powershell
cd D:\proyectos\programasivan\NuevoHeraAppartment
git checkout feature/revenue-management

# Si usas Sail:
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

### Opción B — Con XAMPP/WAMP en Windows
Si tienes PHP+MySQL local:
```powershell
cd D:\proyectos\programasivan\NuevoHeraAppartment
git checkout feature/revenue-management

# Crea BD local "hawkins_local"
# (en phpMyAdmin o consola mysql)

# Copia .env.example a .env y edita:
copy .env.example .env
notepad .env
```

Pega/edita en `.env`:
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hawkins_local
DB_USERNAME=root
DB_PASSWORD=

# Channex (puedes dejar el real para probar push, o mock)
CHANNEX_TOKEN=uAav4eC/4KH15EHPxa3AMPZKtY9bJiywvrVvXqux2mOM6plNAdbRUQxOY626SIf6
CHANNEX_API_TOKEN=uAav4eC/4KH15EHPxa3AMPZKtY9bJiywvrVvXqux2mOM6plNAdbRUQxOY626SIf6
CHANNEX_API_URL=https://app.channex.io/api/v1

# Revenue Management — apuntar al scraper Python local
REVENUE_SCRAPER_URL=http://127.0.0.1:8765
REVENUE_SCRAPER_TOKEN=cambia-este-token-largo-aleatorio-2026
```

> ⚠️ **El SERVICE_TOKEN del scraper Python y el REVENUE_SCRAPER_TOKEN
> del .env del CRM TIENEN QUE COINCIDIR.**

Ahora prepara la BD local:
```powershell
composer install
php artisan key:generate

# Migrar (esto crea TODAS las tablas, incluidas las nuevas de Revenue)
php artisan migrate

# (Opcional) seed con datos de prueba o copia de producción
# Para probar Revenue necesitas al menos:
#  - apartamentos con id_channex y revenue_rate_plan_id
#  - reservas que cubran HOY (para ver "ocupados")
```

Crea tu usuario admin:
```powershell
php artisan tinker
>>> User::create(['name' => 'Test', 'email' => 'test@test.com', 'password' => Hash::make('1234'), 'role' => 'ADMIN']);
>>> exit
```

Lanza Laravel:
```powershell
php artisan serve
```

Visita: http://localhost:8000/login
- email: test@test.com
- password: 1234

---

## 3. El flujo de prueba "Calcular Revenue"

### Paso 1 — Configurar al menos 1 apartamento
Login → http://localhost:8000/admin/revenue/apartamento/1/configurar

Rellena:
- **Precio mínimo**: ej. 50€ (suelo absoluto)
- **Precio máximo**: ej. 200€ (techo absoluto)
- **Posicionamiento**: `match` (mismo precio que competencia)
- **Channex rate_plan_id**: el UUID del rate plan en Channex
  *(necesario solo si vas a probar el push real)*

Si no tienes rate_plan_id real, déjalo vacío y la simulación dirá
"sin Channex" — pero podrás ver el cálculo.

### Paso 2 — Ir a panel reservas
http://localhost:8000/reservas

Verás un nuevo botón **`📊 Calcular Revenue`** junto a "Crear reserva"
y "Ocupación hoy".

### Paso 3 — Pulsar "Calcular Revenue"
Te lleva a: `/admin/revenue/hoy`

Verás:
- Selector de fecha (hoy por defecto)
- KPIs: # libres, # ocupados, mediana competencia (vacía hasta scrapear)
- Estado del **scraper Python** (verde si responde)
- Lista de apartamentos: libres ✓ | ocupados con su huésped

### Paso 4 — Pulsar "Calcular precios competencia"
Esto lanza el scraper Python en directo:
- Tarda **30-60 segundos** (Airbnb ~10s + Booking con Chromium ~20-40s)
- Devuelve mediana, listings, recomendación por apartamento
- La tabla se rellena con el precio recomendado por apartamento
- Los KPIs se actualizan
- Se muestra una sección extra "Listings competencia (top 20)"

### Paso 5 — Aplicar a libres
- Por defecto, todos los apartamentos LIBRES están marcados
- Puedes desmarcar uno a uno o usar "Marcar todos / Desmarcar"
- Pulsa **"Aplicar precios a Channex"**
- Confirma el modal
- El sistema empuja los precios a Channex (que propaga a Booking,
  Airbnb, web)

---

## 4. Modos de prueba sin riesgo

### 4.1. Probar scraper sin tocar Channex
Si quieres ver que todo funciona pero NO mandar nada real a Booking:
- Configura los apartamentos sin `revenue_rate_plan_id`. El "Aplicar"
  reportará "sin Channex configurado" y no llamará a Channex.

### 4.2. Probar con BD local de juguete (más seguro)
- Crea solo 2-3 apartamentos en tu BD local con datos inventados
- `id_channex` no real → cualquier UUID inventado
- Cuando pulses Aplicar, Channex devolverá 404 / error → no se aplica
  nada en producción

### 4.3. Probar con datos reales LEYENDO solo
- Importa tu BD de producción a local (`mysqldump` y restore)
- Configura los apartamentos
- Haz "Calcular Revenue" → ves los precios de competencia REALES
- **NO pulses Aplicar** → cero cambios en Channex / Booking / Airbnb

---

## 5. Resolución de problemas

### "Scraper Python: NO RESPONDE" en la pantalla
- Comprueba que tienes `python service.py` corriendo en su ventana.
- Verifica `curl http://127.0.0.1:8765/health` desde otra terminal.

### "X-Service-Token invalido" (HTTP 401 del scraper)
- Asegúrate de que `SERVICE_TOKEN` en `revenue-scraper-local/.env`
  coincide con `REVENUE_SCRAPER_TOKEN` en el `.env` del CRM Laravel.

### "El servicio de scraping no responde" (HTTP 503 del CRM)
- El CRM no llega a `http://127.0.0.1:8765`. Verifica firewall, o que
  ambos servicios están en la misma máquina.

### Booking devuelve 0 listings
- Tu IP residencial puede estar saturada (raro). Espera 10 min y
  reintenta.
- Cambia a zona `bahia_completa` que tiene más oferta.

### "No hay precios recomendados" al aplicar
- Es porque no se ejecutó "Calcular precios competencia" antes.
  Pulsa primero ese botón.

### Channex devuelve error al aplicar
- Verifica `revenue_rate_plan_id` en cada apartamento.
- Verifica que el `CHANNEX_API_TOKEN` del `.env` sea el bueno.

---

## 6. Lo que hace este sistema bajo el capó

```
1. Click en "Calcular Revenue" → /admin/revenue/hoy
   └─ Carga apartamentos + estado libre/ocupado + última recomendación cacheada

2. Click "Calcular precios competencia"
   └─ POST /admin/revenue/scrape
       └─ Laravel llama HTTP al servicio Python (localhost:8765)
           └─ pyairbnb hace búsqueda Airbnb GraphQL en Algeciras
           └─ patchright lanza Chromium stealth para Booking
           └─ devuelve estadísticas combinadas
       └─ Laravel calcula recomendación por apartamento:
           precio = mediana × factor_segmento × ajustes_dia × ajustes_ocupacion
           clamp(min, max)
       └─ Guarda recomendaciones en BD local (revenue_recomendaciones)
       └─ Devuelve JSON al frontend

3. Click "Aplicar precios a Channex"
   └─ POST /admin/revenue/aplicar-libres-hoy
       └─ Construye batch de cambios
       └─ Llama Channex API: POST /restrictions con todos los precios
       └─ Channex propaga a Booking + Airbnb + web
       └─ Marca aplicado_at en BD local
```

---

## 7. Cuando lo apruebes para producción

Cuando el flujo te convenza en local, los pasos para llevarlo a
producción serán (yo te lo haré, NO lo haré sin tu OK):

1. **Servidor IA (192.168.1.250)**: instalar `revenue-scraper-local/`
   como servicio systemd (no en Coolify, en el servidor IA donde la IP
   es residencial).
2. **Coolify CRM**: añadir env vars `REVENUE_SCRAPER_URL` (apunta a
   192.168.1.250:8765 vía VPN interno) y `REVENUE_SCRAPER_TOKEN`.
3. **Merge `feature/revenue-management` → `main`** (con tu OK explícito).
4. **Deploy producción**: ejecutar migraciones (`php artisan migrate`),
   verificar que el botón aparece, hacer prueba con 1 apartamento de
   los menos críticos.
5. **Tag** `stable-2026-04-30-revenue` cuando confirmes que va bien.

---

## 8. Estructura de archivos creados

```
revenue-scraper-local/                           # Scraper Python self-hosted
├── service.py                                   # FastAPI: el endpoint /scrape-mercado
├── scrape_airbnb.py                             # CLI standalone Airbnb
├── scrape_booking.py                            # CLI Booking (httpx + Camoufox)
├── test_booking_patchright.py                   # CLI Booking con Patchright (el que funciona)
├── busqueda_algeciras_centro.py                 # Test de búsqueda zona
├── run_test.py                                  # Test combinado
├── requirements.txt
├── .env.example
└── README.md

app/                                              # Laravel
├── Models/
│   ├── RevenueCompetidor.php
│   ├── RevenuePrecioCompetencia.php
│   └── RevenueRecomendacion.php
├── Services/
│   ├── ChannexRevenueService.php                # Push bulk a Channex
│   ├── RevenueRecomendadorService.php           # Algoritmo de pricing
│   └── RevenueScraperClient.php                 # HTTP client al scraper Python
└── Http/Controllers/
    └── RevenueManagementController.php          # 7 acciones (hoy, scrape, aplicar...)

database/migrations/
├── 2026_04_29_220000_create_revenue_competidores_table.php
├── 2026_04_29_220001_create_revenue_precios_competencia_table.php
├── 2026_04_29_220002_create_revenue_recomendaciones_table.php
└── 2026_04_29_220003_add_revenue_columns_to_apartamentos.php

resources/views/revenue/
├── hoy.blade.php                                # Pantalla principal "Calcular Revenue"
├── matriz.blade.php                             # Vista avanzada multi-día
├── configurar.blade.php                         # Settings por apartamento
└── historial.blade.php                          # Histórico de cambios aplicados

routes/web.php                                   # 9 rutas nuevas /admin/revenue/*
routes/api.php                                   # 1 endpoint scraper-callback (futuro)
resources/views/reservas/index.blade.php         # 1 botón nuevo (3 líneas)
```

---

## 9. Cosas pendientes que NO bloquean la prueba

- **Servicio Python en el servidor IA**: en producción correrá en
  192.168.1.250, no en tu PC. Por ahora local sirve para validar.
- **Cron diario**: en producción haremos un cron que pre-calcule
  recomendaciones cada noche. En local lo lanzas a mano.
- **Histórico de competencia**: tabla `revenue_precios_competencia`
  está creada pero el scraper aún no escribe ahí (podría hacerlo si lo
  pides).
- **UI matriz multi-día**: existe en `/admin/revenue` (vista matriz)
  pero está más cruda que la vista "hoy".

---

## 10. Lo que prometo

- **Cero impacto en producción** mientras esto está en rama `feature`.
- **Cero migraciones ejecutadas** en BD producción.
- **Cero llamadas a Channex** hasta que tú pulses "Aplicar" en local
  con tu BD local apuntando a tu cuenta Channex real.
- Si rompes algo en local → `git checkout stable-2026-04-29` y volver
  a empezar.

---

**Documento generado por Claude el 29/04/2026.**

¿Algo no claro? Pregunta antes de tocar nada.
