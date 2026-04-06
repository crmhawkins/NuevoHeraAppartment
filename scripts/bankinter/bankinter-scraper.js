/**
 * Bankinter Scraper - Descarga automatizada de movimientos bancarios
 *
 * Flujo simplificado:
 *   1. Login (o reutilizar sesion persistente)
 *   2. Navegar a movimientos de la cuenta
 *   3. Click icono descarga -> "Descargar Excel"
 *   4. Guardar archivo
 *
 * No filtra por fechas: descarga los movimientos que muestra la pagina.
 * El CRM se encarga de omitir duplicados via hash MD5.
 *
 * Uso:
 *   node bankinter-scraper.js [--cuenta ALIAS] [--headless] [--output-dir /ruta]
 *
 * Variables de entorno:
 *   BANKINTER_USER / BANKINTER_PASSWORD / BANKINTER_CUENTA_ALIAS
 *   ANTHROPIC_API_KEY (opcional, para auto-reparacion IA)
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const { repairSelectors } = require('./ai-repair');

// --- Configuracion ---
const SELECTORS_PATH = path.join(__dirname, 'selectors.json');
const PROFILES_BASE_DIR = path.join(__dirname, 'chrome-profiles');
const DEFAULT_OUTPUT_DIR = path.join(__dirname, '..', '..', 'storage', 'app', 'bankinter');
const MAX_AI_RETRIES = 2;
const NAVIGATION_TIMEOUT = 30000;
const ACTION_TIMEOUT = 15000;

// --- Parsear argumentos CLI ---
function parseArgs() {
    const args = process.argv.slice(2);
    const parsed = {};
    for (let i = 0; i < args.length; i++) {
        if (args[i] === '--output-dir' && args[i + 1]) parsed.outputDir = args[++i];
        else if (args[i] === '--cuenta' && args[i + 1]) parsed.cuentaAlias = args[++i];
        else if (args[i] === '--headless') parsed.headless = true;
    }
    if (!parsed.outputDir) parsed.outputDir = DEFAULT_OUTPUT_DIR;
    if (!parsed.cuentaAlias) parsed.cuentaAlias = process.env.BANKINTER_CUENTA_ALIAS || 'default';
    parsed.headless = parsed.headless === true;
    return parsed;
}

function loadSelectors() {
    return JSON.parse(fs.readFileSync(SELECTORS_PATH, 'utf-8'));
}

// --- Intentar encontrar elemento con multiples selectores ---
async function findElement(page, selectorList, stepName, options = {}) {
    const { timeout = ACTION_TIMEOUT, aiRetry = true } = options;

    for (const selector of selectorList) {
        try {
            const element = await page.waitForSelector(selector, {
                timeout: Math.min(timeout / selectorList.length, 5000),
                state: 'visible'
            });
            if (element) {
                console.log(`[OK] ${stepName}: encontrado con "${selector}"`);
                return element;
            }
        } catch (e) { /* siguiente */ }
    }

    // Ninguno funciono - intentar IA
    if (aiRetry && process.env.ANTHROPIC_API_KEY) {
        console.log(`[WARN] ${stepName}: ningun selector funciono, intentando auto-reparacion IA...`);
        for (let attempt = 0; attempt < MAX_AI_RETRIES; attempt++) {
            try {
                const repair = await repairSelectors(page, stepName, selectorList);
                if (repair.close_first) {
                    for (const cs of repair.close_first) {
                        try {
                            const cb = await page.waitForSelector(cs, { timeout: 3000 });
                            if (cb) { await cb.click(); await page.waitForTimeout(1000); }
                        } catch (e) {}
                    }
                }
                if (repair.selectors) {
                    for (const ns of repair.selectors) {
                        try {
                            const el = await page.waitForSelector(ns, { timeout: 5000, state: 'visible' });
                            if (el) {
                                console.log(`[AI-REPAIR] ${stepName}: reparado con "${ns}"`);
                                return el;
                            }
                        } catch (e) {}
                    }
                }
            } catch (e) {
                console.error(`[AI-REPAIR] Error intento ${attempt + 1}: ${e.message}`);
            }
        }
    }

    throw new Error(`[FAIL] ${stepName}: no se encontro elemento. Selectores: ${selectorList.join(', ')}`);
}

async function tryCloseOverlays(page, selectorLists) {
    for (const list of selectorLists) {
        for (const sel of list) {
            try {
                const el = await page.waitForSelector(sel, { timeout: 2000, state: 'visible' });
                if (el) { await el.click(); await page.waitForTimeout(800); console.log(`[POPUP] Cerrado: ${sel}`); return true; }
            } catch (e) {}
        }
    }
    return false;
}

async function waitForLoading(page, selectors) {
    for (const sel of (selectors.common?.loading_indicator || [])) {
        try { await page.waitForSelector(sel, { state: 'hidden', timeout: 15000 }); } catch (e) {}
    }
}

// --- FLUJO PRINCIPAL ---
async function run() {
    const config = parseArgs();
    const selectors = loadSelectors();
    const user = process.env.BANKINTER_USER;
    const password = process.env.BANKINTER_PASSWORD;

    if (!user || !password) {
        console.error('ERROR: Variables BANKINTER_USER y BANKINTER_PASSWORD requeridas');
        process.exit(1);
    }

    const userDataDir = path.join(PROFILES_BASE_DIR, config.cuentaAlias);
    if (!fs.existsSync(userDataDir)) fs.mkdirSync(userDataDir, { recursive: true });
    if (!fs.existsSync(config.outputDir)) fs.mkdirSync(config.outputDir, { recursive: true });

    console.log(`[START] Bankinter Scraper`);
    console.log(`  Cuenta: ${config.cuentaAlias}`);
    console.log(`  Headless: ${config.headless}`);
    console.log(`  AI repair: ${process.env.ANTHROPIC_API_KEY ? 'SI' : 'NO'}`);

    let context, page;
    const result = { success: false, file: null, error: null, cuenta: config.cuentaAlias };

    try {
        context = await chromium.launchPersistentContext(userDataDir, {
            headless: config.headless,
            viewport: { width: 1366, height: 768 },
            locale: 'es-ES',
            timezoneId: 'Europe/Madrid',
            args: ['--disable-blink-features=AutomationControlled', '--no-sandbox'],
            acceptDownloads: true
        });

        page = context.pages()[0] || await context.newPage();
        page.setDefaultTimeout(NAVIGATION_TIMEOUT);

        // ===== PASO 1: Intentar ir directo a movimientos (sesion persistente) =====
        console.log('\n[STEP 1] Intentando acceso directo a movimientos...');
        let enMovimientos = false;

        // Buscar URL de movimientos en el DOM del dashboard
        // Primero intentamos ir al dashboard directamente
        await page.goto('https://empresas.bankinter.com/secure/es/posicion-global', {
            waitUntil: 'domcontentloaded', timeout: 60000
        });
        await page.waitForTimeout(3000);

        let currentUrl = page.url();
        console.log(`[DIAG] URL: ${currentUrl}`);

        // Si estamos en la zona autenticada, buscar enlace de movimientos
        if (currentUrl.includes('/secure/')) {
            console.log('[STEP 1] Sesion activa, buscando enlace movimientos...');
            const movUrl = await page.evaluate(() => {
                const link = document.querySelector('a[href*="tab=movements"]');
                return link ? link.href : null;
            });
            if (movUrl) {
                console.log(`[STEP 1] Navegando directo a movimientos: ${movUrl}`);
                await page.goto(movUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
                await page.waitForTimeout(5000);
                enMovimientos = page.url().includes('tab=movements');
            }
        }

        // ===== PASO 2: Si no hay sesion, login completo =====
        if (!enMovimientos) {
            console.log('\n[STEP 2] Login necesario...');
            await page.goto(selectors.homepage.url, { waitUntil: 'domcontentloaded', timeout: 60000 });
            await page.waitForTimeout(2000);

            // Cerrar cookies y popups
            await tryCloseOverlays(page, [selectors.homepage.cookie_banner_reject]);
            await tryCloseOverlays(page, [selectors.homepage.popup_close]);
            await page.waitForTimeout(1000);

            // Click Acceso clientes
            console.log('[STEP 2] Click "Acceso clientes"...');
            const accesoBtn = await findElement(page, selectors.homepage.acceso_clientes, 'homepage.acceso_clientes');
            await accesoBtn.click();
            await page.waitForTimeout(3000);

            // Rellenar login
            console.log('[STEP 2] Rellenando credenciales...');
            const userField = await findElement(page, selectors.login.username_field, 'login.username_field');
            await userField.click({ clickCount: 3 });
            await userField.fill(user);

            const passField = await findElement(page, selectors.login.password_field, 'login.password_field');
            await passField.click({ clickCount: 3 });
            await passField.fill(password);

            const submitBtn = await findElement(page, selectors.login.submit_button, 'login.submit_button');
            await submitBtn.click();

            console.log('[STEP 2] Esperando login...');
            await page.waitForTimeout(8000);
            await waitForLoading(page, selectors);

            // Cerrar banners post-login
            await tryCloseOverlays(page, [selectors.post_login.ad_banner_close]);
            await page.waitForTimeout(1500);
            await tryCloseOverlays(page, [selectors.post_login.ad_banner_close]);

            currentUrl = page.url();
            console.log(`[DIAG] URL post-login: ${currentUrl}`);

            if (!currentUrl.includes('/secure/')) {
                throw new Error('Login fallido: no se llego a la zona autenticada');
            }

            // Buscar enlace de movimientos
            console.log('[STEP 2] Buscando enlace a movimientos...');
            const movUrl = await page.evaluate(() => {
                const link = document.querySelector('a[href*="tab=movements"]');
                return link ? link.href : null;
            });

            if (!movUrl) {
                throw new Error('No se encontro enlace a movimientos en el dashboard');
            }

            console.log(`[STEP 2] Navegando a movimientos: ${movUrl}`);
            await page.goto(movUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
            await page.waitForTimeout(5000);
            await waitForLoading(page, selectors);
        }

        console.log(`[DIAG] URL movimientos: ${page.url()}`);

        // ===== PASO 3: Click icono Descargar (abre submenu) =====
        console.log('\n[STEP 3] Abriendo menu de descarga...');

        const downloadBtnSelectors = [
            'div.action-bar__btn-small--right',
            'button:has-text("Descargar")',
            '[role="button"]:has(span.icon--download-16)',
            '.action-bar__btn-small:has(span.icon--download-16)'
        ];

        const downloadBtn = await findElement(page, downloadBtnSelectors, 'movimientos.boton_descargar');
        await downloadBtn.click();
        await page.waitForTimeout(1500);
        console.log('[STEP 3] Menu de descarga abierto');

        // ===== PASO 4: Click "Descargar Excel" =====
        console.log('[STEP 4] Seleccionando "Descargar Excel"...');

        // Registrar listener de descarga ANTES de clickar
        const downloadPromise = page.waitForEvent('download', { timeout: 30000 });

        const excelSelectors = [
            'a.action-box__item:has-text("Descargar Excel")',
            'a:has-text("Descargar Excel")',
            '.action-box__item:has(span:has-text("Descargar Excel"))',
            'nav.action-box__content--download a:first-of-type',
            '.action-box__content a:first-of-type'
        ];

        const excelOption = await findElement(page, excelSelectors, 'movimientos.opcion_excel');
        await excelOption.click();
        console.log('[STEP 4] Click en "Descargar Excel"');

        // ===== PASO 5: Esperar y guardar descarga =====
        console.log('[STEP 5] Esperando descarga...');
        const download = await downloadPromise;

        const timestamp = new Date().toISOString().split('T')[0];
        const defaultFilename = `bankinter_${config.cuentaAlias}_${timestamp}.xlsx`;
        const suggestedFilename = download.suggestedFilename() || defaultFilename;
        const outputPath = path.join(config.outputDir, suggestedFilename);

        await download.saveAs(outputPath);
        console.log(`[DOWNLOAD] Archivo guardado: ${outputPath}`);

        result.success = true;
        result.file = outputPath;

    } catch (error) {
        console.error(`[ERROR] ${error.message}`);
        result.error = error.message;

        if (page) {
            try {
                const errShot = path.join(config.outputDir, `error_${config.cuentaAlias}_${new Date().toISOString().replace(/[:.]/g, '-')}.png`);
                await page.screenshot({ path: errShot, fullPage: true });
                result.errorScreenshot = errShot;
                console.log(`[ERROR] Screenshot: ${errShot}`);
            } catch (e) {}
        }
    } finally {
        if (context) await context.close();
    }

    const jsonResult = JSON.stringify(result);
    console.log(`\n[RESULT_JSON]${jsonResult}[/RESULT_JSON]`);
    process.exit(result.success ? 0 : 1);
}

run().catch(e => {
    console.error(`[FATAL] ${e.message}`);
    console.log(`\n[RESULT_JSON]${JSON.stringify({ success: false, error: e.message, file: null })}[/RESULT_JSON]`);
    process.exit(1);
});
