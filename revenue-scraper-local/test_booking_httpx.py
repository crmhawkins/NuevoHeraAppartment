"""
Test: scraping de Booking sin browser, solo con httpx + parsel.
Probamos primero esta via porque:
- No requiere Camoufox (que necesita Visual C++ Redistributable)
- Mas rapido (~2s vs ~10s con Camoufox)
- Si funciona: cero dependencias pesadas

Si Booking nos sirve HTML completo con precios -> OK.
Si responde 403 / pagina vacia -> hay que ir a Camoufox.
"""
from __future__ import annotations
import json
import re
import statistics
from datetime import date, timedelta
from pathlib import Path

import httpx
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

# Headers de un usuario real Chrome reciente
HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
    "Accept-Language": "es-ES,es;q=0.9,en;q=0.7",
    "Accept-Encoding": "gzip, deflate, br, zstd",
    "Connection": "keep-alive",
    "Upgrade-Insecure-Requests": "1",
    "Sec-Fetch-Dest": "document",
    "Sec-Fetch-Mode": "navigate",
    "Sec-Fetch-Site": "none",
    "Sec-Fetch-User": "?1",
    "Sec-Ch-Ua": '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
    "Sec-Ch-Ua-Mobile": "?0",
    "Sec-Ch-Ua-Platform": '"Windows"',
    "Cache-Control": "max-age=0",
}

console.print(f"\n[bold]Booking via httpx (sin browser)[/bold]")
console.print(f"Fechas: {checkin} -> {checkout}")
console.print(f"URL: {URL[:120]}...\n")

with httpx.Client(http2=True, follow_redirects=True, headers=HEADERS, timeout=30) as client:
    r = client.get(URL)
    console.print(f"[dim]HTTP {r.status_code} · {len(r.text):,} bytes recibidos[/dim]")

    if r.status_code != 200:
        console.print(f"[red]Booking respondio {r.status_code}. Body preview:[/red]\n{r.text[:500]}")
        raise SystemExit(2)

    html = r.text

# Detectar bloqueo
bloqueo = any(s in html.lower() for s in ["captcha", "access denied", "robot or human", "unusual traffic"])
if bloqueo:
    console.print("[red]!! Bloqueo detectado en el HTML[/red]")
    Path("output").mkdir(exist_ok=True)
    Path("output/booking_BLOQUEO.html").write_text(html[:50_000], encoding="utf-8")
    raise SystemExit(2)

sel = Selector(html)

# Booking embebe los datos en script JSON
# 1) Probar __SEARCH_RESULTS__ o ROOM_LIST_DATA
script_data = sel.xpath('//script[contains(text(), "b_search_results_card")]/text()').get()

# 2) Property cards visuales
cards = sel.xpath('//div[@data-testid="property-card"]')
console.print(f"property-cards visibles: {len(cards)}\n")

resultados = []
for c in cards:
    titulo = c.xpath('.//div[@data-testid="title"]/text()').get(default="").strip()
    # Diferentes selectores que Booking ha usado
    precios_raw = []
    for xp in [
        './/*[@data-testid="price-and-discounted-price"]//text()',
        './/span[contains(@class,"f6431b446c")]//text()',
        './/span[contains(@aria-label, "Precio")]/text()',
        './/span[contains(text(),"€")]/text()',
    ]:
        precios_raw.extend(c.xpath(xp).getall())
    precio_text = " ".join(t.strip() for t in precios_raw if t.strip())

    rating_text = " ".join(c.xpath('.//div[@data-testid="review-score"]//text()').getall()).strip()
    address = c.xpath('.//*[@data-testid="address"]/text()').get(default="").strip()

    units = c.xpath('.//*[@data-testid="property-card-unit-configuration"]//text()').getall()
    units_str = " ".join(t.strip() for t in units if t.strip())

    # Sacar precio: numeros con € o EUR
    precio = None
    matches = re.findall(r"(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d+)?)\s*€", precio_text)
    if matches:
        # tomar el menor (suele ser por noche, no total)
        nums = []
        for m in matches:
            try:
                nums.append(float(m.replace(".", "").replace(",", ".")))
            except ValueError:
                pass
        if nums:
            precio = min(nums)

    rating = None
    m = re.search(r"(\d{1,2}[.,]\d)", rating_text)
    if m:
        try: rating = float(m.group(1).replace(",", "."))
        except: pass

    resultados.append({
        "titulo": titulo,
        "precio": precio,
        "precio_text": precio_text[:80],
        "rating": rating,
        "address": address,
        "units": units_str,
    })

con_precio = [r for r in resultados if r["precio"] is not None]
con_precio.sort(key=lambda x: x["precio"])

tabla = Table(title=f"Booking Algeciras · {checkin} · 2 adultos (vista busqueda)")
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
    console.print(f"\n[bold cyan]Precio noche Booking ({len(precios)} listings):[/bold cyan]")
    console.print(f"  Min:     {min(precios):.0f}€")
    if len(precios) >= 4:
        q = statistics.quantiles(precios, n=4)
        console.print(f"  P25:     {q[0]:.0f}€")
        console.print(f"  Mediana: {q[1]:.0f}€")
        console.print(f"  P75:     {q[2]:.0f}€")
    else:
        console.print(f"  Mediana: {statistics.median(precios):.0f}€")
    console.print(f"  Media:   {statistics.mean(precios):.0f}€")
    console.print(f"  Max:     {max(precios):.0f}€")
else:
    console.print("[yellow]Ninguno con precio extraido (selectores cambiaron o cards sin precio en HTML inicial).[/yellow]")
    # Volcar HTML para inspeccion
    Path("output").mkdir(exist_ok=True)
    Path(f"output/booking_html_{checkin}.html").write_text(html[:300_000], encoding="utf-8")
    console.print("[dim]HTML guardado en output/booking_html_*.html para inspeccion[/dim]")

# Guardar
out_dir = Path("output"); out_dir.mkdir(exist_ok=True)
(out_dir / f"booking_httpx_{checkin}.json").write_text(
    json.dumps(resultados, indent=2, ensure_ascii=False), encoding="utf-8"
)
