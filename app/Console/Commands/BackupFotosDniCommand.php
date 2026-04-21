<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-21] Backup horario de las fotos de DNI.
 *
 * Contexto del problema que arregla:
 *  Coolify, cuando recrea el contenedor por cualquier razon, ejecuta
 *  en su entrypoint "rm -rf *" si no encuentra .git, y despues clona
 *  de nuevo el repo. Los archivos subidos por clientes (fotos de DNI)
 *  que no estan en git se PIERDEN en ese reset. Antes teniamos los
 *  DNIs en public/imagesCliente/ trackeados en git (sobreviven pero
 *  son publicos); los nuevos en storage/app/photos/dni/ no estan
 *  trackeados (privados pero pueden perderse en un recreate).
 *
 * Solucion por capas (esta es una de ellas):
 *  1. .gitignore impide re-anadirlos al repo.
 *  2. Volumen Docker persistente (ya existe: laravel-files en /var/www/html).
 *  3. ESTE comando: cada hora sincroniza storage/app/photos/ a una
 *     carpeta del HOST (fuera del volumen Docker del contenedor) que
 *     sobrevive aunque el contenedor se destruya y recree.
 *
 * Ruta de backup: /var/www/html/storage/backups-externo/photos-dni/
 *  - Esta carpeta tambien esta dentro del volumen (porque es la misma
 *    raiz /var/www/html), PERO el admin en Coolify puede montarla a
 *    un path externo del host para maxima proteccion. Ver README.
 */
class BackupFotosDniCommand extends Command
{
    protected $signature = 'backup:fotos-dni {--dry-run : Solo mostrar que se copiaria sin hacer nada}';
    protected $description = 'Sincroniza fotos de DNI a una carpeta de backup persistente.';

    public function handle(): int
    {
        $origen = storage_path('app/photos');
        $destino = storage_path('backups-externo/photos-dni');

        if (!is_dir($origen)) {
            $this->warn("Origen no existe: {$origen} (no hay fotos para backupear)");
            return 0;
        }

        if (!is_dir($destino)) {
            if (!mkdir($destino, 0755, true) && !is_dir($destino)) {
                $this->error("No se pudo crear destino: {$destino}");
                Log::error('[BackupFotosDni] No se pudo crear destino', ['destino' => $destino]);
                return 1;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $copiados = 0;
        $saltados = 0;
        $errores = 0;

        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($origen, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterador as $item) {
            /** @var \SplFileInfo $item */
            $relativePath = str_replace($origen . DIRECTORY_SEPARATOR, '', $item->getPathname());
            $destPath = $destino . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    if (!$dryRun) {
                        @mkdir($destPath, 0755, true);
                    }
                }
                continue;
            }

            // Solo copiar si no existe en destino o si el origen es mas reciente
            $hayQueCopiar = false;
            if (!is_file($destPath)) {
                $hayQueCopiar = true;
            } elseif (filemtime($item->getPathname()) > filemtime($destPath)) {
                $hayQueCopiar = true;
            } elseif (filesize($item->getPathname()) !== filesize($destPath)) {
                $hayQueCopiar = true;
            }

            if ($hayQueCopiar) {
                if ($dryRun) {
                    $this->line("  [DRY] copiaria: {$relativePath}");
                    $copiados++;
                    continue;
                }
                if (@copy($item->getPathname(), $destPath)) {
                    $copiados++;
                } else {
                    $errores++;
                    Log::warning('[BackupFotosDni] Error copiando', ['file' => $relativePath]);
                }
            } else {
                $saltados++;
            }
        }

        $resumen = "Copiados: {$copiados} | Saltados (ya OK): {$saltados} | Errores: {$errores}";
        $this->info($resumen);
        Log::info('[BackupFotosDni] Backup horario completado. ' . $resumen);

        return $errores > 0 ? 1 : 0;
    }
}
