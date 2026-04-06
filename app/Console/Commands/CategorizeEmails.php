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
    // Crear el cliente OpenAI con la clave API
    $openai = OpenAI::client(env('OPENAI_API_KEY'));

    try {
        $prompt = $this->generatePrompt($input);
        $this->info('Generated prompt: ' . $prompt);

        // Hacer la petición a OpenAI para categorizar el correo utilizando el endpoint correcto para el modelo de chat
        $response = $openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 10,  // Limitar tokens para que la respuesta sea breve
            'temperature' => 0.0, // Reducir la creatividad para respuestas más consistentes
            'n' => 1, // Obtener una sola respuesta
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

        // Validar que la respuesta sea una de las categorías
        if (in_array($category, $input['categories'])) {
            return $category;
        } else {
            Log::error('OpenAI returned an invalid category for email ID: ' . $input['email_subject']);
            throw new \Exception('Invalid category returned by OpenAI.');
        }

    } catch (\Exception $e) {
        // Mostrar cualquier error en la consola
        $this->error('Error in OpenAI request for email ID: ' . $input['email_subject'] . ' - ' . $e->getMessage());
        Log::error('Error in OpenAI request for email ID: ' . $input['email_subject'] . ' - ' . $e->getMessage());
        return null; // Retornar null en caso de error
    }
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
