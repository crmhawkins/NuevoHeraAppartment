"""
Test: scraping de la pagina de busqueda de Booking.com con Camoufox.
Busca apartamentos en Algeciras centro, 2 adultos, 1 noche el proximo
sabado. Saca todos los precios visibles.

Si funciona -> Booking accesible sin proxies.
Si bloquea  -> mensaje claro de error y sabemos que necesitamos plan B.
"""
from __future__ import annotations
import json
import re
import statistics
from datetime import date, timedelta
from pathlib import Path

from camoufox.sync_api import Camoufox
from parsel import Selector
from rich.console import Console
from rich.table import Table

console = Console()

# Fecha proximo sabado
hoy = date.today()
dias_sab = (5 - hoy.weekday()) % 7 or 7
checkin = hoy + timedelta(days=dias_sab)
checkout = checkin + timedelta(days=1)

URL_BUSQUEDA = (
    "https://www.booking.com/searchresults.es.html"
    f"?ss=Algeciras+centro%2C+Cadiz%2C+Espana"
    f"&checkin={checkin.isoformat()}"
    f"&checkout={checkout.isoformat()}"
    "&group_adults=2&no_rooms=1&group_children=0"
    "&selected_currency=EUR"
)

console.print(f"\n[bold]Booking scraper test (Camoufox)[/bold]")
console.print(f"Fechas: {checkin} -> {checkout} (1 noche, sabado)")
console.print(f"URL: {URL_BUSQUEDA[:120]}...\n")

console.print("[dim]Lanzando Camoufox (Firefox stealth)... primer arranque tarda ~10s[/dim]\n")

with Camoufox(
    headless=True,
    humanize=True,
    os=["windows"],
    locale="es-ES",
    geoip=True,
    block_images=True,
) as browser:
    page = browser.new_page()
    try:
        page.goto(URL_BUSQUEDA, wait_until="domcontentloaded", timeout=60_000)
    except Exception as e:
        console.print(f"[red]Timeout/error cargando Booking: {e}[/red]")
        raise

    # Esperar a que cargue contenido (selector de propiedad)
    try:
        page.wait_for_selector('[data-testid="property-card"]', timeout=15_000)
    except Exception:
        console.print("[yellow]No se vio property-card en 15s. Probablemente bloqueo.[/yellow]")

    # Espera humana
    page.wait_for_timeout(2500)

    html = page.content()
    title = page.title()
    url_final = page.url

console.print(f"[dim]URL final: {url_final[:120]}[/dim]")
console.print(f"[dim]Title: {title}[/dim]")

# Detectar bloqueo
bloqueo_signs = ["captcha", "Access denied", "Robot or human", "unusual traffic"]
bloqueado = any(s.lower() in html.lower() for s in bloqueo_signs)
if bloqueado:
    console.print("[red]⚠ Pagina con CAPTCHA / acceso denegado. Booking nos detectó.[/red]")
    out = Path("output") / f"booking_BLOQUEO_{checkin}.html"
    out.parent.mkdir(exist_ok=True)
    out.write_text(html[:50_000], encoding="utf-8")
    console.print(f"[dim]Snapshot HTML en {out}[/dim]")
    raise SystemExit(2)

# Parsear con parsel
sel = Selector(html)

# Booking usa data-testid="property-card" para cada listing
cards = sel.xpath('//div[@data-testid="property-card"]')
console.print(f"\n[green]Encontradas {len(cards)} property-cards[/green]\n")

resultados = []
for c in cards:
    titulo = c.xpath('.//div[@data-testid="title"]/text()').get(default="").strip()
    precio_text = " ".join(c.xpath('.//*[@data-testid="price-and-discounted-price"]//text()').getall()).strip()
    precio_raw = " ".join(c.xpath('.//span[contains(@class,"f6431b446c")]/text()').getall())  # fallback
    rating_text = c.xpath('.//div[@data-testid="review-score"]//text()').get(default="").strip()
    score_text = c.xpath('.//div[contains(@class, "fff1944c52")]//text()').get(default="").strip()
    address = c.xpath('.//*[@data-testid="address"]/text()').get(default="").strip()
    units_info = c.xpath('.//*[@data-testid="property-card-unit-configuration"]//text()').getall()
    units_info = " ".join(t.strip() for t in units_info if t.strip())

    # Limpiar precio
    precio = None
    for fuente in (precio_text, precio_raw):
        m = re.search(r"(\d[\d.,]*)\s*€", fuente)
        if m:
            num = m.group(1).replace(".", "").replace(",", ".")
            try:
                precio = float(num)
                break
            except ValueError:
                pass
    # rating: "Excelente Excelente puntuación 9,3" -> 9.3
    rating = None
    m = re.search(r"(\d{1,2}[.,]\d)", rating_text or score_text or "")
    if m:
        try: rating = float(m.group(1).replace(",", "."))
        except: pass

    resultados.append({
        "titulo": titulo,
        "precio": precio,
        "precio_text": precio_text or precio_raw,
        "rating": rating,
        "address": address,
        "units": units_info,
    })

# Tabla resumen
con_precio = [r for r in resultados if r["precio"] is not None]
con_precio.sort(key=lambda x: x["precio"])

tabla = Table(title=f"Booking Algeciras · {checkin} · 2 adultos")
tabla.add_column("Precio", justify="right")
tabla.add_column("Rating", justify="right")
tabla.add_column("Hab/Cama", overflow="fold", max_width=22)
tabla.add_column("Titulo", overflow="fold", max_width=50)

for r in con_precio[:25]:
    tabla.add_row(
        f"{r['precio']:.0f}€",
        f"{r['rating']:.1f}" if r['rating'] else "-",
        r["units"][:22] if r["units"] else "-",
        r["titulo"][:50],
    )
console.print(tabla)

# Estadisticas
if con_precio:
    precios = [r["precio"] for r in con_precio]
    console.print(f"\n[bold cyan]Precio noche Booking ({len(precios)} listings):[/bold cyan]")
    console.print(f"  Min:     {min(precios):.0f}€")
    if len(precios) >= 4:
        q = statistics.quantiles(precios, n=4)
        console.print(f"  P25:     {q[0]:.0f}€")
        console.print(f"  Mediana: {q[1]:.0f}€")
        console.print(f"  P75:     {q[2]:.0f}€")
    console.print(f"  Media:   {statistics.mean(precios):.0f}€")
    console.print(f"  Max:     {max(precios):.0f}€")
else:
    console.print("[red]Ninguno con precio extraido. Selector roto?[/red]")

# Guardar JSON
out_dir = Path("output")
out_dir.mkdir(exist_ok=True)
out_file = out_dir / f"booking_{checkin}.json"
out_file.write_text(json.dumps(resultados, indent=2, ensure_ascii=False), encoding="utf-8")
console.print(f"\n[dim]Resultados en {out_file}[/dim]")
