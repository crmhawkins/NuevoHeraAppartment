<?php

use App\Services\TranslationService;
use Illuminate\Support\Facades\App;

if (!function_exists('translated')) {
    /**
     * Helper para obtener un campo traducido de un modelo
     * 
     * @param mixed $model Modelo con trait Translatable
     * @param string $field Campo a traducir
     * @param string|null $fallback Valor por defecto si no existe
     * @return string
     */
    function translated($model, string $field, ?string $fallback = null): string
    {
        if (!$model || !method_exists($model, 'getTranslated')) {
            return $fallback ?? '';
        }

        $value = $model->getTranslated($field);
        return $value ?: ($fallback ?? '');
    }
}

if (!function_exists('translate_dynamic')) {
    /**
     * Helper para traducir contenido dinámico de la base de datos.
     * 
     * IMPORTANTE: NO modifica el HTML, solo traduce el texto preservando la estructura original.
     *
     * @param string $text El texto original de la base de datos.
     * @param string|null $locale El idioma al que traducir (por defecto, el idioma actual de la app).
     * @param string $sourceLocale El idioma original del contenido en la BD (por defecto 'es').
     * @return string El texto traducido o el original si falla la traducción.
     */
    function translate_dynamic(string $text, ?string $locale = null, string $sourceLocale = 'es'): string
    {
        $locale = $locale ?? App::getLocale();

        if ($locale === $sourceLocale || empty($text)) {
            return $text;
        }

        // NO MODIFICAR EL HTML EN ABSOLUTO - Solo traducir el texto
        // Si el texto contiene HTML, devolver el HTML original SIN MODIFICAR
        // La traducción se hará en el controlador antes de enviar a la vista
        if (strpos($text, '<') !== false) {
            // Si tiene HTML, NO traducir aquí - devolver el HTML original
            // Esto evita modificar la estructura HTML
            return $text;
        }
        
        // Si no es HTML, traducir directamente
        $textPlain = trim($text);
        if (empty($textPlain)) {
            return $text;
        }

        // Generar un identificador único basado en el hash del texto plano
        $identifier = 'dynamic_' . md5($textPlain . $sourceLocale);

        // Usar el servicio de traducción
        $translationService = app(TranslationService::class);
        
        // Intentar obtener desde JSON
        $translated = $translationService->getFromJson($identifier, $locale);
        
        if (!$translated) {
            // Si no existe, traducir y guardar
            $translated = $translationService->getOrTranslate($identifier, $textPlain, $locale);
        }
        
        return $translated ?: $text;
    }
}
