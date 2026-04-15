<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Email;
use App\Models\CategoryEmail;
use OpenAI\Client; // Asegúrate de tener un cliente OpenAI adecuado
use Illuminate\Support\Facades\Log;
use OpenAI;

class CategorizeEmails extends Command
{
    // El nombre y la firma del comando de consola
    protected $signature = 'emails:categorize';

    // La descripción del comando de consola
    protected $description = 'Categorize emails without category using OpenAI';

    public function __construct()
    {
        parent::__construct();
    }

    // Ejecuta el comando
    public function handle()
    {
        // Obtener correos sin categoría
        $emailsWithoutCategory = Email::whereNull('category_id')->get();

        if ($emailsWithoutCategory->isEmpty()) {
            $this->info('No emails without category found.');
            return;
        }

        $categories = CategoryEmail::pluck('name', 'id')->toArray();

        foreach ($emailsWithoutCategory as $email) {
            if (empty(trim($email->subject)) || empty(trim($email->body))) {
                $this->info('Skipping email ID ' . $email->id . ' due to missing subject or body.');
                continue;
            }

            $email_subject = $this->cleanEmailSubject($email->subject);
            $email_body = strip_tags($email->body);

            $input = [
                'email_body' => $email_body,
                'email_subject' => $email_subject,
                'categories' => array_values($categories),
            ];

            $prompt = $this->generatePrompt($input);
            $this->info('Sending request to OpenAI with prompt: ' . $prompt);

            try {
                $categoryName = $this->getCategorizationFromOpenAI($input);
                $this->info('Email categorized as: ' . $categoryName);

                if ($categoryName && in_array($categoryName, $input['categories'])) {
                    $categoryId = $this->getCategoryIdByName($categoryName);
                    if ($categoryId) {
                        $email->update(['category_id' => $categoryId]);
                        $this->info('Email updated with category ID: ' . $categoryId);
                    } else {
                        $this->info('Category not found for name: ' . $categoryName);
                    }
                } else {
                    $this->info('Invalid category returned for email ID ' . $email->id);
                }
            } catch (\Exception $e) {
                Log::error('Error categorizing email ID ' . $email->id . ': ' . $e->getMessage());
            }
        }
    }

    // Función para generar el prompt
    private function generatePrompt($input)
    {
        return "You need to classify the email into one of the following categories: "
            . implode(", ", $input['categories']) 
            . ". Please analyze the content carefully and determine which category it belongs to. "
            . "\n\nEmail Subject: " . $input['email_subject'] 
            . "\n\nEmail Body: " . $input['email_body'] 
            . "\n\nProvide only one of the above categories as the answer:";
    }

    // Función para hacer la petición a OpenAI
    // Función para hacer la petición a OpenAI
    // Función para hacer la petición a OpenAI
    // Función para hacer la petición a OpenAI
private function getCategorizationFromOpenAI($input)
{
    // Usar AIGatewayService: intenta OpenAI primero, si falla usa Hawkins AI
    // automaticamente con circuit breaker para no quemar peticiones cuando
    // OpenAI esta sin cuota.
    $gateway = app(\App\Services\AIGatewayService::class);

    try {
        $prompt = $this->generatePrompt($input);
        $this->info('Generated prompt: ' . $prompt);

        $response = $gateway->chatCompletion([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 10,
            'temperature' => 0.0,
            'n' => 1,
        ]);

        // Guardar la respuesta completa en un archivo de log
        //$logPath = storage_path('logs/openai_responses.log');
        //file_put_contents($logPath, "Response for email ID: " . $input['email_subject'] . "\n" . json_encode($response, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
        
        // Verificar si la respuesta tiene la estructura esperada
        if (!isset($response['choices']) || empty($response['choices'])) {
            Log::error('No response from OpenAI for email ID: ' . $input['email_subject']);
            throw new \Exception('No response from OpenAI.');
        }

        $category = trim($response['choices'][0]['message']['content']);
        $this->info('Extracted category from ChatGPT response: ' . $category);

        // Validar que la respuesta sea una de las categorías (matching resiliente)
        $categoriaResuelta = $this->extraerCategoriaResiliente($category, $input['categories']);

        if ($categoriaResuelta !== null) {
            return $categoriaResuelta;
        } else {
            Log::error('OpenAI returned an invalid category for email ID: ' . $input['email_subject'] . ' - raw: ' . $category);
            throw new \Exception('Invalid category returned by OpenAI.');
        }

    } catch (\Exception $e) {
        // Mostrar cualquier error en la consola
        $this->error('Error in OpenAI request for email ID: ' . $input['email_subject'] . ' - ' . $e->getMessage());
        Log::error('Error in OpenAI request for email ID: ' . $input['email_subject'] . ' - ' . $e->getMessage());
        return null; // Retornar null en caso de error
    }
}




    /**
     * Extrae una categoría válida de la respuesta cruda del modelo de forma resiliente.
     * Qwen3 y otros modelos no siempre devuelven exactamente una palabra: pueden añadir
     * markdown, prefijos tipo "Categoría:", puntos finales, etc.
     *
     * Estrategia:
     *  1) Limpieza: quitar markdown, comillas, prefijos comunes, puntos finales.
     *  2) Match exacto case-insensitive contra $categorias.
     *  3) Match por substring (palabra dentro de la respuesta), case-insensitive;
     *     si hay varias candidatas, gana la más larga (más específica).
     *  4) Fallback a "Otros" si existe en $categorias.
     *  5) null si nada encaja.
     */
    private function extraerCategoriaResiliente(string $respuestaRaw, array $categorias): ?string
    {
        $limpio = $respuestaRaw;

        // Quitar markdown basico: **, __, *
        $limpio = str_replace(['**', '__'], '', $limpio);
        $limpio = str_replace('*', '', $limpio);

        // Quitar comillas (simples, dobles y tipograficas)
        $limpio = str_replace(['"', "'", '“', '”', '‘', '’', '`'], '', $limpio);

        // Quitar prefijos comunes (case-insensitive)
        $prefijos = [
            'la categoría es',
            'la categoria es',
            'categoría:',
            'categoria:',
            'category:',
            'respuesta:',
            'answer:',
        ];
        foreach ($prefijos as $prefijo) {
            if (stripos($limpio, $prefijo) === 0) {
                $limpio = substr($limpio, strlen($prefijo));
            }
        }

        // Quitar puntos finales y espacios
        $limpio = trim($limpio);
        $limpio = rtrim($limpio, ".,;:!? \t\n\r\0\x0B");
        $limpio = trim($limpio);

        // 1) Match exacto case-insensitive
        foreach ($categorias as $cat) {
            if (mb_strtolower($cat) === mb_strtolower($limpio)) {
                return $cat;
            }
        }

        // 2) Match por substring: buscar categorias que aparezcan dentro de la respuesta
        $limpioLower = mb_strtolower($limpio);
        $candidatas = [];
        foreach ($categorias as $cat) {
            $catLower = mb_strtolower($cat);
            if ($catLower !== '' && mb_strpos($limpioLower, $catLower) !== false) {
                $candidatas[] = $cat;
            }
        }

        if (!empty($candidatas)) {
            // Elegir la mas larga (mas especifica)
            usort($candidatas, function ($a, $b) {
                return mb_strlen($b) - mb_strlen($a);
            });
            $elegida = $candidatas[0];
            Log::debug('CategorizeEmails: match por substring aplicado. Raw: "' . $respuestaRaw . '" -> Categoria: "' . $elegida . '"');
            return $elegida;
        }

        // 3) Fallback a "Otros" si existe
        foreach ($categorias as $cat) {
            if (mb_strtolower($cat) === 'otros') {
                Log::debug('CategorizeEmails: fallback a "Otros" aplicado. Raw: "' . $respuestaRaw . '"');
                return $cat;
            }
        }

        return null;
    }

    private function cleanEmailSubject($subject)
    {
        return mb_decode_mimeheader($subject);
    }

     // Función para obtener el ID de una categoría por nombre
     private function getCategoryIdByName($categoryName)
     {
         $category = CategoryEmail::where('name', $categoryName)->first();
         return $category ? $category->id : null;
     }
}
