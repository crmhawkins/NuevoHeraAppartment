<?php

namespace App\Console\Commands;

use App\Models\Apartamento;
use App\Models\RevenueRecomendacion;
use App\Services\RevenueRecomendadorService;
use App\Services\RevenueScraperClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-30] Scrape multi-dia en background.
 *
 * Recorre N dias (default 7, max 90), para cada uno:
 *   1. Llama al scraper Python (con cache si esta caliente)
 *   2. Calcula recomendaciones por apartamento (con todas las estrategias)
 *   3. Guarda recomendaciones en BD
 *   4. Actualiza el progreso en cache para que el frontend lo lea
 *
 * El frontend hace polling al endpoint de progreso. Cuando completed=true
 * la matriz se refresca con todos los nuevos precios.
 *
 * Uso:
 *   php artisan revenue:scrape-multi {jobId} {fechaDesde} {dias} {zona?} {adultos?}
 */
class RevenueScrapeMulti extends Command
{
    protected $signature = 'revenue:scrape-multi
        {jobId}
        {fechaDesde}
        {dias=7}
        {zona=algeciras_centro}
        {adultos=2}';

    protected $description = 'Scrape competencia para N dias y guarda recomendaciones (background job)';

    public function handle(RevenueScraperClient $scraper, RevenueRecomendadorService $recomendador): int
    {
        $jobId = $this->argument('jobId');
        $fechaDesde = Carbon::parse($this->argument('fechaDesde'));
        $dias = min((int) $this->argument('dias'), 90);
        $zona = $this->argument('zona');
        $adultos = (int) $this->argument('adultos');

        $cacheKey = "revenue:scrape:{$jobId}";

        // Estado inicial
        $state = [
            'job_id' => $jobId,
            'fecha_desde' => $fechaDesde->toDateString(),
            'dias' => $dias,
            'zona' => $zona,
            'adultos' => $adultos,
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'step' => 0,
            'total' => $dias,
            'fase' => 'iniciando',
            'fase_texto' => 'Preparando scrape...',
            'fecha_actual' => null,
            'segundos_transcurridos' => 0,
            'segundos_estimados' => $dias * 5, // estimacion: ~5s/dia con cache (40s sin cache)
            'apartamentos_actualizados' => 0,
            'completed' => false,
            'errores' => [],
        ];
        Cache::put($cacheKey, $state, 3600);
        $startedAt = now();

        $apartamentos = Apartamento::orderBy('nombre')->get();

        Log::info("[Revenue] scrape-multi iniciado", ['job_id' => $jobId, 'dias' => $dias]);

        for ($i = 0; $i < $dias; $i++) {
            $fechaNoche = $fechaDesde->copy()->addDays($i);
            $checkout = $fechaNoche->copy()->addDay();

            // Actualizar estado: scrapeando
            $state['step'] = $i + 1;
            $state['fecha_actual'] = $fechaNoche->toDateString();
            $state['fase'] = 'scrapeando';
            $state['fase_texto'] = "Buscando precios competencia para {$fechaNoche->isoFormat('ddd D MMM')}...";
            $state['segundos_transcurridos'] = $startedAt->diffInSeconds(now());
            // Estimacion adaptativa: si llevamos N segundos en M dias, extrapolar
            if ($i > 0) {
                $segPorDia = $state['segundos_transcurridos'] / $i;
                $state['segundos_estimados'] = (int) ($segPorDia * $dias);
            }
            Cache::put($cacheKey, $state, 3600);

            // Scrape
            try {
                $datos = $scraper->scrapeMercado(
                    fechaDesde: $fechaNoche->toDateString(),
                    fechaHasta: $checkout->toDateString(),
                    zona: $zona,
                    adultos: $adultos,
                    useCache: true,
                );
            } catch (\Throwable $e) {
                $state['errores'][] = "Dia {$fechaNoche->toDateString()}: " . mb_substr($e->getMessage(), 0, 200);
                Cache::put($cacheKey, $state, 3600);
                Log::warning("[Revenue] scrape-multi error dia {$fechaNoche->toDateString()}", ['error' => $e->getMessage()]);
                continue;
            }

            // Fase: analizando + calculando
            $state['fase'] = 'analizando';
            $state['fase_texto'] = "Analizando {$datos['combinado']['n']} listings de {$fechaNoche->isoFormat('ddd D MMM')}...";
            Cache::put($cacheKey, $state, 3600);

            // Filtrar comp set (solo apartamentos enteros)
            $listings = $datos['listings'] ?? [];
            $premiumOnly = collect($listings)->filter(function ($l) {
                $tipo = strtolower((string) ($l['tipo'] ?? ''));
                $titulo = strtolower((string) ($l['titulo'] ?? ''));
                $esHabitacion = str_contains($tipo, 'hotel') || str_contains($tipo, 'hostal') ||
                                str_contains($titulo, 'habitación') || str_contains($titulo, 'habitacion');
                $esApartamento = str_contains($tipo, 'apartamento') || str_contains($tipo, 'casa') ||
                                str_contains($tipo, 'estudio') || str_contains($tipo, 'vivienda') ||
                                str_contains($tipo, 'entire') || str_contains($titulo, 'apartamento');
                return $esApartamento && !$esHabitacion;
            })->pluck('precio')->filter()->values();

            $statsCompet = [
                'mediana' => $premiumOnly->median() ?? ($datos['combinado']['mediana'] ?? null),
                'media' => $premiumOnly->avg() ?? ($datos['combinado']['media'] ?? null),
                'min' => $premiumOnly->min() ?? ($datos['combinado']['min'] ?? null),
                'max' => $premiumOnly->max() ?? ($datos['combinado']['max'] ?? null),
                'n' => $premiumOnly->count(),
            ];

            // Fase: guardando
            $state['fase'] = 'guardando';
            $state['fase_texto'] = "Guardando recomendaciones de {$fechaNoche->isoFormat('ddd D MMM')}...";
            Cache::put($cacheKey, $state, 3600);

            // Calcular recomendacion por apartamento + persistir
            foreach ($apartamentos as $apt) {
                $rec = $recomendador->calcularRecomendacion($apt, $fechaNoche, $statsCompet);
                if ($rec['precio_recomendado'] === null) continue;

                $valores = [
                    'precio_recomendado' => $rec['precio_recomendado'],
                    'competencia_media' => $statsCompet['media'],
                    'competencia_min' => $statsCompet['min'],
                    'competencia_max' => $statsCompet['max'],
                    'competidores_count' => $statsCompet['n'],
                    'ocupacion_nuestra_pct' => $rec['ocupacion_pct'],
                    'es_finde' => $rec['es_finde'],
                    'es_festivo' => $rec['es_festivo'],
                    'razonamiento' => $rec['razonamiento'],
                    'calculado_at' => now(),
                ];
                $existente = RevenueRecomendacion::where('apartamento_id', $apt->id)
                    ->whereDate('fecha', $fechaNoche)
                    ->first();
                if ($existente) {
                    $existente->update($valores);
                } else {
                    RevenueRecomendacion::create(array_merge($valores, [
                        'apartamento_id' => $apt->id,
                        'fecha' => $fechaNoche->toDateString(),
                    ]));
                }
                $state['apartamentos_actualizados']++;
            }

            $state['segundos_transcurridos'] = $startedAt->diffInSeconds(now());
            Cache::put($cacheKey, $state, 3600);
        }

        // Terminado
        $state['completed'] = true;
        $state['finished_at'] = now()->toIso8601String();
        $state['fase'] = 'completado';
        $state['fase_texto'] = "Listo: {$state['apartamentos_actualizados']} recomendaciones calculadas en {$dias} días.";
        $state['segundos_transcurridos'] = $startedAt->diffInSeconds(now());
        Cache::put($cacheKey, $state, 7200);

        Log::info("[Revenue] scrape-multi completado", [
            'job_id' => $jobId,
            'dias' => $dias,
            'segundos' => $state['segundos_transcurridos'],
            'apartamentos_actualizados' => $state['apartamentos_actualizados'],
        ]);

        return self::SUCCESS;
    }
}
