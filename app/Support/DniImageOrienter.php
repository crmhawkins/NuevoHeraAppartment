<?php

namespace App\Support;

/**
 * [2026-04-22] Corrector de orientacion para fotos de DNI / pasaporte.
 *
 * Problema: las fotos subidas desde movil vienen con metadato EXIF de
 * orientacion que el navegador aplica pero PHP ignora por defecto. Ademas
 * el usuario puede haber sacado la foto con el movil en vertical aunque
 * el DNI sea apaisado. Qwen3-VL tolera texto rotado pero pierde mucha
 * precision con texto pequeno (NUM SOPORTE, fechas), asi que es mejor
 * enderezar la foto antes de mandarla a OCR.
 *
 * Uso:
 *   $bytes = DniImageOrienter::autoOrient($path);   // bytes JPEG corregidos
 *   $tmp   = DniImageOrienter::rotarA($path, 180);  // copia temporal rotada N grados
 */
class DniImageOrienter
{
    /**
     * Devuelve los bytes (JPEG quality 90) de la imagen con su orientacion
     * corregida. Aplica primero la orientacion EXIF, luego, si sigue en
     * vertical, la rota 90 horario (el DNI espanol es SIEMPRE apaisado).
     *
     * Devuelve null si GD no esta disponible o la imagen es ilegible.
     */
    public static function autoOrient(string $path): ?string
    {
        if (!function_exists('imagecreatefromstring')) return null;

        $bytes = @file_get_contents($path);
        if ($bytes === false || $bytes === '') return null;

        $img = @imagecreatefromstring($bytes);
        if (!$img) return null;

        // 1) EXIF orientation (comun en fotos de movil)
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($path);
                if (is_array($exif) && isset($exif['Orientation'])) {
                    switch ((int) $exif['Orientation']) {
                        case 3: $img = imagerotate($img, 180, 0); break;
                        case 6: $img = imagerotate($img, -90, 0); break;
                        case 8: $img = imagerotate($img, 90, 0); break;
                    }
                }
            } catch (\Throwable $e) { /* ignorar */ }
        }

        // 2) Heuristica de aspecto: DNI espanol siempre apaisado. Si la
        //    imagen resultante sigue en vertical (alto > ancho), la giramos
        //    90 horario.
        if ($img && imagesy($img) > imagesx($img)) {
            $img = imagerotate($img, -90, 0);
        }

        if (!$img) return null;

        ob_start();
        imagejpeg($img, null, 90);
        $out = ob_get_clean();
        imagedestroy($img);

        return $out ?: null;
    }

    /**
     * Rota la imagen N grados y devuelve la ruta a un JPEG temporal.
     * El caller debe borrarlo con unlink() al terminar.
     */
    public static function rotarA(string $path, int $grados): ?string
    {
        if (!function_exists('imagecreatefromstring')) return null;

        $bytes = @file_get_contents($path);
        if ($bytes === false) return null;
        $img = @imagecreatefromstring($bytes);
        if (!$img) return null;

        $rotado = imagerotate($img, -$grados, 0);
        imagedestroy($img);
        if (!$rotado) return null;

        $tmp = tempnam(sys_get_temp_dir(), 'dnirot_') . '.jpg';
        imagejpeg($rotado, $tmp, 90);
        imagedestroy($rotado);
        return is_file($tmp) ? $tmp : null;
    }
}
