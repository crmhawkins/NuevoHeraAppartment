"""
Lanza ambos scrapers (Airbnb + Booking) con la misma configuración del .env.
Output: 1 JSON con todo + resumen visual.

Uso:
    python run_test.py
"""
from __future__ import annotations

import json
import os
import sys
from datetime import datetime, date
from pathlib import Path

from rich.console import Console
from rich.panel import Panel

console = Console()


def main() -> int:
    env_file = Path(__file__).parent / ".env"
    if env_file.exists():
        from dotenv import load_dotenv
        load_dotenv(env_file)
    else:
        console.print("[red]Falta archivo .env. Copia .env.example y rellénalo.[/red]")
        return 1

    output_dir = Path(os.getenv("OUTPUT_DIR", "./output"))
    output_dir.mkdir(exist_ok=True)

    todo: dict = {
        "started_at": datetime.utcnow().isoformat() + "Z",
        "fecha_desde": os.getenv("FECHA_DESDE"),
        "fecha_hasta": os.getenv("FECHA_HASTA"),
        "airbnb": [],
        "booking": [],
    }

    # === Airbnb ===
    console.print(Panel.fit("[bold cyan]1/2 — Scrapeando Airbnb[/bold cyan]"))
    if os.getenv("AIRBNB_TEST_URLS", "").strip():
        from scrape_airbnb import scrape_multiples as airbnb_scrape
        urls = [u.strip() for u in os.getenv("AIRBNB_TEST_URLS").split(",") if u.strip()]
        fd = date.fromisoformat(os.getenv("FECHA_DESDE"))
        fh = date.fromisoformat(os.getenv("FECHA_HASTA"))
        delay_min = int(os.getenv("DELAY_MIN", "5"))
        delay_max = int(os.getenv("DELAY_MAX", "30"))
        todo["airbnb"] = airbnb_scrape(urls, fd, fh, delay_min, delay_max)
    else:
        console.print("[yellow]AIRBNB_TEST_URLS vacio, skip[/yellow]")

    # === Booking ===
    console.print(Panel.fit("[bold cyan]2/2 — Scrapeando Booking[/bold cyan]"))
    if os.getenv("BOOKING_TEST_URLS", "").strip():
        from scrape_booking import scrape_listing as booking_scrape
        urls = [u.strip() for u in os.getenv("BOOKING_TEST_URLS").split(",") if u.strip()]
        fd = date.fromisoformat(os.getenv("FECHA_DESDE"))
        fh = date.fromisoformat(os.getenv("FECHA_HASTA"))
        for url in urls:
            r = booking_scrape(url, fd, fh)
            todo["booking"].append(r)
    else:
        console.print("[yellow]BOOKING_TEST_URLS vacio, skip[/yellow]")

    todo["finished_at"] = datetime.utcnow().isoformat() + "Z"

    # Guardar
    out_file = output_dir / f"run_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
    out_file.write_text(json.dumps(todo, indent=2, ensure_ascii=False), encoding="utf-8")
    console.print(f"\n[bold green]Test completo guardado en {out_file}[/bold green]\n")

    # Resumen final
    n_airbnb_ok = sum(1 for r in todo["airbnb"] if not r.get("error"))
    n_booking_ok = sum(1 for r in todo["booking"] if not r.get("error"))
    console.print(Panel(
        f"Airbnb: {n_airbnb_ok}/{len(todo['airbnb'])} OK\n"
        f"Booking: {n_booking_ok}/{len(todo['booking'])} OK",
        title="Resultado final",
        border_style="green" if (n_airbnb_ok + n_booking_ok) > 0 else "red",
    ))

    return 0


if __name__ == "__main__":
    sys.exit(main())
