"""
FastAPI service que el CRM llama bajo demanda cuando el admin pulsa
"Calcular Revenue".

Endpoints:
  POST /scrape-mercado
    Body: { fecha_desde: "YYYY-MM-DD", fecha_hasta: "YYYY-MM-DD",
            zona: "algeciras_centro", adultos: 2 }
    Devuelve: estadisticas precios competencia + listings.

  GET /health
    Estado del servicio.

Lanzar:
  cd revenue-scraper-local
  ./venv/Scripts/Activate.ps1   (Windows)
  uvicorn service:app --host 127.0.0.1 --port 8765 --reload

Auth: simple X-Service-Token (el mismo en .env del CRM y en .env aqui).
Rate limit: 1 request /scrape-mercado por minuto (no saturar Booking).
"""
from __future__ import annotations
import json
import logging
import os
import re
import statistics
import sys
import time
from datetime import date, datetime
from pathlib import Path
from typing import Any, Optional

# UTF-8 stdout para Windows
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8")

from fastapi import FastAPI, Header, HTTPException, BackgroundTasks
from pydantic import BaseModel, Field

# Cargar .env
try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    pass

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("revenue-service")

SERVICE_TOKEN = os.getenv("SERVICE_TOKEN", "dev-token-cambiar-en-produccion")
CACHE_DIR = Path(os.getenv("CACHE_DIR", "./cache"))
CACHE_DIR.mkdir(exist_ok=True)
CACHE_TTL_MIN = int(os.getenv("CACHE_TTL_MIN", "60"))  # cache 60 min por defecto

# Zonas predefinidas (bounding boxes) — ampliable con mas zonas
ZONAS = {
    "algeciras_centro": {
        "nombre": "Algeciras centro (casco antiguo + ensanche)",
        "ne_lat": 36.150, "ne_lon": -5.420,
        "sw_lat": 36.100, "sw_lon": -5.490,
        "zoom": 13,
        "search_query": "Algeciras",  # para Booking
    },
    "algeciras_costa": {
        "nombre": "Algeciras zona costa/playa",
        "ne_lat": 36.135, "ne_lon": -5.425,
        "sw_lat": 36.105, "sw_lon": -5.470,
        "zoom": 14,
        "search_query": "Algeciras",
    },
    "bahia_completa": {
        "nombre": "Bahia de Algeciras (toda)",
        "ne_lat": 36.175, "ne_lon": -5.330,
        "sw_lat": 36.060, "sw_lon": -5.510,
        "zoom": 12,
        "search_query": "Algeciras",
    },
}


# ============================================================
# Modelos pydantic
# ============================================================

class ScrapeRequest(BaseModel):
    fecha_desde: date
    fecha_hasta: date
    zona: str = "algeciras_centro"
    adultos: int = Field(default=2, ge=1, le=10)
    incluir_airbnb: bool = True
    incluir_booking: bool = True
    use_cache: bool = True  # si False, fuerza re-scrape


class Listing(BaseModel):
    plataforma: str            # "airbnb" | "booking"
    titulo: str
    precio: Optional[float]
    rating: Optional[float]
    tipo: Optional[str]
    url: Optional[str] = None


class ScrapeResponse(BaseModel):
    fecha_desde: date
    fecha_hasta: date
    zona: str
    zona_nombre: str
    adultos: int
    scraped_at: datetime
    cached: bool = False
    cache_age_minutes: Optional[float] = None
    airbnb: dict = Field(default_factory=dict)
    booking: dict = Field(default_factory=dict)
    combinado: dict = Field(default_factory=dict)
    listings: list[Listing] = Field(default_factory=list)


# ============================================================
# FastAPI app
# ============================================================

app = FastAPI(title="Hawkins Revenue Scraper", version="1.0.0")


def _check_auth(token: str | None):
    if not token or token != SERVICE_TOKEN:
        raise HTTPException(status_code=401, detail="X-Service-Token invalido")


@app.get("/health")
def health():
    """Health check (sin auth para poder usarlo en monitoring)."""
    return {
        "status": "ok",
        "service": "revenue-scraper",
        "zonas": list(ZONAS.keys()),
        "cache_dir": str(CACHE_DIR),
    }


def _cache_key(req: ScrapeRequest) -> Path:
    key = f"{req.zona}_{req.fecha_desde}_{req.fecha_hasta}_{req.adultos}.json"
    return CACHE_DIR / key


def _load_cache(req: ScrapeRequest) -> Optional[dict]:
    f = _cache_key(req)
    if not f.exists():
        return None
    age_min = (time.time() - f.stat().st_mtime) / 60
    if age_min > CACHE_TTL_MIN:
        return None
    try:
        data = json.loads(f.read_text(encoding="utf-8"))
        data["__cache_age_minutes"] = age_min
        return data
    except Exception:
        return None


def _save_cache(req: ScrapeRequest, data: dict):
    f = _cache_key(req)
    f.write_text(json.dumps(data, default=str, ensure_ascii=False, indent=2), encoding="utf-8")


# ============================================================
# Scrapers
# ============================================================

def scrape_airbnb_zona(zona_cfg: dict, fecha_desde: date, fecha_hasta: date, adultos: int) -> tuple[list[dict], Optional[str]]:
    """Devuelve (listings, error). listings = [{titulo, precio, rating, tipo, url}, ...]"""
    try:
        import pyairbnb
    except ImportError:
        return [], "pyairbnb no instalado"

    try:
        resultados = pyairbnb.search_all(
            check_in=fecha_desde.isoformat(),
            check_out=fecha_hasta.isoformat(),
            ne_lat=zona_cfg["ne_lat"],
            ne_long=zona_cfg["ne_lon"],
            sw_lat=zona_cfg["sw_lat"],
            sw_long=zona_cfg["sw_lon"],
            zoom_value=zona_cfg["zoom"],
            price_min=0,
            price_max=500,
            adults=adultos,
            currency="EUR",
            language="es",
        )
    except Exception as e:
        log.error(f"Airbnb search_all error: {e}")
        return [], f"{type(e).__name__}: {str(e)[:200]}"

    listings = []
    for r in resultados:
        precio = _extraer_precio_airbnb(r)
        titulo = r.get("title") or r.get("name") or "?"
        if isinstance(titulo, dict):
            titulo = titulo.get("text") or "?"
        rating = r.get("rating") or {}
        rating_val = rating.get("value") if isinstance(rating, dict) else None
        tipo = r.get("type") or r.get("kind") or r.get("name") or "?"
        if isinstance(tipo, dict):
            tipo = tipo.get("text") or "?"
        room_id = r.get("room_id") or r.get("id")
        url = f"https://www.airbnb.es/rooms/{room_id}" if room_id else None
        listings.append({
            "plataforma": "airbnb",
            "titulo": str(titulo)[:100],
            "precio": precio,
            "rating": rating_val,
            "tipo": str(tipo)[:30],
            "url": url,
        })
    return listings, None


def _extraer_precio_airbnb(r: dict) -> Optional[float]:
    """Extrae precio por noche de la estructura pyairbnb 2.2.x."""
    p = r.get("price") or {}
    if not isinstance(p, dict):
        return None
    # unit.amount es lo mas fiable (precio por noche redondeado)
    unit = p.get("unit")
    if isinstance(unit, dict):
        amt = unit.get("amount")
        if isinstance(amt, (int, float)) and amt > 0:
            return float(amt)
    total = p.get("total")
    if isinstance(total, dict):
        amt = total.get("amount")
        if isinstance(amt, (int, float)) and amt > 0:
            return float(amt)
    # Fallback: parsear break_down "1 noche por 56,20"
    bd = p.get("break_down")
    if isinstance(bd, list):
        for item in bd:
            if not isinstance(item, dict):
                continue
            desc = item.get("description", "")
            m = re.search(r"\bpor\s+([\d.,]+)|\bfor\s+([\d.,]+)", desc, re.IGNORECASE)
            if m:
                val_str = (m.group(1) or m.group(2)).replace(",", ".")
                try:
                    val = float(val_str)
                    if val > 0:
                        return val
                except ValueError:
                    pass
    return None


def scrape_booking_zona(zona_cfg: dict, fecha_desde: date, fecha_hasta: date, adultos: int) -> tuple[list[dict], Optional[str]]:
    """Booking via Patchright (Chromium stealth)."""
    try:
        from patchright.sync_api import sync_playwright
        from parsel import Selector
    except ImportError:
        return [], "patchright/parsel no instalado"

    url = (
        "https://www.booking.com/searchresults.es.html"
        f"?ss={zona_cfg['search_query']}"
        f"&checkin={fecha_desde.isoformat()}"
        f"&checkout={fecha_hasta.isoformat()}"
        f"&group_adults={adultos}&no_rooms=1&group_children=0"
        "&selected_currency=EUR"
    )

    listings = []
    try:
        with sync_playwright() as pw:
            browser = pw.chromium.launch(
                headless=True,
                args=["--disable-blink-features=AutomationControlled"],
            )
            context = browser.new_context(
                viewport={"width": 1366, "height": 768},
                locale="es-ES",
                timezone_id="Europe/Madrid",
                user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            )
            page = context.new_page()
            page.goto(url, wait_until="domcontentloaded", timeout=60_000)
            try:
                page.wait_for_selector('[data-testid="property-card"]', timeout=20_000)
            except Exception:
                pass
            page.wait_for_timeout(2000)
            html = page.content()
            browser.close()

        if any(s in html.lower() for s in ["captcha", "access denied", "robot or human"]):
            return [], "Booking respondio con CAPTCHA / acceso denegado"

        sel = Selector(html)
        cards = sel.xpath('//div[@data-testid="property-card"]')
        for c in cards:
            titulo = c.xpath('.//div[@data-testid="title"]/text()').get(default="").strip()
            precios_raw = []
            for xp in [
                './/*[@data-testid="price-and-discounted-price"]//text()',
                './/span[contains(@aria-label, "Precio") or contains(@aria-label, "precio")]/text()',
                './/span[contains(text(),"€")]/text()',
            ]:
                precios_raw.extend(c.xpath(xp).getall())
            precio_text = " ".join(t.strip() for t in precios_raw if t.strip())
            rating_text = " ".join(c.xpath('.//div[@data-testid="review-score"]//text()').getall()).strip()
            units = " ".join(t.strip() for t in c.xpath('.//*[@data-testid="property-card-unit-configuration"]//text()').getall() if t.strip())
            href = c.xpath('.//a[@data-testid="title-link"]/@href').get(default="")

            precio = None
            for m in re.findall(r"(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d+)?)\s*€", precio_text):
                try:
                    v = float(m.replace(".", "").replace(",", "."))
                    if v > 5 and (precio is None or v < precio):
                        precio = v
                except ValueError:
                    pass
            rating = None
            m = re.search(r"(\d{1,2}[.,]\d)", rating_text)
            if m:
                try:
                    rating = float(m.group(1).replace(",", "."))
                except ValueError:
                    pass

            listings.append({
                "plataforma": "booking",
                "titulo": titulo[:100],
                "precio": precio,
                "rating": rating,
                "tipo": units[:30] or "Hotel/Hostal",
                "url": "https://www.booking.com" + href.split("?")[0] if href else None,
            })

    except Exception as e:
        log.error(f"Booking patchright error: {e}")
        return listings, f"{type(e).__name__}: {str(e)[:200]}"

    return listings, None


def calcular_estadisticas(listings: list[dict]) -> dict:
    """Estadisticas de precio sobre listings con precio."""
    precios = [l["precio"] for l in listings if l.get("precio") is not None]
    if not precios:
        return {"n": 0, "precios_disponibles": False}
    out = {
        "n": len(precios),
        "n_total": len(listings),
        "precios_disponibles": True,
        "min": round(min(precios), 2),
        "max": round(max(precios), 2),
        "media": round(statistics.mean(precios), 2),
        "mediana": round(statistics.median(precios), 2),
    }
    if len(precios) >= 4:
        q = statistics.quantiles(precios, n=4)
        out["p25"] = round(q[0], 2)
        out["p75"] = round(q[2], 2)
    if len(precios) > 1:
        out["stdev"] = round(statistics.stdev(precios), 2)
    return out


# ============================================================
# Endpoint principal
# ============================================================

@app.post("/scrape-mercado", response_model=ScrapeResponse)
def scrape_mercado(req: ScrapeRequest, x_service_token: str | None = Header(default=None)):
    _check_auth(x_service_token)

    if req.zona not in ZONAS:
        raise HTTPException(400, f"Zona '{req.zona}' no existe. Disponibles: {list(ZONAS.keys())}")
    zona_cfg = ZONAS[req.zona]

    # Cache?
    if req.use_cache:
        cached = _load_cache(req)
        if cached:
            log.info(f"Cache hit para {req.zona} {req.fecha_desde} ({cached.get('__cache_age_minutes', 0):.1f} min)")
            cached["cached"] = True
            cached["cache_age_minutes"] = cached.pop("__cache_age_minutes", None)
            return cached

    log.info(f"Scrape live: zona={req.zona} fechas={req.fecha_desde}->{req.fecha_hasta} adultos={req.adultos}")

    todos_listings: list[dict] = []
    airbnb_data = {"enabled": req.incluir_airbnb}
    booking_data = {"enabled": req.incluir_booking}

    if req.incluir_airbnb:
        a_listings, a_err = scrape_airbnb_zona(zona_cfg, req.fecha_desde, req.fecha_hasta, req.adultos)
        airbnb_data["error"] = a_err
        airbnb_data["stats"] = calcular_estadisticas(a_listings)
        todos_listings.extend(a_listings)
        log.info(f"Airbnb: {len(a_listings)} listings, error={a_err}")

    if req.incluir_booking:
        b_listings, b_err = scrape_booking_zona(zona_cfg, req.fecha_desde, req.fecha_hasta, req.adultos)
        booking_data["error"] = b_err
        booking_data["stats"] = calcular_estadisticas(b_listings)
        todos_listings.extend(b_listings)
        log.info(f"Booking: {len(b_listings)} listings, error={b_err}")

    response = {
        "fecha_desde": req.fecha_desde.isoformat(),
        "fecha_hasta": req.fecha_hasta.isoformat(),
        "zona": req.zona,
        "zona_nombre": zona_cfg["nombre"],
        "adultos": req.adultos,
        "scraped_at": datetime.utcnow().isoformat() + "Z",
        "cached": False,
        "cache_age_minutes": None,
        "airbnb": airbnb_data,
        "booking": booking_data,
        "combinado": calcular_estadisticas(todos_listings),
        "listings": [Listing(**l).model_dump() for l in todos_listings],
    }
    _save_cache(req, response)
    return response


if __name__ == "__main__":
    import uvicorn
    port = int(os.getenv("SERVICE_PORT", "8765"))
    log.info(f"Starting on port {port}")
    uvicorn.run(app, host="127.0.0.1", port=port)
