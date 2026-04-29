# Revenue Management — Propuesta técnica

> Documento de diseño preparado el 29/04/2026 para la próxima sesión de
> implementación. **Léelo y dame feedback antes de tocar código**: hay
> decisiones de coste y de provider que tú tienes que aprobar.

---

## 0. Resumen ejecutivo (TL;DR)

Construimos un módulo de Revenue Management dentro del CRM que:

1. **Cada noche** recolecta precios y disponibilidad de la competencia
   directa de cada uno de nuestros apartamentos (Booking + Airbnb).
2. **Procesa los datos** con el servidor IA local (RTX) usando Qwen3-VL
   para limpieza y extracción robusta + un algoritmo Python para la
   recomendación.
3. **Muestra en el CRM** una pantalla con tabla comparativa
   (nuestro precio vs media competencia vs precio recomendado) por
   apartamento y por día.
4. **Permite al usuario seleccionar apartamentos + rango de fechas + un
   click → empuja los nuevos precios a Channex** (que a su vez los
   propaga a todos los canales: Booking, Airbnb, web propia, etc.).

Plan en 3 fases (~6 semanas):

| Fase | Tiempo | Resultado |
|---|---|---|
| **MVP** | 2-3 sem | Datos de competencia + tabla comparativa + click-to-update Channex. Sin IA. |
| **IA recomendación** | 2 sem | Servicio Python en RTX que sugiere precio óptimo por apartamento/noche. |
| **Autonomía** | 1-2 sem | Reglas custom (mín/máx, factor competidor), histórico ROI, alertas. |

**Coste mensual estimado**: 50-150 € (Apify scrapers) + servidor IA propio (ya pagado).
**Alternativa premium**: AirDNA API ~120 €/mes con datos preprocesados.

---

## 1. Cómo lo hacen los grandes (lo que aprendí)

### PriceLabs (líder del sector, 50+ integraciones PMS)
- Algoritmo propietario **"Hyper Local Pulse" (HLP)** que agrega datos
  scrapeados de Airbnb + datos reales de PMS conectados.
- Variables: oferta/demanda local, estacionalidad, lead time, eventos,
  histórico del propio apartamento.
- Tiene **API abierta** que cualquier PMS puede consumir.
- Punto débil: depende mucho de reglas que el usuario define.

### Beyond Pricing
- Equipo humano lanza/ajusta cada mercado a mano + algoritmo.
- Variables: estacionalidad, día de semana, oferta/demanda, eventos
  locales, **competencia**, amenities, histórico de reservas, búsquedas
  locales en motores.
- Datos agregados de los hoteles que usan Beyond + scraping.

### Wheelhouse
- 10.000 millones de data points/día.
- Comp set builder (el usuario elige qué propiedades son comparables).
- Fuerte en mercados grandes, débil en zonas pequeñas.

### AirDNA (solo data, no pricing automático)
- Agregador. 10M propiedades Airbnb/Vrbo monitorizadas.
- API desde **129$/mes** con 4 packages (Market, Property Comps,
  Rentalizer, **Smart Rates**).
- No incluye Booking.com con tanta cobertura como Airbnb.
- Idea: usarlo COMO source en lugar de scrapear nosotros.

### Patrón común que extraigo
- Los grandes son **agregadores + algoritmo**. La pricing engine es
  relativamente simple; la diferencia la hace la calidad y volumen de
  los datos.
- Para 8-10 apartamentos en Algeciras, **no necesitamos competir con
  Wheelhouse**. Necesitamos resolver el problema concreto: saber qué
  cobra Marisol del piso de al lado y qué cobran los 5-10 apartamentos
  similares en Booking/Airbnb que de verdad nos quitan reservas.

---

## 2. Decisiones de diseño clave

### 2.1. ¿De dónde sacamos los datos?

**Tres opciones evaluadas:**

| Opción | Coste | Fiabilidad | Mantenimiento | Recomendación |
|---|---|---|---|---|
| A. **Apify scrapers** | ~50-100 €/mes | Alta (resuelven anti-bot ellos) | Bajo | **MVP recomendado** |
| B. **AirDNA API** | ~120 €/mes | Muy alta, preprocesado | Cero | Buena si solo Airbnb |
| C. **Scraping propio** (Playwright + proxies) | ~150-300 €/mes proxies | Media-baja, mantenimiento alto | Alto | Solo si A/B fallan |

**Razones para Apify (opción A) en MVP**:
- Pago por uso, no compromiso.
- Tienen scrapers específicos de Booking y Airbnb mantenidos.
- ~$1.25 / 1000 results Airbnb, similar Booking.
- **Cálculo real para nosotros**: 8 apartamentos x ~10 competidores cada
  uno x 90 fechas x 1 vez/día = ~7 200 results/día = $9/día = ~270 €/mes.
  → Demasiado si scrapeamos todas las fechas. **Reducimos a 30 fechas**
  (1 mes vista) = 90 €/mes. Aceptable.
- Cuando volumen suba, evaluar AirDNA.

**Por qué NO scraping propio en MVP**:
- Booking.com usa Cloudflare + Datadome. Airbnb usa anti-bot agresivo.
- Hay que combinar: Playwright stealth + nodriver + residential proxies
  rotativos + bypass JS challenges + a veces resolver Turnstile CAPTCHAs.
- 1-2 semanas de dev solo para tenerlo medio funcionando, después
  mantenimiento constante (cada update de Booking rompe el scraper).
- No es donde queremos invertir tiempo. Apify lo hace por nosotros.

### 2.2. ¿Qué legalidad tiene esto?

- Datos públicos de Booking/Airbnb (precio, disponibilidad, título)
  scrapearlos para uso propio interno → en zona gris pero no perseguido.
- Lo claramente prohibido: PII de clientes, scrapear fotos de huéspedes,
  republicar el contenido como producto.
- Apify aplica sus propias políticas, libera al cliente de la parte
  técnica del bypass anti-bot (ellos asumen el riesgo de su lado).
- **Disclaimer**: yo NO soy abogado. Antes de poner esto en producción,
  pásalo por un asesor legal.

### 2.3. ¿Quién es "competencia" de cada apartamento nuestro?

**Manual el primer mes, semi-auto después**:

1. Para cada apartamento nuestro, defines (en una pantalla del CRM) una
   **lista de URLs de Booking + Airbnb** que consideras comparables.
   Ej. para "Costa 2A": 5 URLs Booking + 5 URLs Airbnb.
2. El sistema scrapea esos URLs cada noche.
3. Cuando hay datos, el algoritmo IA puede **sugerir nuevos comparables**
   buscando por barrio/precio/capacidad similar (Fase 2).

Esto es lo que hace Wheelhouse con su "comp set builder" y es lo más
honesto: tú decides quién es competencia, no un algoritmo opaco.

### 2.4. ¿Empujamos precios automático o manual?

**Manual con un click, NUNCA automático sin supervisión** (al menos en
las primeras semanas).

Razones:
- Los algoritmos de pricing fallan. Si recomendamos 200€/noche cuando
  debería ser 60€ porque el competidor tenía un dato corrupto, perdemos
  reservas durante días sin enterarnos.
- En el incidente de hoy quedamos en que **NUNCA cambios silenciosos
  en producción**. Aquí aplica igual.
- Workflow: el usuario ve la recomendación, decide si aplicar, hace
  click. Auditable y reversible.

**Más adelante** (Fase 3) podemos añadir auto-apply opcional con
guardrails: ±15% del precio actual, mínimo absoluto, máximo absoluto,
notificación WhatsApp a un admin antes de aplicar para que pueda vetar
en X minutos.

---

## 3. Arquitectura propuesta

```
┌────────────────────────────────────────────────────────────────┐
│                    CRM Laravel (existente)                     │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐ │
│  │  Vista Revenue   │→ │  Botón "Aplicar  │→ │  Service:    │ │
│  │  Comparativa     │  │  precios"        │  │  ChannexARI  │ │
│  └──────────────────┘  └──────────────────┘  └──────┬───────┘ │
│         ↑                       ↑                    │         │
│         │                       │                    ↓         │
│  ┌──────┴──────────┐    ┌──────┴───────┐    ┌──────────────┐ │
│  │  Tablas BD      │    │ Endpoint     │    │  Channex API │ │
│  │  competencia    │    │ /api/        │    │  (existente) │ │
│  │  precios_hist   │    │ recommend-   │    │              │ │
│  │  comp_sets      │    │ price        │    └──────────────┘ │
│  └──────┬──────────┘    └──────┬───────┘                      │
└─────────┼──────────────────────┼──────────────────────────────┘
          ↑                      ↓
          │                      │
┌─────────┴──────────┐  ┌────────┴────────────┐
│  Worker Apify      │  │  Servicio Python    │
│  (cron 03:00 AM)   │  │  RTX 5090 (192.168.1.250)
│  scrape_competencia│  │  recommend_price.py │
└────────┬───────────┘  └─────────┬───────────┘
         │                        │
         ↓                        ↓
┌────────────────┐      ┌──────────────────┐
│  Apify API     │      │  Qwen3-VL:8b     │
│  Booking +     │      │  (parser HTML)   │
│  Airbnb        │      │  + algoritmo Py  │
└────────────────┘      └──────────────────┘
```

**Componentes nuevos**:
1. **3 tablas BD nuevas** + 1 join (sección 5).
2. **1 command Artisan** `revenue:scrape-competencia` (cron diario 03:00).
3. **1 service Python** en servidor IA (FastAPI) con endpoint
   `POST /recommend-price`.
4. **1 controller + vista Blade** en CRM `RevenueManagementController`.
5. **Reutilizamos** la integración Channex existente (`ARIController`).

---

## 4. Obtención de datos (la parte crítica)

### 4.1. Worker `revenue:scrape-competencia` (Laravel command)

Ejecuta cada noche a las 03:00 (poca carga, fuera de horario reservas):

```php
// Pseudo:
foreach ($apartamentoNuestro as $apt) {
    foreach ($apt->competidores as $comp) {  // 5-10 URLs Booking + Airbnb
        $datos = ApifyClient::run([
            'actor' => $comp->plataforma === 'booking'
                ? 'voyager/booking-scraper'
                : 'tri_angle/airbnb-scraper',
            'input' => [
                'url' => $comp->url,
                'fechaDesde' => today(),
                'fechaHasta' => today()->addDays(30),
            ]
        ]);
        foreach ($datos as $d) {
            PrecioCompetencia::updateOrCreate([
                'competidor_id' => $comp->id,
                'fecha' => $d->fecha,
            ], [
                'precio' => $d->precio,
                'disponible' => $d->disponible,
                'min_noches' => $d->min_noches,
                'rating' => $d->rating,
                'scrapeado_at' => now(),
            ]);
        }
    }
}
```

### 4.2. Apify SDK para PHP

Apify tiene API REST. No necesitamos SDK oficial. Con `Http::post` de
Laravel basta. Una clase `ApifyClient` simple:

```php
class ApifyClient
{
    public function runActor(string $actor, array $input): array
    {
        $token = config('services.apify.token');
        // 1. Lanzar el actor
        $run = Http::post("https://api.apify.com/v2/acts/$actor/runs?token=$token", $input)->json();
        // 2. Esperar a que termine (polling cada 5s, max 5 min)
        $runId = $run['data']['id'];
        $estado = $this->pollUntilFinished($runId);
        // 3. Recuperar dataset
        $datasetId = $estado['defaultDatasetId'];
        return Http::get("https://api.apify.com/v2/datasets/$datasetId/items?token=$token")->json();
    }
}
```

### 4.3. Estrategia de retries y rate limit

- Cada actor de Apify gestiona él mismo el rate limit hacia Booking/Airbnb.
- Si Apify devuelve error: reintentamos hasta 3 veces con backoff
  exponencial.
- Si tras retries falla: marcamos `competidor.ultimo_error_at` y enviamos
  WhatsApp al admin (siguiendo el patrón del `ia:healthcheck` que ya
  tenemos).

### 4.4. Frescura de los datos

- Cada noche → datos **t+1**. Suficiente para Revenue Management.
- En el dashboard mostramos `scrapeado_at`. Si > 36h, ponemos badge
  amarillo.
- No queremos scrapeo en tiempo real (caro y no aporta tanto).

---

## 5. Schema de base de datos

### 5.1. `revenue_competidores`
```sql
CREATE TABLE revenue_competidores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    apartamento_id BIGINT UNSIGNED NOT NULL,  -- nuestro apartamento
    plataforma ENUM('booking', 'airbnb') NOT NULL,
    url VARCHAR(500) NOT NULL,
    titulo VARCHAR(255),                       -- nombre listing
    apify_actor_id VARCHAR(100),               -- qué scraper usar
    activo BOOLEAN DEFAULT 1,
    notas TEXT,                                -- "vecino piso 2", "ático similar"
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
    fecha DATE NOT NULL,                      -- noche de estancia
    precio DECIMAL(10,2),
    moneda VARCHAR(3) DEFAULT 'EUR',
    disponible BOOLEAN,
    min_noches INT,
    rating DECIMAL(3,2),
    scrapeado_at TIMESTAMP NOT NULL,
    raw_data JSON,                            -- todo lo que devolvió Apify
    UNIQUE KEY unq_comp_fecha_scrape (competidor_id, fecha, scrapeado_at),
    INDEX idx_comp_fecha (competidor_id, fecha),
    FOREIGN KEY (competidor_id) REFERENCES revenue_competidores(id) ON DELETE CASCADE
);
```

> Mantenemos histórico (cada scrape genera un registro). Permite ver
> tendencias. Limpieza automática: borrar > 180 días con un comando
> mensual.

### 5.3. `revenue_recomendaciones`
```sql
CREATE TABLE revenue_recomendaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    apartamento_id BIGINT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,                      -- noche para la que recomendamos
    precio_actual DECIMAL(10,2),              -- el que tenemos en Channex hoy
    precio_recomendado DECIMAL(10,2),         -- lo que la IA sugiere
    precio_aplicado DECIMAL(10,2) NULL,       -- lo que finalmente puso el admin
    aplicado_at TIMESTAMP NULL,
    aplicado_por_user_id BIGINT UNSIGNED NULL,

    -- Variables que entraron en el calculo (transparencia)
    competencia_media DECIMAL(10,2),
    competencia_min DECIMAL(10,2),
    competencia_max DECIMAL(10,2),
    competidores_count INT,
    ocupacion_nuestra_pct DECIMAL(5,2),       -- 0-100, cuántos apts ocupados
    es_finde BOOLEAN,
    es_festivo BOOLEAN,
    razonamiento TEXT,                        -- "Subir 12% por finde + competencia alta"

    calculado_at TIMESTAMP NOT NULL,
    INDEX idx_apt_fecha (apartamento_id, fecha),
    UNIQUE KEY unq_apt_fecha (apartamento_id, fecha),
    FOREIGN KEY (apartamento_id) REFERENCES apartamentos(id) ON DELETE CASCADE
);
```

### 5.4. Migración de apartamentos
Añadir 2 columnas a `apartamentos`:
- `revenue_min_precio DECIMAL(10,2) NULL` — suelo absoluto
- `revenue_max_precio DECIMAL(10,2) NULL` — techo absoluto

---

## 6. Algoritmo de pricing

### 6.1. MVP — sin IA, fórmula simple

```
precio_recomendado = competencia_mediana * factor_segmento

Donde factor_segmento = {
    "premium": 1.10,    // queremos ir 10% por encima
    "match":   1.00,    // mismo precio
    "budget":  0.90     // 10% por debajo (ganar volumen)
}
```

`factor_segmento` lo configura el usuario por apartamento en su ficha.
**Más ajustes**:
- Si `ocupacion_propia > 80%` para esa fecha → `precio * 1.05` (subir)
- Si `ocupacion_propia < 30%` para esa fecha y faltan < 14 días →
  `precio * 0.95` (bajar urgencia)
- Si `es_festivo` o `es_finde` → `precio * 1.10`
- Aplicar siempre `clamp(precio, revenue_min_precio, revenue_max_precio)`

Esto **ya da resultados decentes** sin IA. Es lo que venden Wheelhouse y
Beyond por debajo del marketing.

### 6.2. Fase 2 — IA en RTX 5090

Servicio FastAPI en `192.168.1.250`:

```python
# /opt/revenue-engine/main.py
from fastapi import FastAPI
import psycopg2  # o cliente HTTP que consulta el CRM

app = FastAPI()

@app.post("/recommend-price")
def recommend(req: RecommendRequest):
    """
    Input: apartamento_id, fecha
    Output: precio recomendado + razonamiento natural
    """
    # 1. Recoger features
    competencia = get_precios_competencia(req.apartamento_id, req.fecha)
    ocupacion = get_ocupacion_nuestra(req.fecha)
    festivos = es_festivo_local(req.fecha, "Algeciras")
    historico = get_historico_propio(req.apartamento_id, req.fecha)

    # 2. Algoritmo de Fase 1 (la fórmula simple)
    precio_base = calc_precio_base(competencia, ocupacion, festivos)

    # 3. (Opcional) refinar con LLM
    if SETTINGS.usar_llm:
        prompt = f"""
        Apartamento: {apt.titulo} ({apt.capacidad} pax, {apt.bedrooms} dorm)
        Fecha: {req.fecha} ({dia_semana})
        Competencia (5 listings comparables):
          - Min: {competencia.min}€  Max: {competencia.max}€  Mediana: {competencia.mediana}€
        Nuestra ocupacion ese dia: {ocupacion.pct}%
        Festivos/eventos locales: {festivos}
        Precio base calculado: {precio_base}€

        ¿Ajustarías ese precio? Devuelve JSON:
        {{"precio_final": float, "ajuste_pct": float, "razon": str}}
        """
        ajuste = qwen3_call(prompt)
        precio_final = ajuste.precio_final
    else:
        precio_final = precio_base

    return {
        "precio_recomendado": precio_final,
        "razonamiento": ajuste.razon if ajuste else "Algoritmo base",
        "features": {...}  # transparencia
    }
```

**Modelo a usar**:
- **`gpt-oss:120b-cloud`** para análisis de razonamiento (ya en uso por
  WhatsApp). Inferencia ~5s/recomendación. Aceptable para batch nocturno.
- **`qwen3-vl:8b`** local en GPU para parsing de HTML scrapeado de
  Booking/Airbnb cuando Apify devuelva contenido roto (red de seguridad).
- **NO** usamos modelo entrenado a medida — overkill para 8 apartamentos.

### 6.3. Por qué NO machine learning serio (todavía)

- 8 apartamentos x 365 días/año = 2 920 datapoints/año. Insuficiente
  para entrenar.
- Los algoritmos de RM serios son una mezcla simple de heurísticas + ML
  ligero. Ni Beyond ni Wheelhouse usan deep learning para pricing — usan
  regresiones + reglas.
- Mejor empezar simple, medir resultados, después complicar.

---

## 7. UI en el CRM

### 7.1. Vista principal: `/admin/revenue-management`

**Tabla matriz**:

|                    | Hoy | +1 día | +2 días | … | +30 días |
|--------------------|-----|--------|---------|---|----------|
| **Costa 1A**       | 65€ → **72€** | 65€ → **75€** | … | … | … |
| **Costa 2A**       | 58€ → **58€** | 58€ → **62€** | … | … | … |
| **Hawkins BA**     | … | … | … | … | … |

Cada celda muestra:
- **Precio actual** (lo que tenemos en Channex)
- → **Precio recomendado** (con color: verde si subir, rojo si bajar,
  gris si no cambia)
- Tooltip al hover: "Competencia mediana 75€, ocupación nuestra 60%,
  +5% por finde. Ver detalle"

**Filtros arriba**:
- Selector múltiple de apartamentos (default: todos)
- Rango de fechas (default: próximos 30 días)
- "Solo cambios > X%" (filtra ruido)

**Footer fijo con acciones**:
- Checkboxes en cada celda para marcar qué aplicar
- Botón "Aplicar precios seleccionados" → modal de confirmación →
  POST a `/api/revenue/apply` → empuja a Channex.

### 7.2. Detalle por apartamento/fecha (modal)

Click en celda → modal con:
- Lista de competidores comparables, su precio ese día
- Gráfica histórica (últimos 30 días) precio nuestro vs media competencia
- Razonamiento de la IA en lenguaje natural
- Botón "Aplicar este precio" / "Editar manual" / "Ignorar"

### 7.3. Configuración por apartamento: `/admin/apartamentos/{id}/revenue`

- Lista de competidores (URLs Booking/Airbnb) con drag-drop para añadir
- Min/max absolutos
- Factor de segmento (premium/match/budget)
- Auto-apply on/off (con guardrails) — Fase 3

### 7.4. Histórico: `/admin/revenue/historial`

Tabla con todos los cambios aplicados:
fecha, apartamento, precio_anterior, precio_nuevo, usuario que aplicó,
booking confirmados después de ese cambio. Para medir ROI.

---

## 8. Integración con Channex (push de precios)

Channex API documentado:
**`POST https://app.channex.io/api/v1/restrictions`**

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

Importante:
- **Rate limit Channex**: 10 ARI requests/min/property → batchearlo todo.
- Best practice: **un único POST con todos los cambios** (hasta 1000
  registros en `values`).
- El rate va como string decimal (`"72.00"`) o entero (`7200` = 72€).

Reutilizamos `ARIController` que ya envía cosas a Channex. Le añadimos
un método `bulkUpdateRates(array $cambios)` que respeta rate limits.

**Mapeo property_id ↔ apartamento**:
- Ya existe — `apartamentos.id_channex`. ✓
- Falta: **`rate_plan_id`** por apartamento. Hay que añadir columna o
  configurarlo en setting.

### Plan B si Channex falla

- Empujar a Booking direct (Booking Connect API) — más complejo, requiere
  certificación.
- Empujar a Airbnb direct — Airbnb API solo para partners certificados.
- **Por eso usamos Channex de pasarela**.

---

## 9. Uso del servidor IA (192.168.1.250)

Lo aprovechamos para 3 cosas:

### 9.1. Razonamiento de precio (LLM)
- Modelo: `gpt-oss:120b-cloud` (vía wrapper local existente).
- Llamada: ~5s por recomendación. Si scrapeamos 8 apt x 30 días = 240
  llamadas/noche = 20 minutos. Aceptable en batch 03:00-04:00.
- Caché: la respuesta vale 24h (hasta el siguiente scrape).

### 9.2. Parser HTML de fallback (VL)
- Modelo: `qwen3-vl:8b` local (cabe en RTX 16GB).
- Cuando Apify devuelva un HTML que no parsea (cambian estructura
  Booking, etc.), enviamos screenshot/HTML al modelo y le pedimos JSON
  estructurado: `{precio: 72, disponible: true, ...}`.
- Robusto cuando Booking cambia el DOM.

### 9.3. Análisis de comparables sugeridos (Fase 2)
- "Dado el apartamento Costa 2A (40m², 2 dorm, terraza, exterior),
  busca otros 5 listings de Booking que sean realmente comparables,
  no solo cercanos".
- Modelo: `qwen3-vl:235b-cloud` para análisis serio (es batch, vale la
  pena el extra).

---

## 10. Plan de implementación por fases

### Fase 1: MVP (2-3 semanas)
- [ ] Cuenta Apify + token guardado en `.env` Coolify
- [ ] Migración BD: 3 tablas + 2 columnas en apartamentos
- [ ] Comando `revenue:scrape-competencia` con cron 03:00
- [ ] Vista de configuración: añadir competidores por apartamento
- [ ] Cron procesa todos los competidores activos
- [ ] Vista matriz con precio actual vs **competencia mediana** (sin IA
      todavía, solo dato crudo)
- [ ] Service `ChannexARIService::bulkUpdateRates`
- [ ] Botón "Aplicar precios" → confirma → empuja
- [ ] Tabla histórico de cambios

**Hito**: el usuario puede ver precios competencia y empujar uno
seleccionado a Channex con un click.

### Fase 2: Recomendación con IA (2 semanas)
- [ ] Servicio FastAPI en RTX 5090 con endpoint `/recommend-price`
- [ ] Algoritmo MVP (fórmula con factor segmento + ocupación + finde)
- [ ] Llamada al LLM para razonamiento
- [ ] Tabla `revenue_recomendaciones` poblada cada noche
- [ ] Vista matriz muestra **precio recomendado** + tooltip con razón
- [ ] Modal detalle por celda con gráfica histórica

**Hito**: el usuario ve sugerencias y puede aplicar la sugerencia con
un click.

### Fase 3: Autonomía y ROI (1-2 semanas)
- [ ] Reglas custom por apartamento (auto-apply opcional con guardrails)
- [ ] Alerta WhatsApp si competencia se mueve > 20% en 24h
- [ ] Dashboard ROI: cambios aplicados → bookings posteriores
- [ ] Sugerencia de comparables nuevos (IA)
- [ ] A/B testing precios (apartamentos similares con estrategias
      distintas para comparar)

---

## 11. Costes estimados

### Setup (one-off)
- Mi tiempo: 5-6 semanas de desarrollo (gratis si estamos hablando de
  esto entre nosotros).
- Cuenta Apify: gratis hasta cierto volumen, después pay-per-use.

### Mensual recurrente
| Item | Coste |
|---|---|
| Apify scrapers (8 apt x 10 comp x 30 fechas/día) | **80-120 €/mes** |
| Servidor IA RTX | **0 € (ya pagado)** |
| Channex API | **0 € (ya contratado)** |
| **Total** | **80-120 €/mes** |

Si el sistema te ahorra subir 5€/noche en 100 noches = 500€/mes. ROI
positivo desde el mes 1 si lo usas con criterio.

**Si en 6 meses crece (más apartamentos, más fechas)**: evaluar saltar
a AirDNA API (~120 €/mes pero datos preprocesados, sin scraping
nuestro).

---

## 12. Riesgos y mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Booking/Airbnb cambian DOM y rompen Apify | Media | Alto | Apify lo arregla rápido (es su core). Tenemos parser fallback con Qwen3-VL. |
| Apify nos banea por uso intensivo | Baja | Alto | Pago por uso correcto, sin abuso. Tener AirDNA como plan B. |
| Recomendación de IA falla y empuja precio absurdo | Media | Crítico | NO auto-apply en MVP. Guardrails min/max. Confirmación manual. |
| Channex rate limit lo hace todo lento | Baja | Medio | Batchear updates correctamente. |
| Booking/Airbnb cambian políticas y prohíben scraping para terceros | Baja | Alto | Plan B AirDNA. Worst case, scraping propio (caro pero viable). |
| Competencia detecta nuestro patrón y sube precios cuando subimos | Muy baja | Bajo | Variabilidad en horario de scrape. Pero a 8 apartamentos no somos relevantes para nadie. |
| Yo (Claude) cago la implementación | Media-alta 😅 | Medio | Trabajo en rama feature, deploys con tag, **NO tocar producción sin tu OK** (regla CLAUDE.md sección 0). |

---

## 13. Decisiones que tienes que tomar antes de implementar

1. **¿Qué provider de datos?** Mi recomendación: empezar Apify, evaluar
   AirDNA en Q4 cuando crezca el volumen.
2. **¿Auto-apply algún día?** Si dices que no, yo encantado: menos
   responsabilidad. Si dices que sí, definimos guardrails muy estrictos.
3. **¿Quién decide los comparables?** Mi recomendación: tú al principio
   (manualmente por apartamento, semana 1), después la IA sugiere y
   confirmas.
4. **¿Pricing en Booking + Airbnb por separado o unificado vía Channex?**
   Channex unifica → mismo precio en todos los canales. Si quieres
   precios distintos por canal, complica todo (otra fase).
5. **¿Web propia (apartamentosalgeciras.com) entra?** Sí, Channex la
   gestiona también.
6. **Festivos/eventos locales**: ¿tienes un calendario fiable de eventos
   Algeciras (feria, fiestas, etc.) o tiramos de festivos nacionales?

---

## 14. Lo que NO incluye esta propuesta (para futuro)

- **Yield management complejo** (overbooking, fences por canal,
  segmentación de clientes). Eso son palabras mayores y dudo que valga
  la pena para 8 apartamentos.
- **Forecasting de demanda con ML** sobre años de histórico. Necesitamos
  más datos.
- **Análisis de reviews** para entender posicionamiento (sí útil, pero
  Fase 4+).
- **Integración con Google Hotel Ads / metasearch**. Posible más
  adelante si invertimos en publicidad.

---

## 15. Lo siguiente cuando vuelvas

1. Léete esto y dame feedback (especialmente sección 13).
2. Decidimos provider y arrancamos Fase 1.
3. Yo trabajo en rama `feature/revenue-management`, sin tocar producción
   hasta que esté probado y tú lo apruebes.

Si me dices "vamos", empiezo por:
- Crear rama
- Crear migraciones BD
- Configurar cuenta Apify de prueba con 100€ de crédito test
- Implementar el comando `revenue:scrape-competencia`
- Vista de configuración de competidores por apartamento

Y te aviso cuando tengamos el primer dato real scrapeado para validar
que la cosa funciona antes de seguir construyendo encima.

---

**Documento preparado por Claude el 29/04/2026 mientras estabas en el
gimnasio. Ningún cambio en producción hecho. Cero riesgo. Cero alarmas.
Solo investigación + diseño.**

Sources externos consultados:
- [PriceLabs vs Beyond vs Wheelhouse comparison](https://staystra.com/dynamic-pricing-tools-airbnb-2026/)
- [AirDNA API pricing](https://www.airdna.co/airbnb-api)
- [Apify Booking + Airbnb scrapers](https://apify.com/automation-lab/booking-scraper)
- [Channex ARI API docs](https://docs.channex.io/api-v.1-documentation/ari)
- [Cloudflare/anti-bot landscape 2026](https://scrapfly.io/blog/posts/how-to-bypass-cloudflare-anti-scraping)
- [Qwen3-VL release notes](https://github.com/QwenLM/Qwen3-VL)
- [PriceLabs API for PMS](https://hello.pricelabs.co/dynamic-pricing-api/)
- [GDPR + scraping legal](https://datamam.com/hotel-booking-websites-scraping/)
