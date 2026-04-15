<?php

namespace App\Http\Controllers;

use App\Models\InformeAi;
use App\Models\Gastos;
use App\Models\Ingresos;
use App\Models\CategoriaGastos;
use App\Models\CategoriaIngresos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InformeAiController extends Controller
{
    /**
     * Generar o mostrar informe AI
     */
    public function generarInforme(Request $request)
    {
        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);

        // Verificar si ya existe un informe para este período
        $informeExistente = InformeAi::buscarPorPeriodo($fechaInicio->toDateString(), $fechaFin->toDateString());

        if ($informeExistente) {
            // Si existe, redirigir a la vista del informe
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => route('informe.ai.ver', $informeExistente->id)
                ]);
            }
            return redirect()->route('informe.ai.ver', $informeExistente->id);
        }

        // Si no existe, generar nuevo informe
        try {
            Log::info('Iniciando generación de informe para período: ' . $fechaInicio->toDateString() . ' - ' . $fechaFin->toDateString());
            
            // Obtener datos del período
            $datos = $this->obtenerDatosPeriodo($fechaInicio, $fechaFin);
            Log::info('Datos obtenidos: ' . json_encode($datos));
            
            // Generar prompt para ChatGPT
            $prompt = $this->generarPrompt($datos);
            Log::info('Prompt generado: ' . $prompt);
            
            // Hacer petición a ChatGPT
            $respuesta = $this->consultarChatGPT($prompt);
            Log::info('Respuesta de ChatGPT recibida');
            
            // Guardar en base de datos
            $informe = InformeAi::create([
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString(),
                'contenido_md' => $respuesta,
                'resumen' => $this->extraerResumen($respuesta)
            ]);

            Log::info('Informe guardado con ID: ' . $informe->id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => route('informe.ai.ver', $informe->id)
                ]);
            }
            
            return redirect()->route('informe.ai.ver', $informe->id)
                           ->with('success', 'Informe generado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al generar informe: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al generar el informe: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                           ->with('error', 'Error al generar el informe: ' . $e->getMessage());
        }
    }

    /**
     * Listar todos los informes
     */
    public function index()
    {
        $informes = InformeAi::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.informes.index', compact('informes'));
    }

    /**
     * Mostrar informe
     */
    public function verInforme($id)
    {
        $informe = InformeAi::findOrFail($id);
        $contenidoHtml = $this->parseMarkdown($informe->contenido_md);
        return view('admin.informes.ver', compact('informe', 'contenidoHtml'));
    }

    /**
     * Eliminar informe
     */
    public function eliminar($id)
    {
        $informe = InformeAi::findOrFail($id);
        $informe->delete();
        
        return redirect()->route('informes.ai.index')
                       ->with('success', 'Informe eliminado exitosamente');
    }

    /**
     * Convertir markdown básico a HTML
     */
    private function parseMarkdown($markdown)
    {
        // Convertir markdown básico a HTML
        $html = $markdown;
        
        // Headers
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
        
        // Bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        
        // Italic
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        
        // Lists
        $html = preg_replace('/^\- (.*$)/m', '<li>$1</li>', $html);
        $html = preg_replace('/^(\d+)\. (.*$)/m', '<li>$2</li>', $html);
        
        // Wrap lists in ul/ol
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
        
        // Line breaks
        $html = nl2br($html);
        
        return $html;
    }

    /**
     * Obtener datos del período
     */
    private function obtenerDatosPeriodo($fechaInicio, $fechaFin)
    {
        // Obtener movimientos del diario de caja con sus relaciones
        $movimientos = \App\Models\DiarioCaja::whereBetween('date', [$fechaInicio, $fechaFin])
            ->with(['gasto.categoria', 'ingreso.categoria'])
            ->orderBy('date', 'desc')
            ->get();

        // Separar movimientos de débito y crédito
        $movimientosDebito = $movimientos->where('debe', '<', 0);
        $movimientosCredito = $movimientos->where('haber', '>', 0)->whereNotNull('haber');

        // Agrupar por concepto
        $movimientosPorConcepto = $movimientos->groupBy('concepto');

        // Calcular totales
        $totalDebito = abs($movimientosDebito->sum('debe'));
        $totalCredito = $movimientosCredito->sum('haber');
        $saldoFinal = $totalCredito - $totalDebito;

        return [
            'movimientos' => $movimientos,
            'movimientos_debito' => $movimientosDebito,
            'movimientos_credito' => $movimientosCredito,
            'movimientos_por_concepto' => $movimientosPorConcepto,
            'total_debito' => $totalDebito,
            'total_credito' => $totalCredito,
            'saldo_final' => $saldoFinal,
            'periodo' => [
                'inicio' => $fechaInicio->format('d/m/Y'),
                'fin' => $fechaFin->format('d/m/Y')
            ]
        ];
    }

    /**
     * Generar prompt para ChatGPT
     */
    private function generarPrompt($datos)
    {
        // Resumir movimientos por concepto (máximo 10 conceptos más importantes)
        $conceptosTexto = '';
        $conceptosLimitados = $datos['movimientos_por_concepto']->take(10);
        
        foreach ($conceptosLimitados as $concepto => $movimientos) {
            $totalDebito = $movimientos->sum('debe');
            $totalCredito = $movimientos->sum('haber');
            $neto = $totalCredito - $totalDebito;
            
            // Obtener la categoría y tipo del primer movimiento
            $categoria = 'Sin categoría';
            $tipo = 'Sin tipo';
            $primerMovimiento = $movimientos->first();
            if ($primerMovimiento->gasto && $primerMovimiento->gasto->categoria) {
                $categoria = $primerMovimiento->gasto->categoria->nombre;
                $tipo = 'GASTO';
            } elseif ($primerMovimiento->ingreso && $primerMovimiento->ingreso->categoria) {
                $categoria = $primerMovimiento->ingreso->categoria->nombre;
                $tipo = 'INGRESO';
            }
            
            $conceptosTexto .= "\n- **$concepto** ($tipo - Categoría: $categoria): " . number_format($neto, 2) . " €";
            if ($totalDebito > 0) {
                $conceptosTexto .= " (Debe: " . number_format($totalDebito, 2) . " €)";
            }
            if ($totalCredito > 0) {
                $conceptosTexto .= " (Haber: " . number_format($totalCredito, 2) . " €)";
            }
        }

        // Resumir movimientos de débito más importantes
        $debitosTexto = '';
        $debitosLimitados = $datos['movimientos_debito']->sortByDesc('debe')->take(5);
        foreach ($debitosLimitados as $movimiento) {
            $categoria = 'Sin categoría';
            $tipo = 'Sin tipo';
            if ($movimiento->gasto && $movimiento->gasto->categoria) {
                $categoria = $movimiento->gasto->categoria->nombre;
                $tipo = 'GASTO';
            } elseif ($movimiento->ingreso && $movimiento->ingreso->categoria) {
                $categoria = $movimiento->ingreso->categoria->nombre;
                $tipo = 'INGRESO';
            }
            $debitosTexto .= "\n- " . $movimiento->concepto . " ($tipo - Categoría: $categoria): " . number_format($movimiento->debe, 2) . " €";
        }

        // Resumir movimientos de crédito más importantes
        $creditosTexto = '';
        $creditosLimitados = $datos['movimientos_credito']->sortByDesc('haber')->take(5);
        foreach ($creditosLimitados as $movimiento) {
            $categoria = 'Sin categoría';
            $tipo = 'Sin tipo';
            if ($movimiento->gasto && $movimiento->gasto->categoria) {
                $categoria = $movimiento->gasto->categoria->nombre;
                $tipo = 'GASTO';
            } elseif ($movimiento->ingreso && $movimiento->ingreso->categoria) {
                $categoria = $movimiento->ingreso->categoria->nombre;
                $tipo = 'INGRESO';
            }
            $creditosTexto .= "\n- " . $movimiento->concepto . " ($tipo - Categoría: $categoria): " . number_format($movimiento->haber, 2) . " €";
        }

        $prompt = "
Analiza y presenta de forma profesional y visual los movimientos contables de una empresa de **apartamentos turísticos** correspondientes al período **{$datos['periodo']['inicio']} - {$datos['periodo']['fin']}**.

---

### 🧾 DATOS GENERALES
**Conceptos Principales:**
$conceptosTexto

**Gastos Más Importantes:**
$debitosTexto

**Ingresos Más Importantes:**
$creditosTexto

**Resumen Financiero:**
- 💸 Total Débito: **" . number_format($datos['total_debito'], 2) . " €**
- 💰 Total Crédito: **" . number_format($datos['total_credito'], 2) . " €**
- 🧮 Saldo Final: **" . number_format($datos['saldo_final'], 2) . " €**
- 📊 Total Movimientos: **" . $datos['movimientos']->count() . "**

---

### 📂 SOLICITUD DE ANÁLISIS

Elabora un **informe financiero detallado y visual** con el siguiente formato:

#### 🏷️ 1. Categorización por tipo de gasto e ingreso
Clasifica todos los movimientos en categorías como:
- 🏠 *Aprovisionamiento / Limpieza / Mantenimiento*
- 👥 *Nóminas y personal*
- 💡 *Suministros (agua, luz, gas, internet)* - INCLUYE conceptos como 'holaluz', 'yellow energy', 'luz', 'agua', 'electricidad' y categorías como 'GASTO ELECTRICIDAD', 'GASTO LUZ', 'GASTO AGUA'
- 📦 *Proveedores / Servicios externos*
- 💳 *Comisiones Booking / Airbnb / TPV*
- 💼 *Gastos de gestión, impuestos y tasas*
- 💵 *Ingresos por reservas / otros ingresos*

Muestra cada categoría con subtotales, en formato tabla o lista con emojis.

**CRÍTICO**: Si ves movimientos con categorías como GASTO ELECTRICIDAD, GASTO LUZ, GASTO AGUA, etc., estos son gastos de suministros y DEBEN incluirse en la categoría de suministros (agua, luz, gas, internet). 

**OBLIGATORIO**: Si ves conceptos como yellow energy, holaluz, o categorías como GASTO ELECTRICIDAD, GASTO LUZ, GASTO AGUA, estos son gastos de suministros y DEBEN aparecer en la categoría de suministros. NUNCA digas No se encontraron movimientos para suministros si hay estos datos.

**EJEMPLO ESPECÍFICO**: Si ves Recib/the yellow energy con categoría GASTO ELECTRICIDAD, esto es un gasto de suministros de electricidad y DEBE aparecer en la categoría de suministros.

";


        // Limitar el tamaño del prompt (aproximadamente 4000 caracteres)
        if (strlen($prompt) > 4000) {
            $prompt = substr($prompt, 0, 4000) . "\n\n[Información truncada por límite de contexto]";
        }

        return $prompt;
    }

    /**
     * Consultar ChatGPT
     */
    private function consultarChatGPT($prompt)
    {
        Log::info('Consultando ChatGPT via AIGateway');

        $data = app(\App\Services\AIGatewayService::class)->chatCompletion([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un analista financiero experto en empresas de apartamentos turísticos. Proporciona análisis detallados y recomendaciones útiles.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 2000,
            'temperature' => 0.7
        ]);

        Log::info('Respuesta AIGateway recibida', [
            'source' => $data['_gateway_source'] ?? 'unknown',
        ]);

        return $data['choices'][0]['message']['content'] ?? 'Error al obtener respuesta';
    }

    /**
     * Extraer resumen del contenido
     */
    private function extraerResumen($contenido)
    {
        // Extraer las primeras líneas como resumen
        $lineas = explode("\n", $contenido);
        $resumen = '';
        
        for ($i = 0; $i < min(3, count($lineas)); $i++) {
            $linea = trim($lineas[$i]);
            if (!empty($linea) && !str_starts_with($linea, '#')) {
                $resumen .= $linea . ' ';
            }
        }
        
        return substr($resumen, 0, 200) . '...';
    }
}