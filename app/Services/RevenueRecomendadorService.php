<?php

namespace App\Services;

use App\Models\Apartamento;
use App\Models\Reserva;
use Carbon\Carbon;

/**
 * [2026-04-29] Lógica de Revenue Management:
 *  - Detecta apartamentos libres en una fecha
 *  - Calcula precio recomendado por apartamento basándose en datos de
 *    competencia y reglas internas (factor segmento, finde, ocupación)
 *  - NO toca Channex aquí — solo prepara los datos. El push lo hace
 *    ChannexRevenueService cuando el admin pulsa "Aplicar".
 *
 * Algoritmo MVP (v1):
 *   precio_base = mediana_competencia * factor_segmento_apartamento
 *   ajustes:
 *     - es_finde (V/S/D): +10%
 *     - es_festivo: +15%
 *     - ocupacion_propia > 80% en esa fecha: +5%
 *     - ocupacion_propia < 30% y < 14 dias vista: -10% (urgencia)
 *     - clamp(precio, revenue_min, revenue_max)
 */
class RevenueRecomendadorService
{
    private const FACTOR_PREMIUM = 1.10;
    private const FACTOR_MATCH   = 1.00;
    private const FACTOR_BUDGET  = 0.90;

    /**
     * Devuelve apartamentos libres una fecha concreta + apartamentos
     * ocupados (con reserva) para mostrar contexto.
     *
     * @return array<int, array{apartamento: Apartamento, libre: bool, reserva: ?Reserva}>
     */
    public function apartamentosEnFecha(Carbon $fecha): array
    {
        $apartamentos = Apartamento::orderBy('nombre')->get();

        // Reservas activas (no canceladas) que solapen esa fecha
        $reservas = Reserva::query()
            ->whereNotIn('estado_id', [4, 9])
            ->whereNull('deleted_at')
            ->whereDate('fecha_entrada', '<=', $fecha)
            ->whereDate('fecha_salida', '>', $fecha)  // salida exclusiva: si sale el dia 5, no ocupa la noche del 5
            ->with('cliente')
            ->get()
            ->keyBy('apartamento_id');

        $resultado = [];
        foreach ($apartamentos as $apt) {
            $resultado[] = [
                'apartamento' => $apt,
                'libre' => !isset($reservas[$apt->id]),
                'reserva' => $reservas[$apt->id] ?? null,
            ];
        }
        return $resultado;
    }

    /**
     * Calcula la recomendación de precio para UN apartamento en UNA fecha
     * dada las estadísticas de competencia.
     *
     * @param array $statsCompetencia ej. ['mediana'=>80, 'media'=>87, 'min'=>40, 'max'=>281, 'n'=>25]
     * @return array {precio_recomendado, razonamiento, ajustes_aplicados, precio_actual}
     */
    public function calcularRecomendacion(
        Apartamento $apt,
        Carbon $fecha,
        array $statsCompetencia,
        ?float $precioActual = null,
    ): array {
        if (empty($statsCompetencia['mediana'])) {
            return [
                'precio_recomendado' => null,
                'precio_actual' => $precioActual,
                'razonamiento' => 'Sin datos de competencia. Necesario scrape exitoso primero.',
                'ajustes_aplicados' => [],
            ];
        }

        $mediana = (float) $statsCompetencia['mediana'];
        $factor = match ($apt->revenue_factor_segmento ?? 'match') {
            'premium' => self::FACTOR_PREMIUM,
            'budget'  => self::FACTOR_BUDGET,
            default   => self::FACTOR_MATCH,
        };

        $precio = $mediana * $factor;
        $ajustes = [
            "Mediana competencia: {$mediana}€",
            "Factor segmento ({$apt->revenue_factor_segmento}): ×{$factor}",
        ];

        // Es finde (V/S/D)?
        $diaSemana = $fecha->dayOfWeekIso; // 1=lun ... 7=dom
        $esFinde = in_array($diaSemana, [5, 6, 7], true);
        if ($esFinde) {
            $precio *= 1.10;
            $ajustes[] = 'Fin de semana: ×1.10';
        }

        // Festivo (lista hardcodeada Andalucia/España — ampliable)
        if ($this->esFestivoLocal($fecha)) {
            $precio *= 1.15;
            $ajustes[] = 'Festivo: ×1.15';
        }

        // Ocupación propia
        $ocupPct = $this->ocupacionPropia($fecha);
        if ($ocupPct > 80) {
            $precio *= 1.05;
            $ajustes[] = "Ocupación nuestra alta ({$ocupPct}%): ×1.05";
        } elseif ($ocupPct < 30 && $fecha->diffInDays(now()) <= 14) {
            $precio *= 0.95;
            $ajustes[] = "Ocupación baja + última hora: ×0.95";
        }

        // Clamp con guardrails del apartamento
        $minP = $apt->revenue_min_precio ? (float) $apt->revenue_min_precio : null;
        $maxP = $apt->revenue_max_precio ? (float) $apt->revenue_max_precio : null;
        if ($minP !== null && $precio < $minP) {
            $precio = $minP;
            $ajustes[] = "Clamp a mínimo: {$minP}€";
        }
        if ($maxP !== null && $precio > $maxP) {
            $precio = $maxP;
            $ajustes[] = "Clamp a máximo: {$maxP}€";
        }

        $precio = round($precio, 0);  // redondeo a euros enteros para precios cómodos
        $razonamiento = implode(' · ', $ajustes);

        return [
            'precio_recomendado' => $precio,
            'precio_actual' => $precioActual,
            'razonamiento' => $razonamiento,
            'ajustes_aplicados' => $ajustes,
            'mediana_competencia' => $mediana,
            'es_finde' => $esFinde,
            'es_festivo' => $this->esFestivoLocal($fecha),
            'ocupacion_pct' => $ocupPct,
        ];
    }

    /**
     * % de ocupación nuestra en esa fecha (0-100).
     */
    public function ocupacionPropia(Carbon $fecha): float
    {
        $total = Apartamento::count();
        if ($total === 0) return 0.0;

        $ocupados = Reserva::query()
            ->whereNotIn('estado_id', [4, 9])
            ->whereNull('deleted_at')
            ->whereDate('fecha_entrada', '<=', $fecha)
            ->whereDate('fecha_salida', '>', $fecha)
            ->distinct('apartamento_id')
            ->count('apartamento_id');

        return round(($ocupados / $total) * 100, 1);
    }

    /**
     * Calcula varios escenarios de pricing en paralelo para que el admin
     * pueda comparar antes de decidir cuál aplicar.
     *
     * @param array $listings  Listings crudos del scraper (con plataforma/precio/tipo)
     * @return array  Array de estrategias con precios por apartamento + ingreso esperado
     */
    public function compararEstrategias(array $apartamentos, Carbon $fecha, array $listings): array
    {
        // Filtrar listings con precio
        $conPrecio = array_filter($listings, fn($l) => isset($l['precio']) && $l['precio'] > 0);

        // Mediana TODOS los listings (mercado entero, mezcla hostales+habitaciones+apartamentos)
        $todosPrecios = array_values(array_map(fn($l) => (float) $l['precio'], $conPrecio));
        sort($todosPrecios);
        $medianaTodos = $this->mediana($todosPrecios);

        // Solo apartamentos/casas enteras (filtrar hostales y habitaciones)
        $premiumOnly = array_filter($conPrecio, function ($l) {
            $tipo = strtolower((string) ($l['tipo'] ?? ''));
            $titulo = strtolower((string) ($l['titulo'] ?? ''));
            $esHabitacion = str_contains($tipo, 'hotel') || str_contains($tipo, 'hostal') ||
                           str_contains($titulo, 'habitación') || str_contains($titulo, 'habitacion');
            $esApartamento = str_contains($tipo, 'apartamento') || str_contains($tipo, 'casa') ||
                            str_contains($tipo, 'estudio') || str_contains($tipo, 'vivienda') ||
                            str_contains($tipo, 'entire') || str_contains($titulo, 'apartamento');
            return $esApartamento && !$esHabitacion;
        });
        $premiumPrecios = array_values(array_map(fn($l) => (float) $l['precio'], $premiumOnly));
        sort($premiumPrecios);
        $medianaPremium = $this->mediana($premiumPrecios);

        $statsAll = ['mediana' => $medianaTodos, 'media' => count($todosPrecios) ? array_sum($todosPrecios)/count($todosPrecios) : 0];
        $statsPremium = ['mediana' => $medianaPremium, 'media' => count($premiumPrecios) ? array_sum($premiumPrecios)/count($premiumPrecios) : 0];

        $estrategias = [];

        // === Estrategia 1: CONSERVADORA — mediana del mercado, sin tocar segmento ===
        $estrategias[] = $this->aplicarEstrategiaTodos(
            'A. Conservadora (mediana mercado completo)',
            'Mediana de TODOS los listings (incluye hostales y habitaciones que bajan el precio). Factor segmento del apartamento. Riesgo: dejas dinero sobre la mesa.',
            $apartamentos, $fecha, $statsAll, [], 'red'
        );

        // === Estrategia 2: PREMIUM EN TODOS — fuerza factor 1.10 ===
        $estrategias[] = $this->aplicarEstrategiaTodos(
            'B. Premium uniforme (+10% sobre mediana mercado)',
            'Mediana del mercado entero × 1.10 en todos. Asume que todos tus apartamentos son premium vs hostales/habitaciones.',
            $apartamentos, $fecha, $statsAll, ['factor_override' => 1.10], 'orange'
        );

        // === Estrategia 3: COMP SET REAL — solo apartamentos enteros ===
        $estrategias[] = $this->aplicarEstrategiaTodos(
            'C. Comp Set real (solo apartamentos enteros)',
            "Solo mediana de los apartamentos enteros realmente comparables (no hostales ni habitaciones). N=" . count($premiumPrecios) . " competidores reales. Mediana: " . round($medianaPremium) . "€.",
            $apartamentos, $fecha, $statsPremium, [], 'green'
        );

        // === Estrategia 4: PRICING INTELIGENTE — comp set + factor + finde + cap mínimo ===
        $estrategias[] = $this->aplicarEstrategiaTodos(
            'D. Pricing inteligente (recomendada)',
            'Comp set real + factor segmento del apartamento + ajustes finde/festivo/ocupación + clamp mín-máx. Lo que de verdad debería usarse.',
            $apartamentos, $fecha, $statsPremium, [], 'blue'
        );

        return [
            'estadisticas' => [
                'todos' => array_merge($statsAll, ['n' => count($todosPrecios)]),
                'premium_only' => array_merge($statsPremium, ['n' => count($premiumPrecios)]),
            ],
            'estrategias' => $estrategias,
        ];
    }

    private function aplicarEstrategiaTodos(string $nombre, string $descripcion, array $apartamentos, Carbon $fecha, array $stats, array $opts, string $color): array
    {
        $precios = [];
        $totalDia = 0;
        foreach ($apartamentos as $row) {
            /** @var \App\Models\Apartamento $apt */
            $apt = is_array($row) ? $row['apartamento'] : $row;
            $libre = is_array($row) ? ($row['libre'] ?? true) : true;

            // Si hay factor_override, lo seteamos temporal
            $factorOriginal = $apt->revenue_factor_segmento;
            if (!empty($opts['factor_override'])) {
                // calculamos manualmente sin guardar
                $factor = $opts['factor_override'];
                $precio = ($stats['mediana'] ?? 0) * $factor;
                if ($apt->revenue_min_precio && $precio < $apt->revenue_min_precio) $precio = $apt->revenue_min_precio;
                if ($apt->revenue_max_precio && $precio > $apt->revenue_max_precio) $precio = $apt->revenue_max_precio;
                $precio = round($precio, 0);
            } else {
                $rec = $this->calcularRecomendacion($apt, $fecha, $stats);
                $precio = $rec['precio_recomendado'] ?? 0;
            }

            $precios[$apt->id] = [
                'apartamento' => $apt->nombre,
                'libre' => $libre,
                'precio' => $precio,
                'min' => $apt->revenue_min_precio,
                'max' => $apt->revenue_max_precio,
                'segmento' => $factorOriginal,
            ];
            if ($libre) $totalDia += $precio;
        }
        return [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'color' => $color,
            'precios' => $precios,
            'total_dia' => $totalDia,
            'mes_70pct' => round($totalDia * 30 * 0.70, 0),
            'mes_85pct' => round($totalDia * 30 * 0.85, 0),
        ];
    }

    private function mediana(array $valores): float
    {
        if (empty($valores)) return 0;
        $n = count($valores);
        $mid = (int) ($n / 2);
        return $n % 2 === 0
            ? ($valores[$mid - 1] + $valores[$mid]) / 2
            : $valores[$mid];
    }

    /**
     * Festivos hardcodeados Andalucía 2026. Ampliable a tabla en BD.
     */
    public function esFestivoLocal(Carbon $fecha): bool
    {
        static $festivos = [
            // Nacionales 2026
            '2026-01-01', '2026-01-06', '2026-04-03', // Viernes Santo
            '2026-05-01', '2026-08-15', '2026-10-12',
            '2026-11-01', '2026-12-06', '2026-12-08', '2026-12-25',
            // Andalucía
            '2026-02-28',
            // Algeciras (feria, día de la patrona aprox — ajustable)
            '2026-06-23', '2026-06-24',  // San Juan
        ];
        return in_array($fecha->toDateString(), $festivos, true);
    }
}
