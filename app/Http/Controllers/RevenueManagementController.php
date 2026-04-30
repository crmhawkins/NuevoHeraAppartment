<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\RevenueCompetidor;
use App\Models\RevenuePrecioCompetencia;
use App\Models\RevenueRecomendacion;
use App\Services\ChannexRevenueService;
use App\Services\RevenueRecomendadorService;
use App\Services\RevenueScraperClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
    public function __construct(
        private ChannexRevenueService $channexService,
        private RevenueRecomendadorService $recomendador,
        private RevenueScraperClient $scraper,
    ) {}

    /**
     * GET /admin/revenue/hoy
     * Pantalla principal del flujo "Calcular Revenue".
     *
     * Muestra:
     *  - Apartamentos libres y ocupados HOY
     *  - Estado del servicio scraper Python (health check)
     *  - Última cache de competencia si existe (sin scrape automático)
     *  - Botones:
     *      [ Scrapear competencia ahora ] (lanza POST /scrape)
     *      [ Aplicar precio recomendado a libres ]
     */
    public function hoy(Request $request)
    {
        $fecha = Carbon::today();
        if ($request->filled('fecha')) {
            try {
                $fecha = Carbon::parse($request->input('fecha'));
            } catch (\Exception) {
                $fecha = Carbon::today();
            }
        }
        $checkout = $fecha->copy()->addDay();

        // Health del scraper
        $health = $this->scraper->health();

        // Apartamentos en esa fecha (libres + ocupados)
        $situacion = $this->recomendador->apartamentosEnFecha($fecha);

        $libres = collect($situacion)->where('libre', true)->count();
        $ocupados = collect($situacion)->where('libre', false)->count();
        $ocupacion_pct = $this->recomendador->ocupacionPropia($fecha);

        // Última recomendación cacheada en BD (si la hay)
        $aptIds = collect($situacion)->pluck('apartamento.id');
        $recomendaciones = RevenueRecomendacion::query()
            ->whereIn('apartamento_id', $aptIds)
            ->whereDate('fecha', $fecha)
            ->get()
            ->keyBy('apartamento_id');

        return view('revenue.hoy', [
            'fecha' => $fecha,
            'checkout' => $checkout,
            'situacion' => $situacion,
            'libres_count' => $libres,
            'ocupados_count' => $ocupados,
            'ocupacion_pct' => $ocupacion_pct,
            'recomendaciones' => $recomendaciones,
            'scraper_health' => $health,
            'es_finde' => in_array($fecha->dayOfWeekIso, [5, 6, 7]),
            'es_festivo' => $this->recomendador->esFestivoLocal($fecha),
        ]);
    }

    /**
     * POST /admin/revenue/scrape
     * AJAX: lanza el scraper Python y guarda recomendaciones en BD.
     * Devuelve JSON con stats y recomendaciones por apartamento.
     */
    public function scrape(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'zona' => 'nullable|in:algeciras_centro,algeciras_costa,bahia_completa',
            'adultos' => 'nullable|integer|between:1,10',
            'use_cache' => 'nullable|boolean',
        ]);

        $fecha = Carbon::parse($request->input('fecha'));
        $checkout = $fecha->copy()->addDay();
        $zona = $request->input('zona', 'algeciras_centro');
        $adultos = (int) $request->input('adultos', 2);
        $useCache = (bool) $request->input('use_cache', true);

        // 1. Llamar al scraper Python
        try {
            $datos = $this->scraper->scrapeMercado(
                fechaDesde: $fecha->toDateString(),
                fechaHasta: $checkout->toDateString(),
                zona: $zona,
                adultos: $adultos,
                useCache: $useCache,
            );
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'hint' => 'Lanza el servicio: cd revenue-scraper-local && uvicorn service:app --port 8765',
            ], 503);
        }

        $statsCombinado = $datos['combinado'] ?? [];

        // 2. Calcular recomendación por apartamento
        $situacion = $this->recomendador->apartamentosEnFecha($fecha);
        $resultados = [];

        foreach ($situacion as $row) {
            /** @var Apartamento $apt */
            $apt = $row['apartamento'];
            $rec = $this->recomendador->calcularRecomendacion(
                $apt, $fecha, $statsCombinado, precioActual: null
            );

            // Guardar/actualizar en BD
            // [2026-04-30] Usar whereDate para que SQLite compare por fecha
            // (no datetime). El cast 'fecha' => 'date' del modelo guarda
            // datetimes con hora 00:00:00 en SQLite, lo que rompe el match
            // exacto que hace updateOrCreate.
            if ($rec['precio_recomendado'] !== null) {
                $valoresNuevos = [
                    'precio_recomendado' => $rec['precio_recomendado'],
                    'competencia_media' => $statsCombinado['media'] ?? null,
                    'competencia_min' => $statsCombinado['min'] ?? null,
                    'competencia_max' => $statsCombinado['max'] ?? null,
                    'competidores_count' => $statsCombinado['n'] ?? 0,
                    'ocupacion_nuestra_pct' => $rec['ocupacion_pct'],
                    'es_finde' => $rec['es_finde'],
                    'es_festivo' => $rec['es_festivo'],
                    'razonamiento' => $rec['razonamiento'],
                    'calculado_at' => now(),
                ];
                $existente = RevenueRecomendacion::where('apartamento_id', $apt->id)
                    ->whereDate('fecha', $fecha)
                    ->first();
                if ($existente) {
                    $existente->update($valoresNuevos);
                } else {
                    RevenueRecomendacion::create(array_merge($valoresNuevos, [
                        'apartamento_id' => $apt->id,
                        'fecha' => $fecha->toDateString(),
                    ]));
                }
            }

            $resultados[] = [
                'apartamento_id' => $apt->id,
                'nombre' => $apt->nombre,
                'libre' => $row['libre'],
                'reserva_id' => $row['reserva']?->id,
                'precio_recomendado' => $rec['precio_recomendado'],
                'razonamiento' => $rec['razonamiento'],
                'ajustes' => $rec['ajustes_aplicados'],
            ];
        }

        return response()->json([
            'fecha' => $fecha->toDateString(),
            'zona' => $zona,
            'mercado' => $statsCombinado,
            'fuentes' => [
                'airbnb' => $datos['airbnb']['stats'] ?? [],
                'booking' => $datos['booking']['stats'] ?? [],
            ],
            'cached' => $datos['cached'] ?? false,
            'cache_age_minutes' => $datos['cache_age_minutes'] ?? null,
            'apartamentos' => $resultados,
            'listings_top' => collect($datos['listings'] ?? [])
                ->whereNotNull('precio')
                ->sortBy('precio')
                ->take(20)
                ->values()
                ->all(),
        ]);
    }

    /**
     * POST /admin/revenue/estrategias
     * Devuelve N estrategias de pricing comparadas para la fecha y zona dadas.
     * Ejecuta el scrape (con caché) y aplica varios escenarios sin tocar BD.
     */
    public function estrategias(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'zona' => 'nullable|in:algeciras_centro,algeciras_costa,bahia_completa',
            'adultos' => 'nullable|integer|between:1,10',
        ]);
        $fecha = Carbon::parse($request->input('fecha'));
        $checkout = $fecha->copy()->addDay();
        $zona = $request->input('zona', 'algeciras_centro');
        $adultos = (int) $request->input('adultos', 2);

        try {
            $datos = $this->scraper->scrapeMercado(
                fechaDesde: $fecha->toDateString(),
                fechaHasta: $checkout->toDateString(),
                zona: $zona,
                adultos: $adultos,
                useCache: true,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        $situacion = $this->recomendador->apartamentosEnFecha($fecha);
        $resultado = $this->recomendador->compararEstrategias(
            $situacion, $fecha, $datos['listings'] ?? []
        );

        return response()->json([
            'fecha' => $fecha->toDateString(),
            'zona' => $zona,
            'adultos' => $adultos,
            'estadisticas' => $resultado['estadisticas'],
            'estrategias' => $resultado['estrategias'],
            'listings' => collect($datos['listings'] ?? [])
                ->whereNotNull('precio')
                ->sortBy('precio')
                ->values()
                ->all(),
            'cached' => $datos['cached'] ?? false,
            'cache_age_minutes' => $datos['cache_age_minutes'] ?? null,
        ]);
    }

    /**
     * POST /admin/revenue/scrape-multi
     * Lanza el comando revenue:scrape-multi en background y devuelve el job_id.
     * El frontend hace polling a /admin/revenue/scrape-progress/{jobId}.
     */
    public function scrapeMulti(Request $request)
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'dias' => 'required|integer|between:1,90',
            'zona' => 'nullable|in:algeciras_centro,algeciras_costa,bahia_completa',
            'adultos' => 'nullable|integer|between:1,10',
        ]);

        $jobId = 'scrape_' . bin2hex(random_bytes(8));
        $fechaDesde = Carbon::parse($request->input('fecha_desde'))->toDateString();
        $dias = (int) $request->input('dias');
        $zona = $request->input('zona', 'algeciras_centro');
        $adultos = (int) $request->input('adultos', 2);

        // Estado inicial en cache (para que el frontend vea algo desde el primer poll)
        Cache::put("revenue:scrape:{$jobId}", [
            'job_id' => $jobId,
            'started_at' => now()->toIso8601String(),
            'fecha_desde' => $fechaDesde,
            'dias' => $dias,
            'step' => 0,
            'total' => $dias,
            'fase' => 'arrancando',
            'fase_texto' => 'Arrancando proceso en background...',
            'segundos_transcurridos' => 0,
            'segundos_estimados' => $dias * 5,
            'apartamentos_actualizados' => 0,
            'completed' => false,
            'errores' => [],
        ], 3600);

        // Lanzar comando Artisan en BACKGROUND (no bloquea la peticion HTTP)
        $phpBin = PHP_BINARY;
        $artisan = base_path('artisan');
        $logFile = storage_path("logs/scrape-multi-{$jobId}.log");
        $cmd = sprintf(
            '"%s" "%s" revenue:scrape-multi %s %s %d %s %d',
            $phpBin,
            $artisan,
            escapeshellarg($jobId),
            escapeshellarg($fechaDesde),
            $dias,
            escapeshellarg($zona),
            $adultos
        );

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: usar proc_open con DETACHED_PROCESS+CREATE_NEW_CONSOLE para
            // realmente desacoplar el proceso del padre. start /B desde popen no
            // siempre funciona desde el php server built-in.
            $descriptorspec = [
                0 => ['file', 'NUL', 'r'],
                1 => ['file', $logFile, 'w'],
                2 => ['file', $logFile, 'a'],
            ];
            $proc = proc_open($cmd, $descriptorspec, $pipes, base_path(), null, [
                'bypass_shell' => false,
                'create_new_console' => true,
                'create_process_group' => true,
            ]);
            if (is_resource($proc)) {
                proc_close($proc);  // close handle pero el proceso sigue vivo
            }
        } else {
            exec("nohup {$cmd} > {$logFile} 2>&1 &");
        }

        Log::info('[Revenue] scrape-multi job lanzado', ['job_id' => $jobId, 'dias' => $dias]);

        return response()->json([
            'job_id' => $jobId,
            'segundos_estimados' => $dias * 5,
            'mensaje' => "Job lanzado. Consultar progreso en /admin/revenue/scrape-progress/{$jobId}",
        ]);
    }

    /**
     * GET /admin/revenue/scrape-progress/{jobId}
     * Devuelve el estado actual del job (para polling desde el frontend).
     */
    public function scrapeProgress(string $jobId)
    {
        $state = Cache::get("revenue:scrape:{$jobId}");
        if (!$state) {
            return response()->json(['error' => 'Job no existe o ha expirado'], 404);
        }
        return response()->json($state);
    }

    /**
     * POST /admin/revenue/aplicar-libres-hoy
     * Aplica el precio recomendado a TODOS los apartamentos libres en
     * una fecha. Ataja del flujo "1 click".
     */
    public function aplicarLibresHoy(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'apartamento_ids' => 'nullable|array',
            'apartamento_ids.*' => 'integer|exists:apartamentos,id',
        ]);
        $fecha = Carbon::parse($request->input('fecha'));
        $aptIds = $request->input('apartamento_ids');  // si null = todos los libres

        $situacion = $this->recomendador->apartamentosEnFecha($fecha);
        $libres = collect($situacion)->where('libre', true);
        if ($aptIds) {
            $libres = $libres->filter(fn($r) => in_array($r['apartamento']->id, $aptIds));
        }

        $cambios = [];
        foreach ($libres as $row) {
            $apt = $row['apartamento'];
            $rec = RevenueRecomendacion::where('apartamento_id', $apt->id)
                ->whereDate('fecha', $fecha)
                ->first();
            if ($rec && $rec->precio_recomendado) {
                $cambios[] = [
                    'apartamento_id' => $apt->id,
                    'fecha' => $fecha->toDateString(),
                    'precio' => (float) $rec->precio_recomendado,
                ];
            }
        }

        if (empty($cambios)) {
            return response()->json([
                'error' => 'No hay precios recomendados. Ejecuta primero "Calcular precios competencia".',
            ], 422);
        }

        $stats = $this->channexService->aplicarCambios($cambios, auth()->id());

        Log::info('[Revenue] aplicar-libres-hoy', [
            'user_id' => auth()->id(),
            'fecha' => $fecha->toDateString(),
            'count' => count($cambios),
            'stats' => $stats,
        ]);

        return response()->json($stats + ['fecha' => $fecha->toDateString()]);
    }

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

        // [2026-04-30] Solo apartamentos comerciables. Excluye zonas comunes
        // (escaleras, oficina, lavanderia) y registros de test.
        $apartamentos = Apartamento::apartamentosReales()
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
