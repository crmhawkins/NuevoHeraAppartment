/**
 * Modulo de auto-reparacion con IA
 *
 * Cuando Playwright no encuentra un selector, toma un screenshot,
 * lo envia a Claude API (vision) y obtiene selectores actualizados.
 */

const fs = require('fs');
const path = require('path');
const https = require('https');

const SELECTORS_PATH = path.join(__dirname, 'selectors.json');
const SCREENSHOTS_DIR = path.join(__dirname, 'screenshots');
const REPAIR_LOG = path.join(__dirname, 'repair-log.json');

// Asegurar directorio de screenshots
if (!fs.existsSync(SCREENSHOTS_DIR)) {
    fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

/**
 * Envia screenshot a Claude API y obtiene selectores actualizados
 */
async function askClaudeForSelectors(screenshotPath, failedStep, failedSelectors, currentSelectorsJson) {
    const apiKey = process.env.ANTHROPIC_API_KEY;
    if (!apiKey) {
        throw new Error('ANTHROPIC_API_KEY no configurada');
    }

    const imageBuffer = fs.readFileSync(screenshotPath);
    const base64Image = imageBuffer.toString('base64');
    const mediaType = 'image/png';

    const prompt = `Eres un experto en web scraping. Estoy automatizando la navegacion del banco Bankinter (https://www.bankinter.com/empresas/).

PASO ACTUAL QUE FALLO: "${failedStep}"
SELECTORES QUE NO FUNCIONARON: ${JSON.stringify(failedSelectors)}

Mira el screenshot adjunto de la pagina actual y dame los selectores CSS correctos para completar este paso.

REGLAS:
1. Devuelve SOLO un JSON valido, sin texto adicional ni markdown
2. El JSON debe tener la estructura: {"step": "${failedStep}", "selectors": ["selector1", "selector2"]}
3. Ordena los selectores del mas especifico al mas generico
4. Usa selectores CSS estandar, no XPath
5. Si ves un boton o enlace relevante, incluye selectores basados en texto visible
6. Si hay un popup/banner bloqueando, incluye tambien el selector para cerrarlo como "close_first": ["selector"]

CONTEXTO - Selectores actuales del archivo:
${currentSelectorsJson}

Responde SOLO con el JSON.`;

    const requestBody = JSON.stringify({
        model: 'claude-sonnet-4-20250514',
        max_tokens: 1024,
        messages: [{
            role: 'user',
            content: [
                {
                    type: 'image',
                    source: {
                        type: 'base64',
                        media_type: mediaType,
                        data: base64Image
                    }
                },
                {
                    type: 'text',
                    text: prompt
                }
            ]
        }]
    });

    return new Promise((resolve, reject) => {
        const options = {
            hostname: 'api.anthropic.com',
            path: '/v1/messages',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'x-api-key': apiKey,
                'anthropic-version': '2023-06-01',
                'Content-Length': Buffer.byteLength(requestBody)
            }
        };

        const req = https.request(options, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try {
                    if (res.statusCode !== 200) {
                        reject(new Error(`Claude API error ${res.statusCode}: ${data}`));
                        return;
                    }
                    const response = JSON.parse(data);
                    const text = response.content?.[0]?.text || '';

                    // Extraer JSON de la respuesta (por si viene con markdown)
                    let jsonStr = text;
                    const jsonMatch = text.match(/\{[\s\S]*\}/);
                    if (jsonMatch) {
                        jsonStr = jsonMatch[0];
                    }

                    const result = JSON.parse(jsonStr);
                    resolve(result);
                } catch (e) {
                    reject(new Error(`Error parseando respuesta de Claude: ${e.message} - Raw: ${data.substring(0, 500)}`));
                }
            });
        });

        req.on('error', reject);
        req.write(requestBody);
        req.end();
    });
}

/**
 * Toma screenshot y consulta IA para reparar selectores
 * @param {import('playwright').Page} page - Pagina de Playwright
 * @param {string} stepName - Nombre del paso (ej: "homepage.acceso_clientes")
 * @param {string[]} failedSelectors - Array de selectores que fallaron
 * @returns {object} - {selectors: string[], close_first?: string[]}
 */
async function repairSelectors(page, stepName, failedSelectors) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const screenshotPath = path.join(SCREENSHOTS_DIR, `repair_${stepName}_${timestamp}.png`);

    console.log(`[AI-REPAIR] Tomando screenshot para paso: ${stepName}`);
    await page.screenshot({ path: screenshotPath, fullPage: true });

    const currentSelectors = JSON.parse(fs.readFileSync(SELECTORS_PATH, 'utf-8'));
    const currentSelectorsJson = JSON.stringify(currentSelectors, null, 2).substring(0, 3000);

    console.log(`[AI-REPAIR] Consultando Claude API para nuevos selectores...`);
    const aiResponse = await askClaudeForSelectors(
        screenshotPath,
        stepName,
        failedSelectors,
        currentSelectorsJson
    );

    console.log(`[AI-REPAIR] Respuesta IA:`, JSON.stringify(aiResponse));

    // Actualizar selectores en el JSON
    if (aiResponse.selectors && aiResponse.selectors.length > 0) {
        updateSelectorsFile(stepName, aiResponse.selectors);
    }

    // Registrar la reparacion en el log
    logRepair(stepName, failedSelectors, aiResponse);

    return aiResponse;
}

/**
 * Actualiza el archivo selectors.json con los nuevos selectores
 */
function updateSelectorsFile(stepName, newSelectors) {
    const selectors = JSON.parse(fs.readFileSync(SELECTORS_PATH, 'utf-8'));

    // stepName format: "homepage.acceso_clientes"
    const parts = stepName.split('.');
    if (parts.length === 2) {
        const [section, field] = parts;
        if (selectors[section] && selectors[section][field]) {
            // Prepend nuevos selectores al inicio (prioridad)
            const existing = selectors[section][field];
            const merged = [...new Set([...newSelectors, ...existing])];
            selectors[section][field] = merged;
        }
    }

    selectors._updated_at = new Date().toISOString().split('T')[0];
    selectors._last_repair = stepName;

    fs.writeFileSync(SELECTORS_PATH, JSON.stringify(selectors, null, 2), 'utf-8');
    console.log(`[AI-REPAIR] Selectores actualizados en ${SELECTORS_PATH}`);
}

/**
 * Registra la reparacion en un log historico
 */
function logRepair(stepName, failedSelectors, aiResponse) {
    let log = [];
    if (fs.existsSync(REPAIR_LOG)) {
        try {
            log = JSON.parse(fs.readFileSync(REPAIR_LOG, 'utf-8'));
        } catch (e) {
            log = [];
        }
    }

    log.push({
        timestamp: new Date().toISOString(),
        step: stepName,
        failed_selectors: failedSelectors,
        ai_response: aiResponse,
        success: !!(aiResponse.selectors && aiResponse.selectors.length > 0)
    });

    // Mantener solo las ultimas 100 reparaciones
    if (log.length > 100) {
        log = log.slice(-100);
    }

    fs.writeFileSync(REPAIR_LOG, JSON.stringify(log, null, 2), 'utf-8');
}

module.exports = { repairSelectors };
