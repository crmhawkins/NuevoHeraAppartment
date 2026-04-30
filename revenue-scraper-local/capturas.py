"""Login en CRM local + screenshots de las 2 pantallas clave."""
from patchright.sync_api import sync_playwright
from pathlib import Path

OUT = Path(__file__).parent / "capturas"
OUT.mkdir(exist_ok=True)

with sync_playwright() as pw:
    browser = pw.chromium.launch(headless=True)
    context = browser.new_context(
        viewport={"width": 1440, "height": 900},
        locale="es-ES",
    )
    page = context.new_page()

    # 1) Login (hay tabs: certificado / hawcert / email-password)
    page.goto("http://127.0.0.1:8000/login", wait_until="domcontentloaded")
    page.wait_for_timeout(800)
    # Buscar tab "email" si existe
    for sel in ['button:has-text("Email")', 'a:has-text("Email")',
                'button:has-text("Contraseña")', '[onclick*="email"]',
                '[data-bs-target*="email"]']:
        if page.locator(sel).count() > 0:
            page.locator(sel).first.click()
            page.wait_for_timeout(500)
            break
    # Fill via #email/#password
    try:
        page.locator("#email").fill("admin@local")
        page.locator("#password").fill("1234")
    except Exception:
        page.screenshot(path=str(OUT / "01_login_FAIL.png"), full_page=True)
        raise
    page.screenshot(path=str(OUT / "01_login.png"), full_page=True)
    # Submit el formulario que contiene #email
    page.locator("form:has(#email) button[type='submit']").first.click()
    page.wait_for_load_state("networkidle")

    # 2) Reservas (panel con el boton nuevo)
    page.goto("http://127.0.0.1:8000/reservas", wait_until="networkidle")
    page.screenshot(path=str(OUT / "02_reservas_con_boton.png"), full_page=True)
    print("OK reservas")

    # 3) /admin/revenue/hoy
    page.goto("http://127.0.0.1:8000/admin/revenue/hoy", wait_until="networkidle")
    page.wait_for_timeout(1500)
    page.screenshot(path=str(OUT / "03_revenue_hoy_inicial.png"), full_page=True)
    print("OK revenue hoy inicial")

    # 4) Click "Calcular precios competencia" y esperar (~40s)
    btn = page.locator("#btn-scrape")
    if btn.is_visible() and btn.is_enabled():
        btn.click()
        # Esperar al kpi-mediana cambiar de "—" a un numero
        try:
            page.wait_for_function(
                "document.getElementById('kpi-mediana').textContent.trim() !== '—'",
                timeout=90_000,
            )
            page.wait_for_timeout(1000)
            page.screenshot(path=str(OUT / "04_revenue_hoy_con_datos.png"), full_page=True)
            print("OK revenue hoy con datos")
        except Exception as e:
            page.screenshot(path=str(OUT / "04_revenue_hoy_error.png"), full_page=True)
            print(f"No llego a poblarse: {e}")

    browser.close()
print("Screenshots:", list(OUT.glob("*.png")))
