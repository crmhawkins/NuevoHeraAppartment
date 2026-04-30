"""
Reproduce el flujo del usuario en Chromium REAL:
  1. Abrir /login (incognito)
  2. Login admin@local / 1234
  3. Verificar redirect a /reservas (no /dashboard)
  4. Click "Calcular Revenue"
  5. Verificar pantalla revenue/hoy
  6. Click "Calcular precios competencia"
  7. Esperar resultados y verificar
Cualquier error = se reporta.
"""
import sys
sys.stdout.reconfigure(encoding="utf-8")

from patchright.sync_api import sync_playwright
from pathlib import Path
import re

OUT = Path(__file__).parent / "capturas_test"
OUT.mkdir(exist_ok=True)

errores = []

with sync_playwright() as pw:
    browser = pw.chromium.launch(headless=True)
    # Contexto SIN cookies persistentes (incognito-like)
    context = browser.new_context(
        viewport={"width": 1440, "height": 900},
        locale="es-ES",
    )
    page = context.new_page()

    # === Capturar errores de consola ===
    def on_console(msg):
        if msg.type in ("error", "warning"):
            print(f"[CONSOLE-{msg.type.upper()}] {msg.text[:200]}")
    page.on("console", on_console)

    def on_pageerror(err):
        print(f"[PAGE-ERROR] {err}")
        errores.append(f"page error: {err}")
    page.on("pageerror", on_pageerror)

    # ===== PASO 1: Login =====
    print("\n[PASO 1] Abriendo /login...")
    page.goto("http://127.0.0.1:8000/login", wait_until="domcontentloaded")
    page.wait_for_timeout(800)
    page.screenshot(path=str(OUT / "01_login.png"), full_page=False)

    # El form email/password esta oculto detras del fallback toggle
    fallback = page.locator("#loginFallbackToggle")
    if fallback.count() > 0 and fallback.is_visible():
        print("[PASO 1.5] Pulsando 'Si falla el certificado'...")
        fallback.click()
        page.wait_for_timeout(500)

    # Rellenar email y password
    try:
        page.locator("#email").fill("admin@local", timeout=8000)
        page.locator("#password").fill("1234", timeout=8000)
    except Exception as e:
        errores.append(f"No pude rellenar form login: {e}")
        page.screenshot(path=str(OUT / "01_login_FILL_FAIL.png"))
        print(f"FAIL: {e}")
        browser.close()
        raise SystemExit(1)

    # Submit el formulario que contiene #email
    print("[PASO 2] Submitting login...")
    page.locator("form:has(#email) button[type='submit']").first.click()
    try:
        page.wait_for_load_state("networkidle", timeout=15_000)
    except Exception:
        pass
    page.wait_for_timeout(1500)

    final_url = page.url
    print(f"[PASO 2] URL final tras login: {final_url}")
    page.screenshot(path=str(OUT / "02_post_login.png"), full_page=False)

    if "/login" in final_url:
        errores.append(f"Login falló — sigue en /login (URL: {final_url})")
        # Dump HTML de error
        Path(OUT / "02_post_login_html.txt").write_text(page.content()[:30_000], encoding="utf-8")
        print("FAIL login: ver capturas_test/02_post_login.png + 02_post_login_html.txt")
    elif "/dashboard" in final_url:
        errores.append(f"Login redirigió a /dashboard (debería ir a /reservas). URL: {final_url}")
        print("FAIL: redirect a /dashboard en lugar de /reservas")
    elif "/reservas" in final_url:
        print(f"OK: login + redirect a /reservas")
    else:
        print(f"WARN: redirect a URL inesperada: {final_url}")

    # ===== PASO 3: Verificar boton "Calcular Revenue" en /reservas =====
    print("\n[PASO 3] Verificando boton Calcular Revenue en /reservas...")
    if "/reservas" not in final_url:
        page.goto("http://127.0.0.1:8000/reservas", wait_until="networkidle")
    page.wait_for_timeout(1500)
    page.screenshot(path=str(OUT / "03_reservas.png"), full_page=False)

    btn_revenue = page.locator('a:has-text("Calcular Revenue")')
    if btn_revenue.count() == 0:
        errores.append("Boton 'Calcular Revenue' NO encontrado en /reservas")
        print("FAIL: boton no encontrado")
    else:
        print(f"OK: boton 'Calcular Revenue' encontrado")

    # ===== PASO 4: Click en boton =====
    print("\n[PASO 4] Click en 'Calcular Revenue'...")
    if btn_revenue.count() > 0:
        btn_revenue.first.click()
        try:
            page.wait_for_url("**/admin/revenue/hoy*", timeout=10_000)
        except Exception:
            pass
        page.wait_for_load_state("networkidle", timeout=10_000)
        page.wait_for_timeout(1500)
        url_revenue = page.url
        print(f"URL: {url_revenue}")
        page.screenshot(path=str(OUT / "04_revenue_hoy.png"), full_page=True)

        if "/admin/revenue/hoy" not in url_revenue:
            errores.append(f"No llegamos a /admin/revenue/hoy (estamos en {url_revenue})")
        else:
            # Verificar que la pantalla tiene el contenido esperado
            html = page.content()
            checks = {
                "Calcular Revenue (titulo)": "Calcular Revenue" in html,
                "Apartamentos LIBRES KPI": "LIBRES" in html,
                "Apartamentos OCUPADOS KPI": "OCUPADOS" in html,
                "Costa 1A": "Costa 1A" in html,
                "Boton Calcular precios competencia": "Calcular precios competencia" in html,
                "Scraper Python activo": "activo en" in html or "scraper" in html.lower(),
            }
            for k, v in checks.items():
                print(f"  [{('OK' if v else 'FAIL')}] {k}")
                if not v:
                    errores.append(f"Falta en pantalla revenue/hoy: {k}")

    # ===== PASO 5: Click "Calcular precios competencia" =====
    if not errores:  # solo si llegamos hasta aqui sin errores
        print("\n[PASO 5] Click en 'Calcular precios competencia' (~40s)...")
        btn_scrape = page.locator("#btn-scrape")
        if btn_scrape.count() > 0 and btn_scrape.is_visible() and btn_scrape.is_enabled():
            btn_scrape.click()
            # Esperar al kpi cambiar
            try:
                page.wait_for_function(
                    "document.getElementById('kpi-mediana').textContent.trim() !== '—' && "
                    "document.getElementById('kpi-mediana').textContent.trim() !== ''",
                    timeout=120_000,
                )
                page.wait_for_timeout(1500)
                page.screenshot(path=str(OUT / "05_scrape_done.png"), full_page=True)
                kpi = page.locator("#kpi-mediana").inner_text()
                listings = page.locator("#kpi-listings").inner_text()
                print(f"OK: scrape completado · mediana={kpi} · listings={listings}")
            except Exception as e:
                page.screenshot(path=str(OUT / "05_scrape_FAIL.png"), full_page=True)
                errores.append(f"Scrape no termino o no actualizo KPIs: {e}")
                # Capturar errores en el div msg-area
                try:
                    msg = page.locator("#msg-area").inner_html()
                    print(f"msg-area: {msg[:500]}")
                except:
                    pass
        else:
            print("Boton scrape no disponible (deshabilitado?)")

    browser.close()

# ===== RESUMEN =====
print("\n" + "="*60)
if errores:
    print(f"FALLOS DETECTADOS ({len(errores)}):")
    for e in errores:
        print(f"  - {e}")
    sys.exit(1)
else:
    print("TODO OK")
print("Screenshots en:", OUT)
