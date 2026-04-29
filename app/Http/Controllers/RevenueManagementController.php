<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\RevenueCompetidor;
use App\Models\RevenuePrecioCompetencia;
use App\Models\RevenueRecomendacion;
use App\Services\ChannexRevenueService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-29] Revenue Management UI controller.
 *
 * Pantallas:
 *  - matriz()    → matriz apartamentos × días con precios actuales y recomendados
 *  - configurar()→ por apartamento, lista de competidores, min/max
 *  - aplicar()   → POST batch que empuja precios seleccionados a Channex
 *  - historial() → tabla de cambios aplicados
 *  - scraperCallback() → endpoint protegido por token al que el scraper
 *                        Python envía sus resultados
 */
class RevenueManagementController extends Controller
{
    public function __construct(private ChannexRevenueService $channexService) {}

    // ============================================================
    // VISTAS UI (admin)
    // ============================================================

    /**
     * GET /admin/revenue
     * Matriz apartamentos × próximos 30 días con precios.
     */
    public function matriz(Request $request)
    {
        $apartamentoIds = $request->input('apartamentos', []);
        $diasVista = (int) $request->input('dias', 30);
        $diasVista = max(7, min(60, $diasVista)); // entre 7 y 60

        $apartamentos = Apartamento::query()
            ->when(!empty($apartamentoIds), fn($q) => $q->whereIn('id', $apartamentoIds))
            ->orderBy('nombre')
            ->get();

        $hoy = Carbon::today();
        $fechas = collect(range(0, $diasVista - 1))
            ->map(fn($i) => $hoy->copy()->addDays($i));

        // Cargar recomendaciones existentes
        $recomendaciones = RevenueRecomendacion::query()
            ->whereIn('apartamento_id', $apartamentos->pluck('id'))
            ->whereBetween('fecha', [$hoy, $hoy->copy()->addDays($diasVista - 1)])
            ->get()
            ->keyBy(fn($r) => $r->apartamento_id . '_' . $r->fecha->toDateString());

        return view('revenue.matriz', [
            'apartamentos' => $apartamentos,
            'fechas' => $fechas,
            'recomendaciones' => $recomendaciones,
            'diasVista' => $diasVista,
        ]);
    }

    /**
     * GET /admin/revenue/apartamento/{id}/configurar
     */
    public function configurar(int $apartamentoId)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);
        $competidores = RevenueCompetidor::where('apartamento_id', $apartamentoId)
            ->orderBy('plataforma')
            ->orderBy('titulo')
            ->get();

        return view('revenue.configurar', [
            'apartamento' => $apartamento,
            'competidores' => $competidores,
        ]);
    }

    /**
     * POST /admin/revenue/apartamento/{id}/competidores
     * Añade un competidor manualmente.
     */
    public function addCompetidor(Request $request, int $apartamentoId)
    {
        $request->validate([
            'plataforma' => 'required|in:booking,airbnb',
            'url' => 'required|url|max:500',
            'titulo' => 'nullable|string|max:255',
            'notas' => 'nullable|string',
        ]);

        $comp = RevenueCompetidor::create([
            'apartamento_id' => $apartamentoId,
            'plataforma' => $request->plataforma,
            'url' => $request->url,
            'titulo' => $request->titulo,
            'notas' => $request->notas,
            'activo' => true,
        ]);

        return redirect()
            ->route('revenue.configurar', $apartamentoId)
            ->with('success', "Competidor añadido: {$comp->plataforma} {$comp->titulo}");
    }

    /**
     * DELETE /admin/revenue/competidor/{id}
     */
    public function deleteCompetidor(int $id)
    {
        $comp = RevenueCompetidor::findOrFail($id);
        $aptId = $comp->apartamento_id;
        $comp->delete();
        return redirect()
            ->route('revenue.configurar', $aptId)
            ->with('success', 'Competidor eliminado');
    }

    /**
     * POST /admin/revenue/apartamento/{id}/settings
     * Actualiza min/max/factor del apartamento.
     */
    public function updateSettings(Request $request, int $apartamentoId)
    {
        $request->validate([
            'revenue_min_precio' => 'nullable|numeric|min:0',
            'revenue_max_precio' => 'nullable|numeric|min:0',
            'revenue_factor_segmento' => 'required|in:premium,match,budget',
            'revenue_rate_plan_id' => 'nullable|string|max:100',
        ]);

        $apt = Apartamento::findOrFail($apartamentoId);
        $apt->update($request->only([
            'revenue_min_precio',
            'revenue_max_precio',
            'revenue_factor_segmento',
            'revenue_rate_plan_id',
        ]));
        return redirect()
            ->route('revenue.configurar', $apartamentoId)
            ->with('success', 'Configuración actualizada');
    }

    /**
     * POST /admin/revenue/aplicar
     * Body: { cambios: [{apartamento_id, fecha, precio}], dry_run: bool }
     */
    public function aplicar(Request $request)
    {
        $request->validate([
            'cambios' => 'required|array|min:1|max:500',
            'cambios.*.apartamento_id' => 'required|integer|exists:apartamentos,id',
            'cambios.*.fecha' => 'required|date',
            'cambios.*.precio' => 'required|numeric|min:1',
            'dry_run' => 'nullable|boolean',
        ]);

        $cambios = $request->input('cambios');
        $dryRun = (bool) $request->input('dry_run', false);

        $stats = $dryRun
            ? $this->channexService->simularCambios($cambios)
            : $this->channexService->aplicarCambios($cambios, auth()->id());

        Log::info('[Revenue] aplicar precios', [
            'user_id' => auth()->id(),
            'count' => count($cambios),
            'dry_run' => $dryRun,
            'stats' => $stats,
        ]);

        return response()->json($stats);
    }

    /**
     * GET /admin/revenue/historial
     */
    public function historial(Request $request)
    {
        $registros = RevenueRecomendacion::query()
            ->whereNotNull('aplicado_at')
            ->with(['apartamento', 'aplicadoPor'])
            ->orderByDesc('aplicado_at')
            ->paginate(50);

        return view('revenue.historial', ['registros' => $registros]);
    }

    // ============================================================
    // ENDPOINT API - scraper callback
    // ============================================================

    /**
     * POST /api/revenue/scraper-callback
     *
     * El scraper Python (servidor IA o local de prueba) manda aquí los
     * resultados después de cada ejecución. Token compartido.
     *
     * Body:
     * {
     *   "competidor_id": 12,
     *   "scrapeado_at": "2026-04-29T22:00:00Z",
     *   "noches": [
     *     {"fecha": "2026-05-01", "precio": 65, "disponible": true, "min_noches": 1},
     *     ...
     *   ]
     * }
     *
     * Auth: header X-Scraper-Token (env REVENUE_SCRAPER_TOKEN).
     */
    public function scraperCallback(Request $request)
    {
        $expectedToken = env('REVENUE_SCRAPER_TOKEN');
        if (empty($expectedToken) || $request->header('X-Scraper-Token') !== $expectedToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'competidor_id' => 'required|integer|exists:revenue_competidores,id',
            'scrapeado_at' => 'required|date',
            'noches' => 'required|array',
            'noches.*.fecha' => 'required|date',
            'noches.*.precio' => 'nullable|numeric',
            'noches.*.disponible' => 'required|boolean',
            'noches.*.min_noches' => 'nullable|integer',
            'noches.*.raw' => 'nullable|array',
            'rating' => 'nullable|numeric|between:0,10',
            'titulo' => 'nullable|string|max:255',
            'error' => 'nullable|string',
        ]);

        $compId = $request->input('competidor_id');
        $scrapeadoAt = Carbon::parse($request->input('scrapeado_at'));
        $error = $request->input('error');

        $comp = RevenueCompetidor::findOrFail($compId);

        if ($error) {
            $comp->update([
                'ultimo_error_at' => now(),
                'ultimo_error_msg' => mb_substr($error, 0, 1000),
            ]);
            return response()->json(['status' => 'error_recorded']);
        }

        // Persistir cada noche
        $insertados = 0;
        DB::transaction(function () use ($request, $compId, $scrapeadoAt, &$insertados) {
            foreach ($request->input('noches') as $noche) {
                RevenuePrecioCompetencia::create([
                    'competidor_id' => $compId,
                    'fecha' => $noche['fecha'],
                    'precio' => $noche['precio'] ?? null,
                    'disponible' => (bool) $noche['disponible'],
                    'min_noches' => $noche['min_noches'] ?? null,
                    'rating' => $request->input('rating'),
                    'scrapeado_at' => $scrapeadoAt,
                    'raw_data' => $noche['raw'] ?? null,
                ]);
                $insertados++;
            }
        });

        $update = ['ultimo_scrape_at' => now()];
        if ($titulo = $request->input('titulo')) {
            $update['titulo'] = $titulo;
        }
        $comp->update($update);

        return response()->json([
            'status' => 'ok',
            'insertados' => $insertados,
        ]);
    }
}
