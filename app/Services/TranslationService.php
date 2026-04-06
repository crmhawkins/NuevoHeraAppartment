<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TranslationService
{
    protected $apiUrl;
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiUrl = config('services.ai_translation.url', env('AI_TRANSLATION_URL', 'https://192.168.1.45/chat/chat'));
        $this->apiKey = config('services.ai_translation.api_key', env('AI_TRANSLATION_API_KEY'));
        $this->model = config('services.ai_translation.model', env('AI_TRANSLATION_MODEL', 'gpt-oss:120b-cloud'));
    }

    /**
     * Traduce un texto al idioma solicitado
     * 
     * @param string $text Texto a traducir
     * @param string $targetLocale Idioma destino (es, en, fr, de, it, pt)
     * @param string $sourceLocale Idioma origen (por defecto 'es')
     * @return string|null Texto traducido o null si falla
     */
    public function translate(string $text, string $targetLocale, string $sourceLocale = 'es'): ?string
    {
        // Si el idioma destino es el mismo que el origen, no traducir
        if ($targetLocale === $sourceLocale || empty($text)) {
            return $text;
        }

        // Limpiar el texto
        $text = trim($text);
        if (empty($text)) {
            return $text;
        }

        try {
            $localeNames = [
                'es' => 'ES',
                'en' => 'EN',
                'fr' => 'FR',
                'de' => 'DE',
                'it' => 'IT',
                'pt' => 'PT'
            ];

            $targetLang = $localeNames[$targetLocale] ?? 'EN';
            $sourceLang = $localeNames[$sourceLocale] ?? 'ES';

            $prompt = "Traduceme esto a {$targetLang}: {$text}";

            $response = Http::timeout(30)
                ->withoutVerifying() // Deshabilitar verificación SSL para desarrollo
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'prompt' => $prompt
                ]);

            if ($response->successful()) {
                $result = $response->json();
                
                // Log para debugging
                Log::debug('Respuesta de API de traducción', [
                    'result' => $result,
                    'text' => $text,
                    'locale' => $targetLocale
                ]);
                
                // Intentar diferentes formatos de respuesta (respuesta es el campo principal)
                $translated = $result['respuesta'] 
                    ?? $result['response'] 
                    ?? $result['message'] 
                    ?? $result['text'] 
                    ?? $result['content']
                    ?? $result['translation']
                    ?? $result['answer']
                    ?? $result['output']
                    ?? (is_string($result) ? $result : null);
                
                // Si la respuesta está dentro de metadata.message.content
                if (!$translated && isset($result['metadata']['message']['content'])) {
                    $translated = $result['metadata']['message']['content'];
                }
                
                // Si es un array, intentar obtener el primer valor
                if (is_array($translated) && !empty($translated)) {
                    $translated = reset($translated);
                }
                
                // Si aún no tenemos traducción, intentar buscar en toda la estructura
                if (!$translated && is_array($result)) {
                    // Buscar cualquier valor string que no sea el prompt
                    foreach ($result as $key => $value) {
                        if (is_string($value) && $value !== $prompt && strlen($value) > 0 && $key !== 'modelo') {
                            $translated = $value;
                            break;
                        }
                    }
                }
                
                if ($translated && is_string($translated)) {
                    $translated = trim($translated);
                    
                    // Limpiar formato markdown y prefijos/sufijos de la respuesta
                    // Eliminar **texto en negrita** pero mantener el texto
                    $translated = preg_replace('/\*\*(.*?)\*\*/', '$1', $translated);
                    // Eliminar *texto en cursiva* pero mantener el texto
                    $translated = preg_replace('/\*(.*?)\*/', '$1', $translated);
                    // Eliminar líneas que contengan solo "Traducción al francés:" o similares
                    $translated = preg_replace('/^.*?(Traducción|Translation|Traducción a|Traduction en).*?$/mi', '', $translated);
                    // Eliminar prefijos como "**Traducción al francés:**" o "**Traduction en français :**"
                    $translated = preg_replace('/^(Traducción|Translation|Traducción a|Traduction en).*?[:：]\s*/i', '', $translated);
                    
                    // Extraer texto entre comillas francesas « » si existe (es la traducción principal)
                    if (preg_match('/«\s*([^»]+)\s*»/', $translated, $matches)) {
                        $translated = trim($matches[1]);
                    } else {
                        // Extraer solo la primera línea o el texto principal si hay explicaciones
                        // Si hay saltos de línea, tomar solo la primera línea significativa
                        $lines = explode("\n", $translated);
                        $cleanLines = [];
                        foreach ($lines as $line) {
                            $line = trim($line);
                            // Saltar líneas vacías o que sean solo explicaciones
                            if (empty($line) || 
                                preg_match('/^(Traducción|Translation|Traducción|Traduction|Observación|Note|Remarque|\(|En francés|In French|Si |Si ")/i', $line)) {
                                continue;
                            }
                            // Si la línea contiene solo la traducción (sin explicaciones), añadirla
                            if (!preg_match('/\(.*équivalent.*\)|\(.*significa.*\)|\(.*meaning.*\)|→|también se puede|on peut|peut être/i', $line)) {
                                // Si tiene múltiples opciones separadas por "/", tomar la primera
                                if (strpos($line, '/') !== false) {
                                    $parts = explode('/', $line);
                                    $line = trim($parts[0]);
                                }
                                // Eliminar flechas y explicaciones
                                $line = preg_replace('/\s*→.*$/i', '', $line);
                                $line = preg_replace('/\s*\(.*$/i', '', $line);
                                if (!empty(trim($line)) && strlen(trim($line)) > 2) {
                                    $cleanLines[] = trim($line);
                                }
                            } else {
                                // Si tiene explicaciones, extraer solo la parte antes del paréntesis o flecha
                                $line = preg_replace('/\s*\(.*$/i', '', $line);
                                $line = preg_replace('/\s*→.*$/i', '', $line);
                                if (!empty(trim($line)) && strlen(trim($line)) > 2) {
                                    $cleanLines[] = trim($line);
                                }
                            }
                            // Si ya tenemos una línea buena, parar
                            if (count($cleanLines) >= 1 && strlen($cleanLines[0]) > 3) {
                                break;
                            }
                        }
                        
                        if (!empty($cleanLines)) {
                            $translated = $cleanLines[0];
                        } else {
                            // Si no encontramos líneas limpias, intentar extraer el texto principal
                            // Eliminar observaciones entre paréntesis o asteriscos
                            $translated = preg_replace('/\s*\([^)]*\)\s*/', '', $translated);
                            $translated = preg_replace('/\s*\*[^*]*\*\s*/', '', $translated);
                            // Eliminar flechas y todo lo que viene después
                            $translated = preg_replace('/\s*→.*$/i', '', $translated);
                            // Si tiene "/", tomar solo la primera parte
                            if (strpos($translated, '/') !== false) {
                                $parts = explode('/', $translated);
                                $translated = trim($parts[0]);
                            }
                        }
                    }
                    
                    // Limpiar espacios múltiples y saltos de línea
                    $translated = preg_replace('/\s+/', ' ', $translated);
                    $translated = trim($translated);
                    
                    // NO truncar traducciones largas - mantener todo el contenido
                    
                    Log::info('Traducción exitosa', [
                        'source_length' => strlen($text),
                        'target_length' => strlen($translated),
                        'locale' => $targetLocale
                    ]);
                    return $translated;
                }
            }

            Log::warning('Error en traducción', [
                'status' => $response->status(),
                'response' => $response->body(),
                'text' => $text,
                'locale' => $targetLocale
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Excepción en traducción', [
                'message' => $e->getMessage(),
                'text' => $text,
                'locale' => $targetLocale
            ]);
            return null;
        }
    }

    /**
     * Obtiene o genera la traducción de un contenido
     * 
     * @param string $identifier Identificador único del contenido (ej: "apartamento_1_titulo")
     * @param string $text Texto original
     * @param string $targetLocale Idioma destino
     * @param Carbon|null $updatedAt Fecha de última actualización del contenido original
     * @return string Texto traducido o original si falla
     */
    public function getOrTranslate(string $identifier, string $text, string $targetLocale, ?Carbon $updatedAt = null): string
    {
        // Si el idioma es español, devolver el texto original
        if ($targetLocale === 'es' || empty($text)) {
            return $text;
        }

        $cacheKey = "translation_{$identifier}_{$targetLocale}";
        $metaKey = "translation_meta_{$identifier}_{$targetLocale}";

        // Intentar obtener de cache
        $cached = Cache::get($cacheKey);
        $meta = Cache::get($metaKey);

        // Si existe en cache y el contenido no ha cambiado, devolver cache
        if ($cached && $meta) {
            $cachedUpdatedAt = Carbon::parse($meta['updated_at']);
            if ($updatedAt === null || $cachedUpdatedAt->greaterThanOrEqualTo($updatedAt)) {
                // Limpiar prefijos de idioma
                $cleaned = preg_replace('/^(Español|Spanish|Italiano|Italian|Français|French|Deutsch|German|Português|Portuguese|English|Inglés)[:：]\s*/i', '', $cached);
                $cleaned = preg_replace('/^.*?(Español|Spanish|Italiano|Italian|Français|French|Deutsch|German|Português|Portuguese|English|Inglés)[:：]\s*/i', '', $cleaned);
                $cleaned = trim($cleaned);
                // Limpiar estilos inline antes de devolver (por si acaso hay traducciones antiguas)
                $cleaned = $this->cleanInlineStyles($cleaned);
                return $cleaned;
            }
        }

        // Si no existe o ha cambiado, traducir
        // IMPORTANTE: La traducción devuelve solo texto plano, sin HTML
        $translated = $this->translate($text, $targetLocale);

        if ($translated) {
            // Limpiar prefijos de idioma como "Español:", "Italiano:", etc.
            $translated = preg_replace('/^(Español|Spanish|Italiano|Italian|Français|French|Deutsch|German|Português|Portuguese|English|Inglés)[:：]\s*/i', '', $translated);
            $translated = preg_replace('/^.*?(Español|Spanish|Italiano|Italian|Français|French|Deutsch|German|Português|Portuguese|English|Inglés)[:：]\s*/i', '', $translated);
            $translated = trim($translated);
            
            // IMPORTANTE: NO modificar el HTML aquí
            // La traducción es solo texto plano que se insertará en el HTML original
            // El HTML original se preserva completamente en el controlador
            
            // Guardar en cache
            Cache::put($cacheKey, $translated, now()->addDays(30));
            Cache::put($metaKey, [
                'updated_at' => $updatedAt ? $updatedAt->toIso8601String() : now()->toIso8601String(),
                'source_text' => $text,
                'translated_text' => $translated,
                'locale' => $targetLocale
            ], now()->addDays(30));

            // También guardar en archivo JSON para persistencia
            $this->saveToJson($identifier, $targetLocale, $translated, $updatedAt);

            return $translated;
        }

        // Si falla la traducción, devolver el texto original
        return $text;
    }

    /**
     * Guarda la traducción en un archivo JSON
     */
    protected function saveToJson(string $identifier, string $locale, string $translated, ?Carbon $updatedAt): void
    {
        try {
            // Limpiar estilos inline problemáticos ANTES de guardar
            $cleaned = $this->cleanInlineStyles($translated);
            
            $filePath = storage_path("app/translations/{$locale}.json");
            $dir = dirname($filePath);
            
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $translations = [];
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $translations = json_decode($content, true) ?? [];
            }

            $translations[$identifier] = [
                'text' => $cleaned,
                'updated_at' => $updatedAt ? $updatedAt->toIso8601String() : now()->toIso8601String(),
                'translated_at' => now()->toIso8601String()
            ];

            file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Exception $e) {
            Log::error('Error guardando traducción en JSON', [
                'identifier' => $identifier,
                'locale' => $locale,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Limpia estilos inline problemáticos del HTML
     * NOTA: Esta función NO se usa actualmente para preservar el HTML original
     */
    protected function cleanInlineStyles(string $html): string
    {
        // Procesar todos los tags con atributo style de forma más agresiva
        $html = preg_replace_callback('/<([^>]+)\s+style="([^"]*)"([^>]*)>/i', function($matches) {
            $tag = $matches[1];
            $styleContent = $matches[2];
            $rest = $matches[3];
            
            // Eliminar font-weight: 700 o font-weight: bold (múltiples variaciones)
            $styleContent = preg_replace('/font-weight\s*:\s*(700|bold)\s*;?\s*/i', '', $styleContent);
            $styleContent = preg_replace('/font-weight\s*:\s*700\s*;?\s*/i', '', $styleContent);
            $styleContent = preg_replace('/font-weight\s*:\s*bold\s*;?\s*/i', '', $styleContent);
            
            // Si no es un enlace (a), eliminar text-decoration: underline
            if (stripos($tag, 'a ') === false && stripos($tag, 'a>') === false) {
                $styleContent = preg_replace('/text-decoration\s*:\s*underline\s*;?\s*/i', '', $styleContent);
            }
            
            // Limpiar punto y coma dobles o al inicio
            $styleContent = preg_replace('/;\s*;/', ';', $styleContent);
            $styleContent = preg_replace('/;\s*;\s*/', ';', $styleContent);
            $styleContent = preg_replace('/^\s*;\s*/', '', $styleContent);
            $styleContent = trim($styleContent, ' ;');
            
            // Si queda vacío, no incluir el atributo style
            if (empty($styleContent)) {
                return '<' . $tag . $rest . '>';
            }
            return '<' . $tag . ' style="' . $styleContent . '"' . $rest . '>';
        }, $html);
        
        // Limpieza adicional: eliminar cualquier atributo style que solo contenga font-weight problemático
        $html = preg_replace('/\s+style="[^"]*font-weight[^"]*"/i', '', $html);
        
        return $html;
    }

    /**
     * Obtiene traducción desde archivo JSON (sin hacer petición a IA)
     */
    public function getFromJson(string $identifier, string $locale, ?Carbon $updatedAt = null): ?string
    {
        try {
            $filePath = storage_path("app/translations/{$locale}.json");
            
            if (!file_exists($filePath)) {
                return null;
            }

            $content = file_get_contents($filePath);
            $translations = json_decode($content, true) ?? [];

            if (!isset($translations[$identifier])) {
                return null;
            }

            $translation = $translations[$identifier];

            // Verificar si el contenido ha cambiado
            if ($updatedAt && isset($translation['updated_at'])) {
                $cachedUpdatedAt = Carbon::parse($translation['updated_at']);
                if ($updatedAt->greaterThan($cachedUpdatedAt)) {
                    return null; // El contenido ha cambiado, necesita retraducirse
                }
            }

            $text = $translation['text'] ?? null;
            
            // Limpiar estilos inline antes de devolver (por si acaso hay traducciones antiguas)
            if ($text) {
                // Limpiar estilos inline si existen
                if (strpos($text, 'style=') !== false) {
                    $text = $this->cleanInlineStyles($text);
                }
                // Limpiar prefijos de idioma como "Español:", "Italiano:", etc.
                $text = preg_replace('/^(Español|Spanish|Italiano|Italian|Français|French|Deutsch|German|Português|Portuguese|English|Inglés)[:：]\s*/i', '', $text);
                $text = preg_replace('/^.*?(Español|Spanish|Italiano|Italian|Français|French|Deutsch|German|Português|Portuguese|English|Inglés)[:：]\s*/i', '', $text);
            }
            
            return $text;
        } catch (\Exception $e) {
            Log::error('Error leyendo traducción de JSON', [
                'identifier' => $identifier,
                'locale' => $locale,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}

