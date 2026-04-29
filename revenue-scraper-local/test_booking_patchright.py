"""
Test Booking con Patchright (Playwright Chromium con stealth patches).
No necesita Visual C++ Redistributable como Camoufox.
"""
from __future__ import annotations
import json
import re
import statistics
from datetime import date, timedelta
from pathlib import Path
import sys

# Forzar UTF-8 en stdout (Windows cp1252 default rompe rich)
sys.stdout.reconfigure(encoding="utf-8")

from patchright.sync_api import sync_playwright
from parsel import Selector
from rich.console import Console
from rich.table import Table

console = Console()

hoy = date.today()
dias_sab = (5 - hoy.weekday()) % 7 or 7
checkin = hoy + timedelta(days=dias_sab)
checkout = checkin + timedelta(days=1)

URL = (
    "https://www.booking.com/searchresults.es.html"
    "?ss=Algeciras"
    f"&checkin={checkin.isoformat()}"
    f"&checkout={checkout.isoformat()}"
    "&group_adults=2&no_rooms=1&group_children=0"
    "&selected_currency=EUR"
)

console.print(f"\n[bold]Booking via Patchright (Chromium stealth)[/bold]")
console.print(f"Fechas: {checkin} -> {checkout}\n")
console.print("[dim]Lanzando Chromium stealth (primer arranque ~5s)...[/dim]")

with sync_playwright() as pw:
    browser = pw.chromium.launch(
        headless=True,
        args=[
            "--disable-blink-features=AutomationControlled",
        ],
    )
    context = browser.new_context(
        viewport={"width": 1366, "height": 768},
        locale="es-ES",
        timezone_id="Europe/Madrid",
        user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
    )
    page = context.new_page()
    try:
        page.goto(URL, wait_until="domcontentloaded", timeout=60_000)
    except Exception as e:
        console.print(f"[red]Error cargando: {e}[/red]")
        raise

    # Esperar al property-card
    try:
        page.wait_for_selector('[data-testid="property-card"]', timeout=20_000)
        console.print("[green]property-card visible! Booking sirvio el HTML real.[/green]")
    except Exception:
        console.print("[yellow]No vimos property-card en 20s. Volcando HTML...[/yellow]")

    # Espera humana adicional
    page.wait_for_timeout(2000)

    title = page.title()
    url_final = page.url
    html = page.content()
    browser.close()

console.print(f"[dim]URL final: {url_final[:120]}[/dim]")
console.print(f"[dim]Title: {title}[/dim]")
console.print(f"[dim]HTML: {len(html):,} bytes[/dim]")

bloqueo = any(s in html.lower() for s in ["captcha", "access denied", "robot or human"])
if bloqueo:
    console.print("[red]!! Bloqueo detectado en HTML[/red]")
    Path("output").mkdir(exist_ok=True)
    Path("output/booking_BLOQUEO.html").write_text(html[:80_000], encoding="utf-8")
    raise SystemExit(2)

sel = Selector(html)
cards = sel.xpath('//div[@data-testid="property-card"]')
console.print(f"\nproperty-cards: {len(cards)}\n")

resultados = []
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

    precio = None
    matches = re.findall(r"(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d+)?)\s*€", precio_text)
    if matches:
        nums = []
        for m in matches:
            try:
                v = float(m.replace(".", "").replace(",", "."))
                if v > 5: nums.append(v)
            except ValueError: pass
        if nums:
            precio = min(nums)

    rating = None
    m = re.search(r"(\d{1,2}[.,]\d)", rating_text)
    if m:
        try: rating = float(m.group(1).replace(",", "."))
        except: pass

    resultados.append({"titulo": titulo, "precio": precio, "rating": rating, "units": units, "precio_text": precio_text[:100]})

con_precio = [r for r in resultados if r["precio"] is not None]
con_precio.sort(key=lambda x: x["precio"])

tabla = Table(title=f"Booking Algeciras · {checkin} · 2 adultos")
tabla.add_column("Precio", justify="right")
tabla.add_column("Rating")
tabla.add_column("Hab/Cama", overflow="fold", max_width=22)
tabla.add_column("Titulo", overflow="fold", max_width=50)
for r in con_precio[:25]:
    tabla.add_row(
        f"{r['precio']:.0f}€",
        f"{r['rating']:.1f}" if r["rating"] else "-",
        r["units"][:22] if r["units"] else "-",
        r["titulo"][:50],
    )
console.print(tabla)

if con_precio:
    precios = [r["precio"] for r in con_precio]
    console.print(f"\n[bold cyan]Precio noche Booking ({len(precios)}):[/bold cyan]")
    console.print(f"  Min: {min(precios):.0f}EUR")
    if len(precios) >= 4:
        q = statistics.quantiles(precios, n=4)
        console.print(f"  P25: {q[0]:.0f}EUR  Mediana: {q[1]:.0f}EUR  P75: {q[2]:.0f}EUR")
    else:
        console.print(f"  Mediana: {statistics.median(precios):.0f}EUR")
    console.print(f"  Media: {statistics.mean(precios):.0f}EUR  Max: {max(precios):.0f}EUR")

Path("output").mkdir(exist_ok=True)
Path(f"output/booking_patchright_{checkin}.json").write_text(
    json.dumps(resultados, indent=2, ensure_ascii=False), encoding="utf-8"
)
