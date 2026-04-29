# Revenue Management — Propuesta técnica (v2 self-hosted)

> Documento de diseño actualizado el 29/04/2026 tras decisión del cliente:
> **scraping con recursos propios, sin pagar a Apify ni AirDNA**.
>
> Léelo y dame feedback antes de tocar código. Hay decisiones técnicas
> y de coste/riesgo que tú tienes que aprobar (sección 13).

---

## 0. Resumen ejecutivo (TL;DR)

Construimos un módulo de Revenue Management dentro del CRM que:

1. **Cada noche** recolecta precios y disponibilidad de la competencia
   directa de cada uno de nuestros apartamentos (Booking + Airbnb)
   usando **scrapers Python self-hosted** corriendo en el **servidor
   IA Hawkins (192.168.1.250)**.
2. **Procesa los datos** con el mismo servidor IA usando Qwen3-VL
   como parser de fallback + algoritmo Python para la recomendación.
3. **Muestra en el CRM** una pantalla matriz (apartamentos × días) con
   nuestro precio vs media competencia vs precio recomendado.
4. **Permite al usuario seleccionar apartamentos + rango de fechas + un
   click → empuja los nuevos precios a Channex** (que propaga a Booking,
   Airbnb, web, etc.).

Plan en 3 fases (~6 semanas):

| Fase | Tiempo | Resultado |
|---|---|---|
| **MVP** | 2-3 sem | Scrapers self-hosted funcionando + tabla comparativa + click-to-update Channex. Sin IA. |
| **IA recomendación** | 2 sem | Servicio Python en RTX que sugiere precio óptimo. |
| **Autonomía** | 1-2 sem | Reglas custom, histórico ROI, alertas. |

**Coste mensual recurrente**: **0 €**. Solo electricidad del servidor IA
(que ya está corriendo de todos modos).

**Riesgo principal**: Booking puede empezar a bloquearnos. Si pasa, plan
B = aceptar menos datos o pagar IP residencial barata (~$10/mes Webshare).

---

## 1. Cómo lo hacen los grandes (estado del arte 2026)

### PriceLabs (líder, 50+ integraciones PMS)
Algoritmo "Hyper Local Pulse": agrega datos scrapeados de Airbnb +
datos reales de PMS conectados. Variables: oferta/demanda, lead time,
eventos, histórico. Tiene API abierta para PMS.

### Beyond Pricing
Equipo humano + algoritmo. Variables: estacionalidad, día de semana,
oferta/demanda, eventos locales, **competencia**, amenities, histórico.

### Wheelhouse
10B data points/día. Comp set builder donde TÚ eliges los comparables.
Fuerte en mercados grandes.

### Patrón común
**Scraping + algoritmo simple**. Los grandes son agregadores que ganan
con escala. Para 8-10 apartamentos en Algeciras no necesitamos competir
con ellos: necesitamos saber qué cobra el piso de al lado.

---

## 2. Decisiones de diseño clave

### 2.1. ¿De dónde sacamos los datos? (Self-hosted, sin pagar)

**Estrategia híbrida según plataforma**:

| Plataforma | Método | Por qué |
|---|---|---|
| **Airbnb** | `pyairbnb` (Python, GraphQL interno) | Library open-source, intercepta `/api/v3/StaysSearch`, no parsea HTML. Más resistente a cambios. Sin browser. |
| **Booking.com** | Endpoint GraphQL interno + **Camoufox** (Firefox stealth) de fallback | Camoufox parchea Firefox a nivel C++ → 0% detection rate en tests. Open source. |
| **CSRF tokens / sesiones** | Cookies persistentes guardadas + rotación user-agent | Simulamos usuario habitual. |

### 2.2. La pieza crítica: IP residencial

**El problema**: Booking usa Akamai Bot Manager. Bloquea IPs datacenter
(IONOS) en 10-20 requests. **Sin IP residencial no hay scraping de
Booking estable**.

**La solución**: tu servidor IA `192.168.1.250` está en red Hawkins
(conexión empresa española, IP fija residencial). Eso es lo que
necesitamos. Vamos a montar los scrapers ahí.

```
Servidores Coolify (217.160.39.79/81)  ← IPs IONOS datacenter, NO usar
                                          para scraping de Booking
                ❌
                
Servidor IA Hawkins (192.168.1.250)    ← IP residencial española,
                ✅                       perfecta para scrapers
                                          Tiene RTX 5090 + Linux + GPU
```

### 2.3. ¿Cuánto volumen aguanta nuestra IP residencial?

**Estimación honesta**:
- 1 IP residencial estática + timing humano (5-30s entre requests)
- ~200-400 requests/día sostenibles **probablemente** sin bloqueo
- Volumen ideal: **scrapear búsquedas, no listings individuales**:
  - Una búsqueda Booking tipo "Algeciras 1-2 mayo, 2 adultos"
    devuelve 30-50 propiedades en una sola request
  - 8 apartamentos nuestros × 4 búsquedas tipo distintas × 14 fechas
    futuras = **448 búsquedas/día máximo**
- Reducimos a 14 días vista (más cerca = más relevante) y rescrapeo
  parcial (solo fines de semana fuera de 14 días) → **~250/día**

**Plan B si Booking nos bloquea**:
1. **Webshare** (10 IPs residenciales rotativas): $2.99/mes. Cero
   esfuerzo. Compatible con Camoufox.
2. **Reducir frecuencia**: scrapear cada 2-3 días en lugar de a diario.
3. **VPN propia rotativa**: configurar Tailscale + nodos en casa de
   familiares/amigos. Gratis pero más mantenimiento.
4. **Manual**: para los apartamentos premium, tú miras tú mismo Booking
   1 vez por semana y rellenas un formulario rápido en el CRM.

### 2.4. ¿Es legal?

- **Datos públicos** (precio, disponibilidad, título de listing) →
  scraping para uso propio interno generalmente legal.
- **Uso prohibido**: PII de huéspedes, fotos de personas, republicar
  contenido como producto.
- **Términos de servicio**: técnicamente Booking/Airbnb prohíben
  scraping. En la práctica, no demandan a operadores pequeños usando
  los datos para revenue management interno.
- **Volumen bajo + uso interno + sin republicación = zona gris segura**.

⚠️ **Disclaimer**: yo NO soy abogado. Antes de poner esto en producción,
pásalo por un asesor legal. Por escrito que sea uso interno y no se
republica.

### 2.5. ¿Quién es "competencia" de cada apartamento?

**Manual el primer mes, semi-auto después**:

1. Para cada apartamento nuestro, defines en el CRM una lista de
   **URLs Booking + Airbnb** que consideras comparables (5-10).
2. El sistema scrapea esas URLs cada noche.
3. Después la IA puede sugerir comparables nuevos por similitud.

Esto es lo que hace Wheelhouse con su "comp set builder" — y es lo
más honesto: tú decides quién compite contigo.

### 2.6. ¿Empujamos precios automático o manual?

**Manual con un click. NUNCA automático sin supervisión** (al menos en
las primeras semanas).

Razones:
- Algoritmos fallan. Si recomendamos 200€ cuando debería ser 60€ porque
  un competidor tenía dato corrupto, pierdes reservas días sin saberlo.
- Regla CLAUDE.md sección 0: NUNCA cambios silenciosos en producción.
- Workflow: ves la recomendación, decides si aplicar, click. Auditable
  y reversible.

Más adelante (Fase 3) podemos añadir auto-apply opcional con guardrails
estrictos (±15%, mín/máx absolutos, notificación WhatsApp con veto
durante X minutos antes de aplicar).

---

## 3. Arquitectura propuesta

```
┌────────────────────────────────────────────────────────────────┐
│                    CRM Laravel (existente)                     │
│           Servidor Coolify (217.160.39.79)                     │
│                                                                │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐ │
│  │  Vista Revenue   │→ │  Botón "Aplicar  │→ │  Service:    │ │
│  │  Comparativa     │  │  precios"        │  │  ChannexARI  │ │
│  └──────────────────┘  └──────────────────┘  └──────┬───────┘ │
│         ↑                                            │         │
│         │                                            ↓         │
│  ┌──────┴──────────┐                         ┌──────────────┐ │
│  │  Tablas BD      │                         │  Channex API │ │
│  │  competidores   │                         │  (existente) │ │
│  │  precios_hist   │                         │              │ │
│  │  recomendac.    │                         └──────────────┘ │
│  └──────┬──────────┘                                          │
└─────────┼──────────────────────────────────────────────────────┘
          ↑                              
          │ HTTPS POST /scrape-results  
          │
┌─────────┴──────────────────────────────────┐
│   Servidor IA Hawkins (192.168.1.250)      │
│   - IP residencial española                 │
│   - RTX 5090 (16GB VRAM) + Linux           │
│   - Acceso interno desde Coolify por VPN   │
│                                            │
│  ┌─────────────────────────────┐           │
│  │  /opt/revenue-scraper/      │           │
│  │  - main.py (FastAPI)        │           │
│  │  - airbnb_scraper.py        │           │
│  │      (usa pyairbnb)         │           │
│  │  - booking_scraper.py       │           │
│  │      (Camoufox + GraphQL)   │           │
│  │  - cron 03:00 nightly       │           │
│  │  - cookies/ persistencia    │           │
│  └──────────┬──────────────────┘           │
│             │                              │
│  ┌──────────▼──────────────┐               │
│  │  /opt/revenue-engine/   │               │
│  │  - recommend_price.py   │               │
│  │  - llama Qwen3-VL/gpt-  │               │
│  │    oss para razonamiento│               │
│  └─────────────────────────┘               │
└────────────────────────────────────────────┘
```

**Componentes nuevos**:
1. **3 tablas BD nuevas** (sección 5).
2. **1 service Python** en servidor IA con 2 scrapers + 1 engine de pricing.
3. **1 endpoint API** en CRM para recibir resultados del scraper.
4. **1 controller + vistas Blade** en CRM (`RevenueManagementController`).
5. **Reutilizamos** integración Channex existente.

---

## 4. Obtención de datos — la pieza CRÍTICA

### 4.1. Setup del scraper en servidor IA

```bash
# En 192.168.1.250 como hawkins
cd /opt
sudo mkdir revenue-scraper && sudo chown hawkins:hawkins revenue-scraper
cd revenue-scraper

python3 -m venv venv
source venv/bin/activate

# Instalación
pip install fastapi uvicorn httpx parsel
pip install pyairbnb              # Airbnb GraphQL interno
pip install camoufox[geoip]        # Firefox stealth
camoufox fetch                     # descarga binario

# Servicio systemd
sudo cat > /etc/systemd/system/revenue-scraper.service <<EOF
[Unit]
Description=Hawkins Revenue Scraper
After=network.target

[Service]
Type=simple
User=hawkins
WorkingDirectory=/opt/revenue-scraper
ExecStart=/opt/revenue-scraper/venv/bin/uvicorn main:app --host 127.0.0.1 --port 8765
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl enable revenue-scraper
sudo systemctl start revenue-scraper
```

### 4.2. Scraper Airbnb (`pyairbnb`)

`pyairbnb` no usa browser. Hace requests HTTP directos al GraphQL
interno de Airbnb (`/api/v3/StaysSearch`). Más rápido y menos
detectable. **Funciona sin IP residencial**.

```python
# airbnb_scraper.py
import pyairbnb
from datetime import date, timedelta

def scrape_airbnb_listing(url: str, fecha_desde: date, fecha_hasta: date):
    """
    Devuelve precios y disponibilidad de un listing de Airbnb.
    """
    listing_id = pyairbnb.get_id_from_url(url)
    data = pyairbnb.get_calendar(
        listing_id=listing_id,
        check_in=fecha_desde.isoformat(),
        check_out=fecha_hasta.isoformat(),
    )
    # data es un dict con disponibilidad por noche y precio
    resultados = []
    for noche in data["calendar_days"]:
        if noche["available"]:
            resultados.append({
                "fecha": noche["date"],
                "precio": noche["price"]["nightly_rate"]["amount"],
                "moneda": noche["price"]["nightly_rate"]["currency"],
                "disponible": True,
                "min_noches": noche.get("min_nights", 1),
            })
    return resultados
```

### 4.3. Scraper Booking.com (Camoufox + GraphQL)

Booking es más duro. Estrategia en 2 niveles:

**Nivel 1**: GraphQL interno (rápido, sin browser). Endpoint:
`https://www.booking.com/dml/graphql`. Requiere `X-Booking-CSRF-Token`
que se obtiene de la página HTML de la propiedad.

```python
# booking_scraper.py
import httpx
from parsel import Selector

def scrape_booking_listing_via_graphql(url: str, fecha_desde, fecha_hasta):
    """
    Intenta primero el GraphQL interno de Booking (rápido).
    Si falla, cae a Camoufox.
    """
    # 1. Cargar la página HTML para extraer CSRF token + property_id
    with httpx.Client(timeout=30, follow_redirects=True,
                      headers={"User-Agent": _ua_realista()}) as client:
        r = client.get(url)
        r.raise_for_status()
        sel = Selector(r.text)
        csrf = sel.xpath("//meta[@name='csrf']/@content").get()
        property_id = _extract_property_id(sel)
        cookies = client.cookies

    if not csrf:
        # Fallback Camoufox
        return scrape_booking_via_camoufox(url, fecha_desde, fecha_hasta)

    # 2. Llamar al GraphQL para precios por noche
    graphql_payload = _build_availability_query(property_id, fecha_desde, fecha_hasta)
    with httpx.Client(cookies=cookies, headers={
        "X-Booking-CSRF-Token": csrf,
        "Content-Type": "application/json",
        "User-Agent": _ua_realista(),
    }) as client:
        r = client.post("https://www.booking.com/dml/graphql", json=graphql_payload)
        if r.status_code != 200:
            return scrape_booking_via_camoufox(url, fecha_desde, fecha_hasta)
        return _parse_graphql_response(r.json())
```

**Nivel 2**: Camoufox como fallback cuando el GraphQL no funciona
(Booking cambia hashes, etc.):

```python
from camoufox.sync_api import Camoufox

def scrape_booking_via_camoufox(url, fecha_desde, fecha_hasta):
    with Camoufox(
        headless=True,
        humanize=True,         # mueve ratón humano-like
        geoip=True,            # IP coherente con timezone/locale
        os="windows",
        block_images=True,     # ahorra ancho de banda
    ) as browser:
        page = browser.new_page()
        # Construir URL con fechas como parámetros
        full_url = f"{url}?checkin={fecha_desde}&checkout={fecha_hasta}&group_adults=2"
        page.goto(full_url, wait_until="domcontentloaded")
        page.wait_for_timeout(_random_delay(3000, 8000))  # esperar humano

        # Extraer del DOM o del JSON-LD embebido
        json_ld = page.locator('script[type="application/ld+json"]').inner_text()
        data = _parse_jsonld(json_ld)

        # También capturar precio principal mostrado
        precio_visible = page.locator('[data-testid="price-display"]').inner_text()

        return _merge(data, precio_visible)
```

### 4.4. Cron schedule (en el servidor IA)

```cron
# /etc/cron.d/revenue-scraper
# A las 03:00 cada noche, cuando el tráfico de Booking es bajo
0 3 * * * hawkins /opt/revenue-scraper/venv/bin/python /opt/revenue-scraper/run_nightly.py >> /var/log/revenue-scraper.log 2>&1

# A las 14:00 refresh ligero solo de fechas próximas (próximos 7 días)
0 14 * * * hawkins /opt/revenue-scraper/venv/bin/python /opt/revenue-scraper/run_nearby.py >> /var/log/revenue-scraper.log 2>&1
```

### 4.5. Estrategia anti-bloqueo

**Reglas que aplicamos en TODOS los scrapers**:

1. **Timing humano**: random delay 5-30s entre requests.
2. **User-Agent rotation**: lista de 20 UAs reales actualizados.
3. **Sin headless detectable**: Camoufox lo resuelve. Para `httpx` usamos
   headers HTTP completos (Accept-Language, Sec-Fetch-*, etc.).
4. **Cookies persistentes**: guardamos y reutilizamos cookies por
   plataforma → simulamos usuario habitual.
5. **Volumen escalonado**: máximo 50 requests Booking en 1h, espaciar.
6. **Retry exponencial**: si 429 o 403, esperar 30min y reintentar.
   Si 3 fallos consecutivos → notificación WhatsApp al admin.
7. **No scrapear si hay CAPTCHA**: si Camoufox detecta Turnstile/CAPTCHA,
   abortar y avisar. El admin lo resuelve manualmente o reducimos volumen.

### 4.6. Frescura de datos

- Cada noche → datos de "ayer noche". Suficiente para Revenue Management.
- En el dashboard mostramos `scrapeado_at`. Si > 36h, badge amarillo.
- No queremos scraping en tiempo real: caro, detectable, no aporta tanto.

### 4.7. Comunicación scraper ↔ CRM

El scraper guarda los resultados en BD del CRM via API HTTPS:

```python
# En el scraper
import httpx
def push_resultados_a_crm(resultados):
    httpx.post(
        "https://crm.apartamentosalgeciras.com/api/revenue/scraper-callback",
        json={"resultados": resultados},
        headers={"X-Scraper-Token": SCRAPER_SECRET_TOKEN},
        timeout=60,
    )
```

En el CRM, un endpoint protegido por token:

```php
Route::post('/api/revenue/scraper-callback', [
    RevenueController::class, 'storeScraperResults'
])->middleware('scraper-auth');
```

**Auth**: token compartido entre servidor IA y CRM (env var
`REVENUE_SCRAPER_TOKEN`).

---

## 5. Schema de base de datos

### 5.1. `revenue_competidores`
```sql
CREATE TABLE revenue_competidores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    apartamento_id BIGINT UNSIGNED NOT NULL,
    plataforma ENUM('booking', 'airbnb') NOT NULL,
    url VARCHAR(500) NOT NULL,
    titulo VARCHAR(255),
    activo BOOLEAN DEFAULT 1,
    notas TEXT,
    ultimo_scrape_at TIMESTAMP NULL,
    ultimo_error_at TIMESTAMP NULL,
    ultimo_error_msg TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_apt (apartamento_id, activo),
    FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id) ON DELETE CASCADE
);
```

### 5.2. `revenue_precios_competencia`
```sql
CREATE TABLE revenue_precios_competencia (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competidor_id BIGINT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    precio DECIMAL(10,2),
    moneda VARCHAR(3) DEFAULT 'EUR',
    disponible BOOLEAN,
    min_noches INT,
    rating DECIMAL(3,2),
    scrapeado_at TIMESTAMP NOT NULL,
    raw_data JSON,
    UNIQUE KEY unq_comp_fecha_scrape (competidor_id, fecha, scrapeado_at),
    INDEX idx_comp_fecha (competidor_id, fecha),
    FOREIGN KEY (competidor_id) REFERENCES revenue_competidores(id) ON DELETE CASCADE
);
```

> Mantenemos histórico (cada scrape = nuevo registro). Permite ver
> tendencias. Limpieza mensual: borrar > 180 días.

### 5.3. `revenue_recomendaciones`
```sql
CREATE TABLE revenue_recomendaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    apartamento_id BIGINT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    precio_actual DECIMAL(10,2),
    precio_recomendado DECIMAL(10,2),
    precio_aplicado DECIMAL(10,2) NULL,
    aplicado_at TIMESTAMP NULL,
    aplicado_por_user_id BIGINT UNSIGNED NULL,

    competencia_media DECIMAL(10,2),
    competencia_min DECIMAL(10,2),
    competencia_max DECIMAL(10,2),
    competidores_count INT,
    ocupacion_nuestra_pct DECIMAL(5,2),
    es_finde BOOLEAN,
    es_festivo BOOLEAN,
    razonamiento TEXT,

    calculado_at TIMESTAMP NOT NULL,
    INDEX idx_apt_fecha (apartamento_id, fecha),
    UNIQUE KEY unq_apt_fecha (apartamento_id, fecha),
    FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id) ON DELETE CASCADE
);
```

### 5.4. Migración apartamentos
- `revenue_min_precio DECIMAL(10,2) NULL` — suelo absoluto
- `revenue_max_precio DECIMAL(10,2) NULL` — techo absoluto
- `revenue_factor_segmento ENUM('premium','match','budget') DEFAULT 'match'`

---

## 6. Algoritmo de pricing

### 6.1. MVP — sin IA, fórmula simple

```
precio_base = mediana_competencia * factor_segmento

factor_segmento:
  - premium: 1.10
  - match:   1.00
  - budget:  0.90

Ajustes:
  - Ocupación nuestra > 80% en esa fecha → × 1.05
  - Ocupación < 30% y < 14 días vista   → × 0.95
  - Es viernes/sábado o festivo         → × 1.10
  - clamp(precio, revenue_min, revenue_max)
```

Esto **ya da resultados decentes** sin IA. Es lo que venden Wheelhouse
y Beyond por debajo del marketing.

### 6.2. Fase 2 — IA en RTX 5090

Servicio FastAPI en `192.168.1.250`:

```python
# /opt/revenue-engine/main.py
from fastapi import FastAPI

app = FastAPI()

@app.post("/recommend-price")
def recommend(req: RecommendRequest):
    # 1. Recoger features
    competencia = get_precios_competencia(req.apartamento_id, req.fecha)
    ocupacion = get_ocupacion_nuestra(req.fecha)
    festivos = es_festivo_local(req.fecha, "Algeciras")
    historico = get_historico_propio(req.apartamento_id, req.fecha)

    # 2. Algoritmo de Fase 1 (la fórmula)
    precio_base = calc_precio_base(competencia, ocupacion, festivos)

    # 3. Refinar con LLM para añadir matiz
    if SETTINGS.usar_llm:
        prompt = build_prompt(competencia, ocupacion, festivos, precio_base, ...)
        ajuste = qwen3_call(prompt, modelo="gpt-oss:120b-cloud")
        precio_final = ajuste.precio_final
        razonamiento = ajuste.razon
    else:
        precio_final = precio_base
        razonamiento = "Fórmula base"

    return {
        "precio_recomendado": precio_final,
        "razonamiento": razonamiento,
        "features": {...}
    }
```

**Modelos a usar**:
- **`gpt-oss:120b-cloud`** para razonamiento (ya en uso). ~5s/recomendación.
- **`qwen3-vl:8b`** local en GPU para parsear screenshots cuando
  Camoufox devuelva DOM raro.
- **NO** entrenamiento custom — overkill para 8 apartamentos.

### 6.3. Por qué NO machine learning serio (todavía)

- 8 apartamentos × 365 días × 2 años = ~5840 datapoints. Insuficiente.
- Algoritmos serios de RM = heurísticas + regresiones simples + reglas.
- Empezar simple, medir resultados, después complicar.

---

## 7. UI en el CRM

### 7.1. Vista principal: `/admin/revenue-management`

**Tabla matriz**:

|              | Hoy           | +1 día        | +2 días       | … | +30 días |
|--------------|---------------|---------------|---------------|---|----------|
| **Costa 1A** | 65€ → **72€** | 65€ → **75€** | 65€ → 65€     | … | …        |
| **Costa 2A** | 58€ → 58€     | 58€ → **62€** | 58€ → 58€     | … | …        |
| **Hawkins BA** | …           | …             | …             | … | …        |

Cada celda muestra:
- **Precio actual** (lo que tenemos en Channex)
- → **Precio recomendado** (color: verde subir, rojo bajar, gris igual)
- Hover: "Competencia mediana 75€, ocupación 60%, +5% finde"

**Filtros arriba**:
- Selector múltiple de apartamentos
- Rango fechas (default: próximos 30 días)
- "Solo cambios > X%" (filtra ruido)

**Footer fijo**:
- Checkboxes en cada celda → marcar qué aplicar
- Botón "Aplicar precios seleccionados" → modal confirmación → POST →
  empuja a Channex.

### 7.2. Detalle por celda (modal)

Click en celda:
- Lista competidores y su precio ese día
- Gráfica histórica (últimos 30 días) precio nuestro vs media competencia
- Razonamiento de la IA
- Botón "Aplicar este precio" / "Editar manual" / "Ignorar"

### 7.3. Configuración por apartamento

`/admin/apartamentos/{id}/revenue`:
- Lista de competidores (URLs Booking/Airbnb) con drag-drop
- Min/max absolutos
- Factor segmento (premium/match/budget)
- Auto-apply on/off — Fase 3

### 7.4. Histórico

`/admin/revenue/historial`:
Tabla con cambios aplicados: fecha, apartamento, precio antes → después,
usuario, bookings posteriores. Para medir ROI.

### 7.5. Alertas y health

`/admin/revenue/health`:
- Estado del scraper en servidor IA (último éxito por plataforma)
- Errores recientes (CAPTCHA detectados, 429s, etc.)
- Notificación WhatsApp si scraper lleva > 36h sin datos

---

## 8. Integración con Channex (push de precios)

Endpoint: `POST https://app.channex.io/api/v1/restrictions`

Body:
```json
{
  "values": [
    {"property_id": "<uuid>", "rate_plan_id": "<uuid>",
     "date_from": "2026-05-01", "date_to": "2026-05-31",
     "rate": "72.00"}
  ]
}
```

- **Rate limit**: 10 ARI/min/property. Batchear todo en 1 request hasta
  1000 cambios.
- Reutilizar `ARIController` existente, añadir `bulkUpdateRates()`.

**Pendiente configurar**: `rate_plan_id` por apartamento. Añadir columna
o setting.

---

## 9. Uso del servidor IA Hawkins (192.168.1.250)

Lo aprovechamos para 4 cosas:

### 9.1. Scrapers (CPU + IP residencial)
- Python 3.12 + venv
- pyairbnb (HTTP)
- Camoufox (Firefox stealth, headless)
- httpx (HTTP avanzado)
- Cron nocturno

### 9.2. Razonamiento de precio (LLM)
- `gpt-oss:120b-cloud` ya disponible. ~5s/llamada.
- 8 apt × 30 días = 240 llamadas/noche = ~20 min batch. Aceptable.

### 9.3. Parser visual fallback (VL)
- `qwen3-vl:8b` local en GPU.
- Cuando Camoufox devuelve DOM que no parsea (Booking cambia clases),
  enviamos screenshot al modelo y pedimos JSON estructurado.
- Robusto a cambios de layout.

### 9.4. Análisis de comparables (Fase 2)
- "Dado Costa 2A (40m², 2 dorm, terraza), busca 5 listings Booking
  realmente comparables".
- `qwen3-vl:235b-cloud` (batch).

---

## 10. Plan de implementación por fases

### Fase 1: MVP (2-3 semanas)
- [ ] Setup `/opt/revenue-scraper/` en 192.168.1.250 (venv, deps, systemd)
- [ ] Implementar `airbnb_scraper.py` con `pyairbnb`
- [ ] Implementar `booking_scraper.py` con GraphQL + Camoufox fallback
- [ ] Test con 3 apartamentos, 5 competidores, 7 fechas → validar
- [ ] **Punto de control**: si Booking nos bloquea aquí, decidir plan B
- [ ] Cron nocturno 03:00
- [ ] Endpoint API `/api/revenue/scraper-callback` en CRM
- [ ] Migración BD: 3 tablas + columnas en apartamentos
- [ ] Vista configuración: añadir competidores
- [ ] Vista matriz con precio actual vs competencia mediana (sin IA)
- [ ] Service `ChannexARIService::bulkUpdateRates`
- [ ] Botón "Aplicar precios" → confirma → empuja
- [ ] Tabla histórico de cambios

**Hito 1**: ver precios competencia y empujar uno seleccionado a Channex
con un click.

### Fase 2: Recomendación con IA (2 semanas)
- [ ] Servicio FastAPI `/opt/revenue-engine/` con `/recommend-price`
- [ ] Algoritmo MVP (fórmula con factor segmento + ocupación + finde)
- [ ] Llamada al LLM para razonamiento
- [ ] Tabla `revenue_recomendaciones` poblada cada noche
- [ ] Vista matriz muestra **precio recomendado** + tooltip
- [ ] Modal detalle por celda con gráfica histórica

**Hito 2**: ver sugerencias y aplicar con un click.

### Fase 3: Autonomía y ROI (1-2 semanas)
- [ ] Reglas custom por apartamento (auto-apply opcional)
- [ ] Alerta WhatsApp si competencia se mueve > 20% en 24h
- [ ] Dashboard ROI: cambios → bookings posteriores
- [ ] Sugerencia de comparables nuevos (IA)
- [ ] A/B testing de estrategias

---

## 11. Costes estimados

### Setup (one-off)
- Mi tiempo: 5-6 semanas dev.
- Infraestructura: cero (todo ya existe).

### Mensual recurrente
| Item | Coste |
|---|---|
| Servidor IA Hawkins (electricidad + ya pagado) | **0 €** |
| Servidor Coolify CRM (ya pagado) | **0 €** |
| Channex API (ya contratado) | **0 €** |
| Scrapers (self-hosted, sin proxies de pago) | **0 €** |
| **Total mensual nuevo** | **0 €** |

### Plan B si Booking nos bloquea (a partir del mes 2-3)
- Webshare 10 IPs residenciales: **$2.99/mes** (~3 €)
- O reducir frecuencia y aceptar menos datos: **0 €**

---

## 12. Riesgos y mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Booking detecta nuestra IP y bloquea | **Media-alta** | Alto | (1) Camoufox + timing humano. (2) Plan B Webshare $3/mes. (3) Reducir frecuencia. |
| Airbnb bloquea pyairbnb (cambian GraphQL) | Baja-media | Medio | pyairbnb se actualiza rápido (open source). Fork si necesario. |
| Booking cambia DOM y rompe Camoufox parser | Media | Medio | Qwen3-VL parser fallback. Alerta WhatsApp si 3 fallos seguidos. |
| Rate limit Channex hace lento el push | Baja | Bajo | Batchear updates (1 req/100 cambios). |
| CAPTCHA recurrente bloquea scraper | Baja | Alto | Reducir volumen + alerta admin para resolución manual + plan B Webshare. |
| Recomendación IA empuja precio absurdo | Media | Crítico | NO auto-apply MVP. Guardrails min/max. Confirmación manual. |
| Servidor IA cae | Baja | Medio | Datos antiguos siguen en CRM. Workflow manual mientras. |
| Yo (Claude) cago la implementación | Media-alta 😅 | Medio | Rama feature, deploys por tag, NO tocar producción sin OK (CLAUDE.md sección 0). |

---

## 13. Decisiones que tienes que tomar antes de implementar

1. **¿Confirmas que vamos sin pagar nada (Apify/AirDNA descartados)?**
   → asumido en esta v2.
2. **¿Auto-apply algún día?** Mi voto: nunca o muy tarde con guardrails.
3. **¿Quién decide los comparables?** Mi voto: tú al inicio, después la
   IA sugiere y confirmas.
4. **¿Pricing unificado o por canal?** Mi voto: unificado vía Channex
   (más simple, lo que hacen todos).
5. **¿Qué hacemos si Booking nos bloquea?** Opciones:
   - a) Aceptar menos datos (solo Airbnb + listados Booking que
     funcionen) → coste 0 €
   - b) Pagar Webshare ~3 €/mes → coste mínimo, datos completos
   - c) Configurar VPN propia rotativa → 0 € pero curro
6. **¿Calendario de eventos Algeciras?** ¿Tienes uno propio o tiramos
   de festivos nacionales/Andalucía solamente?
7. **¿Qué apartamentos arrancamos primero?** Mi voto: 3 piloto (1 Costa,
   1 Suites premium, 1 medio) durante 2 semanas, después extender.

---

## 14. Lo que NO incluye (futuro)

- Yield management complejo (overbooking, fences por canal).
- Forecasting con ML sobre años de histórico (no hay datos suficientes).
- Análisis de reviews para posicionamiento (Fase 4+).
- Integración Google Hotel Ads / metasearch.

---

## 15. Lo siguiente cuando vuelvas

1. Léete esto + dame feedback (especialmente sección 13).
2. Decidimos arranque: 3 apartamentos piloto.
3. Yo creo rama `feature/revenue-management`. SIN tocar producción
   hasta que esté probado y aprobado.

Si dices "vamos":
- Crear rama
- Crear migraciones BD (en rama, no en main)
- Configurar `/opt/revenue-scraper/` en 192.168.1.250
- Implementar scrapers Airbnb + Booking
- **Punto de control crítico**: validar en una semana que Booking no
  nos bloquea con volumen real. Si bloquea → decisión plan B.

---

**Documento preparado por Claude el 29/04/2026 mientras estabas en el
gimnasio. Ningún cambio en producción hecho. Cero riesgo. Cero alarmas.
Solo investigación + diseño self-hosted en tu propia infraestructura.**

Sources externos consultados:
- [PriceLabs vs Beyond vs Wheelhouse 2026](https://staystra.com/dynamic-pricing-tools-airbnb-2026/)
- [Camoufox: stealth Firefox open source](https://camoufox.com/)
- [Patchright: Playwright stealth Chromium](https://github.com/Kaliiiiiiiiii-Vinyzu/patchright)
- [pyairbnb GraphQL interceptor](https://github.com/johnbalvin/pyairbnb)
- [Booking.com GraphQL internal API](https://substack.thewebscraping.club/p/scraping-booking-using-apiss)
- [Channex ARI bulk update API](https://docs.channex.io/api-v.1-documentation/ari)
- [Anti-bot Cloudflare/Akamai 2026](https://scrapfly.io/blog/posts/how-to-bypass-cloudflare-anti-scraping)
- [Self-hosted scraping browsers benchmark](https://github.com/techinz/browsers-benchmark)
- [Qwen3-VL multimodal](https://github.com/QwenLM/Qwen3-VL)
- [GDPR + scraping legal](https://datamam.com/hotel-booking-websites-scraping/)
