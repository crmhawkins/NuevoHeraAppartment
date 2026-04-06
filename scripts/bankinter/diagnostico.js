/**
 * Script de diagnostico: hace login y lista todos los enlaces del dashboard
 * para identificar el selector correcto del IBAN / movimientos.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const SELECTORS_PATH = path.join(__dirname, 'selectors.json');
const PROFILES_DIR = path.join(__dirname, 'chrome-profiles');
const OUTPUT_DIR = path.join(__dirname, '..', '..', 'storage', 'app', 'bankinter');

async function run() {
    const selectors = JSON.parse(fs.readFileSync(SELECTORS_PATH, 'utf-8'));
    const user = process.env.BANKINTER_USER;
    const password = process.env.BANKINTER_PASSWORD;
    const alias = process.env.BANKINTER_CUENTA_ALIAS || 'helen';
    const userDataDir = path.join(PROFILES_DIR, alias);

    if (!fs.existsSync(userDataDir)) fs.mkdirSync(userDataDir, { recursive: true });
    if (!fs.existsSync(OUTPUT_DIR)) fs.mkdirSync(OUTPUT_DIR, { recursive: true });

    const context = await chromium.launchPersistentContext(userDataDir, {
        headless: false,
        viewport: { width: 1366, height: 768 },
        locale: 'es-ES',
        timezoneId: 'Europe/Madrid',
        args: ['--disable-blink-features=AutomationControlled', '--no-sandbox'],
        ignoreHTTPSErrors: true
    });

    const page = context.pages()[0] || await context.newPage();
    page.setDefaultTimeout(30000);

    try {
        // Navegar
        console.log('Navegando a Bankinter...');
        await page.goto(selectors.homepage.url, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await page.waitForTimeout(2000);

        // Cerrar cookies
        for (const sel of (selectors.homepage.cookie_banner_reject || [])) {
            try { await page.click(sel, { timeout: 2000 }); break; } catch(e) {}
        }

        // Acceso clientes
        for (const sel of (selectors.homepage.acceso_clientes || [])) {
            try { await page.click(sel, { timeout: 5000 }); break; } catch(e) {}
        }
        await page.waitForTimeout(3000);

        // Login si es necesario
        const url1 = page.url();
        if (!url1.includes('/secure/')) {
            console.log('Haciendo login...');
            for (const sel of selectors.login.username_field) {
                try {
                    const el = await page.waitForSelector(sel, { timeout: 3000 });
                    if (el) { await el.click({clickCount:3}); await el.fill(user); break; }
                } catch(e) {}
            }
            for (const sel of selectors.login.password_field) {
                try {
                    const el = await page.waitForSelector(sel, { timeout: 3000 });
                    if (el) { await el.click({clickCount:3}); await el.fill(password); break; }
                } catch(e) {}
            }
            for (const sel of selectors.login.submit_button) {
                try { await page.click(sel, { timeout: 3000 }); break; } catch(e) {}
            }
            await page.waitForTimeout(8000);
        } else {
            console.log('Ya logueado (sesion persistente)');
        }

        console.log(`URL dashboard: ${page.url()}`);

        // Screenshot del dashboard
        await page.screenshot({ path: path.join(OUTPUT_DIR, 'diag_dashboard_full.png'), fullPage: true });

        // ====== DIAGNOSTICO: Listar todos los enlaces ======
        const links = await page.evaluate(() => {
            const allLinks = Array.from(document.querySelectorAll('a'));
            return allLinks.map((a, i) => ({
                index: i,
                text: (a.textContent || '').trim().substring(0, 100),
                href: a.href || '',
                classes: a.className || '',
                id: a.id || '',
                parentText: (a.parentElement?.textContent || '').trim().substring(0, 60),
                visible: a.offsetParent !== null,
                rect: a.getBoundingClientRect() ? {
                    x: Math.round(a.getBoundingClientRect().x),
                    y: Math.round(a.getBoundingClientRect().y),
                    w: Math.round(a.getBoundingClientRect().width),
                    h: Math.round(a.getBoundingClientRect().height)
                } : null
            }));
        });

        // Filtrar los que contienen ES (para encontrar IBAN)
        console.log('\n=== ENLACES CON "ES" EN EL TEXTO ===');
        links.filter(l => l.text.includes('ES') && l.visible).forEach(l => {
            console.log(`  [${l.index}] "${l.text}" -> ${l.href} (visible:${l.visible}, pos:${l.rect?.x},${l.rect?.y})`);
        });

        // Todos los enlaces visibles con href que contengan "cuenta", "movimiento", "extracto"
        console.log('\n=== ENLACES RELACIONADOS CON CUENTAS/MOVIMIENTOS ===');
        links.filter(l => l.visible && (
            l.href.includes('cuenta') || l.href.includes('movimiento') || l.href.includes('extracto') ||
            l.href.includes('posicion') || l.href.includes('saldo') ||
            l.text.toLowerCase().includes('cuenta') || l.text.toLowerCase().includes('movimiento') ||
            l.text.toLowerCase().includes('extracto') || l.text.toLowerCase().includes('saldo')
        )).forEach(l => {
            console.log(`  [${l.index}] "${l.text}" -> ${l.href}`);
        });

        // Todos los enlaces visibles en la seccion de cuentas (area superior)
        console.log('\n=== ENLACES VISIBLES EN AREA SUPERIOR (y < 400px) ===');
        links.filter(l => l.visible && l.rect && l.rect.y < 400 && l.rect.y > 100 && l.text.length > 0).forEach(l => {
            console.log(`  [${l.index}] "${l.text}" -> ${l.href} (y:${l.rect.y})`);
        });

        // Buscar elementos clickables que no son <a> (botones, spans, etc)
        const clickables = await page.evaluate(() => {
            const elements = Array.from(document.querySelectorAll('[onclick], [role="button"], button, [data-link], [tabindex]'));
            return elements.filter(e => e.offsetParent !== null).map((e, i) => ({
                index: i,
                tag: e.tagName,
                text: (e.textContent || '').trim().substring(0, 100),
                classes: e.className?.substring?.(0, 80) || '',
                onclick: (e.getAttribute('onclick') || '').substring(0, 100),
                dataLink: e.getAttribute('data-link') || '',
                rect: { x: Math.round(e.getBoundingClientRect().x), y: Math.round(e.getBoundingClientRect().y) }
            }));
        });

        console.log('\n=== ELEMENTOS CLICKABLES (no <a>) EN ZONA CUENTAS ===');
        clickables.filter(c => c.rect.y > 100 && c.rect.y < 500).forEach(c => {
            console.log(`  [${c.tag}] "${c.text.substring(0,60)}" class="${c.classes.substring(0,50)}" onclick="${c.onclick}" data-link="${c.dataLink}" (y:${c.rect.y})`);
        });

        // Guardar todo el HTML de la seccion de cuentas
        const cuentasHtml = await page.evaluate(() => {
            // Buscar seccion que contenga "Cuentas Corrientes"
            const sections = Array.from(document.querySelectorAll('section, div, table'));
            for (const s of sections) {
                if (s.textContent?.includes('Cuentas Corrientes') && s.innerHTML.length < 10000) {
                    return s.outerHTML;
                }
            }
            // Fallback: area central
            const main = document.querySelector('main, [role="main"], .content, #content');
            return main ? main.innerHTML.substring(0, 5000) : 'No encontrado';
        });

        fs.writeFileSync(path.join(OUTPUT_DIR, 'diag_cuentas_html.html'), cuentasHtml, 'utf-8');
        console.log('\n[OK] HTML de seccion cuentas guardado en diag_cuentas_html.html');

    } catch (e) {
        console.error(`ERROR: ${e.message}`);
        await page.screenshot({ path: path.join(OUTPUT_DIR, 'diag_error.png') });
    } finally {
        await context.close();
    }
}

run().catch(e => { console.error(`FATAL: ${e.message}`); process.exit(1); });
