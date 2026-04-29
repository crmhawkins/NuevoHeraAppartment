"""
Scraper de Booking.com en 2 niveles:

  1. Intento httpx + parsel (rápido, sin browser): leer la página HTML
     y extraer JSON-LD + __NEXT_DATA__/__INITIAL_STATE__ que contienen
     precio y disponibilidad. Si no hay datos, fallback a nivel 2.

  2. Camoufox (Firefox stealth, headless) que ejecuta JS y captura
     el estado real de la página. Más lento pero más fiable.

Importante: el scraper se debe ejecutar desde una IP residencial
(p.ej. tu casa o el servidor IA Hawkins). Si lo lanzas desde un
datacenter (IONOS Coolify, AWS, etc.) Booking lo bloqueará en pocos
requests.

Uso:
    python scrape_booking.py
"""
from __future__ import annotations

import json
import os
import re
import sys
import time
import random
from datetime import date, datetime, timedelta
from pathlib import Path
from typing import Any
from urllib.parse import urlparse, parse_qs, urlencode, urlunparse

from rich.console import Console
from rich.table import Table
from rich.progress import track

console = Console()


# Lista de UAs reales actualizados (rotamos)
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:131.0) Gecko/20100101 Firefox/131.0",
]


def _build_url_with_dates(url: str, checkin: date, checkout: date) -> str:
    """Añade ?checkin=...&checkout=...&group_adults=2 a la URL."""
    p = urlparse(url)
    qs = parse_qs(p.query)
    qs["checkin"] = [checkin.isoformat()]
    qs["checkout"] = [checkout.isoformat()]
    qs.setdefault("group_adults", ["2"])
    qs.setdefault("no_rooms", ["1"])
    qs.setdefault("group_children", ["0"])
    return urlunparse(p._replace(query=urlencode(qs, doseq=True)))


def _http_headers() -> dict[str, str]:
    return {
        "User-Agent": random.choice(USER_AGENTS),
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language": "es-ES,es;q=0.9,en;q=0.8",
        "Accept-Encoding": "gzip, deflate, br",
        "Connection": "keep-alive",
        "Upgrade-Insecure-Requests": "1",
        "Sec-Fetch-Dest": "document",
        "Sec-Fetch-Mode": "navigate",
        "Sec-Fetch-Site": "none",
        "Sec-Fetch-User": "?1",
    }


def _extraer_jsonld_precios(html: str) -> dict[str, Any]:
    """
    Busca el JSON-LD en el HTML y extrae datos del hotel/apartamento.
    Booking embebe schema.org Hotel con priceRange, aggregateRating, etc.
    """
    from parsel import Selector
    sel = Selector(html)
    out: dict[str, Any] = {}
    for script in sel.xpath('//script[@type="application/ld+json"]/text()').getall():
        try:
            data = json.loads(script)
        except json.JSONDecodeError:
            continue
        if isinstance(data, dict):
            if data.get("@type") in ("Hotel", "LodgingBusiness", "Apartment"):
                out["title"] = data.get("name")
                out["price_range"] = data.get("priceRange")
                rating = data.get("aggregateRating") or {}
                out["rating"] = rating.get("ratingValue")
                out["reviews_count"] = rating.get("reviewCount")
    return out


def _extraer_precio_visible(html: str) -> str | None:
    """Intenta extraer el precio mostrado en la página (data-testid)."""
    from parsel import Selector
    sel = Selector(html)
    # Booking cambia esto frecuentemente. Intentamos varios selectores.
    candidatos = [
        sel.xpath('//*[@data-testid="price-and-discounted-price"]//text()').getall(),
        sel.xpath('//*[contains(@class, "prco-valign-middle-helper")]/text()').getall(),
        sel.xpath('//span[contains(@class, "fcab3ed991")]/text()').getall(),
    ]
    for c in candidatos:
        text = " ".join(t.strip() for t in c if t.strip())
        if text:
            # Limpiar para que solo queden cifras + €
            m = re.search(r"(\d[\d.,\s]*)\s*€", text)
            if m:
                return m.group(0)
    return None


def scrape_via_httpx(url: str, checkin: date, checkout: date,
                    timeout: int = 30) -> dict[str, Any]:
    """Nivel 1: HTTP simple sin browser. Rápido pero detectable a volumen."""
    import httpx

    full_url = _build_url_with_dates(url, checkin, checkout)
    resultado: dict[str, Any] = {
        "url": full_url,
        "metodo": "httpx",
        "scrapeado_at": datetime.utcnow().isoformat() + "Z",
    }

    try:
        with httpx.Client(http2=True, follow_redirects=True,
                         headers=_http_headers(), timeout=timeout) as client:
            r = client.get(full_url)
            resultado["http_status"] = r.status_code
            if r.status_code != 200:
                resultado["error"] = f"HTTP {r.status_code}"
                resultado["body_preview"] = r.text[:300]
                return resultado

            # Detectar bloqueos comunes
            if "captcha" in r.text.lower() or "Access denied" in r.text:
                resultado["error"] = "Bloqueo detectado (captcha/access denied)"
                return resultado

            jsonld = _extraer_jsonld_precios(r.text)
            resultado.update(jsonld)
            precio_visible = _extraer_precio_visible(r.text)
            if precio_visible:
                resultado["precio_visible"] = precio_visible
            return resultado

    except Exception as e:
        resultado["error"] = f"{type(e).__name__}: {e}"
        return resultado


def scrape_via_camoufox(url: str, checkin: date, checkout: date) -> dict[str, Any]:
    """Nivel 2: Camoufox (Firefox stealth)."""
    full_url = _build_url_with_dates(url, checkin, checkout)
    resultado: dict[str, Any] = {
        "url": full_url,
        "metodo": "camoufox",
        "scrapeado_at": datetime.utcnow().isoformat() + "Z",
    }

    try:
        from camoufox.sync_api import Camoufox
    except ImportError:
        resultado["error"] = "camoufox no instalado. pip install camoufox && camoufox fetch"
        return resultado

    try:
        with Camoufox(
            headless=True,
            humanize=True,        # mueve ratón humano-like
            os=["windows", "macos"],  # rota OS
            block_images=True,
            locale="es-ES",
            timezone="Europe/Madrid",
        ) as browser:
            page = browser.new_page()
            page.goto(full_url, wait_until="domcontentloaded", timeout=60000)

            # Espera humana
            page.wait_for_timeout(random.randint(2000, 5000))

            html = page.content()

            # Detectar CAPTCHA en pleno DOM
            if "captcha" in html.lower():
                resultado["error"] = "CAPTCHA detectado"
                return resultado

            jsonld = _extraer_jsonld_precios(html)
            resultado.update(jsonld)
            precio_visible = _extraer_precio_visible(html)
            if precio_visible:
                resultado["precio_visible"] = precio_visible
            return resultado

    except Exception as e:
        resultado["error"] = f"{type(e).__name__}: {e}"
        return resultado


def scrape_listing(url: str, checkin: date, checkout: date,
                   forzar_camoufox: bool = False) -> dict[str, Any]:
    """Intenta httpx, fallback a Camoufox si no hay datos suficientes."""
    if not forzar_camoufox:
        r = scrape_via_httpx(url, checkin, checkout)
        # Si tenemos al menos title y precio, OK
        if not r.get("error") and r.get("title") and (
            r.get("precio_visible") or r.get("price_range")
        ):
            return r
        console.print(f"   [yellow]httpx insuficiente, fallback a Camoufox[/yellow]")

    return scrape_via_camoufox(url, checkin, checkout)


def main() -> int:
    env_file = Path(__file__).parent / ".env"
    if env_file.exists():
        from dotenv import load_dotenv
        load_dotenv(env_file)

    urls_str = os.getenv("BOOKING_TEST_URLS", "")
    if not urls_str.strip():
        console.print("[red]Falta BOOKING_TEST_URLS en .env[/red]")
        return 1

    urls = [u.strip() for u in urls_str.split(",") if u.strip()]
    fecha_desde = date.fromisoformat(os.getenv("FECHA_DESDE", "2026-05-01"))
    fecha_hasta = date.fromisoformat(os.getenv("FECHA_HASTA", "2026-05-15"))
    delay_min = int(os.getenv("DELAY_MIN", "5"))
    delay_max = int(os.getenv("DELAY_MAX", "30"))
    output_dir = Path(os.getenv("OUTPUT_DIR", "./output"))
    output_dir.mkdir(exist_ok=True)

    console.print(f"\n[bold]Booking scraper — test local[/bold]")
    console.print(f"URLs: {len(urls)}")
    console.print(f"Rango: {fecha_desde} → {fecha_hasta}")
    console.print(f"Delay: {delay_min}-{delay_max}s\n")
    console.print(f"[yellow]IMPORTANTE: ejecuta desde IP residencial. "
                  f"NO desde Coolify/datacenter.[/yellow]\n")

    resultados = []
    for url in track(urls, description="Scrapeando Booking..."):
        r = scrape_listing(url, fecha_desde, fecha_hasta)
        resultados.append(r)
        if url != urls[-1]:
            d = random.uniform(delay_min, delay_max)
            console.print(f"   [dim]Esperando {d:.1f}s...[/dim]")
            time.sleep(d)

    # Guardar JSON
    out_file = output_dir / f"booking_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
    out_file.write_text(json.dumps(resultados, indent=2, ensure_ascii=False), encoding="utf-8")
    console.print(f"\n[green]Resultados guardados en {out_file}[/green]\n")

    # Resumen
    table = Table(title="Resumen Booking", show_lines=True)
    table.add_column("URL", overflow="fold")
    table.add_column("Método")
    table.add_column("Title")
    table.add_column("Precio visible")
    table.add_column("Rating")
    table.add_column("Estado")
    for r in resultados:
        estado = "[green]OK[/green]" if not r.get("error") else f"[red]{r['error'][:30]}[/red]"
        url_corta = r.get("url", "")[:50] + "..."
        table.add_row(
            url_corta, r.get("metodo", "?"),
            (r.get("title") or "")[:40],
            r.get("precio_visible") or r.get("price_range") or "-",
            str(r.get("rating", "")),
            estado,
        )
    console.print(table)
    return 0


if __name__ == "__main__":
    sys.exit(main())
