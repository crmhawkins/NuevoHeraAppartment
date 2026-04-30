<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-29] Cliente HTTP que llama al servicio Python de scraping
 * (revenue-scraper-local/service.py).
 *
 * El servicio Python corre en local del admin (Windows en pruebas) o en
 * el servidor IA Hawkins en produccion.
 *
 * Configuracion en .env:
 *   REVENUE_SCRAPER_URL=http://127.0.0.1:8765
 *   REVENUE_SCRAPER_TOKEN=dev-token-cambiar-en-produccion
 */
class RevenueScraperClient
{
    public function __construct(
        private ?string $baseUrl = null,
        private ?string $token = null,
    ) {
        $this->baseUrl = $baseUrl ?? rtrim(env('REVENUE_SCRAPER_URL', 'http://127.0.0.1:8765'), '/');
        $this->token = $token ?? env('REVENUE_SCRAPER_TOKEN', 'dev-token-cambiar-en-produccion');
    }

    /**
     * Comprueba que el servicio esta vivo. No requiere token.
     */
    public function health(): array
    {
        try {
            $r = Http::timeout(5)->get("{$this->baseUrl}/health");
            return [
                'alive' => $r->successful(),
                'status' => $r->status(),
                'data' => $r->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'alive' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Lanza scrape del mercado para una zona y fechas.
     *
     * @param string $fechaDesde "YYYY-MM-DD"
     * @param string $fechaHasta "YYYY-MM-DD"
     * @param string $zona "algeciras_centro" | "algeciras_costa" | "bahia_completa"
     * @param int $adultos
     * @param bool $useCache
     * @return array Estructura ScrapeResponse del Python service
     * @throws \RuntimeException si el servicio no responde o devuelve error
     */
    public function scrapeMercado(
        string $fechaDesde,
        string $fechaHasta,
        string $zona = 'algeciras_centro',
        int $adultos = 2,
        bool $useCache = true,
    ): array {
        $payload = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'zona' => $zona,
            'adultos' => $adultos,
            'incluir_airbnb' => true,
            'incluir_booking' => true,
            'use_cache' => $useCache,
        ];

        try {
            // 90s timeout: scraping Booking puede tardar 30-40s
            $r = Http::timeout(90)
                ->withHeaders(['X-Service-Token' => $this->token])
                ->post("{$this->baseUrl}/scrape-mercado", $payload);
        } catch (\Throwable $e) {
            Log::error('[Revenue] scraper Python no responde', [
                'url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                "El servicio de scraping no responde. ¿Está levantado el servidor Python? " .
                "Comprueba: {$this->baseUrl}/health  · Error: {$e->getMessage()}"
            );
        }

        if (!$r->successful()) {
            Log::error('[Revenue] scraper devolvio error', [
                'status' => $r->status(),
                'body' => mb_substr($r->body(), 0, 500),
            ]);
            throw new \RuntimeException(
                "Scraper devolvio HTTP {$r->status()}: " . mb_substr($r->body(), 0, 200)
            );
        }

        return $r->json();
    }
}
