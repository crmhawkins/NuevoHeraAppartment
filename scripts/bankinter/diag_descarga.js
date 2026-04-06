/**
 * Diagnostico: navega a movimientos y lista todos los botones/iconos de descarga
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const PROFILES_DIR = path.join(__dirname, 'chrome-profiles');
const OUTPUT_DIR = path.join(__dirname, '..', '..', 'storage', 'app', 'bankinter');

async function run() {
    const alias = process.env.BANKINTER_CUENTA_ALIAS || 'helen';
    const userDataDir = path.join(PROFILES_DIR, alias);

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
        // Navegar directamente a movimientos (ya tenemos sesion del perfil persistente)
        const movUrl = 'https://empresas.bankinter.com/secure/es/cuentas-y-tarjetas/cuentas?tab=movements&type=CURRENT&id=7b96D9eoecwfw99OoOo2ccO2fs9xoDJeOBHc9B2o';
        console.log('Navegando a movimientos...');
        await page.goto(movUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await page.waitForTimeout(5000);

        // Verificar si estamos logueados
        const url = page.url();
        console.log(`URL actual: ${url}`);
        if (!url.includes('/secure/')) {
            console.log('No logueado. Ejecuta primero el scraper completo para hacer login.');
            await context.close();
            return;
        }

        await page.screenshot({ path: path.join(OUTPUT_DIR, 'diag_movimientos_full.png'), fullPage: true });

        // Buscar TODOS los elementos interactivos en la pagina
        const elements = await page.evaluate(() => {
            const result = { links: [], buttons: [], icons: [], downloads: [], selects: [] };

            // Links
            document.querySelectorAll('a').forEach(a => {
                if (a.offsetParent !== null || a.closest('[aria-hidden="false"]')) {
                    const r = a.getBoundingClientRect();
                    result.links.push({
                        text: (a.textContent || '').trim().substring(0, 80),
                        href: a.href || '',
                        classes: (a.className || '').substring(0, 80),
                        title: a.title || '',
                        download: a.download || '',
                        pos: `${Math.round(r.x)},${Math.round(r.y)}`
                    });
                }
            });

            // Buttons
            document.querySelectorAll('button, [role="button"]').forEach(b => {
                if (b.offsetParent !== null) {
                    const r = b.getBoundingClientRect();
                    result.buttons.push({
                        text: (b.textContent || '').trim().substring(0, 80),
                        classes: (b.className || '').substring(0, 80),
                        title: b.title || '',
                        ariaLabel: b.getAttribute('aria-label') || '',
                        type: b.type || '',
                        pos: `${Math.round(r.x)},${Math.round(r.y)}`
                    });
                }
            });

            // Icons (span/i with icon classes)
            document.querySelectorAll('[class*="icon"], [class*="download"], [class*="export"], [class*="excel"]').forEach(el => {
                if (el.offsetParent !== null) {
                    const r = el.getBoundingClientRect();
                    if (r.width > 0) {
                        result.icons.push({
                            tag: el.tagName,
                            text: (el.textContent || '').trim().substring(0, 40),
                            classes: (el.className || '').substring(0, 100),
                            title: el.title || '',
                            parentTag: el.parentElement?.tagName || '',
                            parentClasses: (el.parentElement?.className || '').substring(0, 60),
                            pos: `${Math.round(r.x)},${Math.round(r.y)}`
                        });
                    }
                }
            });

            // Selects
            document.querySelectorAll('select').forEach(s => {
                if (s.offsetParent !== null) {
                    const options = Array.from(s.options).map(o => ({ text: o.text, value: o.value }));
                    result.selects.push({
                        name: s.name || '',
                        id: s.id || '',
                        classes: (s.className || '').substring(0, 60),
                        options: options
                    });
                }
            });

            return result;
        });

        // Filtrar lo relevante
        console.log('\n=== LINKS con download/export/excel/xls ===');
        elements.links.filter(l =>
            l.href.match(/download|export|excel|xls|csv|fichero/i) ||
            l.text.match(/descargar|export|excel|xls|csv|fichero/i) ||
            l.classes.match(/download|export/i) ||
            l.download
        ).forEach(l => console.log(`  "${l.text}" href=${l.href} class="${l.classes}" download="${l.download}" pos=${l.pos}`));

        console.log('\n=== BUTTONS con download/export/descargar ===');
        elements.buttons.filter(b =>
            b.text.match(/descargar|export|excel|xls|download/i) ||
            b.classes.match(/download|export/i) ||
            b.title.match(/descargar|export|download/i) ||
            b.ariaLabel.match(/descargar|export|download/i)
        ).forEach(b => console.log(`  "${b.text}" class="${b.classes}" title="${b.title}" aria="${b.ariaLabel}" pos=${b.pos}`));

        console.log('\n=== TODOS LOS BUTTONS ===');
        elements.buttons.forEach(b => {
            console.log(`  "${b.text.substring(0,40)}" class="${b.classes.substring(0,50)}" title="${b.title}" pos=${b.pos}`);
        });

        console.log('\n=== ICONS con download/export ===');
        elements.icons.filter(i =>
            i.classes.match(/download|export|excel|file|save|arrow-down/i) ||
            i.title.match(/descargar|export|download/i)
        ).forEach(i => console.log(`  <${i.tag}> class="${i.classes}" title="${i.title}" parent=<${i.parentTag}> "${i.parentClasses}" pos=${i.pos}`));

        console.log('\n=== SELECTS (formato de descarga?) ===');
        elements.selects.forEach(s => {
            console.log(`  name="${s.name}" id="${s.id}" class="${s.classes}"`);
            s.options.forEach(o => console.log(`    - "${o.text}" value="${o.value}"`));
        });

        // Buscar en todo el HTML la palabra "descargar", "exportar", "excel"
        const htmlSnippets = await page.evaluate(() => {
            const body = document.body.innerHTML;
            const matches = [];
            const patterns = ['descargar', 'exportar', 'excel', 'xls', 'download', 'export', 'formato'];
            for (const p of patterns) {
                const idx = body.toLowerCase().indexOf(p);
                if (idx >= 0) {
                    matches.push({ pattern: p, context: body.substring(Math.max(0,idx-50), idx+80) });
                }
            }
            return matches;
        });
        console.log('\n=== OCURRENCIAS EN HTML ===');
        htmlSnippets.forEach(s => console.log(`  [${s.pattern}]: ...${s.context.replace(/\n/g,' ').substring(0,120)}...`));

    } catch (e) {
        console.error(`ERROR: ${e.message}`);
        await page.screenshot({ path: path.join(OUTPUT_DIR, 'diag_descarga_error.png') });
    } finally {
        await context.close();
    }
}

run().catch(e => { console.error(`FATAL: ${e.message}`); process.exit(1); });
