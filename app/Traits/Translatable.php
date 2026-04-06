<?php

namespace App\Traits;

use App\Services\TranslationService;
use Illuminate\Support\Facades\App;

trait Translatable
{
    /**
     * Obtiene un campo traducido
     * 
     * @param string $field Nombre del campo
     * @param string|null $locale Idioma (si es null, usa el locale actual)
     * @return string
     */
    public function getTranslated(string $field, ?string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        $originalValue = $this->getAttribute($field);

        if (empty($originalValue)) {
            return '';
        }

        // Si es español, devolver el valor original
        if ($locale === 'es') {
            return $originalValue;
        }

        // Generar identificador único
        $identifier = $this->getTranslationIdentifier($field);

        // Obtener updated_at del modelo
        $updatedAt = $this->updated_at ?? null;

        // Usar el servicio de traducción
        $translationService = app(TranslationService::class);
        
        // Primero intentar desde JSON
        $translated = $translationService->getFromJson($identifier, $locale, $updatedAt);
        
        if ($translated) {
            return $translated;
        }

        // Si no existe, traducir y guardar
        return $translationService->getOrTranslate($identifier, $originalValue, $locale, $updatedAt);
    }

    /**
     * Genera un identificador único para la traducción
     */
    protected function getTranslationIdentifier(string $field): string
    {
        $modelName = strtolower(class_basename($this));
        $id = $this->getKey();
        return "{$modelName}_{$id}_{$field}";
    }

    /**
     * Magic method para acceder a campos traducidos
     * Ejemplo: $apartamento->titulo_translated
     */
    public function __get($key)
    {
        if (str_ends_with($key, '_translated')) {
            $field = str_replace('_translated', '', $key);
            if ($this->isFillable($field) || in_array($field, $this->getFillable())) {
                return $this->getTranslated($field);
            }
        }

        return parent::__get($key);
    }
}

