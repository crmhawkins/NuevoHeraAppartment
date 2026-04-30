"""
Busqueda real de apartamentos en el centro de Algeciras para 2 personas.
Calcula precio medio por noche.
"""
import pyairbnb
import statistics
import json
import re
from datetime import date, timedelta
from pathlib import Path
from rich.console import Console
from rich.table import Table

console = Console()

# Bounding box mas amplio: incluye casco antiguo + ensanche + La Reconquista
# Algeciras tiene oferta limitada en Airbnb, ampliamos para tener N mayor
NE_LAT = 36.150
NE_LON = -5.420
SW_LAT = 36.100
SW_LON = -5.490
ZOOM = 13

# Fechas: proximo finde sabado-domingo
hoy = date.today()
dias_hasta_sabado = (5 - hoy.weekday()) % 7 or 7
checkin = hoy + timedelta(days=dias_hasta_sabado)
checkout = checkin + timedelta(days=1)

console.print(f"\n[bold]Busqueda Airbnb — Centro de Algeciras, 2 adultos[/bold]")
console.print(f"Fechas: {checkin} -> {checkout} (1 noche, sabado)")
console.print(f"Bounding box: NE({NE_LAT},{NE_LON}) SW({SW_LAT},{SW_LON})\n")

console.print("[dim]Llamando a Airbnb GraphQL...[/dim]")

resultados = pyairbnb.search_all(
    check_in=checkin.isoformat(),
    check_out=checkout.isoformat(),
    ne_lat=NE_LAT,
    ne_long=NE_LON,
    sw_lat=SW_LAT,
    sw_long=SW_LON,
    zoom_value=ZOOM,
    price_min=0,
    price_max=500,
    adults=2,
    currency="EUR",
    language="es",
)

console.print(f"[green]Encontrados: {len(resultados)} listings[/green]\n")

# Guardar raw inmediatamente
out_dir = Path("output")
out_dir.mkdir(exist_ok=True)
out_file = out_dir / f"algeciras_centro_{checkin}.json"
out_file.write_text(json.dumps(resultados, indent=2, ensure_ascii=False, default=str), encoding="utf-8")
console.print(f"[dim]JSON crudo guardado en {out_file}[/dim]\n")

if not resultados:
    console.print("[red]Sin resultados. Bounding box demasiado pequeno o Airbnb bloqueando.[/red]")
    raise SystemExit(1)


def extraer_precio(r: dict) -> float | None:
    """
    Intenta varias rutas. Devuelve precio numerico (EUR) o None.
    pyairbnb 2.2.1 estructura observada:
      price.unit.amount    -> precio por noche (redondeado)
      price.break_down[0].description  -> "1 noche por 56,20"
      price.break_down[0].amount       -> 5620.0 (en centimos!)
    """
    p = r.get("price") or {}
    if not isinstance(p, dict):
        return None
    # Mejor opcion: unit.amount (precio por noche redondeado, p.ej. 57.0 EUR)
    unit = p.get("unit")
    if isinstance(unit, dict):
        amt = unit.get("amount")
        if isinstance(amt, (int, float)) and amt > 0:
            return float(amt)
    # Segunda: total.amount
    total = p.get("total")
    if isinstance(total, dict):
        amt = total.get("amount")
        if isinstance(amt, (int, float)) and amt > 0:
            return float(amt)
    # Tercera: parsear break_down. Aqui hay que ignorar el "1 noche por" inicial
    # y pillar el numero que sigue a "por". Formatos: "1 noche por 56,20€",
    # "2 noches por 134,40", "3 nights for 220".
    bd = p.get("break_down")
    if isinstance(bd, list):
        for item in bd:
            if not isinstance(item, dict):
                continue
            desc = item.get("description", "")
            # Patron: "X noches por Y" o "X nights for Y"
            m = re.search(r"\bpor\s+([\d.,]+)|\bfor\s+([\d.,]+)", desc, re.IGNORECASE)
            if m:
                val_str = (m.group(1) or m.group(2)).replace(",", ".")
                try:
                    val = float(val_str)
                    if val > 0:
                        return val
                except ValueError:
                    pass
            # Patron alternativo: si hay solo un numero grande > 5 lo tomamos
            # (descartamos el "1" de "1 noche")
            numeros = re.findall(r"(\d+[.,]?\d*)", desc)
            for n in numeros:
                v = float(n.replace(",", "."))
                if v >= 10:  # filtra el "1 noche"
                    return v
    return None


# Extraer precios
filas = []
for r in resultados:
    precio = extraer_precio(r)
    titulo = r.get("title") or r.get("name") or "?"
    if isinstance(titulo, dict):
        titulo = titulo.get("text") or titulo.get("name") or "?"
    rating = r.get("rating") or {}
    if isinstance(rating, dict):
        rating_val = rating.get("value")
        reviews = rating.get("reviewCount")
    else:
        rating_val, reviews = None, None
    coords = r.get("coordinates") or {}
    # Tipo: en pyairbnb 2.2.1 esta en "type" (string tipo "Hotel", "Apartamento entero", etc.)
    # o en "kind", o en "name" (que dice "Vivienda en Algeciras", "Apartamento", etc.)
    tipo = r.get("type") or r.get("kind") or r.get("name") or "?"
    if isinstance(tipo, dict):
        tipo = tipo.get("text") or tipo.get("name") or "?"

    filas.append({
        "id": r.get("room_id") or r.get("id"),
        "titulo": str(titulo)[:60],
        "precio": precio,
        "rating": rating_val,
        "reviews": reviews,
        "bedrooms": r.get("bedrooms"),
        "capacity": r.get("personCapacity") or r.get("guest_capacity"),
        "type": str(tipo)[:25],
        "lat": coords.get("latitude") if isinstance(coords, dict) else None,
        "lon": coords.get("longitude") if isinstance(coords, dict) else None,
    })

filas_con_precio = [f for f in filas if f["precio"] is not None]
filas_con_precio.sort(key=lambda x: float(x["precio"]))

# Tabla resumen
tabla = Table(title=f"Top 25 mas baratos · centro Algeciras · noche {checkin}")
tabla.add_column("Precio", justify="right")
tabla.add_column("Tipo", max_width=15)
tabla.add_column("Hab", justify="right")
tabla.add_column("Cap", justify="right")
tabla.add_column("Rating")
tabla.add_column("Titulo", overflow="fold", max_width=55)
for f in filas_con_precio[:25]:
    tabla.add_row(
        f"{f['precio']:.0f}€",
        str(f["type"] or "?")[:12],
        str(f["bedrooms"] or "?"),
        str(f["capacity"] or "?"),
        f"{f['rating']:.2f}" if f['rating'] else "-",
        f["titulo"],
    )
console.print(tabla)

# Estadisticas
precios = [float(f["precio"]) for f in filas_con_precio]
console.print(f"\n[bold cyan]Estadisticas precio noche (todos, 2 adultos):[/bold cyan]")
console.print(f"  N total con precio: {len(precios)} de {len(resultados)}")
if precios:
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
    if len(precios) > 1:
        console.print(f"  Stdev:   {statistics.stdev(precios):.0f}€")

# Sub-conjunto: alojamientos enteros (no habitacion privada/compartida)
def es_alojamiento_entero(t):
    if not t:
        return False
    s = str(t).lower()
    return any(x in s for x in ("entire", "apartment", "apartamento", "loft", "casa", "house"))

apts = [f for f in filas_con_precio if es_alojamiento_entero(f["type"])]
if apts:
    p_apts = [float(f["precio"]) for f in apts]
    console.print(f"\n[bold cyan]Solo alojamientos enteros (apartamento/loft/casa) — {len(p_apts)}:[/bold cyan]")
    console.print(f"  Mediana: {statistics.median(p_apts):.0f}€")
    console.print(f"  Media:   {statistics.mean(p_apts):.0f}€")
