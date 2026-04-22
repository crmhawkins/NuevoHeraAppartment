<?php

namespace App\Support;

/**
 * [2026-04-22] Saneador de nombres de archivo subidos por el cliente.
 *
 * Meta:
 *  - Aceptar CUALQUIER nombre legitimo (acentos, eñes, caracteres
 *    chinos/arabes/cirilicos, numeros). El cliente sube "Fàctura España.pdf"
 *    y queremos conservarlo lo mas parecido posible.
 *  - Eliminar caracteres INVISIBLES que rompen el sistema de archivos:
 *      * control (U+0000-U+001F, U+007F, U+0080-U+009F)
 *      * formato invisible: U+00AD (soft hyphen), U+200B-U+200F, U+202A-U+202E,
 *        U+2060, U+FEFF, etc. (categoria Unicode Cf)
 *  - Eliminar caracteres ilegales en Windows: < > : " / \ | ? *
 *  - Colapsar espacios / tabs a un solo espacio.
 *  - Limitar longitud total a 120 chars.
 *
 * Si tras limpiar el nombre queda vacio, devuelve 'file'.
 */
class SafeFilename
{
    /**
     * Saneamiento completo. Devuelve 'base.ext' listo para concatenar a timestamp.
     */
    public static function make(string $originalName, ?string $fallbackExt = null): string
    {
        $originalName = (string) $originalName;
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: ($fallbackExt ?: ''));
        $base = pathinfo($originalName, PATHINFO_FILENAME);

        $cleanBase = self::sanitize($base);
        if ($cleanBase === '') {
            $cleanBase = 'file';
        }
        if (strlen($cleanBase) > 120) {
            $cleanBase = substr($cleanBase, 0, 120);
        }

        return $ext !== '' ? $cleanBase . '.' . $ext : $cleanBase;
    }

    /**
     * Limpieza del "stem" (sin extension).
     */
    public static function sanitize(string $s): string
    {
        // Normalizar a UTF-8 seguro
        $s = (string) $s;
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'auto');
        }

        // 1) Quitar caracteres de control (Cc) y de formato invisible (Cf)
        //    La clase Unicode \p{C} cubre: Cc, Cf, Cs, Co, Cn. Usamos u para Unicode.
        $s = preg_replace('/[\p{Cc}\p{Cf}]+/u', '', $s);

        // 2) Quitar caracteres prohibidos en FS Windows/Linux: < > : " / \ | ? *
        $s = preg_replace('/[<>:"\/\\\\|?*]+/u', '_', $s);

        // 3) Colapsar whitespace (incluidos tabs, newlines) a un solo espacio
        $s = preg_replace('/\s+/u', ' ', $s);

        // 4) Trim + quitar puntos y guiones en extremos
        $s = trim($s, " \t\n\r\0\x0B._-");

        return $s;
    }
}
