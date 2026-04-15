<?php

namespace App\Services;

class OpenAIService
{
    public function __construct()
    {
        // Dejamos el constructor vacio: el gateway se resuelve via el container
        // cuando se llama a sendMessage. No necesitamos instanciar Guzzle ni leer
        // las env de OpenAI aqui; el gateway se encarga de eso y del fallback a
        // Hawkins AI.
    }

    public function sendMessage($message, $functions = [])
    {
        $params = [
            'model' => 'gpt-4', // o el modelo que estés utilizando
            'messages' => [
                ['role' => 'user', 'content' => $message]
            ],
        ];

        // Solo anadir functions / function_call si realmente hay funciones
        // (evitamos enviar arrays vacios que algunos modelos rechazan).
        if (!empty($functions)) {
            $params['functions'] = $functions;
            $params['function_call'] = 'auto';
        }

        return app(\App\Services\AIGatewayService::class)->chatCompletion($params);
    }
}
