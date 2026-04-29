"""
Scraper de Airbnb usando pyairbnb (intercepta el GraphQL interno).

No requiere browser ni proxies. Funciona desde cualquier IP normalmente
porque la API GraphQL interna de Airbnb es relativamente permisiva con
volúmenes bajos y user-agents normales.

Uso:
    python scrape_airbnb.py
"""
from __future__ import annotations

import json
import os
import sys
import time
import random
import re
from datetime import date, datetime
from pathlib import Path
from typing import Any

from rich.console import Console
from rich.table import Table
from rich.progress import track

console = Console()


def extraer_room_id(url: str) -> str | None:
    """De https://www.airbnb.es/rooms/12345678 -> '12345678'"""
    match = re.search(r"/rooms/(\d+)", url)
    return match.group(1) if match else None


def scrape_listing(url: str, fecha_desde: date, fecha_hasta: date) -> dict[str, Any]:
    """
    Scrapea un listing de Airbnb. Devuelve dict con:
    - url, room_id, title, ratings
    - calendar: lista de noches con disponibilidad y precio

    Si pyairbnb falla, devuelve dict con 'error'.
    """
    try:
        # Importamos pyairbnb aquí para que el script funcione aunque no
        # esté instalado y se pueda ver el error claro.
        import pyairbnb
    except ImportError:
        return {"url": url, "error": "pyairbnb no instalado. pip install pyairbnb"}

    room_id = extraer_room_id(url)
    if not room_id:
        return {"url": url, "error": f"No se pudo extraer room_id de {url}"}

    resultado: dict[str, Any] = {
        "url": url,
        "room_id": room_id,
        "scrapeado_at": datetime.utcnow().isoformat() + "Z",
        "calendar": [],
    }

    try:
        # 1) Datos del listing (title, ratings, etc.)
        # pyairbnb tiene varias funciones; según versión cambian. Usamos
        # las más estables.
        details = pyairbnb.get_details(
            room_url=url,
            check_in=fecha_desde.isoformat(),
            check_out=fecha_hasta.isoformat(),
            currency="EUR",
            language="es",
        )
        if isinstance(details, dict):
            resultado["title"] = details.get("title")
            resultado["rating"] = details.get("rating", {}).get("guest_satisfaction_overall")
            resultado["reviews_count"] = details.get("rating", {}).get("review_count")
            resultado["capacity"] = details.get("guest_capacity")
            resultado["bedrooms"] = details.get("bedrooms")
    except Exception as e:
        resultado["details_error"] = f"{type(e).__name__}: {e}"

    # 2) Calendario de precios y disponibilidad
    try:
        cal = pyairbnb.get_calendar(
            room_id=room_id,
            currency="EUR",
            language="es",
        )
        # pyairbnb devuelve estructura distinta segun version. Normalizamos.
        if isinstance(cal, dict) and "calendar_months" in cal:
            for month in cal["calendar_months"]:
                for day in month.get("days", []):
                    fecha_str = day.get("calendar_date")
                    if not fecha_str:
                        continue
                    try:
                        d = date.fromisoformat(fecha_str)
                    except ValueError:
                        continue
                    if not (fecha_desde <= d <= fecha_hasta):
                        continue
                    precio = None
                    if day.get("price"):
                        precio = (day["price"].get("local_price")
                                  or day["price"].get("base_price"))
                    resultado["calendar"].append({
                        "fecha": fecha_str,
                        "disponible": day.get("available", False),
                        "precio": precio,
                        "min_noches": day.get("min_nights"),
                    })
    except Exception as e:
        resultado["calendar_error"] = f"{type(e).__name__}: {e}"

    return resultado


def scrape_multiples(urls: list[str], fecha_desde: date, fecha_hasta: date,
                    delay_min: int = 5, delay_max: int = 30) -> list[dict]:
    resultados = []
    for url in track(urls, description="Scrapeando Airbnb..."):
        r = scrape_listing(url.strip(), fecha_desde, fecha_hasta)
        resultados.append(r)
        if url != urls[-1]:
            d = random.uniform(delay_min, delay_max)
            console.print(f"   [dim]Esperando {d:.1f}s antes del siguiente...[/dim]")
            time.sleep(d)
    return resultados


def imprimir_resumen(resultados: list[dict]) -> None:
    table = Table(title="Resumen Airbnb", show_lines=True)
    table.add_column("Listing", style="cyan", overflow="fold")
    table.add_column("Title")
    table.add_column("Rating")
    table.add_column("Noches OK")
    table.add_column("Precio min")
    table.add_column("Precio max")
    table.add_column("Estado")

    for r in resultados:
        cal = r.get("calendar") or []
        precios = [c["precio"] for c in cal if c.get("precio") and c.get("disponible")]
        estado = "[green]OK[/green]"
        if r.get("error") or r.get("details_error") or r.get("calendar_error"):
            estado = "[red]ERROR[/red]"
        table.add_row(
            r.get("room_id", "?"),
            (r.get("title") or "")[:40],
            str(r.get("rating", "")),
            str(len([c for c in cal if c.get("disponible")])),
            f"{min(precios):.0f}€" if precios else "-",
            f"{max(precios):.0f}€" if precios else "-",
            estado,
        )
    console.print(table)


def main() -> int:
    # Cargar .env si existe
    env_file = Path(__file__).parent / ".env"
    if env_file.exists():
        from dotenv import load_dotenv
        load_dotenv(env_file)

    urls_str = os.getenv("AIRBNB_TEST_URLS", "")
    if not urls_str.strip():
        console.print("[red]Falta AIRBNB_TEST_URLS en .env[/red]")
        console.print("Copia .env.example a .env y rellénalo con URLs reales de Airbnb.")
        return 1

    urls = [u.strip() for u in urls_str.split(",") if u.strip()]
    fecha_desde = date.fromisoformat(os.getenv("FECHA_DESDE", "2026-05-01"))
    fecha_hasta = date.fromisoformat(os.getenv("FECHA_HASTA", "2026-05-15"))
    delay_min = int(os.getenv("DELAY_MIN", "5"))
    delay_max = int(os.getenv("DELAY_MAX", "30"))
    output_dir = Path(os.getenv("OUTPUT_DIR", "./output"))
    output_dir.mkdir(exist_ok=True)

    console.print(f"\n[bold]Airbnb scraper — test local[/bold]")
    console.print(f"URLs: {len(urls)}")
    console.print(f"Rango: {fecha_desde} → {fecha_hasta}")
    console.print(f"Delay: {delay_min}-{delay_max}s\n")

    resultados = scrape_multiples(urls, fecha_desde, fecha_hasta,
                                  delay_min=delay_min, delay_max=delay_max)

    # Guardar JSON
    out_file = output_dir / f"airbnb_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
    out_file.write_text(json.dumps(resultados, indent=2, ensure_ascii=False), encoding="utf-8")
    console.print(f"\n[green]Resultados guardados en {out_file}[/green]\n")

    imprimir_resumen(resultados)
    return 0


if __name__ == "__main__":
    sys.exit(main())
