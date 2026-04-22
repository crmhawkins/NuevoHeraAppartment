<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Support\DniImageOrienter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-22] Re-orientar fotos de DNI / pasaporte ya subidas antes del
 * commit de auto-orient. El fichero en disco queda rotado al gusto del
 * movil del cliente (EXIF o vertical) y tanto el panel admin como el
 * OCR de re-analisis las leen mal.
 *
 * Uso:
 *   php artisan dni:orientar-existentes --dry-run
 *   php artisan dni:orientar-existentes                  (solo las que haga falta)
 *   php artisan dni:orientar-existentes --reserva=6335   (una en concreto)
 *   php artisan dni:orientar-existentes --desde=2026-04-01
 *
 * Idempotente: si la foto ya esta apaisada y sin EXIF de rotacion, no
 * se toca. Se guarda backup de la original en {archivo}.bak si se pasa
 * --backup.
 */
class DniOrientarExistentes extends Command
{
    protected $signature = 'dni:orientar-existentes
        {--dry-run : no escribe, solo reporta que se cambiaria}
        {--reserva= : procesar solo las fotos de esta reserva}
        {--desde= : procesar solo fotos de reservas con fecha_entrada posterior a YYYY-MM-DD}
        {--backup : guardar copia .bak de cada foto antes de sobreescribir}
        {--limit=1000 : maximo de fotos a procesar por ejecucion}';

    protected $description = 'Re-orienta fotos de DNI/pasaporte ya subidas (corrige EXIF y pone apaisado). Idempotente.';

    public function handle(): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $backup  = (bool) $this->option('backup');
        $limit   = (int) $this->option('limit');
        $reserva = $this->option('reserva');
        $desde   = $this->option('desde');

        $q = Photo::query()
            ->whereIn('photo_categoria_id', [13, 14, 15, 16])
            ->orderByDesc('id')
            ->limit($limit);

        if ($reserva) {
            $q->where('reserva_id', (int) $reserva);
        }
        if ($desde) {
            // Photo no tiene relacion explicita con Reserva en el modelo, asi
            // que filtramos por reserva_id in (select ...) a mano.
            $ids = \App\Models\Reserva::query()
                ->whereDate('fecha_entrada', '>=', $desde)
                ->pluck('id');
            $q->whereIn('reserva_id', $ids);
        }

        $fotos = $q->get();
        if ($fotos->isEmpty()) {
            $this->info('No hay fotos que procesar con esos filtros.');
            return self::SUCCESS;
        }

        $this->info("Evaluando {$fotos->count()} fotos...");
        $reorientadas = 0;
        $yaOk = 0;
        $errores = 0;

        foreach ($fotos as $f) {
            $rel = preg_replace('~^private/~', '', (string) $f->url);
            $abs = storage_path('app/' . $rel);

            if (!is_file($abs)) {
                $errores++;
                $this->line(" [?]  photo#{$f->id}: archivo no existe ({$rel})");
                continue;
            }

            // Comprobar si hace falta tocarla: si ya esta apaisada y sin
            // EXIF Orientation distinto de 1, lo dejamos como esta.
            if ($this->estaYaOk($abs)) {
                $yaOk++;
                continue;
            }

            if ($dryRun) {
                $this->line(" [DRY] photo#{$f->id} ({$rel}) — se reorientaria");
                $reorientadas++;
                continue;
            }

            $bytes = DniImageOrienter::autoOrient($abs);
            if ($bytes === null) {
                $errores++;
                $this->line(" [X]  photo#{$f->id}: GD no pudo procesar");
                continue;
            }

            if ($backup) {
                @copy($abs, $abs . '.bak');
            }
            if (@file_put_contents($abs, $bytes) === false) {
                $errores++;
                $this->line(" [X]  photo#{$f->id}: no se pudo sobreescribir");
                continue;
            }

            $reorientadas++;
            $this->info(" [OK] photo#{$f->id} reorientada");
            Log::info('[dni:orientar-existentes] Re-orientada', ['photo_id' => $f->id, 'url' => $f->url]);
        }

        $this->line('');
        $this->info("Total: {$fotos->count()} | re-orientadas: {$reorientadas} | ya OK: {$yaOk} | errores: {$errores}");
        return self::SUCCESS;
    }

    /**
     * Una foto ya esta "OK" si cumple: es apaisada (ancho > alto) Y no
     * tiene EXIF Orientation que reclame rotacion (1 o ausente).
     */
    private function estaYaOk(string $path): bool
    {
        if (!function_exists('imagecreatefromstring')) return true; // sin GD no tocamos nada

        // Dimensiones rapidas sin cargar todo
        $info = @getimagesize($path);
        if (!$info) return false;
        [$w, $h] = $info;
        if ($h > $w) return false; // vertical -> hay que rotar

        // EXIF orientation != 1 tambien requiere re-orientar
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($path);
                if (is_array($exif) && isset($exif['Orientation']) && (int) $exif['Orientation'] !== 1) {
                    return false;
                }
            } catch (\Throwable $e) { /* ignorar, asumimos OK */ }
        }
        return true;
    }
}
